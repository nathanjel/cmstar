<?php
    class cms_entry_db extends cms_entry {

        protected $_cmsid = 0; // for faster db operations
        
        public function get_langs() {
        	return $this->langs;
        }
		
		protected function add_lang($nl) {
			$this->langs[] = $nl;
			if (count($this->langs)>1)
				$this->_cmsid = -1; // copy, not completly new element
		}

        protected function get_indexed_id() {
        	return $this->_cmsid;
        }
        
        protected function _store() {
            // for xtables use delete/insert
            // store basic data, pictures, quick find data(pathlt), content and fields
            // also store history of changes
            // insert on duplicate update might be used, but it would bring 
            // too much DB dependency to the solution
            $up = false;
            if ($this->_cmsid < 1) {
                // check entrymode to establish sortid
                if ($this->_cmsid == 0) {
                	// completly new item
                	$up = true;
                	$tpath = cms_path::leavepathpart($this->pathl, $this->level);
                	if ($tpath == '') {
                		$stpath = '*';
                	} else {
                		$stpath = $tpath . '/*';
                	}
                	$stpath = cms_universe::$puniverse->db()->convert_lpath_to_dbre($stpath);
                	$reqmove = false;
                	// dbq_stor1
                	/* select min(sortid), max(sortid), count(sortid) from cms_entry
                	where site = ? and left(pahtl, ?) = ? and lang */
                	$para = array($this->_site_reference->id, $stpath, $this->lang);
                	$res = cms_universe::$puniverse->db()->perform(
                		dbq_stor1, $para);                    

                	switch($this->entrymode) {
	                    case cms_entry::$sort_first:
    	                    $sid = $res[0][0]-1;
        	                break;
            	        case cms_entry::$sort_last:
                	    case cms_entry::$sort_next:
                    	    $sid = $res[0][1]+1;
                        	break;
	                    default:
    	                    $sid = $res[0][0]-1;
        	                $reqmove = true;
            	    }
                } else {
                	// copy to other language
                	$sid = $this->sortid;
                }
                // insert new entry and content
                // dbq_stor2
                /* insert into cms_entry (`site`, pathl, `lang`, typepath, sortid, `name`, lname, published, `level`)
                values (?, ?, ?, ?, ?, ?, ?, ?, ?)*/
                $para = array($this->_site_reference->id, $this->pathl, $this->lang, $this->typepath, $sid, $this->name,
                $this->lname, $this->published?1:0, $this->level);
                $res = cms_universe::$puniverse->db()->perform(
                dbq_stor2, $para);                               	
                // update pathl!
                // last id matters ?
				$lastid = cms_universe::$puniverse->db()->last_ai(); /// TODO - find correct nextid    
				$this->_cmsid = $lastid; 
				if ($up) {					           
                	$this->pathl = $tpath . (strlen($tpath)?'/':'') . $lastid;
                	/* update cms_entry set pathl = ? where cmsid = ? */
                	// dbq_stor3
                	$para = array($this->pathl, $this->_cmsid);
                	$res = cms_universe::$puniverse->db()->perform(
                	dbq_stor3, $para);
            	}                                 
            } else {
                // update existing entry and content
                // dbq_stor5
                $para = array($this->_site_reference->id, $this->pathl, $this->lang, $this->typepath, $this->sortid, $this->name,
                $this->lname, $this->published?1:0, $this->level, $this->_cmsid);
                $res = cms_universe::$puniverse->db()->perform(
                dbq_stor5, $para);
                /* update cms_entry set
                `site` = ?, pathl = ?, `lang` = ?, typepath = ?, sortid = ?, `name` = ?, lname = ?, published = ?, `level` = ?
                where cmsid = ? */
            }
            if (@$reqmove) {
                $this->_move(cms_entry::$move_down, $this->entrymode);
            }
            // delete fields, pictures, pathlt
            // dbq_stor7
            /* delete from fields where cmsid = ?; delete from pictures where cmsid = ?; delete from pathlt where cmsid = ?; */
            $para = array($this->_cmsid, $this->_cmsid, $this->_cmsid);
            $res = cms_universe::$puniverse->db()->perform(
            dbq_stor7, $para);
            
            if (!$this->_enc) {
            	// if not encrypted, store fields and pictures also
            // store fields - mass insert
            /* cmsid, fname, ptyp, value */
            $fa = array();
            foreach ($this->fields as $n=>$fld) {
            	if ($n == '')
            		// do not save empty keys :)
            		continue;
                if (!is_scalar($fld)) {
                    if (!is_scalar(@$fld[0])) {
                        $fa[] = array($this->_cmsid, $n, cms_entry::$field_table, serialize($fld));
                    } else {
                        foreach($fld as $ff) {
                            $fa[] = array($this->_cmsid, $n, cms_entry::$field_multi, $ff);
                        }
                    }
                } else {
                	if (cms_config::$cc_store_empty_fields || (strlen($fld) > 0)) {
                		// store only if non-empty
                    	$fa[] = array($this->_cmsid, $n, cms_entry::$field_string, $fld);
                	}    
                }
            }
            cms_universe::$puniverse->db()->insert(cms_config_db::$cc_tb_prefix.'fields', array( 
            'cmsid', 'fname', 'ptyp', 'value'), $fa);            
            // store pictures - mass insert
            /* cmsid, key, id, file, desc */
            $fa = array();
            foreach ($this->pictures as $n=>$pic) {
                $fa[] = array($this->_cmsid, '-', $n, $pic->file, $pic->descr);
                if(strlen($pic->minifile) || strlen($pic->minidescr)) {
                	$fa[] = array($this->_cmsid, '=', $n, $pic->minifile, $pic->minidescr);
                }
            }
            foreach ($this->graphs as $n=>$pic) {
                $fa[] = array($this->_cmsid, $n, 0, $pic->file, $pic->descr);
            }
            cms_universe::$puniverse->db()->insert(cms_config_db::$cc_tb_prefix.'pictures', array( 
            'cmsid', 'key', 'id','file', 'desc'), $fa);
            }
            // insert into cms_pathlt values (?, ?, ?, ?, left(?, 120), ?) on duplicate key update 
            // pathltc = left(?, 120), pathlt = ?
            if ($this->pathlt != null) {
                $this->store_lt_in_db();
            } else {
                $this->setup_pathlt();
            }
        }        

        protected function _delete() {
            // remove object from content, entry, fields, pictures, pathlt, store deletion in change history
            if ($this->_cmsid > 0) {
                $para = array($this->_cmsid, $this->_cmsid, $this->_cmsid, $this->_cmsid, $this->_cmsid,
                $this->_site_reference->id, $this->pathl, $this->lang, $this->_site_reference->id, $this->pathl, $this->lang);
                $res = cms_universe::$puniverse->db()->perform(
                dbq_entry_delete, $para);
            } else {
                throw new RuntimeException("tried to execute db delete for entry not existing in db");
            }
            $this->_cmsid = 0;
        }

        protected function _move($how, $distance = 1) {
            // load all, update relevant sort id's
            // do przemyslenia... :(
            // load data into table (cmsid, sortid)
            // find yours
            // move fiels on small internal table
            // update data like all (cmsid, i++) in new order if sortid differs (this will allow)
            $tpath = cms_path::leavepathpart($this->pathl, $this->level);
            if ($tpath == '') {
              	$stpath = '*';
            } else {
               	$stpath = $tpath . '/*';
            }
            $stpath = cms_universe::$puniverse->db()->convert_lpath_to_dbre($stpath);
            $para = array($this->_site_reference->id, $stpath, $this->lang);
	        $res = cms_universe::$puniverse->db()->perform(dbq_move1, $para);
	        $location = -1;
	        $object = null;
	        $al = count($res);
	        for($j = 0; $j<$al; $j++) {
	        	if ($res[$j][0] == $this->_cmsid) {
	        		$location = $j;
	        		$object = $res[$j];
	        		break;
	        	}
	        }
	        if ($location == -1) {
	        	throw new RuntimeException("move operation impossible");
	        }	        
	        
	        $newlocation = $location + (($how == cms_entry::$move_up ? -1 : 1) * $distance);

	        if ($newlocation < 0) {
	        	$newlocation = 0;
	        }
	        if ($newlocation > ($al-1)) {
	        	$newlocation = $al - 1;
	        }
	        
	        if ($location == $newlocation)
	        	return;
	        unset($res[$location]);
	        array_splice($res, $newlocation, 0, array($object));	        
	        for($j = 0; $j<$al; $j++) {
	        	$rw = $res[$j];
	        	if ($rw[1] != $j) {
	        		$para = array($j, $rw[0]);
	        		cms_universe::$puniverse->db()->perform(dbq_move2, $para);
	        		if ($rw[0] == $this->_cmsid) {
	        			$this->sortid = $j;
	        		}
	        	}
	        }
        }

        protected function _restore($pathl, $lang) {
            // load all data from db
            $this->dbe = true;
            $this->langs = array();
            $para = array($this->_site_reference->id, $pathl, $lang, $this->_site_reference->id, $pathl, $lang, $this->_site_reference->id, $pathl, $lang, $this->_site_reference->id, $pathl);
            /*$res = cms_universe::$puniverse->db()->perform(
            dbq_retr1, $para);*/
            $res = cms_universe::$puniverse->db()->perform(
            dbq_retr123, $para);
            /* select cmsid, typepath, sortid, `name`, lname, published, `level`, tstamp 
            from cms_entry where site = ?, pathl = ?, lang = ?*/
            if ($res[0][0]>0) {
                $r = array_shift($res);
                $this->_cmsid=$r[0];
                $this->pathl = $pathl;
                $this->lang = $lang;
                $this->typepath = $r[1]?$r[1]:$this->pathl;
                $this->sortid = $r[2];
                $this->name = $r[3];
                $this->lname = $r[4];
                $this->published = $r[5];
                $this->content = $r[8];
                $this->pathlt = $r[9];
                $this->_enc = $r[10];
                $this->slugdata = $r[11];
                /* select fname, ptyp, value from cms_fields where cmsid = ? */
                // $res = cms_universe::$puniverse->db()->perform(dbq_retr23, array($r[0], $r[0]));
                foreach($res as $row) {
                    if ($row[0] == '0') {
                        switch ($row[3]) {
                            case cms_entry::$field_table:
                                $this->fields[$row[1]] = @unserialize($row[4]);
                                break;
                            case cms_entry::$field_string:
                                $this->fields[$row[1]] = $row[4];
                                break;
                            case cms_entry::$field_multi:
                                $this->fields[$row[1]][] = $row[4];
                                break;
                        }
                    } elseif ($row[0] == '1') {
                    	$j = new cms_picture();
                        	if($row[1]=='-') {
                        		if (isset($this->pictures[$row[2]])) {
                        			$this->pictures[$row[2]]->descr = $row[4];
                        			$this->pictures[$row[2]]->file = $row[3];
                        		} else {
                        			$j->descr = $row[4];
                        			$j->file = $row[3];
                        			$this->pictures[$row[2]] = $j;
                        		}
                        	} elseif ($row[1]=='=') {
                        		if (isset($this->pictures[$row[2]])) {
                        			$this->pictures[$row[2]]->minidescr = $row[4];
                        			$this->pictures[$row[2]]->minifile = $row[3];
                        		} else {
                        			$j->minidescr = $row[4];
                        			$j->minifile = $row[3];
                        			$this->pictures[$row[2]] = $j;
                        		}		
                        	} else {
                        		$j->descr = $row[4];
                        		$j->file = $row[3];
                        		$this->graphs[$row[1]] = $j;	
                        	}
                    } else { // row[0] == 2 - full language list
                    	$this->langs[] = $row[1];
                    }
                }
                /* select pathlt from cms_pathlt where cmsid = ? */
                if (!$this->pathlt)
                    $this->setup_pathlt();
                return true; 
            } 
            return false;
        }

        protected function setup_pathlt() {
            // if we have no id yet...
            if (!($this->_cmsid > 0)) {
                $this->pathlt = null;
                return;
            }
            // 1) test pathlt
            $para = array($this->_cmsid);
            $res = cms_universe::$puniverse->db()->perform(dbq_get_lt, $para);
            if (@$res[0][0] == $this->_cmsid && strlen($res[0][1])) {
                $this->pathlt = $res[0][1];
                $this->slugdata = $res[0][2];
                return;
            }
            // path lt is not found or was found empty :(
            $mpl = cms_path::pathlen($this->pathl);
            if($mpl==1) {
                $this->pathlt = $this->lname;
            } else {
                $pp = cms_path::leavepathpart($this->pathl, $mpl-1);
                $pe = $this->_site_reference->get($pp, $this->lang, true);
                $this->pathlt = $pe->pathlt . '/' . $this->lname;
            }
            // start transaction
            $cm = cms_universe::$puniverse->is_change_mode();
            if (!$cm)
                cms_universe::$puniverse->enter_change_mode(false,true);
            $this->store_lt_in_db();
            if (!$cm) 
                cms_universe::$puniverse->leave_change_mode();
            // end transaction
        }

    }

?>