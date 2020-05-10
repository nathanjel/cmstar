<?php

class cms_site extends cms_timely {
	
	private $dfs_lang;
	private $dfs_rpat;
	private $dfs_full;
	private $dfs_plt;
	private $dfs_maxlv;
	private $dfs_rtab;
	private $dfs_pf;
	
	public $id;
	
	public $defaultlang;
	public $default;
	public $desc;
	public $name;
	public $route;
	
	public $dbe;
	public $fulldbe;
	public $resourcedomains;
	
	public $langs;
	public $dhm;
	public $options;

	protected $cache = array();
	
	private function xmlnode() {
		return cms_universe::$puniverse->site_node($this->id);
	}
	
	public function allowed_langs() {
		return array_filter(array_values($this->langs));
	}
	
	public function __construct($rel) {
		$data = cms_universe::$puniverse->sites_list[$rel];
		list($this->id, $this->name, $this->desc, $this->default, $this->defaultlang, $this->route, $this->dbe,
			$this->resourcedomains, $this->fulldbe) = $data;
		$this->default = ($this->default != 0 ? true : false);
		$this->dbe = (bool)$this->dbe;
		$this->fulldbe = (bool)$this->fulldbe;
		$this->resourcedomains = preg_split('/[\n\r\t,;]+/',$this->resourcedomains, 0, PREG_SPLIT_NO_EMPTY);
		$this->langs = $data['lang'];
		$this->dhm = $data['dhm'];
		$this->options = new cms_option_accessor($this);
	}
	
	public function store() {
		cms_universe::$puniverse->db()->perform(dbq_alter_site,
			array($this->name, $this->desc, $this->default?1:0, $this->defaultlang, $this->route, $this->dbe, $this->id));
	}
	
	public function relaxed_pathlt_to_pathl($pathlt, $slang, &$leftover) {
		$result = null;
		$ntf = '';
		if ($result == null) {
			// do a split cyclic name search
			// else trip/slap thru xml/db entries, while xml goes first
			// if there is a chance a child is dbe then also db check
			// if no match on level then return leftover and the converted part
			$pathe = array_filter(explode('/',$pathlt));
			if (!$this->fulldbe)
				$fnode = $this->xmlnode();
			$pathw = '';
			$atdbe = $this->fulldbe;
			$fc = count($pathe);
			while(count($pathe)) {
				$ntf = array_shift($pathe);
				if (!$atdbe) {
					$fnode = cms_xml::find_subnode($fnode, array(cms_entry_xml::$LNAME_ATTR.$slang => $ntf), cms_entry_xml::$ENTRY_TAG);
				} else {
					$fnode = null;
				}
				if ($fnode == null) {
					// not found in xml
					// reason to search deeper in db ?
					if ($this->dbe && (true==$this->get_option('dbe', ($pathw==''?'*':$pathw.'/*')))) {
						// search in db
						$res = cms_universe::$puniverse->db()->perform(
							dbq_find_lname, 
							array($pathw, $ntf, $this->id, $slang));
						if (@$res[0][0]) {
							$atdbe = true;
							$pathw .= '/'.$res[0][1];
							$fc--;
						} else {
							// not found 
							break;
						}
					} else {
						// not found at all - error
						break;
					}
				} else {
					// found in xml
					$pathw .= '/'.$fnode->getAttribute(cms_entry_xml::$ID_ATTR);
					$fc--;
				}
			}
			$result = $pathw;                
		}
		$leftover = $ntf . '/' . join('/', $pathe);
		return substr($result,1);
	}
	
