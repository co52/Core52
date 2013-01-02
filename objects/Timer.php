<?php

class Timer {

	public $time;
	
	public function __construct($t = 0) {
		$this->time = $t;
	}
	
	public function profile($t = 0) {
		sleep($t ? $t : $this->time);
	}
	
	public function __wakeup() {
		if(!$this->time) $this->time = rand(60,200);
		$this->profile();
	}

}

