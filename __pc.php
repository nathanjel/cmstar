<?php

	// css and js assets stored in memory cache if apc supported
	
    $cms_skip_universe = true;
    $cms_skip_session = true;
    
    require "__cms/_/bootstrap.php";    
    
    $__aproto = $_SERVER["SERVER_PROTOCOL"];  
    
    $offset = cms_config::$cc_default_asset_expires_offset;

    //path converter
    $pi = $_SERVER['PATH_INFO'];

    if($pi=='')
        $pi = $_SERVER['QUERY_STRING']; // na wypadek wypadku horyzontu.webhostingu

    if($pi=='')
	    $pi = $_SERVER["ORIG_PATH_INFO"]; // na wypadek 1&1...

    $l1 = explode('*', $pi);        
    
    $file = join('*', array_slice($l1,4));
    $unrel = $l1[2];
    $mode = $l1[1];
    $ppp = $l1[3];
    $hash = $l1[0];

    $compress = ($ppp=='1' && (strpos($_SERVER["HTTP_ACCEPT_ENCODING"], 'gzip') !== FALSE) );

    $filerelpath = dirname($file).'/'; // no other kind of slash is used in url's up till now, so ... :-)
       
    $js = @stat($file);
    
    $usecache = cms_config::$cc_use_asset_cache && !cms_filecache::file_newer_than_cache($file, $mode, $file.$hash);
	
    if ($js) {
        // curr etag
        if (cms_config::$cc_use_etags) {
        	$cet = md5($file.$js[9]);
        	$tet = $_SERVER['HTTP_IF_NONE_MATCH'];
        	if ($cet == $tet) {
            	header("$__aproto 304"); 
            	header('ETag: '.$cet.'');
            	return;
        	}
        }
        if ($usecache) {
        	$a = cms_filecache::restore($mode, $file.$hash);
        	if (!$a)
        		$usecache = false;
        } 
        if (!$usecache) {
        	$a = file_get_contents($file);
        }
        if ($a) {           
        	if (cms_config::$cc_use_etags)
                	header('ETag: '.$cet);
            header("Expires: ". gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT");
            switch ($mode) {
            	case 'css':	
                // tyle nam narazie wystarczy...
                header("Content-type: text/css;");                
                if (!$usecache) {
                    // url's are relative to CSS file (when none or '')
                    $a = preg_replace('/url\((?!")([\']?)(?![a-z]+:|\/)([^\'\)]+)([\']?)\)/','url($1'.$unrel.$filerelpath.'$2$3)',$a);
                    // url's are relative to CSS file (when "")
                    $a = preg_replace('/url\("(?![a-z]+:|\/)([^"]+)"\)/','url("'.$unrel.$filerelpath.'$1")',$a);
                    // src's are relative to the place where css is called (so let's assume it's relative from sites root)
                    $a = preg_replace('/src=([\'"]?)(?![a-z]+:|\/)([^\'"]+)\1/','src=$1'.$unrel.'$2$1',$a);
                    // minifier
                    $a = preg_replace( '#\s+#', ' ', $a );
                    $a = preg_replace( '#/\*.*?\*/#s', '', $a );
                    $a = str_replace( '; ', ';', $a );
                    $a = str_replace( ': ', ':', $a );
                    $a = str_replace( ' {', '{', $a );
                    $a = str_replace( '{ ', '{', $a );
                    $a = str_replace( ', ', ',', $a );
                    $a = str_replace( '} ', '}', $a );
                    $a = str_replace( ';}', '}', $a );
                    if (cms_config::$cc_use_asset_cache)
                    	cms_filecache::store($mode, $file.$hash, $a);
                }
                if ($compress) {
                    header('Content-encoding: gzip'); 
                    header('Vary: Accept-Encoding');
                    $a = gzencode($a);
                }
                $len = strlen($a);
                header("Content-length: ".$len);
                echo $a;
                break;
             case 'js':
                require "__phplibs/jsminplus/jsminplus.php";
                header("Content-type: text/javascript;");
                if (!$usecache) {
                	if ((cms_config::$cc_js_compress_stop !== true) && (false === strpos($a, cms_config::$cc_js_compress_stop))) {                		
                		$a = $minified = JSMinPlus::minify($a);
                	}
                	if (cms_config::$cc_use_asset_cache)
                    	cms_filecache::store($mode, $file.$hash, $a);                   
                }
                if ($compress) {
                	header('Content-encoding: gzip');
                    header('Vary: Accept-Encoding');
                    $a = gzencode($a);
                }
                $len = strlen($a);
                header("Content-length: ".$len);
                echo $a;
                break;
               default:
                header("$__aproto 400 Bad Request");               	
            }
        }
    } else {
        header("$__aproto 404 Not Found");
    }
    
?>