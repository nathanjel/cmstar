<?php

abstract class cms_entry extends cms_entry_data {
	
	public static function treesort_cache_prepare($rest) {
		$GLOBALS['!X-cas'] = array();
		$i = -2100000000;
		foreach($rest as $entry) {			
			// store the original order of items by putting in sequence numbers
			// allows to skip finding parent elements during sort, XML elements
			// order is preserved as is (sequence number)
			$GLOBALS['!X-cas'][$entry->pathl] = $entry->dbe?$entry->sortid:($i++);
		}
	}
	
	protected function store_lt_in_db() {
		if ($this->_site_reference->dbe) {
			$para = array($this->_cmsid ? $this->_cmsid : 0, $this->_site_reference->id, $this->pathl, $this->lang,
				$this->slugdata, $this->pathlt, $this->slugdata, $this->pathlt);
			$res = cms_universe::$puniverse->db()->perform(
				dbq_set_lt, $para);
		}
	}
	
	protected function delete_lt_in_db() {
		if ($this->_site_reference->dbe) {
			$spc = cms_universe::$puniverse->db()->convert_lpath_to_dblike($this->pathl.'/**');
			$para = array($this->id, $spc, $this->pathl, $this->lang);
			$res = cms_universe::$puniverse->db()->perform(dbq_delete_lt, $para);
		}
	}
		
	public static function treesort_compare($a, $b) {
		$wd = 0;
		$pla = cms_path::pathlen($a->pathl);
		$plb = cms_path::pathlen($b->pathl);
		$wd = cms_path::wherediff($a->pathl, $b->pathl);			
		if($pla == $plb && $wd == $pla) {
			// same parent, same length, different on last element
			$st = $a->dbe == $b->dbe;
			if ($st) {
				// same technology
				if ($a->dbe) {
					//dbe - use sortid
					return $a->sortid - $b->sortid;
				} else {
					//xml - use cache
					$sia = $GLOBALS['!X-cas'][$a->pathl];
					$sib = $GLOBALS['!X-cas'][$b->pathl];
				}
			} else {
				// one is xml other in DB, xml comes smaller
				return ($a->dbe)?1:-1; 
			}
		} else {
			if ($wd == (min($pla, $plb)+1)) {
				// same parent, different lenght - shorter path first
				return $pla - $plb;
			} else {
				// different lens or parents, use sorting cache to sort this out
				// on highest possible common level
				// xmls go before dbe
				$sia = $GLOBALS['!X-cas'][cms_path::leavepathpart($a->pathl, $wd)];
				$sib = $GLOBALS['!X-cas'][cms_path::leavepathpart($b->pathl, $wd)];
			}			
		}							
		return $sia - $sib;
	}
	
	public static function treesort(&$data) {
		cms_entry::treesort_cache_prepare($data);
		uasort($data, array("cms_entry", "treesort_compare"));
	}	
	
	public static function sort(&$data, $key, $dir = 1) {
		$GLOBALS['__lcollator'] = collator_create('pl_PL');
		collator_set_attribute($GLOBALS['__lcollator'], Collator::NUMERIC_COLLATION, Collator::ON);
		uasort($data, create_function('$a,$b',
			'if (is_array($a->'.$key.')) {'.
				'return '.$dir.' * collator_compare($GLOBALS[\'__lcollator\'], $a->'.$key.'[0], $b->'.$key.'[0]);'.
			'}'.
			'else {'.
				'return '.$dir.' * collator_compare($GLOBALS[\'__lcollator\'], $a->'.$key.', $b->'.$key.');'.
			'}'));;
	}

	public static function textsort(&$data, $key, $dir = 1) {
		uasort($data, create_function('$a,$b',
			'return '.$dir.' * strcasecmp($a->'.$key.', $b->'.$key.');'));
	}
	
	public static function datesort(&$data, $key, $dir = 1) {
		uasort($data, create_function('$a,$b',
			'return '.$dir.' * ($a->'.$key.'[0] - $b->'.$key.'[0]);'));
	}

