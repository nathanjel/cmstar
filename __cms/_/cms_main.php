<?php

class cms_main {
	
	public $site;
	public $lang;
	public $path;
	public $entry;
	public $user;
	public $login;

	protected $relocalize = false;
	
	protected function simple_localize($idsite, $idlang, $inpath) {
		// site
		// cms_tp::debug(array($idsite, $idlang, $inpath), true);

		$s = false;
		$s = cms_universe::$puniverse->site_by_filter('id', $idsite);
		if ($s == false) {
			$s = cms_universe::$puniverse->site_by_filter('default', true);
		}
		if ($s == false) {
			$j = cms_universe::$puniverse->sites_list;
			$j = array_shift($j);
			$s = cms_universe::$puniverse->site_by_filter('id', $j[0]);
		}
		if ($s == false) {
			throw new RuntimeException('no site found, cannot continue');
		}
		$this->site = $s;
		$a1 = $idlang;
		if (!in_array($a1, $s->langs)) { 
			if ($s->defaultlang > 0) {
				// fallback to default lang
				$a1 = $s->defaultlang;
			} else {
				$a = array_values($s->langs);
				$a1 = $a[0];
			}
		}
		if (!($a1 > 0)) {
			throw new RuntimeException('cannot determine ANY lang for site '.$s->name);
		}
		// lang requested?
		if (@($_POST['action'] == 'switch_lang')) {
			// language switch denies any other action
			$a1 = $_POST['id'];
			$_POST['action'] = '';     	
		}
		$this->lang = $a1;
		// path/entry
		if ($inpath == '0') {
			// do not load entry, will be using site admin tools
			$this->entry = null;
			$this->path = '0';
			// default language will be loaded, but to check actions we have to ask for -1
		} else {
			$a2 = preg_replace('@[^0-9/]@', '_', $inpath);
			$en = $s->get($a2, $a1);
			if($en instanceof cms_entry) {
				// all ok!
				$this->entry = $en;
				$this->path = $a2;
			} else {
				$a2 = $s->route;
				$en = $s->get($a2, $a1);
				if ($en instanceof cms_entry) {
					$this->entry = $en;
					$this->path = $a2;
				} else {
					$this->entry = null;
					$this->path = '';
				}
			}
		}
		// cms_tp::debug(array($this->entry, $this->path), false);
	}
	
	protected function login_screen($newpass = false) {
		$g = new cms_login_gui(null,null,null,null,null);
		$g->set_login($this->login);
		if($newpass) {
			$g->new_pass_mode();
		}
		$g->display();
	}
	
	protected function check_rules_data($path) {
		$rdata = $this->site->get_option('rules_acceptance_control');
		if (!$rdata)
			return false;
		$robj = $this->site->get($rdata, $this->lang);
		if (!($robj instanceof cms_entry))
			return false;
        if (!strlen($robj->f_rules_apply))
        	return false;
        $cv = cms_path::convert_lpath_to_pcre($robj->f_rules_apply);
        if (!preg_match($cv, $path))
        	return false;
        $tobj = $this->site->get($path, $this->lang);
        if ($tobj->{$robj->f_rcf_flag})
        	return false;
        return array($robj->f_rules_text, $robj->f_rules_but);
	}

	protected function edit() {
		$guiclass = 'cms_std_gui';
		if ($this->entry) {
			$xc = $this->entry->get_option('cms_gui');
		}
		if (strlen(@$xc)) {
			$guiclass = $xc;
		}		
		if ($this->path == '0') {
			// site editor
			$guiclass = 'cms_site_gui';
		}
		// a był w ogóle login ?
		if (!$this->login->is()) {
			$this->login_screen();
			return;
		}
		$e = new $guiclass($this->site, $this->lang, $this->path, $this->entry, $this->user);
		$e->set_login($this->login);
		$e->actions();
		// a może był logout ?
		if (!$this->login->is()) {
			$this->login_screen();
			return;
		}
		// kontrola weryfikacji dostępu
		list($ls, $lp) = $this->login->user()->landing_path($this->lang);
		list($t, $b) = $this->check_rules_data($lp);
		if (strlen($t) && strlen($b)) {
			$out = $e->rule_accept_display($t, $b);
		} else {
			$out = $e->display();
		}
		@header("X-GZR-Exec-stats: ". serialize($GLOBALS['__QS__']));
		echo $out;
		$this->site = $e->site;
		$this->path = $e->path;
		$this->lang = $e->lang;
	}
	
