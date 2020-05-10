<?php
class cms_archiver extends cms_timely {

	private $out_dir = '';

	private $root_dir;
	private $db_records_per_file = 100;
	private $db_file_name_rand = '';
	private $db_file_name_start = 'db_';
	private $archive_name = 'cms_dump_';

	public $FULL_BACKUP_FILE_LIST = 15;
	public $STD_BACKUP_FILE_LIST = 7;
	public $SYS_BACKUP_FILE_LIST = 6;
	public $DATA_BACKUP_FILE_LIST = 5;
	public $UPGRADE_OVERWRITE_FILE_LIST = 10;
	public $UPGRADE_DOWNLOAD_FILE_LIST = 14;
	public $INSTALL_DOWNLOAD_FILE_LIST = 15;

	private $SYSTEM_FILE_LIST = 2;
	private $DATA_FILE_LIST = 1;
	private $TRAN_FILE_LIST = 4;
	private $BUNDLE_FILE_LIST = 8;

	private $tablelist;
	private $filelists;

	private $myfilelist;
	private $myfilelistdb;

	private $myfilelisttype;

	public function get_archive_file_path() {
		return $this->out_dir . '/' . $this->archive_name;
	}

	public function get_archive_file_name() {
		return $this->archive_name;
	}

	public function set_archive_file_name($name) {
		$this->archive_name = $name;
	}

	
	public function __construct() {

		$this->db_file_name_rand = md5(date("His") . mt_rand(1000000, 9000000));
		$this->archive_name .= date("YmdHis") . '.zip';

		$this->tablelist = array (
			'content',
			'entry',
			'fields',
			'history_changes',
			'history_login',
			'lang',
			'last_login',
			'mail_att',
			'mail_job',
			'mail_rec',
			'newsletter_subs',
			'pathlt',
			'pictures',
			'relation',
			'site',
			'sitelang'
		);
		$this->filelists = array ();
		$this->myfilelist = array ();
		$this->myfilelistdb = array ();
		$this->myfilelisttype = 0;

		$this->filelists[$this->DATA_FILE_LIST] = array (
			'/__cms/config/cms_config_db\.php',
			'/__cms/config/[^/]+',
			'/__cms/config/_/cms_config_(db|customer)\.php',
			'/__cms/config/_/options\.xml',
			'/__cms/data/(access\.dat|cmsstar\.dat|licence\.php|master\.xml)',
			'/_images/(?!\.x|\.htaccess).*',
			'/_templates/.*',
			'/(?!__|\.|_images|robots\.txt).*'
		);

		$this->filelists[$this->SYSTEM_FILE_LIST] = array (
			'/__cms/(_|ckeditor|externals|kcfinder|template)/.*',
			'/__cms/\.htaccess',
			'/__cms/(index|ajax|initialize|mailer)\.php',
			'/__cms/license.html',
			'/__cms/config/_/cms_config\.php',
			'/__cms/config/_/commons\.xml',
			'/__cms/i18n/i18njs\.php',
			'/__cms/_cache/(c|f|i|mail)/\.x',
			'/_images/\.htaccess',
			'/_images/\.x',
			'/__phplibs/.*',
			'/((__(index|mm|pc|rf|ri)\.php)|\.htaccess|robots\.txt)'
		);

		$this->filelists[$this->TRAN_FILE_LIST] = array (
			'/__cms/i18n/cms_lp_[a-z]+\.php'
		);

		$this->filelists[$this->BUNDLE_FILE_LIST] = array (
			'/__bundle/.*'
		);

		$this->setup_out_dir(cms_config :: $cc_filecache_folder);

	}

	private function check_file($normalized_name, $mode = -1) {
		$r = false;
		if ($mode == -1)
			$mode = $this->myfilelisttype;
		foreach ($this->filelists as $mask => $list) {
			if ($mask & $mode) {
				foreach ($list as $item) {
					$r = $r || (1 === preg_match('@^' . $item . '$@', $normalized_name));
					if ($r)
						return true;
				}
			}
		}
		return false;
	}

