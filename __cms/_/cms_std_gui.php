<?php

function pathlen_reverse_sort($a, $b) {
	$x = cms_path::pathlen($a->pathl);
	$y = cms_path::pathlen($b->pathl);
	return $y - $x;
}

class cms_std_gui extends cms_tpl_gui {
	
	public $acts;
	public $apc = false;
	public $enforce_deftab = false;

	public function dynamic_fields($fields) {
		// initialize
		$spin = array();
		foreach($fields as $field) {
			$spin[] = cms_universe::safesplitter($field, ',');
		}
		$spout = array();
		// process groups
		for($index = 0; $index<count($spin); $index++) {
			$curr = $spin[$index];
			if ($curr[2] == 'spacergroup') { 
				// mamy grupe
				$groupstart = $index;
				$groupstop = $index + 1;
				while(($groupstop < count($spin)) && (strpos($spin[$groupstop][2], 'spacer') === false)) {
					$groupstop++;
				}
				$groupstop -= 1;
				$groupsize = $groupstop - $groupstart;
				if ($groupsize > 0) {
					// to jest jakaś faktycznie istniejąca grupa
					$spin[$groupstart][2] = 'spacer';
					// licznik grup
					$indexes = array();
					$max_existing_element = -1;
					$max_elements = 1000;
					// pole i obecność
					$refname = $spin[$groupstart+1][1];
					for($i = 0; $i<$max_elements; $i++) {
						$testname = $refname.$i;
						$testvalue = $this->currentry->{$testname};
						$exists = 0;
						if (is_array($testvalue)) {
							$exists += count($testvalue);
						} else {
							$exists += strlen($testvalue);
						}
						if ($exists) {
							$indexes[] = $i;
							if ($i > $max_existing_element)
								$max_existing_element = $i;
						}
					}
					// generate fields
					$indexes[] = $max_existing_element+1;
					for($i = 0; $i<count($indexes); $i++) {
						for($j = $groupstart; $j<=$groupstop; $j++) {
							$entry = $spin[$j];
							$entry[1] .= $indexes[$i];
							$spout[] = $entry;
						}
					}
				}
				$index += $groupsize;
			} else {
				$spout[] = $curr;
			}
		}
		// complete 
		$return = array();
		$escaper = function($v) {
			return addcslashes($v, ',');
		};
		foreach ($spout as $f) {
			$return[] = join(',', array_map($escaper, $f));
		}
		return $return;
	}
	
	public function actions() {
		// if ($_POST['actioncode'] == "logout") {
		// 	$this->login->logout();
		// 	return;
		// }
		if ($_POST['action'] == "accept_rules") {
			// very special condition
			$rdata = $this->site->get_option('rules_acceptance_control');
			if ($rdata) {
				$robj = $this->site->get($rdata, $this->lang);
				if ($robj instanceof cms_entry) {
			        $cv = cms_path::convert_lpath_to_pcre($robj->f_rules_apply);
			        if (preg_match($cv, $this->currpath)) {
						cms_universe::$puniverse->enter_change_mode(false,true);
			        	$this->currentry->{$robj->f_rcf_flag} = 1;
			        	$this->currentry->{$robj->f_rcf_date} = array(time());
			        	$this->currentry->store();		    
						cms_universe::$puniverse->leave_change_mode();
			        }
				}
		    }
		    $_POST['action'] = '';
		}
		$this->actions_prepare_check();
		// are there any actions at all ?
		if (count($this->acts)) {
			// is there an entry to do actions on behalf of ?
			if (!$this->currentry instanceof cms_entry) {
				return;
			}
			if ($this->currentry instanceof cms_entry_xml) {
				cms_universe::$puniverse->enter_change_mode(true,$this->currsite->dbe);
			}
			if ($this->currentry instanceof cms_entry_db) {
				cms_universe::$puniverse->enter_change_mode(false,true);
			}
			try {
				$this->std_a();
				$this->ext_a();
				cms_universe::$puniverse->leave_change_mode();
			} catch (Exception $e) {
				cms_universe::$puniverse->leave_change_mode(true);
				throw $e;
			}
		}
	}
	
	protected function translate() {
		// to load valid JS language
		$this->_template->repstr('SITENAME', $this->currsite->name);
		$this->_template->repstr('LANGCODE', cms_lp::get_session_language_code());
		
		$this->_template->repstr('Użytkownik:', __('Użytkownik:'));
		$this->_template->repstr('Nazwa Użytkownika', __('Nazwa Użytkownika'));
		$this->_template->repstr('Wyloguj się', __('Wyloguj się'));
		$this->_template->repstr('Podgląd strony', __('Podgląd strony'));
		$this->_template->repstr('Ostatnie logowanie:', __('Ostatnie logowanie:'));
		$this->_template->repstr('Wersja językowa:', __('Wersja językowa:'));
		$this->_template->repstr('Zwiń listę', __('Zwiń listę'));
		$this->_template->repstr('ZAPISZ', __('ZAPISZ'));
		$this->_template->repstr('Filtr elementów :', __('Wyszukaj:'));
		$this->_template->repstr('Rodzaj', __('Rodzaj'));
		$this->_template->repstr('Grafika', __('Grafika'));
		$this->_template->repstr('Akcja', __('Akcja'));
		$this->_template->repstr('Zdjęcie', __('Zdjęcie'));
		$this->_template->repstr('Opis', __('Opis'));
		$this->_template->repstr('Opis / Plik', __('Opis / Plik'));
		$this->_template->repstr('Przenieś', __('Przenieś'));
		$this->_template->repstr('Akcja', __('Akcja'));
		$this->_template->repstr('Przesuń do góry', __('Przesuń do góry'));
		$this->_template->repstr('Przesuń w dół', __('Przesuń w dół'));
		$this->_template->repstr('Edycja elementu', __('Edycja elementu'));
		$this->_template->repstr('Zapisanie wskazanego elementu', __('Zapisanie wskazanego elementu'));
		$this->_template->repstr('Podgląd na pełnym ekranie', __('Podgląd na pełnym ekranie'));
		$this->_template->repstr('Usunięcie wskazanego elementu', __('Usunięcie wskazanego elementu'));
		
		$this->_template->repstr('Dodaj wiele zdjęć', __('Dodaj wiele zdjęć'));
		$this->_template->repstr('Dodaj zdjęcie', __('Dodaj zdjęcie'));
		
		$this->_template->repstr('Wyślij zdjęcia', __('Wyślij zdjęcia'));
		$this->_template->repstr('Nazwa pliku', __('Nazwa pliku'));
		$this->_template->repstr('Rozmiar pliku', __('Rozmiar pliku'));
		$this->_template->repstr('Status', __('Status'));
		$this->_template->repstr('Brak plików', __('Brak plików'));
		$this->_template->repstr('Dodaj', __('Dodaj'));
		$this->_template->repstr('Usuń element', __('Usuń element'));
		$this->_template->repstr('W górę', __('W górę'));
		$this->_template->repstr('W dół', __('W dół'));
		
		$this->_template->repstr('Legenda:', __('Legenda:'));
		
		$this->_template->repstr('Komunikat w kolorze czerwonym oznacza błąd wypełnienia, niepoprawne działanie lub awarię.', 
			__('Komunikat w kolorze czerwonym oznacza błąd wypełnienia, niepoprawne działanie lub awarię.'));
		$this->_template->repstr('Komunikat w kolorze niebieskim oznacza potwierdzenie lub sukces działania.', 
			__('Komunikat w kolorze niebieskim oznacza potwierdzenie lub sukces działania.'));
		
	}
	
	public function display() {
		$this->std_d();
		if ($this->currentry != null) {
			$this->ext_d();
		} else {
			$this->_template->hideContent();
		}		
		$this->translate();
		return $this->_template;
	}
	
	public static function valid_date($j) {
		$time = strtotime($j);
		$k = date(cms_config::$cc_php_date_format, $time);
		return ($j == $k);
	}
	
