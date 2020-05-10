<?php
    class cms_relation_accessor {

        public static $leftside = 1;
        public static $rightside = 2;

        public static $uniq0w = 0;
        public static $uniq1w = 1;

        public $gsite;
        public $gpath;
        public $glang;

        public $code;
        public $mside;
        public $rmax;
        public $uniq;

        public $ssite;
        public $spat;
        public $spatre;
        public $spatdb;
        public $slang;
        public $stype;
        
        protected $flt = null;

        public function __construct($gsite, $gpath, $glang, $code, $side, $rmax, $uniq, $ssite, $spat, $slang, $stype) {
            if ($gsite->dbe != true) {
                throw new InvalidArgumentException('non-dbe site ['.$gsite->name.'] cannot host relation '.$code);
            }
            $this->gsite = $gsite;
            $this->gpath = $gpath;
            $this->glang = $glang;

            $this->code = $code;
            $this->mside = $side;
            if ($this->mside != cms_relation_accessor::$leftside && $this->mside != cms_relation_accessor::$rightside)
                throw new InvalidArgumentException('relation side for accessor defined wrong in relation '.$code);
            $this->rmax = $rmax;
            $this->uniq = $uniq;
            if ($this->uniq != cms_relation_accessor::$uniq0w && $this->uniq != cms_relation_accessor::$uniq1w)
                throw new InvalidArgumentException('relation uniqueness for accessor defined wrong in relation '.$code);
            $this->ssite = $ssite;
            $this->spat = cms_path::relative_path_alteration($gpath, $spat);
            $this->spatre = cms_path::convert_lpath_to_pcre($this->spat);
            if ($slang == '') 
                $slang = $ssite->allowed_langs();
            if (!is_array($slang))
                $slang = array($slang);
            $this->slang = $slang;
            $this->stype = $stype;
            $this->spatdb = cms_universe::$puniverse->db()->convert_lpath_to_dbre($this->spat);
        }

        public function related() {
            $para = array($this->code, $this->gsite->id, $this->gpath, $this->glang);
            $res = cms_universe::$puniverse->db()->perform(
            $this->mside==cms_relation_accessor::$leftside?dbq_rel_bl:dbq_rel_br,
            $para);
            $rf = array();
            $csid = $this->ssite->id;
            foreach ($res as $ro) {
                if ($ro[0] != $csid) // site id must match
                    continue;
                $j = new cms_entry_data();
                $j->site = $this->ssite;
                $j->pathl = $ro[1];
                $j->lang = $ro[2];
                if ($ro[7] == 1) { // not found a representative in db, look in xml
                    // $m = $j->site->get_entrynode_pathl($j->pathl, $j->lang);
                    // if ($m==null)
                    //     continue;
                    // $j->published = $m->getAttribute(cms_entry_xml::$PUB_LANG_ATTR.$j->lang);
                    // $j->typepath =$m->getAttribute(cms_entry_xml::$TYPE_ATTR);                        
                    // for($cnode = $m->firstChild; $cnode != NULL; $cnode = $cnode->nextSibling) {
                    //     if (($cnode->nodeName == cms_entry_xml::$NAME_TAG) && 
                    //     		($cnode->getAttribute(cms_entry_xml::$L_ATTR) == $j->lang)) {
                    //        $j->name = trim($cnode->firstChild->wholeText);
                    //        break;
                    //     }                
                    // }
                } else { // all data from DB
                    $j->name = $ro[3];
                    $j->typepath = $ro[6]?$ro[6]:$ro[1];
                    $j->published = $ro[8];
                }
                $rf[$j->site->id.':'.$j->pathl.':'.$j->lang] = $j;
            }
            return $rf;
        }

        public function all_selectable() {
            // select all that match filter 
            if ($this->uniq == cms_relation_accessor::$uniq0w)
                return $this->all_possible();
            // unless they are related already (if 1w)
            return array_udiff($this->all_possible(), $this->related(), array('cms_relation_accessor','compare_diff'));
        }

        public static function compare_diff($a, $b) {
            $p = ($a->site->id).$a->pathl.$a->lang;
            $q = ($b->site->id).$b->pathl.$b->lang;
            return strcmp($p, $q);
        }

        public function all_possible() {
            $x = $this->ssite->get($this->spat, $this->slang, false, true, false, $this->flt, null, $this->stype);            
            if ($x instanceof cms_entry_data)
                return array($this->ssite->id.':'.$x->pathl.':'.$this->slang => $x);
            return $x;
        }
        
        public function add_filter($fn, $fil) {
            if ($fil instanceof cms_filter) {
                if ($this->flt == null)
                    $this->flt = array();
                $this->flt[$fn] = $fil;
            }            
        }

        public function add_relation($esite, $epath, $elang, $ignoreorder = -1) {
            if ($esite instanceof cms_site)
                $esite = $esite->id;
            if ($ignoreorder == -1) {
                $j = 0;
            } elseif($ignoreorder>=1) {
                $j = $ignoreorder;
            } else {
                $para = array($this->code, $this->gsite->id, $this->gpath, $this->glang);
                $res = cms_universe::$puniverse->db()->perform(
                $this->mside==cms_relation_accessor::$leftside?dbq_relation_bl_nextid:dbq_relation_br_nextid,
                $para);
                $j = $res[0][0];
            }
            // validate relation entry!
            if ( $esite != $this->ssite->id || !in_array($elang, $this->slang) || !preg_match($this->spatre, $epath))
                throw new RuntimeException("tried to insert a value into a relation, that is outside it's scope");
            $para2 = $this->mside==cms_relation_accessor::$leftside ?
                array($this->code, $j, $this->gsite->id, $esite, $this->gpath, $epath, $this->glang, $elang)
                :
                array($this->code, $j, $esite, $this->gsite->id, $epath, $this->gpath, $elang, $this->glang);
            $res = cms_universe::$puniverse->db()->perform(
            dbq_insert_relation,
            $para2);            
        }

        public function remove_relation($esite, $epath, $elang) {
            if ($esite instanceof cms_site)
                $esite = $esite->id;
            $para = array($this->code, $this->gsite->id, $this->gpath, $this->glang, $esite, $epath, $elang);
            $res = cms_universe::$puniverse->db()->perform(
            $this->mside==cms_relation_accessor::$leftside?dbq_delrelation_bl:dbq_delrelation_br,
            $para);            
        }

        public function cleanup_relation() {
            $para = array($this->code, $this->gsite->id, $this->gpath, $this->glang);
            $res = cms_universe::$puniverse->db()->perform(
            $this->mside==cms_relation_accessor::$leftside?dbq_delrelation_blall:dbq_delrelation_brall,
            $para);
        }

    }
    
?>