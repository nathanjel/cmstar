<?php

  class cms_http {
      
      private static $proto;
      
      public static function si() {
          cms_http::$proto = $_SERVER['SERVER_PROTOCOL'];
      }
      
      public static function cannot_authorize() {
          header(cms_http::$proto.' 403 Forbidden');
      }
      
      public static function not_found() {
          header(cms_http::$proto.' 404 Not Found');
      }
      
      public static function bad_request() {
          header(cms_http::$proto.' 400 Bad Request');
      }
      
      public static function processing_error() {
          header(cms_http::$proto.' 500 Zesralo sie');
      }

      public static function clean_output_buffers() {
      		while(@ob_end_clean());
      }
      
      public static function headers_for_download($fn, $type) {
      	// Headers for an download:
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Content-Type: '.$type);                
                header('Content-Disposition: attachment; filename="' . $fn . '"'); 
                header('Content-Transfer-Encoding: binary');
      }
  }
  
  cms_http::si();
  
?>