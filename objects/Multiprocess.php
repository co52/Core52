<?php

require 'Shma.php';

/**
 * PHP multiprocessing class
 * @author	Jonathon Hill
 * @date	09/10/2008
 * @version	0.5
 */
class Multiprocess
{
	private $work_queue = array();	        // Data to work on
	private $child_func;			// Child function
	
	private $pids = array();		// Working processes
	public  $tpids = array();		// Ended processes
	public  $show_progress = false;	        // Show/hide the progress indicator
	private $relation;			// Parent/child
	private $completed = 0;			// How many child processes have completed
	public $caller = 'call_user_func_array';
	
	
	public function __construct($data, $callback = null)
	{
		if(!is_array($data) || !count($data)) return FALSE;
		$this->work_queue = $data;
		$this->child_func = $callback;
		$this->shma = new SHM_Accessor();
	}
        
	
	public function run($concurrent = 5)
	{
		$this->concurrent = $concurrent;

		$this->start = microtime(true);
		$this->completed = 0;
		foreach($this->work_queue as $work_data)
		{
			$pid = pcntl_fork(); // conceive
			switch($pid)
			{
				case -1:	// miscarried
					trigger_error("Out of memory!", E_USER_ERROR); exit(-1);
					
				case 0:		// child's play
					$this->relation = 'child';
					$this->shma->persist = true;
					if(method_exists($this, 'child')) {
						$ret = $this->child($work_data);
					} elseif($this->caller == 'call_user_func') {
						$ret = call_user_func($this->child_func, $work_data);
					} else {
						$ret = call_user_func_array($this->child_func, $work_data);
					}
					$this->shma->write(posix_getpid(), $ret, true);
					exit(1);
					
				default:	// parental duties: watching after the children
					$this->relation = 'parent';
					$this->pids[$pid]->start = microtime(true);
					$this->pids[$pid]->work  = $work_data;
			}
		
			// wait on a process to finish if we're at our concurrency limit
			while(count($this->pids) >= $concurrent)
				$this->reap_child();
		}
		
		// wait on remaining processes to finish
		while(count($this->pids) > 0)
			$this->reap_child();
		
		// compute runtime stats
		$this->end = microtime(true);
		$this->runtime = $this->end - $this->start;
		$this->compute_processtime();
		$this->processes = count($this->tpids);
		$this->avg_processtime = $this->processtime / $this->processes;
		
		if(method_exists($this, 'end'))	$this->end();
	}
	
	
	private function reap_child()
	{
		$tpid = pcntl_wait($status, WNOHANG);
		switch($tpid)
		{
			case -1: die("\nError: out of memory!");
			case  0: break;
			default:
				$this->completed++;

				$this->tpids[$tpid] = $this->pids[$tpid];
				unset($this->pids[$tpid]);

				$this->tpids[$tpid]->end = microtime(true);
				$this->tpids[$tpid]->runtime = $this->tpids[$tpid]->end - $this->tpids[$tpid]->start;
				$this->tpids[$tpid]->exit = pcntl_wexitstatus($status);
				$this->tpids[$tpid]->return = $this->shma->read($tpid, TRUE);

				$progress = $this->completed/count($this->work_queue)*100;
				if($this->show_progress) echo "Running: ".number_format($progress)."%...\r";
		}
	}
        
	
	private function compute_processtime()
	{
		$this->processtime = 0;
		foreach($this->tpids as $tpid) {
			$this->processtime += $tpid->runtime;
		}
	}
	
	
}


function multiprocess($callback, $data, $processes = 5, $caller = 'call_user_func') {
	$mp = new Multiprocess($data, $callback);
	$mp->caller = $caller;
	$mp->run($processes);
	return $mp->tpids;
}