	public function setup_root_dir($nrd) {
		if (substr($nrd, -1) == '/') {
			$nrd = substr($nrd, 0, -1);
		}
		if (is_dir($nrd)) {
			$this->root_dir = $nrd;
		} else {
			$this->root_dir = null;
		}
	}

	public function setup_out_dir($nrd) {
		if (substr($nrd, -1) == '/') {
			$nrd = substr($nrd, 0, -1);
		}
		if (is_dir($nrd)) {
			$this->out_dir = $nrd;
		} else {
			$this->out_dir = null;
		}
	}

	private function sdir($dir, $gp) {
		$rd = dir($dir);
		while ($e = $rd->read()) {
			$this->extend();
			if ($e == '.' || $e == '..')
				continue;
			$fn = $dir . '/' . $e;
			$cn = $gp . '/' . $e;
			if (is_file($fn)) {
				if ($this->check_file($cn)) {
					$this->myfilelist[] = $cn;
				}
			}
			if (is_dir($fn)) {
				$this->sdir($fn, $cn);
			}
		}
	}

	public function build_list($list_type) {
		$this->myfilelisttype = $list_type;
		$this->myfilelist = array ();
		$this->sdir($this->root_dir, '');
	}

	public function dump_database() {
		$this->myfilelistdb = array ();
		$dbo = cms_universe :: $puniverse->db();
		foreach ($this->tablelist as $table) {
			$tdata = array ();
			$tfilecore = $this->db_file_name_start . $table . '.dump';
			if ($dbo instanceof cms_db_mysql) {
				$table = cms_config_db :: $cc_tb_prefix . $table;
				$sql = "SELECT * FROM $table";
				$con = $dbo->get_con();
				$res = $con->query($sql, MYSQLI_USE_RESULT);
				$rows = array ();
				$rc = 0;
				$tfilecount = 1;
				while ($row = $res->fetch_row()) {
					$this->extend();
					$rows[] = $row;
					$rc++;
					if ($rc == $this->db_records_per_file) {
						$ffn = '/' . $tfilecore . $tfilecount;
						$rx = file_put_contents($this->out_dir . $ffn . $this->db_file_name_rand, serialize($rows));
						if ($rx == FALSE)
							return false;
						$this->myfilelistdb[] = $ffn;
						$rows = array ();
						$tfilecount++;
						$rc = 0;
					}
				}
				if ($rc > 0) {
					$ffn = '/' . $tfilecore . $tfilecount;
					$rx = file_put_contents($this->out_dir . $ffn . $this->db_file_name_rand, serialize($rows));
					if ($rx == FALSE)
						return false;
					$this->myfilelistdb[] = $ffn;
				}
				$res->free();
			}
		}
		return true;
	}

	public function load_database() {
		$this->myfilelistdb = array ();
		$dbo = cms_universe :: $puniverse->db();
		foreach ($this->tablelist as $table) {
			$tdata = array ();
			$tfilecore = $this->db_file_name_start . $table . '.dump';
			$ic = 1;
			$dbo->perform("DELETE FROM " . cms_config_db :: $cc_tb_prefix . $table);
			// truncate table is faster, delete from is transactionally safe in mysql
			while (is_file($this->out_dir . '/' . $tfilecore . $ic)) {
				$this->extendnow();
				$data = @ file_get_contents($this->out_dir . '/' . $tfilecore . $ic);
				$dbo->fullinsert(cms_config_db :: $cc_tb_prefix . $table, unserialize($data));
				$ic++;
			};
		}
		return true;
	}

