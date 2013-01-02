#!/usr/bin/env php
<?php

chdir(dirname(__FILE__));
require_once('_initialize.php');

$dbconn = database();

switch($argv[1]) {
	
	case 'backup':
		$file = ($argv[2])? $argv[2] : "{$dbconn->database}_backup_".date('Y-m-d').".sql";
		$cmd = sprintf('mysqldump --routines -h %s -u %s -p%s %s > %s', $dbconn->host, $dbconn->user, $dbconn->password, $dbconn->database, $file);
		system($cmd);
		echo "Database backed up to $file\n";
		exit;
		
	case 'restore':
		$file = $argv[2];
		$cmd = sprintf('mysql -h %s -u %s -p%s %s < %s', $dbconn->host, $dbconn->user, $dbconn->password, $dbconn->database, $file);
		system($cmd);
		echo "Database restored from $file\n";
		exit;
	
	case 'restore-baseline':
		$file = PATH_APP.'updates/000/migration.sql';
		$cmd = sprintf('mysql -h %s -u %s -p%s %s < %s', $dbconn->host, $dbconn->user, $dbconn->password, $dbconn->database, $file);
		system($cmd);
		echo "Database restored from $file\n";
		exit;
		
	case 'shell':
		$cmd = sprintf('mysql -h %s -u %s -p%s %s > `tty`', $dbconn->host, $dbconn->user, $dbconn->password, $dbconn->database);
		system($cmd);
		exit;
		
	default:
		echo <<<USAGE
Usage:
=========================================================================================
$argv[0] command [arg]

Commands:

    backup [file]    Backup the application database to [file]. If the [file] is omitted,
                     {database-name}_backup_YYYY-MM-DD.sql is used.

    restore file     Restores the application database from file.

    restore-baseline Restores the application database from the baseline dump stored in
                     PATH_APP/updates/000/migration.sql.

    shell            Invokes the MySQL interactive shell (broken on Windows).

    help             Displays this information.

USAGE;

}