	protected function web_localize($path) {
		$am = array();
		$mint = 0;
		$defaultlang = false;
		foreach(cms_universe::$puniverse->sites_list as $site) {
			foreach ($site['lang'] as $dm => $lang) {
				if (!$dm)
					continue;
				$t = preg_match('/'.$dm.'/', $path, $am, PREG_OFFSET_CAPTURE);
				if ($t == 1) {
					if (strlen($am[0][0]) > $mint) {
						$mint = strlen($am[0][0]);
						$sid = $site[0];
						$rim = strlen($am[0][0]);
						$this->lang = $lang;
						$site_def_lang = $site[4]; 
						$defaultlang = ($lang == 0);
					}
				}
			}
		}
		if ($mint == 0) return false;
		$this->site = cms_universe::$puniverse->site_by_filter('id', $sid);
		$spstrim = substr($path, $rim);
		if (cms_config::$cc_path_urlencode_on_web_localize) {
			$spstrimt = explode('/', $spstrim); 
			$spstrim = implode('/', array_map('urlencode', array_map('urldecode', $spstrimt)));
		}
		if (cms_config::$cc_path_lowercase) {
			$spstrim = strtolower($spstrim);
		}
		$trylangs = array();
		if ($defaultlang && (@($_COOKIE['_LANG_COUNTRY']) < 1)) {
			if (cms_config::$cc_ip_based_lang == 'geoip-local') {
				if(!function_exists('geoip_country_code_by_name')) { 
					require("geoip.inc");
					$gi = geoip_open(cms_config::$cc_geoip_file_path, GEOIP_STANDARD);
					$la = strtolower(geoip_country_code_by_addr($gi, $_SERVER['REMOTE_ADDR']));
					geoip_close($gi);
				} else {
					$la = strtolower(geoip_country_code_by_name($_SERVER['REMOTE_ADDR']));
				}
				setcookie('_LANG_COUNTRY', $la, time()+86400, '/', cms_config::$cc_cms_host);
				$_COOKIE['_LANG_COUNTRY'] = $la;				
				foreach (cms_universe::$puniverse->languages as $lang) {
					if ($lang[4] == $la) {							
						setcookie('_LANG_ADVICE', $lang[0], time()+86400, '/', cms_config::$cc_cms_host);
						$_COOKIE['_LANG_ADVICE'] = $lang[0];
						$trylangs[] = $lang[0];
						break;
					}
				}
			}
		}			
		if ($defaultlang) {
			$trylangs[] = $site_def_lang;
		} else {
			$trylangs[] = $this->lang;
		}
		foreach ($trylangs as $tl) {
			$this->lang = $tl;
			$this->path = $this->site->pathlt_to_pathl($spstrim, $this->lang);
			if ($this->path == null) {
				$lo = '';
				$pp = $this->site->relaxed_pathlt_to_pathl($spstrim, $this->lang, $lo);			       
				if ($pp != null && strlen($lo)) {
					$lop = $this->site->pathlt_to_pathl($lo, $this->lang);	
					$this->path = $pp;
					$_GET['_SUB_PATH'] = $lop;
					$_REQUEST['_SUB_PATH'] = $lop;
				}
			}
			$en = $this->site->get($this->path, $this->lang);
			if($en instanceof cms_entry) {
				// all ok!
				$this->entry = $en;
			} else {
				if (substr($path, $rim) == '') {
					$a2 = $this->site->route;
					$en = $this->site->get($a2, $this->lang);
					if ($en instanceof cms_entry &&
							($en->published ||
								(($_GET['m'] != '') &&
								 ($_GET['m'] == md5($en->site->id . $en->pathl . $en->lang . cms_config::$cc_cms_misc_e1code))&&
								 ($en->get_option('preview_unpublished'))
								)
							)
						) {
						$this->entry = $en;
						$this->path = $a2;
					} else {						
						continue;
					}
				} else {
					continue;
				}
			}            
			@$_GET['_LANG_COUNTRY'] = $_COOKIE['_LANG_COUNTRY'];
			@$_REQUEST['_LANG_COUNTRY'] = $_COOKIE['_LANG_COUNTRY'];
			@$_GET['_LANG_ADVICE'] = $_COOKIE['_LANG_ADVICE'];
			@$_REQUEST['_LANG_ADVICE'] = $_COOKIE['_LANG_ADVICE'];
			
			// fix possible broken links on the default site by redirecting
			// to the proper site
			if ($defaultlang) {
			    $protocol = $_SERVER['SERVER_PORT'] == 80 ? 'http://' : 'https://';
			    $url = $this->site->dhm[$this->lang];
			    header("Location: $protocol$url");
			    die();
			}
			
			return true;
		}
		$this->entry = null;
		$this->path = '';
		return false;
	}
	
