<?php
class cms_lp { public static $_ = array(); public static $j = array(); };

$offset = 31536000; // 365 days
header("Content-type: text/javascript; charset=UTF-8");
header("Expires: ". gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT");

$lang = @$_GET['lang'];
if (!preg_match('/[a-z]{2}/', $lang)) {
	$lang = '';
}
if (file_exists("cms_lp_$lang.php")) {
	require("cms_lp_$lang.php");
} else {
	$lang = '';
}

$out = '';
$out.="var i18n = {";
foreach(cms_lp::$j as $v) {
	$out.='"'.addcslashes($v,'"').'" : "'.addcslashes(cms_lp::$_[$v],'"').'",';
}	
$out.='"." : "."';
$out.="}; var ckeditor_language_code = '{$lang}';";

echo $out;
?>