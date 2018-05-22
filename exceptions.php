<?php

core_set_exception_handler(array('ErrorCore', 'handle_exception'));

set_exception_handler('core_handle_exception');
set_error_handler(array('ErrorCore', 'handle_error'), E_ALL ^ E_NOTICE);
assert_options(ASSERT_ACTIVE, 0);
ini_set('track_errors', 'On');

class FatalErrorException extends ErrorException {}
class PageNotFoundException extends Exception {}
class AccessDeniedException extends Exception {}
class SecurityException extends Exception {}
class XSRFSecurityException extends SecurityException {}
class UploadException extends Exception {}
class FormException extends Exception {}

class DatabaseException extends Exception
{
	public $query;
	public $recentQueries = array();

	public static function factory($c)
	{
		$exception = new self($c->error_msg(), $c->error_code());
		$exception->setQuery($c->last_query());
		$exception->setRecentQueries($c->query_history);
		return $exception;
	}

	public function setQuery($query)
	{
		$this->query = $query;
	}

	public function setRecentQueries(array $recentQueries = array())
	{
		$this->recentQueries = $recentQueries;
	}
}

class DatabaseConnectionException extends DatabaseException {}

class ErrorCore
{
	const DISPLAY_HALT = 1;
	const DISPLAY_CONTINUE = 2;
	const SILENT_CONTINUE = 4;
	
	public static function handle_error($code, $message, $file, $line, $context)
    {
		if (error_reporting() == 0)
        {
            return true;
        }

	    switch($code)
        {
			case 65536:
				throw new DatabaseException($message);
				break;

			default:
				throw new ErrorException($message, $code, 0, $file, $line);
				break;
		}
	}
	
	public static function handle_exception($exception, $halt = ErrorCore::DISPLAY_HALT)
	{
        try
        {
			error_log($exception);

			switch (get_class($exception))
			{
				case 'PageNotFoundException':
					return self::show_404();

				case 'AccessDeniedException':
					return self::show_403();

				case 'XSRFSecurityException':
					return self::show_xsrf();
            }
			
			if (Config::get('RAVEN_DSN', false))
            {
                self::handle_exception_raven($exception);
            }
            elseif (Config::get('SHOW_ERROR_TRACE', false))
            {
                self::handle_exception_verbose($exception);
			}
			else
			{
				self::handle_exception_default($exception);
			}
        }
        catch (Exception $e)
        {
            try
            {
                self::handle_exception_default($exception);
            }
            catch (Exception $e)
            {
                error_log($e);
				echo $e;
            }
        }
		
		if ($halt == ErrorCore::DISPLAY_HALT)
		{
			core_halt();
		}
    }

	public static function handle_exception_raven($exception)
    {
        $package = json_decode(file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'package.json'));

        $client = new Raven_Client(Config::get('RAVEN_DSN'), array(
            'name' => PHP_SAPI === 'cli' ? realpath($_SERVER['argv'][0]) : Config::get('HOSTNAME'),
            'release' => $package->version,
            'app_path' => PATH_APP,
        ));

        $client->extra_context(array(
            'php_version' => phpversion(),
            'php_sapi' => PHP_SAPI,
        ));

        if (PHP_SAPI !== 'cli')
        {
            if (!empty($_SESSION))
            {
                $client->extra_context(array(
                    'session' => $_SESSION,
                ));
            }

            $user = User::get_current_user(User::USER_ERROR_FALSE);
            if ($user)
            {
                $client->user_context($user->toArray());
            }
        }

		if ($exception instanceof DatabaseException)
		{
			$recentQueries = array();
			foreach ($exception->recentQueries as $query)
			{
				$key = $query->file . ':' . $query->line;
				$value = (string) $query->query;
				$recentQueries[$key] = $value;
			}

			$client->extra_context(array(
				'errorCode' => $exception->getCode(),
				'errorQuery' => $exception->query,
				'recentQueries' => $recentQueries
			));
		}
		
