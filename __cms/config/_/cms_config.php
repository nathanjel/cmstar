<?php

    // all paths relative to root directory of websites

    class cms_config {
    	
    	public static $cc_version = "2.1.0";
    	
    	public static $cc_use_asset_hash = true;
    	public static $cc_use_etags = true;
    	public static $cc_use_asset_cache = true;
    	public static $cc_assets_across_domains = true;
        public static $cc_memcache_write_flags = 0;

        public static $cc_full_query_debug = false;
    	
    	// put the value defined below in JS file, to ensure it will not get processed by minifier
    	public static $cc_js_compress_stop = '/* DO_NOT_COMPRESS */'; // set to TRUE value to disable ALL javascript compression
    	public static $cc_ip_based_lang = 'geoip-local';
    	public static $cc_geoip_file_path = '__bundle/GeoIP.dat';

        public static $cc_cms_name = 'MASTER';
        public static $cc_cms_host = 'localhost'; // podstawowy host dla URL zasobów (np. dla mailingów)
        public static $cc_cms_path = '/__cms';
        
        public static $cc_cms_misc_qa_instance_name = 'QA_INSTANCE_NAME_FOR_AWS_CONTROL';

        public static $cc_cms_misc_service_key_key = 'key';
        public static $cc_cms_misc_service_key_value = 'service';
        
        public static $cc_cms_misc_e1code = 'abcdefghijklmnopQRSTUVWXYZ123456'; // 32 random characters

        public static $cc_cms_lock_file = "__cms/data/cms.lck"; // plik blokowania CMS
        public static $cc_cms_last_sitemaps = "__cms/data/sitemaps.touch"; // plik blokowania CMS
        public static $cc_cms_last_xmlrpc = "__cms/data/xmlrpc.touch"; // plik blokowania CMS
        public static $cc_cms_lock_tries = 10; // próbuj N razy
        public static $cc_cms_lock_wait = 300000; // jednostką są mikrosekundy (us)

        public static $cc_bpl_mailer_lock_file = "__cms/data/mailer.lck"; // plik blokowania mailera
        public static $cc_bpl_mailer_att_dir = "__cms/_cache/_mail/"; // katalog na zalaczniki
        public static $cc_bpl_mailer_lock_tries = 20;  // próbuj N razy
        public static $cc_bpl_mailer_lock_wait = 2000000; // jednostką są mikrosekundy (us)
        public static $cc_bpl_mailer_blocksize = 50;
        public static $cc_bpl_mailer_blocksperrun = 100;
        
        public static $cc_bpl_mailer_cron = true; // false gdy brak cron'a do wysyłki maili, wysyłaj natychmiastowo

        public static $cc_cms_lic_file = "__cms/data/licence.php";
        public static $cc_cms_xml_file = "__cms/data/master.xml";
        public static $cc_cms_xml_file_backup = "__cms/data/_master.xml.backup_";
        public static $cc_cms_opt_file = "__cms/config/_/options.xml";
        public static $cc_cms_cop_file = "__cms/config/_/commons.xml";
        public static $cc_cms_customer_config_file = "__cms/config/_/cms_config_customer.php";
        public static $cc_cms_system_config_file = "__cms/config/_/cms_config.php";

        public static $cc_cms_images_dir = "_images/";
        public static $cc_cms_extern_dir = "__cms/externals/";

        public static $cc_cms_imcache_folder = "__cms/_cache/i/";
        public static $cc_filecache_folder = "__cms/_cache/f/";
        public static $cc_filecache_disable = false;

        public static $cc_cms_tp_compiled_folder = "__cms/_cache/c/";
        public static $cc_cms_tp_compiled_prefix = "ct_";
        public static $cc_cms_tp_compiled_postfix = ".php";
        public static $cc_cms_tp_show_errors = false;

        public static $cc_client_session_on = true;

        public static $cc_no_picture = '__cms/template/_shared_/x.pl.png';
        public static $cc_fl_picture = '__cms/template/_shared_/fli.png';    
        
        public static $cc_path_cyrylic_translit = false;
        public static $cc_path_pl_translit = true;
        public static $cc_path_run_iconv = false; // "ASCII//TRANSLIT";
        public static $cc_path_lowercase = true;
        
        public static $cc_path_space = "-";
        public static $cc_path_blanks_re = "/\s+|^\.\.$/";
        
        public static $cc_path_urlencode = true;        
        public static $cc_path_urlencode_on_web_localize = false;
        
        public static $cc_compress = true;
        
        public static $cc_file_upload_and = 0777;
        public static $cc_file_upload_or = 0664;

        public static $cc_default_page_expires_offset = 3600; //jednostką są sekundy - domyslnie godzina
        public static $cc_default_asset_expires_offset = 15552000; //jednostką są sekundy - domyslnie pół roku

        public static $cc_cms_user_file = "__cms/data/access.dat";
        public static $cc_cms_user_star_file = "__cms/data/cmsstar.dat";
        public static $cc_cms_userman_show_password = false;

        public static $cc_cms_template_dir = "__cms/template/"; //cms templates
        public static $cc_cms_template = "nth";  //nth
        public static $cc_cms_template_login = "tlogin.html";  //nth
        public static $cc_cms_template_screen = "tmaster.html";  //nth
        
        public static $cc_cms_template_menu_cutout = true;
        public static $cc_cms_template_defftname = false; // domyślnie dodaj tabelkę na dane dodatkowe
        public static $cc_cms_delete_files = false;

        public static $cc_cms_site_templates_dir = "_templates/";        
        
        public static $cc_default_jpg_quality = 80; // dla auto-skalera na stronie, oraz jeśli nie podano jakości w opcjach
        
        public static $cc_admin_site_id = 9999;
        
        public static $cc_login_pass_reset_days = 90; // 180 dni, jednostką są dni
        public static $cc_login_timeout = 900; // 15 minut, jednostką są sekundy, musi być mniejsze niż session.gc_maxlifetime!!
        public static $cc_login_ip_control = true; // operacje możliwe tylko z IP na którym zalogowano
        public static $cc_http_call_timeout = 10; // 10 sekund, jednostką są sekundy
        
        public static $cc_ajax_advanced_relation_result_limit = 100;
        
        public static $cc_fpc_default_timeout = 60; // domyslnie jedna minuta

        public static $cc_js_date_format = 'Y/m/d';
        public static $cc_php_date_format = 'Y/m/d';
        public static $cc_lastmod_date_format = 'Y/m/d H:i';
        
        public static $cc_cms_cache_dirs_variety = 2;
        public static $cc_cms_cache_hashlen = 6;
        
        public static $cc_cms_cache_apc = false;
        public static $cc_apc_store_ttl = 86400; // in seconds, 24h
        
        public static $cc_timezone = 'Europe/Warsaw';
        
        public static $cc_log_logins = true;
        public static $cc_log_changes = true;
        
        public static $cc_auto_rollback_event_callback = null;
        
        public static $cc_encryption_enabled = true;
        public static $cc_encryption_key = 'opekQWE!@3497865cv..]w';    // 22 random characters
        public static $cc_encryption_alg = 'MCRYPT_DES';
        public static $cc_encryption_mode = 'MCRYPT_MODE_CFB';
        
        public static $cc_validator_max_error_msg = 5;
        public static $cc_use_value_keys_in_select_list = false;
        public static $cc_developer_mode = false;
        
        public static $cc_enable_xml_version_1_1 = true;
        
		public static $cc_default_language = 'pl';
		public static $cc_lang_files_dir = '__cms/i18n';
		
		public static $cc_store_empty_fields = false;
		
        public static $cc_error_reporting = (E_ALL & (~E_NOTICE) & (~E_DEPRECATED) & (~E_STRICT));
		public static $cc_display_errors = false;
		public static $cc_log_errors = false;
		public static $cc_cms_error_log_file = "__cms/errorlog.txt";		
        
        // QUERY_STRING
        // REQUEST_URI
        public static $cc_routing_method = 'REQUEST_URI';
        
        private static function prefix(&$val, $pf) {
            if (substr($val,0,1)!='/')
                $val = $pf.$val;
        }

        public static function prefix_config_paths($pf) {
            if ($pf == '')
                return;

			cms_config::prefix(cms_config::$cc_geoip_file_path,$pf);
			
            cms_config::prefix(cms_config::$cc_cms_lock_file,$pf);
            cms_config::prefix(cms_config::$cc_bpl_mailer_lock_file,$pf);
            cms_config::prefix(cms_config::$cc_bpl_mailer_att_dir,$pf);
            
			cms_config::prefix(cms_config::$cc_cms_last_sitemaps,$pf);
			cms_config::prefix(cms_config::$cc_cms_last_xmlrpc,$pf);
			
            cms_config::prefix(cms_config::$cc_cms_lic_file,$pf);
            cms_config::prefix(cms_config::$cc_cms_xml_file,$pf);
            cms_config::prefix(cms_config::$cc_cms_xml_file_backup,$pf);
            cms_config::prefix(cms_config::$cc_cms_opt_file,$pf);
            cms_config::prefix(cms_config::$cc_cms_cop_file,$pf);
            cms_config::prefix(cms_config::$cc_cms_customer_config_file,$pf);
            cms_config::prefix(cms_config::$cc_cms_system_config_file,$pf);
            
            cms_config::prefix(cms_config::$cc_cms_images_dir,$pf);
            cms_config::prefix(cms_config::$cc_cms_extern_dir,$pf);

            cms_config::prefix(cms_config::$cc_cms_imcache_folder,$pf);
            cms_config::prefix(cms_config::$cc_filecache_folder,$pf);
            cms_config::prefix(cms_config::$cc_cms_tp_compiled_folder ,$pf);
            cms_config::prefix(cms_config::$cc_no_picture,$pf);
            cms_config::prefix(cms_config::$cc_fl_picture,$pf);
            cms_config::prefix(cms_config::$cc_cms_user_file,$pf);
            cms_config::prefix(cms_config::$cc_cms_user_star_file,$pf);
            cms_config::prefix(cms_config::$cc_cms_template_dir,$pf);
            cms_config::prefix(cms_config::$cc_cms_site_templates_dir,$pf);
            cms_config::prefix(cms_config::$cc_lang_files_dir,$pf);
            
            cms_config::prefix(cms_config::$cc_cms_error_log_file,$pf);
        }

    }
    
?>