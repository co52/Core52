<?php

/**
 * Core52 Page Class
 *
 * A simple paging class
 *
 * @author "Jonathon Hill" <jhill@companyfiftytwo.com>
 * @package Core52
 * @version 1.0
 *
 **/

class Page {

	public static $current;		// current page number
	public static $offset;		// database record starting position
	public static $perpage;		// items to show per page
	public static $pages;		// number of pages
	public static $items;		// number of items
	public static $key;			// key of $_GET variable indicating the current page
	public static $url_parts;	// parsed URL data
	public static $script_url;	// url of this page (without the query str);
	public static $get = FALSE; // using $_GET param instead of URL segment
	
	public static $first;		// bool
	public static $last;		// bool
	

	public static function init($items, $per_page = 25, $key = 'pg', $url = NULL) {
		
		if(is_null($url)) {
			$url = urldecode(Router::url());
		}
		
		// load the class properties
		self::$key = $key;
		self::$perpage = $per_page;
		self::$items = $items;
		self::$url_parts = parse_url($url);
		self::$script_url = self::$url_parts['path'];
		
		// determine what page we are on
		if($key[0] === '?') {
			self::$key = $key = ltrim($key, '?');
			self::$current = (int) $_GET[$key];
			self::$get = TRUE;
		} else {
			foreach(explode('/', self::$script_url) as $segment) {
				list($pg, $num) = explode(':', $segment);
				if($pg == $key && (int) $num > 0) {
					self::$current = (int) $num;
					break;
				}
			}
		}
		if(self::$current <= 0) self::$current = 1;
		
		// run the page calculations
		self::$pages = ($items > 0)? (int) ceil($items / $per_page) : 1;
		self::$offset = (self::$current - 1) * $per_page;

		// Router::redirect to the first page if the page no. is out of range
		if((self::$current > self::$pages) || (self::$current < 1)) {
			Router::redirect(self::get_url(1));
		}
		
		if(self::$current == 1) self::$first = true;
		if(self::$current == self::$pages) self::$last = true;
	}
	
	
	# return the url of the next page
	public static function next_url($inc = 1) {
		$pg = (self::$current < self::$pages)? self::$current + $inc : self::$current;
		return self::get_url($pg);
	}
	
	
	# return the url of the last page
	public static function prev_url($inc = 1) {
		$pg = (self::$current > 1)? self::$current - $inc : self::$current;
		return self::get_url($pg);
	}
	
	
	# return the url of a specific page
	public static function get_url($page) {
		if(self::$get === TRUE) {
			
			$url = self::$script_url;
			if(self::$url_parts['query']) {
				$url .= '?'.self::$url_parts['query'];
			}
			if(self::$url_parts['fragment']) {
				$url .= '#'.self::$url_parts['fragment'];
			}

			$regex = '/'.self::$key.'=[0-9]+/';
			if(preg_match($regex, $url)) {
				// replace query parameter in the URL
				$url = preg_replace($regex, self::$key.'='.$page, $url);
			} elseif(strpos(rtrim($url, '?'), '?') !== FALSE) {
				// append the query parameter to the URL
				$url .=	'&'.self::$key.'='.$page; // URL has at least one GET parameter in the query
			} else {
				// append the query parameter to the URL
				$url .= '?'.self::$key.'='.$page; // no GET parameters
			}
		} else {
			$regex = '/'.self::$key.'\:[0-9]+/';
			$url = preg_match($regex, self::$script_url)? preg_replace($regex, self::$key.':'.$page, self::$script_url) : rtrim(self::$script_url, '/').'/'.self::$key.':'.$page;
		}
		return $url;
	}
	
	
	# return HTML page links
	public static function page_links($show = 5, $html_first = '<a href="%s">&laquo; First</a>', $html_current = '<b>%s</b>', $html_page = '<a href="%s">%s</a>', $html_last = '<a href="%s">Last &raquo;</a>') {
		
		if(is_array($html_first)) extract($html_first);
		
		$first = (self::$current - $show > 1)? self::$current - $show : 1;
		$last  = (self::$current + $show < self::$pages)? self::$current + $show : self::$pages;
		
		$links = array();
		
		if($first > 1) {
			$links[] = sprintf($html_first, self::get_url(1), 1);
		}
		
		for($i = $first; $i <= $last; $i++) {
			if($i == self::$current) {
				$links[] = sprintf($html_current, $i);
			} else {
				$links[] = sprintf($html_page, self::get_url($i), $i);
			}
		}
		
		if($last < self::$pages) {
			$links[] = sprintf($html_last, self::get_url(self::$pages), self::$pages);
		}
		
		return (count($links) > 1)? implode(' ', $links) : '';
	}
	

	# return text description of result range
	public static function range($fmt = 'Viewing <b>%s</b> to <b>%s</b> of <b>%s</b>') {
		return sprintf(
			$fmt,
			number_format((self::$items > 0)? self::$offset + 1 : self::$offset, 0),
			number_format(((self::$offset + self::$perpage > self::$items)? self::$items : self::$offset + self::$perpage), 0),
			number_format(self::$items, 0)
		);
	}
	
	
	# return the MySQL limit clause for the current page
	public static function limit($fmt = "LIMIT %d, %d") {
		return ($fmt === FALSE)? array(self::$offset, self::$perpage) : sprintf($fmt, self::$offset, self::$perpage);
	}
	
	
	
}