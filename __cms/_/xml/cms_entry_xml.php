<?php
class cms_entry_xml extends cms_entry {

	private $id;

	public static $ROOT_TAG = 'cmsmaster';
	public static $VERSION_ATTR = 'version';
	public static $SITES_TAG = 'sites';
	
	public static $ENTRY_TAG = 'e';
	public static $SITE_TAG = 's';
	public static $FIELD_TAG = 'f';
	public static $NAME_TAG = 'n';
	public static $TEXT_TAG = 't';
	public static $PICTURE_TAG = 'p';
	
	public static $ID_ATTR = 'i';
	public static $TYPE_ATTR = 't';
	public static $LANG_ATTR = 'v';
	public static $LNAME_ATTR = 's';
	public static $LSLUG_ATTR = 'g';
	public static $NAME_ATTR = 'n';
	
	public static $DATE_ATTR = 'm';
	public static $PUB_LANG_ATTR = 'p';
	
	public static $FILE_ATTR = 'f';
	public static $DESCR_ATTR = 'd';
	public static $KEY_ATTR = 'k';
	public static $L_ATTR = 'l';
	
	public static $MFILE_ATTR = 'q';
	public static $MDESCR_ATTR = 'x';
	
	public static function switch_to_xml_long_tags() {	
		cms_entry_xml::$ENTRY_TAG = 'entry';
		cms_entry_xml::$SITE_TAG = 'site';
		cms_entry_xml::$FIELD_TAG = 'field';
		cms_entry_xml::$NAME_TAG = 'name';
		cms_entry_xml::$TEXT_TAG = 'text';
		cms_entry_xml::$PICTURE_TAG = 'picture';
		
		cms_entry_xml::$ID_ATTR = 'id';
		cms_entry_xml::$TYPE_ATTR = 'type';
		cms_entry_xml::$LANG_ATTR = 'lang';
		cms_entry_xml::$LNAME_ATTR = 'lname';
		cms_entry_xml::$LSLUG_ATTR = 'slug';
		cms_entry_xml::$NAME_ATTR = 'name';
		
		cms_entry_xml::$DATE_ATTR = 'date';
		cms_entry_xml::$PUB_LANG_ATTR = 'pub';
		
		cms_entry_xml::$FILE_ATTR = 'i';
		cms_entry_xml::$DESCR_ATTR = 'd';
		cms_entry_xml::$KEY_ATTR = 'k';
		cms_entry_xml::$L_ATTR = 'l';
	}
	
	public static function switch_to_xml_short_tags() {	
		cms_entry_xml::$ENTRY_TAG = 'e';
		cms_entry_xml::$SITE_TAG = 's';
		cms_entry_xml::$FIELD_TAG = 'f';
		cms_entry_xml::$NAME_TAG = 'n';
		cms_entry_xml::$TEXT_TAG = 't';
		cms_entry_xml::$PICTURE_TAG = 'p';
		
		cms_entry_xml::$ID_ATTR = 'i';
		cms_entry_xml::$TYPE_ATTR = 't';
		cms_entry_xml::$LANG_ATTR = 'v';
		cms_entry_xml::$LNAME_ATTR = 's';
		cms_entry_xml::$LSLUG_ATTR = 'g';
		cms_entry_xml::$NAME_ATTR = 'n';
		
		cms_entry_xml::$DATE_ATTR = 'm';
		cms_entry_xml::$PUB_LANG_ATTR = 'p';
		
		cms_entry_xml::$FILE_ATTR = 'f';
		cms_entry_xml::$DESCR_ATTR = 'd';
		cms_entry_xml::$KEY_ATTR = 'k';
		cms_entry_xml::$L_ATTR = 'l';
	}
	
	public static function langs_from_xml_attribute($attr) {
		return explode(',', $attr);
	}

	private function mynode_easy() {
		$mnode = $this->_site_reference->get_entrynode_pathl($this->pathl, '');
		if ($mnode == null) {
			throw new RuntimeException("could not find entry node for xml_entry : ".$this->pathl);
		}
		return $mnode;
	}
		
	private function mynode() {
		$mnode = $this->_site_reference->get_entrynode_pathl($this->pathl, $this->lang);
		if ($mnode == null) {
			throw new RuntimeException("could not find entry node for xml_entry : ".$this->pathl);
		}
		return $mnode;
	}
	
	public function get_langs() {
		return cms_entry_xml::langs_from_xml_attribute($this->langs);
	}
	
	protected function add_lang($nl) {
		$t = $this->get_langs();
		$t[] = $nl;
		$this->langs = implode(',', $t);
	}
	