	public function create_archive() {
		$zip = new ZipArchive;
		if ($zip->open($this->out_dir . '/' . $this->archive_name, ZIPARCHIVE :: OVERWRITE | ZIPARCHIVE :: CREATE | ZIPARCHIVE :: EXCL)) {
			// archive open
			foreach ($this->myfilelist as $file) {
				$this->extend();
				$x = $zip->addFile($this->root_dir . $file, 'files' . $file);
				if ($x == false)
					break;
			}
			if ($x != false) {
				foreach ($this->myfilelistdb as $file) {
					$this->extend();
					$x = $zip->addFile($this->out_dir . $file . $this->db_file_name_rand, 'db' . $file);
					if ($x == false)
						break;
				}
			}
			if ($x != false) {
				$metadata = array (
					'type' => $this->myfilelisttype,
					'dblist' => $this->myfilelistdb,
					'filelist' => $this->myfilelist,
					'signature' => cms_config :: $cc_version
				);
				$this->extendnow();
				$x = $zip->addFromString('metadata', serialize($metadata));
			}
			$y = $zip->close();
			$this->extendnow();
			foreach ($this->myfilelistdb as $file) {
				unlink($this->out_dir . $file . $this->db_file_name_rand);
			}
			if ($x && $y) {
				return true;
			}
		}
		@ unlink($this->out_dir . '/' . $this->archive_name);
		return false;
	}

	public function upgrade_from_archive() {
		$this->restore_from_archive(true);
	}

	private function rrmdir($dir) {
		$this->extend();
		$d = dir($dir);
		while($file = $d->read()) {
			if ($file == '.' || $file == '..')
				continue;
			$file = $dir .'/'. $file;
			if (is_dir($file))
				$this->rrmdir($file);
			else
				@unlink($file);
		}
		@rmdir($dir);
		$d->close();
	}

	public function restore_from_archive($upgrade_mode = false) {
		$allok = false;
		$zip = new ZipArchive;
		$rand = substr(md5(date("His") . mt_rand(1000000, 9000000)),0,8);
		$outdir = $this->out_dir;
		$tmpdir = $this->out_dir . '/' . md5(date("His") . mt_rand(1000000, 9000000));		
		if ($zip->open($this->out_dir . '/' . $this->archive_name)) {
			// good, zip open :)			
			mkdir($tmpdir);
			$zip->extractTo($tmpdir . '/');
			$zip->close();
			$this->extendnow();
			$this->setup_out_dir($tmpdir);
			// data extracted?, cool :)
			$metadata = @ unserialize(file_get_contents($tmpdir . '/metadata'));
			if ($metadata['signature']) {
				// good one :)
				$this->myfilelisttype = $metadata['type'];
				if (in_array($this->myfilelisttype, array (
						$this->FULL_BACKUP_FILE_LIST,
						$this->STD_BACKUP_FILE_LIST,
						$this->SYS_BACKUP_FILE_LIST,
						$this->DATA_BACKUP_FILE_LIST,
						$upgrade_mode ? $this->UPGRADE_DOWNLOAD_FILE_LIST : $this->INSTALL_DOWNLOAD_FILE_LIST,
						$this->INSTALL_DOWNLOAD_FILE_LIST
					))) {
					// some valid data inside
					$isdb = count($metadata['dblist']);
					if ($isdb > 2) {
						// at least 3 data files constitue a valid db (lang, site, sitelang)
						$x = $this->out_dir;
						$this->out_dir .= '/db';
						$res = $this->load_database();
						$this->out_dir = $x;
					}
					// now the files!! :) it's going to be krejzi :)
					$this->out_dir .= '/files';
					// browse the file list
					$ftp = array(); 
					foreach ($metadata['filelist'] as $file) {
						$this->extend();
						if ($upgrade_mode && is_file($this->root_dir . $file) && !$this->check_file($file, $this->UPGRADE_OVERWRITE_FILE_LIST)) {
							continue; // this file could be installed or restored, but cannot be overwritten durign upgrade	
						}
						if ($this->check_file($file)) {
							$ftp[] = $file;
						}
					}
					$ok = true;
					foreach($ftp as $file) {
						$this->extend();
						$ok = $ok && rename($this->out_dir . $file, $this->root_dir . $file . $rand);
					}
					if ($ok) {
						foreach($ftp as $file) {
							$this->extend();
							@unlink($this->root_dir . $file);
							rename($this->root_dir . $file . $rand, $this->root_dir . $file);
						}
						$allok = true;	
					} else {
						foreach($ftp as $file) {
							$this->extend();
							@unlink($this->root_dir . $file. $rand);
						}
					}
				}
			}
		}
		$this->extend();
		$this->setup_out_dir($outdir);
		$this->rrmdir($tmpdir);		
		return $allok;
	}

}