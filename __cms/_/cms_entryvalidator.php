<?php

abstract class cms_entryvalidator {
	
	public static $confirmed = 1;
	public static $rejected = 2;
	public static $go_next = 3;
	public static $skip = 4;
	
	protected $entry;
	protected $config;
	
	public function __construct($entry) {
		if (!($entry instanceof cms_entry)) {
			return false;
		}
		$this->entry = $entry;
		$this->config = array();
		$conf = $entry->get_option('validator');
		$vals = cms_universe::safesplitter($conf, ';');
		foreach ($vals as $val) {
			$p = strpos($val, ':');
			if ($p === FALSE)
				continue;
			$fld = substr($val,0,$p);
			$opt = substr($val,$p+1);
			$opt = cms_universe::safesplitter($opt, ',');
			$this->config[$fld] = $opt;
		}
	}
		
	private function decompose(&$tc, &$para) {		
		$j = strpos($tc,'(');
		if ($j===FALSE) {
			$para = array();
		} else {
			$para = cms_universe::safesplitter(substr($tc,$j+1,-1),':');
			$tc = substr($tc,0,$j);
		}
	}
	
	public function validate(&$details = null) {
		$results = array(true);
		$messages = array();
		$para = array();
		foreach($this->config as $fld=>$set) {
			$val = $this->entry->$fld;
			$res = (isset($results[$fld])?$results[$fld]:true);
			$msg = '';
			if (!$res)
				continue;
			$state = cms_entryvalidator::$go_next;
			foreach($set as $cm) {
				$this->decompose($cm, $para);
				if ($cm == 'msg') {
					// very special command
					$msg = __($para[0]); 
					continue;
				}
				if ($state > cms_entryvalidator::$go_next) {
					$state--;
					continue;
				}
				if ($state == cms_entryvalidator::$confirmed) {
					// if we have confirmation, we're fine, and no message, we know res is true
					break;
				}
				if ($state == cms_entryvalidator::$rejected) {
					// do not test more if rejected, but be ready to read message from cmd set
					continue;
				}
				$state = $this->process_command($cm, $para, $val);
				if ($state == cms_entryvalidator::$rejected) {
					$res = false;
				}
			}
			$results[$fld] = $res;
			if ($res == false) {
				$messages[$fld] = $msg;
			}
		}
		$res = array_reduce($results, array($this, 'valreduce'));
		if ($details === null)
			return $res;
		$details = array();
		foreach ($results as $fld=>$res) {
			$details[$fld] = array($res, ($res?'':$messages[$fld]));		
		}		
		return $res;
	}		
	
	protected function valreduce($x, $y) {
		return $x && $y;
	}
	
	protected function process_command($cmd, $para, $val) {
		switch ($cmd) {
			case 'emptyok':
				return ($val == ''?cms_entryvalidator::$confirmed:cms_entryvalidator::$go_next);
			case 'regex':
				return (preg_match('/'.$para[0].'/u', $val)==1?cms_entryvalidator::$go_next:cms_entryvalidator::$rejected);
			default:
				return $this->process_command_extended($cmd, $para, $val);
		}
	}
	
	abstract protected function process_command_extended($cmd, $para, $val);
	
}

?>