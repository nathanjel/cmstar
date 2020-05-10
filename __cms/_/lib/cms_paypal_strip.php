<?php

    class cms_paypal_strip {
        
        function pp_ipn_incoming($data, $vurl) {
            $req = 'cmd=_notify-validate';
            $ra = array();
            foreach ($data as $key => $value) {
                $ra[$key] = $value;
                $value = urlencode($value);
                $req .= "&$key=$value"; 
            } 
            // Set the curl parameters.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $vurl);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);

            // Turn off the server and peer verification (TrustManager Concept).
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);

            // Set the request as a POST FIELD for curl.
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);

            // Get response from the server.
            $res = curl_exec($ch);

            if (strcmp ($res, "VERIFIED") === 0) {
                // recode
                $se = $ra['charset'];
                $te = 'UTF-8//IGNORE';
                foreach ($ra as $k=>$v) {
                    $i = iconv($se, $te, $v);
                    if ($i !== FALSE) {
                        $ra[$k] = $i;
                    }
                }
                // return to program
                return $ra;
            } 
            
            // error or $RES == "INVALID" -> false!
            return false;
        }
        
    }

?>