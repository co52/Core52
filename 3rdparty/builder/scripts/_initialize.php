<?php

if(PHP_SAPI != 'cli') {
	die("You should not access this file.");
}

echo "\n";

# Include helpers
include_once('../../core/initialize.php');


set_time_limit(0);
