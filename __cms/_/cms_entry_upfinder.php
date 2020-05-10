<?php

class cms_entry_upfinder {
	
	private $e;
	
	public function __construct($x) {
		$this->e = $x;
	}
	
	public function __get($x) {
		$obj = $this->e;
		$val = $obj->$x;
		while($val == '' && ($obj instanceof cms_entry)) {
			$obj = $obj->parent;
			$val = $obj->$x;
		}
		return $val;
	}
}