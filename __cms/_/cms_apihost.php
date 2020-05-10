<?php

abstract class cms_apihost extends cms_timely {

	protected $currsite;
	protected $currpath;
	protected $currlang;
	protected $currentry;
	protected $curruser;

	abstract public function actions();
	abstract public function display();
	 
	public function __construct($site, $lang, $path, $entry, $user) {
		$this->currlang = $lang;
		$this->currsite = $site;
		$this->currpath = $path;
		$this->currentry = $entry;
		$this->curruser = $user;

		if (!isset($_POST['actioncode'])) {
			$_POST['actioncode'] = '';
		}
		if (!isset($_POST['action'])) {
			$_POST['action'] = '';
		}

	}

	public function check_authorization() {
		return $this->curruser->check_access($this->currsite->id, $this->currpath, $this->currlang);
	}
	
	public function __get($a) {
		switch ($a) {
			case 'entry': return $this->currentry;
			case 'site': return $this->currsite;
			case 'siteid': return $this->currsite->id;
			case 'path': return $this->currpath;
			case 'lang': return $this->currlang;
		}
	}

	public function ensure_time_extension() {
		if (!cms_universe::run_time_check()) {
			$this->_template->addMessage('x93',__('Wykryto przekroczenie czasu. Czas pracy skryptu w momencie przerwania to &0 sekund. Żadne dane nie zostały zmienione'));
			$this->_template->showMessage(cms_template::$msg_error,'x93', array(cms_universe::get_run_time()));
			cms_universe::$puniverse->leave_change_mode(true);
			return false;
		}
		return true;
	}

}

?>