	public function std_a() {
		$_k = 0;
		foreach($this->acts as $act) {
			if ($act == '')
				continue;
			if ($this->currentry == null)
				break;
			$id = $_POST['id'];
			switch($act) {
				case 'autosave':
					$this->_template->showMessage(cms_template::$msg_succ,'931', array(round($this->currentry->get_option('auto_save_on_idle')/60)));
					break;
				case 'moveup':
					$this->currentry->move(cms_entry::$move_up,1);
					break;
				case 'movedown':
					$this->currentry->move(cms_entry::$move_down,1);
					break;
				case 'update': $_k++;
				$this->currentry->name = $_POST['name'];
				$this->currentry->slugdata = join('/',array_map(array('cms_path','pathlt_conversion'),explode('/',$_POST['slug'])));
				@$this->currentry->content = $_POST['ctext'];
				// update pictures and descriptions
				foreach ($this->currentry->pictures as $id=>$obj) {
					foreach(array(''=>false,'mini'=>true) as $ministring=>$mini) {
						if (!isset($_POST['filelabel'.$id.$ministring])) {
							// skip items, if there is no textfield available for them
							continue;
						}
						$filetab = "pxxfile".$id.$ministring;
						// was there an upload
						$upload = is_uploaded_file(@$_FILES[$filetab]['tmp_name']);
						$result = $this->currentry->update_picture($id, $filetab, $_POST['filelabel'.$id.$ministring], $mini);
						if (!$result) {
							// file not provided
							if (isset($_POST['filename'.$id.$ministring])) {
							// check file name matches
							$vn = ($mini?'minifile':'file');
							$nfname = realpath($_POST['filename'.$id.$ministring]);
							$efname = realpath(cms_config::$cc_cms_images_dir.$obj->$vn);						
							if (($nfname != $efname) && ($_POST['filename'.$id.$ministring] != '')) {
								if ($nfname != false && is_file($nfname) && getimagesize($nfname)) {
									// 	image override by filename
									$n = cms_universe::get_relative_path(realpath(cms_config::$cc_cms_images_dir), $nfname);
									$this->currentry->pictures[$id]->$vn = $n;
									// good file
									$this->_template->showMessage(cms_template::$msg_succ,'975', array(
										$_POST['filename'.$id.$ministring]));
								} else {
									// bad file
									$this->_template->showMessage(cms_template::$msg_error,'969', array(
										$_POST['filename'.$id.$ministring],
										realpath(cms_config::$cc_cms_images_dir)));
								}
							} else {
								// no file								
							}
							}
						} else {
							$this->_template->showMessage(cms_template::$msg_succ,'965');
						}
						if (!$result && $upload) {
							$this->_template->showMessage(cms_template::$msg_error,'966', array($_FILES[$filetab]['name']));
						}
					}
				}
				// update fields :-)
				$ftable =      $this->currentry->get_option('fieldtable_b');
				$ftable .= ';'.$this->currentry->get_option('fieldtable');
				$ftable .= ';'.$this->currentry->get_option('fieldtable_');
				$ftable .= ';'.$this->currentry->get_option('fieldtable__');
				$ftable .= ';'.$this->currentry->get_option('fieldtable_a');

				$ignored_fields = $this->currentry->get_option('guiignores');
				$ignored_fields = array_filter(explode(',',$ignored_fields));

				$fields = $this->dynamic_fields(array_filter(cms_universe::safesplitter($ftable,';')));
				foreach($fields as $fnumber=>$fdef) {
					$this->handle_field($fdef, $ignored_fields);
				}

				break;
				case 'dbe_preview':
					// preview other entry (current or as marked by ID with parent check)
					$nid = $_POST['id'];
					if ($nid == '0') {
						$dc = $this->currentry;
					} else {
						$nide = explode('.', $nid);
						$pathe = strtr($nide[1],'-','/');
						if ($this->currsite->id == $nide[0]) {
							$dc = $this->currsite->get($pathe, $this->currlang);
							if ($this->currentry->pathl != $dc->parent->pathl)
								die();
						}
					}
					
							$newlocation = '../'.$dc->slug;
                                                        if ($dc->get_option('preview_unpublished')) {
                                                                $preview = md5($dc->site->id . $dc->pathl . $dc->lang . cms_config::$cc_cms_misc_e1code);
                                                                $newlocation .= "?m=$preview";
                                                        }
                                                        header("Location: $newlocation");
                                        die();
					break;
				case 'dbe_delete':
					// delete other entry (as marked by ID)
					$nid = $_POST['id'];
					$nide = explode('.', $nid);
					$pathe = strtr($nide[1],'-','/');
					if ($this->currsite->id == $nide[0]) {
						$dc = $this->currsite->get($pathe, $this->currlang);
						if ($this->currentry->pathl == $dc->parent->pathl)
							$dc->delete();
					}
					break;
				case 'dbe_dup':
					// duplicate other entry (as marked by ID)
					$nid = $_POST['id'];
					$nide = explode('.', $nid);
					$pathe = strtr($nide[1],'-','/');
					if ($this->currsite->id == $nide[0]) {
						$dc = $this->currsite->get($pathe, $this->currlang);
						if ($this->currentry->pathl == $dc->parent->pathl)
							$dc->duplicate();
					}
					break;
				case 'delete':					
					// delete current entry
					$ref = $this->currentry;
					$this->enforce_deftab = true;
					$old_path_len = cms_path::pathlen($this->currpath);
					$this->currpath = '';
					if ($old_path_len <= 1) {
						$this->currentry = null;
					} else {
						if ($this->currentry->options->back_to_referer) {
							$sa = new cms_session();
							$this->currpath = $sa->ppath;
						}
						if (strlen($this->currpath)==0) {
							$this->currpath = cms_path::leavepathpart($ref->pathl, $old_path_len-1);
						}
						$this->currentry = $this->currsite->get($this->currpath, $this->currlang);
						$navigate = true;
					}
					$ref->delete();
					break;
				case 'addtop':
					$newtype = $_POST['new-sibling-type'];
					$this->currpath = $this->currentry->bornsibling($newtype, $this->currlang);
					$this->currentry = $this->currsite->get($this->currpath, $this->currlang, true);
					$this->_template->setvcode($this->currentry->pathl.$this->curruser->name);				
					break;
				case 'add':
					$this->enforce_deftab = true;
					$newtype = $_POST['new-child-type'];
					$this->currpath = $this->currentry->bornchild($newtype, $this->currlang);
					$this->currentry = $this->currsite->get($this->currpath, $this->currlang, true);
					$this->_template->setvcode($this->currentry->pathl.$this->curruser->name);				
					break;
				case 'addfoto':
					$_k++;
					$this->currentry->add_picture();
					break;
				case 'removefoto':
				case 'removefotom':
					$_k++;
					if ($act == 'removefotom') {
						$id = substr($id,0,-4);
					}
					$this->currentry->delete_picture($id, $act == 'removefotom');
					break;
				case 'updatefoto':
				case 'updatefotom':
					$_k++;					
					$filetab = "pxxfile".$id;
					$text = $_POST['filelabel'.$id];
					if (@(is_uploaded_file($_FILES[$filetab]['tmp_name']))) {
						$re = true;
					} else {
						$re = false;
					}
					if ($act == 'updatefotom') {
						$id = substr($id,0,-4);
					}
					$result = $this->currentry->update_picture($id, $filetab, $text, $act == 'updatefotom');
					if ($re) {
						if ($result) {
							$this->_template->showMessage(cms_template::$msg_succ,'965');
						} else {
							$this->_template->showMessage(cms_template::$msg_error,'966');
						}
					}
					break;
				case 'fotoup':
					$_k++;
					$this->currentry->move_picture($id, cms_entry::$move_up, 1);
					break;
				case 'fotodown':
					$_k++;
					$this->currentry->move_picture($id, cms_entry::$move_down, 1);
					break;
				case 'removegraph':
					$_k++;
					$this->currentry->erase_graph($id);
					break;
				case 'updategraph':
					$_k++;
					$this->currentry->update_graph($id, 'graphxx_'.$id, '');
					break;
				default:
					// allowed languages to create in
					$entrylangs = $this->currentry->get_langs();
				$createinlangs = array_diff($this->currsite->langs, array($this->currlang));
				// asked to create maybe ?
				foreach($createinlangs as $cil) {
					if ($act == 'create_lang_'.$cil) {
						$this->enforce_deftab = true; // go to the default tab, instead of keeping the lang one open
						$overwrite = strlen(@$_POST['create_lang_set_overwrite'])>0;
						$deep = strlen(@$_POST['create_lang_set_deep'])>0;
						$oldlang = $this->currlang;					
						$this->currlang = $cil;
						$this->currentry->switch_to_lang($cil, $overwrite);							
						$this->currentry->store();
						if ($deep) {
							// get all objects descendant
							$children = $this->currsite->get($this->currentry->pathl.'/**', $oldlang, true, true, true, null, null, null, true);
							foreach($children as $child) {
								$hl = $child->has_lang($cil);
								$child->switch_to_lang($cil, $overwrite);
								if (!$hl || ($hl && $overwrite))
									$child->store();
							}
						}
						break;
					}
				}
			}
		}
		if ($_k && $this->currentry!=null) {
			if ($this->currentry->get_option('timestamp')) {
				$this->currentry->f_timestamp = array($_SERVER['REQUEST_TIME']);
			}
			$this->currentry->store();		
		}
		if ($navigate) {
			cms_universe::$puniverse->leave_change_mode();
			header("Location: ".str_replace('/', '-', $this->currentry->pathl));
			die();
		}
	}
	/**
	 * 
	 */protected function handle_field($fdef, $ignore_list = array()) {
		// skip empty
		if ($fdef == '')
			return;
		$fdef = cms_universe::safesplitter($fdef,',');
		// skip disabled fields
		if ($this->input_field_was_disabled($fdef[1]))
			return;
		// skip ignored fields
		if (in_array($fdef[1], $ignore_list))
			return;
		// process here
		$params = cms_universe::safesplitter(cms_options::get_data($fdef[2]),':');
		$value = @$_POST[$fdef[1]];
		switch(cms_options::get_operation($fdef[2])) {
			case 'span': return;
			case 'file':
				// load file (if supplied)
				if ($fn = cms_universe::$puniverse->all_purpose_uploadfile($fdef[1])) {
					$ofname = $_FILES[$fdef[1]]['name'];
					$oftype = $_FILES[$fdef[1]]['type'];
					if (!$oftype) {
						$oftype = "application/octet-stream";
					}
					$cfi = cms_entry::combine_file_information($fn, $ofname, $oftype);
					$this->currentry->{$fdef[1]} = $cfi;
				}
				if (@$_POST['delfile_'.$fdef[1]] == 'x') {
					$this->currentry->{$fdef[1]} = '';
				}
				break;
			case 'spacer':
				break;
			case 'textic':
				if (@$_POST[$fdef[1].md5($fdef[1])] == $fdef[1]) {
					$this->currentry->{$fdef[1]} = $value;
				}
				break;
			case 'date':
				if ($value!='') {
					$dates = explode(',', $value);
					$j = count($dates);
					$dates = array_filter($dates, array('cms_std_gui','valid_date'));
					if (count($dates) > $params[0]) {
						$dates = array_slice($dates, 0, $params[0]);
					}
					$k = count($dates);
					if ($j>$k)
						$this->_template->showMessage(cms_template::$msg_error,'940', array($fdef[0], $k));
					$dates = array_map('strtotime', $dates);
					$this->currentry->{$fdef[1]} = $dates;
				} else {
					$this->currentry->{$fdef[1]} = array();
				}
				break;
			case 'table_fs':
				// inaczej seradź
				$rl = max(1,$params[0]);
				$cl = max(1,$params[1]);
				$t0 = array();
				for($r = 0; $r<$rl; $r++) {
					$row = array();
					for ($c = 0; $c<$cl; $c++) {
						$col = $_POST["t2d_{$fdef[1]}_r{$r}c{$c}"];
						$row[]=$col;
					}
					$t0[] = $row;
				}
				$this->currentry->{$fdef[1]} = $t0;
				break;
			case 'relate':
				$n = (int)$params[4];
				if ($n<1)
					$n = 1;
				$techno = cms_std_gui::relate_techno($params[6]);
				$rac = $this->create_relation_accessor($params);
				if ($techno == 'tag') {
					$selected = array();
					$qnew = array();
					if (is_array($_POST[$fdef[1]]))
					foreach($_POST[$fdef[1]] as $tagname) {
						$fx = new cms_filter();
		                $fx->compare='$=';
				        $fx->val0= $tagname;
		    			$rac->add_filter('name', $fx);
						$litems = $rac->all_possible();
						if (count($litems) == 1) {
							$xentry = array_pop($litems);
							$selected[] = $xentry->_site_reference->id.':'.$xentry->pathl.':'.$xentry->lang;											
						} else {
							$qnew[] = $tagname;
						}
					}
					$parent_path = $params[1];
					$lastp = strpos($parent_path, '*');
					if($lastp>=2) {
						$parent_path = substr($parent_path, 0, $lastp-1);
						$sajt = cms_universe::$puniverse->site_by_filter('id', $params[0]);
						$parent = $sajt->get($parent_path, $this->currlang);
						foreach($qnew as $newtag) {
							$childp = $parent->bornchild($params[8], $this->currlang);											
							$child = $sajt->get($childp, $this->currlang);
							$child->name = $newtag;
							$child->published = 1;
							$child->store();
							$selected[] = $child->_site_reference->id.':'.$child->pathl.':'.$child->lang;
						}
					}
				} else {
					if ($techno == 'list' || $techno == 'advanced') {
						$selected = @$_POST[$fdef[1]];
						if ($techno == 'advanced') {
							if ($selected == '') {
								$selected = array();
							} else {
								$ta = array();
								parse_str($selected, $ta);
								$selected = $ta['v'];
							}
						}
					} else {
						$selected = array();
						$h1 = $fdef[1].':';
						$h2 = strlen($h1);
						foreach ($_POST as $k=>$v) {
							if (strncmp($k, $h1, $h2)==0 && $v == 'X')
								$selected[] = substr($k, $h2);
						}
					}
				}
				if (!is_array($selected)) {
					$rac->cleanup_relation();
					break;
				}
				if (count($selected)>$n) {
					$selected = array_slice($selected,0,$n);
					$this->_template->showMessage(cms_template::$msg_info,'941', array($fdef[0], $n));
				}
				$rac->cleanup_relation();
				$j = 0;
				foreach($selected as $sel) {
					list($siteid, $path, $lang) = explode(':', $sel);
					$rac->add_relation($siteid, $path, $lang, ++$j);
				}
				break;
			default:
				$this->currentry->{$fdef[1]} = $value;
		}
	}

