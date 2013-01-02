<?php

/**
 * Core52 Class for 37Signals' Campfire
 * 
 * This Campfire wrapper was originally written by Jake A. Smith
 * to send deployment notifications to the Glarity chat room back
 * in Sept 2008. It's poorly documented, but mostly self explanatory.
 *
 * It's also deprecated, as of the new campfire API.
 * http://developer.37signals.com/campfire/
 *
 * @author "Jake A. Smith" <jake@companyfiftytwo.com>
 * @package Core52
 * @version 1.0
 * 
 **/


class Campfire {

	/*** Variable list
	**********************************************************************/
	
	// Account info.
	private $account;				// CF account.
	private $email;					// CF email.
	private $pass;					// CF password.
	
	private $curl;					// cURL resource.
	private $cookie;				// cURL cookie.
	
	private $room;					// Current room id.
	
	/*********************************************************************/
	
	public function __construct($account, $email, $pass)
	{
		// Set vars.
		$this->account = $account;
		$this->email = $email;
		$this->pass = $pass;
		
		// HTTP Headers.
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Keep-Alive: 300";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Content-Type	text/html; charset=utf-8";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$header[] = "Pragma: "; // browsers keep this blank.
		
		// Cookie info.
		$this->cookie = 'cookie';
		
		// Set up cURL.
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)");
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_VERBOSE, 1);
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookie);
	 	curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookie);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->curl, CURLOPT_MUTE, true);
		
		// Return login result.
		return $this->login();
	}
	
	
	public function __destruct()
	{
		// Close the cURL connection.
		curl_close($this->curl);
	}
	
	
	public function login()
	{
		// Set data.
		$data = 'email_address='. $this->email .'&password='. $this->pass;
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
		
		// Set URL.
		$url = 'http://'. $this->account .'.campfirenow.com/login';
		$this->exec($url);
		
		// Reset stuff.
		curl_setopt($this->curl, CURLOPT_POST, 0);
		
		return true;
	}


	public function logout()
	{	
		// Set URL.
		$url = 'http://'. $this->account .'.campfirenow.com/logout';
		$this->exec($url);
	}
	
	
	public function join_room($id)
	{
		// Save room number.
		$this->room = $id;
		
		// Set URL.
		$url = 'http://'. $this->account .'.campfirenow.com/room/'. $this->room .'/';
		$this->exec($url);
	}
	
	
	public function leave_room()
	{
		// Set URL.
		$url = 'http://'. $this->account .'.campfirenow.com/room/'. $this->room .'/leave';
		$this->exec($url);
		
		// Delete room number.
		$this->room = null;
	}
	
	
	public function speak($msg, $room = null)
	{		
		// Set data.
		$data = 'message='. $msg;
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

		// Set URL.
		$url = 'http://'. $this->account .'.campfirenow.com/room/'. ((isset($room)) ? $room : $this->room) .'/speak';
		$this->exec($url);
		
		// Reset stuff.
		curl_setopt($this->curl, CURLOPT_POST, 0);
	}
	
	
	public function paste($msg, $room = null)
	{			
		// Set data.
		$data = 'paste=true&message='. $msg;
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

		// Set URL.
		$url = 'http://'. $this->account .'.campfirenow.com/room/'. ((isset($room)) ? $room : $this->room) .'/speak';
		$this->exec($url);
		
		// Reset stuff.
		curl_setopt($this->curl, CURLOPT_POST, 0);
	}
	
	
	private function exec($url)
	{	
		// Set URL.
		curl_setopt($this->curl, CURLOPT_URL, $url);
		
	 	// Do that cURL thing.
		curl_exec($this->curl);
	}
}