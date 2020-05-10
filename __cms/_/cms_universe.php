<?php
    class cms_universe {

        public static $site_filter_callkey = 3;
        public static $site_filter_default = 4;
        public static $site_filter_id = 0;

        public static $puniverse;

        private $puserman = null;
        private $pedit = false;
        private $pdb = null;
        private $psession = null;
        private $poptions = null;
        private $pusermanloaded = false;
        private $pgoogleclient = null;

        public $sites_list = array();
        public $languages = array();
        public $langfiles = array();        

        public $master_document = null;
        public $sitenodes = array();

        private $holding_master_xml_lock = false;
        private $change_mode = null;
        
        public $version_1_1_or_newer = false;

        public function __construct($editmode) {
            $this->pedit = $editmode;
            $this->psession = new cms_session();
            // $this->load_xml(); // delay XML loading until really neccesary for a specific site
            $this->load_langs(true);
            $this->puserman = new cms_userman();
            if ($editmode) {
                // gui editing mode
                $this->load_sites(false);
                $this->load_options();
            } else {
                // faster load for viewing mode
                $this->load_sites(true);
            }
        }

        public function __destruct() {
            if ($this->is_change_mode())
                $this->leave_change_mode(true);
        }

        private function load_options() {
            $this->poptions = new cms_options();
        }
        
        public function reload_options() {
            $this->poptions = new cms_options(true);
        }

        public function userman() {
            if ($this->pusermanloaded != true) {
                $this->puserman->read();
                $this->pusermanloaded = true;
            }
            return $this->puserman;
        }

        public function session() {
            return $this->psession;
        }

        public function options() {
            if ($this->poptions == null) 
                $this->load_options();
            return $this->poptions;
        }

        public function googleAPI() {
            if ($this->pgoogleclient == null) {
                $slink = $this->site_by_filter("id", "9999");   // default admin site
                $entry = $slink->get('20','');    // user admin default element
                if ($entry->flge) {
                    $this->pgoogleclient = new Google_Client();
                    $this->pgoogleclient->setClientId($entry->flgid);
                    $this->pgoogleclient->setClientSecret($entry->flgsec);
                    $ri = $_SERVER['REQUEST_URI'];
                    $w = strpos($ri, cms_config::$cc_cms_path);
                    if ($w !== false) {
                        $ri = substr($ri, 0, $w+strlen(cms_config::$cc_cms_path)+1);
                    }
                    $this->pgoogleclient->setRedirectUri($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].$ri);
                    $this->pgoogleclient->addScope('email');
                    $this->pgoogleclient->addScope('profile');
                }
            }
            return $this->pgoogleclient;
        }

        private function _db() {
            switch(cms_config_db::$cc_db_engine) {
                case "mysql": $this->pdb = new cms_db_mysql(); break;
                case "postgresql": throw new InvalidArgumentException('dbe postgresql not supported yet'); break;
                case "sqlite2": throw new InvalidArgumentException('dbe sqlite 2 not supported yet'); break;
                case "sqlite3": throw new InvalidArgumentException('dbe sqlite 3 not supported yet'); break;
                default:
                    throw new InvalidArgumentException('specified db engine not supported');
            }
        }

        public function db() {
            if ($this->pdb == null) {
                $this->_db();
            }
            return $this->pdb;            
        }

		private function load_langs($usecache) {
			if ($usecache) $lan = cms_filecache::restore_if_valid('universe','languages',cms_config::$cc_cms_xml_file);
			if($lan && $usecache) {
				$this->languages = unserialize($lan);
			} else {
				$this->languages = $this->db()->perform(dbq_list_langs, null);
				if ($usecache) cms_filecache::store('universe','languages',serialize($this->languages));            	
			}					 			
			if ($usecache) $lanf = cms_filecache::restore_if_valid('universe','langfiles',cms_config::$cc_cms_xml_file);
			if($lanf && $usecache) {
				$this->langfiles = unserialize($lanf);
			} else {		
				$this->langfiles = array();
				$d = dir(cms_config::$cc_lang_files_dir);
				if ($d !== FALSE) {
					do {
						$entry = $d->read();
						if (is_file(cms_config::$cc_lang_files_dir.'/'.$entry)) {
							$ld = array();
							if (preg_match('/^cms_lp_([a-z]{2})\.php$/',$entry,$ld)) {
								$this->langfiles[$ld[1]] = true;
							}
						}
					} while($entry !== false);
					$d->close();
				}
				if ($usecache) cms_filecache::store('universe','langfiles',serialize($this->langfiles));
			}						
		}

        private function load_sites($usecache) {
            if ($usecache) {
                $dat = cms_filecache::restore_if_valid('universe','sites',cms_config::$cc_cms_xml_file);
                if ($dat) {
                    $this->sites_list = unserialize($dat);                    
                    return;
                }
            }
            $this->sites_list = array();
            $ar0 = $this->db()->perform(dbq_list_sites, null);
            $ar1 = $this->db()->perform(dbq_list_sitelang, null);
            
            reset($ar0);
            foreach($ar0 as $ar) {
                $ll = array();
                $dhm = array();
                foreach ($ar1 as $ar1e) {
                    if ($ar1e[0] == $ar[0]) {
                        $ll[$ar1e[2]] = $ar1e[1];
                        $dhm[$ar1e[1]] = $ar1e[3];
                    }
                }
                $ar['lang'] = $ll;
                $ar['dhm'] = $dhm;
                $this->sites_list[$ar[0]] = $ar;
            }
            if ($usecache) {
                cms_filecache::store('universe','sites',serialize($this->sites_list));
            }
        }

        public function list_sites() {
            if ($this->pedit) {
                $this->load_sites(false);
            }
            reset($this->sites_list);
            return $this->sites_list;
        }

        public function site_by_filter($filtertype, $filterval) {
            $this->list_sites();
            switch ($filtertype) {
                case 'id': $filtertype = 0; break;
                case 'name': $filtertype = 1; break;
                case 'default': $filtertype = 3; break;
            }
            while(list($uid, $site) = each($this->sites_list)) {
                if ($site[$filtertype] == $filterval) {
                    return $this->site_factory($site[0]);
                }
            }
            return false;
        }
        
        public function xml_version() {
        	if (!($this->master_document instanceof DOMDocument)) {
        		load_xml();
        	}
      		$vi = $this->master_document->firstChild->getAttribute(cms_entry_xml::$VERSION_ATTR);
       		$vn = floatval($vi);
       		return $vi;
        }
        
        private function load_xml() {
            // wczytaj główny plik XML i sprawdź wersję
            $this->master_document = new DOMDocument();    
            $res = $this->master_document->load(cms_config::$cc_cms_xml_file,
            LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING);
            if ((!$res)
            		|| ($this->master_document->firstChild->nodeName != cms_entry_xml::$ROOT_TAG)) {
                throw new RuntimeException('__CMS master XML load failed for '.cms_config::$cc_cms_xml_file);
            }            
            if ($this->xml_version() < 1.1) {
            	cms_entry_xml::switch_to_xml_long_tags();
            } else {
            	$this->version_1_1_or_newer = true;
            }
        }

        private function master_xml_lock() {
            $bc = 0;
            while ((@fopen(cms_config::$cc_cms_lock_file,'x')===FALSE) && ($bc < cms_config::$cc_cms_lock_tries)) {
                $bc++;
                usleep(cms_config::$cc_cms_lock_wait);
            }
            if ($bc == cms_config::$cc_cms_lock_tries) {
                throw new cms_lock_exception("locking failed",0,cms_config::$cc_cms_lock_file);
            }
            $this->holding_master_xml_lock = true;
        }

        private function master_xml_unlock() {
            if ($this->holding_master_xml_lock) {
                @unlink(cms_config::$cc_cms_lock_file);
                $this->holding_master_xml_lock = false;
            }
            else
                throw new cms_lock_exception("invalid operation",1,cms_config::$cc_cms_lock_file);
        }
        
        private function save_master_xml() {
            // save new as backup
            @unlink(cms_config::$cc_cms_xml_file_backup);
            $this->master_document->formatOutput = false;
            $this->master_document->preserverWhiteSpace = true;
            $res = @$this->master_document->save(cms_config::$cc_cms_xml_file_backup);
            if ($res) {
                // replace valid to temp
                $res1 = @rename(cms_config::$cc_cms_xml_file, cms_config::$cc_cms_xml_file_backup.'tmp');
                // replace backup to valid
                $res2 = @rename(cms_config::$cc_cms_xml_file_backup, cms_config::$cc_cms_xml_file);
                // replace temp to backup 
                $res3 = @rename(cms_config::$cc_cms_xml_file_backup.'tmp', cms_config::$cc_cms_xml_file_backup);
            } else {                
                throw new RuntimeException('__CMS error when saving data. Data not saved.');
            }
            if (!($res1 && $res2 && $res3)) {
                throw new RuntimeException('__CMS error when saving data. Data saved.');
            }
            // koniec przetwarzania plikowego
            @(chmod(cms_config::$cc_cms_xml_file_backup, cms_config_db::$cc_datafile_rights));
            @(chmod(cms_config::$cc_cms_xml_file, cms_config_db::$cc_datafile_rights));
        }

        public function enter_change_mode($xml = false, $db = false) {
            $db = true;
            if ($db && strpos($this->change_mode, 'D') === false) {
                $this->db()->begin();
                $this->change_mode .= 'D';
            }
            if ($xml && strpos($this->change_mode, 'X') === false) {
                if (!$this->holding_master_xml_lock) {
                    $this->master_xml_lock();
                    $this->load_xml();
                } 
                $this->change_mode .= 'X';
            }
        }

        public function leave_change_mode($rollback = false) {
            if (strpos($this->change_mode, 'D') !== false) {
                if ($rollback) {
                    $this->db()->rollback();
                } else {
                    $this->db()->commit();
                }
            }
            if (strpos($this->change_mode, 'X') !== false) {
                if ($rollback) {
                    $this->load_xml();
                } else {
                    $this->save_master_xml();
                }
                $this->master_xml_unlock();
            }
            $this->change_mode = null;
        }

        public function is_change_mode() {
            return $this->change_mode != null;
        }
        
        public function auto_rollback($callback_if_in_change_mode = null) {
        	if ($this->is_change_mode()) {
        		leave_change_mode(true);
        		if(is_callable($callback_if_in_change_mode)) {
        			return call_user_func($callback_if_in_change_mode); 
        		}        		
        	}
        }

        public function site_node($id) {
            $myroot = $this->master_document->firstChild->firstChild;
            if ($myroot->nodeName != cms_entry_xml::$SITES_TAG)
                throw new InvalidArgumentException("master xml data structure invalid");
            for($node = $myroot->firstChild; $node!=null; $node = $node->nextSibling) {
                    if ($node->getAttribute(cms_entry_xml::$ID_ATTR) == $id) {
                        return $node;
                    }
            }
            return null;
        }
        
        private function site_factory($id) {
            if (!isset($this->sites_list[$id])) {
                throw new RuntimeException("requested site [$id] not found in datastore");
            }
            if ($this->sites_list[$id][8] == 0) {
            	$this->load_xml();
            	$node = $this->site_node($id);
            	if  ($node instanceof DOMNode) {
	                return new cms_site($id);               
    	        } else {
        	        throw new RuntimeException("requested site [$id] not found in xml");
            	}
            } else {
            	return new cms_site($id);
            }
        }

        public function namefile($filetab, $nofiletab = '') {
            $dir = cms_config::$cc_cms_images_dir;
            $j = 0;
            if ($nofiletab != '') {
                $f = pathinfo($nofiletab);
            } else {
                $f = pathinfo($_FILES[$filetab]['name']);
            }
            // secret conversion ;-)
            @($f['filename'] = cms_path::remove_non_url_chars($f['filename'])); 
			@($f['extension'] = cms_path::remove_non_url_chars($f['extension']));
            // cutlen
            $f['filename'] = substr($f['filename'] , 0, 245 - strlen($f['extension']));
            while(@file_exists($dir.$f['filename'].($j>0?'_'.$j:'').'.'.$f['extension'])) {
                $j = ($j==0?mt_rand(1,99):$j+mt_rand(1,9));
            }
            return $f['filename'].($j>0?'_'.$j:'').'.'.$f['extension'];
        }

        public function all_purpose_uploadfile($filetab) {
        	if (!isset($_FILES[$filetab]))
        		return false;
            $newname = $this->namefile($filetab);
            if (move_uploaded_file($_FILES[$filetab]['tmp_name'], cms_config::$cc_cms_images_dir.$newname)) {
                $res = $newname;
            } else {
                if (is_uploaded_file($_FILES[$filetab]['tmp_name']))
                    @unlink($_FILES[$filetab]['tmp_name']);
                $res = false;
            }
            return $res;
        }

        public function deletefile($filename) {
        	if (cms_config::$cc_cms_delete_files) {
            	@unlink(cms_config::$cc_cms_images_dir.$filename);
        	}
        }

        public static function generate_new_password($nplen = 8) {
            $rn = mt_rand(123456789, 987654321);
            return strtr(substr(base64_encode(sha1($rn.time(), true)),1,$nplen),'abcdefABCDEF01234','{}[]<>@#$%^&:"|;?');
        }

        public static function combine_download_key($site, $path, $field, $lang) {
        	if ($site instanceof cms_site) {
        		$site = $site->id;
        	}
            $ar = array ($site, $path, $field, $lang, "SP)");
            $ar = base64_encode(serialize($ar));
            $md = substr(base64_encode(md5($ar . cms_config::$cc_cms_misc_e1code, true)), 0, 10);
            return strtr($ar.$md,'/+','_-');
        }

        public static function uncombine_download_key($dfk) {
            $dfk = strtr($dfk,'_-','/+');
            $dfk_m = @substr($dfk, -10);
            $dfk_d = @substr($dfk, 0, -10);
            $md = substr(base64_encode(md5($dfk_d . cms_config::$cc_cms_misc_e1code, true)), 0, 10);
            if ($md != $dfk_m)
                return false;
            $ar = @unserialize(base64_decode($dfk_d));
            if ($ar == false)
                return false;
            if ($ar[4] != "SP)")
                return false;
            return array_slice($ar,0,4);
        }

        public function create_site() {
			// TODO implement create_site
        }

        public function delete_site() {
        	// TODO implement delete_site
        }
               
        public static function get_run_time() {
        	return (time() - $GLOBALS['!RTCst']);
        }
        
        public static function get_max_upload_file_size() {
        	$max_upload = (int)(ini_get('upload_max_filesize'));
			$max_post = (int)(ini_get('post_max_size'));
			$memory_limit = (int)(ini_get('memory_limit'));
			return min($max_upload, $max_post, $memory_limit);
        }
        
        public static function run_time_check() {
        	if ($GLOBALS['!RTCmt'] == 0) {
        		return true;
        	}
        	$t = time();
        	$j = $t - $GLOBALS['!RTCct'];
        	if ($j >= $GLOBALS['!RTCmtt']) {
        		// zbliża się przekroczenie limitu, spróbój go rozszerzyć
        		if($GLOBALS['!RTCnsm']) {
        			set_time_limit($GLOBALS['!RTCmt']);
        			$$GLOBALS['!RTCct'] = $t;        	
        			return true; // nie trzeba przerywać pracy
        		} else {
        			return false; // nie udało się, trzeba skończyć
        		}        			
        	}
        	return true;        	
        }
        
        public static function delimiter_map(&$str, $key, $delim) {
        	$str = str_replace('\\'.$delim, $delim, $str);
        }
        
        public static function safesplitter($str, $delim) {
        	$delim = mb_substr($delim,0,1);
        	if ($delim == '/') {
        		$delim = "\\/";
        	}
        	$res = preg_split('/(?<!\\\\)'.$delim.'/u', $str);
       		array_walk($res, array('cms_universe','delimiter_map'), $delim);
       		return $res;
        }
        
        public static function get_relative_path($from, $to)
{
    $from     = explode('/', $from);
    $to       = explode('/', $to);
    $relPath  = $to;

    foreach($from as $depth => $dir) {
        // find first non-matching dir
        if($dir === $to[$depth]) {
            // ignore this directory
            array_shift($relPath);
        } else {
            // get number of remaining dirs to $from
            $remaining = count($from) - $depth;
            if($remaining > 1) {
                // add traversals up to first matching dir
                $padLength = (count($relPath) + $remaining - 1) * -1;
                $relPath = array_pad($relPath, $padLength, '..');
                break;
            } else {
                $relPath[0] = './' . $relPath[0];
            }
        }
    }
    return implode('/', $relPath);
}

    }

?>