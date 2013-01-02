<?php

function svn_get_version($path, $required = FALSE, $blankval = 'unknown') {
	
	try {
		
		if(!is_dir($path)) {
			throw new InvalidArgumentException('$path must be a valid directory');
		}
		
		# normalize the dirname
		$path = realpath(rtrim($path, '/'));
		
		# read the .svn/entries file for the version number
		$f = fopen("$path/.svn/entries", 'r');
		for($i = 0; $i < 4; $i++) {
			# the 4th line has the revision number
			$line = fgets($f);
		}
		@fclose($f);
		
		return (int) trim($line);
		
	} catch(ErrorException $e) {
		
		if($required) {
			throw $e;
		} else {
			return $blankval;
		}
		
	}
	
}



