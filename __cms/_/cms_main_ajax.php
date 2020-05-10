<?php
class cms_main_ajax extends cms_main {
	
	/*
	 * text
	 * checkbox
	 * user
	 * img(numer)
	 * file
	 * select(pole:typ)
	 * relate(adres:kod:kierunek:ilosc:pole:typ)
	 * link(pole_z_opisem)
	 */
	protected function value_gen($entry, $field, $_type) {
		$type = cms_options::get_operation ( $_type );
		$v = $entry->$field;
		switch ($type) {
			case 'user' :
				if ($v)
					return cms_universe::$puniverse->userman ()->get_user ( $v )->desc;
				else
					return "---";
			case 'img' :
				$_data = cms_options::get_data ( $_type );
				$params = cms_universe::safesplitter ( $_data, ':' );
				if (is_numeric ( $params [0] ))
					return $entry->pictures [$params [0]]->file;
				else
					return $entry->graphs [$params [0]]->file;
			case 'file' :
				$cfi = $v;
				$fi = cms_entry::uncombine_file_information ( $cfi );
				$ffn = cms_config::$cc_cms_images_dir . $fi [0];
				if (($fi [0] != '') && is_file ( $ffn )) {
					$ffs = @stat ( $ffn );
					$cdk = cms_universe::combine_download_key ( $entry->site->id, $entry->pathl, $field, $entry->lang );
					$size = $ffs ['size'];
					$size /= 100;
					$size = round ( $size );
					$size /= 10;
					$params = array (
							'filename' => $fi [2],
							'downloadkey' => $cdk,
							'filetype' => $fi [1],
							'filesize' => $size 
					);
				} else {
					$params = array (
							'filename' => '',
							'downloadkey' => '',
							'filetype' => '',
							'filesize' => '' 
					);
				}
				return $params;
			case 'link' :
				$_data = cms_options::get_data ( $_type );
				$params = cms_universe::safesplitter ( $_data, ':' );
				if ($params[0]) {
					return array('desc'=>$entry->{$params[0]}, 'href'=>$v);
				} else {
					return array('href'=>$v);
				}
			case 'list' :
				$_data = cms_options::get_data ( $_type );
				$params = cms_universe::safesplitter ( $_data, ':' );
				if ($v>0)
					return($params[$v-1]);
				return '';
			case 'select' :
				$_data = cms_options::get_data ( $_type );
				$params = cms_universe::safesplitter ( $_data, ':' );
				if ($v) {
					$element = $this->site->get ( $v, $this->lang );
					if ($element instanceof cms_entry_data) {
						return $this->value_gen ( $element, $params [0], $params [1] );
					}
				}
				return '';
			case 'relate' :
				$_data = cms_options::get_data ( $_type );
				$params = cms_universe::safesplitter ( $_data, ':' );
				$lr = strtoupper ( $params [2] ) == "L" ? cms_relation_accessor::$leftside : cms_relation_accessor::$rightside;
				$n = $param [3];
				$uniq = cms_relation_accessor::$uniq0w;
				$rac = new cms_relation_accessor ( $entry->site, $entry->pathl, $entry->lang, $params [1], $lr, $n, $uniq, $entry->site, $params [0], $entry->lang, $styp );
				$elements = $rac->related ();
				$out = array ();
				foreach ( $elements as $el ) {
					$w = $this->site->get ( $el->pathl, $el->lang );
					$out [] = $this->value_gen ( $w, $params [4], $params [5] );
				}
				return join(", ",$out);
			case 'trim' :
				$_data = cms_options::get_data ( $_type );
				$params = cms_universe::safesplitter ( $_data, ':' );
				if (is_numeric ( $params [0] )) {
					$len = strlen($v);
					if ($len>$params[0]) {
						return substr($v, 0, $params[0]).'...';
					}
				}
			default : // text, checkbox:
				return $v;
		}
	}
	protected function dbe_list() {
		$GLOBALS['__lcollator'] = collator_create('pl_PL');
		collator_set_attribute($GLOBALS['__lcollator'], Collator::NUMERIC_COLLATION, Collator::ON);
		$sfl = $this->entry->get_option ( 'dbechildlistsearchfields' );
		$sfl = array_filter ( explode ( ',', $sfl ) );
		$sfl2 = array ();
		foreach ( $sfl as $sfn ) {
			if ($_GET ['s_' . $sfn]) {
				$sfl2 [] = $sfn;
			}
		}
		if ($this->entry instanceof cms_entry) {
			$cl = $this->entry->get_option ( 'dbechildlistfields' );
			if ($cl == false) {
				return;
			}
			$cl = explode ( ',', $cl );
			$filter = count ( $sfl2 ) > 0;
			$revcl = array_flip($cl);
			$sft = explode ( ',', $this->entry->get_option ( 'dbechildlisttypes' ) );
			$enable_numbers = (!($this->entry->get_option('dbechildlistnonumbers')))?1:0;
			$filters = null;
			if ($filter) {
				$filters = array ();
				foreach ( $sfl2 as $sfn ) {
					$nf = new cms_filter ();
					$nf->compare = ($sft[$revcl[$sfn]]=='date'?'%?':'$?');
					$nf->val0 = $_GET ['s_' . $sfn];
					$filters [$sfn] = $nf;
				}
			}
			
			$olist = $this->site->get ( $this->path . '/*', $this->lang, true, true, false, null, null, null, false, false );
			$out = array ();
			$sortf = 0;
			
			$sfl = explode ( ',', $this->entry->get_option ( 'dbechildlistsortfields' ) );
			foreach ( $cl as $id => $cli ) {
				if (@$_GET ['sort_' . $cli] && in_array ( $cli, $sfl )) {
					$sortf = $id + 1 + $enable_numbers;
					$sortd = $_GET ['sort_' . $cli] == "up" ? 1 : - 1;
					break;
				}
			}
			$objfieldid = count($cl)+1+$enable_numbers;
			foreach ( $olist as $pl => $o ) {
				if ($this->user->check_access($this->site->id, $o->pathl, $this->lang) == cms_userman::$right_deny) {
					continue;
				}
				$fok = true;
				$a = array (
						strtr ( $pl, ':/', '.-' ) 
				);
				if ($enable_numbers) {
					$a[] = 0;
				}
				foreach ( $cl as $idx => $cli ) {
					if ($cli == 'name') {
						if ($o->typepath != $o->pathl) {
							$tpn = ' [' . $this->site->get_option ( 'typename', $o->typepath ) . ']';
							$a [] = $o->$cli . $tpn;
							continue;
						}
					}
					$v = $this->value_gen ( $o, $cli, $sft [$idx] );
					if (is_object ( $filters [$cli] ) && ! $filters [$cli]->call ( $v )) {
						$fok = false;
						break;
					}
					$a [] = $v;
				}
				$a[] = $o;
				if ($fok)
					$out [] = $a;
			}
			$inhibitortest = '';
			$inhibitor = $this->entry->get_option ( 'dbechildlistinhibitor' );
			$isinhibitor = strlen($inhibitor)>0;
			if ($isinhibitor) {
				$inhibitortest = 'if ($a['.$objfieldid.']->'.$inhibitor.' != $b['.$objfieldid.']->'.$inhibitor.') return $a['.$objfieldid.']->'.$inhibitor.' - $b['.$objfieldid.']->'.$inhibitor.';';
			}
			if ($sortf > 0) {
				switch ($sft [$sortf - 1 - $enable_numbers]) {
					case 'date' :
						usort ( $out, create_function ( '$a,$b', $inhibitortest.'return ' . $sortd . ' * ($a[' . $sortf . '][0] - $b[' . $sortf . '][0]);' ) );
						break;
					case 'checkbox' :
						usort ( $out, create_function ( '$a,$b', $inhibitortest.'return ' . $sortd . ' * strcmp($a[' . $sortf . '], $b[' . $sortf . ']);' ) );
						break;
					default :
						usort ( $out, create_function ( '$a,$b', $inhibitortest.'return ' . $sortd . ' * collator_compare($GLOBALS[\'__lcollator\'], $a[' . $sortf . '], $b[' . $sortf . ']);' ) );
				}
			} else {
				usort ( $out, create_function ( '$a,$b', $inhibitortest ) );
			}
			$lpc = 0;
			foreach ($out as $key => $value) {
				$d0 = 0;
				if ($isinhibitor) {
					$d0 = 0 + $value[$objfieldid]->{$inhibitor};
				}
				$out[$key][$objfieldid] = $d0;
				if ($enable_numbers) {
					$out[$key][1] = ++$lpc;
				}
			}
			echo json_encode ( $out );
			return;
		}
		cms_http::bad_request (); // if no result was found
	}
	protected function rac_search($tags = false) {
		$ex_relation = $_GET ['relation'];
		$ex_search = $_GET [$tags ? 'term' : 'search'];
		$notallmatch = $ex_search != '';
		// find relation
		while ($this->entry instanceof cms_entry && $ex_relation) {
			$params = cms_universe::safesplitter ( $ex_relation, ':' );
			cms_options::drop_slashes_arp ( $params, ':' );
			$techno = cms_std_gui::relate_techno ( $params [6] );
			if (! $tags && $techno != 'advanced')
				break;
			if ($tags && $techno != 'tag')
				break;
			@list ( $siteid, $pat, $code, $lr, $n, $uniq, $techno, $slang, $types ) = $params;
			switch ($slang) {
				case 'all' :
					$slang = '';
					break;
				case 'current' :
					$slang = $this->lang;
					break;
				default :
					if (strlen ( $slang ) > 0) {
						// list lang
						$slang = explode ( ';', $slang );
					} else {
						// current
						$slang = $this->lang;
					}
			}
			if ($types != '') {
				$styp = explode ( ',', $types );
			} else {
				$styp = null;
			}
			if ($siteid == 'current') {
				$siteid = $this->site->id;
			}
			if ($this->site->id == $siteid) {
				$ssite = $this->site;
			} else {
				$ssite = cms_universe::$puniverse->site_by_filter ( 'id', $siteid );
			}
			$lr = strtoupper ( $lr ) == "L" ? cms_relation_accessor::$leftside : cms_relation_accessor::$rightside;
			$n = ( int ) $n;
			if ($n < 1)
				$n = 1;
			$uniq = $uniq == 1 ? cms_relation_accessor::$uniq1w : cms_relation_accessor::$uniq0w;
			$rac = new cms_relation_accessor ( $this->site, $this->path, $this->lang, $code, $lr, $n, $uniq, $ssite, $pat, $slang, $styp );
			/*if ($notallmatch) {
				$fx = new cms_filter ();
				$fx->compare = '$?';
				$fx->val0 = $ex_search;
				$rac->add_filter ( 'name', $fx );
			}*/
			$results = $rac->all_possible ();
			cms_entry::treesort ( $results );
			$out = array ();
			$c = 0;
			foreach ( $results as $eid => $res ) {
				$cname = $res->parent->name . ' / ' . $res->name;
				if (strlen($ex_search))
					if (stripos($cname,$ex_search)===false)
						continue;
				if ($tags)
					$out [] = $cname; // $res->name;
				else
					$out [] = array (
							'k' => $eid,
							'v' => $cname, // $res->name,
							'l' => $res->level 
					);
				$c ++;
				if ($c == cms_config::$cc_ajax_advanced_relation_result_limit)
					break;
			}
			echo json_encode ( $out );
			return; // result was output, good bye
		}
		cms_http::bad_request (); // if no result was found,
	}
	protected function picture_upload() {
		if ($this->entry instanceof cms_entry_xml) {
			cms_universe::$puniverse->enter_change_mode ( true, $this->site->dbe );
		}
		if ($this->entry instanceof cms_entry_db) {
			cms_universe::$puniverse->enter_change_mode ( false, true );
		}
		
		$filetab = "Filedata";
		if (@(is_uploaded_file ( $_FILES [$filetab] ['tmp_name'] ))) {
			$this->entry->add_picture ();
			$id = count ( $this->entry->pictures ) - 1;
			$text = '';
			
			$result = $this->entry->update_picture ( $id, $filetab, $text );
			
			if ($result) {
				$this->entry->store ();
				cms_universe::$puniverse->leave_change_mode ();
				echo "1";
			} else {
				cms_universe::$puniverse->leave_change_mode ( true );
				echo "0";
			}
		} else {
			cms_universe::$puniverse->leave_change_mode ( true );
			echo "0";
		}
	}
	protected function dump_entry() {
		$fields = explode ( ',', $_GET ['fields'] );
		$out = array ();
		foreach ( $fields as $f ) {
			if (strlen ( $f ))
				$out [$f] = $this->entry->$f;
		}
		echo json_encode ( $out );
	}
	protected function calendar() {
		$cevents = array();
		$lstatus = array(__("0. Niezaplanowana"),__( "1. Zaplanowana"),__("2. Przeprowadzona"),__("3. Odwołana przez klienta - płatna"),__("4. Odwołana przez klienta - bezpłatna"),__("5. Nauczyciel odwołał zajęcia"),__("6. Dane nieuzupełnione"));
		$set = $this->site->get('26/333', $this->currlang);
		$cstatus = array(
			$set->f_cc00,
			$set->f_cc01,
			$set->f_cc02,
			$set->f_cc03,
			$set->f_cc04,
			$set->f_cc05,
			$set->f_cc06
		);
	    $fdat = new cms_filter();
	    $fdat->compare = '#<<=';
	    $fdat->val0 = strtotime($_GET['start']);
	    $fdat->val1 = strtotime($_GET['end'])+86400;
	    $filters = array('f_date' => $fdat);
	    $root = $this->path;
	    if ($_GET['root']) {
	    	$root = $_GET['root'];
	    	if (cms_path::pathlen($this->path) == 2) {
	    		// klient
			    $fgrp = new cms_filter();
			    $fgrp->compare = '=';
			    $fgrp->val0 = $this->path;
			    $filters = array('f_date' => $fdat, 'f_klient' => $fgrp);
			}
	    	if (cms_path::pathlen($this->path) > 2) {
	    		// grupa lub uczestnik
			    $fgrp = new cms_filter();
			    $fgrp->compare = '=';
			    $fgrp->val0 = cms_path::leavepathpart($this->path,3);
			    $filters = array('f_date' => $fdat, 'f_group' => $fgrp);
			}
	    }
		$elessons = $this->site->get($root.'/*', $this->lang, true, true, false, $filters, null, null, true, true);
		$now = time();
		foreach($elessons as $les) {
			// calendar event
			$sid = $les->f_state;
			if ($sid == null)
				$sid = 1;
			if ($sid == 1) {
				if ($les->f_date[0] < ($now-86400)) {
					$sid = 6;
				}
			}
			$m = preg_match_all('/\d\d?:\d\d/', $les->f_time, $hm);
			$h = ($m)?($hm[0][0].":00"):'';
			if(strlen($h)==7)
				$h = "0".$h;
			$st = ($m?"T".$h:"");
			if (isset($hm[0][1])) {
				$en = $hm[0][1].":00";
				if(strlen($en)==7)
					$en = "0".$en;
				$en = "T".$en;
				$en = date("Y-m-d", $les->f_date[0]).$en;
			} else {
				$en = NULL;
			}
 			$ce = array(
				title => $les->name,
				start => date("Y-m-d", $les->f_date[0]).$st,
				allDay => false,
				color => $cstatus[$sid],
				__tooltip => $lstatus[$sid]
			);
			if (!is_null($en))
				$ce['end'] = $en;
			if ($sid) {
				$ce['link'] = $this->site->id .'.'. strtr($les->pathl,'/','-') .'.'. $this->lang;
				$ce['pathl'] = $les->pathl;
			}
			$cevents[] = $ce;
		}
		setcookie('cs'.$this->path, date('Y-m-d',($fdat->val0+$fdat->val1)/2), 0, '/');
		echo json_encode ($cevents);
	}

