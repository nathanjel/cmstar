<?php

// link replacement/hasher/compressor for js and css
function xxfh1($matches) {
	if (cms_config::$cc_use_asset_hash) {
		$j = stat($matches[3].'.'.$GLOBALS['!!xTYPE']);
		$j = crc32($j[9]);
	} else {
		$j = '';
	}
	if ($GLOBALS['!!xSITC']) {
		$d = $GLOBALS['!!xHTPS'] . $GLOBALS['!!xSITD'][$GLOBALS['!!xSITI']++ % $GLOBALS['!!xSITC']];
	} else {
		$d = '';
	}
	return 
	$matches[1].
	$GLOBALS['!!xSRC'].'='.
	$matches[2].
	$d.
	$GLOBALS['!!xLINK'].
	'__c/'.
	$j.
	'*'.
	$GLOBALS['!!xTYPE'].
	'*'.
	$GLOBALS['!!xLINK'].
	'*'.
	$GLOBALS['!!xCOMP'].
	'*'.
	$matches[3].'.'.
	$GLOBALS['!!xTYPE'].
	$matches[2].
	$matches[4].
	'>';
}

// link replacement for all other links
function xxfh2($matches) {
	if ($GLOBALS['!!xSITC'] && (0 == preg_match('/^<(a|form)/', $matches[1]))) {
		$d = $GLOBALS['!!xHTPS'] . $GLOBALS['!!xSITD'][$GLOBALS['!!xSITI']++ % $GLOBALS['!!xSITC']];
	} else {
		$d = '';
	}
	return 
	$matches[1].
	$matches[2].
	$d.
	$GLOBALS['!!xLINK'].
	$matches[3].
	$matches[2];
}

class cms_server {
	
	public $site;
	public $entry;
	public $path;
	public $lang;
	
	public $http;
	public $ifnonematch;
	public $compress;
	public $offset;
	public $etagcache;
	
	private $buff;
	private $meta;
	
	private $linklead;
	
	private static function erdrl($p) {
		if (isset($_ENV[$p])) {
			$_SERVER[$p] = $_ENV[$p]; return;
		}
		if (isset($_ENV['REDIRECT_'.$p])) {
			$_SERVER[$p] = $_ENV['REDIRECT_'.$p]; return;
		}
		if (isset($_ENV['REDIRECT_REDIRECT_'.$p])) {
			$_SERVER[$p] = $_ENV['REDIRECT_REDIRECT_'.$p]; return;
		}
	}
	
	private function inithead() {
		cms_server::erdrl('HTTP_IF_NONE_MATCH');
		cms_server::erdrl('HTTP_AUTHORIZATION');
		cms_server::erdrl('HTTP_PRAGMA');
		cms_server::erdrl('HTTP_CACHE_CONTROL');
		
		if (!isset($_SERVER['HTTP_IF_NONE_MATCH']) && (function_exists('apache_request_headers'))) {
			$a = apache_request_headers();
			@$this->ifnonematch = $a['If-None-Match']; // if not exists, no notice 
		} else {
			@$this->ifnonematch = $_SERVER['HTTP_IF_NONE_MATCH'];
		}
		
		if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			@list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
		}
		
