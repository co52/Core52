<?php


/**
 * JSD
 *
 * Jake got tired of typing die(var_dump($var));
 * The End.
 *
 * @return void
 * @author "Jake A. Smith" <jake@companyfiftytwo.com>
 **/
function jsd(&$var) {
	die(var_dump($var));
}

function print_ar($obj, $method = 'print_r') {
	if(is_callable($method)) {
		print "<pre>";
		call_user_func($method, $obj);
		print "</pre>";
	} else {
		throw new InvalidArgumentException("$method is not callable");
	}
}

function defineThese($array = array()) {
	foreach ($array as $name => $value) {
		define($name, $value);
	}
}


function valid_email($email) {
	return preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email);
}


/**
 * Borrowed from Solar (http://svn.solarphp.com/core/trunk/Solar/Filter/ValidateUri.php)
 *
 * @param string $url
 * @return boolean
 */
function valid_url($url) {
	
    // first, make sure there are no invalid chars, list from ext/filter
    $other = "$-_.+"        // safe
           . "!*'(),"       // extra
           . "{}|\\^~[]`"   // national
           . "<>#%\""       // punctuation
           . ";/?:@&=";     // reserved
        
    $valid = 'a-zA-Z0-9' . preg_quote($other, '/');
    $clean = preg_replace("/[^$valid]/", '', $url);
    if ($url != $clean) {
        return false;
    }
        
    // now make sure it parses as a URL with scheme and host
    $result = @parse_url($url);
    if (empty($result['scheme']) || trim($result['scheme']) == '' ||
        empty($result['host'])   || trim($result['host']) == '') {
        // need a scheme and host
        return false;
    } else {
        // looks ok
        return true;
    }
}


function match_type($regex, $var) {
    preg_match($regex, $var, $matches);
    return count($matches) == 0 ? false : true;
}


function time_since($original) {
    // array of time period chunks
    $chunks = array(
        array(60 * 60 * 24 * 365 , 'year'),
        array(60 * 60 * 24 * 30 , 'month'),
        array(60 * 60 * 24 * 7, 'week'),
        array(60 * 60 * 24 , 'day'),
        array(60 * 60 , 'hour'),
        array(60 , 'minute'),
    );

    $today = time(); /* Current unix time  */
    $since = $today - $original;

        if($since > 604800) {
                $print = date("M jS", $original);

                if($since > 31536000) {
                                $print .= ", " . date("Y", $original);
                        }

                return $print;

        }

    // $j saves performing the count function each time around the loop
    for ($i = 0, $j = count($chunks); $i < $j; $i++) {

        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];

        // finding the biggest chunk (if the chunk fits, break)
        if (($count = floor($since / $seconds)) != 0) {
            // DEBUG print "<!-- It's $name -->\n";
            break;
        }
    }

    $print = ($count == 1) ? '1 '.$name : "$count {$name}s";
	$print = $count == 0 ? 'less than a minute' : $print;

    return $print . " ago";

}


function parse_query_string($url) {
	$query = explode('&', parse_url($url, PHP_URL_QUERY));
	$array = array();
	foreach($query as $q) {
		list($arg, $val) = explode('=', $q);
		$array[$arg] = $val;
	}
	return $array;
}

function explode_query_string($str) {
	return parse_query_string($str);
}

function implode_query_string(array $query) {
	$str = array();
	foreach($query as $arg => $val) {
		$str[] = "$arg=$val";
	}
	return implode('&', $str);
}



function if_blank($str, $else = 'n/a') {
	return is_blank($str) ? $else : $str;
}


function is_blank($str) {
	if (is_null($str) || trim($str)=='' || !isset($str)) return true;
	else return false;
}

# E.g. uses:
#		redate('2009-01-02');
#		redate('2009-01-02', 'M d, Y');
#		redate('2009-01-02', 'm/d/Y', '--unknown date--');
function redate($date_str, $format = 'F d, Y', $invalid_str = 'n/a', $accept_1969 = false) {
	$ts = is_blank($date_str) ? false : strtotime($date_str);
	return ($ts && ($ts!=-64800 || $accept_1969)) ? date($format, $ts) : $invalid_str;
}

