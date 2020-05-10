<?php

class cms_path {

	public static function pathlt_conversion($pathe) {
		if (cms_config::$cc_path_cyrylic_translit) {
			$pathe = str_replace('А','A', $pathe);    $pathe = str_replace('а','a', $pathe);
			$pathe = str_replace('Б','B', $pathe);    $pathe = str_replace('б','b', $pathe);
			$pathe = str_replace('В','V', $pathe);    $pathe = str_replace('в','v', $pathe);
			$pathe = str_replace('Г','G', $pathe);    $pathe = str_replace('г','g', $pathe);
			$pathe = str_replace('Д','D', $pathe);    $pathe = str_replace('д','d', $pathe);
			$pathe = str_replace('Е','E', $pathe);    $pathe = str_replace('е','e', $pathe);
			$pathe = str_replace('Ё','Yo', $pathe);    $pathe = str_replace('ё','yo', $pathe);
			$pathe = str_replace('Ж','Zh', $pathe);    $pathe = str_replace('ж','zh', $pathe);
			$pathe = str_replace('З','Z', $pathe);    $pathe = str_replace('з','z', $pathe);
			$pathe = str_replace('И','I', $pathe);    $pathe = str_replace('и','i', $pathe);
			$pathe = str_replace('Й','J', $pathe);    $pathe = str_replace('й','j', $pathe);
			$pathe = str_replace('К','K', $pathe);    $pathe = str_replace('к','k', $pathe);
			$pathe = str_replace('Л','L', $pathe);    $pathe = str_replace('л','l', $pathe);
			$pathe = str_replace('М','M', $pathe);    $pathe = str_replace('м','m', $pathe);
			$pathe = str_replace('Н','N', $pathe);    $pathe = str_replace('н','n', $pathe);
			$pathe = str_replace('О','O', $pathe);    $pathe = str_replace('о','o', $pathe);
			$pathe = str_replace('П','P', $pathe);    $pathe = str_replace('п','p', $pathe);
			$pathe = str_replace('Р','R', $pathe);    $pathe = str_replace('р','r', $pathe);
			$pathe = str_replace('С','S', $pathe);    $pathe = str_replace('с','s', $pathe);
			$pathe = str_replace('Т','T', $pathe);    $pathe = str_replace('т','t', $pathe);
			$pathe = str_replace('У','U', $pathe);    $pathe = str_replace('у','u', $pathe);
			$pathe = str_replace('Ф','F', $pathe);    $pathe = str_replace('ф','f', $pathe);
			$pathe = str_replace('Х','H', $pathe);    $pathe = str_replace('х','h', $pathe);
			$pathe = str_replace('Ц','C', $pathe);    $pathe = str_replace('ц','c', $pathe);
			$pathe = str_replace('Ч','Ch', $pathe);    $pathe = str_replace('ч','ch', $pathe);
			$pathe = str_replace('Ш','Sh', $pathe);    $pathe = str_replace('ш','sh', $pathe);
			$pathe = str_replace('Щ','Shh', $pathe);    $pathe = str_replace('щ','shh', $pathe);
			$pathe = str_replace('Ъ','\'\'', $pathe);    $pathe = str_replace('ъ','\'\'', $pathe);
			$pathe = str_replace('Ы','Y', $pathe);    $pathe = str_replace('ы','y', $pathe);
			$pathe = str_replace('Ь','\'', $pathe);    $pathe = str_replace('ь','\'', $pathe);
			$pathe = str_replace('Э','Eh', $pathe);    $pathe = str_replace('э','eh', $pathe);
			$pathe = str_replace('Ю','Ju', $pathe);    $pathe = str_replace('ю','ju', $pathe);
			$pathe = str_replace('Я','Ja', $pathe);    $pathe = str_replace('я','ja', $pathe);
		}
		if (cms_config::$cc_path_pl_translit) {
			$pathe = str_replace('ę','e', $pathe);    $pathe = str_replace('Ę','E', $pathe);
			$pathe = str_replace('ó','o', $pathe);    $pathe = str_replace('Ó','O', $pathe);
			$pathe = str_replace('ą','a', $pathe);    $pathe = str_replace('Ą','A', $pathe);
			$pathe = str_replace('ś','s', $pathe);    $pathe = str_replace('Ś','S', $pathe);
			$pathe = str_replace('ł','l', $pathe);    $pathe = str_replace('Ł','L', $pathe);
			$pathe = str_replace('ż','z', $pathe);    $pathe = str_replace('Ż','Z', $pathe);
			$pathe = str_replace('ź','z', $pathe);    $pathe = str_replace('Ź','Z', $pathe);
			$pathe = str_replace('ć','c', $pathe);    $pathe = str_replace('Ć','C', $pathe);
			$pathe = str_replace('ń','n', $pathe);    $pathe = str_replace('Ń','N', $pathe);
		}
		if (cms_config::$cc_path_run_iconv) {
			$pathe = iconv("UTF-8", cms_config::$cc_path_run_iconv, $pathe);
		}		
		$pathe = preg_replace(cms_config::$cc_path_blanks_re, cms_config::$cc_path_space, $pathe); // spaces
		if (cms_config::$cc_path_urlencode) {
			$pathe = urlencode($pathe);
		} else {
			$pathe = cms_path::remove_non_url_chars($pathe); // remove all non-url characters that might be left over
		}
		if (cms_config::$cc_path_lowercase) {
			$pathe = strtolower($pathe);
		}
		return $pathe;
	}

