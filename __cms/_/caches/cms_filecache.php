<?php

    class cms_filecache {
        
        private static $useapc = true;
       
        public static function cname($family, $key) {
            $j = md5($family.$key);
            $x = cms_config::$cc_cms_cache_dirs_variety;            
            if ($family == 'images') {
                $cf = cms_config::$cc_cms_imcache_folder;
                self::$useapc = false;
            } elseif ($family == 'template') {
                $cf = cms_config::$cc_cms_tp_compiled_folder;
                self::$useapc = true;
            } else {
                $cf = cms_config::$cc_filecache_folder;
                self::$useapc = true;
            }
            return $cf.substr($j,0,$x).'/C'.substr($j,$x,cms_config::$cc_cms_cache_hashlen-$x).strtr($key,'/\-=+*:?&','_________');
        }
        
        public static function ensuredir($nn) {
            $dname = dirname($nn);
            if (!is_dir($dname)) {
                $p = umask(0);
                mkdir($dname, 0770, true);
                umask($p);
            }
        }

        public static function file_newer_than_cache($orig_name, $family, $key) {
            if (cms_config::$cc_filecache_disable) 
                return true;
            $nn = cms_filecache::cname($family, $key);
            if (cms_config::$cc_cms_cache_apc && self::$useapc) {
                return !apc_exists($nn);
            }            
            if (is_array($orig_name)) {
                $s0 = 0;
                foreach($orig_name as $on) {
                    $s0b = @stat($on);
                    if ($s0b['mtime']>$s0)
                        $s0 = $s0b['mtime'];
                }
            } else {
                $s0 = @stat($orig_name);
                $s0 = $s0['mtime'];
            }
            $s1 = @stat($nn);
            if ($s1 == false) {
                return true;
            }
            return ($s0 > $s1['mtime']);
        }

        public static function time_newer_than_cache($time, $family, $key) {
            if (cms_config::$cc_filecache_disable) 
                return true;
            $nn = cms_filecache::cname($family, $key);
            if (cms_config::$cc_cms_cache_apc && self::$useapc) {
                return !apc_exists($nn);
            }            
            $s0 = $time;
            $s1 = @stat($nn);
            if ($s1 == false) {
                return true;
            }
            return ($s0 > $s1['mtime']);
        }

        public static function store($family, $key, $content) {
            if (cms_config::$cc_filecache_disable) 
                return false;                
            $nn = cms_filecache::cname($family, $key);
            if (cms_config::$cc_cms_cache_apc && self::$useapc) {
                return apc_store($nn, $content, cms_config::$cc_apc_store_ttl);
            }
            self::ensuredir($nn);
            $fd = fopen($nn, "wb");
            if ($fd) {
                if (fwrite($fd, $content, strlen($content))) {
                    fclose($fd);
                    return true;
                }
                fclose($fd);
            }
            return false;
        }

        public static function restore($family, $key) {
            if (cms_config::$cc_filecache_disable) 
                return false;
            $nn = cms_filecache::cname($family, $key);
            if (cms_config::$cc_cms_cache_apc && self::$useapc) {
                $s = false;
                $r = apc_fetch($nn, $s);
                if ($s) return $r;
                return false;
            }
            if (file_exists($nn)) {
                $p = @file_get_contents($nn, FILE_BINARY);
                return $p;
            } else {
                return false;
            }
        }

        public static function restore_if_valid($family, $key, $orig_file_or_time) {
            if (cms_config::$cc_filecache_disable) 
                return false;
            if (is_array($orig_file_or_time) || file_exists($orig_file_or_time)) {
                if (!cms_filecache::file_newer_than_cache($orig_file_or_time, $family, $key)) {
                    return cms_filecache::restore($family, $key);
                } else
                    return false;
            } else {
                if (!cms_filecache::time_newer_than_cache($orig_file_or_time, $family, $key)) {
                    return cms_filecache::restore($family, $key);
                } else
                    return false;
            }
        }

        public static function deletedir($dir) {
            $omit = array('.', '..', '.x');
            @($d = dir($dir));
            if ($d) {
              while(false !== ($e = $d->read())) {
                if (in_array($e, $omit))
                    continue;
                $w = $dir . DIRECTORY_SEPARATOR . $e;
                if (is_file($w)) {
                    @unlink($w);
                }
                if (is_dir($w)) {
                    cms_filecache::deletedir($w);
                    rmdir($w);
                }
              }
            }
        }
    }
    
?>