function date_range($s_date_str, $e_date_str, $format = 'F d, Y', $invalid_str = 'n/a') {

	$s_ts = is_blank($s_date_str) ? false : strtotime($s_date_str);
	$e_ts = is_blank($e_date_str) ? false : strtotime($e_date_str);
	if ($format == 'F d, Y') {
		$s_yr = date('Y', $s_ts);
		$e_yr = date('Y', $e_ts);
		$s_mo = date('F', $s_ts);
		$e_mo = date('F', $e_ts);
		$s_day = date('d', $s_ts);
		$e_day = date('d', $e_ts);
	}
	
	if (is_blank($e_date_str) || ($s_ts && $s_ts==$e_ts)) return redate($s_date_str, $format);
	if (!$s_ts || !$e_ts) return $invalid_str;
	
	switch ($format) {
		# Nov 04, 2009 to Dec 15, 2009:  Nov 04 - Dec 15, 2009
		# Dec 04, 2009 to Dec 15, 2009:  Dec 04 - 15, 2009
		# Dec 04, 2009 @1PM to Dec 04, 2009 @4PM:  Dec 04, 2009, 1PM - 4PM
		case 'F d, Y':
			if ($s_yr != $e_yr)
				return redate($s_date_str, $format).' &ndash; '.redate($e_date_str, $format);
			else {
				if ($s_mo != $e_mo)
					return redate($s_date_str, 'F d').' &ndash; '.redate($e_date_str, 'F d, Y');
				else {
					if ($s_day != $e_day)
						return redate($s_date_str, 'F d').' &ndash; '.redate($e_date_str, 'd, Y');
					else
						return redate($s_date_str, 'F d, Y, ga').' &ndash; '.redate($e_date_str, 'ga');
				}
			}
			break;
		
		default:
			return redate($s_date_str, $format).' &ndash; '.redate($e_date_str, $format);
			break;
	}
}

function inflect_word($word, $number) {
	
	# thanks to 'joelgreen' at http://www.webmasterworld.com/php/3281240.htm
	$plural_rules = array(
		'/(x|ch|ss|sh)$/' => '\1es',			# search, switch, fix, box, process, address
		'/series$/' => '\1series',
		'/([^aeiouy]|qu)ies$/' => '\1y',
		'/([^aeiouy]|qu)y$/' => '\1ies',		# query, ability, agency
		'/(?:([^f])fe|([lr])f)$/' => '\1\2ves',	# half, safe, wife
		'/sis$/' => 'ses',						# basis, diagnosis
		'/([ti])um$/' => '\1a',					# datum, medium
		'/person$/' => 'people',				# person, salesperson
		'/man$/' => 'men',						# man, woman, spokesman
		'/child$/' => 'children',				# child
		'/(.*)status$/' => '\1statuses',
		'/s$/' => 's',							# no change (compatibility)
		'/$/' => 's'
	);
	
	$find = array_keys($plural_rules);
	$replace = array_values($plural_rules);

	return ($number <> 1)? preg_replace($find, $replace, $word) : $word;
	}

/**
 * The letter l (lowercase L) and the number 1
 * have been removed, as they can be mistaken
 * for each other.
 */

function generate_pass() {

    $chars = "abcdefghijkmnopqrstuvwxyz023456789";
    srand((double)microtime()*1000000);

    $i = 0;
    $pass = '' ;

    while ($i <= 7) {
        $num = rand() % (strlen($chars) - 1);
        $tmp = substr($chars, $num, 1);
        $pass = $pass . $tmp;

        $i++;
    }

    return $pass;
}

function ifnull() {
	$args = func_get_args();
	foreach($args as $k => $v) {
		if(!is_null($v)) return $v;
	}
	return NULL;
}

function ifempty() {
	$args = func_get_args();
	foreach($args as $k => $v) {
		if(!empty($v)) return $v;
	}
	return NULL;
}

