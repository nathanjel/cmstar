<?php

class cms_entry_related {
	protected $entry = null;
	protected $res = true;
	
	public function __construct($entry, $resx=true) {
		$this->entry = $entry;
		$this->res = $resx;
	}
	
	public function __get($field) {
		if ($this->res) {
			return array_values(cms_tp_rac($this->entry, $field));
		}
		return cms_tp_rac($this->entry, $field, false);
	}
}

?>