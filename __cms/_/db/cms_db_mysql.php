<?php

class cms_db_mysql implements cms_db {
	
	//public $profile = array();
	
	private static $connection = NULL;
	private static $_pst = array();
	
	public function get_con() {
		return self::$connection;  
	}
	
	public function is_connected() {
		if (self::$connection instanceof mysqli && self::$connection->server_version) {
			return true;
		}
		return false;
	}
	
	protected function connect() {
		// from config
		if ($this->is_connected()) {
			// mamy poczenie juz :)
			return true;
		}
		
		self::$connection = mysqli_init();

		self::$connection->real_connect(
			(cms_config_db::$cc_mysql_pconnect?'p:':'').cms_config_db::$cc_mysql_host,
			cms_config_db::$cc_mysql_user, cms_config_db::$cc_mysql_pass, 
			cms_config_db::$cc_db,
			cms_config_db::$cc_mysql_port,
			'',
			MYSQLI_CLIENT_COMPRESS
		);
		
		if (!$this->is_connected()) {                
			throw new RuntimeException('DB connecting to server '.cms_config_db::$cc_mysql_host.' failed with error '.self::$connection->error, 9);
		}
		return true;
	}
	
	public function perform($query, $params = null, $dbg = false, $nosplit = false) {
		if($nosplit) {
			$queries = array($query);
		} else {
			$queries = preg_split("/;+(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $query);
			$queries = array_filter($queries);			
		}
		$res = null;
		if (is_array($params)) {
			$GLOBALS['__WPC_'] =& $params;
			reset($GLOBALS['__WPC_']);
			// risky - relies on reset/each "global" data
			foreach($queries as $iquery) {
				// TODO: Fix the shitty bug down here!
				$iquery = preg_replace_callback("/(\s|\(|,)([\?@])/", 'cms_db_generic_query_param_helper', $iquery);
				if ($res instanceof mysqli_result) // free previous call
					$res->free();
				$stime = microtime(true);
				$res = self::$connection->query($iquery, MYSQLI_USE_RESULT);
				if (cms_config::$cc_full_query_debug) {
					error_log($iquery);
					error_log(print_r(debug_backtrace(),true));
				}
				$GLOBALS['__QS__']['qtime'] += (microtime(true) - $stime);
				$GLOBALS['__QS__']['queries']++;
				if (!$res) {
					throw new RuntimeException('DB perform failed with query '.$iquery.' resulting in error ' . 
						self::$connection->error, 12);
				}
			}
		} else {
			foreach($queries as $iquery) {
				if ($res instanceof mysqli_result) // free previous call
					$res->free();
				$stime = microtime(true);
				$res = self::$connection->query($iquery, MYSQLI_USE_RESULT);
				if (cms_config::$cc_full_query_debug) {
					error_log($iquery);
					error_log(print_r(debug_backtrace(),true));
				}
				$GLOBALS['__QS__']['qtime'] += (microtime(true) - $stime);
				$GLOBALS['__QS__']['queries']++;
				if (!$res) {
					throw new RuntimeException('DB perform failed with query '.$iquery.' resulting in error ' . 
						self::$connection->error, 12);
				}
			}
		}
		$j = strlen($query);
		if ($res instanceof mysqli_result) { // if last call returned rows as result
			$rows = array();
			while($row = $res->fetch_row()) {
				$rows[] = $row;
			}
			$res->free();			
			return $rows;
		} else { // update call - get stats
			if (
					($j >= 14 && (strtoupper(substr($query,0,14))=='INSERT DELAYED')) ||
					($j >= 19 && (strtoupper(substr($query,0,19))=='UPDATE LOW_PRIORITY'))
			) {
				return true;
			} else {
				return self::$connection->affected_rows;
			}
		}
	}
	
	public function fullinsert($table,$rows,$escape = true) {
		if (is_array($rows) && is_array(@$rows[0])) {
			$sql = "insert into `$table` values ";
			$c = count($rows);
			$d = 0;
			reset($rows);
			while(list($rid,$row) = each($rows)) {
				if ($escape) {
					for ($j=0; $j<count($row); $j++) {
						$row[$j] = addslashes($row[$j]);
					}
				}
				$sql .= "('". join ("','", $row). "')";
				if (++$d < $c) {
					$sql .= ", ";
				}
			}
			$this->perform($sql, null, false, true);
			return $this->last_ai();
		}
	}
	
	public function insert($table,$fields,$rows,$escape = true) {
		if (is_array($rows) && is_array(@$rows[0]) && is_array($fields)) {
			$sql = "insert into `$table` ( ";
			$sql .= "`".join('`,`', $fields)."` ) values ";
			$c = count($rows);
			$d = 0;
			reset($rows);
			while(list($rid,$row) = each($rows)) {
				if ($escape) {
					for ($j=0; $j<count($row); $j++) {
						$row[$j] = addslashes($row[$j]);
					}
				}
				$sql .= "('". join ("','", $row). "')";
				if (++$d < $c) {
					$sql .= ", ";
				}
			}
			$this->perform($sql, null, false, true);
			return $this->last_ai();
		}
	}
	
	public function pass($pass, $alg = 'zz0') {
		switch ($alg) {
			case 'sha1':
				return sha1($pass);
				break;
			case 'zz0':
				$salt = md5($pass."t45con456_+{;'[]<>");
				return sha1($salt.$pass);
			case 'non':
				return $pass;
			case 'md5':
			default:
				return md5($pass);
		}
	}
	
	public function last_ai() {
		$sql = "select concat('',last_insert_id()) as lid";
		$r = $this->perform($sql);
		return $r[0][0];
	}
	
	public function tcount($table, $field='*') {
		$r=$this->perform("select count($field) from $table");
		return $r[0][0];
	}
	
	public function __construct() {
		if ($this->connect()) {                
			self::$connection->query("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");			
			self::$connection->query("SET autocommit=1");
			$r = self::$connection->set_charset('utf8');
			if (!$r) {
				throw new RuntimeException('DB would not switch to utf8, resulting in error ' . 
					self::$connection->error, 12);    
			}
		}
		return true;
	}
	
	public function begin() {
		self::$connection->query("SET autocommit=0");
		self::$connection->query("START TRANSACTION");
		$GLOBALS['__QS__']['tstarts']++;
	}
	
	public function commit() {
		self::$connection->query("COMMIT WORK AND NO CHAIN NO RELEASE");
		self::$connection->query("SET autocommit=1");
		$GLOBALS['__QS__']['tstops']++;
	}
	
	public function rollback() {
		self::$connection->query("ROLLBACK WORK AND NO CHAIN NO RELEASE");
		self::$connection->query("SET autocommit=1");
		$GLOBALS['__QS__']['tstops']++;
	}              
	
	public function convert_lpath_to_dbre($lpath) {
		if ($lpath == '**')
			return '^.{0,}$';
		$lpath = str_replace(',', '|', $lpath);
		$lpath = str_replace('**', '.{0,}', $lpath);
		$lpath = str_replace('*', '[^/]+', $lpath);            
		$lpath = '^'.$lpath.'$';
		return $lpath;
	}
	
	public function convert_lpath_to_dblike($lpath) {
		if ($lpath == '**')
			return '%';
		$lpath = str_replace('**', '%', $lpath);
		$lpath = str_replace('*', '%', $lpath);
		return $lpath;
	}
	
}

$px = cms_config_db::$cc_tb_prefix;

define('dbq_mailer_snumber', 'select count(active) from '.$px.'newsletter_subs where area = ? and active = ?');
define('dbq_mailer_listgr', 'select distinct area from '.$px.'newsletter_subs');

define('dbq_mailer_deactivate', 'insert delayed into '.$px.'newsletter_subs (area, email, joined, active) values (?, ?, now(), -1) on duplicate key update joined = now(), active = -1');
define('dbq_mailer_activate', 'insert delayed into '.$px.'newsletter_subs (area, email, joined) values (?, ?, now()) on duplicate key update joined = now(), active = 1');
define('dbq_mailer_delete', 'delete from '.$px.'newsletter_subs where area = ? and email = ?');
define('dbq_mailer_resign', 'update '.$px.'newsletter_subs set active = 0 where area = ? and email = ?');

define('dbq_mailer_simplelist', 'select email from '.$px.'newsletter_subs where area = ? and if( ? = -99,true,active = ?) order by email');
define('dbq_mailer_list', 'select email, joined, if(active=1,\'aktywny\',if(active=-1,\'niepotwierdzony\',\'zrezygnował\')), area from '.$px.'newsletter_subs where area = ? and if( ? =-99,true,active = ?) order by email');

define('dbq_mailer_createjob', 'insert into '.$px.'mail_job (priority, sender, subject, content, sendhtml, created) values (?, ?, ?, ?, ?, FROM_UNIXTIME(?))');
define('dbq_mailer_addatt', 'insert into '.$px.'mail_att values (?, ?, ?, ?)');
define('dbq_mailer_addrec', 'insert into '.$px.'mail_rec (id_job, rcpt) values (?, ?)');

define('dbq_mailer_nextjob', 'select * from '.$px.'mail_job where sent = 0 order by priority desc, created asc limit 0,1');
define('dbq_mailer_getatt', 'select attfile, attname from '.$px.'mail_att where id_job = ? order by sortid asc');
define('dbq_mailer_getrec', 'select rcpt from '.$px.'mail_rec where id_job = ? and sent = 0');

define('dbq_mailer_confirm_rcpt', 'update LOW_PRIORITY '.$px.'mail_rec set sent = 1 where id_job = ? and rcpt = ?');
define('dbq_mailer_confirm_job', 'update '.$px.'mail_job set sent = 1 where id_job = ?');

define('dbq_login_in', 'insert into '.$px.'history_login values (?, FROM_UNIXTIME(?), NULL, 1, ?)');
define('dbq_login_err', 'insert into '.$px.'history_login values (?, FROM_UNIXTIME(?), NULL, 0, ?)');
define('dbq_logout', 'update '.$px.'history_login set logout = FROM_UNIXTIME(?) where user = ? and login = FROM_UNIXTIME(?) and succ = 1');
define('dbq_last_log_succ', 'insert into '.$px.'last_login values (?, FROM_UNIXTIME(0), FROM_UNIXTIME(0), FROM_UNIXTIME(?), FROM_UNIXTIME(0)) on duplicate key update lsl = lcl, lcl = FROM_UNIXTIME(?)');
define('dbq_last_log_fail', 'insert into '.$px.'last_login values (?, FROM_UNIXTIME(0), FROM_UNIXTIME(?), FROM_UNIXTIME(0), FROM_UNIXTIME(0)) on duplicate key update lul = FROM_UNIXTIME(?)');
define('dbq_last_log_lout', 'insert into '.$px.'last_login values (?, FROM_UNIXTIME(0), FROM_UNIXTIME(0), FROM_UNIXTIME(0), FROM_UNIXTIME(?)) on duplicate key update llo = FROM_UNIXTIME(?)');
define('dbq_count_logged', 'SELECT count(user) FROM  `'.$px.'last_login` WHERE lcl > llo AND lcl > FROM_UNIXTIME(?)');
define('dbq_lastlog', 'SELECT UNIX_TIMESTAMP(lsl), UNIX_TIMESTAMP(lul) FROM '.$px.'last_login WHERE user = ?');

define('dbq_record_change', 'insert into '.$px.'history_changes values (?,FROM_UNIXTIME(?),?,?,?,?,?,?,?,?)');
define('dbq_last_change', 'select UNIX_TIMESTAMP(max(`change`)) from '.$px.'history_changes where site = ? and lang = ? and pathl = ?');

define('dbq_list_sites','select * from '.$px.'site order by site asc');
define('dbq_alter_site','update '.$px.'site set `name` = ?, `description` = ?, `default` = ?, `defaultlang` = ?, `route` = ?, `dbe` = ? where `site` = ?');
define('dbq_find_pathlt','select cmsid, pathl from '.$px.'pathlt where (pathltc = ? or pathlt = ?) and siteid = ? and lang = ? order by pathltc desc');
define('dbq_find_lname','select cmsid, pathl from '.$px.'entry where pathl like concat(?,"/%") and lname = ? and site = ? and lang = ?');
define('dbq_list_sitelang','select * from '.$px.'sitelang order by site asc, lang asc');
define('dbq_list_langs','select * from '.$px.'lang order by lang asc');

define('dbq_insert_relation','insert into '.$px.'relation values (?, ?, ?, ?, ?, ?, ?, ?)');
define('dbq_relation_bl_nextid','select if(isnull(max(sort)+1),1,max(sort)+1) from '.$px.'relation where code = ? and lsite = ? and `left` = ? and llang = ?');
define('dbq_relation_br_nextid','select if(isnull(max(sort)+1),1,max(sort)+1) from '.$px.'relation where code = ? and rsite = ? and `right` = ? and rlang = ?');
define('dbq_delrelation_bl','delete from '.$px.'relation where code = ? and lsite = ? and `left` = ? and llang = ? and rsite = ? and `right` = ? and rlang = ?');
define('dbq_delrelation_br','delete from '.$px.'relation where code = ? and rsite = ? and `right` = ? and rlang = ? and lsite = ? and `left` = ? and llang = ?');
define('dbq_delrelation_blall','delete from '.$px.'relation where code = ? and lsite = ? and `left` = ? and llang = ? ');
define('dbq_delrelation_brall','delete from '.$px.'relation where code = ? and rsite = ? and `right` = ? and rlang = ? ');
define('dbq_entry_delete','delete from '.$px.'entry where cmsid = ?; delete from '.$px.'fields where cmsid = ?; delete from '.$px.'pathlt where cmsid = ?; delete from '.$px.'pictures where cmsid = ?; delete from '.$px.'relation where (lsite = ? and `left` = ? and llang = ? ) or (rsite = ? and `right` = ? and rlang = ? );');

define('dbq_move1','select cmsid, sortid from '.$px.'entry where site = ? and pathl rlike ? and lang = ? order by sortid, cmsid');
define('dbq_move2','update '.$px.'entry set sortid = ? where cmsid = ?');

define('dbq_stor1','select min(sortid), max(sortid), count(sortid) from '.$px.'entry
where site = ? and pathl rlike ? and lang = ?');
define('dbq_stor2','insert into '.$px.'entry (`site`, pathl, `lang`, typepath, sortid, `name`, lname, published, `level`) values (?, ?, ?, ?, ?, ?, ?, ?, ?)');
define('dbq_stor3','update '.$px.'entry set pathl = ? where cmsid = ? ');
define('dbq_stor5','update '.$px.'entry set
	`site` = ?, pathl = ?, `lang` = ?, typepath = ?, sortid = ?, `name` = ?, lname = ?, published = ?, `level` = ?
where cmsid = ?');

define('dbq_stor7','delete from '.$px.'fields where cmsid = ?; delete from '.$px.'pictures where cmsid = ?; delete from '.$px.'pathlt where cmsid = ?;');


define('dbq_invalidate_lt','update '.$px.'pathlt set pathlt = \'\' where siteid = ? and (pathl like ? or pathl = ?) and lang = ? ');
define('dbq_delete_lt','delete from '.$px.'pathlt where siteid = ? and (pathl like ? or pathl = ?) and lang = ?');
define('dbq_set_lt','insert into '.$px.'pathlt values (?, ?, ?, ?, ?, ?) on duplicate key update pathltc = ?, pathlt = ?');
define('dbq_get_lt','select cmsid, pathlt, pathltc from '.$px.'pathlt where cmsid = ?');
define('dbq_get_lt_by_site_pathl_lang','select pathltc, pathlt from '.$px.'pathlt where site = ? and pathl = ? and lang = ?');
define('dbq_count_lt','select count(*) from '.$px.'pathlt where siteid = ? and (pathltc = ? or pathlt = ?) and lang = ?');

// search
define('dbq_search','select DISTINCT pathl, lang, typepath, sortid, name, lname, published, level
	from '.$px.'entry where
site = ? and left(pathl,@) = ? and level >= @ and level <= @ and find_in_set(lang, ?) and __WHERE__ order by level, sortid');
define('dbq_search_limit','select DISTINCT pathl, lang, typepath, sortid, name, lname, published, level
	from '.$px.'entry where
site = ? and left(pathl,@) = ? and level >= @ and level <= @ and find_in_set(lang, ?) and __WHERE__ order by level, sortid limit @');

define('dbq_search2','select DISTINCT pathl, lang, typepath, sortid, name, lname, published, level
	from __PRE__ '.$px.'entry __POST__ where
site = ? and left(pathl,@) = ? and level >= @ and level <= @ and find_in_set(lang, ?) and ( ( true __WHERE__ ) ) order by level, sortid');
define('dbq_search_limit2','select DISTINCT pathl, lang, typepath, sortid, name, lname, published, level
	from __PRE__ '.$px.'entry __POST__ where
site = ? and left(pathl,@) = ? and level >= @ and level <= @ and find_in_set(lang, ?) and ( ( true __WHERE__ ) ) order by level, sortid limit @');

define('dbq_rel_bl','select rsite, `right`, rlang, name, lname, level, typepath, isnull(name), published from ( select rsite, `right`, rlang, sort from 
	'.$px.'relation where code = ? and lsite = ? and `left` = ? and llang = ? ) as re join '.$px.'entry on ('.$px.'entry.site = re.rsite 
	and re.right = '.$px.'entry.pathl and re.rlang = '.$px.'entry.lang)
order by sort');
define('dbq_rel_br','select lsite, `left`, llang, name, lname, level, typepath, isnull(name), published from ( select lsite, `left`, llang, sort from 
	'.$px.'relation where code = ? and rsite = ? and `right` = ? and rlang = ? ) as re join '.$px.'entry on (re.lsite = '.$px.'entry.site
	and re.left = '.$px.'entry.pathl and re.llang = '.$px.'entry.lang)
order by sort');
define('dbq_retr123','select cms_entry.cmsid, typepath, sortid, `name`, lname, published, `level`, pathlt, pathltc
	from ('.$px.'entry) left join '.$px.'pathlt using (cmsid) where site = ? and '.$px.'entry.pathl = ? and '.$px.'entry.lang = ?
	union
	(select 0, fname, 0, ptyp, value, NULL, NULL, NULL, NULL from '.$px.'fields join '.$px.'entry using (cmsid) where site = ? and pathl = ? and lang = ? having fname != \'\')
	union 
	(select 1, `key`, `id`, file, `desc`, NULL, NULL, NULL, NULL from '.$px.'pictures join '.$px.'entry using (cmsid) where site = ? and pathl = ? and lang = ? order by `key`, `id`)
	union
	(select 2, `lang`, NULL, NULL, NULL, NULL, NULL, NULL, NULL from '.$px.'entry where site = ? and pathl = ?)');

define('dbq_relation_copy0','insert `cms_relation` SELECT `code`,`sort`,`lsite`, ? ,`left`, ? ,`llang`, ? FROM `cms_relation` WHERE rsite = ? and `right`= ? and rlang = ?');
define('dbq_relation_copy1','insert `cms_relation` SELECT `code`,`sort`, ? , `rsite`, ? ,`right`, ? ,`rlang` FROM `cms_relation` WHERE lsite = ? and `left`= ? and llang = ?');

?>
