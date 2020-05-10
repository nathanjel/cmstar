<?php

include "_/cms_template.php";
include "_/cms_std_gui.php";
include "_/../externals/cms_eev.php";

$loader = require '../vendor/autoload.php';
$loader->add('Aws\\', '../vendor/aws/aws-sdk-php/src');
use Aws\Ec2\Ec2Client;

class cms_db1_gui extends cms_std_gui {
	
	protected $options_regex = array(
		'name_display_in_menu_if_empty' => array('.*',array(0)),
		'typename' => array('.*',array(0)),
		'datelabel' => array('.*',array(0)),
		'namelabel' => array('.*',array(0)),
		'codelabel' => array('.*',array(0)),
		'sluglabel' => array('.*',array(0)),
		'pathtlabel' => array('.*',array(0)),
		'fieldtablename' => array('.*',array(0)),
		'dbechildlistlabel' => array('.*',array(0)),
		'dbechildlistlabels' => array('([^,]+),?',array(1)),
		'contentlabel' => array('.*',array(0)),
		'message_[0-9]+' => array('.*',array(0)),
		'fieldtable.*' => array('(.*?)(?<!\\\\),[0-9A-Za-z_]*,(.*?)(?<!\\\\);',array(1,-1)),
		'.*graphparams' => array('[0-9]+,[0-9]+,(AA|AF|AS|ASSF|ASSC|SC|SF)?,[bBcCGN]*,[0-9]+,([^,]+),([^,]+),([^,]+)',array(2,3,4))
	);
	
	protected $select_list = "/select\(list:(.*)(?<!\\\\)\)/";
	
	protected $files_regex = array(
		'/___.js$/' => array('/[^_]_\(([\"\'])(.*?)\1\)/','js'),
		'/proc.js$/' => array('/[^_]_\(([\"\'])(.*?)\1\)/','js'),
		'/jquery-ajaxtable.js$/' => array('/[^_]_\(([\"\'])(.*?)\1\)/','js'),
		'/tlogin.html$/' => array('/[^_]_\(([\"\'])(.*?)\1\)/','js'),
		'/tmaster.html$/' => array('/[^_]_\(([\"\'])(.*?)\1\)/','js'),
		'/.+\.php$/' => array('/[^_]__\(([\"\'])(.*?)\1\)/','php')
	);
	
	protected $texts = array();
	
	protected $startdir = "../..";
	
	protected $lang = '';
	
	function process_file($name, $dir) {
		echo "processing file $dir/$name";
		$p = false;
		foreach($this->files_regex as $rn=>$rx) {		
			if (preg_match($rn, $name)) {
				$p = true;
				break;
			}
		}
		if ($p) {
			$f = file_get_contents("$dir/$name");
			$ra = array();
			$res = preg_match_all($rx[0],$f, $ra);
			if ($res) {
				foreach ($ra[2] as $x) {
					$this->texts[] = array($x, $rx[1], $x == trim($x)?'':'!', @cms_lp::$_[$x], "$dir/$name" );
				}
			}
			echo " - processed\n";
		} else {
			echo " - skipped\n";
		}
	}
	
	function process_dir($dir) {
		echo "processing dir $dir\n";
		$d = @dir($dir);
		if ($d instanceof Directory) {
			do {
				$entry = $d->read();
				if ($entry === FALSE)
					break;
				if ($entry == '.' || $entry == '..')
					continue;
				if (is_dir($dir.'/'.$entry)) {
					$this->process_dir($dir.'/'.$entry);
				} else {
					$this->process_file($entry, $dir);
				}
			} while (true);
			$d->close();
		}
	}
	
