<?php

    class cms_licence {
        
        // reading licence
        
        private static $lo = null;
        
        private $licencedata = array();
        
        public static function get() {
            if (cms_licence::$lo == null)
                cms_licence::$lo = new cms_licence();
            return cms_licence::$lo;
        }
        
        public function __get($a) {
            return $this->licencedata[$a];
        }
        
        private function __construct() {
        	if (!$this->cms_licence_read())
            	$this->licencedata = array(
                'valid_until' => 0,
                'edit_valid_until' => 0,
                'hosts' => array ('@non-existent-domain.never-ever@'),
                'maxcu' => 0,
                'issued_by' => 'OTD Marcin Gałczyński',
                'issued_for' => 'default lack of licence',
                'issue_date' => 0
            	);
        }
        
        private function cms_licence_read() {
        	$licence_data = $licence_signature = '';
        	require cms_config::$cc_cms_lic_file;
        	$p = serialize($licence_data);
        	$q = '';
        	$qc = '';
        	if ($q == $qc) {
        		$this->licencedata = $licence_data;
        		return true;
        	}
        	return false;
        }
		
    }

?>