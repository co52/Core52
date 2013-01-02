<?php

class split_vhost_log {
	
	protected $master;
	protected $master_fh;
	protected $master_size;
	protected $format = '%v:%p %h %l %u %t \"%r\" %>s %O \"%{Referer}i\" \"%{User-Agent}i\"';
	
	protected $outfiles = array();
	
	/**
	 * Log parser object
	 * @var ApacheLogRegex
	 */
	protected $parser;
	
	public function __construct() {
		
		$this->master = realpath($_SERVER['argv'][1]);
		$this->master_fh = fopen($this->master, 'r');
		$this->master_size = filesize($this->master);
		
		chdir(dirname(__FILE__));
		require '_initialize.php';
		
		// Create an instance of our object
		$this->parser = new ApacheLogRegex($this->format);
	}
	
	public function run() {
			
		$i = 1;
		$processed = 0;
		
		while($line = fgets($this->master_fh)) {
			$processed += strlen($line);
		    $data = $this->parser->parse($line);
		    if($data === null) {
		        throw new Exception("Parse failed for line #$i in $this->master");
		    } else {
		        $data['Time'] = date("Y-m-d h:i:s", $this->parser->logtime_to_timestamp($data['Time']));
		        if(strtotime('12/01/2010') <= strtotime($data['Time'])) {
			    	$this->saveln($data);
		    	}
		    	echo number_format(100*$processed/$this->master_size, 1)."%\r";
		    }
		    $i++;
		}
	}
	
	protected function saveln(array $data) {
		
		if(!isset($this->outfiles[$data['Server-Name']]) || !is_resource($this->outfiles[$data['Server-Name']])) {
			$file = $this->master.'-'.$data['Server-Name'].'.csv';
			$this->outfiles[$data['Server-Name']] = fopen($file, 'w');
			echo "Creating file: $file\n";
			fputcsv($this->outfiles[$data['Server-Name']], array_keys($data));
		}
		
		fputcsv($this->outfiles[$data['Server-Name']], $data);
	}
	
	
}


class convert_vhost_log extends split_vhost_log {
	
	protected $out_fh;
	protected $out_lines = 0;
	
	public function __construct() {
		parent::__construct();
		$this->out_fh = fopen($this->master.'.csv', 'w');
	}
	
	protected function saveln(array $data) {
		
		if($this->out_lines == 0) {
			echo "Creating file: $this->master.csv\n";
			fputcsv($this->out_fh, array_keys($data));
		}
		
		fputcsv($this->out_fh, $data);
		$this->out_lines++;
	}
	
}


$splitter = new convert_vhost_log();
$splitter->run();