	public static $field_string = 'a';
	public static $field_table = 's';
	public static $field_multi = 'm';
	
	public static $move_up = -1;
	public static $move_down = -2;
	public static $sort_first = 0;
	public static $sort_last = -1;
	public static $sort_next = -2;
	
	protected $entrymode = -1;
	protected $restored = false;
	
	protected $_cc;
	
	protected $langs;
	
	public function store() {
		if($this->_enc) {
			$this->_cc = $this->content;
			$this->encrypt();	
		}
		$this->_store();
		if ($this->enc) {
			$this->content = $this->_cc;
			unset($this->_cc);
		}
		if (!$this->restored)
			$this->record_change_log('!cre', '');
		$this->store_changelog();
		$this->setup_pathlt();
		if (is_object($this->_site_reference))
			$this->_site_reference->invalidate_cache();
		$__tmp = $this->_site_reference;
		unset($this->_site_reference);
		if ($this->dbe)
			$GLOBALS['__MC__']->set($__tmp->id.'.'.$this->pathl.'.'.$this->lang, serialize($this), cms_config::$cc_memcache_write_flags, 0);
		$this->_site_reference = $__tmp;
	}
	
	public function delete() {
		// remove from cache
		$GLOBALS['__MC__']->delete($this->_site_reference->id.'.'.$this->pathl.'.'.$this->lang);
		// list all children
		$children = $this->_site_reference->get($this->currentry->pathl.'/**', $this->currentry->lang);
		// sort children by path len
		usort($children, 'pathlen_reverse_sort');
		// delete children
		foreach($children as $child) {
			$child->delete();
		}
		$this->_delete();
		$this->delete_lt_in_db();
		$this->record_change_log('!del','');
		$this->store_changelog();

	}
	
	public function move($how, $distance = 1) {
		$this->_move($how, $distance);
		$this->record_change_log('!mov',$distance*($how==cms_entry::$move_up?-1:1));
		$this->store_changelog();
	}
	
	public function restore($pathl, $lang) {
		$this->cleanup();
		$objt = $GLOBALS['__MC__']->get($this->_site_reference->id.'.'.$pathl.'.'.$lang);
        if ($objt !== false) {
        	$obj = unserialize($objt);
            $this->_cmsid = $obj->_cmsid;
            $this->pathl = $obj->pathl;
            $this->lang = $obj->lang;
            $this->typepath = $obj->typepath;
            $this->sortid = $obj->sortid;
            $this->name = $obj->name;
            $this->lname = $obj->lname;
            $this->published = $obj->published;
            $this->content = $obj->content;
            $this->pathlt = $obj->pathlt;
            $this->_enc = $obj->_enc;
            $this->slugdata = $obj->slugdata;
            $this->pictures = $obj->pictures;
            $this->graphs = $obj->graphs;
            $this->fields = $obj->fields;
            $this->langs = $obj->langs;
            $this->dbe = $obj->dbe;
            if (strlen($this->typepath)==0)
            	$this->typepath = $this->pathl;
            if (!$this->pathlt)
                $this->setup_pathlt();
            $GLOBALS['__QS__']['memhits']++;
	    } else {
			$this->_restore($pathl, $lang);
            $GLOBALS['__QS__']['memmiss']++;			
		}
		if($this->_enc) {
			$this->decrypt();
		}
		$this->changelog = array();
		$this->restored = true;
		if ($objt === false) {
			$__tmp = $this->_site_reference;
			unset($this->_site_reference);
			if ($this->dbe)
				$GLOBALS['__MC__']->set($__tmp->id.'.'.$this->pathl.'.'.$this->lang, serialize($this), cms_config::$cc_memcache_write_flags, 0);
			$this->_site_reference = $__tmp;
		}
	}
	
