<?php

    //reimage.php?px=330&if=....&filters=.....
    //reimage.php/xxx/yyy/filepath/file.ppp/filters
    //reimage.php/xxx/0/filepath/file.ppp/filters
    //reimage.php/-xxx/-yyy/filepath/file.ppp/filters
    //filters N negage / b bright down / B bright up / G greyscale / c contrast down / C constrast up

// images are NOT using apc cache, only strict filecache is used

$cms_skip_universe = true;
$cms_skip_session = true;

require "__cms/_/bootstrap.php";

/* ini_set('display_errors',1);
 error_reporting(E_ALL); */

$__aproto = $_SERVER["SERVER_PROTOCOL"];

$tet = $_SERVER['HTTP_IF_NONE_MATCH'];

$offset = 31449600; // (364 dni)

$pi = $_SERVER['PATH_INFO'];
if($pi=='') {
	$pi = $_SERVER["ORIG_PATH_INFO"]; // na wypadek 1&1...
} 
if ($pi=='') {
	$pi = '/'.$_SERVER['QUERY_STRING']; // na wypadek wypadku nietypowego webhostingu
}

if (strlen($pi)>5) {
	$vars = explode('/',$pi);
	if (count($vars) > 3) {
		$rx = $vars[1];
		$ry = $vars[2];
		$laf = array_pop($vars);
		if (preg_match('/^FX::[NcCbBG]+$/', $laf)) {
			$filters = substr($laf,4);
		} else {
			$vars[] = $laf;
			$filters = '';
		}
		$zif = join('/', array_slice($vars,3));
	} else {
		$rx = $_GET['px'];
		$ry = $_GET['py'];
		$zif = $_GET['if'];
		$filters = $_GET['filters'];
	}
} else {
	$rx = $_GET['px'];
	$ry = $_GET['py'];
	$zif = $_GET['if'];
	$filters = $_GET['filters'];
}

$rx = (int)$rx;
$ry = (int)$ry;

if ( ($rx*$ry) < 0 ) {
	header("$__aproto 400 Bad Request");
	return;
}

if ((substr($zif,0,4)!='http') && (!file_exists($zif) || !is_file($zif))) {
	header("$__aproto 404 Not Found");
	return;
}

$cached = true;
$nn = cms_filecache::cname('images',$rx.'.'.$ry.'.'.$filters.'.'.$zif);    

if (!file_exists($nn) || (!cms_config::$cc_use_asset_cache)) {
	// image not cached or asset cache disabled
	$cached = false;
	$i = getimagesize($zif);
	$process = false;
	
	$ni = new cms_nthimage();
	$nit = ($i[2]==IMAGETYPE_JPEG?IMAGETYPE_JPEG:IMAGETYPE_PNG);
	
	if ($i[2]==IMAGETYPE_SWF || $i[2]==IMAGETYPE_SWC) {
		$zif = cms_config::$cc_fl_picture;
	}
	
	$ni->load($zif);
	
	if (
			!(
					(($i[1] == $i[0]) && ($i[0] == 1)) 
					|| 
					(($i[0] == abs($rx)) && ($i[1] == abs($ry))) 
			)
	) {
		$process = true;            
	}
	
	// apply filters if neccesary && if possible...
	if(strlen($filters)) {
		$process = true;
		if(!function_exists('imagefilter')) {
			// however, if filtering is requested but not possible
			header("$__aproto 501 Not Implemented");
			echo "filtering is not possible in this installation, as imagefilter function was not found";
			die();
		}         
	}
	
	if ($process) {
		//image size must be modified
		if ($ry == 0) {
			$ni->resizeProportionalToFit(abs($rx),1000000);
		} elseif ($rx == 0) {
			$ni->resizeProportionalToFit(1000000,abs($ry));
		} elseif (($rx < 0) && ($ry < 0)){
			$ni->resizeProportionalToFit(-$rx,-$ry);
		} else {
			$ni->resizeProportionalAndClip($rx,$ry);
		}		
		if(strlen($filters)) {
			$ni->filter($filters);
		}		
		// save scaled & filtered image file for reference
		// use use jpg for jpg, use png for all others        
		if (cms_config::$cc_use_asset_cache) {
			cms_filecache::ensuredir($nn);
			$ni->save($nn, $nit);
		}		
		if (!file_exists($nn)) {
				// if saving to cache fails, then cache is wrong, just output image
				// or there is no cache, which literally means the same
				$mime = image_type_to_mime_type($nit);
				header("Content-type: ".$mime);
				header("Expires: ". gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT");
				header("X__CMS-filename: ".$nn);
				header("X__CMS-status: ".'regenerated');
				$ni->output($nit);
				return;
		}		
	} else {
		$nn = $zif;
	}
} else {
	// read mime from cache
	$i = getimagesize($nn);
	$nit = ($i[2]==IMAGETYPE_JPEG?IMAGETYPE_JPEG:IMAGETYPE_PNG);
}

$fs = stat($nn);
$cet = md5($nn.$fs[9]);
if (cms_config::$cc_use_etags) {
	if ($cet == $tet) {
		header($__aproto.' 304 Not Modified'); 
		header('ETag: '.$cet.'');
		return;
	}
}

$mime = image_type_to_mime_type($nit);
header("X__CMS-filename: ".$nn);
header("X__CMS-status: ".'from cache');
header("Content-type: ".$mime);
header("Expires: ". gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT");
if (cms_config::$cc_use_etags)
	header('ETag: '.$cet.'');
header("Content-length: ".$fs[7]);

/* ob_clean(); */
flush();

readfile($nn);
flush();

?>