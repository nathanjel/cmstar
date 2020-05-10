<?php

	abstract class cms_lp {
		public static $_ = array();
		public static $j = array();
			
		public static function available_languages() {
			$al = array();			
			foreach (cms_universe::$puniverse->languages as $lang) {
				if (@cms_universe::$puniverse->langfiles[$lang[4]] || $lang[4]=='pl') {
					// default or polish (the core language)
					$al[$lang[4]] = $lang[3];
				}
			}
			return $al;
		}	
		
		public static function default_language_code() {
			return cms_config::$cc_default_language;
		}
		
		public static function get_session_language_code() {
			return @$_SESSION["_x__"]['_ses_st_lang'];
		}
		
		public static function set_language_code($lc) {
			$al = cms_lp::available_languages();
			if (!isset($al[$lc]))
				$lc = cms_lp::default_language_code();
			$_SESSION["_x__"]['_ses_st_lang'] = $lc;
			$lcn = "cms_lp_".$lc;
    		new $lcn(); 
		}
	}
	
	function __($x) {
		if (isset(cms_lp::$_[$x])) {
			return cms_lp::$_[$x];
		} else {
			return $x;
		}
	}
?>