	protected function _store() {
		$dom = cms_universe::$puniverse->master_document;
		try { // maybe we are already in the xml
			$n = $this->mynode_easy();
		} catch (Exception $ex) {
			// otherwise a new node is needed or the same node in new language
			// new node, need to create entry
			// path and level must be set to some pseudo-realistic value
			$lpe = strrpos($this->pathl, '/');
			$lpe = (int)substr($this->pathl, $lpe+1);
			$this->pathl = cms_path::leavepathpart($this->pathl, $this->level); // change to parent pathl
			$n = $this->mynode_easy(); // trick - gets parent node!
			$nid = (($lpe>0)?$lpe:cms_xml::find_freeid($n)); // find id or use existing if lang copy
			$r = $dom->createElement(cms_entry_xml::$ENTRY_TAG); // create node
			$r->setAttribute(cms_entry_xml::$ID_ATTR, $nid); // write down new id into node
			$r->setAttribute(cms_entry_xml::$LANG_ATTR, $this->langs); // and language set info (id+lang != key) (pathl+lang = key)
			$this->pathl .= ($this->pathl==''?'':'/').$nid; // set new path
			switch ($this->entrymode) {
				// place node in correct location
				case cms_entry::$sort_first:
					$n->insertBefore($r, $n->firstChild);
					break;
				case cms_entry::$sort_last:
				case cms_entry::$sort_next:
					$n->appendChild($r);
					break;
				default:
					$n->insertBefore($r, $n->firstChild);
				$this->_move(cms_entry::$move_down, $this->entrymode);
			}
			$n = $this->mynode(); // use the correct node (slow but sure!)
		}

		// remove all non-entry child nodes from element, as long they are in current language
		$nc = $n->firstChild;
		$tbr = array();
		while($nc != null) {
			if (($nc->nodeName != cms_entry_xml::$ENTRY_TAG) && ($nc->getAttribute(cms_entry_xml::$L_ATTR) == $this->lang)) {
				$tbr[] = $nc;
			}
			$nc = $nc->nextSibling;
		}
		foreach ($tbr as $tbre)
			$n->removeChild($tbre);

		// write
		// $n->setAttribute('level', $this->level); // remove for speed
		$n->setAttribute(cms_entry_xml::$LANG_ATTR, $this->langs);
		$n->setAttribute(cms_entry_xml::$TYPE_ATTR, $this->typepath);

		$n->setAttribute(cms_entry_xml::$PUB_LANG_ATTR.$this->lang,(string)$this->published);

		// name
		$n_nnode = $dom->createElement(cms_entry_xml::$NAME_TAG);
		$n_nnode->setAttribute(cms_entry_xml::$L_ATTR, $this->lang);
		$n_tnnode = $this->textorcdata($dom,$this->name);
		$n_nnode->appendChild($n_tnnode);

		// lname in language
		$n->setAttribute(cms_entry_xml::$LNAME_ATTR.$this->lang, $this->lname);
		$n->setAttribute(cms_entry_xml::$LSLUG_ATTR.$this->lang, $this->slugdata);

		// text
		$n_vnode = $dom->createElement(cms_entry_xml::$TEXT_TAG);
		$n_vnode->setAttribute(cms_entry_xml::$L_ATTR, $this->lang);		
		$n_tvnode = (strlen($this->content)>0)?$dom->createCDATASection($this->content):$dom->createTextNode('');
		$n_vnode->appendChild($n_tvnode);

		$n->appendChild($n_nnode);
		$n->appendChild($n_vnode);

		if (!$this->_enc) {
			// unencrypted data stored here			
			foreach ($this->pictures as $pi) {
				$ne = $dom->createElement(cms_entry_xml::$PICTURE_TAG);
				$ne->setAttribute(cms_entry_xml::$FILE_ATTR, $pi->file);
				$ne->setAttribute(cms_entry_xml::$DESCR_ATTR, $pi->descr);
				$ne->setAttribute(cms_entry_xml::$MFILE_ATTR, $pi->minifile);
				$ne->setAttribute(cms_entry_xml::$MDESCR_ATTR, $pi->minidescr);
				$ne->setAttribute(cms_entry_xml::$KEY_ATTR, '');
				$ne->setAttribute(cms_entry_xml::$L_ATTR, $this->lang);
				$n->appendChild($ne);
			}

			foreach ($this->graphs as $k=>$pi) {
				$ne = $dom->createElement(cms_entry_xml::$PICTURE_TAG);
				$ne->setAttribute(cms_entry_xml::$L_ATTR, $this->lang);
				$ne->setAttribute(cms_entry_xml::$FILE_ATTR, $pi->file);
				$ne->setAttribute(cms_entry_xml::$DESCR_ATTR, $pi->descr);
				$ne->setAttribute(cms_entry_xml::$KEY_ATTR, $k);
				$n->appendChild($ne);
			}			
			
			foreach ($this->fields as $k=>$v) {
				if ($k == '')
					// do not save empty keys :)
					continue;
				if (cms_universe :: $puniverse->version_1_1_or_newer && ($v == null || (is_scalar($v) && (strlen($v)==0)))) {
					// do not save empty fields into storage when using new file version
					// unless config allows to do so
					if (!cms_config::$cc_store_empty_fields)
						continue;
				}				
				$newnodet = $dom->createElement(cms_entry_xml::$FIELD_TAG);
				$newnodet->setAttribute(cms_entry_xml::$NAME_ATTR, $k);
				$newnodet->setAttribute(cms_entry_xml::$L_ATTR, $this->lang);
				if (is_array($v) || is_object($v)) {
					if (is_object($v) || is_array(@$v[0])) {					
						$newnodet->setAttribute(cms_entry_xml::$TYPE_ATTR,cms_entry::$field_table);
						$newnodecd = $dom->createCDATASection(serialize($v));
						$newnodet->appendChild($newnodecd);
					} else {						
						foreach($v as $vv) {							
							$newnodet = $dom->createElement(cms_entry_xml::$FIELD_TAG);
							$newnodet->setAttribute(cms_entry_xml::$NAME_ATTR, $k);
							$newnodet->setAttribute(cms_entry_xml::$L_ATTR, $this->lang);
							$newnodet->setAttribute(cms_entry_xml::$TYPE_ATTR,cms_entry::$field_multi);
							$newnodecd = $this->textorcdata($dom,$vv);
							$newnodet->appendChild($newnodecd);
							$n->appendChild($newnodet);							
						}
						continue;
					}
				} else {
					$newnodet->setAttribute(cms_entry_xml::$TYPE_ATTR,cms_entry::$field_string);					
					$newnodet->appendChild($this->textorcdata($dom,$v));					
				}				
				$n->appendChild($newnodet);
			}
		}
	}

