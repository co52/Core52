<?php

class UpdateException extends Exception {}
class CodeUpdateException extends UpdateException {}
class DatabaseUpdateException extends UpdateException {}


/**
 *
 * @author Jonathon Hill
 *
 */
class Server_Updater {
	
	protected $base_path;
	protected $app_dir = 'app/';
	
	protected $banner = '{{FILE}} - {{APP_NAME}} distributed update script';
	protected $script;
	
	protected $chown = 'chown -R www-data.www-data';
	protected $chmod = 'chmod a+x';
	
	protected $svn_cmd;
	protected $svn_usr;
	protected $svn_pwd;
	protected $svn_rev = 0;
	protected $svn_last_rev = 0;
	protected $svn_repo;
	protected $svn_log = array();
	
	protected $migrate_script = 'migrate.php';
	protected $postprocessor_script = 'postprocessor.php';
	
	
	public function __construct() {
		
		# get the current codebase revision
		$this->svn_last_rev = (int) database()->execute('SELECT revision FROM codebase_version ORDER BY time_stamp DESC LIMIT 1')->row()->revision;
		
		# insert the project and script names into the banner
		$this->script = basename(__FILE__);
		$banner = str_replace(
			array(
				'{{FILE}}',
				'{{APP_NAME}}',
			),
			array(
				$this->script,
				APP_NAME
			),
			$this->banner
		);
		$this->banner = str_repeat('*', 4 + strlen($banner)) . "\n* $banner *\n" . str_repeat('*', 4 + strlen($banner));
		
		# subversion settings
		$this->svn_repo = Config::get('svn_repo');
		$this->svn_usr  = Config::get('svn_usr');
		$this->svn_pwd  = Config::get('svn_pwd');
		$this->svn_cmd = Config::get('svn_cmd', FALSE)?
			Config::get('svn_cmd') :
			'svn up --username %s --password %s --revision %s --force --trust-server-cert --non-interactive --accept theirs-conflict';
			
		if(!$this->base_path)
			$this->base_path = PATH_BASE;
	}
	
	public function run($rev = 0) {
		
		$success = TRUE;
		
		$this->_out($this->banner);
		$this->_out();
		
		# set the revision number to update to
		if($rev > 0) {
			$this->svn_rev = $rev;
		} else {
			# use the latest rev. we're setting it here so that we
			# can guarantee that all servers are running the same revision.
			$this->svn_rev = $rev = $this->_get_latest_revision();
		}
		
		# need to update?
		if($this->svn_rev <= $this->svn_last_rev) {
			# up-to-date already
			$this->_out("Already running r$this->svn_rev, exiting.");
			$this->_out();
			return $success;
		}
		
		$this->_update_codebase($rev);
		$this->_update_database();
		$this->_post_processing();
		$this->_show_notes();
		$this->_email_changelog();
		
		$this->_out();
		
		return $success;
	}
	