	public function pathlt_to_pathl($pathlt, $slang) {
		$result = null;
		// if there are any options in db!
		// run db query first on full path
		if ($this->dbe) {
			$res = cms_universe::$puniverse->db()->perform(dbq_find_pathlt, 
				array($pathlt, $pathlt, $this->id, $slang));
			$j = count($res);
			if ($j == 1) {
				return $res[0][1];
			}
		}
		if ($result == null) {
			// on failure, do a split cyclic name search
			// else trip/slap thru xml/db entries, while xml goes first
			// if there is a chance a child is dbe then also db check
			// if no check on given level, then error
			$pathe = array_filter(explode('/',$pathlt));
			if (!$this->fulldbe)
				$fnode = $this->xmlnode();
			$pathw = '';
			$atdbe = $this->fulldbe;
			$fc = count($pathe);
			while(count($pathe)) {
				$ntf = array_shift($pathe);
				if (!$atdbe) {
					$fnode = cms_xml::find_subnode($fnode, array(cms_entry_xml::$LNAME_ATTR.$slang => $ntf), cms_entry_xml::$ENTRY_TAG);
				} else {
					$fnode = null;
				}
				if ($fnode == null) {
					// not found in xml
					// reason to search deeper in db ?
					if ($this->dbe && (true==$this->get_option('dbe', ($pathw==''?'*':$pathw.'/*')))) {
						// search in db
						$res = cms_universe::$puniverse->db()->perform(
							dbq_find_lname, 
							array($pathw, $ntf, $this->id, $slang));
						if (@$res[0][0]) {
							$atdbe = true;
							$pathw .= '/'.$res[0][1];
							$fc--;
						} else {
							// not found 
							break;
						}
					} else {
						// not found at all - error
						break;
					}
				} else {
					// found in xml
					$pathw .= '/'.$fnode->getAttribute(cms_entry_xml::$ID_ATTR);
					$fc--;
				}
			}
			if ($fc == 0) {
				$result = $pathw;
			}
		}
		return substr($result,1);
	}

	public function invalidate_cache() {
		$this->cache = array();
	}