	private function encrypt() {
		$fdata = serialize(
			array(
				$this->fields,
				$this->content,
				$this->graphs,
				$this->pictures
			)
		);
		$key = hash('sha256', cms_config::$cc_encryption_key, true);
		$p = strlen($fdata);
		$res = @mcrypt_encrypt(cms_config::$cc_encryption_alg, $key, $fdata, cms_config::$cc_encryption_mode);
		$this->content = "$p ".$res;		
	}
	
	private function decrypt() {
		$key = hash('sha256', cms_config::$cc_encryption_key, true);
		$q = strpos($this->content, ' ');
		$len = substr($this->content,0,$q);
		$res = @mcrypt_decrypt(cms_config::$cc_encryption_alg, $key, substr($this->content,$q+1), cms_config::$cc_encryption_mode);
		$res = substr($res,0,$len);
		list($this->fields,
			$this->content,
			$this->graphs,
			$this->pictures) = unserialize($res);
	}
	
	private function cleanup() {
		$this->pathl='';
		$this->typepath='';
		$this->lang='';
		$this->sortid='';
		
		$this->name='';
		
		$this->published = 0;
		
		$this->lname='';
		$this->pathlt='';
		
		$this->graphs = array();
		$this->pictures = array();
		$this->fields = array();
		
		$this->content='';
		$this->changelog=array();
	}
	
	abstract protected function _store();
	abstract protected function _delete();
	abstract protected function _move($how, $distance = 1);
	abstract protected function _restore($pathl, $lang);
	abstract protected function setup_pathlt();
	
	abstract public function get_langs();
	abstract protected function add_lang($nl);
	
	public function has_lang($nl) {
		return in_array($nl, $this->get_langs());
	}
	
	public function is_unique_slug() {
		$para = array($this->_site_reference->id, $this->slug, $this->slug, $this->lang);
		$res = cms_universe::$puniverse->db()->perform(dbq_count_lt, $para);
		return ($res[0][0] <= 1);
	}
	
	public function switch_to_lang($nl, $overwrite = false) {
		$oldlang = $this->lang;
		if ($this->has_lang($nl)) {
			$this->restore($this->pathl, $nl);
			if ($overwrite) {
				$this->delete();
				$this->restore($this->pathl, $oldlang);
				$this->add_lang($nl);
				$this->lang = $nl;
			} 		
		} else {
			$this->add_lang($nl);
			$this->lang = $nl;
		}
	}
	
	protected function get_indexed_id() {
		return 0;
	}
	
	public function get_last_mod_date() {
		$sc = $this->get_option('changelog');
		if ($sc && $this->_site_reference->dbe && cms_config::$cc_log_changes) {
			$para = array($this->_site_reference->id, $this->lang,	$this->pathl);
			$res = cms_universe::$puniverse->db()->perform(dbq_last_change, $para);
			return (int)$res[0][0];
		} else {
			return 0;
		}
	}
	
	protected function store_changelog() {
		$sc = $this->get_option('changelog');
		if ($sc && $this->_site_reference->dbe && cms_config::$cc_log_changes) {
			$d = time();
			foreach($this->changelog as $cle) {
				if (!is_scalar($cle[1])) { $cle[1] = serialize($cle[1]); }
				if (!is_scalar($cle[2])) { $cle[2] = serialize($cle[2]); }
				$para = array(defined('CMS_IN_VIEW_MODE') ? $GLOBALS['_userid'] : cms_universe::$puniverse->session()->luser, $d,
					$this->get_indexed_id(),
					$this->_site_reference->id,
					$this->lang,
					$this->pathl,
					$cle[0], $cle[1], $cle[2],
					$_SERVER['REMOTE_ADDR']);
				if (cms_universe::$puniverse->db()->perform(dbq_record_change, $para)) {
					array_shift($this->changelog);
				}
			}
		}
	}
	
