<?php
# ***************************************************************************************
# *** DO NOT REDISTRIBUTE, DO NOT PUBLISH CODE IN PUBLIC DOMAIN (forums or otherwise) ***
# ***************************************************************************************
#
# 			Copyright 2006-2010 Svetlozar Petrov
# 			All Rights Reserved
#
# 			For more info/contact: 
# 			http://svetlozar.net
# 			http://www.linkedin.com/in/svetlozarpetrov
#
# ***************************************************************************************

include("sp_session.php");

// AOL/AIM mail accepts username (not a complete email address) - @domain will be automatically stripped
function get_contacts ($username, $password)
{
	global $simplecookie, $user_agent, $extendedcookie, $auto_redirect;
	
	$extendedcookie = false; // use simple cookie for AOL
	$auto_redirect = true;
	$user_agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)";
	
	if (strstr($username, "@"))
	{
		$username = current(explode("@", $username));
	}
	
	if ((isset($username) && trim($username)=="") || (isset($password) && trim($password)==""))
	{
		return SP_NoUserPassword;
	}
	
	start_session();
	
	// attempt login
	$html = get("https://my.screenname.aol.com/_cqr/login/login.psp?mcState=initialized&seamless=novl&sitedomain=sns.webmail.aol.com&lang=en&locale=us&authLev=2&siteState=ver%3a2%7cac%3aWS%7cat%3aSNS%7cld%3awebmail.aol.com%7cuv%3aAOL%7clc%3aen-us");
	$url = "https://my.screenname.aol.com/_cqr/login/login.psp";
	
	$matches = array();
	preg_match('/<form name="AOLLoginForm".*?action="([^"]*).*?<\/form>/si', $html, $matches);
	preg_match_all('/<input type="hidden" name="([^"]*)" value="([^"]*)".*?>/si', $matches[0], $matches);
	$params = array();
	$i = 0;
	foreach($matches[1] as $name)
	{
		$params[$name] = $matches[2][$i++];
	}
	$params['loginId'] = $username;
	$params['password'] = $password;
	
	$html = post($url, $params);

	# check if login passed
	if(!preg_match("/'loginForm', 'false', '([^']*)'/si", $html, $matches))
	{
		#return error if it's not
		return SP_InvalidLogin;
	}
	
	$url = $matches[1];
	$html = get($url);

	if (preg_match('/gTargetHost = "([^"]*)".*?gSuccessPath = "([^"]*)"/si', $html, $matches) || preg_match('/gPreferredHost = "([^"]*)".*?gSuccessPath = "([^"]*)"/si', $html, $matches))
	{
		$url = $matches[1];
		$url .= $matches[2];
		$url = "http://" . $url;
	}
	else
	{
		if(preg_match("/'loginForm', 'false', '([^']*)'/si", $html, $matches))
		{
			$html = get($matches[1]);
			$url = $location;
		}

		if (preg_match('/gTargetHost = "([^"]*)".*?gSuccessPath = "([^"]*)"/si', $html, $matches) || preg_match('/gPreferredHost = "([^"]*)".*?gSuccessPath = "([^"]*)"/si', $html, $matches))
		{
			$url = $matches[1];
			$url .= $matches[2];
			$url = "http://" . $url;
		}
	}
	
	$opturl = $url;
	
	//get settings:
	$opturl = explode("/", $opturl);
	$opturl[count($opturl)-1]="common/settings.js.aspx";
	$opturl = implode("/", $opturl);
	
	$html = get($opturl);
	
	$opturl = explode("/", $url);
	$opturl[count($opturl)-1]="AB";
	$opturl = implode("/", $opturl);
	
	if (preg_match('/"UserUID":"([^"]*)/si', $html, $matches) || preg_match('/uid:([^&]*)/si', $simplecookie["Auth"], $matches))
	{
		$usr = $matches[1];
	}
	
	#get the address book:
	$opturl .= "/addresslist-print.aspx?command=all&undefined&sort=LastFirstNick&sortDir=Ascending&nameFormat=FirstLastNick&version=$simplecookie[Version]&user=$usr";

	$html = get($opturl);
	end_session();

	$m = explode("contactSeparator", $html);
	$e = array_map("parse_emails", $m);
	$n = array_map("parse_names", $m);
	
	$maxi = count($n);
	for ($i = 0; $i < $maxi; $i++)
	{
		if ($e[$i] != "")
		{
			if ($n[$i] == "")
			{
				$n[$i] = current(explode("@", $e[$i]));
			}
			$names[] = $n[$i];
			$emails[] = $e[$i];
		}
	}
		
	if (count($names) == 0)
	{
		return SP_NoContacts;
	}
	
	return array($names, $emails);

}

function parse_emails($str)
{
	$matches = array();
	if( preg_match('/(?>Email).*?([^@<>]+@[^<]+)/si', $str, $matches) )
		return trim($matches[1]);
	else
		return "";
}

function parse_names($str)
{
	$matches = array();
	if( preg_match('/fullName[^>]*>(.*?)<[^>]*>([^<]*)/si', $str, $matches) )
		return trim($matches[1]);
	else
		return "";
}

?>