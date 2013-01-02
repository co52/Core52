<?php

/**
 * Core52 Class for entp's Lighthouse
 * 
 * This Lighthouse wrapper was originally written by Jake A. Smith
 * for Glarity to automagically push certain tickets to the "Known
 * Issues" page.
 *
 * @author "Jake A. Smith" <jake@companyfiftytwo.com>
 * @package Core52
 * @version 0.4
 * @todo It can only grab tickets. Uhh.. pretty sure there's more to life..er.. Lighthouse than just tickets. 
 * 
 **/

class Lighthouse {
	
	private $token;
	private $project;
	private $curl;
	private $ticket;
	private $tickets;
	
	function __construct($project, $token)
	{
		// Set vars
		$this->project = $project;
		$this->token = $token;
		$this->curl = curl_init();
		
		// Set cURL options
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_USERPWD, $this->token .':x');
	}
	
	function __destruct()
	{
		curl_close($this->curl);
	}
	
	function ticket($id)
	{
		// See if this ticket has been cached.
		if(isset($this->ticket[$id])) return $this->ticket[$id];
		
		// Get ticket from LH.
		$url = 'http://fiftytwo.lighthouseapp.com/projects/'. $this->project .'/tickets/'. $id .'.xml';
		curl_setopt($this->curl, CURLOPT_URL, $url);
		
		// Cache it.
		$this->ticket[$id] = simplexml_load_string(curl_exec($this->curl));
		
		// Return it.
		return $this->ticket[$id];
	}
	
	function tickets($q = null, $page = null)
	{
		// See if these tickets have been cached.
		if(isset($this->tickets[$q .'&'. $page])) return $this->ticket[$q .'&'. $page];
		
		// Build URL.
		$url = 'http://fiftytwo.lighthouseapp.com/projects/'. $this->project .'/tickets.xml?';
		if($q) $url .= 'q='. urlencode($q) .'&';
		if($page) $url .= 'page='. $page;
		// die(var_dump($url));
		
		// Get tickets from LH.
		curl_setopt($this->curl, CURLOPT_URL, $url);
		
		// Cache it.
		$this->ticket[$q .'&'. $page] = simplexml_load_string(curl_exec($this->curl));
		
		// Return it.
		return $this->ticket[$q .'&'. $page];
	}	
}