	function process_optxml($file) {
		$oxml = new DOMDocument();
		$res = $oxml->load($file, LIBXML_NOCDATA | LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT);
		if (!$res) {
			throw new RuntimeException('failed to load options file : '.$file);
		}
		$oroot = $oxml->firstChild;
		while($oroot->nodeName != 'cmsoptions') {
			$oroot = $oroot->nextSibling;
			if ($oroot == null) {
				break;
			}
		}
		if ($oroot == null) {
			throw new InvalidArgumentException('invalid xml strucutre of file : '.$file);
		}
		
		$merge = array();        
		$onodesa = array();
		for($conode = $oroot->firstChild; $conode != null; $conode = $conode->nextSibling) {
			if ($conode->nodeName != 'options')
				continue;
			// load!
			$site = $conode->getAttribute('site');
			for($onode = $conode->firstChild; $onode != null; $onode = $onode->nextSibling) {
				if ($onode->nodeName != 'option')
					continue;
				$name = $onode->getAttribute('name');
				$value = $onode->getAttribute('value');
				foreach($this->options_regex as $rx=>$rm) {
					if (preg_match("/^$rx\$/", $name)) {
						echo "on: $name ov: $value\n";
						$find = array();
						$res = preg_match_all("/{$rm[0]}/", $value, $find);
						foreach($rm[1] as $index) {
							if ($index == -1) {
								// special case: select(list:...)
								foreach($find[2] as $sls) {
									$slr = array();
									$sl = preg_match_all($this->select_list, $sls, $slr);
									if ($sl) {
										$x = preg_split('/(?<!\\\\)\\//',$slr[1][0]);
										foreach($x as $text) {
											$tx = stripslashes($text);
											if (strlen($tx))
												$this->texts[] = array($tx, 'php', $tx == trim($tx)?'':'!', @cms_lp::$_[$tx], $file, $site, $name);
										}
									}
								}
							} else {
								foreach($find[$index] as $text) {
									$tx = stripslashes($text);
									if (strlen($tx))
										$this->texts[] = array($tx, 'php', $tx == trim($tx)?'':'!', @cms_lp::$_[$tx], $file, $site, $name);
								}	
							}
						}					
					}
				}
			}
		}		
		unset($oroot, $oxml);	
	}
	
	public static function tcmp($a, $b) {
		$r = strcasecmp($a[0], $b[0]);
		if ($r == 0) {
			return strcmp($a[1], $b[1]);
		}
		return $r;
	}
	
	function sort_texts(&$t) {
		usort($t, array('cms_db1_gui',"tcmp"));
	}
	
	function dedup(&$t) {
		$j = array();
		$lv = '';
		$c = 0;
		foreach($t as $v) {
			$cv = $v[0].$v[1];
			if ($lv != $cv) {
				$j[] = $v;
				$c++;
			} else {
				$j[$c-1][4] .= ";".$v[4];
			}
			$lv = $cv;
		}
		$t = $j;
	}
	
	function csv_sve(&$a) {
		return str_replace('"', '""', $a);
	}
	
	function csv_escape(&$a) {
		return array_map(array('cms_db1_gui',"csv_sve"), $a);
	}
	
	function output_csv($t, $tl) {
		echo chr(0xEF);
		echo chr(0xBB);
		echo chr(0xBF);
		
		$row0 = array("Original Text","Class","Spaces!","Fill in for ".strtoupper($tl),"File(s)","Site","Key");
		
		echo '"'. "OTD CMS Translation file" . "\"\r\n";
		echo '"'. strtoupper($tl) . "\"\r\n";
		echo '"'. join('";"', $this->csv_escape($row0)) . "\"\r\n";
		foreach ($t as $row1) {
			echo '"'.join('";"', $this->csv_escape($row1)) . "\"\r\n";
		}	
	}
	
