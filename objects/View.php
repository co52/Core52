<?php

# set recursion limits for regexes
ini_set('pcre.backtrack_limit', '10000000');
ini_set('pcre.recursion_limit', '10000000');


/**
 * Core52 View Class
 *
 * This View class evolved from a view class originally written for Glarity.
 * Rewritten after much blood, tears and experience, this is a C52 staple.
 * Documentation started by "Jake A. Smith" <jake@companyfiftytwo.com> when
 * he couldn't remember how to use the holder method.
 *
 * @author "David Boskovic" <dboskovic@companyfiftytwo.com>
 * @package Core52
 * @version 2.0
 * @todo Document Load_String
 * @todo Document Holder
 * @todo Document Parse
 * @todo Document Global_Data
 *
 *
 * [2009-09-01: REVISION 31 - "Jake A. Smith" <jake@companyfiftytwo.com>]
 * [new] You can now utilize only a module with Load() using syntax "file_name::module_name"
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 **/

interface ViewInterface {
	public function Load($file = false);
	public function Load_String($str);
	public function Parse();
	public function Data($var, $val = '');
	public function Global_Data($var, $val = NULL);
	public function Holder($holder, $file, $do_includes=false);
	public function Publish($return = false);
}

class View {

	/**
	 * @var ViewObject
	 */
	private static $instance;
	public static $global_data;

	public static function instance($reset = FALSE) {
		if($reset || !is_object(self::$instance)) {
			self::$instance = new ViewObject();
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
		return self::instance()->Publish($print);
	}

	public static function Data($var, $val = '') {
		return self::instance()->Data($var, $val);
	}

	public static function Global_Data($var, $val = NULL) {
		return self::instance()->Global_Data($var, $val);
	}

}

class ViewObject {

	private $parse;
	private $is_parsed = FALSE;
	public $data = array();

	private $code = '';

	public function __construct($file = FALSE) {
		if($file) $this->Load($file);
	}
	
	public function __toString() {
		$this->parse();
		return $this->publish(TRUE);
	}

	/**
	 * Load in a template by path and name. Path starts in
	 * the dir set by constant PATH_VIEWS and the extension
	 * by constant TPL_EXT.
	 *
	 * @static
	 * @param string $file File path and name
	 * @return NULL
	 * @author David Boskovic
	 */
	public function Load($file = false) {

		if(strstr($file, '::'))
			list($file, $module) = explode('::', $file);

		$content = $this->_require_file($file);

		preg_match_all("/(<!--MODULE ([\w ]+)-->)([\s\S]*?)(<!--END[ ]*MODULE \\2-->)/i", $content, $modmatch, PREG_SET_ORDER);
		$matched = false;
		foreach ($modmatch as $mval) {
			if($mval[2] == trim($module)) {
				$content = $mval[3];
			}
		}

		$this->code = $content;
		$this->_include_files();
		$this->_add_elements();
	}

	public function Load_String($str) {

		$this->code = $str;
		$this->_include_files();
		$this->_add_elements();
	}

	public function Holder($holder, $file) {

		$content =& $this->code;
		$insert = $this->_get_file($file);
		if($insert) {
			$content = str_replace("%holder:$holder%", $insert, $content);
			$content = str_replace("%holder:{$holder}%", $insert, $content);
		}
		$this->_include_files();
		$this->_add_elements();
	}

	public function Parse()	{
		if($this->is_parsed) return;
		$this->parse['loops'] = $this->_parse_loops();
		$this->parse['vars'] = $this->_parse_vars();
		$this->parse['conditions'] = $this->_parse_conditions();
		$this->is_parsed = TRUE;
	}

	/**
	 * Take the current template and all the variables
	 * and publish it. If $return is true, instead of
	 * printing the output it is, you guessed it, returned.
	 *
	 * @static
	 * @param bool $return
	 * @return string
	 * @author David Boskovic
	 */
	public function Publish($return = false) {
		// merge data with global data
		$this->Data(View::$global_data);

		// create a shortcut to our parsed out variables
		$parse =& $this->parse;

		// create a shortcut to our source code
		$source =& $this->code;

		// get the values for the parsed vars
		$values = $this->_map_values($parse['vars']);

		// load those values into the template
		$this->_load_vars($values);

		$this->_publish_loops(false, $source, $this->data);

		// get the values for the parsed condition vars
		$cond_values = $this->_map_values($parse['conditions']);

		// publish conditions
		$this->_publish_conditions($cond_values);

		// replace sid vars
		$this->_add_sid();

		$code = $this->code;
		$this->parse = NULL;
		$this->data = array();
		$this->code = '';

		// print or return the final product
		if($return) return $code;
		else print $code;
		return true;
	}

