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
$addopt = false; // set to true if you want to add @yahoo.com to id's that do not have email address. 
				 // not recommended since yahoo emails could end with yahoo.co.uk or others

// whenever possible send a complete email address to this function, though yahoo will accept yahoo id instead
function get_contacts ($username, $password)
{
	global $simplecookie, $addopt;
	$matches = array();
	
	if ((isset($username) && trim($username)=="") || (isset($password) && trim($password)==""))
	{
		return SP_NoUserPassword;
	}
	
	start_session();
	
	// attempt login
	$html = get("http://address.yahoo.com");
	preg_match_all('/<input type\="hidden" name\="([^"]+)" value\="([^"]*)">/si', $html, $matches);
		
	$params = array();
	$i = 0;
	foreach($matches[1] as $name)
	{
		$params[$name] = $matches[2][$i++];
	}
	
	$params['login'] = $username;
	$params['passwd'] = $password;
	
	$html = post("http://login.yahoo.com/config/login?", $params);

	if (!isset($simplecookie['F']))
	{
		return SP_InvalidLogin;
	}

	$params = array();
	$params[".src"] = "";
    $params["VPC"] = "print";
    $params["field[allc]"] = "1";
    $params["field[catid]"] = "0";
    $params["field[style]"] = "quick";
	$params["submit[action_display]"] = "Display for Printing";
	
	$url = "http://address.yahoo.com/?_src=&VPC=tools_print";
	$html = post($url, $params);

	end_session();

	$tableM = $rowM = $nameM = $emailM = array(); // matches arrays
	$names = array();
	$emails = array();
	
	if (preg_match_all('/(?><table class="qprintable2"[^>]*>)(.+?)(?><\/table>)/si', $html, $tableM))
	{		
		foreach ($tableM[0] as $m)
		{
			$name = $email = "";
			if(preg_match('/(?><tr class="phead">)(.*?)<\/tr>(?>.*?<tr>)((?>[^@]+)@[^<]+)/si', $m, $rowM))
			{
				if(preg_match('/(?:<b>(.*?)<\/b>)[^<]*(?:<small>(.*?)<\/small>)?/si', $rowM[1], $nameM))
				{
					$name = trim($nameM[1]);
					if ($name == "")
					{
						$name = trim($nameM[2]);
					}
				}
				
				if(preg_match('/>((?>[^>@]+)@[^<]+)/si', $rowM[2], $emailM))
				{
					$email = $emailM[1];
					if ($name == "")
					{
						$name = current(explode("@", $email));
					}
					
					$emails[] = $email;
					$names[] = $name;
				}
			}
			else if ($addopt && preg_match('/(?><tr class="phead">)(.*?)<\/tr>(?>.*?<tr>)/si', $m, $rowM))
			{
				if(preg_match('/(?:<b>(.*?)<\/b>)[^<]*(?:<small>(.*?)<\/small>)?/si', $rowM[1], $nameM))
				{
					$name = trim($nameM[1]);
					if ($name == "")
					{
						$name = trim($nameM[2]);
					}
					
					if (trim($nameM[2]) != "")
					{
						$email = trim($nameM[2]) . "@yahoo.com";
					}
				}

				if(preg_match('/>((?>[^>@]+)@[^<]+)/si', $rowM[1], $emailM))
				{
					$email = $emailM[1];
					if ($name == "")
					{
						$name = current(explode("@", $email));
					}
				}
				
				if ($email != "")
				{
					$emails[] = $email;
					$names[] = $name;
				}
			}
		}
	}
	
	if (count($names) == 0)
	{
		return SP_NoContacts;
	}
	
	return array($names, $emails);

}

#function to trim the whitespace around names and email addresses
#used by get_contacts when parsing the csv file
function trimvals($val)
{
  return trim ($val, '" ');
}
?>