	function start_stop_instance($xname, $start) {
		try {
			$ec2Client = new Ec2Client([
			    'region' => 'eu-central-1',
			    'version' => 'latest'
			]);
			$result = $ec2Client->describeInstances();
			$ress = $result->get('Reservations');
			foreach($ress as $res) {
				$insts = $res['Instances'];
				foreach($insts as $inst) {
					$name = '';
					$iid = $inst['InstanceId'];
					$tags = $inst['Tags'];
					foreach($tags as $t) {
						if ($t['Key'] == 'Name')
							$name = $t['Value'];
					}
	 				// LS-DEV-QA
						// 0 : pending
						// 16 : running
						// 32 : shutting-down
						// 48 : terminated
						// 64 : stopping
						// 80 : stopped
	 				if ($name == $xname) {
	 					$state = $inst['State']['Code'];
	 					$state &= 0x00ff;
	 					if ($state == 16) {
	 						if ($start == false) {
	 							$result = $ec2Client->stopInstances([
								    'InstanceIds' => [
								        $iid,
								    ],
								]);
								$this->_template->showMessage(cms_template :: $msg_info, 'a02', array (
									$iid
								));
	 						}
	 					}
	 					if ($state == 80) {
	 						if ($start == true) {
	 							$result = $ec2Client->startInstances([
								    'InstanceIds' => [
								        $iid,
								    ],
								]);
								$this->_template->showMessage(cms_template :: $msg_info, 'a03', array (
									$iid
								));
	 						}
	 					}
	 				}
	 			}
			}
		} catch (Exception $e) {
				$this->_template->showMessage(cms_template :: $msg_error, 'a01', array (
					$e->getMessage()
				));
		}
	}
	