	private function _publish_loops($loops = false, &$source = false, $data = false) {

		if (!$loops) {
			// create a shortcut to our parsed out variables
			$loops =& $this->parse['loops'];
	
			// create a shortcut to our source code
			$source =& $this->code;
		}
// print_r($loops);

		// now loop through each loop
		foreach((array)$loops as $loop) {

			// for holding the published loop code before sending it to the template
			$lc = '';


			// get the AS var or default to "loop"
			$as = $loop['as'] ? $loop['as'] : 'loop';

			// data for loop from template
			$ld = $this->_get_value($loop['label'], $this->data);

			// data for loop from template
			$ld = $data && $this->_get_value($loop['label'], $data) != false ? $this->_get_value($loop['label'], $data) : $this->_get_value($loop['label'], $this->data);

			// make sure we have data to foreach through
			if(is_array($ld) && count($ld) > 0) {

				// initiate our loop info stuff
				$li = array(
					'iteration' => 0,
					'type' => 'even',
					'first' => false,
					'last' => false,
					'count' => count($ld),
				);

				// iterate our loop
				foreach((array)$ld as $rd) {

					// inform our loop of where we are in everything
					++$li['iteration'];		# increment our iteration
					$li['type'] = $li['type'] == 'even' ? 'odd' : 'even';	# decide whether we're on an even line or an odd line
					$li['first'] = $li['iteration'] == 1 ? true : false;	# decide whether we're on the first iteration or not
					$li['last'] = $li['count'] == $li['iteration'] ? true : false;	# decide whether we're on the last iteration or not

					// create iteration variable (starts at 1)
					if(is_array($rd)) $rd['iteration'] = $li['iteration'];

					// assign our AS ability
					$rd = array($as => $rd);
					if (is_array($data)) {
						$rd = array_merge($rd, $data);
					}
					
					// this is the code we want to manipulate
					$code = $loop['content'];

					# VARIABLES ----------------------------------------------------

						// get the values for the parsed vars
						$values = $this->_map_values($loop['vars'], array_merge(
							array('_view_iteration'=>$li['iteration'], '_view_loop_id'=>$id), $rd));
						$values['_loop_iteration'] = $li['iteration'];
						$values['_view_loop_id'] = $id;
						// $values = $this->_map_values($loop['vars']);


						// load the vars into the code
						$this->_load_vars($values, $code);

					# EXCEPTIONS ---------------------------------------------------

						// loop through our exceptions and make our decisions
						foreach( (array) $loop['exceptions'] as $type => $exceptions) {
							foreach((array)$exceptions as $when => $exception) {
								foreach((array)$exception as $number => $value) {

									// default to nothing if the cases don't work out
									$replacement = '';

									// go through our options here
									switch($type) {
										case 'except' :
											// show value always except when you're on the last iteration of a loop
											if($when == 'last' && $li['last'] != true) $replacement = $value;

											// show value always except when you're on the first iteration of a loop
											if($when == 'first' && $li['first'] != true) $replacement = $value;

											// show value always except when you're on the even iteration of a loop
											if($when == 'even' && $li['type'] != 'even') $replacement = $value;

											// show value always except when you're on the odd iteration of a loop
											if($when == 'odd' && $li['type'] != 'odd') $replacement = $value;
										break;
										case 'only' :
											// show value only when you're on the last iteration of a loop
											if($when == 'last' && $li['last'] == true) $replacement = $value;

											// show value only when you're on the first iteration of a loop
											if($when == 'first' && $li['first'] == true) $replacement = $value;
											
											// show value only when you're on the third iteration of a loop
											if(($li['iteration'] % 3) == 0) $replacement = $value;

											// show value only when you're on the even iteration of a loop
											if($when == 'even' && $li['type'] == 'even') $replacement = $value;

											// show value only when you're on the odd iteration of a loop
											if($when == 'odd' && $li['type'] == 'odd') $replacement = $value;

											// show value only when you're on the odd iteration of a loop
											if($when == 'right' && $rd[$as]['_right']) $replacement = $value;
										break;
									}
									$code = str_replace("{{$type}:{$when}!{$number}}", $replacement, $code);
								}
							}
						}

					# CONDITIONS ---------------------------------------------------
						if(count($loop['conditions']) > 0) {
						//print $loop['as'];
						//print $code;
						//print_r($loop['conditions']);

							// get the values for the parsed condition vars
							$cond_values = $this->_map_values($loop['conditions'], $rd);
							//print_r($cond_values);

							$this->_publish_conditions($cond_values, $code);
						}
					# SUB LOOPS ---------------------------------------------------
						if($loop['loops']) {
							$data[$loop['as']] = (array) $data[$loop['label']][$li['iteration'] -1];
// print_r($li['iteration']);
// print_r($code);
// print_r($subdata);
							$this->_publish_loops($loop['loops'], $code, $data);
							unset($data[$loop['as']]);
						}



					// add this line to our code output
					$lc .= $code;
				}
			}
			// otherwise the loop is empty so we want to display the default
			else {
				// change our source code to the empty option
				$code = $loop['empty'];

				// get the values for the parsed vars
				$values = $this->_map_values($loop['emptyvars']);

				// load those values into the template
				$this->_load_vars($values, $code);

				// add this line to our code output
				$lc .= $code;
			}
			$source = str_replace("{!loop:{$loop['label']}}", $lc, $source);
		}

	}

