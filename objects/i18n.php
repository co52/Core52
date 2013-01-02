<?php

class InternationalizationException extends Exception { }


class i18n {
	
	protected static $lang;
	protected static $country;
	protected static $ptags = array();
	protected static $gtags = array();
	
	
	public static function Initialize($lang = NULL, $page = NULL, array $classes = array('Format', 'DateTime', 'Number', 'Currency')) {
		
		if(is_array($lang)) {
			extract($lang);
		}
		
		
		# load the I18N PEAR extension
		require_once('I18N/Common.php');
		foreach($classes as $class) {
			require_once("I18N/$class.php");
		}
		
		# auto-detect the preferred locale settings
		# based on the Accept-Language HTTP request header
		$locales = self::detect();
		
		# load the translation file
		foreach($locales as $locale) {
			try {
				list($lang, $country) = explode('-', $locale);
				self::set_country($country);
				self::set_lang($lang);
				$e = NULL;
				break;
			}
			catch(InternationalizationException $e) {
				# try the next locale
			}
		}
		
		# fallback on en_US
		if($e instanceof InternationalizationException || empty(self::$lang)) {
			self::set_lang('en');
		}
		if($e instanceof InternationalizationException || empty(self::$country)) {
			self::set_country('US');
		}
		
	}
	
	
	public static function get_locale() {
		return self::$lang.'_'.self::$country;
	}
	
	
	public static function get_country() {
		return strtolower(self::$country);
	}
	
	
	public static function get_lang() {
		return self::$lang;
	}
	
	
	public static function load_translation_file($lang) {
		
		$lang = strtolower($lang);
		$xlate_file = PATH_I18N . $lang .'/_'. $lang .'.php';
		
		if(!file_exists($xlate_file)) {
			
			throw new InternationalizationException("No translation file for this language: ".$lang);
			
		} else {
			
			self::$lang = $lang;
			
			include($xlate_file);
			self::$gtags = $gtags;
			self::$ptags = $ptags;
			
			return TRUE;
		}
		
	}
	
	
	public static function detect() {
		
		# function courtesy of Jesse Skinner
		# http://www.thefutureoftheweb.com/blog/use-accept-language-header
		
		$langs = array();

		if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		    // break up string into pieces (languages and q factors)
		    preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);
		
		    if (count($lang_parse[1])) {
		        // create a list like "en" => 0.8
		        $langs = array_combine($lang_parse[1], $lang_parse[4]);
		    	
		        // set default to 1 for any without q factor
		        foreach ($langs as $lang => $val) {
		            if ($val === '') $langs[$lang] = 1;
		        }
		
		        // sort list based on value
		        arsort($langs, SORT_NUMERIC);
		    }
		}
		
		return array_keys($langs);
	}
	
	
	public static function ptag($tag) {
		throw new exception('Not implemented: i18n::ptag()');
	}
	
	
	public static function gtag($tag) {
		if(!empty(self::$gtags[$tag])) {
			return self::$gtags[$tag];
		} else {
			$lang = self::$lang;
			if(empty($lang)) {
				throw new InternationalizationException("Language not initialized, please run i18n::Initialize()");
			} else {
				throw new InternationalizationException("Not translated ($lang): '$tag'");
			}
		}
	}
	
	/**
	 * Returns the file path for an internationalized template
	 *
	 * @param	string	$name	template name
	 * @return 	string			path to the template with the current language
	 * @author 	Alex King
	 **/
	public static function template($name) {
		$path = PATH_I18N . self::$lang .'/'. $name;
		return $path;
	}
	
	
	public static function set_country($country) {
		self::$country = strtoupper($country);
	}
	
	
	public static function set_lang($lang, $load_file = TRUE) {
		if($load_file) {
			return self::load_translation_file($lang);
		} else {
			self::$lang = strtolower($lang);
		}
	}
	
	
	# split currency symbol from abount
	public static function currency_split($amount) {
		return array(mb_substr($amount, 0, 1), mb_substr($amount, 1));
	}
	
	
	public static function currency_symbol_to_abbr($symbol) {
		
		$convert = array(
			'$' => 'USD',
			'£' => 'GBP',
			'¥' => 'YEN',
			'€' => 'EUR',
		);
		
		foreach($convert as $s => $abbr) {
			if(strcmp($symbol, $s) === 0) {
				return $abbr;
			}
		}
		
		throw new InternationalizationException("Unrecognized currency symbol: $symbol");
	}
	
	
	public static function currency_abbr_to_symbol($abbr) {
		
		$abbr = strtoupper($abbr);
		
		$convert = array_flip(array(
			'$' => 'USD',
			'£' => 'GBP',
			'¥' => 'YEN',
			'€' => 'EUR',
		));
		
		foreach($convert as $a => $symbol) {
			if($abbr === $a) {
				return $symbol;
			}
		}
		
		throw new InternationalizationException("Unrecognized currency abbreviation: $abbr");
	}
	
}

function gxl($string) {
	return i18n::gtag($string);
}

/**
 * Returns translated string from tag, and runs sprintf with the extra arguments
 *
 * @param 	string	translation tag
 * @param 	any number of variables to be inserted into the translation text using sprintf
 * @return  string 	translated text
 * @author 	Alex King
 **/
function gxls() {
	$arguments = func_get_args();
	$string = gxl(array_shift($arguments));
	
	return vsprintf($string, $arguments);
}

function pxl($string) {
	return i18n::ptag($string);
}

