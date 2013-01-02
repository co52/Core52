#!/usr/bin/env php
<?php

// grab the core
chdir(dirname(__FILE__));
require "_initialize.php";

// restore the default error and exception handlers for non-HTML output
restore_error_handler();
restore_exception_handler();

// migrate the schema using the default database connection
$migrator = new Update_Post_Processor((int) $argv[1], (int) $argv[2]);
$migrator->exec();


