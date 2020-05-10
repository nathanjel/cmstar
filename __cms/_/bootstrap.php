<?php

    function cms_tp_rac($entry,$ex_relation,$rrdata = true) {
        if ($entry instanceof cms_entry && $ex_relation) {
            $ei = $entry->site->id.'.'.$entry->lang.'.'.$entry->pathl.'-'.$ex_relation;
            $params = $GLOBALS['$%tp']->$ei;
            if (!is_array($params)) {            
                $ftable =      $entry->get_option('fieldtable_b');    
                $ftable .= ';'.$entry->get_option('fieldtable');
                $ftable .= ';'.$entry->get_option('fieldtable_');
                $ftable .= ';'.$entry->get_option('fieldtable__');
                $ftable .= ';'.$entry->get_option('fieldtable_a');
                if (strlen($ftable)>4) {
                    $fields = cms_universe::safesplitter($ftable,';');
                    foreach($fields as $fnumber=>$fdef) {
                        if ($fdef == '')
                            continue;
                        $fdef = cms_universe::safesplitter($fdef,',');
                        if ($fdef[1] != $ex_relation)
                            continue;
                        if (cms_options::get_operation($fdef[2]) != 'relate')
                            continue;
                        $params = cms_universe::safesplitter(cms_options::get_data($fdef[2]),':');
                        break;
                    }
                }
            }
        }
        if (@(!is_array($params))) {
            return array();
        }
        $GLOBALS['$%tp']->$ei = $params;
        @list($siteid, $pat, $code, $lr, $n, $uniq, $techno, $slang, $types) = $params; // params might be shorter
        switch($slang) {                                            
            case 'all':
                $slang = '';
                break;
            case 'current':
                $slang = $entry->lang;
                break;
            default:
                if(strlen($slang)>0) {
                    // list lang
                    $slang = explode(';',$slang);
                } else {
                    // current
                    $slang = $entry->lang;
                }
        }
        if ($types != '') {
            $styp = explode(',',$types);
        } else {
            $styp = null;
        }
        if ($siteid == 'current') {
            $siteid = $entry->_site_reference->id;
        }
        if ($entry->_site_reference->id == $siteid) {
            $ssite = $entry->_site_reference;
        } else {
            $ssite = cms_universe::$puniverse->site_by_filter('id', $siteid);
        }
        $lr = ($lr =="L" || $lr == 'l') ? cms_relation_accessor::$leftside : cms_relation_accessor::$rightside;
        $n = (int)$n;
        if ($n<1) $n = 1;
        $uniq = $uniq == 1 ? cms_relation_accessor::$uniq1w: cms_relation_accessor::$uniq0w;
        $rac = new cms_relation_accessor(
        $entry->_site_reference, $entry->pathl, $entry->lang,
        $code, $lr, $n, $uniq,
        $ssite, $pat, $slang, $styp);
        if ($rrdata) {
        	$results = $rac->related();
        	return $results;
        }
        return $rac;
    }

    // run time control timers
	$GLOBALS['!RTCct'] = $GLOBALS['!RTCst'] = time();
	$GLOBALS['!RTCmt'] = ini_get('max_execution_time');
	$GLOBALS['!RTCmtt'] = round(0.8*$GLOBALS['!RTCmt']); 
	$GLOBALS['!RTCnsm'] = ini_get('safe_mode') == 0;
	
    // all cms is utf8
    mb_internal_encoding("UTF-8");

    // make files groupwritable
    umask(0117);
    
    // process inputs 
    $mqgpc = get_magic_quotes_gpc();

    $in = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($k,$v) = each($in)) {
        reset($v);
        while (list($key, $val) = each ($v)) {
            if (!is_array($val)) {
                $in[$k][$key] = ($mqgpc?stripslashes(trim($val)):trim($val));
                continue;
            }
            $in[] =& $in[$k][$key];
        }
    }
    unset($in, $k, $v, $key, $val, $mqgpc);

    // autoloader
    //function __autoload($cn) {
    //    require $cn.'.php';
    //}

    // run configuration
    $bn = basename($_SERVER['SCRIPT_NAME']);

    if (substr($bn,0,2)=='__') {
        // view functions 
        $lead = '';
        $edit = false;
        define('CMS_IN_VIEW_MODE', 1);
        session_name("WEBSID");
    } else {
        // cms tools
        $lead = '../';
        $edit = true;
        define('CMS_IN_EDIT_MODE', 1);
        session_name("CMSSID");
    }

    require($lead.'vendor/autoload.php');

    set_include_path(get_include_path() . PATH_SEPARATOR . $lead . join(PATH_SEPARATOR.$lead, array(
    '__cms/_',
    '__cms/_/db',
    '__cms/_/i18n',
    '__cms/i18n',
    '__cms/_/caches',
    '__cms/_/lib',
    '__cms/_/xml',
    '__cms/config/_',
    '__cms/externals',
    '__phplibs/fpdf',
    '__phplibs/facebook-php-sdk-master',
    '__phplibs/pear',
    '__phplibs/geoip'
    )));

    cms_config::prefix_config_paths($lead);
    require cms_config::$cc_cms_customer_config_file;
    
    // error display and logging 
    
    if (cms_config::$cc_log_errors || cms_config::$cc_display_errors) {
    	error_reporting(cms_config::$cc_error_reporting);
    } else {
    	error_reporting(0);
    }
    if (cms_config::$cc_display_errors) {
    	ini_set('display_errors', '1');    	
    } else {
    	ini_set('display_errors', '0');
    }
    if (cms_config::$cc_log_errors) { 	
    	ini_set('log_errors', '1');
    	ini_set('error_log', cms_config::$cc_cms_error_log_file);    	   
    } else {
    	ini_set('log_errors', '0');
    }
        
    date_default_timezone_set(cms_config::$cc_timezone);

    // startup session
    if (
    		(isset($cms_skip_session) && $cms_skip_session) || // file requesting no session
    		((!$edit) && (cms_config::$cc_client_session_on == false)) // not edit mode and client sessions explictly disabled
    ) {
        // skip session
    } else {
        session_start();

        if (isset($_SESSION["_x__"])) {
            $_SESSION["_x__"] = $_SESSION["_x__"];
        } else {
            $_SESSION["_x__"] = array();
        }
    }
    
    // load language
    $sl = cms_lp::get_session_language_code();           

    // load universe
    if(isset($cms_skip_universe) && $cms_skip_universe) {
    } else {
        cms_universe::$puniverse = new cms_universe($edit);
    }
    
    ini_set("zlib.output_compression", "0");
        
    if (!isset($_SERVER['HTTP_IF_NONE_MATCH']) && (function_exists('apache_request_headers'))) {
    	$a = apache_request_headers();
    	@$_SERVER['HTTP_IF_NONE_MATCH'] = $a['If-None-Match'];
    }

    $GLOBALS['__MC__'] = new Memcache();
    $GLOBALS['__MC__']->connect('127.0.0.1',11211);

    $GLOBALS['__QS__'] = array(
        "queries" => 0,
        "qtime" => 0,
        "memhits" => 0,
        "memmiss" => 0,
        "tstarts" => 0,
        "tstops" => 0
    );

?>
