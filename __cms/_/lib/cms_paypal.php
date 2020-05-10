<?php

    class cms_paypal {
        
        function pp_button_add_custom($html_in, $custom) {
            $custom = addslashes($custom);
            /*
            <form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="hosted_button_id" value="2BPNPG6BJMCTG">
            */
            $custom_html = "<input type=\"hidden\" name=\"custom\" value=\"$custom\"/>";
            return preg_replace('/>[^<]*</',">\n".$custom_html."\n<",$html_in,1);
        }

        function pp_ipn_incoming() {
            // Read the post from PayPal and add 'cmd' 
            $req = 'cmd=_notify-validate';
            $ra = array();
            foreach ($_POST as $key => $value) {
                $ra[$key] = $value;
                $value = urlencode($value);
                $req .= "&$key=$value"; 
            } 
            $serv = get_bank_data('PAYPAL');
            $serv = $serv[5];
            // Set the curl parameters.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $serv);
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
            } else if (strcmp ($res, "INVALID") === 0) { 
                    // IF INVALID, THEN OLEJ	
                    // olewam...
                    return false;
                }

        }

        function ppapi_call($fun, $nvparray) {

            // Set up your API credentials, PayPal end point, and API version.
            $bd = get_bank_data('PAYPAL');
            $API_UserName = urlencode($bd[1]);
            $API_Password = urlencode($bd[2]);
            $API_Signature = urlencode($bd[3]);
            $API_Endpoint = $bd[0];
            $version = urlencode($bd[4]);

            // Set the curl parameters.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);

            // Turn off the server and peer verification (TrustManager Concept).
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);

            $nvpStr_ = '';
            foreach ($nvparray as $n=>$v) {
                $nvpStr_ .= '&'.$n.'='.urlencode($v);
            }

            // Set the API operation, version, and API signature in the request.
            $nvpreq = "METHOD=$fun&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";

            // Set the request as a POST FIELD for curl.
            curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

            // Get response from the server.
            $httpResponse = curl_exec($ch);

            if(!$httpResponse) {
                exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
            }

            // Extract the response details.
            $httpResponseAr = explode("&", $httpResponse);

            $httpParsedResponseAr = array();
            foreach ($httpResponseAr as $i => $value) {
                $tmpAr = explode("=", $value);
                if(sizeof($tmpAr) > 1) {
                    $httpParsedResponseAr[$tmpAr[0]] = urldecode($tmpAr[1]);
                }
            }

            if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
                throw new bpl_exception("Invalid PayPal Response for POST request($nvpreq) to $API_Endpoint. Please contact site manager.", 303);
            }

            return $httpParsedResponseAr;

        }

        function pp_buynow_button_prepare($pid, $name, $price, $curr, $linkok, $linkca, $linkipn) {
            $pi = $bd[6];
            $ea = $bd[7];
            $ra = array(
            'BUTTONTYPE'=>'BUYNOW',
            'BUTTONCODE'=>'HOSTED',
            'BUTTONSUBTYPE'=>'SERVICES',
            'BUTTONIMAGE'=>'SML',
            'L_BUTTONVAR0'=>"amount=$price",
            'L_BUTTONVAR1'=>"item_name=$name",
            'L_BUTTONVAR2'=>"item_number=$pid",
            'L_BUTTONVAR3'=>"currency_code=$curr",
            'L_BUTTONVAR4'=>"shipping=0",
            'L_BUTTONVAR5'=>"tax=0",
            'L_BUTTONVAR6'=>"quantity=1",
            'L_BUTTONVAR7'=>"no_shipping=1",
            'L_BUTTONVAR8'=>"no_note=1",
            'L_BUTTONVAR9'=>"charset=utf-8",
            'L_BUTTONVAR10'=>"page_style=primary",
            'L_BUTTONVAR11'=>"business=$ea"
            );
            $i = 12;
            if ($linkok) {
                $ra['L_BUTTONVAR'.$i++]="return=$linkok";
            }
            if ($linkca) {
                $ra['L_BUTTONVAR'.$i++]="cancel_return=$linkca";
            }
            if ($linkipn) {
                $ra['L_BUTTONVAR'.$i++]="notify_url=$linkipn";
            }
            if (strlen($pi)) {
                $ra['L_BUTTONVAR'.$i++]="cpp_header_image=$pi";
            }
            return $ra;
        }

        function pp_buynow_button_update($pid, $bdef, $ltcode = '') {
            // return true or false
            // do we have a button in DB
            $havebut = false;
            $pb = pp_buynow_button_get($pid, $ltcode);
            if ($pb[0]) {
                // get details from paypal
                $nvp = array(
                'HOSTEDBUTTONID'=>$pb[0]
                );
                $rnvp = ppapi_call('BMGetButtonDetails', $nvp);
                // check
                if ($rnvp['HOSTEDBUTTONID'] == $pb[0]) {
                    // to jest ten !!
                    $havebut = true;
                    $nbdef = $bdef;
                    $nbdef['HOSTEDBUTTONID'] = $pb[0];
                    $rnvp = ppapi_call('BMUpdateButton', $nbdef);
                    if ($rnvp['HOSTEDBUTTONID'] == $pb[0]) {
                        // udane
                        $wc = addslashes($rnvp['WEBSITECODE']);
                        $GLOBALS["__BPL"]->db()->perform("update paypal_buttons set html = '$wc' where ownkey = '$ltcode$pid'");
                        return true;
                    } else {
                        // wyjebka
                        // usuń wpis z BD
                        $GLOBALS["__BPL"]->db()->perform("delete from paypal_buttons where ownkey = '$ltcode$pid'");
                    }
                } else {
                    // wyjebka
                    // zachowany button nie jest tym za kogo się podaje...
                    // usuń wpis z BD
                    $GLOBALS["__BPL"]->db()->perform("delete from paypal_buttons where ownkey = '$ltcode$pid'");
                }
            }
            // was ok ?
            if (!$havebut) {
                // if no then create new one
                $nvp = $bdef;
                $rnvp = ppapi_call('BMCreateButton', $nvp);
                if ($rnvp['HOSTEDBUTTONID']) {
                    // we have ej button
                    $table = "paypal_buttons";
                    $fields = array('ownkey', 'palkey', 'html');
                    $val = array(array($ltcode.$pid, $rnvp['HOSTEDBUTTONID'], $rnvp['WEBSITECODE']));
                    $GLOBALS["__BPL"]->db()->insert($table, $fields, $val);
                    return true;
                }
                // else no luck this time
            }	
            // no button if we're still here
            return false;
        }

        function pp_buynow_button_clear($pid, $ltcode = '') {
            // return true or false
            // do we have a button in DB
            return $GLOBALS["__BPL"]->db()->perform("delete from paypal_buttons where ownkey = '$ltcode$pid'");
        }


        function pp_buynow_button_get($pid, $ltcode = '') {
            // do we have it ?
            $pid = addslashes($pid);
            $pb = $GLOBALS["__BPL"]->db()->perform("select palkey, html from paypal_buttons where ownkey = '$ltcode$pid'");
            if ($pb[0][0]) {
                // yes we have
                return $pb[0];
            } else {
                // no button in db
                return false;
            }
        }

        function pp_subscribe_button_clear($pid) {
            return pp_buynow_button_clear($pid, 'SS');
        }

        function pp_subscribe_button_get($pid) {
            return pp_buynow_button_get($pid, 'SS');
        }

        function pp_subscribe_button_update($pid, $bdef) {
            return pp_buynow_button_update($pid, $bdef, 'SS');
        }

        function pp_subscribe_button_prepare($pid, $name, $price, $curr, $linkok, $linkca, $linkipn, $period, $unit) {
            $bd = get_bank_data('PAYPAL');
            $pi = $bd[6];
            $ea = $bd[7];
            $ra = array(
            'BUTTONTYPE'=>'SUBSCRIBE',
            'BUTTONCODE'=>'HOSTED',
            'BUTTONSUBTYPE'=>'SERVICES',
            'BUTTONIMAGE'=>'SML',
            'L_BUTTONVAR0'=>"a3=$price",
            'L_BUTTONVAR1'=>"item_name=$name",
            'L_BUTTONVAR2'=>"item_number=$pid",
            'L_BUTTONVAR3'=>"currency_code=$curr",
            'L_BUTTONVAR4'=>"shipping=0",
            'L_BUTTONVAR5'=>"tax=0",
            'L_BUTTONVAR6'=>"quantity=1",
            'L_BUTTONVAR7'=>"no_shipping=1",
            'L_BUTTONVAR8'=>"no_note=1",
            'L_BUTTONVAR9'=>"p3=$period",
            'L_BUTTONVAR10'=>"t3=$unit",
            'L_BUTTONVAR11'=>"business=$ea",
            'L_BUTTONVAR12'=>"no_note=1",
            'L_BUTTONVAR13'=>"src=1",
            'L_BUTTONVAR14'=>"srt=",
            'L_BUTTONVAR15'=>"charset=utf-8",
            'L_BUTTONVAR16'=>"page_style=primary"
            );
            $i = 17;
            if ($linkok) {
                $ra['L_BUTTONVAR'.$i++]="return=$linkok";
            }
            if ($linkca) {
                $ra['L_BUTTONVAR'.$i++]="cancel_return=$linkca";
            }
            if ($linkipn) {
                $ra['L_BUTTONVAR'.$i++]="notify_url=$linkipn";
            }
            if (strlen($pi)) {
                $ra['L_BUTTONVAR'.$i++]="cpp_header_image=$pi";
            }
            return $ra;
        }

    }

?>