	public function relocate($newparent_pathl) {
		if (!$fulldata) {
			$this->restore($this->pathl, $this->lang);
			$this->fulldata = true;
		}
		$oldpath = $this->pathl;
		$nparent = $this->_site_reference->get($newparent_pathl, $this->lang);
		$newpath = $nparent->bornchild($this->type, $this->lang);
		$nentry = $this->_site_reference->get($newpath, $this->lang);
		if($nentry instanceof cms_entry) {
			$this->remap_move($nentry);
			$nentry->_site_reference->invalidate_pathlt($nentry->pathl, $nentry->lang);
			$nentry->setup_pathlt();
			$nentry->store();
			// relations
			$db = cms_universe::$puniverse->db();
			$params = array($nentry->pathl, $this->_site_reference->id, $oldpath, $this->lang);
			$db->perform(dbq_relation_rename0, $params);
			$db->perform(dbq_relation_rename1, $params);
			// children as well
			$children = $this->_site_reference->get($this->currentry->pathl.'/**', $this->currentry->lang);
			// sort children by path len
			usort($children, 'pathlen_reverse_sort');
			// relocate children
			foreach($children as $child) {
				$child->relocate($newpath);
			}
			$this->delete();
		}
		return $newpath;
	}
	
	public function duplicate() {
		$newparent_pathl = $this->parent->pathl;
		if (!$fulldata) {
			$this->restore($this->pathl, $this->lang);
			$this->fulldata = true;
		}
		$oldpath = $this->pathl;
		$nparent = $this->_site_reference->get($newparent_pathl, $this->lang);
		$newpath = $nparent->bornchild($this->type, $this->lang);
		$nentry = $this->_site_reference->get($newpath, $this->lang);
		if($nentry instanceof cms_entry) {
			$this->remap_move($nentry);
			$nentry->name .= $nentry->get_option('copyappend_name');
			$nentry->lname = cms_path::pathlt_conversion($nentry->name);
			// relations
			$db = cms_universe::$puniverse->db();
			$params = array($this->_site_reference->id, $nentry->pathl, $this->lang, $this->_site_reference->id, $oldpath, $this->lang);
			$db->perform(dbq_relation_copy0, $params);
			$db->perform(dbq_relation_copy1, $params);
			// fix path issue
			$nentry->_site_reference->invalidate_pathlt($nentry->pathl, $nentry->lang);
			$nentry->setup_pathlt();
			$nentry->store();
		}
		return $newpath;
	}
	
	public function bornchild($type, $lang, $sortid = -1) {
		return cms_entry::born($this->_site_reference, $this->pathl, $type, $lang, $sortid, $this instanceof cms_entry_db, 0, $this->effectivetypepath);
	}
	
	public function bornsibling($type, $lang, $sortid = -1) {
		if ($sortid == cms_entry::$sort_next) {
			// TODO enable create element next to current
			$sortid = cms_entry::$sort_last;
		}
		return cms_entry::born($this->_site_reference, cms_path::leavepathpart($this->pathl, $this->level), $type, $lang, $sortid, $this instanceof cms_entry_db, 0, 
			$this->level == 0 ? '**' : $this->parent->effectivetypepath);
	}

	protected static function born($siteref, $parentpath, $type, $lang, $sortid, $parentdbe, $newid = 0, $effectiveparenttypepath = '') {
		if ($effectiveparenttypepath == '') {
			$effectiveparenttypepath = $parentpath;
		}	
		$act = explode(',', $siteref->get_option('child_types', $effectiveparenttypepath));
		if (!in_array($type, $act)) {
			$type = $act[0]; // first available type if trying to use unsupported
		}
		if ($type == '**') { 
			$type = '';	// if default type, we put no value
		}		 
		$newpath = $parentpath.(strlen($parentpath)>0?'/':'').$newid;
		if ($type == '') {
			$dbe = $siteref->get_option('dbe', $newpath);
		} else {
			$dbe = $siteref->get_option('dbe', $type);
		}
		if ($parentdbe && !$dbe) {
			throw new Exception("tried to create a non dbe child into dbe parent ". $parentpath);
		}
		if ($dbe) {
			$entry = new cms_entry_db($siteref);
		} else {
			$entry = new cms_entry_xml($siteref);
		}
		$entry->sortid = 0;
		$entry->typepath = $type;
		$entry->pathl = $newpath;
		$entry->lang = $lang;
		$entry->entrymode = $sortid;
		$entry->published = 0;
		$entry->name = $entry->get_option('newelement_name');
		$entry->lname = cms_path::pathlt_conversion($entry->name);
		if ($entry->get_option('hascontent'))
			$entry->content = $entry->get_option('newelement_text');
		if(cms_config::$cc_encryption_enabled && $entry->get_option('encrypt') == 'true') {
			$entry->_enc = true;
		}
		$entry->add_lang($lang);
		$entry->store();		
		return $entry->pathl;
	}
	
