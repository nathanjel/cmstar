<?php
    // utf-8 aśłóćżńŁŚĆÓŻ
    // to musi być dobry kod...
    // choć narazie tylko mysql'a obrobi
    
    function cms_db_generic_query_param_helper($x) {
        list($a,$b) = each($GLOBALS['__WPC_']);
        if ($x[2] == '@')
            return $x[1].floatval($b);
        return $x[1]."'".addcslashes($b,"'\\")."'";
    }    

    interface cms_db {
        public function get_con();
        public function is_connected();
        public function perform($query, $params = null, $dbg = false, $nosplit = false);
        public function fullinsert($table,$rows,$escape = true);
        public function insert($table,$fields,$rows,$escape = true);
        public function pass($pass, $alg = 'zz0');
        public function last_ai();
        public function tcount($table, $field='*');
        public function begin();
        public function commit();
        public function rollback();
        public function convert_lpath_to_dbre($lpath);
        public function convert_lpath_to_dblike($lpath);
    }    
    
?>