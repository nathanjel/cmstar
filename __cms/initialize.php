<?php

function save_file($name, $lines) {
	$fp = fopen($name, "w");
	if (!$fp) {
		die("failed to work on ".$name);
	}
	foreach($lines as $line) {
		fprintf($fp, "%s", $line);
	}
	fclose($fp);
}

function fix_htaccess(&$file_lines, $newpath) {
	if (strlen($newpath) > 1) {
		if(substr($newpath, -1) == '/') {
			$newpath = substr($newpath,0,-1);
		}
	}
	foreach($file_lines as $line_no=>$line) {
		if (stripos($line, "RewriteBase") === 0) {
			if ($line != "RewriteBase $newpath\n") {
				$file_lines[$line_no] = "RewriteBase $newpath\n";
				return true;
			}
			return false;
		}
	}
}

$x = $_SERVER['REQUEST_URI'];
$p = strpos($x, '__cms/initialize.php');
if ($p === FALSE || $p < 1) {
	die("cannot initialize, wrong filename or weird server config");
} 
$y = substr($x, 0, $p);
$z = $y."__cms/";

$fn0 = '.htaccess';
$fn1 = '../.htaccess';

$f0 = file($fn0);
$f1 = file($fn1);

if (fix_htaccess($f1, $y)) { save_file($fn1, $f1); echo "$fn1 updated\n"; }
if (fix_htaccess($f0, $z)) { save_file($fn0, $f0); echo "$fn0 updated\n"; }

echo "completed";

?>