		$client->captureException($exception);
    }
    
    public static function handle_exception_verbose($exception)
    {
        $whoops = new \Whoops\Run;

        if (PHP_SAPI === 'cli')
        {
            $whoops->pushHandler(new \Whoops\Handler\PlainTextHandler);
        }
        elseif (Router::is_ajax())
        {
            $handler = new \Whoops\Handler\JsonResponseHandler();
            $handler->addTraceToOutput(true);
            $whoops->pushHandler($handler);
        }
        else
        {
            $handler = new \Whoops\Handler\PrettyPageHandler();

            if ($exception instanceof DatabaseException)
            {
                $handler->addDataTable('Database Error', array(
                    'Query' => (string) $exception->query,
                    'Error' => 'MySQL Error #' . $exception->getCode() . ': ' . $exception->getMessage(),
                    //'recentQueries' => $exception->recentQueries
                ));

                $recentQueries = array();
                foreach ($exception->recentQueries as $query)
                {
                    $key = $query->file . ':' . $query->line;
                    $value = (string) $query->query;
                    $recentQueries[$key] = $value;
                }

                $handler->addDataTable('Recent Database Queries', $recentQueries);
            }

            $user = User::get_current_user(User::USER_ERROR_FALSE);
            if ($user)
            {
                $handler->addDataTable('User', $user->toArray());
            }

            $whoops->pushHandler($handler);
        }
        
		if (PHP_SAPI !== 'cli')
		{
            header('HTTP/1.1 500 Internal Server Error', true, 500);
        }

        echo $whoops->handleException($exception);
	}
	
	public static function handle_exception_default($exception)
	{
		if (PHP_SAPI === 'cli')
		{
			echo $exception;
		}
		elseif (Router::is_ajax())
		{
			header('HTTP/1.1 500 Internal Server Error', true, 500);
			echo json_encode(array(
				'error' => array(
					'type' => get_class($exception),
					'message' => $exception->getMessage(),
					'code' => $exception->getCode()
				)
			));
		}
		else
		{
			header('HTTP/1.1 500 Internal Server Error', true, 500);
			@FastView::Load('errors/error');
			@FastView::Publish();
		}
	}

	public static function show_404()
	{
		header('HTTP/1.1 404 Not Found', true, 404);

		$headers = headers_list();
		$mime = 'text/html';
		foreach ($headers as $header)
		{
			if (preg_match('/content-type:/i', $header))
			{
				list(, $mime) = explode(':', $header);
			}
		}

		if ($mime == 'text/html')
		{
			if (file_exists(PATH_VIEWS .'http/404.php'))
			{
				@FastView::Load('http/404');
				@FastView::Publish();
			}
			elseif (file_exists(PATH_VIEWS .'errors/404.php'))
			{
				@FastView::Load('errors/404');
				@FastView::Publish();
			}
			else {
				echo '<h1>HTTP 404 Page Not Found</h1>';
			}
		}

		core_halt();
	}
	
	public static function show_403()
	{
		header('HTTP/1.1 403 Forbidden', true, 403);

		$headers = headers_list();
		$mime = 'text/html';
		foreach ($headers as $header)
		{
			if (preg_match('/content-type:/i', $header))
			{
				list(, $mime) = explode(':', $header);
			}
		}

		if ($mime == 'text/html')
		{
			if(file_exists(PATH_VIEWS .'http/403.php'))
			{
				@FastView::Load('http/403');
				@FastView::Publish();
			}
			elseif (file_exists(PATH_VIEWS .'errors/403.php'))
			{
				@FastView::Load('errors/403');
				@FastView::Publish();
			}
			else
			{
				echo '<h1>HTTP 403 Forbidden</h1>';
			}
		}

		core_halt();
	}
	
	public static function show_xsrf()
	{
		header('HTTP/1.1 403 Forbidden', true, 403);

		if (Router::is_ajax())
		{
			// try not to break AJAX applications
			json_output((object) array(), 'This page has expired, please refresh the page and try again.');
		}
		else
		{
			// determine the page MIME type
			$headers = headers_list();
			$mime = 'text/html';
			foreach($headers as $header)
			{
				if (preg_match('/content-type:/i', $header))
				{
					list(, $mime) = explode(':', $header);
				}
			}
			
			if($mime == 'text/html')
			{
				if(file_exists(PATH_VIEWS .'http/xsrf.php'))
				{
					@FastView::Load('http/xsrf');
					@FastView::Publish();
				}
				elseif (file_exists(PATH_VIEWS .'errors/xsrf.php'))
				{
					@FastView::Load('errors/xsrf');
					@FastView::Publish();
				}
				else
				{
					echo sprintf('<h1>This page has expired</h1><p>Please <a href="%s">click here</a> to reload the page and try again.</p>', Router::url());
				}
			}
		}

		core_halt();
	}
}
