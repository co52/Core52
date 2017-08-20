<?php

/**
 * Shortcut for htmlspecialchars
 * Encode special characters to HTML entities to avoid XSS
 *
 * @param	string 	html to be encoded
 * @param   string  character set
 * @return 	string	text safe to send to the browser
 * @author 	Alex King
 **/

if (! function_exists('e')) {
	function e($html, $encoding = 'UTF-8') {

		# convert encoding and ensure consistency
		$html = mb_convert_encoding((string) $html, $encoding);

		return htmlspecialchars($html, ENT_QUOTES, $encoding);
	}
}

function format_date($dt = NULL, $fmt = 'n/d/y g:ia T', $hide_null_dates = FALSE) {
	if(strtolower($fmt) == 'rss') $fmt = 'D, d M Y H:i:s T';
	if(strtolower($fmt) == 'mysql') $fmt = 'Y-m-d H:i:s';
	
	if($dt === NULL && !$hide_null_dates) {
		return date($fmt);
	} elseif(is_numeric($dt)) {
		return date($fmt, $dt);
	} elseif(strtotime($dt) === FALSE || strlen($dt) == 0) {
		return '';
	} else {
		return date($fmt, strtotime($dt));
	}
}

function format_date_mysql($dt = NULL, $include_time = TRUE) {
	$fmt = ($include_time)? 'Y-m-d H:i:s' : 'Y-m-d';
	return format_date($dt, $fmt);
}

function format_zip($zip) {
	$zip = preg_replace('/[^0-9]/', '', $zip);
	return ($zip[8])? number_format($ph / 10000, 4, '-', '-') : $zip;
}

function format_phone($ph) {
	$ph = preg_replace('/[^0-9]/', '', $ph);
	return ($ph[0])? number_format($ph / 10000, 4, '-', '-') : '';
}

function percent($pct, $decimals = 0) {
	return number_format($pct, $decimals).'%';
}

function currency($amount, $decimals = 2, $symbol = '$') {
	return (strlen($amount) == 0)? '' : $symbol.number_format($amount, $decimals);
}

function format_time_interval($seconds, $precision = 'dhms') {
	
	$text = array();
	
	if(!is_numeric($seconds)) {
		$seconds = strtotime($seconds);
	}
	
	if($seconds <= 0) {
		return '';
	}
	
	$days = floor($seconds / 86400);
	$seconds = $seconds % 86400;
	$hours = floor($seconds / 3600);
	$seconds = $seconds % 3600;
	$minutes = floor($seconds / 60);
	$seconds = $seconds % 60;
	
	if($days > 0 && stripos($precision, 'd') !== FALSE) {
		$text[] = ($days == 1)? "1 day" : "$days days";
	}
	
	if($hours > 0 && stripos($precision, 'h') !== FALSE) {
		$text[] = ($hours == 1)? "1 hour" : "$hours hours";
	}
	
	if($minutes > 0 && stripos($precision, 'm') !== FALSE) {
		$text[] = ($minutes == 1)? "1 minute" : "$minutes minutes";
	}
	
	if($seconds > 0 && stripos($precision, 's') !== FALSE) {
		$text[] = ($seconds == 1)? "1 second" : "$seconds seconds";
	}
	
	return implode(' ', $text);
}

// Probably not an accurate name, since we go down to minutes if need be
// Credit: Michael Wales (http://codeigniter.com/forums/viewthread/84271/)
function timespan($seconds = 1) {
  if (!is_numeric($seconds)) {
    $seconds = strtotime($seconds);
  }
  $seconds = time() - $seconds;
  
  if ($seconds < 0) {
    return 'just now';
  }
  
  $days = floor($seconds / 86400);
  if ($days > 0) {
	if($days >= 30 && $days <= 45)
		return '1 month ago';
	elseif($days > 45) {
		$months = round($days / 30);
		if($months >= 12 && $months <= 17)
			return '1 year ago';
		elseif($months > 18) {
			$years = round($months / 12);
			return $years .' years ago';
		}
		return $months .' months ago';
	}
    return $days . ' days ago';
  }
  
  $hours = floor($seconds / 3600);
  if ($hours > 0) {
    if ($hours == 1) {
      return '1 hour ago';
    }
    return $hours . ' hours ago';
  }
  
  $minutes = floor($seconds / 60);
  if ($minutes > 52) {
    return '1 hour ago';
  } elseif ($minutes > 38) {
    return '45 minutes ago';
  } elseif ($minutes > 24) {
    return '30 minutes ago';
  } elseif ($minutes > 10) {
    return '15 minutes ago';
  } else {
    return 'just now';
  }
}