	public function rule_accept_display($text, $buttext) {
		$this->rd_screen();
		$this->rd_entry_basic();
		$this->_template->addTab('rules', __('Informacja'));
		$this->_template->addField('rules', '', 'f_text', 'spanhtml', null, $text);
		$this->_template->addField('rules', '', 'f_accept', 'button', array ('action' => 'accept_rules', "not_caller" => false ), $buttext);
		$this->translate();
		$this->_template->enable('f_accept');
		$this->_template->disable('f_text');
		$this->_template->selectTab('rules');
		$this->_template->hideClass('sajty');
		$this->_template->hideClass('left-nav');
		$this->_template->hideClass('act');
		return $this->_template;
	}

	public function std_d() {
		$this->rd_screen();
		$this->rd_sites();
		$this->rd_bread();
		$this->rd_menu();
		if ($this->currentry != null) {
			$this->rd_entry_basic();
			$this->rd_validation();
		}
	}
	
	public function rd_validation() {
		if (!$this->currentry->is_unique_slug()) {
			$this->_template->showMessage(cms_template::$msg_error,'901');
		}
		$ev = new cms_eev($this->currentry);
		$details = array();
		$val = $ev->validate($details);
		$c = 0;
		$d = false;
		if ($val == false) {
			$this->_template->addMessage('x00',__('Błąd: &0'));
			$this->_template->addMessage('x01',__('Istnieją kolejne komunikaty o błędach, pokazano pierwsze &0'));
			foreach($details as $fld=>$det) {
				if ($c < cms_config::$cc_validator_max_error_msg) {
					if ($det[1]) {
						$this->_template->showMessage(cms_template::$msg_error,'x00', array($det[1]));
						$c++;
					}
				} else {
					if ($d == false) {
						$this->_template->showMessage(cms_template::$msg_error,'x01', array($c));
						$d = true;
					}					
				}
				if (!$det[0]) {
					$this->_template->markFieldBad($fld, $det[1]);
				}
			}
			
		}
	}
	
