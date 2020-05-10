<?php
    class cms_filter {
        public $compare;
        public $val0;
        public $val1;
        public $not = false;
        public $null = false;

        public function sqlcond($fname) {
            switch(cms_config_db::$cc_db_engine) {
                case 'mysql':
                switch ($this->compare) {
                    case '#>':
                    case '#<':
                    case '#>=':
                    case '#<=':
                    case '#=':
                    case '#==':
                    case '#!=':
                    case '#<<':
                    case '#<<=':
                        $z = (float)($this->val1);
                        $b = (float)($this->val0);
                        break;
                    case '>':
                    case '<':
                    case '>=':
                    case '<=':
                    case '=':
                    case '==':
                    case '!=':
                    case '$>':
                    case '$<':
                    case '$>=':
                    case '$<=':
                    case '$=':
                    case '$==':
                    case '$!=':
                    case '&>':
                    case '&<':
                    case '&>=':
                    case '&<=':
                    case '&=':
                    case '&==':
                    case '&!=':
                    case '$<<':
                    case '&<<':
                    case '&<<=':
                    case '$<<=':
                    case '$?':
                    default:
                        $z = addslashes((string)($this->val1));
                        $b = addslashes((string)($this->val0));
                }
                switch ($this->compare) {
                    case '<':
                    case '#<':
                        return "( $fname < '$b' )";
                    case '>':
                    case '#>':
                        return "( $fname > '$b' )";
                    case '<=':
                    case '#<=':
                        return "( $fname <= '$b' )";
                    case '>=':
                    case '#>=':
                        return "( $fname >= '$b' )";
                    case '=':
                    case '==':
                    case '#=':
                    case '#==':
                        return "( $fname = '$b' )";
                    case '!=':          
                    case '#!=':              
                        return "( $fname != '$b' )";
                    case '$=':
                    case '$==':
                        return "( UCASE($fname) = UCASE('$b') )";
                    case '$>':
                        return "( UCASE($fname) > UCASE('$b') )";
                    case '$<':
                        return "( UCASE($fname) < UCASE('$b') )";
                    case '$!=':
                        return "( UCASE($fname) != UCASE('$b') )";
                    case '<<':
                        return "( ('$b' < $fname) AND ($fname < '$z') )";
                    case '#<<':
                        return "( ($b < $fname) AND ($fname < $z) )";
                    case '<<=':
                        return "( ('$b' <= $fname) AND ($fname <= '$z') )";
                    case '#<<=':
                        return "( ($b <= $fname) AND ($fname <= $z) )";
                    case '$<<':
                        return "( (UCASE('$b') < UCASE($fname)) AND (UCASE($fname) < UCASE('$z')) )";
                    case '$<<=':
                        return "( (UCASE('$b') <= UCASE($fname)) AND (UCASE($fname) <= UCASE('$z')) )";
                    case '$?':
                        return "( $fname LIKE '%$b%' )";
                    case '&=':
                    case '&==':
                    case '&>':
                    case '&<':
                    case '&!=':                        
                    case '&<<':                    
                    case '&<<=':
                        throw new InvalidArgumentException("operator {$this->compare} not supported with mysql db");
                    default:
                        return '1';
                };
                break;
            }
        }

	public function call($val) {
		if (!is_array($val))
			return $this->_call($val);
		foreach($val as $v)
			if ($this->_call($v)) return true;
		return false;
	}

        private function _call($value) {
            if ($value == '' || $value == null)
                return $this->null;
            if (is_array($value))
                        $a = $value[0];
                    else
                        $a = $value;
            switch ($this->compare) {
                case '$>':
                case '$<':
                case '$>=':
                case '$<=':
                case '$=':
                case '$==':
                case '$!=':
                case '&>':
                case '&<':
                case '&>=':
                case '&<=':
                case '&=':
                case '&==':
                case '&!=':
                case '$<<':
                case '&<<':
                case '&<<=':
                case '$<<=':
                case '$?':
                    $z = (string)($this->val1);
                    $a = (string)($a);
                    $b = (string)($this->val0);
                    break;
                case '#>':
                case '#<':
                case '#>=':
                case '#<=':
                case '#=':
                case '#==':
                case '#!=':
                case '#<<':
                case '#<<=':
                    $z = (float)($this->val1);
                    $a = (float)($a);
                    $b = (float)($this->val0);
                    break;
                case '%?':
                    $a = date("Y-m-d|d.m.Y|m/d/y", $a);
                default:
                    $z = $this->val1;
                    $b = $this->val0;
            }
            switch ($this->compare) {
                case '<':
                case '#<':
                    $ret =  $a < $b; break;
                case '>':
                case '#>':
                    $ret =  $a > $b; break;
                case '<=':
                case '#<=':
                    $ret =  $a <= $b; break;
                case '>=':
                case '#>=':
                    $ret =  $a >= $b; break;
                case '=':
                case '==':
                case '#=':
                case '#==':
                    $ret =  $a == $b; break;
                case '!=':          
                case '#!=':              
                    $ret =  $a != $b; break;
                case '&=':
                case '&==':
                    $ret =  strnatcasecmp($a,$b) == 0; break;
                case '&>':
                    $ret =  strnatcasecmp($a,$b) > 0; break;
                case '&<':
                    $ret =  strnatcasecmp($a,$b) < 0; break;
                case '&!=':
                    $ret =  strnatcasecmp($a,$b) != 0; break;
                case '$=':
                case '$==':
                    $ret =  strcasecmp($a,$b) == 0; break;
                case '$>':
                    $ret =  strcasecmp($a,$b) > 0; break;
                case '$<':
                    $ret =  strcasecmp($a,$b) < 0; break;
                case '$!=':
                    $ret =  strcasecmp($a,$b) != 0; break;
                case '<<':
                case '#<<':
                    $ret = ($b < $a) && ($a < $z); break;
                case '<<=':
                case '#<<=':
                    $ret = ($b <= $a) && ($a <= $z); break;
                case '$<<':
                    $ret = (strcasecmp($b , $a) < 0) && (strcasecmp($a , $z) < 0 ); break;
                case '$<<=':
                    $ret = (strcasecmp($b , $a) <= 0) && (strcasecmp($a , $z) <= 0 ); break;
                case '&<<':
                    $ret = (strnatcasecmp($b , $a) < 0) && (strnatcasecmp($a , $z) < 0 ); break;
                case '&<<=':
                    $ret = (strnatcasecmp($b , $a) <= 0) && (strnatcasecmp($a , $z) <= 0 ); break;
                case '$?':
                case '%?':
                    $ret = (stripos($a, $b) !== false); break;
                default:
                    $ret =  true;
            }
            $ret = $ret xor $this->not;
            return $ret;
        }

    }
?>