/**
 * Calculates a date lying a given number of months in the future of a given date.
 * The results resemble the logic used in MySQL where '2009-01-31 +1 month'
 * is '2009-02-28' rather than '2009-03-03' (like in PHP's strtotime).
 *
 * Taken from a user comment on http://us3.php.net/strtotime
 *
 * @author akniep
 * @since 2009-02-03
 * @param $base_time long, The timestamp used to calculate the returned value .
 * @param $months int, The number of months to jump to the future of the given $base_time.
 * @return long, The timestamp of the day $months months in the future of $base_time
 */
function future_month($base_time = null, $months = 1) {
	
    if(is_null($base_time)) {
        $base_time = time();
    } elseif(is_string($base_time)) {
    	$base_time = strtotime($base_time);
    }
   
    $x_months_to_the_future    = strtotime( "+" . $months . " months", $base_time );
   
    $month_before              = (int) date( "m", $base_time ) + 12 * (int) date( "Y", $base_time );
    $month_after               = (int) date( "m", $x_months_to_the_future ) + 12 * (int) date( "Y", $x_months_to_the_future );
   
    if ($month_after > $months + $month_before) {
        $x_months_to_the_future = strtotime( date("Ym01His", $x_months_to_the_future) . " -1 day" );
    }
    
    return $x_months_to_the_future;
}

/**
 * Incloses matched "needles" inside of a span
 *
 * @return string
 * @author Jake Smith
 **/
function highlight_match($needle, $haystack, $class = 'matched') {
	return str_ireplace($needle, '<span class="'. $class .'">'. $needle .'</span>', $haystack);
}

/**
 * Thanks to http://richardathome.wordpress.com/2006/03/28/php-function-to-return-the-number-of-days-between-two-dates/
 * for this function
 *
 * @param string $start
 * @param string $end
 * @return integer
 */
function days_elapsed($start, $end, $absolute = TRUE) {
	$start_ts = strtotime($start);
	$end_ts = strtotime($end);
	$diff = $end_ts - $start_ts;
	$days = (int) round($diff / 86400);
	return ($absolute)? abs($days) : $days;
}

/**
 * Converts a comma-separated string to an array
 * @param string $string
 * @return array
 */
function csv2array($string, $separator = ',', $delimiter = "'") {
	$array = explode($separator, $string);
	foreach($array as &$val) {
		$val = trim($val, $delimiter);
	}
	return $array;
}

/**
 * Converts a UTF-8 string with international characters to their closest (UTF-7) ASCII equivalents
 *
 * @param string $s
 * @param string $locale
 */
function format_ascii($s, $charset = 'UTF-8') {
	$ascii = iconv($charset, 'ASCII//TRANSLIT', $s);
	if($ascii === FALSE) {
		throw new Exception("iconv() error transliterating '$s' from $charset to ASCII");
	} else {
		return $ascii;
	}
}

/**
 * Format seconds as H:M:S
 *
 * @param	int		$seconds		seconds
 * @param	bool	$leading_zeroes	whether each element should be padded with zeroes (HH:MM:SS)
 * @return 	string	H:M:S or HH:MM:SS
 **/
