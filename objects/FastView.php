<?php

class FastView {

	private static $instance;
	public static $global_data;

    public static function instance($reset = FALSE) {
        if($reset || !is_object(self::$instance)) {
            self::$instance = new FastViewObject();
        }
        return self::$instance;
    }

	public static function Load($file = false, $newInstance = FALSE) {
        return self::instance($newInstance)->Load($file);
	}

	public static function Load_String($str) {
        return self::instance()->Load_String($str);
	}

	public static function Holder($holder, $file, $do_includes=false) {
        return self::instance()->Holder($holder, $file, $do_includes);
	}

	public static function Parse()	{
        return self::instance()->Parse();
	}

	public static function Publish($return = FALSE) {
        return self::instance()->Publish($return);
	}

	public static function Data($var, $val = '') {
        return self::instance()->Data($var, $val);
	}

	public static function Global_Data($var, $val = NULL) {
        return self::instance()->Global_Data($var, $val);
	}

}


class FastViewObject implements ViewInterface {

	private $_file;
	private $_view;
	private $_data = array();
	private $_global_data = array();

	public function __construct($file = false, array $data = array()) {
		if(!defined('TPL_DIR')) define('TPL_DIR', APPLICATION_PATH.'/w3/tpl/');
		if($file) $this->Load($file);
		if($data) $this->Data($data);
	}

	public function Load($file = false) {
		
		$this->_file = $file.'.php';
		
		# prepend default template path
		if(substr($file, 0, 1) !== '/' && substr($file, 0, 1) !== '\\' && strpos($file, ':') === FALSE) {
			$this->_file = PATH_VIEWS.$this->_file;
		}
		
		if(!file_exists($this->_file)) {
			throw new Exception("The view file does not exist: $file.php");
		}
	}

	public function Load_String($str) {
		throw new ErrorException("Not implemented: FastViewObject::Load_String()", E_USER_NOTICE);
	}

	public function Parse() {
		throw new ErrorException("Not implemented: FastViewObject::Parse()", E_USER_NOTICE);
	}

	public function Data($var, $val = '') {

		# normalize the data into an array format for ease of handling
		if(!is_array($var)) {
			$var = array($var => $val);
		}

		$this->_data = array_merge((array) $this->_data, $var);
	}

	public function Global_Data($var, $val = NULL) {

		# normalize the data into an array format for ease of handling
		if(!is_array($var)) {
			$var = array($var => $val);
		}

		FastView::$global_data = array_merge((array) FastView::$global_data, $var);
	}

	public function Holder($holder, $file, $do_includes=false) {
		throw new ErrorException("Not implemented: FastViewObject::Holder()", E_USER_NOTICE);
	}

	public function Publish($return = FALSE) {

		extract((array) FastView::$global_data);
		extract((array) $this->_data);
		ob_start();
		include($this->_file);
		$this->_view = ob_get_clean();

		if($return == FALSE) {
			echo $this->_view;
		} else {
			return $this->_view;
		}

	}

}

function fastview_inc($file, array $vars = array()) {
	$view = new FastViewObject();
	$view->Load($file);
	$view->Data($vars);
	$view->Publish();
}

