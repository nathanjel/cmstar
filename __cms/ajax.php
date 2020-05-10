<?php

    require "_/bootstrap.php";

    // use full compression and none caching for the server output pages
    ini_set("zlib.output_compression", "On");
    ini_set("zlib.output_compression_level", "9");

    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    if (@$_GET['pathx'] != '')
        $_GET['path'] = strtr($_GET['pathx'],'-+ ','///');

    if (@$_GET['sitex'] != '')
        $_GET['site'] = substr($_GET['sitex'],0,-1);

    if (@$_GET['langx'] != '')
        $_GET['lang'] = substr($_GET['langx'],1);

    $main = new cms_main_ajax();
    $main->main(true, true);

?>