	public function __set($a, $b) {
		if ($a == '')
			return;
		$this->record_change_log($a, $b);
		switch ($a) {
			case 'site':
            case '_site_reference':
                return $this->_site_reference = $b;
            case 'text':
                return $this->content = $b;
			case 'pathlt':
				$this->pathlt = $b;
				return;
			case 'content':
				$this->content = $b;
				break;
			case 'published':
				$this->published = $b?1:0;
				break;
			case 'name':
				$on = $this->lname;
				$this->name = $b;
				$this->lname = cms_path::pathlt_conversion($b);
				if ($this->lname != $on) {
					$this->_site_reference->invalidate_pathlt($this->pathl, $this->lang);
					$this->setup_pathlt();
				}
				break;
			case 'slug':
                return $this->slugdata = $b;
			case 'pathl':
			case 'pictures':
			case 'graphs':
			case 'type':
			case 'lang':
			case 'id':
			case 'level':
				throw new Exception('invalid set operation called on entry, cannot set : '.$a);
			default:
				$this->fields[$a] = $b;
		}
	}
	
	protected function record_change_log($field, $newval) {
		if (!cms_config::$cc_log_changes) return;
		$oldval = $this->__get($field);
		$va = substr($field,0,1);
		if ($va == '!') {
			$this->changelog[] = array($field, '', $newval);
		} elseif ($oldval != $newval) {
			$this->changelog[] = array($field, $oldval, $newval);
		}
	}
	
	public function get_option($name) {
		$path = $this->effectivetypepath;
		$ov = $this->_site_reference->get_option($name, $path);
		while ($ov == null || ($ov !== false && strlen($ov) == 0)) {
			// really not found, use inheritance			
			// check if inheritance defined
			$path = $this->_site_reference->get_option('inherit_from', $path);
			if (!$path) {
				// nowhere to inherit from
				break;
			}
			// try to get value from ancestor
			$ov = $this->_site_reference->get_option($name, $path);			
		}
		return $ov;
	}
	
	public function move_picture($id, $how, $distance = 1) {
		$ele = array($this->pictures[$id]);
		// remove
		array_splice($this->pictures, $id, 1);
		$sp = $id + (($how==cms_entry::$move_down?1:-1)*$distance);
		if ($sp<0)
			$sp = 0;
		if ($sp>(count($this->pictures)+1))
			$sp = count($this->pictures)+1;
		// insert
		array_splice($this->pictures, $sp, 0, $ele);
	}
	
	public function update_picture($id, $filetab, $descr, $onlymini = false) {
		$r = $this->picture_upload('', $filetab);
		if ($onlymini) {
			$this->pictures[$id]->minidescr = $descr;			
			if ($r!=false) {
				$this->pictures[$id]->minifile = $r;
				return true;
			}			
		} else {
			$this->pictures[$id]->descr = $descr;			
			if ($r!=false) {
				$this->pictures[$id]->file = $r;
				return true;
			}
		}
		return false;
	}
	
