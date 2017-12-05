<?php

core_set_exception_handler(array('ErrorCore', 'handle_exception'));

set_exception_handler('core_handle_exception');
set_error_handler(array('ErrorCore', 'handle_error'), E_ALL ^ E_NOTICE);
assert_options(ASSERT_ACTIVE, 0);
ini_set('track_errors', 'On');


class FatalErrorException extends ErrorException {}
class DatabaseException extends Exception {}
class DatabaseConnectionException extends Exception {}
class PageNotFoundException extends Exception {}
class AccessDeniedException extends Exception {}
class SecurityException extends Exception {}
class XSRFSecurityException extends SecurityException {}
class UploadException extends Exception {}
class FormException extends Exception {}


class ErrorCore {

	const DISPLAY_HALT = 1;
	const DISPLAY_CONTINUE = 2;
	const SILENT_CONTINUE = 4;
	
	
	public static function _print_error_header() {

		$core_rev = svn_get_version(PATH_CORE, FALSE);
		$app_rev = svn_get_version(PATH_APP, FALSE);
		
?>
<html>
<head>
<title>System Error</title>
<style type="text/css">
	span.value { font-style:italic; }
	span.a_key, span.o_key { font-weight:bold; }
	pre, code { display:block; padding:12px; margin:12px 0; background:#eeeeee; border-top:3px solid #ccc; border-bottom:3px solid #ccc; }
	ol { margin-left:0; }
	li { margin-left:0px; padding-bottom:12px; }
	h1, h2, h3 { margin-top:25px; margin-bottom:18px; }
</style>
</head>
<body id="main">
	<div style="padding:40px;">
		<p><em>Running Core52 rev. <?=$core_rev;?>, app rev. <?=$app_rev;?></em></p>
<?php

	}
	
	
	public static function _print_error_backtrace($exception) {

?>
		<h2>Backtrace:</h2>
		
		<ol>
<?php
			
			$cnt = 0;
			#print_r($exception->getTrace()); die;
			#$prev_args = array();
			$trace = $exception->getTrace();
			foreach($trace as $i => $line) {
				$args = (array) $line['args'];
				foreach((array) $args as $i => $arg) {
					if(is_array($arg)) {
						$args[$i] = print_r($arg, TRUE);
					}
					elseif(is_object($arg)) {
						$args[$i] = '['.get_class($arg).' object]';
					}
					elseif(is_numeric($arg)) {
						$args[$i] = $arg;
					}
					elseif(is_null($arg)) {
						$args[$i] = 'NULL';
					}
					elseif($arg === TRUE) {
						$args[$i] = 'TRUE';
					}
					elseif($arg === FALSE) {
						$args[$i] = 'FALSE';
					}
					else {
						$args[$i] = "&quot;$arg&quot;";
					}
				}
				$args = implode(', ', $args);
				if(strlen($args) > 128) $args = '[omitted]';
				echo "		<li><b>{$line['class']}{$line['type']}{$line['function']}(</b>$args<b>)</b><br />\n     <i>called at</i> <b>{$line['file']}[{$line['line']}]</b>\n\n</li>";
				$cnt++;
				#$prev_args = $line['args'];
			}
?>
		</ol>
<?php

	}
	
	
	public static function _print_error_footer() {

?>
	</div>
</body>
</html>
<?php

	}
	
	public static function _print_error_message($exception) {

?>
		<h1><?=get_class($exception);?></h1>
		<p>An error has occured in <b><?=$exception->getFile();?></b> line <b><?=$exception->getLine();?></b>:</p>
		<code><?=$exception->getMessage();?></code>
<?php

	}
	
	
	public static function _print_query_backtrace($exception) {
?>
		<h1><?=get_class($exception);?></h1>
		<p>A <b>MySQL Query Error #<?=database()->error_code();?></b> has occured in <b><?=$exception->getFile();?></b> line <b><?=$exception->getLine();?></b>:</p>
		<code><?=$exception->getMessage();?></code>
		<h2>Most Recent Queries:</h2>
		<ol>
		<? foreach(Database::c()->query_history as $i => $q): ?>
			<li><b><?=$q->call;?></b><br />
			<em>Called in</em> <b><?=$q->file;?>[<?=$q->line;?>]</b>
			<code><?=$q->query;?></code></li>
		<? endforeach; ?>
		</ol>
		
<?php
	}
	
	
	public static function handle_exception($exception, $halt = ErrorCore::DISPLAY_HALT) {
		
		try {
	    	
	    	switch(get_class($exception)) {
	    	
	    		case 'PageNotFoundException':
		    		while(ob_get_level()) ob_end_clean();
					self::show_404();
		    	break;
		    		
	    		case 'AccessDeniedException':
	    			while(ob_get_level()) ob_end_clean();
					self::show_403();
	    		break;
	    		
	    		case 'XSRFSecurityException':
	    			while(ob_get_level()) ob_end_clean();
	    			self::show_xsrf();
	    		break;
		    		
		    	case 'DatabaseException':
				case 'DatabaseConnectionException':
		    		ob_start();
		    		self::_print_error_header();
					echo $exception->getMessage();
					
					// Don't show the backtrace when there is a DatabaseConnectionException
					// This keeps the database password from being exposed.
					if (get_class($exception) != 'DatabaseConnectionException') {
						self::_print_error_backtrace($exception);
					}
						
					self::_print_error_footer();
					ErrorCore::report(ob_get_clean(), $exception, $halt);
		    	break;
	    		
		    	case 'ErrorException':
		    	default:
		    		ob_start();
		    		self::_print_error_header();
		    		self::_print_error_message($exception);
					self::_print_error_backtrace($exception);
		    		self::_print_error_footer();
		    		ErrorCore::report(ob_get_clean(), $exception, $halt);
				break;
		    }
		    
		    if($halt == ErrorCore::DISPLAY_HALT) core_halt();
		    
	    }
	    catch (Exception $e) {
	        echo get_class($e)." thrown within the exception handler. Message: <pre>$e</pre>";
	        core_halt();
	    }
	}
	
	
	public static function handle_error($code, $message, $file, $line, $context) {
		
		if(error_reporting() == 0) return TRUE;
	    
	    switch($code) {
			
			case 65536:
				
				throw new DatabaseException($message);
				break;
			
			case E_STRICT:
			case E_NOTICE:

				/*
				if(class_exists('FirePHP') && DEV_MODE){
					$fb = new FirePHP();
	    			$e = new ErrorException($message, 0, $code, $file, $line);
					$fb->fb($e);
				}
				return true;
				*/
				
				return TRUE;
				
			default:
					
				throw new ErrorException($message, $code, 0, $file, $line);
				break;
				
		}
	    
	}
	
	
	public static function show_404() {
		header('http/1.0 404 Not Found');
		$headers = headers_list();
		$mime = 'text/html';
		foreach($headers as $header) {
			if(preg_match('/content-type:/i', $header)) {
				list(, $mime) = explode(':', $header);
			}
		}
		if($mime == 'text/html') {
			if(file_exists(PATH_VIEWS .'http/404'. TPL_EXT)) {
				View::Load('http/404');
				View::Parse();
				View::Publish();
			} elseif(file_exists(PATH_VIEWS .'errors/404'. TPL_EXT)) {
				View::Load('errors/404');
				View::Parse();
				View::Publish();
			} else {
				echo '<h1>HTTP 404 Page Not Found</h1>';
			}
		}
		core_halt();
	}
	
	
	public static function show_403() {
		header('http/1.0 403 Forbidden');
		$headers = headers_list();
		$mime = 'text/html';
		foreach($headers as $header) {
			if(preg_match('/content-type:/i', $header)) {
				list(, $mime) = explode(':', $header);
			}
		}
		if($mime == 'text/html') {
			if(file_exists(PATH_VIEWS .'http/403'. TPL_EXT)) {
				View::Load('http/403');
				View::Parse();
				View::Publish();
			} elseif(file_exists(PATH_VIEWS .'errors/403'. TPL_EXT)) {
				View::Load('errors/403');
				View::Parse();
				View::Publish();
			} else {
				echo '<h1>HTTP 403 Forbidden</h1>';
			}
		}
		core_halt();
	}
	
	
	public static function show_xsrf() {
		if(Router::is_ajax()) {
			
			// try not to break AJAX applications
			json_output((object) array(), 'This page has expired, please refresh the page and try again.');
			
		} else {
			
			// determine the page MIME type
			header('http/1.0 403 Forbidden');
			$headers = headers_list();
			$mime = 'text/html';
			foreach($headers as $header) {
				if(preg_match('/content-type:/i', $header)) {
					list(, $mime) = explode(':', $header);
				}
			}
			
			if($mime == 'text/html') {
				if(file_exists(PATH_VIEWS .'http/xsrf'. TPL_EXT)) {
					View::Load('http/xsrf');
					View::Parse();
					View::Publish();
				} elseif(file_exists(PATH_VIEWS .'errors/xsrf'. TPL_EXT)) {
					View::Load('errors/xsrf');
					View::Parse();
					View::Publish();
				} else {
					echo sprintf('<h1>This page has expired</h1><p>Please <a href="%s">click here</a> to reload the page and try again.</p>', Router::url());
				}
			}
		}
		core_halt();
	}
	
	
	public static function report($html, $e = NULL, $halt = ErrorCore::DISPLAY_HALT) {
		
		$email_debug_rcpt = Config::get('EMAIL_DEBUG_RCPT', FALSE);
		if(!empty($email_debug_rcpt)) {
			
			$mailer = Mailer::factory();
			
			if(PHP_SAPI === 'cli') {
				$mailer->Subject = sprintf(
					'%s script error in %s',
					defined('APP_NAME')? APP_NAME : "Core52",
					$_SERVER['PHP_SELF']
				);
			} else {
				$mailer->Subject = sprintf(
					'%s page error in %s',
					defined('APP_NAME')? APP_NAME : "Core52",
					Router::url()
				);
			}
			
			$mailer->Body = $html;
			$mailer->AltBody = "An unexpected error occurred!\n\n";
			
			$attachments = array();
			$attachments['session.html'] = (defined('ENABLE_LEGACY_SESSIONS'))? dump(Session::$data) : dump($_SESSION);
			if(count($_GET))  $attachments['get.html']  = dump($_GET);
			if(count($_POST)) $attachments['post.html'] = dump($_POST);
			if(count($_SERVER)) $attachments['server.html'] = dump($_SERVER);
			foreach($attachments as $filename => $contents) {
				$mailer->AddStringAttachment($contents, $filename);
			}
			
			foreach((array) Config::get('EMAIL_DEBUG_RCPT') as $addr) {
				$mailer->AddAddress($addr);
			}
			if(Session::get(FALSE)) {
				$mailer->From = Session::user()->email;
			}
			
			if(defined('QUEUE_ERROR_EMAILS')) {
				$mailer->queue();
			} else {
				$mailer->send();
			}
			
			if($halt === ErrorCore::DISPLAY_HALT || $halt === ErrorCore::DISPLAY_CONTINUE) {
				if(PHP_SAPI === 'cli' && $e instanceof Exception) {
					echo str_repeat('=', 80).PHP_EOL;
					echo $e;
					echo PHP_EOL.str_repeat('=', 80).PHP_EOL;
				} elseif(PHP_SAPI != 'cli' && !Router::is_ajax() && file_exists(PATH_VIEWS.'errors/error.php')) {
					include(PATH_VIEWS.'errors/error.php');
				} else {
					echo "\n\nAn unexpected error occurred!\n\n";
				}
				
				if($halt === ErrorCore::DISPLAY_HALT) {
					core_halt();
				}
			}
			
		} else {
			
			$buf_status = ob_get_status(TRUE);
			
			if($halt === ErrorCore::DISPLAY_HALT || $halt === ErrorCore::DISPLAY_CONTINUE) {
				if(PHP_SAPI === 'cli' && $e instanceof Exception) {
					echo str_repeat('=', 80).PHP_EOL;
					echo $e;
					echo PHP_EOL.str_repeat('=', 80).PHP_EOL;
				} else {
					echo $html;
				}
				
				if($halt === ErrorCore::DISPLAY_HALT) {
					core_halt();
				}
			}
		}
	}

	
}
	

if(!function_exists('dump')) {
	function dump(&$var, $info = FALSE)	{
    	$scope = false;
	    $prefix = 'unique';
    	$suffix = 'value';

	    if($scope) $vals = $scope;
    	else $vals = $GLOBALS;

	    $old = $var;
    	$var = $new = $prefix.rand().$suffix; $vname = FALSE;
	    foreach($vals as $key => $val) if($val === $new) $vname = $key;
    	$var = $old;

	    ob_end_flush();
    	ob_start();
	    if($info != FALSE) echo "<b style='color: red;'>$info:</b><br>";
    	do_dump($var, '$'.$vname);
	    $buf = ob_get_clean();
    	return $buf;
    }
}

if(!function_exists('do_dump')) {
	////////////////////////////////////////////////////////
	// Function:         do_dump
	// Inspired from:     PHP.net Contributions
	// Description: Better GI than print_r or var_dump
	
	function do_dump(&$var, $var_name = NULL, $indent = NULL, $reference = NULL) {
		
	    $do_dump_indent = "<span style='color:#eeeeee;'>|</span> &nbsp;&nbsp; ";
	    $reference = $reference.$var_name;
	    $keyvar = 'the_do_dump_recursion_protection_scheme'; $keyname = 'referenced_object_name';
	
	    #$var = '';
	    if (is_array($var) && isset($var[$keyvar]))
	    {
	        $real_var = &$var[$keyvar];
	        $real_name = &$var[$keyname];
	        $type = ucfirst(gettype($real_var));
	        echo "$indent$var_name <span style='color:#a2a2a2'>$type</span> = <span style='color:#e87800;'>&amp;$real_name</span><br>";
	    }
	    else
	    {
	        $var = array($keyvar => $var, $keyname => $reference);
	        $avar = &$var[$keyvar];
	
	        $type = ucfirst(gettype($avar));
	        if($type == "String") $type_color = "<span style='color:green'>";
	        elseif($type == "Integer") $type_color = "<span style='color:red'>";
	        elseif($type == "Double"){ $type_color = "<span style='color:#0099c5'>"; $type = "Float"; }
	        elseif($type == "Boolean") $type_color = "<span style='color:#92008d'>";
	        elseif($type == "NULL") $type_color = "<span style='color:black'>";
	
	        if(is_array($avar))
	        {
	            $count = count($avar);
	            echo "$indent" . ($var_name ? "$var_name => ":"") . "<span style='color:#a2a2a2'>$type ($count)</span><br>$indent(<br>";
	            $keys = array_keys($avar);
	            foreach($keys as $name)
	            {
	                $value = &$avar[$name];
	                do_dump($value, "['$name']", $indent.$do_dump_indent, $reference);
	            }
	            echo "$indent)<br>";
	        }
	        elseif(is_object($avar))
	        {
	            echo "$indent$var_name <span style='color:#a2a2a2'>$type</span><br>$indent(<br>";
	            foreach($avar as $name=>$value) do_dump($value, "$name", $indent.$do_dump_indent, $reference);
	            echo "$indent)<br>";
	        }
	        elseif(is_int($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color$avar</span><br>";
	        elseif(is_string($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color\"$avar\"</span><br>";
	        elseif(is_float($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color$avar</span><br>";
	        elseif(is_bool($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color".($avar == 1 ? "TRUE":"FALSE")."</span><br>";
	        elseif(is_null($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> {$type_color}NULL</span><br>";
	        else echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $avar<br>";
	
	        $var = $var[$keyvar];
	    }
	}
}


