<?php

# Taken from http://www.sitepoint.com/forums/showthread.php?t=438748
function object_to_array($result, $recurse = TRUE, $include_subobjects = TRUE)
{
    $array = array();
    if(is_array($result) || is_object($result)) {
	    foreach ($result as $key=>$value) {
	    	if(is_object($value) || is_array($value)) {
	    		if($recurse) {
	    			$array[$key] = object_to_array($value);
	    		} elseif($include_subobjects) {
	    			$array[$key] = $value;
	    		}
	    	} else {
	        	$array[$key] = $value;
	    	}
    	}
    }
    return $array;
}

function object_2_array($result, $recurse = TRUE) {
	return object_to_array($result, $recurse = TRUE);
}


# Deep clone an object or array of objects recursively
function deep_clone($var) {
	if(is_object($var)) {
		return clone $var;
	} elseif(is_array($var)) {
		$clone = array();
		foreach($var as $k => $v) {
			$clone[$k] = deep_clone($v);
		}
		return $clone;
	} else {
		return $var;
	}
}


function unset_obj(&$var) {

	if(is_object($var)) {
		
		if(method_exists($var, '__destruct')) {
			$var->__destruct();
		}
		
	} elseif(is_array($var)) {
		
		foreach($var as &$v) {
			unset_obj($v);
		}
		
	}
	
	unset($var);
}


function set_object_state($class, array $data = array()) {
	$obj = new $class;
	foreach($data as $k => $v) {
		$obj->$k = $v;
	}
	return $obj;
}


function array_to_object(array $array) {
	$object = new stdClass();
	foreach($array as $key => $val) {
		$object->$key = (is_array($val))? array_to_object($val) : $val;
	}
	return $object;
}


function get_object_vars_public($obj) {
	return get_object_vars($obj);
}
