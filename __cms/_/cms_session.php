<?php

    class cms_session {

        public static function purge() {
            unset($_SESSION["_x__"]);
        }
        
        public function __set($a,$b) {
            $_SESSION["_x__"][$a] = $b;
        }

        public function __get($a) {
            @$x =  $_SESSION["_x__"][$a]; // no value result in undef result, but no notice message
            return $x;
        }

        public function __isset($a) {

            return isset($_SESSION["_x__"][$a]);
        }

        public function __unset($a) {
            unset($_SESSION["_x__"][$a]);
        }
        
    }

?>