/**
 * Split an array's keys from its values
 * @param array $array
 */
function array_split(array $array) {
	return array(array_keys($array), array_values($array));
}


function array_merge_recursive_keys() {
	
	# support an indefinite number of array arguments
	$args = func_get_args();

	# merge each array into the previous one
	$product = array();
	foreach($args as $i => $arg) {
	
		# ensure only arrays are passed
		if(!is_array($arg)) {
			throw new InvalidArgumentException("Arguments must be arrays");
		}
	
		# no merging to be done for the first array
		if($i === 0) {
		
			$product = $arg;
		
		} else {
		
			foreach($arg as $j => $k) {
				if(is_array($product[$j]) && is_array($k)) {
					# merge sub-arrays together recursively
					$product[$j] = array_merge_recursive_keys($product[$j], $k);
				} else {
					# add (or overwrite)
					$product[$j] = $k;
				}
			}
		}
	}

	return $product;

}


function array_filter_blank(array $input) {
	return array_filter($input, create_function('$a', 'return !empty($a);'));
}

function mime($file) {
	try {
		$fi = new finfo(FILEINFO_MIME);
	} catch(ErrorException $e) {
		$fi = new finfo(FILEINFO_MIME, Config::get('fileinfo_magic_db'));
	}
	return $fi->file($file);
}


function slug($string, $length = 30) {
	
	# convert international characters to their closest ASCII equivalents
	$slug = format_ascii($string);
	
	# filter non-alphanumeric chars, retaining spaces and dashes
	$slug = preg_replace('/[^a-zA-Z0-9 \-_]/',' ',$slug );
	
	# replace spaces with dashes
	$slug = str_replace(' ','-', $slug);
	
	# remove extraneous dashes
	$slug = trim(preg_replace('/[-]+/', '-', $slug), '-_');
	
	# chop
	$slug = character_limiter($slug, $length, '');
	
	# make it lowercase
	$slug = strtolower($slug);
	
	# trim again
	$slug = rtrim($slug, ' -_');
	
	return $slug;
}


function uuid() {
	# courtesy of http://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid/2040279#2040279
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}



function word_truncate($str, $limit) {
	$char_lim = character_limiter($str, $limit);
	
	if (strlen($char_lim) > $limit){
		$o = substr($char_lim, 0, $limit) . "&#8230;";
		return $o;
	}else{
		return $char_lim;
	}
}

/* DESC		: Generate a random string for uniqueness
 * RETURNS	: (string) $random_string
 * USAGE	: _random_gen((int) $length);
 * NOTES	:
**/
function _random_gen($length){
	$random = '';
	srand((double)microtime()*1000000);
	$char_list = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
	for($i = 0; $i < $length; $i++){
		$random .= substr($char_list,(rand()%(strlen($char_list))), 1);
	}
	return $random;
}

function rrmdir($path){
	if(empty($path)){
		return false;
	}
	if(is_file($path)){
		return @unlink($path);
	} elseif(is_dir($path)){
		return array_map('rrmdir',glob($path.'/*'))==@rmdir($path);
	}
	return false;
}

function coalesce(){
	foreach (func_get_args() as $arg) {
		if(!empty($arg)){
			return $arg;
		}
	}
	return NULL;
}

/**
 * @author dave_walter at NOSPAM dot yahoo dot com
 * http://www.php.net/manual/en/function.str-getcsv.php#88773
 */
if(!function_exists('str_putcsv')) {
    function str_putcsv($input, $delimiter = ',', $enclosure = '"') {
        // Open a memory "file" for read/write...
        $fp = fopen('php://temp', 'r+');
        // ... write the $input array to the "file" using fputcsv()...
        fputcsv($fp, $input, $delimiter, $enclosure);
        // ... rewind the "file" so we can read what we just wrote...
        rewind($fp);
        // ... read the entire line into a variable...
        $data = fgets($fp);
        // ... close the "file"...
        fclose($fp);
        // ... and return the $data to the caller, with the trailing newline from fgets() removed.
        return rtrim( $data, "\n" );
    }
}


