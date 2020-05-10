<?php
    
    // output compression is handled "manually" by the script
    ini_set("zlib.output_compression", "0");  
    require "__cms/_/bootstrap.php";
    $preload = new cms_http();
    
    try {
        $main = new cms_main();
        $main->main(false, false);
    } catch (Exception $e) {
    	cms_universe::$puniverse->leave_change_mode(true);
        cms_http::processing_error();
        echo "<pre>\n";
        print_r($e);
        echo "</pre>\n";
    }
    
?>