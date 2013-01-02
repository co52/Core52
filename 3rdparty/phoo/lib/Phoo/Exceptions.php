<?php

/**
 * Base Exception Class
 *
 * @author jhill
 *
 */
namespace Phoo {
	class Exception extends \Exception {}
}

/**
 * Phoo Exceptions
 *
 * @author jhill
 *
 */
namespace Phoo\Exception {
	class Http extends \Phoo\Exception {}
	class SignatureMismatch extends \Phoo\Exception {}
	class ParseError extends \Phoo\Exception {}
	class XMLParseError extends ParseError {
		public function __construct(LibXMLError $error, $xml = '') {
			
			// get the offending line and point to the error location
		    $return  = $xml[$error->line - 1] . "\n" . str_repeat('-', $error->column) . "^\n";
		
		    // get the error prefix based on severity
		    switch ($error->level) {
		        case LIBXML_ERR_WARNING:
		            $return .= "Warning $error->code: ";
		            break;
		         case LIBXML_ERR_ERROR:
		            $return .= "Error $error->code: ";
		            break;
		        case LIBXML_ERR_FATAL:
		            $return .= "Fatal Error $error->code: ";
		            break;
		    }
		
		    // append the error message itself
		    $return .= trim($error->message) . " on line $error->line, column $error->column\n";
		    
		    // set up the Exception properties
			parent::__construct($return, $error->code);
		}
	}
	class JSONParseError extends ParseError {
		public function __construct($error, $json = '') {
			
		    // get the error message based on the error code
		    switch ($error) {
		        case JSON_ERROR_DEPTH:
		            $msg = 'JSON decode error: maximum stack depth exceeded';
		        break;
		        
		        case JSON_ERROR_CTRL_CHAR:
		            $msg = 'JSON decode error: Unxpected control character found';
		        break;
		        
		        case JSON_ERROR_SYNTAX:
		            $msg = 'JSON decode error: Syntax error, malformed JSON';
		        break;
		        
		        case JSON_ERROR_NONE:
		            $msg = 'JSON decode error: unknown error';
		        break;
		    }
			
		    // append the offending data
		    $msg .= trim($json) . ": $json";
		    
		    // set up the Exception properties
			parent::__construct($msg, $error);
		}
	}
	class EmptyResponse extends \Phoo\Exception {}
	class CURLError extends \Phoo\Exception {}
}

/**
 * Phoo HTTP exceptions
 *
 * @author jhill
 *
 */
namespace Phoo\Exception\Http {
	class BadGateway extends \Phoo\Exception\Http {}
	class BadRequest extends \Phoo\Exception\Http {}
	class Forbidden extends \Phoo\Exception\Http {}
	class InternalServerError extends \Phoo\Exception\Http {}
	class MethodNotAllowed extends \Phoo\Exception\Http {}
	class NotAcceptable extends \Phoo\Exception\Http {}
	class NotFound extends \Phoo\Exception\Http {}
	class ServiceUnavailable extends \Phoo\Exception\Http {}
	class Unauthorized extends \Phoo\Exception\Http {}
	
}