	public function ext_a() {
		// to be extended in subclass
	}
	
	public function ext_d() {
		$this->rd_entry();
		// to be extended (might also be replaced) in subclass
	}
	
	public function rd_screen() {
		// check last login and set screen
		$ll = cms_login::getlastlogin($this->curruser->name);
		if ($ll) {
			$this->_template->setP('last-succ-login', $ll['LAST_SUCC'] ? date(cms_config::$cc_lastmod_date_format, $ll['LAST_SUCC']):'---');
			$this->_template->setP('last-fail-login',  $ll['LAST_FAIL'] ? date(cms_config::$cc_lastmod_date_format, $ll['LAST_FAIL']):'---');
		}
		$showname = cms_universe::$puniverse->session()->extauthname;
		if (!$showname) {
			$showname = $this->curruser->name;
		}
		$this->_template->setP('logged-user', $showname);
		$pt = cms_config::$cc_cms_name;
		if ($this->currentry instanceof cms_entry_data) {
			$pt .= ' :: '.$this->currentry->name;
		}
		$this->_template->setP('page-title', $pt);
		$this->_template->setP('cms-logo-alt', cms_config::$cc_cms_name);
	}
	
	public function rd_sites() {
		$sites = cms_universe::$puniverse->list_sites();
		foreach($sites as $lsite) {
			if ($this->curruser->check_access($lsite[0], "*", $this->currlang) != cms_userman::$right_deny)
				$this->_template->addSite($lsite[0], $lsite[1], $lsite[0] == $this->currsite->id);
		}
		if ($this->currentry) {
			$entrylangs = $this->currentry->get_langs();	
		} else {
			$entrylangs = array();
		}
		if (count($this->currsite->langs) > 1) {
			foreach ($this->currsite->langs as $lng) {
				if($lng == 0)
					continue;
				$lang = cms_universe::$puniverse->languages[$lng-1];
				$dil = in_array($lang[0], $entrylangs);
				// list language only if we have rights for the language ??
				if (
						($this->curruser->check_access($this->currsite->id, $this->currpath, $lang[0]) != cms_userman::$right_deny)
						||
						($this->currlang == $lang[0])
				) {
					$this->_template->addLanguage($lang[0], strtoupper($lang[4]), $lang[2], $dil?true:false, ($lang[0] == $this->currlang)?true:false);
				}
			}
		}
	}
	
	public function rd_bread() {
		$p0 = array($this->currsite->id.'.0');
		$r0 = array($this->currsite->name);
		$p = array();
		$r = array();
		$path = $this->currpath;
		if ($path != '0') {
		while ($path != "") {
			$entrym = $this->currsite->get($path, $this->currlang, false, true, false);
			$p[] = $entrym->pathl;
			$r[] = $entrym->name;
			$path = cms_path::leavepathpart($path, cms_path::pathlen($path) - 1);
		}
		$p = array_reverse($p);
		$r = array_reverse($r);
		}
		$this->_template->breadcrumb(array_merge($r0,$r),array_merge($p0,$p));
	}
	
	public function rd_menu() {
		$mil = $this->currsite->get_option("menu_inclusion_list");
		if (!$mil) {
			$mil = "**";
		}
		$menu = $this->currsite->get($mil, $this->currlang, false, true, false, 
			null, $this->currsite->get_option("menu_exclusion_list"), null, 
			true, false);
		// sort menu
		cms_entry::treesort($menu);
		// do a small cutout of menu items...
		if (is_array($menu)) {
			if (cms_config::$cc_cms_template_menu_cutout) {
				$dmenu = array();
				foreach ($menu as $mi) {
					$pass = true;
					$pathlen = count(explode('/', $this->currpath));
					if ($mi->level == 0) {
						$pass = true;
					} else {
						if ($mi->level < $pathlen) {
							$lspath = cms_path::leavepathpart($this->currpath, $mi->level);
							$lspathi = cms_path::leavepathpart($mi->pathl, $mi->level);
							$pass = ($lspath == $lspathi);
						} else {
							if ($mi->level == $pathlen) {
								$pass = cms_path::pathcontains($mi->pathl, $this->currpath);
							} else {
								$pass = false;
							}
						}
					}
					if ($pass != false) {
						$dmenu[] = $mi;
					}
				}
			} else {
				$dmenu = $menu;
			}
			foreach($dmenu as $item) {
				// final unit filtering
				if(!$this->currsite->get_option('display_in_menu', $item->pathl))
					continue;
				// security filtering
				if ($this->curruser->check_access($this->currsite->id, $item->pathl, $this->lang) == cms_userman::$right_deny) {
					continue;
				}
				$pp = cms_path::leavepathpart($item->pathl, cms_path::pathlen($item->pathl)-1);
				$sele = (cms_path::pathcontains($this->currpath, $item->pathl)?1:0);
				if ($this->currpath == $item->pathl)
					$sele = 2;
				$iname = (strlen($item->name)==0?__($this->currsite->get_option('name_display_in_menu_if_empty', $item->effectivetypepath)):$item->name);
				if ($item->typepath != $item->pathl) {
					$tp = ' ['.__($this->currsite->get_option('typename', $item->typepath)).']';
				} else {
					$tp = '';
				}
				$this->_template->addMenu($pp, $item->pathl, $item->level, $iname . $tp, $sele);
			}
		}
	}
	
