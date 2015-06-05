<?php

require_once(PATH_CORE.'3rdparty/phpmailer/class.phpmailer.php');
include_once(PATH_CORE.'3rdparty/phpmailer/class.smtp.php');

/**
 * Mailer
 *
 * Some sort of wrapper for PHPMailer. Definitely needs some documentation
 * to explain what, why and what the benefits are.
 *
 * @author unknown
 * @package Core52
 * @version 1.0
 * @todo Document purpose and use.
 *
 **/

class Mailer {
	
	private static $mailer;
	public static $_mailer_agent;
	
	public static function Initialize($from = '', $from_name = '', $word_wrap = '', $smtp = FALSE, $smtp_host = '', $smtp_port = '', $smtp_user = '', $smtp_pass = '', $smtp_timeout = '', $smtp_secure = '', $smtp_debug = FALSE) {
		
		if (is_array($from)) extract($from);
		
		self::$mailer = new MY_PHPMailer();

		if ($smtp) {
			self::$mailer->IsSMTP();
			self::$mailer->Host		= $smtp_host;
			self::$mailer->Port		= $smtp_port;
			self::$mailer->SMTPSecure = $smtp_secure;
			self::$mailer->SMTPDebug = $smtp_debug;
			if(strlen($smtp_user) > 0) {
				self::$mailer->SMTPAuth	= TRUE;
				self::$mailer->Username	= $smtp_user;
				self::$mailer->Password	= $smtp_pass;
				self::$mailer->Timeout	= $smtp_timeout;
			}
		}
		
		self::$mailer->FromName	= $from_name;
		self::$mailer->From		= $from;
		self::$mailer->Sender	= $from;
		self::$mailer->WordWrap	= $word_wrap;
	}
	
	/**
	 * Returns a configured PHPMailer instance
	 *
	 * @return MY_PHPMailer
	 */
	public static function factory() {
		return clone self::$mailer;
	}
	
	/**
	 * Returns the primary PHPMailer instance
	 *
	 * @return PHPMailer
	 */
	public static function agent($reset = false) {
		if ($reset) self::$_mailer_agent = self::factory();
		
		return self::$_mailer_agent;
	}
	
}


class MY_PHPMailer extends PHPMailer {

	public $to              = array();
	public $cc              = array();
	public $bcc             = array();
	public $ReplyTo         = array();
	public $all_recipients  = array();
	public $Mailer			= 'mail';
	public $Host			= 'localhost';
	public $Port			= 25;
	public $SMTPSecure		= '';
	public $SMTPDebug    	= FALSE;
	public $SMTPAuth		= FALSE;
	public $Username		= '';
	public $Password		= '';
	public $Timeout			= 10;
	
	
	public function __construct() {
		parent::__construct(TRUE); // throw exceptions on error
	}
	
	
	protected function EncodeFile($path, $encoding = 'base64') {
		try {
			if (!is_readable($path)) {
		      throw new phpmailerException($this->Lang('file_open') . $path, self::STOP_CONTINUE);
		    }
		    if (function_exists('get_magic_quotes')) {
		      function get_magic_quotes() {
		        return false;
		      }
		    }
		    if (PHP_VERSION < 6) {
		      $magic_quotes = get_magic_quotes_runtime();

				// Only attempt to turn magic_quotes off if they're on (which they won't be on PHP5.3)
				if ($magic_quotes) {
		      	set_magic_quotes_runtime(0);
				}
		    }
		    $file_buffer  = file_get_contents($path);
		    $file_buffer  = $this->EncodeString($file_buffer, $encoding);
		    if (PHP_VERSION < 6 && $magic_quotes) { set_magic_quotes_runtime($magic_quotes); }
		    return $file_buffer;
		} catch (Exception $e) {
			$this->SetError($e->getMessage());
			return '';
		}
	}
	
	
	public function AddAddress($address, $name = '') {
		parent::AddAddress($address, $name);
	}
	
	
	public function AddCC($address, $name = '') {
		parent::AddCC($address, $name);
	}
	
	
	public function AddBCC($address, $name = '') {
		parent::AddBCC($address, $name);
	}
	
	
	public function send_raw($header = FALSE, $body = FALSE, $subject = FALSE) {
	    $result = true;
	    $this->SetLanguage('en');
	    
	    if($header === FALSE) {
			$header = $this->CreateHeader();
	    }
	    if($body === FALSE) {
			$body = $this->CreateBody();
	    }
	
		if ($subject) {
			$this->Subject = $subject;
		}
		
		# no addresses
    	if((count($this->to) + count($this->cc) + count($this->bcc)) < 1) {
	    	$this->SetError($this->Lang('provide_address'));
	    	return FALSE;
	    }
	    if(count($this->cc) > 1) {
	    	$header .= $this->AddrAppend('Cc', $this->cc);
	    }

		if($body == '') {
			return FALSE;
		}


    	switch($this->Mailer) {
    		case 'sendmail':
				$result = $this->SendmailSend($header, $body);
				break;
			case 'smtp':
				$result = $this->SmtpSend($header, $body);
				break;
			case 'mail':
			default:
				$result = $this->MailSend($header, $body);
				break;
	    }

	    return $result;
	}


	# Clear out previous message
	public function clear()	{
		$this->ClearAllRecipients();
		$this->ClearReplyTos();
		$this->ClearAttachments();
		$this->ClearCustomHeaders();
		$this->Subject = '';
		$this->Body = '';
		$this->AltBody = '';
	}
	
	
	public function send() {
		if(Config::get('DEV_EMAIL', FALSE)) {
			$this->ClearAllRecipients();
			$this->AddAddress(Config::get('DEV_EMAIL'));
		}
		
		if (Config::get('LOG_EMAIL', FALSE)) {

			# -- Save to the database
			$query = DatabaseConnection::factory()->start_query('mail_log', 'INSERT')
				->set_strip_tags(FALSE)
				->set('subject', $this->Subject)
				->set('email_body', $this->Body . "\n==Plain Text==\n" . $this->AltBody)
				->set('to', implode(',', array_keys((array) $this->all_recipients)))
				->set('user', (Session::logged_in())? Session::user()->pk() : NULL)
				->set('page_url', PHP_SAPI === 'cli'? $_SERVER['argv'][0] : Router::url())
				->set('send_after', date('Y-m-d'))
				->set('debug', serialize($debug))
					->run();

		}
		return parent::send();
	}
	

	public function queue($clear = TRUE) {
		
		# -- Save to the database
		$query = DatabaseConnection::factory()->start_query('mailqueue', 'INSERT')
			->set_strip_tags(FALSE)
			->set('subject', $this->Subject)
			->set('email_body', serialize($this))
			->set('to', implode(',', array_keys((array) $this->all_recipients)))
			->set('user', (Session::logged_in())? Session::user()->pk() : NULL)
			->set('page_url', PHP_SAPI === 'cli'? $_SERVER['argv'][0] : Router::url())
			->set('send_after', date('Y-m-d'))
				->run();
		if($clear) $this->clear();
		return true;
	}


}