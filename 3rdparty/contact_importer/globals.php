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

// ********* Globals Section ********* //
#error codes:
define("SP_NoUserPassword", 2);             # username or password was not given
define("SP_InvalidLogin", 1);	            # invalid login
define("SP_NoContacts", 3);                 # no contacts were found, potential error during web requests processing/contacts parsing
define("SP_ERROR", 4);						# used in gmail only for right now if encoding cannot be converted to utf-8


# modify if your php setup does not allow curl auto redirects (note that auto redirects are much faster through curl)
$curl_autoredirect = true;

$csv_source_encoding = "UTF-8";
$cookie = array();
$simplecookie = array();
$separatePost = true; // enabled by default for cleaner requests (sometimes curl cannot switch back to get 
				      // after it has done a post request, so just keep get and post separate)
$curl = null;
$curlpost = null;
$auto_redirect = false;
$user_agent = "";
$referrer = "";
$redir_limit = 15;
$extendedcookie = true; // use extended cookies by default, if false use simple cookie
$location = ""; 

# These are set in read header function:
$redirect = false;
$nextlocation = "";
// ********* end globals ********* //
?>