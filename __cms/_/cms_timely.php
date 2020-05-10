<?php

class cms_timely {
	
	protected $mycounter = 0;
	protected $extendsize = 50;

	protected function extend($x = 1) {
		$this->mycounter += $x;
		if ($this->mycounter > $this->extendsize) {
			cms_universe :: run_time_check();
			$this->mycounter = 0;
		}
	}

	protected function extendnow() {
		$this->extend($this->extendsize + 1);
	}
	
}