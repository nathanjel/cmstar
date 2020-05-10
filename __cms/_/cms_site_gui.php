<?php
class cms_site_gui extends cms_std_gui {

	public function tpl_name() {
		return cms_config :: $cc_cms_template_screen;
	}

	public function actions() {
		// if ($_POST['actioncode'] == "logout") {
		// 	$this->login->logout();
		// 	return;
		// }
		$tl = $this->currlang;
		// $this->currlang = -1; // special conditions for site
		$this->actions_prepare_check();
		// $this->currlang = $tl;
		// are there any actions at all ?
		if (count($this->acts)) {
			// site data changed is only in DB
			cms_universe::$puniverse->enter_change_mode(false,true);
			try {
				$this->ext_a();
				cms_universe::$puniverse->leave_change_mode();
			} catch (Exception $e) {
				cms_universe::$puniverse->leave_change_mode(true);
				throw $e;
			}
		}
	}
	
	public function ext_a() {
		if ($_POST['action'] == 'update') {
			$this->currsite->name = $_POST['slug'];
			$this->currsite->desc = $_POST['name'];
			$this->currsite->store();
		}
		$xx = false;
		if ($_POST['action'] == 'mapsmaps') {
			$xx = true;
		}
		if ($xx || $_POST['action'] == 'genmaps') {
			$smm = new cms_sitemaps($this->currsite);
			if ($smm->create_all_sitemaps('../')) { 
				$this->_template->showMessage(cms_template :: $msg_succ, '906');
				touch(cms_config::$cc_cms_last_sitemaps);
			} else {
				$this->_template->showMessage(cms_template :: $msg_error, '905');
			}
		}
		if ($xx || $_POST['action'] == 'sendmaps') {
			$smm = new cms_sitemaps($this->currsite);
			$smm->create_notify_urls();
			foreach($smm->notify_sitemaps_urls as $url) {
				$para = array(parse_url($url,PHP_URL_HOST));
				if ($smm->notify_url($url)) { 
					$this->_template->showMessage(cms_template :: $msg_succ, '908', $para);
				} else {
					$this->_template->showMessage(cms_template :: $msg_error, '907', $para);
				}
			}
		}
		if ($_POST['action'] == 'ping' || $_POST['action'] == 'ping1') {
			$smm = new cms_sitemaps($this->currsite);
			$smm->create_ping_list($_POST['action'] == 'ping1' ? $this->currlang : -1);
			// print_r($smm->ping_list);
			
			foreach($smm->ping_list as $plist) {
				$para = array(parse_url($plist[0],PHP_URL_HOST));
				if ($smm->weblog_update_ping($plist[0], $plist[1], $plist[2])) { 
					$this->_template->showMessage(cms_template :: $msg_succ, '909', $para);
				} else {
					$this->_template->showMessage(cms_template :: $msg_error, '907', $para);
				}
			}
			
		}
	}