function format_playlength($seconds, $leading_zeroes = false) {
	
	if($seconds == NULL || $seconds == '') {
		return '00:00:00';
	}
	if($seconds < 0 || !is_numeric($seconds)) {
		throw new InvalidArgumentException('format_playlength() expects a numeric argument, and must be greater than zero');
	}
	
	$hours = floor($seconds / 3600);
	$seconds = $seconds % 3600;
	$minutes = floor($seconds / 60);
	$seconds = $seconds % 60;

	// Pad with leading zereos
	if ($leading_zeroes) {
		$hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
		$minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
		$seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);
	}

	return "$hours:$minutes:$seconds";
}


/**
 * Convert and optionally number format a file size measurement to and from bytes, kilobytes, megabytes, and gigabytes
 *
 * @param mixed   $bytes
 * @param integer $decimals = 2  Number of desired decimals. If FALSE, no number formatting will be applied.
 * @param string  $to = 'KB'     Output unit of measure. Defaults to kilobytes.
 * @param string  $from = 'B'    Input unit of measure. Defaults to bytes.
 * @param integer $kb = 1024     Kilobytes size. Unit of measure to calculate true kilobytes (1024 bytes) or decimal kilobytes (1000 bytes).
 * @return mixed
 */
function format_bytes($bytes, $decimals = 2, $to = 'KB', $from = 'B', $kb = 1024) {
	
	if(!is_numeric($bytes)) {
		throw new InvalidArgumentException("Non-numeric \$bytes parameter: $bytes");
	}
	
	# pass 1: normalize to bytes
	switch(strtoupper($from)) {
		
		case 'G':
		case 'GB':
		case 'GIGABYTES':
			$bytes = $bytes * $kb; // intentional fall-through to MB
		
		case 'M':
		case 'MB':
		case 'MEGABYTES':
			$bytes = $bytes * $kb; // intentional fall-through to KB
			
		case 'K':
		case 'KB':
		case 'KILOBYTES':
			$bytes = $bytes * $kb;
		break;
		
		default:
		case 'B':
			# do nothing
	}
	
	# pass 2: convert to desired measurement
	switch(strtoupper($to)) {
		
		case 'G':
		case 'GB':
			$bytes = $bytes / $kb; // intentional fall-through to MB
		
		case 'M':
		case 'MB':
			$bytes = $bytes / $kb; // intentional fall-through to KB
			
		case 'K':
		case 'KB':
			$bytes = $bytes / $kb;
		break;
		
		default:
		case 'B':
			# do nothing
	}
	
	# format
	return ($decimals === FALSE)? $bytes : number_format($bytes, $decimals).$to;
}



/**
 * Automatically formats a file size using the largest possible units
 *
 * @param mixed   $bytes
 * @param integer $decimals = 2
 * @param string  $from = 'B'
 * @param integer $kb = 1024
 * @return mixed
 */
function format_bytes_auto($bytes, $decimals = 2, $from = 'B', $kb = 1024) {
	
	if(!is_numeric($bytes)) {
		throw new InvalidArgumentException("Non-numeric \$bytes parameter: $bytes");
	}
	
	# pass 1: normalize to bytes
	switch(strtoupper($from)) {
		
		case 'G':
		case 'GB':
		case 'GIGABYTES':
			$bytes = $bytes * $kb; // intentional fall-through to MB
		
		case 'M':
		case 'MB':
		case 'MEGABYTES':
			$bytes = $bytes * $kb; // intentional fall-through to KB
			
		case 'K':
		case 'KB':
		case 'KILOBYTES':
			$bytes = $bytes * $kb;
		break;
		
		default:
		case 'B':
			# do nothing
	}
	
	
	
	# determine the largest unit that can be used while maintaining a measurement > 1
	if($bytes >= $kb * $kb * $kb) { // gigabytes
		$bytes = $bytes / ($kb * $kb * $kb);
		$to = 'GB';
	} elseif($bytes >= $kb * $kb) { // megabytes
		$bytes = $bytes / ($kb * $kb);
		$to = 'MB';
	} elseif($bytes >= $kb) { // kilobytes
		$bytes = $bytes / $kb;
		$to = 'KB';
	} else { // bytes
		# do nothing
		$to = 'B';
	}
	
	# format
	return ($decimals === FALSE)? $bytes : number_format($bytes, $decimals).$to;
	
}


