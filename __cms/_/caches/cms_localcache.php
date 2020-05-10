<?php

    class cms_localcache {
        
        public static $localcache = 'lcx1';
        public static $notfound = -2000000011;

        private $realm;
        private $per;
        private $perm;

        public function __construct($xrealm, $persistent = false) {
            $this->realm = '#$%#!'.$xrealm;
            $this->per = $persistent;
            $this->perm = false; $v = false;
            if ($persistent) $v = cms_filecache::restore(cms_localcache::$localcache, $this->realm);
            if ($v !== false) {
                $GLOBALS[$this->realm] = unserialize($v);
            } else {
                $GLOBALS[$this->realm] = array();
            }
        }

        public function __set($a, $b) {
            $this->perm = true;
            $GLOBALS[$this->realm][$a] = $b;
        }

        public function __get($a) {
            if (isset($GLOBALS[$this->realm][$a]))
                return $GLOBALS[$this->realm][$a];
            else
                return self::$notfound;
        }

        public function __isset($a) {
            return isset($GLOBALS[$this->realm][$a]);
        }

        public function __unset($a) {
            unset($GLOBALS[$this->realm][$a]);
        }

        public function __destruct() {
            if ($this->per && $this->perm) cms_filecache::store(cms_localcache::$localcache, $this->realm, serialize($GLOBALS[$this->realm]));
            unset($GLOBALS[$this->realm]);
        }
        
        public function &storage() {
            return $GLOBALS[$this->realm];
        }

    }

?>