	protected function _update_codebase($rev = 0) {
		
		$this->_out('Currently running r'.$this->svn_last_rev);
		$this->_out('Updating to r'.$this->svn_rev);
		$this->_out();
		$this->_out('>> (1) Codebase <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
		$this->_out();
		
		# prepare the commands to be run
		$cmds = array(
		
			# update the app dir
			sprintf($this->svn_cmd.' '.$this->base_path.' > /dev/null', $this->svn_usr, $this->svn_pwd, $this->svn_rev),
			
			# set the user and group for both the app dir and the core dir
			$this->chown.' '.$this->base_path,
			
			# set execute permissions on everything in the app/scripts dir
			$this->chmod.' '.$this->base_path.$this->app_dir.'scripts/*',
			
			# get the app dir revision number
			'svn info '.$this->base_path.' | grep Revision:',
		);
		$cmd = rtrim(implode('; ', $cmds), '; ');
		
		
		# update all servers in parallel
		$servers = Servers::get_nodes('*');
		$result = Servers::multi_exec($servers, $cmd, 30);
		
		
		# detect errors
		$success = TRUE;
		foreach($result as &$command) {
			
			# convert to an array so we can count the lines of output
			$output = explode("\n", trim($command->output));
			
			# extract the revision number from the output
			preg_match('/Revision: (?P<rev>[0-9]+)/', $command->output, $matches);
			$server_rev = (int) $matches['rev'];
			
			# check to make sure the server actually updated, and without errors
			# (since we are discarding STDOUT, the only output we will have is STDERR and the result of `svn info`)
			if($command->result === TRUE && (count($output) > 1 || $server_rev != $this->svn_rev)) {
				$command->result = new CodeUpdateException("Exception while updating $command->server to r$this->svn_rev: ".trim($command->output));
			}
			
			# report what happened
			if($command->result !== TRUE) {
				# one of the servers had an error...
				$success = FALSE;
				$this->_out(sprintf('   %s update failed:', $command->server));
				$this->_out('--------------------------------------------------------------------------------');
				$this->_out($command->result);
				$this->_out('--------------------------------------------------------------------------------');
			} else {
				$this->_out(sprintf('   %s updated to r%s', $command->server, $server_rev));
			}
			
		}
		
		$this->_out();
		
		# store the codebase version number
		database()->execute('TRUNCATE TABLE codebase_version');
		database()->replace('codebase_version', array('revision' => $this->svn_rev));
		return $success;
		
	}
	
	protected function _update_database() {
		
		$this->_out('>> (2) Database <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
		
		try {
		
			# update the database schema on the database server via app/scripts/migrate.php
			$cmd = $this->chmod.' '.$this->base_path.$this->app_dir.'scripts/*; '.
				   $this->base_path.$this->app_dir.'scripts/'.$this->migrate_script;
			$server = Servers::get_node('DatabaseMasterServer');
			$output = $server->exec($cmd, 30);
			
			# detect errors
			if(strpos($output, 'Rolled back migration') === FALSE) {
				#$this->_out(sprintf('   %s database updated to r%s', $server, $this->svn_rev));
				$this->_out($output);
				return TRUE;
			} else {
				throw new DatabaseUpdateException(trim($output));
			}
			
		} catch(Exception $e) {

			$this->_out(sprintf('   %s database updated failed:', $server, $this->svn_rev));
			$this->_out('--------------------------------------------------------------------------------');
			$this->_out($e->getMessage());
			$this->_out('--------------------------------------------------------------------------------');
			$this->_out();
			return FALSE;
			
		}
		
	}
	
	protected function _get_latest_revision() {
		$html = CURL::get($this->svn_repo, NULL, array(
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_USERPWD  => "$this->svn_usr:$this->svn_pwd",
		));
		
		preg_match('/- Revision (?P<revision>[0-9]+):/', $html, $matches);
		return (int) $matches['revision'];
	}
	
	protected function _post_processing() {
		
		$this->_out('>> (3) Post Processing <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
		$this->_out();
		
		# update all servers in parallel
		$cmd = sprintf($this->base_path.$this->app_dir.'scripts/'.$this->postprocessor_script.' %s %s 2>&1', $this->svn_last_rev, $this->svn_rev);
		$servers = Servers::get_nodes('*');
		$result = Servers::multi_exec($servers, $cmd, 30);

		# detect errors
		$success = TRUE;
		foreach($result as &$command) {
			
			# check to make sure the server actually updated, and without errors
			# (since we are discarding STDOUT, the only output we will have is STDERR)
			if($command->result instanceof Exception) {
				
				# ssh execution error
				$success = FALSE;
				$this->_out(sprintf('   %s post processing failed:', $command->server));
				$this->_out('--------------------------------------------------------------------------------');
				$this->_out($command->result);
				$this->_out('--------------------------------------------------------------------------------');
				
			} elseif(strlen(trim($command->output)) > 0) {
				
				# script error on server
				$success = FALSE;
				$this->_out(sprintf('   %s post processing failed 2:', $command->server));
				$this->_out('--------------------------------------------------------------------------------');
				$this->_out(trim($command->output));
				$this->_out('--------------------------------------------------------------------------------');
				
			} else {
				
				# success
				$this->_out(sprintf('   %s post processing completed', $command->server));
				
			}
			
		}
		
		$this->_out();
		
		return $success;
		
	}
	
	protected function _show_notes() {
		$this->_out('>> (4) Update Complete <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
		$this->_out();
		$this->_out(sprintf('   All systems updated to r%s.', $this->svn_rev));
		$this->_out();
		#$this->_out('   Please make note of the following revision notes:');
#-- begin r[rev] notes ----------------------------------------------------------
#[file output]
#-- end r[rev] notes ------------------------------------------------------------
	}
	
	protected function _email_changelog() {
		
		$this->_get_changelog($this->svn_last_rev + 1, $this->svn_rev);
		
		$this->_out('>> (5) Change Log <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
		$this->_out();
		
		$changelog = '';
		foreach($this->svn_log as $line) {
			$changelog .= '   * '.wordwrap($line, 55, "\n     ")."\n\n";
		}
		
		$this->_out($changelog);
		
		$timestamp = format_date();
		
		$notify = Config::get('EMAIL_UPDATE_NOTIFY');
		$mailer = Mailer::factory();
		foreach((array) $notify as $email) {
			$mailer->AddAddress($email);
		}
		$mailer->Subject = APP_NAME." r$this->svn_rev server update notice";
		
		$mailer->Body = <<<PLAINTEXT

$this->banner

$timestamp

Server was updated from r$this->svn_last_rev to r$this->svn_rev

Changelog:
------------------------------------------------------------

$changelog

Regards,

$this->script
PLAINTEXT;

		if($mailer->send()) {
			$this->_out("Change log sent to to: ".implode(', ', (array) $notify));
		} else {
			$this->_out("Failed to send change log to: ".implode(', ', (array) $notify));
		}

	}
	
	protected function _out($str = '') {
		echo $str.PHP_EOL;
	}
	
	protected function _get_changelog($from, $to) {
		
		if(!function_exists('svn_log')) {
			throw new Exception('Requires the PECL SVN module');
		}
		
		# get the change log
		svn_auth_set_parameter(SVN_AUTH_PARAM_DEFAULT_USERNAME, $this->svn_usr);
		svn_auth_set_parameter(SVN_AUTH_PARAM_DEFAULT_PASSWORD, $this->svn_pwd);
		foreach(svn_log($this->svn_repo, $from, $to) as $log) {
			$this->svn_log[$log['rev']] = $log['msg'];
		}
		
		# eliminate duplicate commit messages
		$this->svn_log = array_flip(array_flip($this->svn_log));
	}
	
}