	private function textorcdata(&$dom, &$v) {
		if (mb_check_encoding($v, 'ASCII') && (strpos($v, '<') === false) && (strpos($v, '>') === false)) {
			// jeśli to jest prosty tekst lub wartości proste, wpisz go jako stoi
			return $dom->createTextNode($v);
		} else {						
			// w przeciwnym razie CDATĘ zastosujcie
			return $dom->createCDATASection($v);
		}		
	}
	
	protected function _delete() {
		// whole element, or only current language
		$cl = $this->get_langs();
		if (count($cl) > 1) {
			// delete one lang
			$cl = array_diff($cl, array($this->lang));
			$this->langs = implode(',', $cl);
			// delete lang data
			$nodes_to_delete = array();
			$n = $this->mynode();
			$n->setAttribute(cms_entry_xml::$LANG_ATTR, $this->langs);
			for($j = $n->firstChild; $j != null; $j = $j->nextSibling) {
				if ($j->getAttribute(cms_entry_xml::$L_ATTR) == $this->lang) {
					$nodes_to_delete[] = $j;
				}
			}
			foreach($nodes_to_delete as $ntd) {
				$n->removeChild($ntd);
			}
		} else {
			// delete complete object (last lang deleted)
			$n = $this->mynode();
			$p = $n->parentNode;
			$p->removeChild($n);	
		}
	}

	protected function _move($how, $distance = 1) {
		$n = $this->mynode();
		$p = $n->parentNode;
		$all = $p->childNodes;
		$len = $all->length;
		if($len == 1)
		return;
		$myord = -1;
		$jk = 0;
		$emap = array();
		$emct = 0;
		$myid = $n->getAttribute(cms_entry_xml::$ID_ATTR);
		for($j=0; $j<$len; $j++) {
			$v1 = $all->item($j);
			if ($v1->nodeName == cms_entry_xml::$ENTRY_TAG) {
				if ($myid == $v1->getAttribute(cms_entry_xml::$ID_ATTR)) {
					$myord = $emct;
				}
				$emap[] = $jk;
				$emct++;
			}
			$jk++;
		}
		$p->removeChild($n);
		$myneword = $myord + ($distance*($how==cms_entry::$move_up?-1:1));
		if ($myneword < 0)
		$myneword = 0;
		if ($myneword > ($emct-1)) {
			$p->appendChild($n);
		} else {
			$p->insertBefore($n, $p->childNodes->item($emap[$myneword]));
		}
	}

