<?php
function languages_psort($b, $a) {
	if ($a[4] != $b[4]) {
		if ($a[4])
			return 2;
		if ($b[4])
			return -2;
	} else {
		if ($a[3] != $b[3]) {
			if ($a[3])
				return 1;
			if ($b[3])
				return -1;
		} else {
			return $b[0] - $a[0];
		}
	}
}

class cms_template {

	public static $msg_error = 1;
	public static $msg_info = 2;
	public static $msg_succ = 3;

	protected $templatedir;
	protected $template_replace_singature = "/* TEMPLATE_JS_WILL_GO_HERE */";
	protected $template = false;

	protected $messages = array ();

	protected $_msg = array ();
	protected $_par = array ();
	protected $_hc = false;
	protected $_hcla = array();
	protected $_sites = array ();
	protected $_bcra = array ();
	protected $_bcrb = array ();
	protected $_menu = array ();
	protected $_tabs = array ();
	protected $_types = array ();
	protected $_gids = array ();
	protected $_flds = array ();
	protected $_stid = '';
	protected $so = false;

	protected $_langs = array ();
	protected $_langsl = array ();

	protected $_bf = array ();

	protected $_replacesf = array ();
	protected $_replacest = array ();

	protected $disable = false;
	protected $disabledList = array();
	protected $enabledList = array();

	private $vcode = '';

	public $contentLanguage = '';

	public function disable($fname) {
		$this->disabledList[$fname] = 1;
	}

	public function enable($fname) {
		$this->enabledList[$fname] = 1;
	}

	public function setvcode($vc) {
		$this->vcode = $vc;
	}

	public function getvcode() {
		return $this->vcode;
	}

	public function disableAll() {
		$this->disable = true;
	}

	public function suppressOutput() {
		$this->so = true;
	}

	public function repstr($f, $t) {
		$this->_replacesf[] = $f;
		$this->_replacest[] = $t;
	}
	public function addType($block, $name, $type) {
		$this->_types[$block][] = array (
			$name,
			$type
		);
	}

	public function addLanguage($id, $ename, $nname, $data, $first) {
		$this->_langs[] = array (
			$id,
			$ename,
			$nname,
			$data,
			$first
		);
	}

	public function addLoginLanguage($id, $name, $sel) {
		$this->_langsl[] = array (
			$id,
			$name,
			$sel
		);
	}

	public function addTab($tabid, $tabname) {
		$this->_tabs[$tabid] = array (
			'name' => $tabname,
			'groups' => array (),
			'hidden' => false
		);
	}

	public function hideTab($tabid) {
		$this->_tabs[$tabid]['hidden'] = true;
	}

	public function addGroup($tabid, $groupid, $groupname) {
		$this->_tabs[$tabid]['groups'][$groupid] = $groupname;
	}

	public function addField($parentid, $fieldlabel, $fieldname, $fieldtype, $fieldtypepara, $fielddata) {
		$this->_flds[] = array (
			'parentid' => $parentid,
			'label' => $fieldlabel,
			'name' => $fieldname,
			'type' => $fieldtype,
			'params' => $fieldtypepara,
			'data' => $fielddata
		);
	}

	public function selectTab($tabid) {
		if (isset ($this->_tabs[$tabid]))
			$this->_stid = $tabid;
	}

	public function addMenu($parentpath, $mypath, $mylevel, $name, $selected) {
		$this->_menu[] = array (
			$parentpath,
			$mypath,
			$mylevel,
			$name,
			$selected
		);
	}

	public function breadcrumb($names, $paths) {
		$j = min(count($names), count($paths));
		$this->_bcra = array_slice($names, 0, $j);
		$this->_bcrb = array_slice($paths, 0, $j);
	}

	public function addSite($sid, $sitename, $selected) {
		$this->_sites[$sid] = array (
			$sitename,
			$selected
		);
	}

	public function hideContent() {
		$this->_hc = true;
	}

	public function hideClass($cn) {
		$this->_hcla[] = $cn;
	}

	public function setP($name, $value) {
		switch ($name) {
			case 'save-visible' :
			case 'up-visible' :
			case 'down-visible' :
			case 'new-child-visible' :
			case 'new-sibling-visible' :
			case 'new-picture-visible' :
			case 'delete-visible' :
			case 'name-editable' :
				$this->_par[$name] = ($value ? 'true' : 'false');
				break;
			default :
				$this->_par[$name] = $value;
		}
	}

	public function addMessage($messageid, $messagetext) {
		$this->messages[$messageid] = $messagetext;
	}