	public function display() {
		$this->rd_screen();
		$this->rd_sites();
		$this->rd_bread();
		
		$this->_template->setP('last-change-label', __('Identyfikator witryny'));
		$this->_template->setP('name-label', __('Opis witryny'));
		$this->_template->setP('code-label', __('Zdefiniowane wersje językowe'));
		$this->_template->setP('slug-label', __('Nazwa witryny'));
		$this->_template->setP('patht-label', __('Link główny'));
		$this->_template->setP('save-visible', true);
		$this->_template->setP('up-visible', false );
		$this->_template->setP('down-visible', false );
		$this->_template->setP('delete-visible', false );	
		$this->_template->setP('name-editable', true);
		
		// lang versions defined
		$lv = array();
		foreach ($this->currsite->allowed_langs() as $lang) {
			$lang = cms_universe::$puniverse->languages[$lang-1];
			$lv[] = $lang[2];
		}
		
		$this->_template->setP('last-change', $this->currsite->id);
		$this->_template->setP('name', $this->currsite->desc);
		$this->_template->setP('code', join(', ', $lv));
		$this->_template->setP('site', '');
		$this->_template->setP('path', '');
		$this->_template->setP('lang', '');
		$this->_template->setP('slug', $this->currsite->name);		
		$this->_template->setP('patht', 'http://'.$this->currsite->dhm[$this->currlang]);
		$this->_template->setP('new-child-visible', false);
		$this->_template->setP('new-sibling-visible', false);
		
		$this->rd_menu();

		if ($this->curruser->check_access($this->currsite->id, "ANYPATH", $this->lang) != cms_userman::$right_deny) {
			

		$this->_template->addTab('g0', __('Informacje'));

		$this->_template->addGroup('g0', 'g', __('Technologia'));
		$this->_template->addField('g', __('Wykorzystanie bazy danych'), '', 'span', null, $this->currsite->dbe?__('Tak'):__('Nie'));
		$this->_template->addField('g', __('Podstawowe źródło danych'), '', 'span', null, $this->currsite->fulldbe?__('Baza danych'):__('XML'));

		$this->_template->addGroup('g0', 'ga', __('Across domains'));
		$this->_template->addField('ga', __('Lista domen dodatkowych dla danych (do odczytu)'), 'xc1', 'code', array('rows'=>10, 'cols'=>76, 'mode'=>'php'), join("\n", $this->currsite->resourcedomains));


		$this->_template->addTab('g1', __('Mapy witryny'));
		
		$this->_template->addGroup('g1', 'g1a', __('Stan'));
		$this->_template->addField('g1a', __('Mapy witryny'), '', 'span', null, $this->currsite->options->sitemap?__('Włączone'):__('Wyłączone'));

		$smm = new cms_sitemaps($this->currsite);
		
			if ($this->currsite->options->sitemap) {	
				$this->_template->addField('g1a', __('Kompresja'), '', 'span', null, $this->currsite->options->sitemap_gz?__('Tak'):__('Nie'));
				$this->_template->addGroup('g1', 'g1b', __('Mapy witryn'));			
				$fx = cms_config::$cc_cms_last_sitemaps;
				$fxs = @stat($fx);
				if ($fxs[9]) {
					$lmd = date('Y-m-d H:i:s', $fxs[9]);
				} else {
					$lmd = __('mapy nie były jeszcze tworzone');
				}
				$this->_template->addField('g1b', __('Data ostatniego utworzenia map'), '', 'span', null, $lmd);
				$this->_template->addField('g1b','', '', 'button', array('action'=>'genmaps'), __('Aktualizuj mapy witryny'));
				// rozgłaszanie
				$smm->create_notify_urls();
				$this->_template->addGroup('g1', 'g1c', __('Rozgłaszanie map witryn'));
			$this->_template->addField('g1c', __('Lista URL'), '', 'span', null, 'Lista prezentuje skonfigurowane URL. przyjmujące adres mapy strony w 	formacie Sitemaps 0.9, wg. sitemaps.org. Zmiany można dokonać w ustawieniach konfiguracji witryn (options.xml).');
				$this->_template->addField('g1c',  __('Lista URL do rozgłoszenia'), '', 'code',
					array('rows'=>10, 'cols'=>76, 'mode'=>'php'), join("\n", $smm->notify_sitemaps_urls));
				$this->_template->addField('g1c','', '', 'button', array('action'=>'sendmaps'), __('Rozgłoś informacje o mapach'));			
				$this->_template->addField('g1c','', '', 'button', array('action'=>'mapsmaps'), __('Aktualizuj oraz rozgłoś informacje o nowych mapach'));
			}
			
			$this->_template->addTab('g2', __('Pingowanie XMLRPC'));
			
			$this->_template->addGroup('g2', 'g2a', __('Stan'));
			$this->_template->addField('g2a', __('Pingowanie'), '', 'span', null, $this->currsite->options->xmlrpc_ping?__('Włączone'):__('Wyłączone'));
			
			if ($this->currsite->options->xmlrpc_ping) {
				$this->_template->addGroup('g2', 'g2b', __('Serwery'));
			$this->_template->addField('g2b', __('Lista serwerów'), '', 'span', null, 'Lista prezentuje skonfigurowane serwery, obsługujące metodę 	weblogUpdates przez XMLRPC. Zmiany można dokonać w ustawieniach konfiguracji witryn (options.xml).');
				$this->_template->addField('g2b',  __('Lista skonfigurowanych serwerów XMLRPC weblogUpdates'), '', 'code',
					array('rows'=>10, 'cols'=>76, 'mode'=>'php'), join("\n", $smm->ping_servers_list));
				$calllist = '';
				$smm->create_ping_list();
				$cx = count($smm->ping_list);
				$fx = cms_config::$cc_cms_last_xmlrpc;
				$fxs = @stat($fx);
				if ($fxs[9]) {
					$lmd = date('Y-m-d H:i:s', $fxs[9]);
				} else {
					$lmd = __('nie odbyło się');
				}
				$this->_template->addGroup('g2', 'g2c', __('Pingowanie'));
				$this->_template->addField('g2c', __('Data ostatniego pingowania'), '', 'span', null, $lmd);
				$waittime = $cx * 3;
				$waittimewj = count($smm->ping_servers_list) * 3;
			$this->_template->addField('g2c','', '', 'button', array('action'=>'ping1'), __('Wykonaj pingowanie bieżącej wersji językowej, przybliżony czas')	 .' '.$waittimewj . ' '.__('sekund.'));
			$this->_template->addField('g2c','', '', 'button', array('action'=>'ping'), __('Wykonaj pełne pingowanie, przybliżony czas') .' '.$waittime . ' '	.__('sekund.'));
			}

		}
				
		$this->translate();
		echo $this->_template;
	}
}
?>