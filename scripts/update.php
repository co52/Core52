#!/usr/bin/php
<?php

chdir(dirname(__FILE__));
require_once('_initialize.php');
require_once PATH_CORE.'objects/Server_Updater.php';
require_once PATH_CORE.'objects/Servers.php';
require_once Config::get('SERVERS_CONFIG_FILE');


$updater = new Server_Updater();
$success = $updater->run((int) $argv[1]);

exit((int) !$success);