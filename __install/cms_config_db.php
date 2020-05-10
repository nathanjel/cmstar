<?php

    class cms_config_db {
        
        public static $cc_db_engine = 'mysql'; // mysql
        
        // mysql conn detail
        public static $cc_mysql_host = 'localhost';
        public static $cc_mysql_port = 3306;
        public static $cc_mysql_user = 'db_user';
        public static $cc_mysql_pass = 'db_pass';

        public static $cc_mysql_pconnect = true;
        
        // postgresql conn details
        public static $cc_post_conn_str = '';
        
        // sqlite3 file
        public static $cc_sqlite_file = '';
        
        // generic db setting (tbl prefix, database name)
        public static $cc_db = 'db_name';
        public static $cc_tb_prefix = 'cms_';
        public static $cc_db_limit = 10000;
        
        public static $cc_datafile_rights = 0666;
    }
    
?>