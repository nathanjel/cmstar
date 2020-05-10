<?php

cms_config::$cc_cms_host = "example.com/";
cms_config::$cc_cms_name = "cmstar";
cms_config::$cc_cms_path = "/__cms";


cms_config::$cc_cms_cache_apc = function_exists('apc_store'); // (optimistic)
cms_config::$cc_routing_method = 'REQUEST_URI';
cms_config::$cc_developer_mode = true;
cms_config::$cc_cms_userman_show_password = true;

/*
 * below typical config settings for development environment
 * default config in cms_config is designed for production environment
 */
/*
cms_config::$cc_cms_lock_tries = 1;
cms_config::$cc_use_etags = false;
cms_config::$cc_use_asset_cache = false;
cms_config::$cc_compress = false;
*/

cms_config::$cc_path_urlencode = false;
cms_config::$cc_cms_template = "nth"; //nth
cms_config::$cc_cms_template_menu_cutout = false;
cms_config::$cc_login_timeout = 2400;

/*
 * some implementations might rely on this behaviour
 */
// cms_config::$cc_store_empty_fields = true;

/*
 * enable logging or display errors
 */
cms_config::$cc_cms_tp_show_errors = true;
cms_config::$cc_display_errors = true;
cms_config::$cc_log_errors = true;
cms_config::$cc_cms_error_log_file = "/opt/cmstar/__cms/errorlog.txt";

cms_config::$cc_login_pass_reset_days = 180;

?>