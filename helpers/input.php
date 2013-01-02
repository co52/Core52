<?php

/**
 * Ensure that a variable is an integer datatype
 *
 * @param unknown_type $v
 * @param unknown_type $default
 * @return unknown
 */
function input_integer($v = NULL, $default = NULL) {
	return (isset($v) && $v == (int) $v)? (int) $v : $default;
}

/**
 * Ensure that a variable is a float datatype
 *
 * @param unknown_type $v
 * @param unknown_type $default
 * @return unknown
 */
function input_float($v = NULL, $default = NULL) {
	return (isset($v) && $v == (float) $v)? (float) $v : $default;
}

/**
 * Ensure that a variable is numeric
 *
 * @param unknown_type $v
 * @param unknown_type $default
 * @return unknown
 */
function input_number($v = NULL, $default = NULL) {
	return (isset($v) && is_numeric($v))? $v : $default;
}

/**
 * Ensure that a variable is an array
 *
 * @param unknown_type $v
 * @param unknown_type $default
 * @return unknown
 */
function input_array($v = NULL, $default = NULL) {
	return (isset($v) && is_array($v))? $v : $default;
}

/**
 * Ensure that a string matches a valid value
 *
 * @param unknown_type $v
 * @param array $values
 * @param unknown_type $default
 * @return unknown
 */
function input_string($v = NULL, array $values, $default = NULL) {
	return (isset($v) && in_array($v, $values))? $v : $default;
}

/**
 * Ensure that a string matches a valid URL
 *
 * @param unknown_type $v
 * @param array $values
 * @param unknown_type $default
 * @return unknown
 */
function input_url($v = NULL, $default = NULL) {
	return preg_match('/^(http[s]?:\/\/)?((([a-zA-Z0-9&\$%:\_\-~])+\.?)*[\/]?)*(\?(([a-zA-Z0-9%:=\_\-\.~])*&?)*)?$/', $v)>0 ? $v : $default;
}

/**
 * Ensure that a string matches a valid image name
 *
 * @param unknown_type $v
 * @param array $values
 * @param unknown_type $default
 * @return unknown
 */
function input_image($v = NULL, $default = NULL) {
	return input_filename($v, 'jpg|jpeg|gif|png');
}
function input_filename($v = NULL, $ext = '', $default = NULL) {
	return preg_match('/^([a-zA-Z]\:(\\|\/))?(([^\/\\:*?"|<>])+(\\|\/)?)*\.('.$ext.')$/', $v)>0 ? $v : $default;
}

/**
 * Ensure that each element of an array is a valid string
 *
 * @param array $v
 * @param array $values
 * @param unknown_type $default
 * @return array
 */
function input_string_array(array $v, array $values, $default = NULL) {
	foreach($v as &$value) {
		$value = input_string($value, $values, $default);
	}
	return $v;
}

/**
 * Ensure that a variable is a valid date string
 *
 * @param string $v
 * @param string $format
 * @param unknown_type $default
 * @return unknown
 */
function input_date($v = NULL, $format = 'Y-m-d H:i:s', $default = NULL) {
	return (isset($v) && strtotime($v) !== FALSE)? date($format, strtotime($v)) : $default;
}

function input_textile($text, $restrict = TRUE) {
	static $textile = FALSE;
	if(!$textile) {
		$textile = new Textile();
	}
	return ($restrict)? $textile->TextileRestricted($text) : $textile->TextileThis($text);
}