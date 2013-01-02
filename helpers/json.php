<?php

/**
 * Output JSON data wrapped in an object for XSS security and JSON headers
 *
 * @param	mixed	data to be sent to the server
 * @param	error	any exceptions that should be presented to the user.
 * @param	bool	whether it should return the output instead of halting.
 */
function json_output($data, $error = false, $return = false) {

	// Setup wrapper object and add data.
	$wrapper = new stdClass;
	$wrapper->result = $data;
	
	// If there is an error add it to the wrapper.
	if ($error) {
		$wrapper->error  = $error;
	}
	
	if ($return) {
		return json_encode($wrapper);
	} else {
		json_send($wrapper);
	}
	
}

/**
 * Output JSON data and JSON headers
 *
 * @param array $array
 */
function json_print(array $array) {
	header('Content-type: application/json');
	echo json_encode($array);
}


/**
 * Indents a flat JSON string to make it more human-readable
 *
 * @param string $json The original JSON string to process
 * @return string Indented version of the original JSON string
 */
function json_encode_human($json) {
	$json = json_encode($json);
	
	#only format this code if in dev mode
 	if(!DEV_MODE)
 		return $json;
	
	
    $result    = '';
    $pos       = 0;
    $strLen    = strlen($json);
    $indentStr = '  ';
    $newLine   = "\n";
 
    for($i = 0; $i <= $strLen; $i++) {
        
        // Grab the next character in the string
        $char = substr($json, $i, 1);
        
        // If this character is the end of an element,
        // output a new line and indent the next line
        if($char == '}' || $char == ']') {
            $result .= $newLine;
            $pos --;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }
        
        // Add the character to the result string
        $result .= $char;
 
        // If the last character was the beginning of an element,
        // output a new line and indent the next line
        if ($char == ',' || $char == '{' || $char == '[') {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }
            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }
    }
 
    return $result;
}


/**
 * Alias for backward-compatibility (deprecated)
 */
if(!function_exists('j_encode')){
	function j_encode($json) {
		return json_encode_human($json);
	}
}


function json_send($data, $allow_jsonp = false) {
	
	# It is JSONP if it's allowed, and we have a $_GET callback that doesn't contain non word characters 
	$is_jsonp = ($allow_jsonp && isset($_GET['callback']) && !preg_match('/\W/', $_GET['callback']));
	
	# Encode the JSON
	$json = json_encode($data);
	
	# Don't try to resend the headers 
	if(!headers_sent()) {
		
		if ($is_jsonp) {
			
			# JSONP is actually Javascript, not JSON
			header('Content-type: application/javascript; charset=utf-8');	
					
		} else {
			
			# charset typos here can cause IE errors:
			# http://www.webmasterworld.com/javascript/3341129.htm
			# http://stackoverflow.com/questions/477816/the-right-json-content-type
			header('Content-Type: application/json; charset=utf-8');
			
		}
	}
	
	# If this is JSONP, wrap in a callback
	if ($is_jsonp) {
		$json = e($_GET['callback']) . '(' . $json . ');';
	}
	
    echo $json;
}
