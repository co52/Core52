<?php

class SiteSettings {
	
	private static $settings;
	private static $table = 'sitesettings';
	private static $key_col = 'name';
	private static $val_col = 'value';
	
	public static function get($setting = NULL, $refresh = FALSE) {
		if(is_null(self::$settings) || $refresh) {
			self::$settings = Database::findSelArray(self::$table, self::$key_col, self::$val_col);
		}
		return (is_null($setting))? self::$settings : self::$settings[$setting];
	}
	
	public static function set($setting, $value) {
		self::$settings[$setting] = $value;
		Database::replace(self::$table, array(self::$key_col => $setting, self::$val_col => $value));
	}
	
}