	public function ext_a() {
		// no actions
		
		if ($_POST['action'] == 'tr-download') {
			
			$this->lang = @$_POST['fdl'];
			
			if(!preg_match('/^[a-z][a-z]$/', $this->lang)) {
				return $this->_template->showMessage(cms_template :: $msg_error, '914', array($this->lang));
			}
			
			// keep current lang data intact
			$tl0 = cms_lp::$_;
			$tl1 = cms_lp::$j;
			
			// load working lang
			if (!class_exists("cms_lp_{$this->lang}", false)) {
				if (file_exists("i18n/cms_lp_{$this->lang}.php")) {
					include "i18n/cms_lp_{$this->lang}.php";
				} else {
					cms_lp::$_ = array();
					cms_lp::$j = array();
				}
			}
			
			ob_start();
			$this->process_dir($this->startdir);
			$this->process_optxml('config/_/options.xml');
			$this->process_optxml('config/_/commons.xml');
			$log = ob_get_clean();
			
			// keep current lang data intact
			cms_lp::$_ = $tl0;
			cms_lp::$j = $tl1;
			
			ob_start();
			$this->sort_texts($this->texts);
			$this->dedup($this->texts);
			$this->output_csv($this->texts, $this->lang);
			$csv = ob_get_clean();
						
			$f0 = trim($this->site->name);
			$f0 = preg_replace('/[^A-Za-z0-9]/','_',$f0);
			$fn = $f0.'_translation_file_'.$this->lang.'_'. date("Y-m-d").'.csv';
			
			cms_http::clean_output_buffers();				
			cms_http::headers_for_download($fn.'.cmsb', 'application/vnd.ms-excel');
							
			echo $csv;
			
			$this->_template->suppressOutput();
			
			return;
		}
		
		if ($_POST['action'] == 'tr-upload') {
			$file = $_FILES['ffiletr']['tmp_name'];
			
			if (is_uploaded_file($file)) {
				$lines = file($file);
				unlink($file);
			}
			
			if (count($lines)<4) {
				// echo "No suitable data in file\n";
				return $this->_template->showMessage(cms_template :: $msg_error, '911');
			}
			
			$data = array();
			
			foreach($lines as $line) {
				$data[] = str_getcsv($line, ";", '"');	
			}
			
			$c1 = preg_match('/OTD CMS Translation file/',$data[0][0]);
			$tl = strtolower($data[1][0]);
			$c2 = preg_match('/^[a-z][a-z]$/', $tl);
			
			if (!($c1 && $c2)) {
				// echo "File format invalid\n";
				return $this->_template->showMessage(cms_template :: $msg_error, '911');
			}
			
			$oct = date("r");
			$out = "<?php // $oct \n";
			$dc = count($data);
			for($i=3; $i<$dc; $i++) {
				$js = ($data[$i][1]=='js');
				$orig = addcslashes($data[$i][0],"'");
				$tran = addcslashes($data[$i][3],"'");
				if (strlen($tran)) {
					$out .= 'cms_lp::$_'."['$orig'] = '$tran';\n";
					if ($js) $out .= 'cms_lp::$j'."[] = '$orig';\n";
				}	
			}
			$out .= "class cms_lp_$tl extends cms_lp {} ?>";
			
			$tfn = "i18n/cms_lp_$tl.php";
			
			@unlink($tfn);
			@file_put_contents($tfn, $out);
			
			if ($out == file_get_contents($tfn)) {
				$this->_template->showMessage(cms_template :: $msg_info, '912', array($tl));
				$_POST['action'] = 'clearcache1';
			} else {
				return $this->_template->showMessage(cms_template :: $msg_error, '913', array($tfn));
			}
		}

		if ($_POST['action'] == 'mcflush') {
			$GLOBALS['__MC__']->flush();
			$this->_template->showMessage(cms_template :: $msg_info, '910', array('Memcached'));
		}
		
		if ($_POST['action'] == 'phpinfo') {
			cms_universe::$puniverse->leave_change_mode(true);
			phpinfo();
			die();
		}

		if ($_POST['action'] == 'stop-qa') {
			$this->start_stop_instance(cms_config::$cc_cms_misc_qa_instance_name, false);
		}

		if ($_POST['action'] == 'start-qa') {
			$this->start_stop_instance(cms_config::$cc_cms_misc_qa_instance_name, true);
		}
		
		if ($_POST['action'] == 'clearcache1' || $_POST['action'] == 'clearcache2') {
			if (function_exists('apc_clear_cache')) {
				// apc_clear_cache('user');
				apc_clear_cache();
				$this->_template->showMessage(cms_template :: $msg_info, '910', array('APC'));
			}			
			$dirs = array(cms_config::$cc_filecache_folder, cms_config::$cc_cms_tp_compiled_folder);
			if ($_POST['action'] == 'clearcache2')
				$dirs[] = cms_config::$cc_cms_imcache_folder;		
			foreach ($dirs as $dir) {
				$this->_template->showMessage(cms_template :: $msg_info, '910', array($dir));
				cms_filecache::deletedir($dir);
			}
		}
		
		if ($_POST['action'] == 'db-defrag') {
			$q = cms_universe :: $puniverse->db();
			// $q->perform('ALTER TABLE cms_content ENGINE=INNODB');
			$this->ensure_time_extension();
			$q->perform('ALTER TABLE cms_entry ENGINE=INNODB');
			$this->ensure_time_extension();
			$q->perform('ALTER TABLE cms_fields ENGINE=INNODB');
			$this->ensure_time_extension();
			// $q->perform('ALTER TABLE cms_pictures ENGINE=INNODB');
			$this->ensure_time_extension();
			$q->perform('ALTER TABLE cms_pathlt ENGINE=INNODB');
			$this->ensure_time_extension();
			$q->perform('ALTER TABLE cms_relation ENGINE=INNODB');
			$this->ensure_time_extension();
			
			$this->_template->showMessage(cms_template :: $msg_info, '930');
			
		}
		
		if ($_POST['action'] == 'xml-c11') {
			if (cms_universe :: $puniverse->xml_version() >= 1.1) {
				$this->_template->showMessage(cms_template :: $msg_error, '920');
				return;
			}
			if (!cms_config::$cc_enable_xml_version_1_1) {
				$this->_template->showMessage(cms_template :: $msg_error, '923');
				return;
			}
			cms_universe :: $puniverse->enter_change_mode(true, false);
			// run master xml conversion;
			$tnmap = array (
				'entry' => 'e',
				'site' => 's',
				'field' => 'f',
				'name' => 'n',
				'text' => 't',
				'picture' => 'p',
				'cmsmaster' => 'cmsmaster',
				'sites' => 'sites'
			);
			$anmap = array (
				'id' => 'i',
				'type' => 't',
				'lang' => 'v',
				'name' => 'n',
				'i' => 'f',
				'd' => 'd',
				'k' => 'k',
				'l' => 'l',
				'default' => 'default'
			);
			$danmap = array (
				'lname' => 's',
				// 'date' => 'm',  - lang dependent date will NOT be stored in the main data, but taken from change log if neccesary
				'pub' => 'p',
			);
			
			try {
				$newDocument = new DOMDocument('1.0', 'utf-8');
				$queue = array();
				array_push($queue,
					array (
						$newDocument,
						cms_universe :: $puniverse->master_document->firstChild
					)
				);
				$rcnt = $check_every_n_items = 100;
				while ($qe = array_pop($queue)) {
					$rcnt++;
					if ($rcnt > $check_every_n_items) {
						$rcnt = 0;
						if (!$this->ensure_time_extension()) {
							throw new RuntimeException(_('Brak możliwości rozszerzenia czasu pracy.'));
						}
					}
					$np = $qe[0];
					$cn = $qe[1];
					// process single node
					if ($cn->nodeType == XML_CDATA_SECTION_NODE) {
						$x =& $cn->wholeText;
						if (strlen($x)) {
							$nn = $newDocument->createCDATASection($x);
						} else {
							$nn = $newDocument->createTextNode($cn->wholeText);
						}
					}
					elseif ($cn->nodeType == XML_TEXT_NODE) {
						$nn = $newDocument->createTextNode($cn->wholeText);
					} else { // Element
						// optimize out empty field nodes
						if ($cn->nodeName == 'field') {
							if ($cn->firstChild == null || $cn->firstChild->wholeText == '') {
								continue;
							}
						}
						$nn = $newDocument->createElement($tnmap[$cn->nodeName]);
						// std attrs
						foreach ($anmap as $oan => $nan) {
							if ($cn->hasAttribute($oan)) {
								$nn->setAttribute($nan, $cn->getAttribute($oan));
							}
						}
						// lang based attrs (complex?)
						if ($cn->hasAttribute('lang')) {
							$langs = explode(',', $cn->getAttribute('lang'));
							foreach ($langs as $lang) {
								if ($lang=='')
									continue;
								foreach ($danmap as $oan => $nan) {
									$oan = $oan . $lang;
									$nan = $nan . $lang;
									if ($cn->hasAttribute($oan)) {
										$nn->setAttribute($nan, $cn->getAttribute($oan));
									}
								}
							}
						}
						// all children
						$ncld = $cn->lastChild;
						while ($ncld != null) {
							$queue[] = array (
								$nn,
								$ncld
							);
							$ncld = $ncld->previousSibling;
						}
					}
					$np->appendChild($nn);
				}
				cms_universe :: $puniverse->master_document = $newDocument;
				cms_universe :: $puniverse->master_document->firstChild->setAttribute(cms_entry_xml :: $VERSION_ATTR, '1.1');
				cms_universe :: $puniverse->leave_change_mode();
				$this->_template->showMessage(cms_template :: $msg_succ, '921');
				cms_entry_xml :: switch_to_xml_short_tags();
			} catch (Exception $e) {
				$this->_template->showMessage(cms_template :: $msg_error, '922', array (
					$e->getMessage()
				));
				cms_universe :: $puniverse->leave_change_mode(true);
			}
			
		}
		
		if ($_POST['action'] == 'backup-ds' || $_POST['action'] == 'backup-df'
	|| $_POST['action'] == 'backup-dd' || $_POST['action'] == 'backup-dc') {
			$arch = new cms_archiver();
			$arch->setup_root_dir('../');
			$f = 0;
			$n = '';
			switch($_POST['action']) {
				case 'backup-ds': $f = $arch->STD_BACKUP_FILE_LIST; $n='full'; break;
				case 'backup-df': $f = $arch->FULL_BACKUP_FILE_LIST; $n='full-os';break;
				case 'backup-dd': $f = $arch->DATA_BACKUP_FILE_LIST; $n='data';break;
				case 'backup-dc': $f = $arch->SYS_BACKUP_FILE_LIST; $n='system';break;
			}
			$arch->build_list($f);
			if ($_POST['action'] != 'backup-dc') {
				$arch->dump_database();
			}
			$r = $arch->create_archive();
			if ($r) {
				$fn = $arch->get_archive_file_name();
				cms_http::clean_output_buffers();				
				cms_http::headers_for_download($n.'-'.$fn.'.cmsb', 'application/zip');
				readfile($arch->get_archive_file_path());				
				@unlink($arch->get_archive_file_path());
				$this->_template->suppressOutput();
			} else {
				$this->_template->showMessage(cms_template :: $msg_error, '902');
			}			
		}
		
		if ($_POST['action'] == 'backup-u' || $_POST['action'] == 'update-u') {
			// upload!! :)
			$file = $_FILES[($_POST['action'] == 'backup-u')?'ffileb':'ffileu'];
			$arch = new cms_archiver();
			$arch->setup_root_dir('../');
			$ok = false;
			if (move_uploaded_file($file['tmp_name'], $arch->get_archive_file_path())) {
				// file moved to archive location, we can progress :)
				cms_universe::$puniverse->enter_change_mode(true, true); // start
				if ($arch->restore_from_archive()) {
					// all ok
					cms_universe::$puniverse->leave_change_mode(); // commit					
					$ok = true;					
				} else {
					cms_universe::$puniverse->leave_change_mode(true); // rollback
				}
				unlink($arch->get_archive_file_path());
			}
			if (!$ok) {
				$this->_template->showMessage(cms_template :: $msg_error, '903');
			} else {
				$this->_template->showMessage(cms_template :: $msg_info, '904');
			}					
		}
		
	}
	
