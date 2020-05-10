<?php

    function sha2($x) {
        return hash('sha512', $x, false);
    }

    class cms_user {

        public $name;
        public $valid = false;
        public $pass = '';
        // public $adminset;
        public $desc;
        public $allowall = array();
        public $denyact = array();
        public $denyall = array();
        // public $lpcd = 0;

        public function needs_to_change_password() {
            if ($this->name == 'cms*') {
                return false;
            }
            if (!isset($this->lpcd)) {
                $this->lpcd = 0;
            }
            $diff = cms_config::$cc_login_pass_reset_days * 86400;
            return ($this->adminset) || ((time() - $this->lpcd) > $diff);
        }

        function test_pass($pass1) {
            $udp = $this->pass;
            $udc1 = sha1(cms_config::$cc_cms_misc_e1code.sha1($pass1));
            $udc2 = sha2(cms_config::$cc_cms_misc_e1code.sha2($pass1));
            return ($udc2 == $udp) || ($udp == $udc1);
        }     

        function set_pass($new_pass, $old_pass = -1024.1234) {
            if ($old_pass === -1024.1234) {
                $this->pass = sha2(cms_config::$cc_cms_misc_e1code.sha2($new_pass));
                $this->lpcd = time();
                $this->adminset = false;
                return true;
            }
            if ((strlen($this->pass) == 0) || $this->test_pass($old_pass)) {
                $this->pass = sha2(cms_config::$cc_cms_misc_e1code.sha2($new_pass));
                $this->lpcd = time();
                $this->adminset = false;
                return true;
            }
            return false;
        }

        function __construct($name1, $valid1, $pass1, $desc1, $allowall1, $denyact1, $denyall1) {
            $this->name = $name1;
            $this->valid = $valid1;
            if ($name1=='cms*') 
                $this->pass = $pass1;
            else
                $this->set_pass($pass1);
            $this->desc = $desc1;
            if (is_array($allowall1)) {
                $this->allowall = $allowall1;
            } else {
                $this->allowall[] = $allowall1;
            }
            if (is_array($denyall1)) {
                $this->denyall = $denyall1;
            } else {
                $this->denyall[] = $denyall1;
            }
            if (is_array($denyact1)) {
                $this->denyact = $denyact1;
            } else {
                $this->denyact[] = $denyact1;
            }
        }

        private function achelper($entry, $site, $path, $lang) {
        	// w $entry pojedynczy zapis uprawnien uzytkownika
            if ($entry == '') {
                return false;
            }
            @list($ts, $tp, $tl) = explode('\\', $entry);
            // site
            if ($ts == '*') {
                $ts = true;
            } else {
                $ts = ($ts == $site);
            }
            // path
            if ($path == '**') {
                $tp = true;
            } else {
                $tp = cms_path::convert_lpath_to_pcre($tp);
                if (preg_match($tp, $path)) {
                    $tp = true;
                } else {
                    $tp = false;
                }
            }
            // lang
            if ($tl == '*' || $tl == '') {
            	$tl = true;
            } else {
            	$tl = explode(',',$tl);
            	$tl = in_array($lang, $tl);
            }
            // overall
            return $ts && $tp && $tl;
        }

        function check_access($site, $path, $lang) {
            if ($path != '*') {
                foreach($this->denyall as $te) {
                    if ($this->achelper($te, $site, $path, $lang)) {
                        return cms_userman::$right_deny;
                    }
                }                  
            }      
            foreach($this->denyact as $te) {
                if ($this->achelper($te, $site, $path, $lang)) {
                    return cms_userman::$right_allowdenyact;
                }
            }
            foreach($this->allowall as $te) {
                if ($this->achelper($te, $site, $path, $lang)) {
                    return cms_userman::$right_allow;
                }
            }
            return cms_userman::$right_deny;
        }

        function landing_path($lang) {
            $vall = array_filter(array_merge($this->allowall, $this->denyact));
            while(count($vall)) {
                @list($ts, $tp, $tl) = explode('\\', array_shift($vall));
                if ($tl != '*' && $tl != $lang)
                    continue;
                $siteo = cms_universe::$puniverse->site_by_filter ('id', $ts);
                if (!is_object($siteo))
                    continue;
                if (strpos($tp,'*') !== false) {
                    $tp = cms_path::relative_path_alteration($tp, "-");
                }
                $block = $siteo->get($tp, $lang, false);
                if (is_object($block) && ($this->check_access($ts, $block->pathl, $lang) != cms_userman::$right_deny))
                    return array($ts,$block->pathl);
            }
            return array("","");
        }

        function access_filter($elist) {
            return array_filter($elist, function($e) {
                return $this->check_access($e->site->id, $e->pathl, $e->lang) != cms_userman::$right_deny;
            });
        }

    }

?>