	public function showMessage($class, $messageid, $messageparams = NULL) {
		// load message content from options if not there already
		if (!isset ($this->messages[$messageid])) {
			$tmsg = $this->messages[$messageid] = __(cms_universe :: $puniverse->options()->get('*', 'message_' . $messageid, '**'));
		} else {
			$tmsg = $this->messages[$messageid];
		}
		if ($messageparams) {
			reset($messageparams);
			while (list ($mpid, $mpv) = each($messageparams)) {
				$tmsg = str_replace('&' . $mpid, $mpv, $tmsg);
			}
		}
		$this->_msg[] = array (
			$class,
			$tmsg
		);
	}

	public function __construct() {
		$this->templatedir = cms_config :: $cc_cms_template_dir . '/' . cms_config :: $cc_cms_template . '/';
	}

	public function setTemplate($template) {
		$this->template = $template;
		if (!is_file($this->templatedir . $this->template))
			throw new RuntimeException('__CMS panel template not found : ' . $this->templatedir . $this->template);
	}

	public function __toString() {
		if ($this->so)
			return '';
		if ($this->template) {
			$tdata = @ file_get_contents($this->templatedir . $this->template);
			$tdata = str_replace($this->_replacesf, $this->_replacest, $tdata);
			return str_replace($this->template_replace_singature, $this->generateJsCodeBlock(), $tdata);
		} else {
			return '';
		}
	}

	public function markFieldBad($fieldname, $message) {
		$this->_bf[$fieldname] = $message;
	}

