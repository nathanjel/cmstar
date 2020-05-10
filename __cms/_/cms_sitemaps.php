<?php
class cms_sitemaps extends cms_timely {

	private $site;

	public $notify_sitemaps_urls;
	public $ping_list;
	public $ping_servers_list;

	public function __construct($site) {
		$this->site = $site;
		$urllist = $this->site->options->xmlrpc_ping_url;
		$this->ping_servers_list = array_filter(explode(';', $urllist));
	}

	private function site_lang_exists($lang) {
		if ($lang < 1)
			return false;
		$x = $this->site->get($this->site->route, $lang);
		return ($x instanceof cms_entry_data && $x->published);
	}

	public function create_all_sitemaps($folder) {
		$filemask = $this->site->options->sitemap_file_pattern;
		foreach ($this->site->dhm as $lang => $url) {
			if (!$this->site_lang_exists($lang))
				continue;
			$filename = str_replace('{lang}', $lang, $filemask);
			$this->extendnow();
			$rx = $this->create_sitemap($folder . $filename, $lang);
			if (!$rx)
				return false;
		}
		return true;
	}

	public function create_sitemap($filename, $lang) {
		$mil = $this->site->options->sitemap_include_paths;
		if (!$mil) {
			$mil = "**";
		}
		$filters = array ();
		$filter = new cms_filter();
		$filter->val0 = 1;
		$filter->compare = '#=';
		$filters['published'] = $filter;
		$this->extendnow();
		$menu = $this->site->get($mil, $lang, false, true, true, $filters, $this->site->options->sitemap_exclude_paths, null, true, false);
		$this->extendnow();
		$urldata = '';
		$urlstart = 'http://' . $this->site->dhm[$lang];
		foreach ($menu as $item) {
			$url = $urlstart . $item->slug;
			$urldata .= '<url><loc>' . htmlentities($url, 20, 'UTF-8') . '</loc></url>';
			// 20 goes for ENT_XML1 | ENT_IGNORE
		}
		$res = false;
		if ($this->site->options->sitemap_gz) {
			@ unlink($filename);
			$sf = @ gzopen($filename, 'wb');
			// no to jedziemy
			if ($sf) {
				$res = gzwrite($sf, '<?xml version="1.0" encoding="UTF-8"?>') && 
					gzwrite($sf, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">') && 
					gzwrite($sf, $urldata) &&
					// i końcówka
					gzwrite($sf, '</urlset>');
			}
			@ gzclose($sf);
		} else {
			$sf = @ fopen($filename, 'wb+');
			// no to jedziemy
			if ($sf) {
				$res = fwrite($sf, '<?xml version="1.0" encoding="UTF-8"?>') && 
					fwrite($sf, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">') && 
					fwrite($sf, $urldata) &&
					// i końcówka
					fwrite($sf, '</urlset>');
			}
			@ fclose($sf);
		}
		return $res;
	}

	public function create_notify_urls() {
		$this->notify_sitemaps_urls = array ();
		$list = $this->site->options->sitemap_ping_url;
		$list = array_filter(explode(';', $list));
		foreach ($this->site->dhm as $lang => $url) {
			// should we consider the language ?
			if ($this->site_lang_exists($lang)) {
				$fn = 'http://' . $url . str_replace('{lang}', $lang, $this->site->options->sitemap_file_pattern);
				foreach ($list as $u) {
					$this->notify_sitemaps_urls[] = str_replace('{url}', urlencode($fn), $u);
				}
			}
		}
	}

	public function create_ping_list($lang = -1) {
		$tl = array ();
		foreach ($this->site->dhm as $lg => $url) {
			if ($lang != -1 && $lg != $lang)
				continue;
			if ($this->site_lang_exists($lg)) {
				$ne = $this->site->get($this->site->options->xmlrpc_ping_name_entry, $lg);
				$fn = $this->site->options->xmlrpc_ping_name_field;
				$name = $ne-> $fn;
				$sname = str_replace('{name}', $name, $this->site->options->xmlrpc_ping_name_pattern);
				$surl = 'http://' . $url;
			} else
				continue;
			foreach ($this->ping_servers_list as $purl) {
				$this->ping_list[] = array (
					$purl,
					$surl,
					$sname
				);
			}
		}
	}

	public function notify_url($url) {
		$this->extendnow();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, cms_config :: $cc_http_call_timeout);
		$res = curl_exec($ch);
		curl_close($ch);
		if ($res === FALSE) {
			return false;
		}
		return true;
	}

	public function weblog_update_ping($target, $myurl, $myname) {
		$this->extendnow();
		$weblog_name = htmlentities($myname, ENT_XML1, 'UTF-8');
		$weblog_url = htmlentities($myurl, ENT_XML1, 'UTF-8');
		$request = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
		<methodCall>
		<methodName>weblogUpdates.ping</methodName>
		<params>
		 <param>
		  <value>
		   <string>$weblog_name</string>
		  </value>
		 </param>
		 <param>
		  <value>
		   <string>$weblog_url</string>
		  </value>
		 </param>
		</params>
		</methodCall>";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $target);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, trim($request));
		$result = curl_exec($ch);
		$doc = new DOMDocument();
		if (($result !== FALSE) && @$doc->loadXML($result) && (strpos($result, '<methodResponse>') !== FALSE) && (strpos($result, '<fault>') === FALSE)) {
			$result = true;
		} else {
			$result = false;
		}
		curl_close($ch);
		return $result;
	}

}