	public function rd_entry_basic() {
		
		$modification_allowed = ($this->curruser->check_access($this->currsite->id, $this->currpath, $this->currlang) == cms_userman::$right_allow);
		if (!$modification_allowed)
			$this->_template->disableAll();

		$this->_template->contentLanguage = cms_universe::$puniverse->languages[$this->currlang-1][4];
		
		$this->_template->setP('last-change-label', __($this->currentry->get_option('datelabel')));
		$this->_template->setP('name-label', __($this->currentry->get_option('namelabel')));
		$this->_template->setP('code-label', __($this->currentry->get_option('codelabel')));
		$this->_template->setP('slug-label', __($this->currentry->get_option('sluglabel')));
		$this->_template->setP('patht-label', __($this->currentry->get_option('pathtlabel')));
		
		$this->_template->setP('save-visible', $modification_allowed && !$this->currentry->get_option('hidesave') );
		$this->_template->setP('preview-visible', $this->currentry->get_option('canpreview') );
		$this->_template->setP('up-visible', $modification_allowed && $this->currentry->get_option('updown') );
		$this->_template->setP('down-visible', $modification_allowed && $this->currentry->get_option('updown') );
		
		$this->_template->setP('delete-visible', $modification_allowed && $this->currentry->get_option('candelete') );
		
		$this->_template->setP('name-editable', $modification_allowed && $this->currentry->get_option('editname') );
		
		// element basic data
		$dt = $this->currentry->get_last_mod_date();
		$this->_template->setP('last-change', $dt==0 ? '---' : date(cms_config::$cc_lastmod_date_format,$this->currentry->get_last_mod_date()));
		$this->_template->setP('name', $this->currentry->name);
		$this->_template->setP('code', (($this->currentry instanceof cms_entry_db)?'DB':'XML') . ' ' .$this->currentry->pathl . ' ['.$this->currentry->effectivetypepath.'] ' );
		$this->_template->setP('site', $this->currsite->id);
		$this->_template->setP('path', $this->currentry->pathl);
		$this->_template->setP('lang', $this->currlang);
		$this->_template->setP('slug', $this->currentry->slugdata);
		
		$this->_template->setP('patht', 'http://'.$this->currsite->dhm[$this->currlang].$this->currentry->slug);
		
		$idle = $this->currentry->get_option('auto_save_on_idle');
		if ($modification_allowed && $idle>0)
			$this->_template->setP('auto_save_on_idle', $idle);
		
		// child add name (try)
		$moja_nazwa_w_bierniku = $this->currsite->get_option('typename-passive',$this->currentry->pathl);
		$domyslna_nazwa_w_bierniku = $this->currsite->get_option('typename-passive',$this->currentry->pathl.'/1');

		// allowed new types
		$crc = $this->currentry->get_option('createchild');
		if ($modification_allowed && $crc) {
			$xtl = explode(',', $this->currentry->get_option('child_types'));
			if (!count($xtl)) {
				$crc = false;
			}
			foreach($xtl as $xtype) {
				$this->_template->addType('new-child-visible',$this->currsite->get_option('typename',$xtype), $xtype);
			}
			if (count($xtl) == 1 && $xtl[0] != '**') {
				$nazwa_w_bierniku = $this->currsite->get_option('typename-passive',$xtl[0]);
			} else {
				$nazwa_w_bierniku = $domyslna_nazwa_w_bierniku;
			}
			if (count($xtl) == 1 && strlen($nazwa_w_bierniku)>0) {
				$this->_template->repstr('podrzędny', $nazwa_w_bierniku);
			} else {
				$this->_template->repstr('podrzędny', __('podrzędny'));
			}
		}
		$this->_template->setP('new-child-visible', $modification_allowed && $crc);
		
		$crs = $this->currentry->get_option('createsibling');
		if ($modification_allowed && $crs) {
			if ((($p = $this->currentry->parent) instanceof cms_entry)) {
				$xtl = $p->get_option('child_types');	
			} else {
				$xtl = $this->currsite->get_option('child_types');
			}
			$xtl = explode(',',$xtl);
			if (!count($xtl)) {
				$crs = false;
			}			
			foreach($xtl as $xtype) {
				$this->_template->addType('new-sibling-visible',$this->currsite->get_option('typename',$xtype), $xtype);
			}
			if (count($xtl) == 1 && $xtl[0] != '**') {
				$nazwa_w_bierniku = $this->currsite->get_option('typename-passive',$xtl[0]);
			} else {
				$nazwa_w_bierniku = $moja_nazwa_w_bierniku;
			}
			if (count($xtl) == 1 && strlen($nazwa_w_bierniku)>0) {
				$this->_template->repstr('kolejny', $nazwa_w_bierniku);
			} else {
				$this->_template->repstr('kolejny', __('kolejny'));
			}
		}
		$this->_template->setP('new-sibling-visible', $modification_allowed && $crs);
		
		// allowed languages to select from list
		$langcount = count($this->currsite->langs);		
		if ($langcount > 1) {
			// currently maintained languages			
			$entrylangs = $this->currentry->get_langs();	
			// allowed languages to create (overall)		
			$createinlangs = array_diff($this->currsite->langs, array($this->currlang));
			// allowed languages - include parent
			$parent_path_len = count(explode("/",$this->currentry->pathl)) - 1;
			if ($parent_path_len > 0) {
				$parent_path = cms_path::leavepathpart($this->currentry->pathl, $parent_path_len);
				$parent = $this->currsite->get($parent_path, $this->currlang);
				$parent_langs = $parent->get_langs();
				$createinlangs = array_intersect($createinlangs, $parent_langs);
				$msg = true;
			} else {
				$msg = false;
			}
			
			$this->_template->addTab('xxlangman',__('Języki'));
			$this->_template->addGroup('xxlangman', 'xxcurrent', __('Ten element jest stworzony w językach'));
			$lc = 0;
			$langlist = array();
			foreach ($this->currsite->langs as $lng) {
				if ($lng == 0)
					continue;
				$lang = cms_universe::$puniverse->languages[$lng-1];
				$dil = in_array($lang[0], $entrylangs);
				if ($dil) {
					$langlist[]= $lang[2] . ' [' .strtoupper($lang[4]).'] ('.$lang[3].')';
					$lc++;
				}
				
			}
			if ($lc)
				$this->_template->addField('xxcurrent', join(", ",$langlist), '','span',NULL, '');
			// copy to other langs
			$ctl = array();
			foreach ($createinlangs as $lng) {
				if($lng == 0)
					continue;
				$lang = cms_universe::$puniverse->languages[$lng-1];					
				if($this->curruser->check_access($this->currsite->id, $this->currpath, $lang[0]) == cms_userman::$right_allow) {
					$ctl[] = $lang;
				} else {
					$msg = true;
				}					
			}
			// where we can copy?
			$this->_template->addGroup('xxlangman', 'xxcreate', __('Skopiuj element do innego języka'));
			if(count($ctl)>0) { 				
				if (count($this->currsite->langs)>1) {
					$this->_template->addField('xxcreate',__('Nadpisz istniejące elementy (UWAGA - wybranie tej opcji napisze istniejące elementy w wybranym języku!)'), 'create_lang_set_overwrite', 'simplecheckbox', null, '');
					$this->_template->addField('xxcreate',__('Skopiuj rownież elementy podrzędne'), 'create_lang_set_deep', 'simplecheckbox', null, '1');
					foreach ($ctl as $lang) {
						if($this->curruser->check_access($this->currsite->id, $this->currpath, $lang[0]) == cms_userman::$right_allow) {
							$this->_template->addField('xxcreate','', '', 'button', array('action'=>'create_lang_'.$lang[0], 'group'=>'1'), $lang[2]. ' [' .strtoupper($lang[4]).']');
						}
					}
				}
			}
			if ($msg) {
				$this->_template->addField('xxcreate', __('Nie widzisz potrzebnego języka na liście?'), 'warn','span',NULL, __('Sprawdź, czy element nadrzędny został już utworzony w odpowiednim języku oraz czy posiadasz odpowiednie uprawnienia'));
			}		
			$this->_template->addGroup('xxlangman', 'xxdeled', __('Usuwanie'));
			$lc = ($lc > 1) || ($this->currentry->get_option('candelete') == 'true');
			if ($lc) {
				foreach ($this->currsite->langs as $lng) {
					if($lng == $this->currlang)
						$lang = cms_universe::$puniverse->languages[$lng-1];
				}
				if ($this->currentry->get_option('candelete') != 'true') {
					$this->_template->addField('xxdeled', __('Ostrzeżenie'), 'warn','span',NULL, __('Ten element jest kluczowy dla struktury strony i jego usunięcie może spowodować, że wersja językowa').' '.$lang[2].
						' '.__('przestanie działać. Jego odtworzenie może wiązać się z dużym nakładem pracy. Po usunięciu zawsze możesz skopiować ten element z innej wersji językowej.'));
				}
				$this->_template->addField('xxdeled','', '', 'button', array('action'=>'delete', 'group'=>'3'), __('Usuń w aktualnym języku').' - '.$lang[2].' ['.strtoupper($lang[4]).']');
			} else {
				$this->_template->addField('xxdeled', __('Nie ma możliwości usunięcia'), 'warn','span',NULL, __('Ten element musi pozostać w serwisie przynajmniej w jednym języku.'));
			}
			
		}
	}
	
