<?php

	$GLOBALS['___cnt_foreach'] = 0;
	
    class cms_tp {
       
        private $tfname;

        private static $allcmd = '|match|aget|applyfilter|array|aset|autograph|break|calculate|concat|concatenate|continue|count|debug|dec|dequeue|download|else|elseif|elseifnot|endif|endloop|endrepeat|enqueue|filter|fin|get|getcache|getcookie|getsession|header|if|ifnot|inc|load|loadentry|loadfulllist|loadfulllistfilter|loadlist|loadrelated|loadsite|obstart|obstop|option|pathcut|pathlen|php|pop|push|redirect|repeat|replace|reverse|sampler|set|setcache|setcookie|setsession|sortinplace|tp|treesort|varloop';
        private static $beginblock = '<?php require_once "__cms/_/tp.php"; ?>';
        
        private static $shouterrors = '<?php ini_set(\'display_errors\', 1); error_reporting(E_ALL & ~(E_NOTICE | E_STRICT)); ?>';
        private static $hideerrors = '<?php ini_set(\'display_errors\', 0); error_reporting(0); ?>';

        private static $endblock ='<?php // :-) ?>';

        public function debug($ref, $comment = true) {
            if ($comment) echo "<!--";
            print_r($ref);
            if ($comment) echo "-->";
        }

        public function setup($location, $site, $lang, $path, $entry) {
            $GLOBALS['_path'] = $path;
            $GLOBALS['_site'] = $site;
            $GLOBALS['_entry'] = $entry;    
            $GLOBALS['_lang'] = $lang;    
            $GLOBALS['_location'] = $location;
        }

        public function run() {
            require $this->compiled_name();
        }

        public function __construct($tfname) {
            $this->tfname = cms_config::$cc_cms_site_templates_dir.$tfname;
            if(!$this->iscompiled())
                $this->compile();
        }

        public function compiled_name() {
            return  
            cms_config::$cc_cms_tp_compiled_folder.
            cms_config::$cc_cms_tp_compiled_prefix.
            strtr($this->tfname,'/\\','__').
            cms_config::$cc_cms_tp_compiled_postfix;
        }

        private function iscompiled() {
            $p1 = @stat($this->tfname);
            $p2 = @stat($this->compiled_name());
            if ($p2 == false) 
                return false;
            return $p2['mtime'] > $p1['mtime'];
        }

        private function compile() {
            $src = file_get_contents($this->tfname);
            $cn = $this->compiled_name();

            $sout = cms_tp::compiler($src);

            if ($sout === false) {
                @unlink($cn);
                return false;    
            }

            file_put_contents($cn, $sout);
            return true;
        }

        private static function compiler($source) {
            // command regex
            $regex = "#{(".cms_tp::$allcmd.")([;:@])([^}]{0,})}#su";
            // compile
            $result = preg_replace_callback($regex, array('cms_tp', 'block_compiler'), $source);
            // build
            $result = cms_tp::$beginblock
            	.(cms_config::$cc_cms_tp_show_errors ? cms_tp::$shouterrors : cms_tp::$hideerrors)
            	.$result
            	.cms_tp::$endblock;
            // remove html comments
                // leaves the FBML's and the <!--[if... ] sequences alone
            $result = preg_replace('/<!(?!-->|--<|--\s*FBML|--\s*\[[^\]]+\])(?:--(?:[^-]*|-[^-]+)*--\s*)>/u', "", $result);
            // remove spaces
            $result = preg_replace('/\?\>\s*\<\?php/u', '', $result);
            $result = preg_replace('/\>\s+\</u', '> <', $result);
            return $result;
        }

        private static function control_wrapper($control, $command) {
            if ($control == ';') {
                return "cms_tp_module_jsvalue($command)";
            } elseif ($control == '@') { 
                return "cms_tp_module_htmlvalue($command)";
            } else {
                return $command;
            }
        }

        private static function block_compiler($matches) {
            $parameter = $matches[3];
            $control = $matches[2];
            $command = $matches[1];
            $out='<?php ';
            $params = cms_universe::safesplitter($parameter,',');
            // special vars support
            foreach ($params as $k=>$v) {
                $v = addcslashes($v,'"');
                $params[$k] = $v;
            }

            $command = strtolower($command);
            if(substr($command,0,1) == '/') {
                return '';
            }
            
            switch($command) {
                case '':
                case 'get':
                    $out .= "echo ".cms_tp::control_wrapper($control, cms_tp::compiler_simpleblock($params[0])).";";
                    break;
                case 'fin':
                    $out .= 'die(); ';
                    break;
            	case 'redirect':
            	    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
            	    $out .= 'cms_tp_redir($ref); ';
            	    break;
                case 'tp':
                    $out .= '
                    $t = new cms_tp('.cms_tp::compiler_simpleblock($params[0]).'); $t->run();';
                    break;
                case 'loadsite':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = cms_universe::$puniverse->site_by_filter("id", '.cms_tp::compiler_simpleblock($params[1]).',true);';
                    break;
                case 'load': // to support old short version - {load:var,path}
                case 'loadentry':
                    if (count($params)==4) {
                        $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                        $out .= '$__site = '.cms_tp::compiler_simpleblock($params[1]).'; ';
                        $out .= '$__lang = '.cms_tp::compiler_simpleblock($params[3]).'; ';                    
                        $out .= '$__path = '.cms_tp::compiler_simpleblock($params[2]).'; ';
                        $out .= 'if ($__path[0] == "/") { $__path = $GLOBALS["_path"].$__path;}';
                        $out .= '$ref = $__site->get($__path,$__lang,true,$__site->dbe);';
                    } else {
                        $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                        $out .= '$__path = '.cms_tp::compiler_simpleblock($params[1]).'; ';
                        $out .= 'if ($__path[0] == "/") { $__path = $GLOBALS["_path"].$__path;}';
                        $out .= '$ref = $GLOBALS["_site"]->get($__path,$GLOBALS["_lang"],true,$GLOBALS["_site"]->dbe);';
                    }
                    break;
                case 'loadrelated':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = cms_tp_rac('.cms_tp::compiler_simpleblock($params[1]).','.cms_tp::compiler_simpleblock($params[2]).');';
                    break;
                case 'loadlist':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$__site = '.cms_tp::compiler_simpleblock($params[1]).'; ';
                    $out .= '$__lang = '.cms_tp::compiler_simpleblock($params[3]).'; ';                    
                    $out .= '$ref = $__site->get('.cms_tp::compiler_simpleblock($params[2]).',$__lang,false,$__site->dbe);';     
                    break;
                case 'loadfulllist': 
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$__site = '.cms_tp::compiler_simpleblock($params[1]).'; ';
                    $out .= '$__lang = '.cms_tp::compiler_simpleblock($params[3]).'; ';                    
                    $out .= '$ref = $__site->get('.cms_tp::compiler_simpleblock($params[2]).',$__lang,true,$__site->dbe,true,null,null,null,true,false);';     
                    break;
                case 'loadfulllistfilter': 
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$__site = '.cms_tp::compiler_simpleblock($params[1]).'; ';
                    $out .= '$__lang = '.cms_tp::compiler_simpleblock($params[3]).'; '; 
                    $out .= '$__filter = '.cms_tp::compiler_simpleblock($params[4]).'; ';
                    $out .= '$ref = $__site->get('.cms_tp::compiler_simpleblock($params[2]).',$__lang,true,$__site->dbe,true,$__filter,null,null,true,false);';     
                    break;
                case 'applyfilter':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = cms_tp_filter('.cms_tp::compiler_simpleblock($params[1]).','.cms_tp::compiler_simpleblock($params[2]).');';
                    break;
                case 'array':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = array(';
                    foreach ($params as $i=>$v) {
                        if ($i==0)
                            continue;
                        $out .= cms_tp::compiler_simpleblock($v).', ';
                    }
                    $out .= '); ';
                    break;
                case 'filter':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = new cms_filter(); ';
                case 'inc':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref++; ';
                    break;                    
                case 'dec':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref--; ';
                    break;
                case 'reverse':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = array_reverse($ref); ';
                    break;
                case 'treesort':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= 'cms_entry::treesort($ref); ';
                    break;
                case 'sortinplace':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$field = '.cms_tp::compiler_simpleblock($params[1]).'; ';
                    if (count($params)==3) {
                    	    $out .= '$dir = '.cms_tp::compiler_simpleblock($params[2]).'; ';
                    } else {
                    	    $out .= '$dir = 1; ';
                    }
                    $out .= 'cms_entry::sort($ref, $field, $dir); ';
                    break;
                case 'obstart':
                    $out .= 'ob_start(); ';
                    break;
                case 'obstop':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = ob_get_clean(); ';
                    break;
                case 'calculate':
                    $out .= '$a = '.cms_tp::compiler_simpleblock($params[1]).'; ';
                    $out .= '$b = '.cms_tp::compiler_simpleblock($params[3]).'; ';
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    if (in_array($params[2], array('/','%'))) {
                        $out .= '$ref = ($b==0?0:$a '.$params[2].' $b ); ';    
                    } else {
                        $out .= '$ref = ( $a '.$params[2].' $b ); ';
                    }                    
                    break;
                case 'concatenate':
                case 'concat':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; $ref = \'\' ';       
                    foreach ($params as $i=>$v) {
                        if ($i==0)
                            continue;
                        $out .= ' . '.cms_tp::compiler_simpleblock($v).' ';
                    }
                    $out .= ';';
                    break;                    
                case 'replace':
                    $out .= '$__inwhere =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$__fromwhat = '.cms_tp::compiler_simpleblock($params[1]).'; ';
                    $out .= '$__towhat = '.cms_tp::compiler_simpleblock($params[2]).'; ';
                    $out .= '$__inwhere = str_ireplace($__fromwhat, $__towhat, $__inwhere); ';
                    break;
                case 'set':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = '.cms_tp::compiler_simpleblock($params[1]).';';
                    break;
                case 'sampler':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = cms_tp_sampler('.cms_tp::compiler_simpleblock($params[1]).",".cms_tp::compiler_simpleblock($params[2]).");";
                    break;
                case 'option':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$__site = '.cms_tp::compiler_simpleblock($params[1]).'; ';
                    $out .= '$__path = '.cms_tp::compiler_simpleblock($params[2]).'; if ($__path instanceof cms_entry) $__path = $__path->effectivetypepath;';
                    $out .= '$__oname = '.cms_tp::compiler_simpleblock($params[3]).'; ';
                    $out .= '$ref = $__site->get_option($__oname, $__path);';
                    break;                    
                case 'download':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    if (count($params) == 4) {
                    	$out .= '$ref = cms_universe::combine_download_key($GLOBALS[\'_site\'], '.cms_tp::compiler_simpleblock($params[1]).','.cms_tp::compiler_simpleblock($params[2]).','.cms_tp::compiler_simpleblock($params[3]).'); ';
                    } else {
                    	$out .= '$ref = cms_universe::combine_download_key('.cms_tp::compiler_simpleblock($params[1]).','.cms_tp::compiler_simpleblock($params[2]).','.cms_tp::compiler_simpleblock($params[3]).','.cms_tp::compiler_simpleblock($params[4]).'); ';
                    }
                    break;
                case 'php':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref_f = '.cms_tp::compiler_simpleblock($params[1]).'; $ref_f = \'\'.$ref_f; $ref = $ref_f(';
                    $pps = array_slice($params,2);
                    foreach ($pps as $k=>$v) {
                        $pps[$k] = cms_tp::compiler_simpleblock($v);
                    }
                    $out .= join(',', $pps);
                    $out .= '); ';
                    break;                                  
                case 'varloop':
                    $li = $GLOBALS['___cnt_foreach']++;
                    $out .= '$elements_'.$li.'_ = '.cms_tp::compiler_simpleblock($params[1]).';';
                    $out .= '$elementsc_'.$li.'_ = count ($elements_'.$li.'_); ';
                    if (isset($params[2])) {
                        $out .= '$elementstabix_'.$li.'_ =& '.cms_tp::compiler_simpleblock($params[2]).';';
                    }
                    $out .= 'if (!is_array($elements_'.$li.'_) || ($elementsc_'.$li.'_ == 0)) { $elements_'.$li.'_ = array(); } ';
                    $out .= '$elementstabix_'.$li.'_ = -1; reset($elements_'.$li.'_); ';
                    $out .= 'while (list($k, $element_'.$li.'_) = each($elements_'.$li.'_)) :';
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = $element_'.$li.'_;';
                    $out .= '$elementstabix_'.$li.'_++; ';
                    break;
                case 'count':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).';';
                    $out .= '$ref = count('.cms_tp::compiler_simpleblock($params[1]).');';
                    break;
                case 'header':
                    $out .= '$GLOBALS[\'&&*(!)HD\'][] = '.cms_tp::compiler_simpleblock($params[0]).';';
                    break;
                case 'setcookie':
                    $out .= '$GLOBALS[\'&&*(!)CD\']['.cms_tp::compiler_simpleblock($params[0]).'] = '.cms_tp::compiler_simpleblock($params[1]).';';
                    break;
                case 'getcookie':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).';';
                    $out .= '$ref = $_COOKIE['.cms_tp::compiler_simpleblock($params[1]).'];';
                    break;    
                case 'setsession':
                    $out .= 'if (cms_config::$cc_client_session_on) { $cvn = '.cms_tp::compiler_simpleblock($params[0]).'; $rv = '.cms_tp::compiler_simpleblock($params[1]).';';
                    $out .= 'cms_universe::$puniverse->session()->$cvn = $rv;';
                    $out .= '$GLOBALS[\'&&*(!)SD\'][$cvn] = $rv; };;';
                    break;
                case 'getsession':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).';';
                    $out .= '$cvn = '.cms_tp::compiler_simpleblock($params[1]).';';
                    $out .= '$ref = cms_universe::$puniverse->session()->$cvn;';
                    break;                
                case 'autograph':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = cms_tp_autograph('.cms_tp::compiler_simpleblock($params[1]).');';
                    break;
                case 'pathcut':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = cms_path::leavepathpart('.cms_tp::compiler_simpleblock($params[1]).','.cms_tp::compiler_simpleblock($params[2]).');';
                    break;
                case 'pathlen':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = count(explode("/",'.cms_tp::compiler_simpleblock($params[1]).'));';
                    break;
                case 'match':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';                    
                    if (count($params)>3) {
                        $out .= '$ref2 =& '.cms_tp::compiler_simpleblock($params[3]).'; ';
                        $out .= '$ref = preg_match_all('.cms_tp::compiler_simpleblock($params[1]).','.cms_tp::compiler_simpleblock($params[2]).',$ref2,PREG_SET_ORDER); ';
                    } else {
                    	$out .= '$ref = preg_match('.cms_tp::compiler_simpleblock($params[1]).','.cms_tp::compiler_simpleblock($params[2]).'); ';
                    }
                case 'endloop':
                    $out .= '; endwhile;';
                    break;
                case 'repeat':
                    if (count($params)>0 && strlen($params[0])) {
                    	    $rid = md5(mt_rand(1234567890,9987654321));
                    	    $out .= '$ref'.$rid.' =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    	    $out .= 'while($ref'.$rid.'):';
                    } else {
                    	    $out .= 'while(true):';
                    }
                    break;
                case 'endrepeat':
                    $out .= '; endwhile;';
                    break;
                case 'break':
                    $out .= '; break '.$params[0].';';
                    break;
                case 'continue':
                    $out .= '; continue '.$params[0].';';
                    break;
                case 'push':
                case 'enqueue':
                    $out .= 'array_push('.cms_tp::compiler_simpleblock($params[0]).",".cms_tp::compiler_simpleblock($params[1]).");";
                    break;
                case 'pop':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = array_pop('.cms_tp::compiler_simpleblock($params[1]).");";
                    break;
                case 'debug':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= 'cms_tp::debug($ref, false);';
                    break;                
                case 'dequeue':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= '$ref = array_shift('.cms_tp::compiler_simpleblock($params[1]).");";
                    break;
                case 'aget':
                    $out .= '$obj =& '.cms_tp::compiler_simpleblock($params[1]).';';
                    $out .= '$adr = '.cms_tp::compiler_simpleblock($params[2]).';';
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= 'if(is_array($obj)) $ref = $obj[$adr]; if(is_object($obj)) $ref = $obj->$adr;';
                    break;
                case 'aset':
                    $out .= '$obj =& '.cms_tp::compiler_simpleblock($params[1]).';';
                    $out .= '$adr = '.cms_tp::compiler_simpleblock($params[2]).';';
                    $out .= '$ref = '.cms_tp::compiler_simpleblock($params[0]).'; ';
                    $out .= 'if(is_array($obj)) $obj[$adr] = $ref; if(is_object($obj)) $obj->$adr = $ref;';
                    break;                    
                case 'else':
                    $out .= '; else:';
                    break;                    
                case 'endif':
                    $out .= '; endif;';
                    break;
                case 'getcache':
                    $out .= '$ref =& '.cms_tp::compiler_simpleblock($params[0]).';';
                    $out .= '$con = '.cms_tp::compiler_simpleblock($params[1]).';';
                    $out .= '$cco = new cms_timedcache("tpr" . __FILE__); $cco->set_timeout('.cms_tp::compiler_simpleblock($params[2]).');';
                    $out .= '$ref = $cco->$con; if ($ref == cms_timedcache::$notfound || $cco->old) { $ref = ""; }';
                    break;                    
                case 'setcache':
                    $out .= '$con = '.cms_tp::compiler_simpleblock($params[0]).';';
                    $out .= '$cco = new cms_timedcache("tpr" . __FILE__); $cco->$con = '.cms_tp::compiler_simpleblock($params[1]).';';
                    break;
                case 'if':
                case 'ifnot':
                case 'elseif':
                case 'elseifnot':
                if(count($params)==1) {
                    $params[1] = '!=';
                    $params[2] = '#';
                }
                switch($params[1]) {
                    case '>':
                    case '<':
                    case '>=':
                    case '<=':
                    case '==':
                    case '!=':
                        $function = '';
                        $operator = $params[1];
                        break;
                    case '=':
                        $function = '';
                        $operator = '==';
                        break;
                    case '@>':
                    case '@<':
                    case '@<=':
                    case '@>=':
                    case '@==':
                    case '@!=':
                        $function = 'cms_tp_module_strlen';
                        $operator = substr($params[1],1);
                        break;
                    case '@=':
                        $function = 'cms_tp_module_strlen';
                        $operator = '==';
                        break;
                    default:
                        throw new RuntimeException("don't know how to handle operator ".$params[1]);
                }
                $rc = 'if ('.(substr($command,-3)=='not'?'!':'').'('.$function.'('.cms_tp::compiler_simpleblock($params[0]).')'.' '.$operator.' '.$function.'('.cms_tp::compiler_simpleblock($params[2]).')'.')):';
                if (substr($command,0,4) == 'else')
                	$rc = 'else'.$rc;
                $out .= '; '.$rc;                 
                break;
                default:
                    // no template...
                    throw new RuntimeException("don't know how to compile $command");
            }
            $out.=' ?>';
            return $out;
        }

        private static function compiler_simpleblock($block) {
            if ($block == '')
                return "''";
            if (substr($block,0,1) == '#')
                return '"'.addcslashes(substr($block,1),'$').'"';            
            return 'cms_tp_module_se_processor("'.$block.'")';
        }        

    }

?>