	public function ext_d() {
		$this->_template->addTab('g0', __('Baza danych'));
		
		$this->_template->addGroup('g0', 'g1', __('Baza danych'));
		
		$this->_template->addField('g1', '', '', 'span', null, __('Połączono z bazą danych'));
		
		$this->_template->addGroup('g0', 'g', __('Dane połączenia z bazą danych'));
		$conn = cms_universe :: $puniverse->db()->get_con();
		switch (cms_config_db :: $cc_db_engine) {
			case 'mysql' :
				$this->_template->addField('g', __('Silnik bazy danych'), '', 'span', null, 'mysql');
				$this->_template->addField('g', __('Połączenie do'), '', 'span', null, $conn->host_info);
				$this->_template->addField('g', __('Użytkownik'), '', 'span', null, cms_config_db :: $cc_mysql_user);
				$this->_template->addField('g', __('Baza danych'), '', 'span', null, cms_config_db :: $cc_db);
				break;
			default :
				$this->_template->addField('g', __('Moduł statusu bazy nie wspiera silnika bazy'), '', 'span', null, cms_config_db :: $cc_db_engine);
			
		}
		
		$map['pid'] = "Process id of this server process";
		$map['uptime'] = "Number of seconds this server has been running";
		$map['time'] = "Current UNIX time according to the server";
		$map['version'] = "Version string of this server";
		$map['rusage_user'] = "Accumulated user time for this process";
		$map['rusage_system'] = "Accumulated system time for this process";
		$map['curr_items'] = "Current number of items stored by the server";
		$map['total_items'] = "Total number of items stored by this server ever since it started";
		$map['bytes'] = "Current number of bytes used by this server to store items";
		$map['curr_connections'] = "Number of open connections";
		$map['total_connections'] = "Total number of connections opened since the server started running";
		$map['connection_structures'] = "Number of connection structures allocated by the server";
		$map['cmd_get'] = "Cumulative number of retrieval requests";
		$map['cmd_set'] = "Cumulative number of storage requests";
		$map['get_hits'] = "Number of keys that have been requested and found present";
		$map['get_misses'] = "Number of items that have been requested and not found";
		$map['bytes_read'] = "Total number of bytes read by this server from network";
		$map['bytes_written'] = "Total number of bytes sent by this server to network";
		$map['limit_maxbytes'] = "Number of bytes this server is allowed to use for storage";

		$this->_template->addTab('gmc', __('Pamięć podręczna'));
		$this->_template->addGroup('gmc', 'gmc1', __('Memcached'));
		$stats = $GLOBALS['__MC__']->getStats();
		foreach($stats as $k=>$v) 
		$this->_template->addField('gmc1', isset($map[$k])?$map[$k]:$k, '', 'span', null, $v);


		$this->_template->addTab('g0a', __('Operacje'));
		
		if (function_exists('apc_cache_info')) {
			$this->_template->addTab('g3a', __('APC info'));
			$this->_template->addField('g3a', __('Zrzut informacji stanu APC danych'), '', 'code', array('rows'=>25,'cols'=>76,'mode' => 'php'), 
				print_r(apc_cache_info('user'), true));
			$this->_template->addField('g3a', __('Zrzut informacji stanu APC bytecode'), '', 'code', array('rows'=>40,'cols'=>76,'mode' => 'php'), 
				print_r(apc_cache_info(), true));
		}
		
		$this->_template->addGroup('g0a', 'g1a', __('Defragmentacja'));
		$this->_template->addField('g1a', '', 'warn', 'span', null, __('Okresowo, po masowych zmianach lub importach danych.'));
		$this->_template->addField('g1a', '', '', 'button', array (
			'action' => 'db-defrag',
			'group' => '1'
		), __('Wykonaj defragmentacje'));
		
		$this->_template->addGroup('g0a', 'g4a', __('Czyszczenie cache'));
		$this->_template->addField('g4a', '', '', 'button', array (
			'action' => 'clearcache1',
			'group' => '4'
		), __('Szablony, konfiguracja, assety (poza obrazkami)'));
		$this->_template->addField('g4a', '', '', 'button', array (
			'action' => 'clearcache2',
			'group' => '4'
		), __('Szablony, konfiguracja, wszystkie assety'));
		$this->_template->addField('g4a', '', '', 'button', array (
			'action' => 'mcflush',
			'group' => '4'
		), __('Memcached'));
		
		$this->_template->addGroup('g0a', 'g5a', __('PHP Info - dane środowiska systemowego'));
		$this->_template->addField('g5a', '', '', 'button', array (
			'action' => 'phpinfo',
			'group' => '5'
		), __('Wywołaj phpinfo()'));
		
		if (!cms_config::$cc_enable_xml_version_1_1) {
			$this->_template->showMessage(cms_template :: $msg_info, '923');
		} else {
			$this->_template->addGroup('g0a', 'g1b', __('Konwersja master.xml do wersji 1.1'));
			if (cms_universe :: $puniverse->xml_version() < 1.1) {
				$this->_template->addField('g1b', '', 'warn', 'span', null, __('Jednorazowa konwersja master.xml do wersji 1.1 może spowodować podniesienie sprawności operacji zapisu/odczytu danych dla dużych serwisów.'));
				$this->_template->addField('g1b', '', '', 'button', array (
					'action' => 'xml-c11',
					'group' => '2'
				), __('Konwertuj master.xml do wersji 1.1'));
			} else {
				$this->_template->addField('g1b', '', 'warn', 'span', null, __('Plik master.xml spełnia już standard wersji 1.1'));
			}
		}
		
		$this->_template->selectTab('g0');
		
		// translacje
		
		$this->_template->addTab('g6', __('Tłumaczenia'));		
		$this->_template->addGroup('g6', 'g6a', __('Pobierz plik do tłumaczenia'));
		
		// get all languages, mark THE ONES
		$lk = array();
		$lv = array();
		
		foreach (cms_universe::$puniverse->languages as $lang) {
			$lk[] = $lang[4];
			if (@cms_universe::$puniverse->langfiles[$lang[4]]) {
				$lv[] = '**** ' . $lang[2] .' ['. $lang[3] . '] ' . __('(istnieje tłumaczenie)');
			} else {
				$lv[] = $lang[2] .' ['. $lang[3] . '] ';
			}
		}
		
		$this->_template->addField('g6a', 'Wybierz język do tłumaczenia', 'fdl',
			'simpleselect', array(
				'v'=>$lv, 'k'=>$lk), '');
		
		$this->_template->addField('g6a','', '', 'button', array('action'=>'tr-download', 'group'=>'10', 'notcaller' => 'true'), __('Pobierz'));
		
		$this->_template->addGroup('g6', 'g6b', __('Wczytaj plik z tłumaczeniem'));
		$params = array(
			'filename' => '',
			'downloadkey' => '',
			'filetype' => '',
			'filesize' => ''
		);
		$this->_template->addField('g6b',__('Plik .csv'), 'ffiletr', 'file', $params, '');    
		$this->_template->addField('g6b','', '', 'button', array('action'=>'tr-upload', 'group'=>'11'), __('Wczytaj'));       


		// archiwum
		
		$this->_template->addTab('g9', __('Kopia zapasowa'));		
		$this->_template->addGroup('g9', 'g9a', __('Utwórz i pobierz kopię zapasową'));
			
		$this->_template->addField('g9a','', '', 'button', array('action'=>'backup-ds', 'group'=>'10a'), __('Pobierz pełne dane kopii bezpieczeństwa'));
		$this->_template->addField('g9a','', '', 'button', array('action'=>'backup-dc', 'group'=>'10a'), __('Pobierz kopię programu CMS'));
		
		$this->_template->addGroup('g9', 'g9x', __('Pobierz szczególne informacje z kopii zapasowej'));
		$this->_template->addField('g9x','', '', 'button', array('action'=>'backup-df', 'group'=>'10b'), __('Pobierz pełne dane oraz źródłowe pakiety open source'));
		$this->_template->addField('g9x','', '', 'button', array('action'=>'backup-dd', 'group'=>'10b'), __('Pobierz kopię tylko bazy danych'));
		
		
		$this->_template->addGroup('g9', 'g9b', __('Wczytaj kopię zapasową'));
		$params = array(
			'filename' => '',
			'downloadkey' => '',
			'filetype' => '',
			'filesize' => ''
		);
		$maxmb = __("maksymalny rozmiar: "). cms_universe::get_max_upload_file_size() . 'MB';
		$this->_template->addField('g9b',__('Plik .cmsb')." ($maxmb)", 'ffileb', 'file', $params, '');    
		$this->_template->addField('g9b','', '', 'button', array('action'=>'backup-u', 'group'=>'11b'), __('Wczytaj'));
		
		// aktualizacja
		
		$this->_template->addTab('g8', __('Aktualizacja'));		
		
		$this->_template->addGroup('g8', 'g8b', __('Wczytaj aktualizację'));
		$params = array(
			'filename' => '',
			'downloadkey' => '',
			'filetype' => '',
			'filesize' => ''
		);
		$this->_template->addField('g8b', '', 'warn', 'span', null, __('Przed wczytaniem aktualizacji wykonaj kopię zapasową!'));
		$this->_template->addField('g8b',__('Plik .cmsu')." ($maxmb)", 'ffileu', 'file', $params, '');    
		$this->_template->addField('g8b','', '', 'button', array('action'=>'update-u', 'group'=>'11c'), __('Wczytaj'));          

		// ec2 management
		$this->_template->addTab('gec2', __('EC2'));
		$this->_template->addGroup('gec2', 'gec21', __('Instancje'));
		
		try {
			$ec2Client = new Ec2Client([
			    'region' => 'eu-central-1',
			    'version' => 'latest'
			]);
			$result = $ec2Client->describeInstances();
			$ress = $result->get('Reservations');
			foreach($ress as $res) {
				$insts = $res['Instances'];
				foreach($insts as $inst) {
					$name = '';
					$iid = $inst['InstanceId'];
					$tags = $inst['Tags'];
					foreach($tags as $t) {
						if ($t['Key'] == 'Name')
							$name = $t['Value'];
					}
	 				$this->_template->addField('gec21', $name, '', 'span', null, $iid . ' ' . $inst['State']['Name']);
	 				// LS-DEV-QA
						// 0 : pending
						// 16 : running
						// 32 : shutting-down
						// 48 : terminated
						// 64 : stopping
						// 80 : stopped
	 				if ($name == cms_config::$cc_cms_misc_qa_instance_name) {
	 					$state = $inst['State']['Code'];
	 					$state &= 0x00ff;
	 					if ($state == 16) {
							$this->_template->addField('gec21','', '', 'button', array('action'=>'stop-qa', 'group'=>'0x01'), __('Zatrzymaj '.$name));
	 					}
	 					if ($state == 80) {
							$this->_template->addField('gec21','', '', 'button', array('action'=>'start-qa', 'group'=>'0x01'), __('Uruchom '.$name));
	 					}
	 				}
	 			}
			}
		} catch (Exception $e) {
			$this->_template->addField('gec21', 'Exception', '', 'span', null, $e->getMessage());
		}

	}
}
?>