	public function rd_entry() {
		if ($this->currentry != null) {
			// remember me
			$sa = new cms_session();
			$cp = $sa->cpath;
			if ($cp != $this->currpath) {
				$sa->ppath = $cp;
				$sa->cpath = $this->currpath;
			}
			// dbe child list
			$j = $this->currentry->get_option('dbechildlist');
			if ($j) {
				// prepare setting
				$j = explode(',', $this->currentry->get_option('dbechildlistfields'));
				$l = explode(',', $this->currentry->get_option('dbechildlistlabels'));
				$t = explode(',', $this->currentry->get_option('dbechildlisttypes'));
				$ss = $this->currentry->get_option('dbechildlistsearchfields');
				$s = ($ss=='')?array():explode(',', $ss);
				$sf = explode(',',$this->currentry->get_option('dbechildlistsortfields'));
				$enable_numbers = !($this->currentry->get_option('dbechildlistnonumbers'));
				$cols = array();
				foreach($j as $i=>$k) {
					$type = cms_options::get_operation($t[$i]);
					if ($type == 'relate') {
						$_data = cms_options::get_data($t[$i]);
						$_params = cms_universe::safesplitter($_data,':');
						$type = cms_options::get_operation($_params[5]);
					}
					if ($type == 'select') {
						$_data = cms_options::get_data($t[$i]);
						$_params = cms_universe::safesplitter($_data,':');
						$type = cms_options::get_operation($_params[1]);
					}
					$cols[] = @array('name'=>__($l[$i]), 'type'=>$type, 'fn'=>$j[$i], 'sf'=>in_array($j[$i], $sf));
				}
				if ($enable_numbers) {
					array_unshift($cols,@array('name'=>__('LP'), 'type'=>'lp'));
				}
				$this->_template->addTab('dbelist', __($this->currentry->get_option('dbechildlistlabel')));
				// button_edit
				// button_delete
				// button_up
				// button_down
				// button_preview
				// button_save
				// button_new
				// button_duplicate
				// legend
				// actionprefix
				// boxed (shows tabli within standard field frame, instead of free form)
				// columns (array of objects - .name .type, where type can be (text,image,date,dates,checkbox),
				// don't include action cols here, omit first column (the ID one)
				// rows (2d array - 1st lev - row, 2nd lev - cell value, first cell contains the ID (mandatory)
				// if empty an ajax call will be made if path is set
				// path - path for ajax call, ?search and /dbe-list will be added automagically
				// if not set, all is ok but ajax call will not be possible
				// also search field will not be shown then
				// extra - extra buttons (array of objects - .name, .function)
				$this->_template->addField('dbelist', '', '', 'autolist', array(
					'button_edit' => true,
					'button_delete' => $this->currentry->get_option('dbechildlistdelete'),
					'button_up' => false,
					'button_down' => false,
					'button_preview' => $this->currentry->get_option('dbechildlistpreview'),
					'button_save' => false,
					'button_new' => $this->currentry->get_option('createchild'),
					'button_duplicate' => $this->currentry->get_option('dbechildlistcopy'),
					'legend' => true,
					'actionprefix' => 'dbe',
					'boxed' => false,
					'columns' => $cols,
					'rows' => null,
					'path' => $this->currsite->id.'.'.strtr($this->currpath,'/','-').'.'.$this->currlang,
					'extra' => null,
					'searchfields' => $s
				), '');
			}
			
			// some fck editor settings
			$ljsb = array('fckeditor_bodyid', 'fckeditor_bodyclass', 'fckeditor_editorcss');
			foreach ($ljsb as $lj) {
				$this->_template->setP($lj, $this->currentry->get_option($lj, $this->currpath));
			}
			
			// standard content (if exists)
			$hc = $this->currentry->get_option('hascontent');
			if ($hc) {
				$this->_template->addTab('content', __($this->currentry->get_option('contentlabel')));
				$this->_template->addField('content', 'text', 'ctext', 'textareaeditorfull', '', $this->currentry->content);
			}
			
			$vtabc = array();
			$graphs = $this->currentry->get_option('graphs');
			if ($graphs!='' && $graphs!='none') {
				$graphs = explode(',', $graphs);
				foreach($graphs as $gkind) {
					// czy w opcjach dany element jest włączony ?
					$oset = $this->currentry->get_option($gkind.'graphparams');
					$oset = explode(',', $oset);
					$oset7fn = preg_replace('/[^a-z0-9]/u','_',mb_strtolower($oset[7]));
					if (!isset($vtabc[$oset[7]])) {
						$this->_template->addTab('graph_'.$oset7fn, __($oset[7]));
						$vtabc[$oset[7]] = true;
					}
					// graph
					$graph = array();
					$obj = @$this->currentry->graphs[$gkind];
					
					if ($obj instanceof cms_picture && is_file(cms_config::$cc_cms_images_dir.$obj->file)) {
						$graph['src'] = cms_config::$cc_cms_images_dir . $obj->file;
						$graph['kind'] = $gkind;
						$img = @getimagesize($graph['src']);
						$graph['type'] = (($img[2]==IMAGETYPE_SWC)||($img[2]==IMAGETYPE_SWF)?"SWF":($img[2]==IMAGETYPE_PNG?"PNG":($img[2]==IMAGETYPE_GIF?"GIF":"JPG")));
						$fil = @stat($graph['src']);
						$graph['min'] = ($graph['type']=="SWF"?cms_config::$cc_fl_picture:$graph['src']);
						$graph['size'] = round($fil[7]/1024, 2)." kB";
						$graph['dimensions'] = "[$img[0] x $img[1]]";
						$graph['limit'] = "[limit : ".$oset[0] . ' x '.$oset[1]." ".$oset[2]."]";
						if ($graph['type'] == "SWF") {
							$graph['src'].="?width=$img[0]&height=$img[1]";
						}
					} else {
						$graph['min'] = $graph['src'] = cms_config::$cc_no_picture;
						$graph['kind'] = $gkind;
						$graph['type'] = '---';
						$graph['size'] = "0 kB";
						$graph['dimensions'] = '';
						$graph['limit'] = "[limit : ".$oset[0] . ' x '.$oset[1]." ".$oset[2]."]";
					}
					$this->_template->addField('graph_'.$oset7fn, __($oset[5]), $gkind, 'visual', $graph, '');
				}
			}
			// photos
			$id = 0;
			$pictures = array();
			$roset = $this->currentry->get_option('graphparams');			
			if (is_array($this->currentry->pictures) && strlen($roset)) {
				$c0 = count($this->currentry->pictures);
				$oset = explode(',', $roset);
				foreach($this->currentry->pictures as $picture) {
					$pict=array();
					$pict['id'] = $id++;
					$pict['pid'] = $id;
					$pict['total'] = $c0;
					if ($picture instanceof cms_picture && is_file(cms_config::$cc_cms_images_dir.$picture->file)) {
						$pict['src'] = cms_config::$cc_cms_images_dir.$picture->file;
						$pict['name'] = '';
						$img = @getimagesize($pict['src']);
						$pict['type'] = (($img[2]==IMAGETYPE_SWC)||($img[2]==IMAGETYPE_SWF)?"SWF":($img[2]==IMAGETYPE_PNG?"PNG":"JPG"));
						$fil = @stat($pict['src']);
						$pict['size'] = round($fil[7]/1024, 2)." kB";
						$pict['dimensions'] = "[$img[0] x $img[1]]";
						$pict['limit'] = "[limit : ".$oset[0] . ' x '.$oset[1]." ".$oset[2]."]";
						$pict['photolabel'] = __($oset[5]);
						$pict['photolabellabel'] = __($oset[6]);
						$pict['photolabeltext'] = $picture->descr;
						if ($pict['type'] == "SWF") {
							$pict['src'].="?width=$img[0]&height=$img[1]";
						}
						
					} else {
						$pict['src'] = cms_config::$cc_no_picture;
						$pict['name'] = '';
						$pict['type'] = '---';
						$pict['size'] = "0 kB";
						$pict['dimensions'] = '';
						$pict['limit'] = "[limit : ".$oset[0] . ' x '.$oset[1]." ".$oset[2]."]";
						$pict['photolabel'] =__($oset[5]);
						$pict['photolabellabel'] = __($oset[6]);
						$pict['photolabeltext'] = '';
					}
					
					$pict['showfile'] = (strpos(@$oset[8],'F') !== FALSE);
					
					if(strpos(@$oset[8],'M') !== FALSE) {
						// miniatury
						$pict['mini'] = true;
						if (is_file(cms_config::$cc_cms_images_dir.$picture->minifile)) {
							$pict['minisrc'] = cms_config::$cc_cms_images_dir.$picture->minifile;
							$pict['mininame'] = '';
							$img = @getimagesize($pict['minisrc']);
							$pict['minitype'] = (($img[2]==IMAGETYPE_SWC)||($img[2]==IMAGETYPE_SWF)?"SWF":($img[2]==IMAGETYPE_PNG?"PNG":"JPG"));
							$fil = @stat($pict['minisrc']);
							$pict['minisize'] = round($fil[7]/1024, 2)." kB";
							$pict['minidimensions'] = "[$img[0] x $img[1]]";
							$pict['miniphotolabeltext'] = $picture->minidescr;
						} else {
							$pict['minisrc'] = cms_config::$cc_no_picture;
							$pict['mininame'] = '';
							$pict['minitype'] = '---';
							$pict['minisize'] = "0 kB";
							$pict['minidimensions'] = '';
							$pict['miniphotolabeltext'] = '';
						}
						
						$pict['minilimit'] = '';
					} else {
						$pict['mini'] = false;
					}
					
					$pictures[] = $pict;
				}
			}
			
			// display actions
			// czy nowe zdjecie
			$this->_template->setP('new-picture-visible', false);
			$maxfoto = $this->currentry->get_option('pictures');
			$picturesc = count($this->currentry->pictures);
			$dodajfoto = $picturesc < $maxfoto;
			$plabel = $this->currentry->get_option('photolabel');
			if ($maxfoto) {
				$this->_template->addTab('photo', __($oset[7]));
				if($dodajfoto) {
					$this->_template->setP('new-picture-visible', true);
					$pictures_left = $maxfoto - $picturesc;
					$this->_template->setP('pictures-left', $pictures_left);
				}
				reset($pictures);
				while(list($pid,$picture) = each($pictures)) {
					$picture['ord'] = $pid+1;
					$this->_template->addField('photo', $plabel, $pid, 'picture', $picture, $picturesc);
				}
			}
			
			//fieldtable - display
			$ftable =      $this->currentry->get_option('fieldtable_b');
			$ftable .= ';'.$this->currentry->get_option('fieldtable');
			$ftable .= ';'.$this->currentry->get_option('fieldtable_');
			$ftable .= ';'.$this->currentry->get_option('fieldtable__');
			$ftable .= ';'.$this->currentry->get_option('fieldtable_a');
			$run_textarea_id = 1;

			$current_tab = 'data';
			$current_parent = $current_tab;
			if(cms_config::$cc_cms_template_defftname) {
				$this->_template->addTab($current_tab,__($this->currentry->get_option('fieldtablename')));
			}

			$fields = $this->dynamic_fields(array_filter(cms_universe::safesplitter($ftable,';')));

			reset($fields);
			while(list($fnumber, $fdef) = each($fields)) {		
				if (strlen($fdef)>0 && count($fdef = cms_universe::safesplitter($fdef,','))==3) {
					$fieldtableentry = array();
					$fieldtableentry['clm']='';
					$fieldtableentry['dshow']='1';
					$value = $this->currentry->{$fdef[1]};
					$fdef[0] = __($fdef[0]) . (cms_config::$cc_developer_mode ? ($fdef[1] ? " [{$fdef[1]}]" : '') : '');						
					switch($fdef[2]) {
						case "tabspacer":
							$current_tab = 'data_'.((strlen($fdef[1])>0)?$fdef[1]:cms_path::pathlt_conversion($fdef[0]));
							$current_parent = $current_tab;
							$this->_template->addTab($current_tab, $fdef[0]);
							break;
						case "spacer":
							$current_parent = 'sp_'.((strlen($fdef[1])>0)?$fdef[1]:cms_path::pathlt_conversion($fdef[0]));
							$this->_template->addGroup($current_tab, $current_parent, $fdef[0]);
							break;
						case "text":
						case "texti":
						case "textf":
							$this->_template->addField($current_parent, $fdef[0], $fdef[1], $fdef[2], '', $value);
							break;
						case "span":
							$this->_template->addField($current_parent, '', $fdef[1], $fdef[2], '', $fdef[0]);
							break;
						case "textic":
							$this->_template->addField($current_parent, $fdef[0], $fdef[1], $fdef[2], array(
								"cfname" => $fdef[1].md5($fdef[1])
							), $value);
							break;
						case "checkbox":
							$this->_template->addField($current_parent, $fdef[0], $fdef[1], 'simplecheckbox', '', $value?'1':'');
							break;
						case "file":
							$cfi = $value;
							$fi = cms_entry::uncombine_file_information($cfi);
							$ffn = cms_config::$cc_cms_images_dir.$fi[0];
							if (($fi[0]!='') && is_file($ffn)) {
								$ffs = @stat($ffn);
								$cdk = cms_universe::combine_download_key($this->currsite->id, $this->currpath, $fdef[1], $this->currlang);
								$size = $ffs['size'];
								$size /= 100;
								$size = round($size);
								$size /= 10;
								$params = array(
									'filename' => $fi[2],
									'downloadkey' => $cdk,
									'filetype' => $fi[1],
									'filesize' => $size
								);
							} else {
								$params = array(
									'filename' => '',
									'downloadkey' => '',
									'filetype' => '',
									'filesize' => ''
								);
							}
							$this->_template->addField($current_parent, $fdef[0], $fdef[1], 'file', $params, '');
							break;
						default:
						$_operation = cms_options::get_operation($fdef[2]);
						$_data = cms_options::get_data($fdef[2]);
						$params = cms_universe::safesplitter($_data,':');
						switch($_operation) {
							case 'table_fs':
								$rl = max(1,$params[0]);
								$cl = max(1,$params[1]);
								if (!is_array($value)) {
									for($r=0;$r<$rl;$r++)
										for($c=0;$c<$cl;$c++)
											$value[$r][$c]='';
								}
								$this->_template->addField($current_parent, $fdef[0], $fdef[1], 'table',
									array(
										'rows' => $rl,
										'cols' => $cl
									), $value);
								break;
							case 'textarea':
								if ($params[0] == '') {
									$params[0] = 3;
								}
								if ($params[1] == '') {
									$params[1] = 44;
								}
								$type = ((@$params[2] == 'editor')?'textareaeditormini':
									((@$params[2] == 'code')?'code':'textarea'));
								$this->_template->addField($current_parent, $fdef[0], $fdef[1], $type,
									array(
										'rows' => $params[0],
										'cols' => $params[1],
										'mode' => @$params[3]
									), $value);
								break;
							case 'date':
								if (!is_array($value))
									$value = array();
								$multi = $params[0] > 1;
								$dates = array_filter($value);
								$dates = array_map(array('cms_std_gui','tstodate'), $dates);
								$this->_template->addField($current_parent, $fdef[0], $fdef[1], 'date',
									array(
										'format' => cms_config::$cc_js_date_format,
										'mode' => ($multi?'multiple':'single'),
										'date' => ($multi?$dates:@$dates[0]),
									), join(',', $dates));
								break;
							case 'select':
								$techno = @$params[2];
								if (!in_array($techno, array('dropdown', 'radio')))
									$techno = 'dropdown';
								$rsite = $params[0];
								if ($rsite == 'user' || $rsite == 'list') {
									// this is handled later in this function
								} else {
									if ($rsite == 'current') {
										$rsite = $this->currsite->id;
									}
									if ($this->currsite->id == $rsite) {
										$ssite = $this->currsite;
									} else {
										$ssite = cms_universe::$puniverse->site_by_filter('id', $rsite);
									}
									if (! $ssite instanceof cms_site) {
										throw new InvalidArgumentException('misconfiguration encountered, references nonexisting site '.$rsite);
									}
								}									
								switch(@$params[3]) {
									case 'all':
										// all allowed within given site
										$slang = '';
										break;
									case 'current':
										$slang = $this->currlang;
										break;
									default:
										if(@$params[3]) {
											// list lang
											$slang = explode(';',$params[3]);
										} else {
											$slang = $this->currlang;
										}
								}
								if (@$params[4] != '') {
									$styp = explode(',',$params[4]);
								} else {
									$styp = null;
								}
								if ($rsite == 'user') {
									$v0 = array('---');
									$k0 = array('');
									foreach(cms_universe::$puniverse->userman()->get_user_list() as $user) {
										$k0[] = $user->name;
										$v0[] = "{$user->desc} ({$user->name})";
									}
								} else if ($rsite == 'list') {
									$v0 = array('---');
									$k0 = array('');
									$data = cms_universe::safesplitter($params[1],'/');
									$iter = 0;
									foreach($data as $dat) {
										$v0[] = __($dat); 
										$k0[] = (cms_config::$cc_use_value_keys_in_select_list ? $dat : (++$iter));
									}
								} else {
									$spath = $params[1];
									$list = $ssite->get($spath, $slang, false, true, false, null, null, $styp);
									$v0 = array('---');
									$k0 = array('');
									foreach($list as $li) {
										$k0[] = $li->pathl;
										$v0[] = (in_array("withparent", $params))
													?($li->parent->name . ' :: ' . $li->name)
													:($li->name);
									}
								}
								$temp = array_combine($k0, $v0);
								$GLOBALS['__lcollator'] = collator_create('pl_PL');
								collator_asort($GLOBALS['__lcollator'], $temp);
								$v0 = array_values($temp);
								$k0 = array_keys($temp);
								if ($rsite == 'user' && $params[1] == 'current' && strlen($value)==0)
									$value = $this->curruser->name;
								$this->_template->addField($current_parent, $fdef[0], $fdef[1],
									($techno == 'dropdown'?'simpleselect':'radio'), array(
										'v'=>$v0, 'k'=>$k0 ), $value);
								break;
							case 'relate':
								$this->output_relate ( $params, $fdef, $current_parent );
								break;
							default:
								break;
						}
					}
				}
			}

			if ($this->enforce_deftab || strlen($_POST['deftx'])==0)
				$t = $this->currentry->get_option('deftab');
			else 
				$t = $_POST['deftx'];
			$this->_template->selectTab($t);
			$gui_hides_tabs = array_filter(explode(',',$this->currentry->get_option('guihides')));
			foreach ($gui_hides_tabs as $tab) {
				$this->_template->hideTab('data_-'.$tab);
			}
		}
		
	}
	/**
	 * @param params
	 */protected function output_relate($params, $fdef, $current_parent) {
		$techno = cms_std_gui::relate_techno($params[6]);
		$rac = $this->create_relation_accessor($params);
		$current = $rac->related();
		switch ($techno) {
			case 'list':
			case 'checkbox':
				$ark = array();
				$arv = array();
				$ars = array();
				$all = $rac->all_possible(); // uniqueness is granted by technology here!
				$mlev = 9;
				foreach ($all as $rk => $co) {
					if ($mlev>$co->level)
						$mlev = $co->level;
				}
				foreach ($all as $rk => $co) {
					$ark[] = $rk;
					$arv[] = ($techno=='list'?str_repeat('> ',$co->level - $mlev).' ':'').$co->name;
					$ars[] = isset($current[$rk]);
				}
				$this->_template->addField($current_parent, $fdef[0], $fdef[1],
					($techno == 'list'?'multiselectbox':'multicheckbox'), array(
						'v'=>$arv, 'k'=>$ark, 's'=>$ars ), $value);
				break;
		    case 'tag':
			case 'advanced':
				$arl = array();
				if($techno == 'tag') {
					foreach($current as $rk=>$co) {
						$arl[] = $co->name;
					}
				} else {
					foreach($current as $rk=>$co) {
						$arl[] = array('k'=>$rk, 'v'=>$co->name, 'l'=>$co->level);
					}
				}
				$n = (int)$params[4];
				if ($n<1) $n = 1;
				// lajf apdejt
				$lu = $this->currentry->get_option('live_update:'.$fdef[1]);
				$ludata = array();
				if (strlen($lu)) {
					$lu1 = explode(",", $lu);
					foreach($lu1 as $lu2) {
						$lu3 = explode("=", $lu2);
						$ludata[] = array('t'=>$lu3[0], 's'=>$lu3[1]);
					}
				}
				$this->_template->addField($current_parent, $fdef[0], $fdef[1],
					($techno == 'tag')?'tag':'advrel', array(
						'data'=>$arl, 'uniq'=>$params[5]==1, 'n'=>$n,
							'tfp'=>$this->currsite->id.'.'.strtr($this->currpath,'/','-').'.'.$this->currlang,
							'liveupdate'=>$ludata, 'relationdef'=>join(":",$params)), $value);
				break;
		}
	}

	
	public static function tstodate($p) {
		return date(cms_config::$cc_php_date_format, $p);
	}
	
