<?php

/**
 * Core52 Contact Importer
 *
 * This class logs into an email account, retrieves the address book
 * and returns an array of names and address. It requires code that
 * we purchased from Svetlozar Petrov for the core.
 *
 * Wrapper originally written for the Green Hands project.
 * 
 * http://svetlozar.net/page/Import-Contacts-from-Gmail-Yahoo-Hotmail-MSN-AOL-using-PHP-cURL.html
 *
 * @author "Jake A. Smith" <jake@companyfiftytwo.com>
 * @package Core52
 * @version 1.0
 **/

class Contact_Importer {
	
	protected $contacts = array();
	protected $service;

	public $error_codes = array(
		1 => 'InvalidLogin',		# invalid login
		2 => 'NoUserPassword',		# username or password was not given
		3 => 'NoContacts',			# no contacts were found, potential error during web requests processing/contacts parsing
		4 => 'ERROR'				# used in gmail only for right now if encoding cannot be converted to utf-8
	);
	
	function __construct($service) {
		$services = array('aol', 'gmail', 'hotmail', 'msn', 'yahoo');
		$this->service = $service;
		
		if(!in_array($service, $services)) throw new Exception("The service given ($service) is not supported.");
		
		require_once(PATH_CORE.'3rdparty/contact_importer/'. $service .'.php');
	}
	
	
	/**
	 *
	 * @return array(array($name, $address), ...)
	 * @author Jake
	 **/
	public function get_contacts($user, $pass) {

		$import = get_contacts($user, $pass);

		if(is_numeric($import)) return $import;
		
		$count = 0;
		
		while(count($import[0]) > $count) {
			
			$this->contacts[] = array(
				'name' => $import[0][$count],
				'address' => $import[1][$count]
			);
			
			$count++;
		}


		return $this->contacts;
	}
}