		$this->http = $_SERVER['SERVER_PROTOCOL'];
		$this->offset = cms_config::$cc_default_page_expires_offset;
		$this->compress = cms_config::$cc_compress && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE);
		$sited = $this->site->dhm[$this->lang];
		$p = strpos($sited,'/');
		$this->linklead = substr($sited,$p);
	}
	
	public function __construct($site, $entry, $path, $lang) {
		$this->site = $site;
		$this->entry = $entry;
		$this->lang = $lang;
		$this->path = $path;
		$this->inithead();
		$this->buff = '';   
	}
	
	private function reroute($np) {
		$ne = $this->site->get($np, $this->lang);
		if (strpos($np,'*') !== FALSE) { // czy szukano z wildcardem
			$ne = @array_shift($ne);
		}
		if ($ne instanceof cms_entry) {
			$this->path = $np;
			$this->entry = $ne;
		}
	}
	
	private function route() {
		$dr = $this->entry->get_option('route');
		if ($dr) {
			// implicit request to re-route
			$this->reroute($dr);
		}
		if (strlen($this->entry->content) != 0)
			return true; 
		if ($this->entry->get_option('route_hold'))
			return true;
		// content not found and route was not held back
		$this->reroute($this->path.'/*');
	}
	
	private function initentry() {
		$this->etagcache = !$this->entry->get_option('disable_etag_cache');
		$j = $this->entry->get_option('expires_timeout');
		if ($j > 0 || $j < 0) {
			$this->offset = $j;
		}
	}
	
	private function process_template($tname) {
		if ($tname == '') {
			throw new InvalidArgumentException('no template configured for path '.$this->path);   
		}
		$this->meta = new cms_meta();
		$ct = new cms_tp($tname);
		$ct->setup($this->linklead, $this->site, $this->lang, $this->path, $this->entry);
		$this->meta->beforepage();            
		ob_start();
		$ct->run();
		$this->buff = ob_get_clean()."\n";
		$this->meta->afterpage();                        
	}
	
	private function update_references() {
		$GLOBALS['!!xSITD'] =& $this->site->resourcedomains;
		$GLOBALS['!!xSITC'] = count($GLOBALS['!!xSITD']);
		if (cms_config::$cc_assets_across_domains && ($GLOBALS['!!xSITC']>0)) {
			$GLOBALS['!!xSITI'] = 0;        							
			$GLOBALS['!!xHTPS'] = (@$_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
		} else {
			$GLOBALS['!!xSITC'] = 0;
		}
		$GLOBALS['!!xCOMP'] = $this->compress?'1':'0';
		$GLOBALS['!!xLINK'] = $this->linklead;
		
		// css
		$GLOBALS['!!xSRC'] = 'href';
		$GLOBALS['!!xTYPE'] = 'css';				
		$this->buff = preg_replace_callback('/(<link[^>]+)href=([\'"])(?![a-z]+:|#|\/)([^\'"]+).css\2([^>]*)>/ui',
			'xxfh1', $this->buff);
		
		// js
		$GLOBALS['!!xSRC'] = 'src';
		$GLOBALS['!!xTYPE'] = 'js';
		$this->buff = preg_replace_callback('/(<script[^>]+)src=([\'"])(?![a-z]+:|#|\/)([^\'"]+).js\2([^>]*)>/ui',
			'xxfh1', $this->buff);
		
		// local links rewriter
		$this->buff = preg_replace_callback(
			"/(<param\s+name=['\"]movie['\"]\s+value=|[\s:]url\(|<form[^>]+action=|\sdata=|\ssrc=|<a[^>]+href=)(['\"]?)(?![a-z]+:|#|\/)([^'\"]+)\\2/ui", 
			'xxfh2' /*'$1$2'.$this->linklead.'$3$2'*/, $this->buff);
	}
	
	private function output($fromcache = false) {
		if ($this->compress) {
			header("Content-encoding: gzip");
			$this->buff = gzencode($this->buff);
		}
		$len = strlen($this->buff);
		header('Content-length: '.$len);
		$this->meta->render($fromcache);
		header("Expires: ". gmdate ("D, d M Y H:i:s", time() + $this->offset) . " GMT");
		header("X-GZR-Exec-stats: ". serialize($GLOBALS['__QS__']));
		echo $this->buff;            
		flush();
	}        
	
	public function protection() {
		$ap = $this->entry->get_option('abuse_protection');		
		cms_websecure::abuse_protection($ap, $ap ? $this->entry->get_option('abuse_protection_check') : null);
	}
	
	public function serve() {
		$this->route();
		$this->initentry();
		$this->protection();
		$fpc = $this->entry->get_option('fpc') == 'true';
		$ttl = $this->entry->get_option('fpcttl');
		$cancel = array_filter(explode(',',$this->entry->get_option('fpccancel')));
		$sfpc = $fpc && ($_SERVER["REQUEST_METHOD"] == 'GET');
		$ufpc = $sfpc && @(strpos($_SERVER["HTTP_PRAGMA"].$_SERVER["HTTP_CACHE_CONTROL"], 'no-cache') === FALSE);
		foreach ($cancel as $x) {
			if (isset($_GET[$x])) {
				$sfpc = false; $ufpc = false;
				break;
			}
		}
		if ($sfpc) {
			$c = new cms_timedcache('cmsserver');
			$gd = '';   
			$fpcg = array_filter(explode(',',$this->entry->get_option('fpcget')));
			$fpcc = array_filter(explode(',',$this->entry->get_option('fpccookie')));
			foreach($fpcg as $k) $gd.='.'.$_GET[$k];
			foreach($fpcc as $k) $gd.='.'.$_COOKIE[$k];                
			$ec = $this->entry->site->id.'.'.$this->entry->pathl.'.'.$this->entry->lang.$gd;                
		}
		if ($ufpc) {
			if (!$ttl)
				$ttl = cms_config::$cc_fpc_default_timeout;
			$c->set_timeout($ttl);
			$p = $c->$ec;                
			if (!$c->old && ($p != cms_timedcache::$notfound)) {
				// znalazł coś i do tego nie stare!
				list($this->buff, $this->meta) = $p;
				// wysli zawartosc bufora (i reply ustawień sesji i cookie!)
				$this->output(true);
				session_write_close(); // to ma sens :)
				if ($c->old) {                        
					// jesli w buforze bylo starsze niz kiedyśtam to tworzymy nowe
					$tname = $this->entry->get_option('template');
					$this->process_template($tname);
					$this->update_references();
					$c->$ec = array($this->buff, $this->meta);
				}                    
				return;
			}
		} 		
		$tname = $this->entry->get_option('template');
		$this->process_template($tname);
		$this->update_references();
		if ($sfpc) 
			{ $c->$ec = array($this->buff, $this->meta); }		
		$this->output();            		
	} 	
}

?>