	protected function edit() {
		// all happens in current content context
		$comm = $_GET ['command'];
		switch ($comm) {
			case 'rac-search-tag' :
				$this->rac_search ( true );
				break;
			case 'rac-search' :
				$this->rac_search ();
				break;
			case 'dbe-list' :
				$this->dbe_list ();
				break;
			case 'picture-upload' :
				$this->picture_upload ();
				break;
			case 'dump' :
				$this->dump_entry ();
				break;
			case 'calendar' :
				$this->calendar();
				break;
			default :
				cms_http::bad_request ();
				return;
		}
	}
	public function main_implementation($login, $edit) {
		if (! $login) {
			return; // cannot access call without panel login
		}
		$this->authorize ( false );
		if (! ($this->user instanceof cms_user)) { // puszcza dalej jeśli nie mamy użytkownika
			return;
		}
		if ($this->check_date ( true )) {
			$s = strlen ( $_GET ['site'] ) ? $_GET ['site'] : cms_universe::$puniverse->session ()->csite;
			$l = strlen ( $_GET ['lang'] ) ? $_GET ['lang'] : cms_universe::$puniverse->session ()->clang;
			$p = strlen ( $_GET ['path'] ) ? $_GET ['path'] : cms_universe::$puniverse->session ()->cpath;
			$this->simple_localize ( $s, $l, $p );
			ob_start();
			$this->edit ();
			header("X-GZR-Exec-stats: ". serialize($GLOBALS['__QS__']));
			ob_end_flush();
		}
	}
}

?>
