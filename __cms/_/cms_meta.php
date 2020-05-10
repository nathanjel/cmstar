<?php
  class cms_meta {
        public $asession;
        public $aheader;
        public $acookie;
        
        public function clear() {
            $this->aheader = array();
            $this->acookie = array();
        }
        
        public function __construct() {
            $this->clear();
        }
        
        public function render($fromcache = false) {
            foreach ($this->aheader as $h) {
                header($h);
            }
            foreach ($this->acookie as $k=>$v) {
                setcookie($k,$v,0,'/');
            }
            if ($fromcache) {
                $s = cms_universe::$puniverse->session();
                foreach ($this->asession as $k=>$v) {
                    $s->$k = $v;
                }
            }
        }
        
        public function beforepage() {
            $GLOBALS['&&*(!)HD'] = array();
            $GLOBALS['&&*(!)CD'] = array();
            $GLOBALS['&&*(!)SD'] = array();
        }
        
        public function afterpage() {
            $this->aheader = $GLOBALS['&&*(!)HD'];
            $this->acookie = $GLOBALS['&&*(!)CD'];
            $this->asession = $GLOBALS['&&*(!)SD'];
        }
  }
?>
