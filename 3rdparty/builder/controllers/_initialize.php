<?php

define('PATH_APP', substr(strtr(dirname(__FILE__), '\\', '/'), 0, (strrpos(strtr(dirname(__FILE__), '\\', '/'), '/')+1)));

# Grab the core
require("../../../initialize.php");