	public static function remove_non_url_chars($c) {
		return preg_replace('/[^0-9A-Za-z\/_\-\.\:\;]/', '', $c); // remove all non-url characters that might be left over
	}

	public static function relative_path_alteration($path, $alteration) {
		if (substr($alteration,0,1) == "-") {
			$p = explode("/", $path);
			while(substr($alteration,0,1)=="-") {
				$alteration=substr($alteration,1);
				array_pop($p);
			}
			$alteration = join("/",$p).$alteration;
		}
		return $alteration;
	}
	
	public static function convert_lpath_to_pcre($path, $starstar = '@^.{0,}$@') {
		if ($path == '**')
		return $starstar;
		$path = str_replace(',', '|', $path);
		$path = str_replace('**', '.{0,}', $path);
		$path = str_replace('*', '[^/]+', $path);		
		$path = '@^'.$path.'$@';
		return $path;
	}

	public static function pathlen($path) {
		$sp = 0;
		$ef = 1;
		if (strlen($path)==0)
		return 0;
		while(true) {
			$sp = strpos($path, '/', $sp+1);
			if ($sp === FALSE) {
				return $ef;
			}
			$ef++;
		}
	}

	public static function pathcontains($haystack, $needle) {
		$n = strlen($needle);
		$h = strlen($haystack);
		$result = (
		($n>0)
		&&
		($n<=$h)
		&&
		(substr_compare($haystack, $needle, 0, $n)==0)
		&&
		(($n==$h)?true:(substr($haystack, $n,1)=='/'))
		);
		return $result;
	}

	public static function reducepatht($a1) {
		if (($b1 = mb_strlen($a1)) > 70) {
			$c1 = $b1 - 72;
			if ($c1 > 5) {
				$c2 = mb_strpos($a1, '/', $c1); $c2 = FALSE;
				if ($c2 !== FALSE) {
					$a1 = '...'.mb_substr($a1, $c2);
				} else {
					$a1 = mb_substr($a1,0,20).'...'.mb_substr($a1, -50);
				}
			}
		}
		return $a1;
	}

	public static function leavepathpart($path, $leavenelements) {
		$n = strlen($path);
		$ef = 1;
		$sp = 0;
		while(($ef <= $leavenelements) && ($sp+1 < $n)) {
			$sp = strpos($path, '/', $sp+1);
			if ($sp === FALSE) {
				return $path;
			}
			$ef++;
		}
		if ($sp == 0) {
			if ($leavenelements == 0) {
				return "";
			} else {
				return $path;
			}
		}
		return substr($path,0,$sp);
	}

	public static function wherediff($a, $b) {
		// return path length where first difference occurs
		// 0 if equal or different length
		$a1 = explode('/', $a);
		$b1 = explode('/', $b);
		$ml = max(count($a1),count($b1));
		for($j=0;$j<$ml;$j++) {
			if (@$a1[$j] != @$b1[$j]) {
				return $j+1;
			}
		}
		return 0;
	}
}

?>