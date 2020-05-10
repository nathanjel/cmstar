<?php

    class cms_entry_data {

        public $_site_reference;

        public $pathl;
        public $typepath;
        public $lang;
        public $sortid;
        public $slugdata;

        protected $name;
        public $_enc;

        public $published = 0;

        public $lname;
        public $pathlt;

        public $graphs = null;
        public $pictures = null;
        public $fields = null;

        public $content;

        public $changelog;
        public $dbe = false;

        public $fulldata = false;
        private $level = -1;
		
		protected function remap_move($target) {
			$target->_site_reference = $this->_site_reference;
			$target->slugdata = $this->slugdata;
			$target->name = $this->name;
			$target->graphs = $this->graphs;
			$target->pictures = $this->pictures;
			$target->fields = $this->fields;
			$target->content = $this->content;
			$target->lname = $this->lname;
			$target->published = $this->published;
		}

        public function &__get($a) {
            switch ($a) {
            	case 'level': // left for "LEGACY" reasons only
            		if ($this->level == -1) {
            			$this->level = cms_path::pathlen($this->pathl) - 1; 
            		}
            		return $this->level;
                case 'site':
                    return $this->_site_reference;
                case 'lang':
                    return $this->lang;
                case 'text':
                    return $this->content;
                case 'slug':
                    return $this->slugdata ? $this->slugdata : $this->pathlt;
                case 'name':
                    return $this->name;
                case 'type':
                    return $this->typepath;
                case 'effectivetypepath':
                	return ($this->typepath?$this->typepath:$this->pathl);
        		case 'allfieldstable':
        			return $this->all_fields_table();                    
                case 'childrenlist':
                    return $this->_site_reference->get($this->pathl.'/*', $this->lang, false);
                case 'children':
                    return $this->_site_reference->get($this->pathl.'/*', $this->lang);
                case 'parent':
                    return $this->_site_reference->get(cms_path::leavepathpart($this->pathl, $this->__get('level')), $this->lang);
                case 'options':
                	return new cms_option_accessor($this);
                case 'related':
                    return new cms_entry_related($this);
                case 'relation':
                    return new cms_entry_related($this,false);
                case 'findupwards':
                	return new cms_entry_upfinder($this);
                case 'validate':
                	$lv = new cms_eev($this);
                	return $lv->validate();
                default:
                    return @$this->fields[$a];
            }
        }
        
        public function all_fields_table() {
       		return $this->fields;       		 
        }
        
        public function all_fields_concated() {
           	return join(' ',$this->fields);
        }
        
        public function __set($a, $b) {
            switch ($a) {
                case 'site':
                case '_site_reference':
                    return $this->_site_reference = $b;
                case 'text':
                    return $this->content = $b;
                case 'slug':
                    return $this->slugdata = $b;
                case 'name':
                    $this->lname = cms_path::pathlt_conversion($b);
                    return $this->name = $b;                          
                case 'type':
                    return $this->typepath = $b;
                case 'level': // cannot really set level!
                	return false;           
                default:
                    return $this->fields[$a] = $b;
            }
        }

    }

?>