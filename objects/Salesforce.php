<?php
	
require_once(PATH_CORE.'3rdparty/salesforce/soapclient/SforceEnterpriseClient.php');
require_once(PATH_CORE.'3rdparty/salesforce/soapclient/SforcePartnerClient.php');
require_once(PATH_CORE.'3rdparty/salesforce/soapclient/SforceMetadataClient.php');


class SalesforceException extends Exception {
	public $response;
}


class Salesforce {
	
	private static $username, $password;
	private static $apis = array();
	private static $auth = array();
	protected static $metadata = array();
	
	
	public static function Initialize($username, $password = NULL) {
		
		if(is_array($username)) {
			extract($username);
		}
		
		self::$username = $username;
		self::$password = $password;
	}
	
	
	public static function factory($api = 'partner', $auth = FALSE) {
		
		if(array_key_exists($api, self::$apis)) {
			return self::$apis[$api];
		}
		
		
		switch($api) {
			case 'enterprise':
				self::$apis[$api] = new SforceEnterpriseClient();
				self::$apis[$api]->createConnection(PATH_CORE."3rdparty/salesforce/soapclient/enterprise.wsdl.xml");
				break;
				
			case 'partner':
				self::$apis[$api] = new SforcePartnerClient();
				self::$apis[$api]->createConnection(PATH_CORE."3rdparty/salesforce/soapclient/partner.wsdl.xml");
				break;
				
			case 'metadata':
				self::$apis[$api] = new SforceMetadataClient();
				self::$apis[$api]->createConnection(PATH_CORE."3rdparty/salesforce/soapclient/metadata.wsdl.xml");
				break;
			
			default:
				throw new Exception('Invalid Saleforce API object request');
		}
		
		
		switch($auth) {
			
			case 'login':
				self::login($api);
				break;
				
			case 'session_login':
				self::session_login($api);
				break;
		}
		
		
		return self::$apis[$api];
	}
	
	
	public static function session_login($api = 'partner', array $data = NULL) {
		
		if(is_array($data)) {
			extract($data);
		} else {
			$wsdl = Session::data('sforce_wsdl');
			$location = Session::data('sforce_location');
			$sessionId = Session::data('sforce_sid');
		}
		
		$api = self::factory($api);
		$api->createConnection($wsdl);
		$api->setEndpoint($location);
		$api->setSessionHeader($sessionId);
	}
	
	
	public static function login($api = 'partner', array $data = NULL, $force_auth = FALSE) {
		
		# only login once
		if(self::$auth[$api] && !$force_auth) return TRUE;
		
		if(is_array($data)) {
			extract($data);
		} else {
			$username = self::$username;
			$password = self::$password;
		}
		
		self::$auth[$api] = TRUE;
		return self::factory($api)->login($username, $password);
	}
	
	
	public static function metadata($class, $api = FALSE, $auth = FALSE) {
		
		if(empty($class)) {
			throw new Exception('Empty $class parameter');
		}
		
		if(!$api) $api = 'partner';
		if(!$auth) $auth = 'login';
		
		if(!self::$metadata[$class]) {
			self::$metadata[$class] = Salesforce::factory($api, $auth)->describeSObjects(array($class));
		}
		
		return self::$metadata[$class];
	}
	
	
	public static function picklist_values($class, $field_name, $api = FALSE, $auth = FALSE) {
		$fields = self::metadata($class, $api, $auth);
		$field = self::filter_metadata_fields($fields->fields, array('type' => 'picklist', 'name' => $field_name));
		$field = array_pop($field);
		$values = array();
		foreach((array) $field->picklistValues as $val) {
			$values[$val->value] = $val->label;
		}
		return $values;
	}
	
	
	public static function multipicklist_values($class, $field_name, $api = FALSE, $auth = FALSE) {
		$fields = self::metadata($class, $api, $auth);
		$field = self::filter_metadata_fields($fields->fields, array('type' => 'multipicklist', 'name' => $field_name));
		$field = array_pop($field);
		$values = array();
		foreach((array) $field->picklistValues as $val) {
			$values[$val->value] = $val->label;
		}
		return $values;
	}
	
	
	public static function filter_metadata_fields(array $fields, array $filter) {
		$result = array();
		foreach($fields as $field) {
			foreach($filter as $fkey => $fval) {
				if($field->$fkey != $fval) continue 2;
			}
			if($field->name != 'OwnerId' && $field->name != 'CreatedById' && $field->name != 'LastModifiedById' && $field->name != 'ReportsToId') {
				$result[$field->name] = $field;
			}
		}
		return $result;
	}
	
	
	// A demonstration of creating an array of complex objects from the result of a relationship
	// query of the Salesforce database. Parent objects are returned as a single object.
	// Child object subqueries are returned as an array of objects. Standard object syntax
	// can be used to navigate the result.
	//
	// To see how this works you will want to run the demo against an account that has tasks
	// associated with some of the contacts.
	//
	// Author: Park Walker - park at redsummit dot com
	// Date:   28 February 2009
	// http://shared.redsummit.com/sforce/unpackSObjects.zip
	//
	// Modified on 1/29/2010 by Jonathon Hill <jhill@company52.com>
	//
    public static function unpackSObjects($queryResult, array $columns = NULL) {
	    
    	$list = array();
		if(empty($queryResult->records)) return $list;
	    
	    if(!$columns) {
	    	$meta = self::metadata($queryResult->records[0]->type);
		    $columns = self::filter_metadata_fields($meta->fields, array('type' => 'reference'));
		    $relationships = array();
		    foreach($columns as $column) {
		    	$relationships[$column->relationshipName] = clone $column;
		    }
	    }
	        
	    foreach($queryResult->records as $record) {
	    	$r = self::printSObject($record, $relationships);
		    $newObj = self::arrayToObject($r);
		    $list[] = $newObj;
	    }
	    
	    return $list;
    }
	
	private static function printSObject($obj, array $columns = NULL) {
	
		$result = array();
		$result['type'] = $obj->type;
		$result['Id'] = $obj->Id;
		
		if($columns) {
			foreach(array_keys($columns) as $i => $col) {
				if(isset($obj->fields->$col)) {
					unset($columns[$col]);
				}
			}
			$cols = array_keys($columns);
		}
		
		$i = 0;
		foreach((array) $obj->fields as $key => $val) {
		
		  	if($val instanceOf SObject && $key != 'CreatedById' && $key != 'OwnerId' && $key != 'ReportsToId') {
		  		$name = (isset($cols[$i]))? $columns[$cols[$i]]->name : $val->type;
		  		$result[$name] = self::printSObject($val);
		  		$result[$name]['Id'] = $val->Id;
		  		$i++;
		  	} else {
		  		$result[$key] = $val;
		  	}
		}
		/*
		if (! is_null($obj->queryResult))
		{
		  	foreach($obj->queryResult as $qr)
		  	{
			  	$t = $qr->records[0]->type . 's';  // attempt to pluralize; should get plural name
			  	$s = $qr->size;
			  	$tmp = array($s);
		  		for($i = 0; $i < $qr->size; $i++)
		  		{
		  			$tmp[$i] = self::printSObject($qr->records[$i]);
		  		}
		  		$result[$t] = $tmp;
		  	}
		}
		*/
		return $result;
	}
	  
	private static function arrayToObject( $array ) {
		foreach( $array as $key => $value ) {
			if( is_array( $value ) ) $array[ $key ] = self::arrayToObject( $value );
		}
		return (object) $array;
	}
	
	
}

