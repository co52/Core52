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

// complete email address must be given, if not @hotmail or @msn will be added but this is not very reliable
// hotmail domains include msn.*, live.*, hotmail.* and many other custom domains
// it is recommended that you allow users to enter complete email address, including email
function get_contacts ($username, $password)
{
	global $simplecookie, $cookie, $addatmsn, $auto_redirect, $location, $curl_autoredirect, $user_agent;
	$auto_redirect = true;
	$curl_autoredirect = false;
	$user_agent = "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3";

	
	if ((isset($username) && trim($username)=="") || (isset($password) && trim($password)==""))
	{
		return SP_NoUserPassword;
	}

	if (!strstr($username, "@"))
	{
	 // Don't rely on this, there are many other domains supported by hotmail, allow users to enter full email address including domain part
		if (isset($addatmsn) && $addatmsn)
			$username .= "@" . "msn.com";
		else
			$username .= "@" . "hotmail.com";
	}
	
	start_session();
	
	// attempt login
	$html = get("http://login.live.com/login.srf?id=2");
	$simplecookie['CkTst']= "G" . time() . "000";
	if (!isset($cookie['login.live.com']))
	{
		$cookie['login.live.com'] = array();
	}
	$cookie['login.live.com']['CkTst'] = $simplecookie['CkTst']; 
	$matches = array();
	preg_match('/<form [^>]+action\="([^"]+)"[^>]*>/', $html, $matches);
	$url = $matches[1];

	preg_match_all('/<input type="hidden"[^>]*name\="([^"]+)"[^>]*value\="([^"]*)"[^>]*>/', $html, $matches);
	
	$params = array();
	$i = 0;
	foreach($matches[1] as $name)
	{
		$params[$name] = $matches[2][$i++];
	}

	$sPad="IfYouAreReadingThisYouHaveTooMuchFreeTime";
	$lPad=strlen($sPad)-strlen($password);
	$PwPad=substr($sPad, 0,($lPad<0)?0:$lPad);
	
	$params['PwdPad']=$PwPad;
	if (strlen($password) > 16)
	{
		$password = substr($password, 0, 16);
	}
	
	$params['login'] = $username;
	$params['passwd'] = $password;
	$params['LoginOptions'] = "3";
	
	$html = post($url, $params);
	
	if (!isset($simplecookie['MSPAuth']) || !isset($simplecookie['MSNPPAuth']))
	{
		return SP_InvalidLogin;
	}
	
	if(!preg_match('/replace[^"]*"([^"]*)"/', $html, $matches)) 
		preg_match("/url=([^\"]*)\"/si", $html, $matches);

	$html = get($matches[1]);

	if(preg_match('#top.document.location="(http://mail.live.com[^"]*)#', $html, $matches))
	{
		$html = get("http://mail.live.com");
	}
	
	if(preg_match("/self.location.href\s*=\s*'(http\\\\x3a\\\\x2f[^']*)/si", $html, $matches))
	{
		$html = get(urldecode(str_replace('\x', '%', $matches[1])));
	}
	
	if (strstr($location, "MessageAtLogin"))
	{
	   	preg_match_all('/<input [^>]*name\="([^"]+)"[^>]*value\="([^"]*)"[^>]*>/si', $html, $matches);
		$params = array();
	
		$i = 0;
		foreach($matches[1] as $name)
		{
			$params[$name] = $matches[2][$i++];
		}
	   	preg_match_all('/<input [^>]*value\="([^"]+)"[^>]*name\="([^"]*)"[^>]*>/si', $html, $matches);
	
		$i = 0;
		foreach($matches[2] as $name)
		{
			$params[$name] = $matches[1][$i++];
		}
		
		$html = post($location, $params);
	}
	
	$names = array();
	$emails = array();

	if (preg_match("/(?:nonce.?:.?')([^']*)/si", $html, $matches) || preg_match("/(?:(?>(?>#61;)|(?>x3d))(\d+))/si", $html, $matches))
	{
		$url = $location;
		$urlparts = explode("/", $url);
		$urlparts[count($urlparts)-1] = ($urlparts[count($urlparts)-2] == "mail" ? "" : "mail/") . "EditMessageLight.aspx?n=" . $matches[1];
		$url = implode("/", $urlparts);
		$html = get($url);

		if (preg_match('/"(ContactList.aspx[^"]*)/si', $html, $matches))
		{
			$urlparts[count($urlparts)-1] = ($urlparts[count($urlparts)-2] == "mail" ? "" : "mail/") . $matches[1];
			$url = implode("/", $urlparts);
			$html = get($url);
			end_session();
			
			if (preg_match_all("/(?>\[)(?>(?>'[^']+)'[^']*){2}'([^']*)(?>[^\[]+)\['([^\]']+)(?>'\]\],)/si", $html, $matches))
			{
				for($i = 0; $i < count($matches[2]); $i++)
				{
					$e = trim(decodeurlentity($matches[2][$i]));

					if (strstr($e, "@"))
					{
						$n = trim(decodeurlentity($matches[1][$i]));
						if ($n == "")
						{
							$n = current(explode("@",$e));
						}
						
						$names[] = $n;
						$emails[] = $e;
					}
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

function decodeurlentity($string)
{
	$string = urldecode(str_replace('\x', '%', $string));
	
	//the following from: http://us3.php.net/manual/en/function.html-entity-decode.php
	//html_entity_decode may generate unexpected results/no decoding, use following instead

	// replace numeric entities
	$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
	$string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
	// replace literal entities
	$trans_tbl = get_html_translation_table(HTML_ENTITIES);
	$trans_tbl = array_flip($trans_tbl);
	return strtr($string, $trans_tbl);
}
?>