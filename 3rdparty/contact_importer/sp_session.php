<?php
# ***************************************************************************************
# *** DO NOT REDISTRIBUTE, DO NOT PUBLISH CODE IN PUBLIC DOMAIN (forums or otherwise) ***
# ***************************************************************************************
#
# 			Copyright 2006-2008 Svetlozar Petrov
# 			All Rights Reserved
#
# 			For more info/contact: 
# 			http://svetlozar.net
# 			http://www.linkedin.com/in/svetlozarpetrov
#
# ***************************************************************************************

global
    $curl,
    $curlpost,
    $auto_redirect,
    $curl_autoredirect,
    $separatePost,
    $proxy,
    $cookie,
    $simplecookie,
    $separatePost,
    $user_agent,
    $referrer,
    $redir_limit,
    $location,
    $redirect,
    $nextlocation,
	$csv_source_encoding;

	
include("globals.php");

function start_session() // creates and initializes curl objects to use with get and post requests
{
	global $curl, $curlpost, $auto_redirect, $curl_autoredirect, $separatePost, $proxy;
	
	$headers = array();
    $headers['Connection'] = "close";
    $headers['Accept'] = "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $auto_redirect && $curl_autoredirect);
	curl_setopt($curl, CURLOPT_HEADERFUNCTION, 'read_header');

	if ($separatePost)
	{
		$curlpost = curl_init();
		curl_setopt($curlpost, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curlpost, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlpost, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curlpost, CURLOPT_FOLLOWLOCATION, $auto_redirect && $curl_autoredirect);
		curl_setopt($curlpost, CURLOPT_HEADERFUNCTION, 'read_header');
	}
	else
	{
		$curlpost = $curl;
	}

	set_referrer();
	set_useragent();
}

function set_referrer() // $referrer must be specified globally before calling this / used internally no need to call directly
{
	global $referrer, $separatePost, $curl, $curlpost;
	if ($curl && $referrer != "")
		curl_setopt($curl, CURLOPT_REFERER, $referrer);
	if ($separatePost && $curlpost && $referrer != "")
		curl_setopt($curlpost, CURLOPT_REFERER, $referrer);
}

function set_useragent() // $user_agent must be specified globally before calling this / used internally no need to call directly
{
	global $user_agent, $separatePost, $curl, $curlpost;
	if ($curl && $user_agent != "")
		curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
	if ($separatePost && $curlpost && $user_agent != "")
		curl_setopt($curlpost, CURLOPT_USERAGENT, $user_agent);
}

function end_session() // close curl connections, clean up
{
	global $curl, $curlpost;
	if ($curl)
		@curl_close($curl);
	if ($curlpost)
		@curl_close($curlpost);
}

function read_header($ch, $string) // parse cookies and redirect locations
{
	global $cookie, $simplecookie, $location, $referrer, $redirect, $auto_redirect, $curl_autoredirect, $nextlocation, $csv_source_encoding;
	
    $length = strlen($string);
	$uri = explode("/", $location);
	$hostname = $uri[2];
	$expired = false;
	$cookiedomain = "";

	if (preg_match("/Content-Type: text\\/csv; charset=([^\s;$]+)/",$string,$matches))
		$csv_source_encoding=$matches[1];

	if (trim($string) == "" && $redirect)
	{
		if ($auto_redirect && $curl_autoredirect)
		{
			$uri = explode("/", $nextlocation);
			$domain = $uri[2];
			$cookiestr = get_cookies($domain);
			if ($cookiestr != "")
				curl_setopt($ch, CURLOPT_COOKIE, $cookiestr);
		
			$redirect = false;
		}
	}
    else if(!strncmp($string, "Location:", 9))
    {
    	$referrer = $location;
		$nextlocation = trim(substr($string, 9, -1));
		$redirect = true;
		if (!strncmp($nextlocation, "/", 1))
		{
			$nextlocation = "http://" . $hostname . $nextlocation;
		}
    }
	else if(!strncmp($string, "Set-Cookie:", 11))
    {
		$cookiestr = trim(substr($string, 11, -1));
		$cookiearr = explode(';', $cookiestr);
		$cookienv = explode('=', array_shift($cookiearr));
		$cookiename = trim(array_shift($cookienv)); 
		$cookievalue = trim(implode('=', $cookienv));
		$cookiedomain = "";
		$expired = false;
		
		if (strtolower($cookievalue)== "expired")
		{
			$expired = true;
		}
			  
		foreach($cookiearr as $c)
		{
		  $cookienv = explode('=', $c);
		  $cn = trim(array_shift($cookienv));
		  if (strtolower($cn) == "domain")
		  {
		  	$cookiedomain = trim(array_shift($cookienv));
		  }
		  else if(strtolower($cn) == "expires")
		  {
		  	$year = (int) date("Y");
			$m = array();
			preg_match("([0-9]{4})", array_shift($cookienv), $m);
			if ($year > (int) $m[0])
			{
				$expired = true;
			}
		  }
		}
		
		if ($cookiedomain == "")
		{
			$cookiedomain = $hostname;
		}

		if (!$expired)
		{
			$simplecookie[$cookiename] = $cookievalue;
			if (!isset($cookie[$cookiedomain]))
			{
				$cookie[$cookiedomain] = array();
				// uksort($cookie, 'cmp_len'); // not currently used
			}
			$cookie[$cookiedomain][$cookiename] = $cookievalue;
		}
		else
		{
			if (isset($simplecookie[$cookiename]))
				unset($simplecookie[$cookiename]);
			if (isset($cookie[$cookiedomain]) && isset($cookie[$cookiedomain][$cookiename]))
				unset($cookie[$cookiedomain][$cookiename]);
		}
		
    }

    return $length;
}