	protected function display() {
		$s = new cms_server($this->site, $this->entry, $this->path, $this->lang);
		$s->serve();
	}
	
	protected function authorize($fallback_to_login = true) {
		$this->login = new cms_login();
		if ($this->login->is()
		  &&  
		  ((!cms_config::$cc_login_ip_control) || ($_SERVER['REMOTE_ADDR'] == cms_universe::$puniverse->session()->lip)) ) {
			// zalogowany!
			$this->user = $this->login->user();
		} else {
			// z jakiegoś powodu niekoniecznie zalogowany
			if ($fallback_to_login) {
				$e = new cms_login_gui($this->site, $this->lang, $this->path, $this->entry, $this->user);
				$e->set_login($this->login);
				$e->actions();
				if (!$this->login->is()) {
					// a jak sie nie zaloguje to trzeba pokazać ekran
					$e->display();                    
				} else {
					{
						// jeśli się jednak zaloguje to wyświetlanie nie ma sensu
						// i trzeba zapamiętać dane
						$this->user = $this->login->user();
					}
				}
			} else {
				cms_http::cannot_authorize();
				return;
			}
		}
	}
	
	protected function security() {
		// TODO session step control and action time control
	}
	
	public function main($login, $edit) {
		$this->check_hostname();
		$this->security();
		$this->main_implementation($login, $edit);
		cms_universe::$puniverse->auto_rollback(cms_config::$cc_auto_rollback_event_callback);
	}
	
