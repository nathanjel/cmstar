<?php

    // use full compression and none caching for the server output pages
    ini_set("zlib.output_compression", "On");
    ini_set("zlib.output_compression_level", "9");

    require "_/bootstrap.php";    

    // no cache of CMS page content
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Content-type: text/html; charset=utf-8");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    if (@$_GET['pathx'] != '')
        $_REQUEST['path'] = $_GET['path'] = strtr($_GET['pathx'],'-+ ','///');

    if (@$_GET['sitex'] != '')
        $_REQUEST['site'] = $_GET['site'] = substr($_GET['sitex'],0,-1);

    if (@$_GET['langx'] != '')
        $_REQUEST['lang'] = $_GET['lang'] = substr($_GET['langx'],1);

    try {
        $main = new cms_main();
        $main->main(true, true);
    } catch (Exception $e) {
    	cms_universe::$puniverse->leave_change_mode(true);
        echo "<pre>\n";
        print_r($e);
        echo "</pre>\n";
    }

?>