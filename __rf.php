<?php

    $cms_skip_session = true;    
    require "__cms/_/bootstrap.php";
    
    $key = @$_GET['key'];

    if (strlen($key) == 0)
        return;

    $dat = cms_universe::uncombine_download_key($key);

	if (count($dat)==4) {
		$__siteid = $dat[0];
    	$__locpath = $dat[1];
    	$__pfname = $dat[2];
    	$__lang = $dat[3];

        $site = cms_universe::$puniverse->site_by_filter(cms_universe::$site_filter_id, $__siteid);

        $ent = $site->get($__locpath, $__lang);

        $download = true;
        if (@$_GET['no_download']) {
            $download = false;
        }

        if ($ent instanceof cms_entry) {
            $cfi = $ent->$__pfname;
            $fi = cms_entry::uncombine_file_information($cfi);
            $fs = cms_config::$cc_cms_images_dir.$fi[0];
            if (is_file($fs)) {
                $stf = stat($fs);
                $efn = urlencode($fi[2]);
                header('Content-Type: '.$fi[1]);
                header('Content-Length: '.$stf[7]);
                header('ETag: "'.md5($stf[9]).'"');
                if ($download) {
                    header('Content-Disposition: attachment; filename="'.$efn.'"'); 
                    header('Content-Transfer-Encoding: binary');
                }
                readfile($fs);
                return;
            } 
        }

    }

// if this was not a super download, maybe a simple file call ?

    $file = @$_GET['key'];
    $p = substr(str_replace('/','',$file),0,1);

    if ($p == '.' || $p == '_') {
	header("HTTP/1.0 404 Not Found");
    	die('__CMS file not found');
    }
    
    if (strlen($file) == 0)
        die();
        
    if (is_file($file)) {
	$fs = $file;
        $download = true;
        if ($_GET['no_download']) {
            $download = false;
        }

                $stf = stat($fs);
		if (
			(strpos($_SERVER["HTTP_USER_AGENT"] , "Trident") !== FALSE) ||
			(strpos($_SERVER["HTTP_USER_AGENT"] , "MSIE 7") !== FALSE) ||
			(strpos($_SERVER["HTTP_USER_AGENT"] , "MSIE 6") !== FALSE) ||
			(strpos($_SERVER["HTTP_USER_AGENT"] , "MSIE 5") !== FALSE)

		) {
			$efn = iconv("UTF-8", "ASCII//TRANSLIT", basename($file));
		} else {
			$efn = mb_encode_mimeheader(basename($file));
		}
                header('Content-Type: '.mime_content_type($file));
                header('ETag: "'.md5($stf[9]).'"');
                if ($download) {
                    header('Content-Disposition: attachment; filename="'.$efn.'"');                     
                }
		  header('Content-Transfer-Encoding: binary');
                readfile($fs);
                die();
    }

    header("HTTP/1.0 404 Not Found");
    echo ('__CMS file not found');
    return;

?>