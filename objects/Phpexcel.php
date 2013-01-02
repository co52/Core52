<?php

	//$path = PATH_CORE.'3rdparty/phpexcel/';
	$path = PATH_CORE.'3rdparty/phpexcel/';
	set_include_path(get_include_path() . PATH_SEPARATOR . $path);	// have to set the path so PHPExcel can load its components correctly
	
	// Load PHPExcel components
	require_once('PHPExcel.php');
	require_once('PHPExcel/Reader/Excel5.php');
	require_once('PHPExcel/Reader/Excel2007.php');
	require_once('PHPExcel/Writer/Excel5.php');
	require_once('PHPExcel/Writer/Excel2007.php');
	require_once('PHPExcel/Writer/PDF.php');
	require_once('PHPExcel/Cell/AdvancedValueBinder.php');