	public function add_picture($after = -1) {
		$j = count($this->pictures);
		if ($j >= $this->get_option('pictures'))
			return false;
		$this->pictures[] = new cms_picture();
		if ($after != -1) {
			$c = count($this->pictures);
			$this->move_picture($c-1, cms_entry::$move_up, $after+$c-1);
		}
	}
	
	public function delete_picture($id, $onlymini = false) {
		if (is_object(@$this->pictures[$id])) {
			if ($onlymini) {
				cms_universe::$puniverse->deletefile($this->pictures[$id]->minifile);
				$this->pictures[$id]->minifile = '';
				$this->pictures[$id]->minidescr = '';
			} else {
				cms_universe::$puniverse->deletefile($this->pictures[$id]->file);
				array_splice($this->pictures, $id, 1);
			}
		}
	}
	
	public function update_graph($kind, $filetab, $descr) {
		if (!($this->graphs[$kind] instanceof cms_picture)) {
			$this->graphs[$kind] = new cms_picture();
		}
		$this->graphs[$kind]->descr = $descr;
		$r = $this->picture_upload($kind, $filetab);
		if ($r!=false) {
			$this->graphs[$kind]->file = $r;
		}
	}
	
	public function erase_graph($kind) {
		cms_universe::$puniverse->deletefile($this->graphs[$kind]->file);
		$this->graphs[$kind] = new cms_picture();
	}
	
	public function __construct($site) {
		if ($site instanceof cms_site)
			$this->_site_reference = $site;
		else
			throw new Exception("invalid operation - create only by supplying site");
		$this->changelog = array();
		$this->pictures=array();
		$this->graphs=array();
		$this->fields=array();
		$this->fulldata = true;
	}
	
	protected function picture_checks($kind, $file) {
		$p = getimagesize($file);
		if (($p[0]+$p[1])<2)
			return false;
		if (!in_array($p[2], array(
			IMAGETYPE_GIF,
			IMAGETYPE_JPEG,
			IMAGETYPE_JPEG2000,
			IMAGETYPE_PNG,
			IMAGETYPE_SWF,
			IMAGETYPE_SWC
		)))
			return false;
			/*		if ($kind == '' && in_array($p[2], array(
			 IMAGETYPE_SWF,
			 IMAGETYPE_SWC)))
			 return false; */
		return $p;
	}
	
