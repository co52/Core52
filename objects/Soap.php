<?php

/**
 * Soap
 *
 * @author Jonathon Hill
 * @package Core52
 *
 **/

class Soap {
	
	public $wsdl;
	public $dbg = FALSE;
	protected $client;
	
	
	# Initialize the payment processor gateway SOAP connection
	public function __construct($wsdl = NULL, array $options = array(), $debug = FALSE) {
		
		if(!class_exists('SOAPClient')) {
			throw new FatalErrorException('PHP SOAP extension is required');
		}
		
		$this->dbg = $debug;
		
		if($wsdl !== NULL) {
			$this->wsdl = $wsdl;
		}
		
		if(strlen($this->wsdl) == 0) {
			throw new FatalErrorException('SOAP service WSDL not specified');
		}
		
		if(strtolower(substr($this->wsdl, 0, 5)) == 'https' && !extension_loaded('openssl')) {
			throw new FatalErrorException('PHP openssl extension is required for SOAP services over https');
		}
		
		if($this->dbg) {
			$options['trace'] = TRUE;
			$options['features'] = SOAP_WAIT_ONE_WAY_CALLS;
		}
		
		$this->client = new Core52_SoapClient($this->wsdl, $options);
		$this->client->debug($this->dbg);
		
		if($this->dbg) {
			//print_r($this->soap_dbg('functions'));
		}
	}


	# Show debugging info about the SOAP connection
	public function soap_dbg($which = 'functions') {
		switch($which) {
			case 'functions':		return $this->client->__getFunctions();
			case 'request_body':	return $this->client->__getLastRequest();
			case 'request_headers':	return $this->client->__getLastRequestHeaders();
			case 'response_body':	return $this->client->__getLastResponse();
			case 'response_headers':return $this->client->__getLastResponseHeaders();
		}
	}


	# Call a SOAP method
	public function soap_call($method, $data, $wsdl = NULL) {
		
		if(!is_null($wsdl)) {
			$this->__construct($wsdl);
		}
		
		try {
			$response = $this->client->__soapCall($method, $data);
		} catch(SoapFault $e) {
			if(!$this->dbg) throw $e;
		}
		
		if($this->dbg) {
			print_ar("\nREQUEST:\n-------------------------------\n".htmlentities($this->soap_dbg('request_headers'))."\n".htmlentities($this->soap_dbg('request_body')));
			print_ar("\nRESPONSE:\n-------------------------------\n".htmlentities($this->soap_dbg('response_headers'))."\n".htmlentities($this->soap_dbg('response_body'))."\n\n");
			if($e) throw $e;
		}
		
		return $response;
		
	}
	
	
	# Call a SOAP method
	public static function call($method, $data, $wsdl, $debug = FALSE) {
		$s = new Soap($wsdl, array(), $debug);
		return $s->soap_call($method, $data);
	}
	
}

class Core52_SoapClient extends SoapClient {
	protected $debug = FALSE;
	
	public function debug($dbg = TRUE) {
		$this->debug = $dbg;
	}
	
	public function __soapCall($function_name, array $arguments) {
		//if($this->debug) print_r(var_export(compact('function_name', 'arguments'), TRUE).PHP_EOL.'---'.PHP_EOL);
		return parent::__call($function_name, $arguments);
	}
	
	public function __doRequest($request, $location, $action, $version) {
		//if($this->debug) print_r(var_export(compact('request', 'location', 'action', 'version'), TRUE).PHP_EOL.'---'.PHP_EOL);
		return parent::__doRequest($request, $location, $action, $version);
	}
	
}