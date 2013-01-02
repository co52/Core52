<?php
namespace Phoo;

/**
 * Ooyala API Response object
 * Contianer that holds response to check HTTP status code, parse XML and JSON responses, etc.
 *
 * @package Phoo
 * @link http://github.com/company52/Phoo
 */
use Phoo\Exception;

class Response
{
    protected $_url;
    protected $_body;
    protected $_info;
    protected $_status;
    
    
    /**
     * Construct
     */
    public function __construct($url, $body, $info)
    {
        $this->_url = $url;
        $this->_body = $body;
        $this->_info = $info;
        $this->_status = $info['http_code'];
        
        // Exceptions for invalid or error HTTP response
        $status = $this->_status;
        if($status >= 400) {
        	$info = print_r($this, TRUE);
            switch($status) {
                // 4xx
                case 400:
                    throw new Exception\Http\BadRequest(self::format_info($info), 400);
                break;
                case 401:
                    throw new Exception\Http\Unauthorized(self::format_info($info), 401);
                break;
                case 403:
                    throw new Exception\Http\Forbidden(self::format_info($info), 403);
                break;
                case 404:
                    throw new Exception\Http\NotFound(self::format_info($info), 404);
                break;
                case 405:
                    throw new Exception\Http\MethodNotAllowed(self::format_info($info), 405);
                break;
                case 406:
                    throw new Exception\Http\NotAcceptable(self::format_info($info), 406);
                break;
                // 5xx
                case 500:
                    throw new Exception\Http\InternalServerError(self::format_info($info), 500);
                break;
                case 502:
                    throw new Exception\Http\BadGateway(self::format_info($info), 502);
                break;
                case 503:
                    throw new Exception\Http\ServiceUnavailable(self::format_info($info), 503);
                break;
                default:
                    throw new Exception\Http("HTTP " . $status . " Error Returned", $status);
                break;
            }
        } elseif(trim($body) == 'signature mismatch') {
        	$info = print_r($this, TRUE);
        	throw new Exception\SignatureMismatch(self::format_info($info));
        }
    }
    
    
    public static function format_info($info) {
    	if(is_array($info)) {
	    	$str = '<table>';
	    	foreach($info as $k => $v) $str .= "<tr><td>$k</td><td>$v</td></tr>";
	    	$str .= '</table>';
    	} else {
    		$str = "<pre>$info</pre>";
    	}
	    return $str;
    }
    
    
    /**
     * Get/set status code
     */
    public function status($code = null)
    {
        if(null === $code) {
            return $this->_status;
        }
        
        $this->_status = $code;
        return $this;
    }
    
    
    /**
     * Get/set response body
     */
    public function body($body = null)
    {
        if(null === $body) {
            return $this->_body;
        }
        
        $this->_body = $body;
        return $this;
    }
    
    
    /**
     * Detect whether or not response was an error
     *
     * @return boolean
     */
    public function isError()
    {
        return $this->_status >= 400;
    }
    
    
    /**
     * Convert response body into native PHP objects from JSON response
     *
     * @return stdClass
     */
    public function fromJson()
    {
        // special handling for empty response
    	if(strlen(trim($this->_body)) == 0) {
    		throw new Exception\EmptyResponse();
    	}
    	
    	// catch JSON errors
    	$json = json_decode($this->_body);
    	$error = json_last_error();
    	
        if(is_object($json)) {
			return $json;
        } elseif($error !== JSON_ERROR_NONE) {
        	throw new Exception\JSONParseError($error, $this->_body);
		} else {
			throw new Exception\ParseError("Error loading JSON object from string: $this->_body");
		}
    }
    
    
    /**
     * Convert response body into SimpleXMLElement DOM nodes
     *
     * @return SimpleXMLElement
     * @link http://us.php.net/manual/en/class.simplexmlelement.php
     */
    public function fromXml()
    {
    	// special handling for empty response
    	if(strlen(trim($this->_body)) == 0) {
    		throw new Exception\EmptyResponse();
    	}
    	
    	// catch libxml errors
    	$throw_errors = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($this->_body);
        $error = libxml_get_last_error();
        
        // reset the libxml error handler
        libxml_use_internal_errors($throw_errors);
        
        // handle any errors that happened
        if($error === FALSE && is_object($xml)) {
        	return $xml;
        } elseif($error instanceof LibXMLError) {
        	throw new Exception\XMLParseError($error, $this->_body);
        } else {
	        throw new Exception\ParseError("Unknown error loading SimpleXMLElement object from string: $this->_body");
        }
    }
    
    
    /**
     * Parse response body based in information received from HTTP response about its contents
     * Currently supports XML and JSON responses
     *
     * @return \SimpleXMLElement or \stdClass
     * @throws \UnexpectedValueException
     */
    public function parse()
    {
        // XML response, acconting for inforrect Content-Type in the response headers
        if(false !== strpos($this->_info['content_type'], 'xml')
           || ($this->_info['content_type'] == 'text/html' && false !== strpos($this->_body, '<?xml '))) {
            return $this->fromXml();
        }
        
        if(false !== strpos($this->_info['content_type'], 'json')) {
            return $this->fromJson();
        }
        
        throw new \UnexpectedValueException("Response type expected was XML or JSON. Received: (" . $this->_info['content_type'] . ")");
    }
    
    
    /**
     * Output full raw response body
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->_body;
    }
}