<?php

chdir(dirname(__FILE__));
require_once('_initialize.php');


class Mail_Engine extends Daemon {

	public $monitor_sleep_interval 	= 1;		// daemon monitor polling interval (seconds)
	public $daemon_memory_limit		= '32M';	// memory limit
	public $daemon_listen_interval	= 1000000;	// daemon polling interval microseconds
	public $daemon_restart_limit   	= 100;		// max daemon restart attempts
	public $max_concurrent 			= 5;		// max number of child processes the daemon can fork at a given time
	public $emergency_email 		= 'someone@your-company.com';	// send emergency error e-mails here
	public $log_file_path			= 'mail_daemon.log';			// log to this file
	public $log_to					= 'screen';						// log to file or screen?
	
	protected $debug = TRUE;
	

	public function process() {
		foreach($this->__daemon_listen() as $data) {
			$this->__daemon_child($data);
			#exit;
		}
	}
	
	
	protected function __daemon_listen() {

		try {
			if(!database()->ping()) {
				$this->log('Daemon listener database connection died, reconnecting');
				database()->connect();
				$this->log('Database re-connected');
			}

			return database()->execute("SELECT * FROM mailqueue WHERE status = 'pending' LIMIT 50", FALSE) // don't cache
				->result();
				
		} catch (Exception $e) {
			$this->log('Exception in Daemon listener: '.$e);
			$this->log($e->getMessage());
			return array();
		}
	}
	
	
	protected function __daemon_child($data) {
				
		$pid = getmypid();
		$data = $data;
		database()->connect();
		$this->log("Processing message #$data->id for $data->to with subject $data->subject...");
		
		# mark msg as processing
		database()->update('mailqueue', array(
			'status' => 'processing',
			'process_id' => $pid,
			'process_started' => date('Y-m-d H:i:s'),
		), array('id' => $data->id));
		
		# try to send the e-mail
		try {
			
			$mailer = unserialize($data->email_body);
			$result = $mailer->send();
			$this->log("Sent message #$data->id");
			database()->update('mailqueue', array(
				'status' => 'sent',
				'attempts' => $data->attempts + 1,
			), array('id' => $data->id));
			
		} catch(phpmailerException $e) {
			
			if($data->attempts < 10) {
				
				$this->log("Failed! Message #$data->id ({$e->getMessage()})");
				database()->update('mailqueue', array(
					'status' => 'pending',
					'attempts' => $data->attempts + 1,
					'send_after' => date('Y-m-d H:i:s', strtotime('+1 minute')),
				), array('id' => $data->id));
				
			} else {
				
				$this->log("Failed (final attempt)! Message #$data->id ({$e->getMessage()})");
				database()->update('mailqueue', array(
					'status' => 'failed',
					'attempts' => $data->attempts + 1,
				), array('id' => $data->id));
				
			}
			
		}
	
		DatabaseConnection::factory()->__destruct();
	}
	
	
}


$engine = new Mail_Engine();
$engine->log_to = PATH_APP.'logs/mail_engine_'.date('Ymd').'.log';
echo "\n\nMail engine initialized. Waiting for requests...\n\n";

$engine->process();