	public function main_implementation($login, $edit) {
		if ($edit) {
			// load language
			$gl = @$_POST['guilang'];
			if (!strlen($gl)) {
				$gl = cms_lp::get_session_language_code();
			}
			cms_lp::set_language_code($gl);
		}				
		if ($login) {
			$this->authorize();
			if (!($this->user instanceof cms_user)) { // puszcza dalej jeśli mamy użytkownika
				return;
			}
			if ($this->login->user()->needs_to_change_password() && ($this->login->fresh() || $this->login->login_error()) || $_POST['actioncode']) {
				// nie loguj, żądaj zmiany hasła
				$e = new cms_login_gui($this->site, $this->lang, $this->path, $this->entry, $this->user);
				$e->set_login($this->login);
				$e->actions();
				if (!cms_universe::$puniverse->session()->extauth && $this->login->user()->needs_to_change_password()) {
					$e->new_pass_mode();
					$e->display();
					return;
				}
			}
			// proxy scenario
			$uo = cms_universe::$puniverse->userman()->get_user(cms_universe::$puniverse->session()->luser);
			if ($_POST['action'] == 'ume_takeover' && strlen($_POST['uid']) && ($uo->check_access('*', '*', '*') == cms_userman::$right_allow)) {
				cms_universe::$puniverse->session()->luser = $_POST['uid'];
				$this->relocalize = true;
				// cms_universe::$puniverse->session()->cxsite = '';
				// cms_universe::$puniverse->session()->cxlang = '';
				// cms_universe::$puniverse->session()->cxpath = '';
				return $this->main_implementation($login, $edit);		
			}
		}
		if ($edit && $this->check_date(true)) {
			// existing route
			$s = @strlen($_REQUEST['site']) ? $_REQUEST['site'] : cms_universe::$puniverse->session()->cxsite;
			$l = @strlen($_REQUEST['lang']) ? $_REQUEST['lang'] : cms_universe::$puniverse->session()->cxlang;
			$p = @strlen($_REQUEST['path']) ? $_REQUEST['path'] : cms_universe::$puniverse->session()->cxpath;
			if (
				// relocalize
				($this->relocalize) ||
				// if there is no existing route
				(strlen($p)==0) ||
				// or it's a fresh login, and we do not have access in our landing place 
				($this->login->fresh() && ($this->user->check_access($s,$p,$l) == cms_userman::$right_deny))
			) {
				// then generate a landing path
				@list($s, $p) = $this->user->landing_path($l);
			}
			$this->simple_localize($s, $l, $p);
			cms_universe::$puniverse->session()->cxsite = $this->site->id;
			cms_universe::$puniverse->session()->cxlang = $this->lang;
			cms_universe::$puniverse->session()->cxpath = $this->path;
			$this->edit();
		} elseif ($this->check_date(false)) {
			switch (cms_config::$cc_routing_method) {
			case 'REQUEST_URI':
				$qs = $_SERVER['REQUEST_URI'];
				$qs = substr($qs, 1); // remove leading '/'
				$k = strpos($qs,'?');
				if ($k !== false) {
					// remove trailing query params
					$qs = substr($qs,0,$k);
				}
				break;
			case 'QUERY_STRING':
				$k = strpos($_SERVER['QUERY_STRING'],'&');
				if ($k === false) {
					$qs = $_SERVER['QUERY_STRING'];
				} else {
					$qs = substr($_SERVER['QUERY_STRING'],0,$k);
				}
				break;
			default:
				throw new RuntimeException("unknown routing option");
			}
			if ($this->web_localize($_SERVER['HTTP_HOST'].'/'.$qs)) {
				// published or overruled by CMS preview option
				$this->display();
			} else {
				// not localized, but we need to check wheter a site or resource is missing
				// graceful exit out of this situation is a 404 if one is set of course...
				// and if we have a site :)
				$nosite = false;
				if (!$this->site instanceof cms_site) {
					// no site even... let's load the default one with default language
					$nosite = true;
					$this->site = cms_universe::$puniverse->site_by_filter('default', 1);
					$this->lang = $this->site->defaultlang;
				}
				if ($this->site instanceof cms_site) {
					$a404 = $this->site->get_option('404','0');
					if ($a404) {
						$this->path = $a404;
						$this->entry = $this->site->get($a404, $this->lang);
						if ($this->entry instanceof cms_entry) {
							$this->display();
							return;			
						}
					}

				}
				if ($nosite)
					throw new RuntimeException("site identification failed for ".$_SERVER['HTTP_HOST'].'/'.$qs);
				else
					throw new RuntimeException("not found ".$qs);
			}
		}		
	}
	
	protected function check_hostname() {
		if(cms_universe::$puniverse->session()->licence_host_validated)
			return;
		$hostmap = cms_licence::get()->hosts;
		$hmtest = false;
		foreach($hostmap as $sh) {
			if (preg_match($sh,$_SERVER["SERVER_NAME"])) {
				$hmtest = true;
				cms_universe::$puniverse->session()->licence_host_validated = true;
				break;
			}
		}            
		if (!$hmtest)
			throw new RuntimeException("cms licence does not cover host ".$_SERVER["SERVER_NAME"]);
	}
	
	protected function check_date($edit) {
		if ($edit) {
			$d0 = cms_licence::get()->edit_valid_until;
			$v = cms_universe::$puniverse->session()->licence_edit_date_validated;
		} else {
			$d0 = cms_licence::get()->valid_until;
			$v = cms_universe::$puniverse->session()->licence_display_date_validated;
		}
		if (!$v) {
			if (($d0 != -1) && ($d0 < time())) {
				throw new RuntimeException($edit?'edit':'display' . " cms licence was valid only until ".date(cms_config::$cc_lastmod_date_format, $d0));
				$v = false;
			}
		}
		if ($edit) {
			cms_universe::$puniverse->session()->licence_edit_date_validated = true;
		} else {
			cms_universe::$puniverse->session()->licence_display_date_validated = true;
		}
		return true;
	}
	
}

?>
