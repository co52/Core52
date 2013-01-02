<?php
# ***************************************************************************************
# *** DO NOT REDISTRIBUTE, DO NOT PUBLISH CODE IN PUBLIC DOMAIN (forums or otherwise) ***
# ***************************************************************************************
#
# 			Copyright 2006-2009 Svetlozar Petrov
# 			All Rights Reserved
#
# 			For more info/contact: 
# 			http://svetlozar.net
# 			http://www.linkedin.com/in/svetlozarpetrov
#
# ***************************************************************************************

include("sp_session.php");

// send complete email address, gmail will accept either email or just username
function get_contacts ($username, $password)
{
	global $simplecookie, $csv_source_encoding;
	
	if ((isset($username) && trim($username)=="") || (isset($password) && trim($password)==""))
	{
		return SP_NoUserPassword;
	}
	
	start_session();
	
	$html = get("https://www.google.com/accounts/ServiceLoginAuth?service=mail");
	$params = array("Email"=>$username, "Passwd"=>$password, "PersistentCookie"=>"");
	if (preg_match_all('/<input type="hidden"[^>]*name\="([^"]+)"[^>]*value\="([^"]*)"[^>]*>/', $html, $matches))
	{
		for ($i = 0; $i < count($matches[1]); $i++)
		{
			$hname = $matches[1][$i];
			$hvalue = $matches[2][$i];
			$params[$hname] = $hvalue;
		}
	}

	// attempt login
	post("https://www.google.com/accounts/ServiceLoginAuth?service=mail", $params);
	
	if (!isset($simplecookie['GX']) && (!isset($simplecookie['LSID']) || $simplecookie['LSID'] == "EXPIRED"))
	{
		return SP_InvalidLogin;
	}
	
	// get the contacts csv file
	$html = get("http://mail.google.com/mail/contacts/data/export?exportType=ALL");
	
	end_session();

	if (strtolower($csv_source_encoding) != 'utf-8' || !strstr($csv_source_encoding, 'ascii'))
	{
		if (function_exists("iconv"))
			$html = iconv($csv_source_encoding,'utf-8', $html);
		else if(function_exists("mb_convert_encoding"))
			$html = mb_convert_encoding($html, 'utf-8', $csv_source_encoding);
		else
			return SP_ERROR; // could not find a function to encode the string to utf-8
	}
	
	$csvrows = explode("\n", $html);
	array_shift($csvrows);
	
	$names = array();
	$emails = array();
	$matches = array();
	
	foreach ($csvrows as $row)
	{
		if (preg_match('/^((?:"[^"]*")|(?:[^,]*)).*?([^,@]+@[^,]+)/', $row, $matches))
		{
			$names[] = trim( ( trim($matches[1] )=="" ) ? current(explode("@",$matches[2])) : $matches[1] , '" ');
			$emails[] = trim( $matches[2] );
		}
	}
	
	if (count($names) == 0)
	{
		return SP_NoContacts;
	}
	
	return array($names, $emails);

}
?>