	/**
	 * Set template variables. You can either set a
	 * single variable using $var as key and $val as
	 * value or you can set multiple keys and values
	 * by passing $var an array.
	 *
	 * @static
	 * @param string or array $var
	 * @param string $val
	 * @return NULL
	 * @author David Boskovic
	 */
	public function Data($var, $val = NULL) {

		if(is_array($var)) {
			foreach($var as $v => $val) {
				$this->Data($v, $val);
			}
			return;
		}

		$path = explode(":", $var);
		$path = array_reverse($path);

		$array = array();

		foreach((array)$path as $key => $item) {
			if($key == 0) {
				$array = array($item => $val);
			} else {
				$array = array($item => $array);
			}
		}

		$this->data = true ? my_array_merge($this->data, $array) : array_merge_recursive($this->data, $array);
	}

	public function Global_Data($var, $val = NULL) {

		if(is_array($var)) {
			foreach($var as $v => $val) {
				$this->Global_Data($v, $val);
			}
			return;
		}

		View::$global_data[$var] = $val;
	}

	private function _parse_loops(&$content = false, $sub = false) {

		$loops = array();

		if(!$content) $content =& $this->code;
		// if($sub) print($content);

		// parse out the loops
		preg_match_all(
			"/(<!--LOOP ([\w: ]+)[ ]*(AS)*[ ]*([\w]*)-->)([\s\S]*?)(<!--END[ ]*LOOP \\2-->)/i", //regex
			$content, // source
			$matches, // variable to export results to
			PREG_SET_ORDER|PREG_OFFSET_CAPTURE // settings
		);
		// if($sub) print_r($matches);
		// loop through the loops
		foreach((array)$matches as $match) {

			$content = str_replace($match[0][0], strtolower("{!loop:{$match['2'][0]}}"), $content);

			// split the content into default and empty options if there <!--EMPTY--> tag is there
			list($cd, $ce) = explode("<!--EMPTY {$match[2][0]}-->",$match[5][0]);

			$loop = array(
				"label" => $match[2][0],
				"as" => strlen($match[3][0]) == 2 ? $match[4][0] : false,
				"content" => $cd,
				"empty" => $ce ? $ce : ' ',
				"startpos" => $match[0][1],
				"endpos" => strlen($match[0][0]) + $match[0][1],
				"length" => strlen($match[0][0])
			);

			foreach((array)$loops as $otherloop) {
				// this skips the loop if it's nested
				if($loop['startpos'] > $otherloop['startpos'] && $loop['startpos'] < $otherloop['endpos']) continue;
			}

			if(($subloops = $this->_parse_loops($loop['content'], true))) {
				$loop['loops'] = $this->_parse_loops($loop['content']);
			}
						
			$loop['vars'] = $this->_parse_vars($loop['content']);
			$loop['emptyvars'] = $this->_parse_vars($loop['empty']);
			$loop['exceptions'] = $this->_parse_exceptions($loop['content']);
			$loop['conditions'] = $this->_parse_conditions($loop['content']);
			$loops[] = $loop;
		}
		$loops = count($loops) > 0 ? $loops : false;
// print_r($loops);
		return $loops;
	}

	private function _parse_vars($content = false) {

		if(!$content) $content =& $this->code;

		// parse out the variables
		preg_match_all(
			"/{([\w: ]+?)}/", //regex
			$content, // source
			$matches_vars, // variable to export results to
			PREG_SET_ORDER // settings
		);
// print_r($matches_vars);
		foreach((array)$matches_vars as $var) {
			$vars[] = $var[1];
		}

		return $vars;
	}