	protected function generateJsCodeBlock() {
		if ($this->so)
			return '';
		$out = '';
		$out .= "var cms_contents_language_code = " . $this->jsstring($this->contentLanguage) . ';';
		// sites
		reset($this->_sites);
		while (list ($sid, $sdata) = each($this->_sites)) {
			$sname = $this->jsstring($sdata[0]);
			$selected = ($sdata[1] ? 'true' : 'false');
			$out .= '$' . "t.addSite($sid, $sname, $selected);";
		}
		$out .= '$' . "t.sitesComplete();";
		// breadcrumb
		$e1 = array ();
		$e2 = array ();
		for ($j = 0; $j < count($this->_bcra); $j++) {
			$e1[] = $this->jsstring($this->_bcra[$j]);
			$e2[] = $this->jsstring($this->_bcrb[$j]);
		}
		$e1 = join(',', $e1);
		$e2 = join(',', $e2);
		$e1 = '[' . $e1 . ']';
		$e2 = '[' . $e2 . ']';
		$out .= '$' . "t.breadcrumb($e1, $e2);";
		// main menu
		reset($this->_menu);
		while (list ($mid, $mdata) = each($this->_menu)) {
			$pp = $this->jsstring($mdata[0]);
			$mp = $this->jsstring($mdata[1]);
			$ml = $this->jsstring($mdata[2]);
			$n = $this->jsstring($mdata[3]);
			$s = $mdata[4];
			$out .= '$' . "t.addMenu($pp, $mp, $ml, $n, $s);";
		}
		$out .= '$' . "t.menuComplete();";
		// tabs & groups
		$valid_parents_list = array();
		reset($this->_tabs);
		while (list ($tid, $tdata) = each($this->_tabs)) {
			if (!$tdata['hidden']) {
				$ti = $this->jsstring($tid);
				$tn = $this->jsstring($tdata['name']);
				$out .= '$' . "t.addTab($ti, $tn);";
				$valid_parents_list[] = $tid;
				reset($tdata['groups']);
				while (list ($gid, $gname) = each($tdata['groups'])) {
					$gi = $this->jsstring($gid);
					$gn = $this->jsstring($gname);
					$out .= '$' . "t.addGroup($ti, $gi, $gn);";
					$valid_parents_list[] = $gid;
				}
			}
		}
		// fields
		reset($this->_flds);
		$efpid = '';
		$cnt = 0;
		$disabled_fields = array();
		while (list ($fid, $fdata) = each($this->_flds)) {
			if (!in_array($fdata['parentid'], $valid_parents_list)) {
				$disabled_fields[] = $fdata['name'];
				continue;
			}
			$d0 = (($this->disable || $this->disabledList[$fdata['name']]==1) && !($this->enabledList[$fdata['name']]==1)) ? 1 : 0;
			if (!is_array($fdata['params']))
				$fdata['params'] = array();
			$fdata['params']['disabled'] = $d0;
			if ($d0) {
				// cleanup value list, except radio
				if (isset($fdata['params']['v']) && ($fdata['type'] != 'radio')) {
					$vl = array($fdata['params']['v'][0]);
					$kl = array($fdata['params']['k'][0]);
					foreach($fdata['params']['v'] as $k=>$v)
						if ($fdata['params']['k'][$k]==$fdata['data']) {
							$fdata['data'] = 1;
							$vl[] = $fdata['params']['v'][$k];
							$kl[] = '1';
							break;
						}
					$fdata['params']['v'] = $vl;
					$fdata['params']['k'] = $kl;
				}
				$disabled_fields[] = $fdata['name'];
			} else {
				$efpid = $fdata['parentid'];
			}
			$out .= '$' . "t.addField(" . json_encode($fdata) . ");";
		}
		// disabled validator
		$disabled_fields = array_merge($disabled_fields, array_keys($this->disabledList));
		$disabled_fields = array_filter($disabled_fields);
		$disabled_validator = count($disabled_fields) . cms_config::$cc_cms_misc_e1code . $this->getvcode();
		$disabled_fields = '' . join(',',$disabled_fields);
		$disabled_validator = sha1($disabled_validator.$disabled_fields);
		$out .= '$' . "t.addField(" . json_encode(array(
					'parentid' => $efpid,
					'name'=>'df',
					'type'=>'hidden',
					'data'=>$disabled_fields
				)) . ");";
		$out .= '$' . "t.addField(" . json_encode(array(
					'parentid' => $efpid,
					'name'=>'dv',
					'type'=>'hidden',
					'data'=>$disabled_validator
				)) . ");";
		// we're finishing
		$out .= '$' . "t.tabsComplete();";
		$out .= '$' . "t.groupsComplete();";
		if ($this->_stid) {
			$stid = $this->jsstring($this->_stid);
			$out .= '$' . "t.selectTab($stid);";
		}
		// messages
		reset($this->_msg);
		while (list ($msid, $msg) = each($this->_msg)) {
			$msg[1] = $this->jsstring($msg[1]);
			$out .= '$' . "t.displayMessage($msid, $msg[0], $msg[1]);";
		}
		// new types
		reset($this->_types);
		while (list ($typeblock, $types) = each($this->_types)) {
			$tb = $this->jsstring($typeblock);
			foreach ($types as $type) {
				$tn = $this->jsstring($type[0]);
				$tt = $this->jsstring($type[1]);
				$out .= '$' . "t.addType($tb,$tn,$tt);";
			}
		}
		// parameters
		reset($this->_par);
		while (list ($pn, $par) = each($this->_par)) {
			$pn = $this->jsstring($pn);
			$par = $this->jsstring($par);
			$out .= '$' . "t.set($pn,$par);";
		}
		// bad fields marking
		reset($this->_bf);
		while (list ($fn, $msg) = each($this->_bf)) {
			$fn = $this->jsstring($fn);
			$msg = $this->jsstring($msg);
			$out .= '$' . "t.markFieldBad($fn,$msg);";
		}
		// hide content
		if ($this->_hc) {
			$out .= '$' . "t.hc();";
		}
		// hide classes
		foreach ($this->_hcla as $cls) {
			$out .= '$' . "('.$cls').hide();";
		}
		// finish
		usort($this->_langs, "languages_psort");
		foreach ($this->_langs as $lang) {
			$id = $lang[0];
			$d0 = $lang[2] . ' [' . $lang[1] . ']';
			$descr = $this->jsstring($d0);
			$curr = $lang[4] ? 'true' : 'false';
			$present = $lang[3] ? 'true' : 'false';
			$out .= '$' . "t.addLang($id, $descr, $curr, $present);";
		}
		foreach ($this->_langsl as $lang) {
			$id = $this->jsstring($lang[0]);
			$descr = $this->jsstring($lang[1]);
			$x = ($lang[2] ? 'true' : 'false');
			$out .= '$' . "t.addLoginLang($id, $descr, $x);";
		}
		$out .= '$' . "t.finish();" // .'$t = undefined;'
		;

		return $out;
	}

	protected function jsstring($code) {
		if (is_scalar($code)) {
			$code = preg_split('/[\n\r]/u', addcslashes($code, "'"), null, PREG_SPLIT_NO_EMPTY);
			$out = '';
			if (count($code) == 0) {
				return "''";
			}
			if (count($code) == 1) {
				return "'$code[0]'";
			}
			reset($code);
			while (list ($ci, $codeline) = each($code)) {
				$out .= "'";
				$out .= $codeline;
				$out .= "' + '\\n' + ";
			}
			return substr($out, 0, -10);
		} else {
			return json_encode($code);
		}
	}
}
?>