<?php

    class cms_timedcache {
        
        public static $notfound = -2000000011;
        
        private $realm;
        private $to = PHP_INT_MAX;
        
        public $old = false;        
                
        public function __construct($xrealm) {
            $this->realm = '*/-'.$xrealm;
        }
        
        public function set_timeout($seconds) {
            $this->to = $seconds;
        }

        public function __set($a,$b) {
            cms_filecache::store('tdc.'.$this->realm, $a, serialize(array(time(), $b)));
        }

        public function __get($a) {
            $r = cms_filecache::restore('tdc.'.$this->realm, $a);
            if ($r == false)
                return self::$notfound;
            list($t,$v) = unserialize($r);            
            $this->old = ( (time() - $t) > $this->to );
            return $v;
        }

    }

?>