	protected function picture_process($kind, $tmpfile, &$destfile, $tmpfileinfo) {
		$folder = cms_config::$cc_cms_images_dir;
		$pa = $this->get_option($kind.'graphparams');
		$wm = $this->get_option($kind.'watermark');
		$pa = explode(',', $pa);
		$watermark = explode(',', $wm);
		$p = $tmpfileinfo;
		$maxx = $pa[0];
		$maxy = $pa[1];
		$oper = $pa[2];
		$filters = $pa[3];
		$quality = $pa[4];
		$wmi = new cms_nthimage();
		$dowatermark = (strlen($watermark[0])>0 && @$wmi->load($folder . $watermark[0]));
		if (!strlen($oper)) {
			$oper = 'AF';
		}
		if (($quality > 100) || ($quality < 10)) {
			$quality = cms_config::$cc_default_jpg_quality;
		}
		if (($p[2] != IMAGETYPE_SWC) && ($p[2] != IMAGETYPE_SWF)) {
			// nie pracuj z swf/swc bo nie umiesz jeszcze!
			$rc = false;
			$rf = false;
			
			if ($oper == 'AA') {
				// nic nie rób - accept any
			}
			if ($oper == 'AF') {
				// accept fixed
				if (($maxx != $p[0]) || ($maxy != $p[1])) {
					return false;
				}
			}
			if ($oper == 'AS') {
				// accept fixed and smaller
				if (($maxx < $p[0]) || ($maxy < $p[1])) {
					return false;
				}
			}
			if ($oper == 'ASSC') {
				// accept fixed and smaller, scale bigger
				if (($maxx < $p[0]) || ($maxy < $p[1])) {
					// is bigger, so scale
					$rc = true;
				}
			}
			if ($oper == 'ASSF') {
				// accept fixed and smaller, scale bigger
				if (($maxx < $p[0]) || ($maxy < $p[1])) {
					// is bigger, so scale
					$rf = true;
				}
			}
			if (($maxx != $p[0]) || ($maxy != $p[1])) {
				// if size different than requested
				// then allow explicit scaling operations
				if ($oper == 'SC') {
					$rc = true;
				}
				if ($oper == 'SF') {
					$rf = true;
				}
			}
			if ($rc || $rf || $dowatermark || strlen($filters)>0) {
				// there are changes to be applied
				$image = new cms_nthimage();
				$image->load($tmpfile);
				if ($rc) {
					$image->resizeProportionalAndClip($maxx, $maxy);
				}
				if ($rf) {
					$image->resizeProportionalToFit($maxx, $maxy);
				}
				if ($dowatermark) {
					$image->watermark($wmi, $watermark[1], $watermark[2], $watermark[3]);
				}
				if (strlen($filters)) {
					$image->filter($filters);
				}
				if ($p[2] == IMAGETYPE_JPEG || $p[2] == IMAGETYPE_JPEG2000) {
					$ext = 'jpg';
					$typ = IMAGETYPE_JPEG;
				} else {
					$ext = 'png';
					$typ = IMAGETYPE_PNG;
				}
				$pi = pathinfo($destfile);
				$destfile = cms_universe::$puniverse->namefile('', $pi['filename'].'.'.$ext);
				$image->save($folder.$destfile, $typ, $quality);
			} else {
				// no changes
				// test size
				if ($oper == 'AA') {
					// nic nie rób - accept any
				}
				if ($oper == 'AF') {
					// accept fixed
					if (($maxx != $p[0]) || ($maxy != $p[1])) {
						return false;
					}
				}
				if ($oper == 'AS') {
					// accept fixed and smaller
					if (($maxx < $p[0]) || ($maxy < $p[1])) {
						return false;
					}
				}
				if ($oper == 'ASSC') {
					// accept fixed and smaller, scale bigger
					if (($maxx < $p[0]) || ($maxy < $p[1])) {
						// is bigger, so reject
						return false;
					}
				}
				if ($oper == 'ASSF') {
					// accept fixed and smaller, scale bigger
					if (($maxx < $p[0]) || ($maxy < $p[1])) {
						// is bigger, so scale
						return false;
					}
				}
				@rename($tmpfile, $folder.$destfile);
			}
		} else {
			// is an swf/swc
			@rename($tmpfile, $folder.$destfile);
		}
		if (is_file($folder.$destfile)) {
			$cp = fileperms($folder.$destfile);
			$cp = $cp & cms_config::$cc_file_upload_and;
			$cp = $cp | cms_config::$cc_file_upload_or;
			chmod($folder.$destfile, $cp);
			return true;
		}
		
		return false;
	}
	
	protected function picture_upload($kind, $filetab) {
		$res = false;
		if (@(is_uploaded_file($_FILES[$filetab]['tmp_name']))) {
			$a = $this->picture_checks($kind, $_FILES[$filetab]['tmp_name']);
			if ($a != false) {
				$newname = cms_universe::$puniverse->namefile($filetab);
				if ($this->picture_process($kind, $_FILES[$filetab]['tmp_name'], $newname, $a))
					$res = $newname;
			}
			@unlink($_FILES[$filetab]['tmp_name']);
		}
		return $res;
	}
	
	public static function combine_file_information($fname, $ofname, $oftype) {
		return $fname . '|' . $oftype. '|' . $ofname;
	}
	
	public static function uncombine_file_information($cfi) {
		$j = explode('|', $cfi);
		if (count($j)>3) {
			$p = '';
			$w = array_slice($j, 2);
			$j = array_slice($j, 0, 2);
			$j[] = join('|', $w);
		}
		return $j;
	}
	
}

?>