	public static function relate_techno($p) {
		$techno = $p;
		if (!in_array($techno, array('checkbox', 'list', 'advanced', 'tag')))
			$techno = 'list';
		return $techno;
	}
	
	public function create_relation_accessor($params, $asite = null, $apath = null, $alang = null) {
		@list($siteid, $pat, $code, $lr, $n, $uniq, $techno, $slang, $types) = $params;
		switch($slang) {
			case 'all':
				$slang = '';
				break;
			case 'current':
				$slang = $this->currlang;
				break;
			default:
				if(strlen($slang)>0) {
					// list lang
					$slang = explode(';',$slang);
				} else {
					// current
					$slang = $this->currlang;
				}
		}
		if ($types != '') {
			$styp = explode(',',$types);
		} else {
			$styp = null;
		}
		if ($siteid == 'current') {
			$siteid = $this->currsite->id;
		}
		if ($this->currsite->id == $siteid) {
			$ssite = $this->currsite;
		} else {
			$ssite = cms_universe::$puniverse->site_by_filter('id', $siteid);
		}
		$lr = strtoupper($lr) =="L" ? cms_relation_accessor::$leftside : cms_relation_accessor::$rightside;
		$n = (int)$n;
		if ($n<1) $n = 1;
		$uniq = $uniq == 1 ? cms_relation_accessor::$uniq1w: cms_relation_accessor::$uniq0w;
		if ($apath == null) $apath = $this->currpath;
		if ($asite == null) $asite = $this->currsite;
		if ($alang == null) $alang = $this->currlang;
		return new cms_relation_accessor(
			$asite, $apath, $alang,
			$code, $lr, $n, $uniq,
			$ssite, $pat, $slang, $styp);
	}
	
