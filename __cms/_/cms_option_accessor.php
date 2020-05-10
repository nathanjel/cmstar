<?php

class cms_option_accessor {
	
	private $obj = null;
	
	public function __construct($main) {
		$this->obj = $main;	
	}
	
	public function __set($a, $b) {
		throw new RuntimeException('Cannot set an option');
	}
	
	public function __get($a) {
		if ($this->obj instanceof cms_site || $this->obj instanceof cms_entry) {
			return $this->obj->get_option($a);
		}
		throw new RuntimeException('Object null or has no options');
	}
}