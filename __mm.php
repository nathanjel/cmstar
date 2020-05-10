<?php

header("Content-type: text/plain; charset=utf-8");

require "__cms/config/_/cms_config.php";
require cms_config::$cc_cms_customer_config_file;

if (@$_REQUEST[cms_config::$cc_cms_misc_service_key_key] != cms_config::$cc_cms_misc_service_key_value) {
	header("HTTP/1.0 403 Forbidden");
	die("cannot execute, service key missing or invalid");
}

$fld = cms_config::$cc_cms_path;

if (isset($_SERVER['SERVER_NAME'])) {
	$spn = $_SERVER['SERVER_NAME'];
} else {
	$spn = cms_config::$cc_cms_host;
}

$url = 'http://'.$spn.$fld.'/mailer.php?&topsecret1234';

$ch = curl_init();
$timeout = 10;
$timeout2 = 3600;
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
curl_setopt($ch,CURLOPT_TIMEOUT,$timeout2);
$data = curl_exec($ch);
curl_close($ch);

echo "MM run :\n" . $data;

?>