	protected function _restore($pathl, $lang) {
		$v11ornewer = (cms_universe :: $puniverse->xml_version() >= 1.1);
		$this->pathl = $pathl;
		$this->lang = $lang;
		$node = $this->mynode();
		if ($node == null)
		return false;
		$this->id = $node->getAttribute(cms_entry_xml::$ID_ATTR);
		// $this->level = $node->getAttribute('level'); skip this
		$this->langs = $node->getAttribute(cms_entry_xml::$LANG_ATTR);
		$this->typepath = $node->getAttribute(cms_entry_xml::$TYPE_ATTR);
		$this->sortid = 0;

		$this->published = (int)($node->getAttribute(cms_entry_xml::$PUB_LANG_ATTR.$lang));
		$this->slugdata = $node->getAttribute(cms_entry_xml::$LSLUG_ATTR.$lang);
		$firstlang = $this->get_langs();
		$firstlang = $firstlang[0];
		for($cnode = $node->firstChild; $cnode != NULL; $cnode = $cnode->nextSibling) {
			// czytaj tylko dane bieżącego języka
			$subnodelang = $cnode->getAttribute(cms_entry_xml::$L_ATTR);
			if ($subnodelang != $lang) {
				continue;
			}
			$cname = $cnode->nodeName;
			if ($cname == cms_entry_xml::$FIELD_TAG) {
				$cc = '';
				$ft = $cnode->getAttribute(cms_entry_xml::$TYPE_ATTR);
				@($cc = $cnode->firstChild->wholeText);
				if (!(cms_universe :: $puniverse->version_1_1_or_newer) && $cc==null) {
					// never allow null values in the old version of file
					$cc = '';
				}
				$nn = $cnode->getAttribute(cms_entry_xml::$NAME_ATTR);
				switch ($ft) {
					case cms_entry::$field_table:
						$this->fields[$nn] = @unserialize($cc);
						break;
					case cms_entry::$field_string:
						$this->fields[$nn] = $cc;
						break;
					case cms_entry::$field_multi:
						$this->fields[$nn][] = $cc;
						break;
				}
			}
			if ($cname == cms_entry_xml::$TEXT_TAG) {
				$this->content = trim(@$cnode->firstChild->wholeText);
			}
			if ($cname == cms_entry_xml::$NAME_TAG) {
				$this->name = trim($cnode->firstChild->wholeText);
				$this->lname = cms_path::pathlt_conversion($this->name);
			}
			if ($cname == cms_entry_xml::$PICTURE_TAG) {
				$p = new cms_picture();
				$k = $cnode->getAttribute(cms_entry_xml::$KEY_ATTR);
				$p->file = $cnode->getAttribute(cms_entry_xml::$FILE_ATTR);
				$p->descr = $cnode->getAttribute(cms_entry_xml::$DESCR_ATTR);
				$p->minifile = $cnode->getAttribute(cms_entry_xml::$MFILE_ATTR);
				$p->minidescr = $cnode->getAttribute(cms_entry_xml::$MDESCR_ATTR);
				if ($k == '') {
					$this->pictures[] = $p;
				} else {
					$this->graphs[$k] = $p;
				}
			}
		}
		$this->setup_pathlt_helper($node);
	}

	private function setup_pathlt_helper($rnode) {
		// maybe there is pathlt in dbe already ?
		// define('dbq_get_lt_by_site_pathl','select pathlt from '.$px.'pathlt where site = ? and pathl = ?');
		$para = array($this->_site_reference->id, $this->pathl);
		if ($this->_site_reference->dbe) {
            $res = cms_universe::$puniverse->db()->perform(dbq_get_lt, $para);
            if ((count($res) > 0) && strlen($res[0][1])) {
                $this->pathlt = $res[0][1];
                $this->slugdata = $res[0][2];
                return;
            }
		}
		// not in dbe... sorry, dig and fetch
		$rpathl = array();
		$rpathlt = array();
		while($rnode->nodeName == cms_entry_xml::$ENTRY_TAG) {
			$rpathl[] = $rnode->getAttribute(cms_entry_xml::$ID_ATTR);
			for($cnode = $rnode->firstChild; $cnode != NULL; $cnode = $cnode->nextSibling) {
				if (($cnode->nodeName==cms_entry_xml::$NAME_ATTR) && ($cnode->getAttribute(cms_entry_xml::$L_ATTR) == $this->lang)) {
					$rpathlt[] = $cnode->firstChild->nodeValue;
					break;
				}
			}
			$rnode = $rnode->parentNode;
		}
		$rpathl=array_reverse($rpathl);
		$rpathlt=array_reverse($rpathlt);
		$this->pathl = join ('/', $rpathl);
		$this->pathlt = join('/', array_map(array('cms_path','pathlt_conversion'), $rpathlt));
		// pathlt can be updated now, since recreated
		$cm = cms_universe::$puniverse->is_change_mode();
        cms_universe::$puniverse->enter_change_mode(false,true);
        $this->store_lt_in_db();
        if (!$cm) 
            cms_universe::$puniverse->leave_change_mode();
	}

	protected function setup_pathlt() {
		$node = $this->mynode();
		$this->setup_pathlt_helper($node);
		$this->store_lt_in_db();
	}
}

?>