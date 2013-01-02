<?php

/**
 * YouTube screen scraper
 *
 * Gets the title, description, and image URL for a YouTube video
 *
 * @author "Jonathon Hill" <jhill@companyfiftytwo.com>
 * @package Core52
 * @version 1.0
 */
class Youtube {
	
	public $key;
	public $url;
	public $title;
	public $description;
	public $image;
	
	private $_fetched = FALSE;
	private $_image = 'http://i1.ytimg.com/vi/{key}/default.jpg';
	private $_embed = '<object width="{width}" height="{height}"><param name="movie" value="http://www.youtube.com/v/{key}&hl=en&fs=1&rel=0"></param><param value="opaque" name="wmode"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/{key}&hl=en&fs=1&rel=0" type="application/x-shockwave-flash" allowscriptaccess="always" wmode="opaque" allowfullscreen="true" width="{width}" height="{height}"></embed></object>';
	private $_embed_alt = '<object width="{width}" height="{height}"><param name="movie" value="http://www.youtube.com/swf/l.swf?video_id={key}&rel=0&eurl=&iurl=http%3A//i2.ytimg.com/vi/{key}/hqdefault.jpg"></param><param value="opaque" name="wmode"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param></object>';
	
	function __construct($url, $fetch = FALSE) {
		$this->url = $url;
		$this->key = $this->_extract_key($this->url);
		$this->image = str_replace(array('{key}'), array($this->key), $this->_image);
		
		if($fetch == TRUE) $this->fetch();
	}
	
	function fetch() {
		$this->_fetched = FALSE;
		$body = CURL::get($this->url);
		if($body !== FALSE) {
			$this->description = $this->_extract_description($body);
			$this->title = $this->_extract_title($body);
			$this->_fetched = TRUE;
		}
	}
	
	function fetched() {
		return $this->_fetched;
	}
	
	function embed($width = 200, $height = 130) {
		return str_replace(
			array('{key}', '{width}', '{height}'), 
			array($this->key, $width, $height), 
			$this->_embed
		);
	}
	
	private function _extract_key($url) {
		$q = parse_query_string($url);
		return $q['v'];
	}
	
	private function _extract_title($body) {
		$i = stripos($body, '<meta name="title" content="') + 28;
		$j = stripos($body, '">', $i);
		return trim(substr($body, $i, $j-$i));
	}
	
	private function _extract_description($body) {
		$desc_i = stripos($body, 'watch-video-desc description');
		if($desc_i !== FALSE) {
			$desc_start_i = stripos($body, '<span', $desc_i);
			$desc_end_i = stripos($body, '</span>', $desc_start_i);
			$description = trim(strip_tags(substr($body, $desc_start_i, $desc_end_i-$desc_start_i)));
		}
		return $description;
	}
}