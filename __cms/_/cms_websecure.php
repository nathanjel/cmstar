<?php
    class cms_websecure {

        public static function abuse_protection($protect, $acheck) {

            // we do not clen the forms
            $clean = false;    

            // was there any reffer in this session ?
            if (isset($_SERVER['HTTP_REFERER'])) {
            	$p = true;
            	$ref = $_SERVER['HTTP_REFERER'];
            } else {
            	$ref = '';
            	$p = false;
            }
            
            // if there was ever reffer in this connection
            // then check if we're here from the same server
            // we send from
            if (cms_universe::$puniverse->session()->cms_overprot_refer == true) {
                if (strpos($ref, $_SERVER['HTTP_HOST'])===FALSE) {
                    $clean = true;
                }
            }
                        
            if ($p) {
            	// if we have a refferer now, let's make a point
            	cms_universe::$puniverse->session()->cms_overprot_refer = true;
            }

            // count $_POST size
            $val = count($_POST);
            $lasttime = cms_universe::$puniverse->session()->cms_overprot_time;

            if (!($lasttime>0)) {
                $lasttime = microtime(true);
            }

            $now = microtime(true);
            $diff = $now - $lasttime;

            if ($val > 3) {
                // several fields - 2 sec protection
                if ($diff<3) {
                    $clean = true;
                }
            } elseif ($val > 0) {
                // just a field or two or three or four - 1 sec protection
                if ($diff<1) {
                    $clean = true;
                }
            }

            cms_universe::$puniverse->session()->cms_overprot_time = $now;

            if (!$protect) {
                // if no protection, then exit
                return false;
            }

            if (strlen($acheck)>0) {
                // if there is an extra check, do it
                $acheck = '$tr0 = ('.$acheck.'?true:false);';
                eval($acheck);
                if ($tr0) {
                    // exit if check validated to true
                    return false;
                }
            }

            if ($clean) {
                reset($_POST);
                while(list($k,$v) = each($_POST))
                    unset($_REQUEST[$k]);
                unset($_POST);
                return true;
            }

            return false;

        }

    }
?>