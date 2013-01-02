<?php

/**
 * Core52 Benchmark Class
 *
 *
 * @author "Jonathon Hill" <jhill@companyfiftytwo.com>
 * @package Core52
 * @version ?
 *
 **/

class Benchmark {
	
	private static $markers = array();
	private static $memory = array();
	private static $data = array();
	private static $trace = array();
	private static $profiling = array();
	private static $enabled = TRUE;
	private static $index = 0;
	private static $time = 0;
	private static $report_template = '
<div style="padding:20px;background:white;border:1px solid #ccc;margin:20px;">
	<h3 style="border-bottom:2px solid #333;">Profiling information <a href="{url}">{url}</a></h3>
	<p style="margin:6px 0;">Total runtime <b>{time}</b> seconds; peak memory <b>{memory peak}k</b></p>
	<table style="width:100%;" cellspacing="0" cellpadding="5" style="margin:0;">
		<tr>
			<th align="left" style="width:50%;">&nbsp;</th>
			<th align="right">Time</th>
			<th align="right">%</th>
			<th align="right">Mem</th>
			<th align="left">Extra</th>
		</tr>
	
	<!--LOOP markers AS marker-->
		<tr class="{marker:class}">
			<td valign="top" align="left"><strong>{marker:function}</strong><br />
				<small>called from {marker:file}</small></td>
			<td valign="top" align="right">{marker:time}</td>
			<td valign="top" align="right">{marker:time_pct}</td>
			<td valign="top" align="right">{marker:memory}</td>
			<td valign="top" align="left">{marker:extra}</td>
		</tr>
	<!--ENDLOOP markers-->
	</table>
</div>
';
	
	
	public static function Initialize($enable = FALSE) {
		#$settings = unserialize(Config::get_val(BENCHMARK_CONFIG));
		if($enable) {
			self::$enabled = TRUE;
			self::start($settings);
			register_shutdown_function(array('Benchmark', 'profile'));
		} else {
			self::$enabled = FALSE;
		}
	}
	
	
	public static function mark($label = '', $extra = '', $trace = NULL) {
		
		if(!self::$enabled) return;
		
		$label = (strlen($label) > 0)? $label : self::$index;
				
		self::$markers[$label] = microtime(TRUE);
		self::$memory[$label]  = memory_get_usage();
		self::$data[$label]    = $extra;
		self::$trace[$label]   = (is_array($trace))? $trace : self::trace();
		
		self::$index++;
	}
	
	
	public static function trace() {
		
		$trace = debug_backtrace();
		
		$files = array(__FILE__, FRAMEWORK_PATH.'Database.php', FRAMEWORK_PATH.'Database.php');
		
		foreach($trace as $line) {
			if(!in_array($line['file'], $files) && $line['class'] != __CLASS__) {
				return array(
					'file' => $line['file'].':'.$line['line'],
					'function' => $line['class'].$line['type'].$line['function'].'()',
				);
			}
		}
		
		$line = array_pop($trace);
		return array(
			'file' => $line['file'].':'.$line['line'],
			'function' => $line['class'].$line['type'].$line['function'].'()',
		);
	}
	
	
	public static function start() {
		if(!self::$enabled) return;
		self::mark('Start');
	}
	
	
	public static function stop() {
		if(!self::$enabled) return;
		self::mark('Stop');
		self::$time = self::$markers['Stop'] - self::$markers['Start'];
		self::$profiling = self::compute_profile_times();
	}
	
	
	public static function total_time() {
		return self::$time;
	}
	
	
	public static function compute_profile_times() {
		
        $i = $total = 0;
        $result = array();
        $temp = reset(self::$markers);

        foreach (self::$markers as $marker => $time) {
            $diff  = $time - $temp;
            
            $result[$marker] = $diff;

            $temp = $time;
            $i++;
        }

        $first = array_shift(array_keys($result));
        $result[$first] = '-';

        return $result;
    }
    
	
	public static function get_marker_data($label) {
		$data = array();
		
		$data['file'] = self::$trace[$label]['file'];
		$data['function'] = self::$trace[$label]['function'];
		
		$data['memory'] = number_format((float) self::$memory[$label] / 1024, 0).'k';
		$data['time'] = number_format((float) self::$profiling[$label], 5);
		$data['time_pct'] = number_format((float) self::$profiling[$label] * 100 / self::$time, 0).'%';
		
		$data['extra'] = '<pre style="width:99%; white-space:-moz-pre-wrap !important; white-space:-pre-wrap; white-space:-o-pre-wrap; white-space:pre-wrap; word-wrap:break-word; white-space:normal;">'.self::$data[$label]."</pre>";
		
		return $data;
	}
	
	
	public static function display($publish = TRUE) {
		
		$view = new ViewObject();
		$view->Load_String(self::$report_template);
		$view->Parse();
		
		foreach(self::$markers as $label => $marker) {
			$data = self::get_marker_data($label);
			if(self::$data[$label] == 'HIDE') continue;
			
			$data['label'] = trim(trim($data['label']), "\t");
			$data['class'] = trim($data['time_pct'], '%') > 25 ? 'hot' : '';
			$markers[] = $data;
		}
		
		
		$view->Data(array(
			'time' => number_format(self::$time, 4),
			'memory peak' => number_format(memory_get_peak_usage() / 1024, 0),
			'markers' => $markers,
			'url' => isset(Router::$domain[3]) ?
				Router::protocol() . '://'. Router::$domain[3] .'.'. Router::$domain[2] .'.'. Router::$domain[1] . Router::$path :
				Router::protocol() . '://'. Router::$domain[2] .'.'. Router::$domain[1] . Router::$path
		));
		
		$html = $view->Publish(TRUE);
		
		if($publish) {
			echo $html;
		} else {
			return $html;
		}
	}
	
	
	public static function profile() {
		try {
			self::stop();
			self::display();
		} catch(Exception $e) {
			core_handle_exception($e);
		}
	}
	
	
}



