<?php

    class cms_login {

        public static $no_login_error = 0;
        public static $bad_cred = 1;
        public static $userlimit = 2;
        public static $timeout = 3;
        public static $logout = 4;
        public static $passchangefail = 5;
        public static $passchangereq = 6;

        protected $_user;
        protected $loginfailed = 0;
        protected $fresh_login = 1;

        public function inform_pcr() {
            if ($this->loginfailed)
                return;
            $this->loginfailed = cms_login::$passchangereq;
        }

        public function fresh() {
            return $this->fresh_login;
        }

        public static function getlastlogin($user) {
        	if (!cms_config::$cc_log_logins)
        		return null;
        	$para = array($user);
        	$res = cms_universe::$puniverse->db()->perform(dbq_lastlog, $para);
        	if(count($res) == 1) {
        		return array('LAST_SUCC'=>$res[0][0], 'LAST_FAIL'=>$res[0][1]);
        	}
        	return null;
        }
        
        public function __construct() {
            if (cms_universe::$puniverse->session()->login !== true) {
                $this->_user = null;
            } else {
                $now = time();
                if ((cms_universe::$puniverse->session()->latime + cms_config::$cc_login_timeout) >= $now) {
                    // within login timeout
                    $this->_user = cms_universe::$puniverse->userman()->get_user(cms_universe::$puniverse->session()->luser);
                    $this->fresh_login = 0;
                    cms_universe::$puniverse->session()->latime = $now;
                } else {
                    // login timeout for this user
                    cms_universe::$puniverse->session()->login = false;
                    $this->loginfailed = cms_login::$timeout;
                }
            }
            if ($this->is()) {
              if ($_POST['actioncode'] == "logout") {
                $this->logout();
                $_POST['actioncode'] = '';      
              }
            }
        }

        public function update_password($p0, $p1) {
            if (cms_universe::$puniverse->userman()->update_password($this->user()->name, $p0, $p1)) {
                // ok
                $this->loginfailed = cms_login::$no_login_error;
            } else {
                // fail
                $this->loginfailed = cms_login::$passchangefail;
            }
        }

        private function dbc($user, $ltime, $otime, $login, $error = false) {        	        	
        	if ($error) {
        		$para = array($user, $ltime, $_SERVER['REMOTE_ADDR']);
                if (cms_config::$cc_log_logins) $res = cms_universe::$puniverse->db()->perform(dbq_login_err, $para);
                $para = array($user, $ltime, $ltime);
                $res = cms_universe::$puniverse->db()->perform(dbq_last_log_fail, $para);
        	} elseif ($login) {
        		$para = array($user, $ltime, $_SERVER['REMOTE_ADDR']);
                if (cms_config::$cc_log_logins) $res = cms_universe::$puniverse->db()->perform(dbq_login_in, $para);
                $para = array($user, $ltime, $ltime);
                $res = cms_universe::$puniverse->db()->perform(dbq_last_log_succ, $para);
        	} else {
        		// logout
        		$para = array($otime, $user, $ltime);
        		if (cms_config::$cc_log_logins) $res = cms_universe::$puniverse->db()->perform(dbq_logout, $para);
        		$para = array($user, $otime, $otime);
                $res = cms_universe::$puniverse->db()->perform(dbq_last_log_lout, $para);
        	}        	
        }
        
        public function login($user, $pass, $extauth = false) {
        	$res = true;
        	cms_universe::$puniverse->enter_change_mode(false, true);
            if (cms_universe::$puniverse->userman()->check_login($user, $pass, $extauth)) {
                if ($this->inc_cu()) {
                    cms_universe::$puniverse->session()->login = true;
                    cms_universe::$puniverse->session()->lip = $_SERVER['REMOTE_ADDR'];
                    cms_universe::$puniverse->session()->luser = $user;
                    cms_universe::$puniverse->session()->ltime = time();
                    cms_universe::$puniverse->session()->latime = time();
                    cms_universe::$puniverse->session()->extauth = $extauth;
                    $this->loginfailed = cms_login::$no_login_error;
                    $this->_user = cms_universe::$puniverse->userman()->get_user(cms_universe::$puniverse->session()->luser);
                    $this->dbc(cms_universe::$puniverse->session()->luser, cms_universe::$puniverse->session()->ltime, 0, true, false);
                } else {
                    $this->_user = null;
                    $this->loginfailed = cms_login::$userlimit;
                }
            } else {
                $this->_user = null;
                $this->loginfailed = cms_login::$bad_cred;
            }
            if ($this->loginfailed != cms_login::$no_login_error) {
            	$this->dbc($user, time(), 0, true, true);
            	$res = false;
            }            
            cms_universe::$puniverse->leave_change_mode(false);
            return $res;
        }

        public function logout($user = '') {
        	cms_universe::$puniverse->enter_change_mode(false, true);
            if (cms_universe::$puniverse->session()->login === true) {
            	$this->dbc(cms_universe::$puniverse->session()->luser, cms_universe::$puniverse->session()->ltime, time(), false, false);
                cms_universe::$puniverse->session()->login = false;
                cms_universe::$puniverse->session()->luser = '';
                cms_universe::$puniverse->session()->ltime = 0;
                cms_universe::$puniverse->session()->latime = 0;
                cms_universe::$puniverse->session()->lip = '';
                cms_universe::$puniverse->session()->cxsite = '';
                cms_universe::$puniverse->session()->cxlang = '';
                cms_universe::$puniverse->session()->cxpath = '';
                cms_universe::$puniverse->session()->extauthname = '';
                if (cms_universe::$puniverse->session()->extauth == 'google') {
                    $gapi = cms_universe::$puniverse->googleAPI();
                    if ($gapi) {
                        $gapi->revokeToken();
                    }
                }
                cms_universe::$puniverse->session()->extauth = '';
                $this->_user = null;
                $this->dec_cu();
                $this->loginfailed = cms_login::$logout;
            }
            cms_universe::$puniverse->leave_change_mode(false);
        }

        public function login_error() {
            return $this->loginfailed;
        }

        public function user() {
            return $this->_user;
        }
        
        public function is() {
            return $this->_user != null;
        }

        protected function max_cu() {
            return cms_licence::get()->maxcu;
        }

        protected function current_cu() {
			$latest_login_time_considered_inactive = time() - cms_config::$cc_login_timeout;
			$para = array($latest_login_time_considered_inactive);
            $res = cms_universe::$puniverse->db()->perform(dbq_count_logged, $para);
            return $res[0][0];			  
        }

        protected function inc_cu() {
            return $this->current_cu() < $this->max_cu();
        }

        protected function dec_cu() {
			return true;
        }

    }

?>