	private function _map_values($map, $data = false) {

		if(!$data) $data =& $this->data;

		$values = array();

		$map = (array) $map;

		foreach((array)$map as $var) {
			$value = $this->_get_value($var, $data);

			// if it has an array, check to see if the _default item is set and print that, otherwise give an empty string
			if($l == $count && is_array($value)) {
				$value = isset($value['_default']) ? $value['_default'] : true;
			}
			$values[$var] = $value;
		}
		return $values;

	}

	private function _get_value($path, $data) {
		$i1 = stripos($path, '[');
		if ($i1 !== false) {
			$i2 = stripos($path, ']');
			if ($i2 !== false) {
				$subVal = substr($path, $i1 + 1, $i2 - $i1 - 1);
				$val = $this->_get_value($subVal, $data);
				$path = str_replace("[$subVal]", $val, $path);
				#print("$path<br>");
			}
		}

		$path_was = $path;
		$path = explode(":", $path);
		$count = count($path);
		$current = $data;
		$l = 1;
		foreach((array)$path as $item) {

			// make sure the variable we're looking for is actually set
			if(is_object($current) && isset($current->$item)) {
				$current = $current->$item;
			}
			elseif(is_array($current) && isset($current[$item])) {
				$current = $current[$item];
			}
			else {
				$current = ''; break;
			}
			// if we're at the end of our path, stop here.
			if($l == $count) break;

			// increment loop counter
			++$l;
		}
		return $current;
	}
	
	private function _parse_exceptions(&$content = false) {

		if(!$content) $content =& $this->code;

		// parse out all the exceptions
		preg_match_all(
			"/(<!--(EXCEPT|ONLY) ([\w: ]+)-->)([\s\S]*?)(<!--END[ ]*\\2 \\3-->)/i", //regex
			$content, // source
			$matches_ex, // variable to export results to
			PREG_SET_ORDER // settings
		);

		$excount = 0;

		foreach((array)$matches_ex as $ex) {
			$exceptions[strtolower($ex[2])][strtolower($ex[3])][$excount] = $ex[4];
			$content = str_replace($ex[0], strtolower("{{$ex['2']}:{$ex['3']}!{$excount}}"), $content);
			++$excount;
		}

		// parse out the other way of calling exceptions
		preg_match_all(
			"/{(except|only):([\w]+) ([^}]+)}/i", //regex
			$content, // source
			$matches_exc, // variable to export results to
			PREG_SET_ORDER // settings
		);

		foreach((array)$matches_exc as $ex) {
			$exceptions[strtolower($ex[1])][strtolower($ex[2])][$excount] = $ex[3];
			$content = str_replace($ex[0], strtolower("{{$ex['1']}:{$ex['2']}!{$excount}}"), $content);
			++$excount;
		}

		return $exceptions;
	}


	private function _parse_conditions($content = false) {

		if(!$content) $content =& $this->code;

		// parse out all the exceptions
		preg_match_all(
			"/(<!--IF ([\w: ]+)-->)([\s\S]*)(<!--END[ ]*IF \\2-->)/i", //regex
			$content, // source
			$matches_ex, // variable to export results to
			PREG_SET_ORDER // settings
		);

		$excount = 0;
		$conditions = array();
		foreach((array)$matches_ex as $ex) {
			$conditions[] = $ex[2];
			++$excount;
			$conditions = array_merge($conditions, $this->_parse_conditions($ex[3]));
		}
		return $conditions;
	}


	private function _publish_conditions($cond_values, &$content = false) {
		if(!$content) $content =& $this->code;
		//print $content;
		// we parse again BECAUSE we want to do the switch after whatever it is has taken place in the template
		// parse out all the exceptions
		preg_match_all(
			"/(<!--IF ([\w: ]+)-->)([\s\S]*?)(<!--END[ ]*IF \\2-->)/i", //regex
			$content, // source
			$matches_ex, // variable to export results to
			PREG_SET_ORDER // settings
		);
		//print_r($matches_ex);
		foreach((array)$matches_ex as $ex) {
			// split the content into default and secondary options if the <!--ELSE condition--> tag is there

			$this->_publish_conditions($cond_values, $ex[3]);
			list($cd, $ce) = explode("<!--ELSE {$ex[2]}-->",$ex[3]);
			if (array_key_exists($ex[2], $cond_values)) {
				$replace = !is_blank($cond_values[$ex[2]]) ? $cd : $ce;		# WAS: $replace = strlen($cond_values[$ex[2]]) > 0 ? $cd : $ce;
				$content = str_replace($ex[0], $replace, $content);
			}

		}
	}