	public function tpl_name() {
		return cms_config::$cc_cms_template_screen;
	}
	
	public function actions_prepare_check() {
		$this->post_data_validation();
		switch($this->check_authorization()) {
			case cms_userman::$right_allow:
				$this->acts = explode(',',$_POST['action']);
			break;
			case cms_userman::$right_allowdenyact:
				$this->acts = explode(',',$_POST['action']);
				$okacts = array_filter($this->acts, function($v) { return substr($v,0,3)=='ro_'; });
				$notokacts = array_filter($this->acts, function($v) { return strlen($v) && (substr($v,0,3)!='ro_'); });
				$this->acts = $okacts;
				$_POST['action'] = join(',',$okacts);
				if (!$this->currentry->options->suppress_readonly_message)
					$this->_template->showMessage(cms_template::$msg_info, '996');
				if (count($notokacts)>0) {
					$this->_template->showMessage(cms_template::$msg_error, '998');
				}
			break;
			case cms_userman::$right_deny:
				$this->_template->showMessage(cms_template::$msg_error, '997');
				$this->acts = array();
				$this->currpath = '';
				$this->currentry = null;
				$_POST['action'] = '';
			break;
		}
		$this->acts = array_filter($this->acts);
	}
	
	protected function initialize() {
	}
	
}

?>
