<?php

    class cms_options {

        private $options = array();
        private $lc;

        public function __construct($reset = false) {
            $this->lc = new cms_localcache('opts');

            $files = array(cms_config::$cc_cms_cop_file, cms_config::$cc_cms_opt_file);
            if($reset == false) {
            	$res = cms_filecache::restore_if_valid('options','all', $files);
            	if ($res != false) {
                	$this->options = unserialize($res);
                	return;
            	}	
            }

            foreach ($files as $file) {
                $oxml = new DOMDocument();
                $res = $oxml->load($file, LIBXML_NOCDATA | LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT);
                if (!$res) {
                    throw new RuntimeException('failed to load options file : '.$file);
                }
                $oroot = $oxml->firstChild;
                while($oroot->nodeName != 'cmsoptions') {
                    $oroot = $oroot->nextSibling;
                    if ($oroot == null) {
                        break;
                    }
                }
                if ($oroot == null) {
                    throw new InvalidArgumentException('invalid xml strucutre of file : '.$file);
                }
                $this->load($oroot);
                unset($oroot, $oxml);
            }                        
            cms_filecache::store('options', 'all', serialize($this->options));
        }

        private function load($onode) {
            $merge = array();        
            $onodesa = array();
            for($conode = $onode->firstChild; $conode != null; $conode = $conode->nextSibling) {
                if ($conode->nodeName != 'options')
                    continue;
                $site = $conode->getAttribute('site');
                $merge = $conode->getAttribute('merge')=="true"?true:false;
                // load!
                $opts =& $this->options[$site];
                for($onode = $conode->firstChild; $onode != null; $onode = $onode->nextSibling) {
                    if ($onode->nodeName != 'option')
                        continue;
                    $name = $onode->getAttribute('name');
                    $path = $onode->getAttribute('level');
                    $path = cms_path::convert_lpath_to_pcre($path,'+');
                    $value = $onode->getAttribute('value');
                    if ((strcasecmp($value,'true')==0) || (strcasecmp($value,'false')==0)) {
                        @$opts[$name][$path] = (strcasecmp($value,'true')==0?true:false);
                    } elseif ($merge) {
                        @$opts[$name][$path] .= $value;
                    } else {
                        @$opts[$name][$path] = $value;
                    }
                }
            }
        }

        function get($site, $optname, $path) {
        	// is local cached ?
        	$on = $site.'-'.$optname.'-'.$path;
            $ov = $this->lc->$on;
            if ($ov !== cms_localcache::$notfound) {
                return $ov;            
        	}
        	// is it defined ?
            @$q = $this->options[$site][$optname]; //the option migth not be set
            $r = NULL; // default value if none found should be null, false was a bad choice here
            if (!is_array($q) ) {
                if($site=='*') {
                	$this->lc->$on = $r;
                    return $r;
                } else {
                	$r = $this->get('*', $optname, $path);
            		$this->lc->$on = $r;
            		return $r;
                }
            }            
        	$af = false; // anything found ?
            foreach($q as $p=>$v) {
                if (($p=='+') || preg_match($p, $path)) {
                    $r = $v; $af = true;
                }
            }
            if (!$af) // if option not found at all, fallback to common site options
                $r = $this->get('*', $optname, $path);
            $this->lc->$on = $r;
            return $r;
        }

        public static function get_operation($str) {
            $s1 = strpos($str,'(');
            $s2 = strrpos($str,')',$s1);
            if ($s2>$s1) {
                return substr($str,0,$s1);
            } else {
                return $str;
            }
        }

        public static function get_data($str) {
            $s1 = strpos($str,'(');
            $s2 = strrpos($str,')',$s1);
            if ($s2>$s1) {
                return substr($str,$s1+1,$s2-$s1-1);
            } else {
                return "";
            }
        }

        public static function drop_slashes_arp(&$inar, $char) {
            foreach ($inar as $k=>$v) {
                $inar[$k] = str_replace('\\'.$char, $char, $v);
            }
        }

    }

?>