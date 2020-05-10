<?php
	
	$GLOBALS['$RN'] = null;
    $GLOBALS['$%tp'] = new cms_localcache('tp');

    function &cms_tp_module_se_processor($name) {
        if (substr($name,0,1) == '#') {
            // stała znakowa
            return substr($name,1);
        }
        // zmienna z globals/tp
        $jumps = explode('/', $name);
        $local =& $GLOBALS;
        foreach($jumps as $jump) {
            if (is_object($local)) {
                @$local =& $local->$jump;
            } elseif (is_array($local)) {
                $local =& $local[$jump];
            } else {
                return $GLOBALS['$RN'];
            }
        }
        return $local;
    }

    function cms_tp_redir($entry) {
   	   while(ob_end_clean());
   	   if (($entry instanceof cms_entry_data) || (substr($entry,0,4) != 'http')) {
   	   	   header("Location: http".($_SERVER['HTTPS']!=''?'s':'')."://".
   	   	   	   $_SERVER['SERVER_NAME'].
   	   	   	   $GLOBALS['_location'].
   	   	   	   (($entry instanceof cms_entry_data)?$entry->slug:$entry));
   	   } else {
   	   	   header("Location: $entry");
   	   }
   	   die();
    }
    
    function cms_tp_module_jsvalue($code) {
        $code = preg_split('/[\n\r]/u',addcslashes($code,"'"),null, PREG_SPLIT_NO_EMPTY );
        $out = '';
        if (count($code) == 0) {
            return "''";
        }
        reset($code);
        while(list($ci,$codeline) = each($code)) {
            $out.="'";
            $out.=$codeline;
            $out.="' + '\\n' + ";
        }
        return mb_substr($out,0,-10);
    }

    function cms_tp_module_htmlvalue($code) {    
        return htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    }

    function cms_tp_module_strlen($code) {
        return mb_strlen($code);
    }

    function cms_tp_sampler($str, $much) {
        $sl = mb_strlen($str);
        if ($much>$sl)
            return $str;
        $l = mb_strpos($str,' ', $much);
        if (($l > ($much*1.25)) || (($l===FALSE) && ( $sl > ($much*1.25) ))) {
            $rr = mb_strrpos(mb_substr($str,0,$much), ' ');
            if ($rr)
                return (mb_substr($str,0,$rr).'...');
        }
        if ($l === FALSE) { 
            return $str;
        }
        return (mb_substr($str,0,$l).'...');
    }

    function cms_tp_autograph($file) {
        $load = getimagesize($file);
        if (($load[2] == IMAGETYPE_SWF) || ($load[2] == IMAGETYPE_SWC)) {
            //output :-)
            $df = strrpos($file, ".");
            $fid = "cms_fl_".time().mt_rand(12345,98765);
            $file_a = substr($file, 0, $df);
            $out = '<script language="JavaScript" type="text/javascript">
            var flashname_src="'.$file_a.'";      
            '."AC_FL_RunContent(
            'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=10,0,0,0',
            'width', '".$load[0]."',
            'height', '".$load[1]."',
            'src', 'flash',
            'quality', 'high',
            'pluginspage', 'http://www.adobe.com/go/getflashplayer',
            'align', 'middle',
            'play', 'true',
            'loop', 'true',
            'scale', 'showall',
            'wmode', 'transparent',
            'devicefont', 'false',
            'id', '".$fid."',
            'name', '".$fid."',
            'menu', 'true',
            'allowFullScreen', 'false',
            'allowScriptAccess','sameDomain',
            'movie', flashname_src,
            'salign', ''
            ); //end AC code
            </script> ".'
            <noscript>
            <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=10,0,0,0" width="857" height="307" id="flash" align="middle">
            <param name="allowScriptAccess" value="sameDomain" />
            <param name="allowFullScreen" value="false" />
            <param name="movie" value="'.$file.'" /><param name="quality" value="high" /><param name="bgcolor" value="#ffffff" />
            </object>
            </noscript>';           
        } else {
            $out = "<img src=\"$file\" width=\"$load[0]\" height=\"$load[1]\" alt=\"\" />";
        }    
        return $out;
    }
/*
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
*/
    function cms_tp_filter($list,$filter) {
        $GLOBALS["__%%x##@@"] = $filter;
        return array_filter($list,create_function('$x','
        $p = true;
        foreach ($GLOBALS["__%%x##@@"] as $fld=>$filter){
        if (is_array($x)) {
        $c = $x[$fld];
        } elseif (is_object($x)) {
        $c = $x->$fld;
        }
        $p = $p && $filter->call($c);
        if (!$p)
        break;
        }
        return $p;
        '));
    }

    if(cms_config::$cc_client_session_on) {
        @session_start();
    }

?>