	public function get($pattern, $lang, $full = true, $dbe = true, $pathlt = true, $datafilter = null, $pathfilter = null, $typefilter = null, $supress_dbe_limit = false, $supress_xml = false) {
		$this->extendnow();
		// returns assoc table (pathl => data (menu or entry))
		// if dbe, then select all from dbe into some table (pathl => entrydata), use later
		// in xml, seek first non-star entry, and deep-dive from there (dfs)
		if ($this->fulldbe)
			$supress_xml = true;
		if ($lang == '') 
			$lang = $this->allowed_langs();
		if (!is_array($lang))
			$lang = array($lang);
		
		// startup values
		$pcre_pattern = cms_path::convert_lpath_to_pcre($pattern);
		if ($pathlt) {
			// TODO must fix this later!
			$full = true;
		}
		
		if (!is_array($typefilter))
			$typefilter = array();
			// TODO implement typefilter
		
		// temp results from xml
		$rtab_tx = array();			           
		
		$patlen = strlen($pattern);
		$starpos = strpos($pattern, '*');
		$spanpos = strrpos($pattern, '/', $starpos-$patlen);
			
		if (($starpos === false) && count($lang)==1) {
			$trycache = $GLOBALS['__MC__']->get($this->id.'.'.$pattern.'.'.$lang[0]);
			if ($trycache !== false) {
				$obj = new cms_entry_db($this);
				$obj->restore($pattern, $lang[0]);	// it will do the "hit" count
				return $obj;
			} else {
				$GLOBALS['__QS__']['memmiss']++;	// so we don't miss it
			}
		}

		if ($pathfilter != null && (strlen($pathfilter) > 0)) {
			$this->dfs_pf = cms_path::convert_lpath_to_pcre($pathfilter); 
		} else {
			$this->dfs_pf = false;
		}
		
		if (!$supress_xml) {
			// read all from xml - almost there
			// starting xml node path
			if ($starpos === false) {
				$parpath = cms_path::leavepathpart($pattern, cms_path::pathlen($pattern)-1);                
			} elseif ($spanpos === false) {
				$parpath = '';
			} else {
				$parpath = substr($pattern, 0, $spanpos);
			}			
			// is it in xml at all ?
				{
					$startnode = $this->get_entrynode_pathl($parpath, ''); // find start node despite of lang
					if ($startnode != null) {
						// run xml dfs
						$this->dfs_lang = $lang;
						$this->dfs_rpat = $pcre_pattern;
						$this->dfs_full = $full || is_array($datafilter) || $pathlt; // TODO suboptimal...
						$this->dfs_plt = $pathlt;
						$this->dfs_maxlv = (strpos($pattern, '**') === false?cms_path::pathlen($pattern):0);
						$this->dfs_rtab =& $rtab_tx;
						$this->xml_dfs($startnode, $parpath);
					}
				}
		}           
		
		$supress_xml == $supress_xml || (count($rtab_tx) == 0);
		
		// count enc
		$tenc = 0;
		// dbe
		if ($dbe) {
			$px = cms_config_db::$cc_tb_prefix;
			$rtab_td = array();
			$likepat = cms_universe::$puniverse->db()->convert_lpath_to_dblike($pattern);
			$regpat = cms_universe::$puniverse->db()->convert_lpath_to_dbre($pattern);
			if ($this->dfs_pf !== false) {
				$delete_regpat = " NOT ( {$px}entry.pathl RLIKE '".cms_universe::$puniverse->db()->convert_lpath_to_dbre($pathfilter)."' ) ";
			} else {
				$delete_regpat = " 1 ";
			}
			// change search method
			$firststar = strpos($pattern, '*');

			if ($pattern == '**') {
				$search_stable_segment = '';
				$search_minlev = 1;
				$search_maxlev = 100;
			} else {
				$search_stable_segment = ($firststar!==false)?substr($pattern, 0, $firststar):$pattern; 
				$search_minlev = count(array_filter(explode('/', $pattern)));
				$search_maxlev = ((strpos($pattern, '**') === false) ? $search_minlev : 100);
			}

			$search_maxlev--;
			$search_minlev--;
			// std data request
			$para = array($this->id, strlen($search_stable_segment), $search_stable_segment, $search_minlev, $search_maxlev, join(',', $lang), cms_config_db::$cc_db_limit);
			// type filter goes here also
			// data filter for db elements would go here but it will later... unfortunatelly
			if (is_array($datafilter)) {
				$idc = 0;
				$idt = 'fdef';
				$pre = '';
				$post = '';
				$aw = '';
				foreach ($datafilter as $field=>$filter) {
					$tn = ($idt . ($idc++));
					$flf = substr($field,0,1);
					if ($flf == 'f') {
						// $aw .= ' AND '.$filter->sqlcond($tn.'.value');
						// we don't need the general filter if we join fully (skipping the left join complexities)
						// we just apply the specific one
						$aw0 = $filter->sqlcond('value');
						$pre .= '(';
						$post .= " join (select cmsid, value from {$px}fields where fname = '$field' and $aw0 ) as $tn using (cmsid) )";
					} elseif ($field == '*') {
						$pre .= '(';
						$c1 = $filter->sqlcond('value');
						$c3 = $filter->sqlcond('name');
						$post .= " left join (select cmsid, count(value) as {$tn}vc from {$px}fields where ( {$c1} ) group by cmsid ) as {$tn}a using (cmsid) )";
						$aw .= " AND ( (not isnull(fdef0vc)) OR {$c3} ) ";
					} elseif ($flf == 'R' || $flf == 'L') { 
						// relacja...
						$pre .= '(';
						list($xs, $xe, $xl) = explode(':',$filter->val0);
						$code = $filter->compare;
						if ($flf == 'L') {
							$post .= " join (select lsite, `left`, llang from {$px}relation where rsite = '$xs' and `right` = '$xe' and rlang = '$xl' and `code` = '$code') 
								as $tn on ( {$tn}.lsite = {$px}entry.site and {$tn}.left = {$px}entry.pathl and {$tn}.llang = {$px}entry.lang) )";
						} else {
							$post .= " join (select rsite, `right`, rlang from {$px}relation where lsite = '$xs' and `left` = '$xe' and llang = '$xl' and `code` = '$code' ) 
								as $tn on ( {$tn}.rsite = {$px}entry.site and {$tn}.right = {$px}entry.pathl and {$tn}.rlang = {$px}entry.lang) )";
						}
					} else {
						$aw .= ' AND '.$filter->sqlcond($field);
					}
				}
				$aw .= ' AND '.$delete_regpat;
				if ($starpos == false) {
					$aw .= ' AND pathl = "'.addcslashes($pattern, "'\\").'" ';
				}
				$c = $supress_dbe_limit ? dbq_search2 : dbq_search_limit2;
				$c = str_replace('__PRE__', $pre, $c);
				$c = str_replace('__POST__', $post, $c);
				$c = str_replace('__WHERE__', $aw, $c);  
				$res = cms_universe::$puniverse->db()->perform(
					$c, $para);
			} else {
				$res = cms_universe::$puniverse->db()->perform(
					str_replace('__WHERE__', $delete_regpat, ($supress_dbe_limit ? dbq_search : dbq_search_limit)) , $para);
			}
			// select pathl, lang, typepath, sortid, name, lname, published, level, tstamp
			// TODO pathlt loading for not-full entry load
			foreach ($res as $ro) {
				$this->extend();
				$j = new cms_entry_data();
				$j->site = $this;
				$j->pathl = $ro[0];
				$j->sortid = $ro[3];
				$j->typepath = $ro[2]?$ro[2]:$ro[0];
				$j->lang = $ro[1];
				$j->published = $ro[6]>0?1:0;
				$j->timestamp = $ro[8];
				$j->name = $ro[4];
				$j->dbe = true;
				$tenc += ($j->_enc = $ro[9]);
				$rtab_td[$this->id.':'.$j->pathl.':'.$j->lang] = $j;
			}
			// merge
			$rtab = array_merge($rtab_tx, $rtab_td);
		} else {
			// xml only!
			$rtab = $rtab_tx;
		}
		// load full elements data if requested
		if ($this->dfs_full || $full || ($tenc>0)) { // this is suboptimal for sure if dbe/xml mixture comes into play...
			foreach($rtab as $pathl=>$me) {
				$this->extend();
				$x = false;
				// if ($this->get_option('dbe', $me->typepath)) {
				if ($me->dbe) {
					if ($me->_enc) {
						$x = true;
					}
					if (!$x && !$full) {
						// do not make full load of db entries if this was not requested explicitly
						// or requested by the dbe encrypt
						continue;
					}
					$obj = new cms_entry_db($this);
				} else {
					// full load of entries (xml) might be needed to enable filter
					$obj = new cms_entry_xml($this);
					$x = true;
				}
				$obj->restore($me->pathl, $me->lang);
				// data filter goes here
				if ($x) { // data filter executed only for xml stuff, db related has filter executed already (despite the encrypted data)
					if ($datafilter != null) {
						$pass = true;
						foreach($datafilter as $field=>$filter) {
							// support for GENERAL filter
							if ($field == '*') {
								$pass = $pass && $filter->call($obj->name.' '.$obj->content.' '.$obj->all_fields_concated());
							} elseif ($field == 'R' || $field == 'L') {
								$pass = true; // TODO - test relation set for XML entry and for ENC
							} else {
								$pass = $pass && $filter->call($obj->$field);
							}
							if (!$pass)
								break;
						}
						if (!$pass) {
							unset($rtab[$pathl]);
							continue;
						}
					}
				}
				if ($full) {
					$rtab[$pathl] = $obj;
				} else {
					$rtab[$pathl]->name = $obj->name;
					$rtab[$pathl]->level = $obj->level;
					$rtab[$pathl]->pathlt = $obj->pathlt;
				}                 
			}
		}
		
		$c = count($rtab);
		if ($starpos === false) {
			if ($c == 0)
				return null;
			else
				return array_shift($rtab);
		}
		
		// search with wildcard - return table always!
		return $rtab;
	}
	
	private function xml_dfs($currnode, $currpath) { 
		for($node=$currnode->firstChild; $node != null; $node = $node->nextSibling) {
			if ($node->nodeName == cms_entry_xml::$ENTRY_TAG) {
				$cpath = $currpath.($currpath==''?'':'/').$node->getAttribute(cms_entry_xml::$ID_ATTR);
				if ($this->dfs_pf !== false && preg_match($this->dfs_pf, $cpath)) {
					continue;
				}
				$ctype = $node->getAttribute(cms_entry_xml::$TYPE_ATTR);
				$clangs = $node->getAttribute(cms_entry_xml::$LANG_ATTR);
				$clevel = cms_path::pathlen($cpath) - 1;
				// same langs searched and available in element
				$common_langs = array_intersect(cms_entry_xml::langs_from_xml_attribute($clangs), $this->dfs_lang); 
				if ((count($common_langs)) && preg_match($this->dfs_rpat, $cpath)) {
					// TODO type filter goes here
					// store all found elements
					foreach($common_langs as $clang) {
						$j = new cms_entry_data();
						$j->site = $this;
						$j->pathl = $cpath;
						$j->typepath = $ctype?$ctype:$cpath;
						$j->lang = $clang;
						$j->timestamp = $node->getAttribute(cms_entry_xml::$DATE_ATTR);
						$firstlang = cms_entry_xml::langs_from_xml_attribute($node->getAttribute(cms_entry_xml::$LANG_ATTR));
						$langcount = count($firstlang);
						$firstlang = $firstlang[0];
						$j->published = ((bool)($node->getAttribute(cms_entry_xml::$PUB_LANG_ATTR.$clang)))?1:0;
						if (!$this->dfs_full) {
							// load name and pathlt if element will not be loaded fully later on
							for($xnode=$node->firstChild; $xnode != null; $xnode = $xnode->nextSibling) {
								$subnodelang = $xnode->getAttribute(cms_entry_xml::$L_ATTR);
								if (($xnode->nodeName == cms_entry_xml::$NAME_TAG) && ($subnodelang == $clang)) {
									$j->name = $xnode->firstChild->nodeValue;
									break;
								}
							}
							if ($this->dfs_plt) {
								// TODO pathlt loading for non-full load
							}                            
						}
						// copy result
						$this->dfs_rtab[$this->id.':'.$j->pathl.':'.$j->lang] = $j;
					}
				}
				if ($this->dfs_maxlv == 0 || ($this->dfs_maxlv - 1) > $clevel ) {
					$this->xml_dfs($node, $cpath);
				}
			}
		}
	}
	
	public function get_option($name, $pathl='**') {
		return cms_universe::$puniverse->options()->get($this->id, $name, $pathl);
	}
	
	public function &get_entrynode_pathl($pathl, $lang) {            
		$node = $this->xmlnode();
		// go on with path
		$pathelements = explode('/', $pathl);
		$ec = count($pathelements);
		if (strlen($pathl) < 1) {
			$ec = 0;
		}
		$ecc = $ec;
		// go
		while(($node != NULL) && ($ecc>0)) {
			$node = cms_xml::find_subnode_rlang($node, array(cms_entry_xml::$ID_ATTR => $pathelements[$ec-$ecc], cms_entry_xml::$LANG_ATTR =>$lang), cms_entry_xml::$ENTRY_TAG);
			$ecc--;
		}
		return $node;
	}
	
	public function invalidate_pathlt($sp, $lang) {
		if ($this->dbe) {
			$spc = cms_universe::$puniverse->db()->convert_lpath_to_dblike($sp.'/**');
			$para = array($this->id, $spc, $sp, $lang);
			$res = cms_universe::$puniverse->db()->perform(dbq_invalidate_lt, $para);
		}
	}
	
}

?>