function cmp_len($a, $b)
{
	return strlen($a) < strlen($b);
}

function get_cookies($domain) // get cookies for specified domain (depending on whether extendedcookie is on)
{
	global $simplecookie, $cookie, $extendedcookie;
	$cookiestr = "";
	if (!$extendedcookie)
	{
		foreach ($simplecookie as $k=>$v)
		{
			$cookiestr .= "$k=$v; ";
		}
		$cookiestr = trim($cookiestr, "; ");
	}
	else
	{
		foreach ($cookie as $key=>$value)
		{
			if (preg_match("/$key\$/i", $domain))
			{
				foreach ($value as $k=>$v)
				{
					$cookiestr .= "$k=$v; ";
				}
			}
		}
		$cookiestr = trim($cookiestr, "; ");
	}
	
	return $cookiestr;
}

function get($url, $redir_count = 0) // send a GET request for a url
{
	global $curl, $redir_limit, $auto_redirect, $curl_autoredirect, $location, $redirect, $nextlocation;
	$redirect = false;	

	if (!preg_match("/^http/i", $url))
	{
		$url = "http://" . $url;
	}

	$location = $url;
	
	$uri = explode("/", $url);
	$hostname = $uri[2];
	
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_HTTPGET, 1);

	set_referrer();
	set_useragent();

	$cookiestr = get_cookies($hostname);
	if ($cookiestr != "")
		curl_setopt($curl, CURLOPT_COOKIE, $cookiestr);

	$html = curl_exec($curl);

	if ($redirect && $auto_redirect && !$curl_autoredirect && $redir_count < $redir_limit)
	{
		$html = get($nextlocation, $redir_count + 1);
	}

	return $html;
}

function post($url, $data) // send a POST request for url, $data must be associative array
{
	global $curlpost, $redir_limit, $auto_redirect, $curl_autoredirect, $location, $redirect, $nextlocation;
	$redirect = false;	

	if (!preg_match("/^http/i", $url))
	{
		$url = "http://" . $url;
	}

	$location = $url;
	
	$uri = explode("/", $url);
	$hostname = $uri[2];
	
	curl_setopt($curlpost, CURLOPT_URL, $url);
	curl_setopt($curlpost, CURLOPT_RETURNTRANSFER, 1);
	

	set_referrer();
	set_useragent();

	$poststr = "";
	
	foreach ($data as $k=>$v)
	{
		$poststr .= urlencode($k) . "=" . urlencode($v) . "&";
	}
	
	$poststr = trim($poststr, "& ");

	curl_setopt($curlpost, CURLOPT_POSTFIELDS, $poststr);
	curl_setopt($curlpost, CURLOPT_POST, 1);
	$cookiestr = get_cookies($hostname);
	if ($cookiestr != "")
		curl_setopt($curlpost, CURLOPT_COOKIE, $cookiestr);

	$html = curl_exec($curlpost);	

	if ($redirect && $auto_redirect && !$curl_autoredirect)
	{
		$html = get($nextlocation, 1);
	}

	return $html;
}
?>