	private function _require_file($file = false)	{

		// if the file wasn't passed, throw an exception
		if(!$file)	throw new Exception("No template provided to load");

		// declare file location
		$location = PATH_VIEWS.$file.TPL_EXT;

		// if the file doesn't exist, throw an exception
		if(!file_exists($location)) throw new Exception("The template file $location does not exist");

		// save the code the object
		return file_get_contents($location);
	}

	private function _get_file($file = false)	{

		// if the file wasn't passed, throw an exception
		if(!$file)	return false;

		// declare file location
		$location = PATH_VIEWS.$file.TPL_EXT;

		// echo $location;

		// if the file doesn't exist, throw an exception
		if(!file_exists($location)) return false;

		// save the code the object
		return file_get_contents($location);
	}

	private function _include_files() {
		$content =& $this->code;

		preg_match_all("/<!--@([\w\.\/\-: ]+)-->/", $content, $matches, PREG_SET_ORDER);

		foreach ($matches as $val) {
			if(strstr($val[1], '::')) {
				list($file, $module) = explode('::', $val[1]);
			}
			else {
				$file = $val[1];
			}

			if (( $code = $this->_get_file($file))) {

				if($module) {
					$this->_extract_module($module, $code);
				}
				$content = str_replace($val[0], $code, $content);
				$this->_include_files();
			}
			else {
				$content = str_replace($val[0], $val[0].'<!--file could not be found-->' , $content);
			}

		}
		//print_r($matches);
		//$this->template[$tpl]['toggles'] = $toggles;
	}

	private function _add_sid() {
		$content =& $this->code;

		# make sure this project is using the session functionality and that
		if(class_exists('Session') && (Session::$variable == 'uri' && Session::$sid)) {
			$content = str_replace('?SID', '?sid='.Session::$sid, $content);
			$content = str_replace('&SID', '&sid='.Session::$sid, $content);
		}
		else {
			$content = str_replace('?SID', '', $content);
			$content = str_replace('&SID', '', $content);
		}
	}


	private function _add_elements() {
		$content =& $this->code;

		preg_match_all("/<!--ADD ELEMENT ([\w\.\/: ]+)-->/i", $content, $matches, PREG_SET_ORDER);

		foreach ($matches as $val) {

			$css = '<link rel="stylesheet" type="text/css" media="all" href="{src:elements}css/'.$val[1].'"/>';
			$js = '<script type="text/javascript" src="{src:elements}js/'.$val[1].'"></script>';
			if(substr($val[1], -2) == 'js') {
				$use = '	'.$js.'
</head>';
			}
			elseif(substr($val[1], -3) == 'css') {
				$use = '	'.$css.'
</head>';
			}

			$content = str_replace('</head>',$use, $content);
			$content = str_replace($val[0], '' , $content);
		}
		//print_r($matches);
		//$this->template[$tpl]['toggles'] = $toggles;
	}

	private function _extract_module($module = false, &$content = false) {

		if(!$content) $content =& $this->code;

		if($module) {
			preg_match_all("/(<!--MODULE ([\w ]+)-->)([\s\S]*?)(<!--END[ ]*MODULE \\2-->)/i", $content, $modmatch, PREG_SET_ORDER);
			$matched = false;
			foreach ($modmatch as $mval) {
				if($mval[2] == trim($module)) {
					$content = $mval[3];
					return true;
				}
			}
		}
		return false;
	}



	private function _load_vars($vars, &$source = false) {

		if(!$source) $source =& $this->code;

		foreach((array)$vars as $key => $var) {
			$key = '{'.$key.'}';
			if(strpos($source, $key) !== FALSE) {
				$source = str_replace($key, (string) $var, $source);
			}
		}

	}

}

function my_array_merge ($arr,$ins)
    {
        if(is_array($arr))
            {
                if(is_array($ins)) foreach($ins as $k=>$v)
                    {
                        if(isset($arr[$k])&&is_array($v)&&is_array($arr[$k]))
                            {
                                $arr[$k] = my_array_merge($arr[$k],$v);
                            }
                        else $arr[$k] = $v;
                    }
            }
        elseif(!is_array($arr)&&(strlen($arr)==0||$arr==0))
            {
                $arr=$ins;
            }
        return($arr);
    }
    

function view_inc($file, array $vars = array()) {
	$view = new ViewObject();
	$view->Load($file);
	$view->Parse();
	$view->Data($vars);
	$view->Publish();
}