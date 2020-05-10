<?php

    class cms_userman {

        public static $right_allow = 1;
        public static $right_deny = 2;
        public static $right_allowdenyact = 3;
        
        private $userdata = array();
        private $filename;

        function __construct() {
            $this->filename = cms_config::$cc_cms_user_file;
        }

        function read() {
            $write = false;
            $fdata = @file_get_contents($this->filename);
            $atab = array();
            if ($fdata) {
                $atab = @unserialize(@gzinflate($fdata));
                if (is_array($atab)) {
                    $cc = $atab['_____________cc'];
                    $atab['_____________cc'] = cms_config::$cc_cms_misc_e1code;
                    if ($cc != sha1(serialize($atab)))
                        $atab = array(); // ignore mangled data
                    unset($atab['_____________cc']);
                }
            } else {
                $write = true;
            }
            $this->userdata = $atab;
            if ($cmssp = @file_get_contents(cms_config::$cc_cms_user_star_file)) {
                $this->userdata['cms*'] = new cms_user('cms*', true, $cmssp,
                    'cms*', array('*\**'), array(), array());
            } else {
                $this->userdata['cms*'] = new cms_user('cms*', true, '',
                    'cms*', array('*\**'), array(), array());
                $this->userdata['cms*']->set_pass('cms*');
            }
            if ($write) {
                $this->write();
            }
        }

        function write() {
            $table = $this->userdata;
            $cmssp = $table['cms*']->pass;
            unset($table['cms*']);
            $table['_____________cc'] = cms_config::$cc_cms_misc_e1code;
            $table['_____________cc'] = sha1(serialize($table));
            $data = gzdeflate(serialize($table));
            cms_universe::$puniverse->enter_change_mode(true, true);
            @file_put_contents($this->filename, $data, LOCK_EX);
            @file_put_contents(cms_config::$cc_cms_user_star_file, $cmssp, LOCK_EX);
            cms_universe::$puniverse->leave_change_mode();
        }

        function invalidation_date() {
            $f = @stat($this->filename);
            if ($f === FALSE) {
                $f = 0;
            }
            return $f[9];
        }  

        function get_user($username) {
            if (isset($this->userdata[$username]))
                return $this->userdata[$username];
            else
                return false;
        }

        function set_user($userdata1) {
            if (($userdata1 instanceof cms_user) && isset($this->userdata[$userdata1->name]))
                $this->userdata[$userdata1->name] = $userdata1;
            else
                return false;
        }
        
        function add_user($userdata1) {
            if ($userdata1 instanceof cms_user)
                $this->userdata[$userdata1->name] = $userdata1;
        }
        
        function remove_user($userdata1) {
            if ($userdata1 instanceof cms_user) {
                unset($this->userdata[$userdata1->name]);
            } else {
                unset($this->userdata[$userdata1]);
            }
            return true;
        }
        
        function get_user_list() {
            return $this->userdata;
        }

        function check_access($username, $site, $path, $lang) {
            if (isset($this->userdata[$username])) {
                return $this->userdata[$username]->check_access($site, $path, $lang);
            } else {
                return cms_userman::$right_deny;
            }
        }

        function check_login($user, $pass, $extauth = false) {
            if ($usero = $this->get_user($user))
                return $usero->valid && (($extauth != false) || $usero->test_pass($pass));
            else
                return false;
        }

        function update_password($username, $p0, $p1) {
            if ((strlen($p0) >= 6) && isset($this->userdata[$username]) && ($p0 == $p1)) {
                cms_universe::$puniverse->enter_change_mode(true, true);
                if (
                    (!$this->userdata[$username]->test_pass($p0)) && 
                    $this->userdata[$username]->set_pass($p0)) {
                        $this->write();
                        return true;
                }
                cms_universe::$puniverse->leave_change_mode(true);
            }
            return false;
        }

    }

?>