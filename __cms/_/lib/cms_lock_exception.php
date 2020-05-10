<?php

    class cms_lock_exception extends Exception {
        public $lockfile = '';
        public function __construct($text, $code, $xfile) {
            parent::__construct($text, $code);
            $this->lockfile = $xfile;
        }
    }

?>