#!/usr/bin/env php
<?php

// grab the core
chdir(dirname(__FILE__));
require "_initialize.php";

// restore the default error and exception handlers for non-HTML output
restore_error_handler();
restore_exception_handler();

// desired revision number (default to latest)
$rev = ($argv[1] > 0)? $argv[1] : FALSE;

// migrate the schema using the default database connection
$migrator = new Database_Migrator((array) database());
$success = $migrator->migrate($rev);

exit((int) !$success);

