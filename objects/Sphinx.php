<?php

require_once(PATH_CORE.'3rdparty/sphinx/sphinxapi.php');


class Sphinx {
	
	private static $connections = array();
	private static $default;
	
	/**
	 * Initialize or get a Sphinx search connection api object
	 *
	 * @param string $name Connection name
	 * @param array $connect Connection parameters (host, port, timeout)
	 * @param boolean $default Set as default connection
	 * @return SphinxSearch
	 */
	public static function factory($name = NULL, $connect = FALSE, $default = TRUE) {

		if(is_array($connect)) {
			self::$connections[$name] = new SphinxSearch();
			self::$connections[$name]->SetServer($connect['host'], $connect['port']);
			self::$connections[$name]->SetConnectTimeout($connect['timeout']);
			
			if($default) {
				self::$default = $name;
			}
		}

		if(is_null($name)) {
			$name = self::$default;
		}

		if(self::$connections[$name] instanceof SphinxClient) {
			return self::$connections[$name];
		} else {
			throw new SphinxException("Invalid Sphinx connection: '$name'");
		}
	}
}


class SphinxSearch extends SphinxClient {
	
	function Search($query, $index) {
		$res = $this->Query($query, $index);
		if($res == false) {
		    throw new SphinxException($this->GetLastError());
		} else {
			return $res;
		}
	}
	
}


class SphinxException extends Exception {}


function sphinx($name = NULL, $connect = FALSE, $default = TRUE) {
	return Sphinx::factory($name, $connect, $default);
}