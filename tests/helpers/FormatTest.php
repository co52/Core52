<?php

require_once 'PHPUnit/Autoload.php';

define('PATH_CORE', '../../');
require_once PATH_CORE.'helpers/format.php';


/**
 * Format helper test case.
 */
class Format_BytesTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * @covers format_bytes()
	 */
	public function testPassthru() {
		$bytes = 10000;
		$this->assertTrue(format_bytes($bytes, FALSE, 'B', 'B') === $bytes);
	}
	
	
    /**
     * @dataProvider provider
     * @covers format_bytes()
     */
    public function testFormat($bytes, $decimals, $from, $to, $kb, $result) {
    	$this->assertEquals($result, format_bytes($bytes, $decimals, $to, $from, $kb));
    }
    
    
    public function provider() {
    	
        return array(
        
        	# test zeros
			array(0, 0, 'b', 'b',  1024, '0b'),
        	array(0, 0, 'b', 'kb', 1024, '0kb'),
			array(0, 0, 'b', 'mb', 1024, '0mb'),
			array(0, 0, 'b', 'gb', 1024, '0gb'),
			
			# test gb conversions
			array(2.3456, 2, 'g', 'g', 1024, '2.35g'),
			array(2.3456, 2, 'g', 'm', 1024, '2,401.89m'),
			array(2.3456, 2, 'g', 'k', 1024, '2,459,539.87k'),
			array(2.3456, 0, 'g', 'b', 1024, '2,518,568,822b'),
			
			# test mb conversions
			array(2.3456, 4, 'm', 'g', 1024, '0.0023g'),
			array(2.3456, 2, 'm', 'm', 1024, '2.35m'),
			array(2.3456, 2, 'm', 'k', 1024, '2,401.89k'),
			array(2.3456, 0, 'm', 'b', 1024, '2,459,540b'),
			
			# test kb conversions
			array(2.3456, 8, 'k', 'g', 1024, '0.00000224g'),
			array(2.3456, 4, 'k', 'm', 1024, '0.0023m'),
			array(2.3456, 2, 'k', 'k', 1024, '2.35k'),
			array(2.3456, 0, 'k', 'b', 1024, '2,402b'),
			
			# test byte conversions
			array(23456, 8, 'b', 'g', 1024, '0.00002185g'),
			array(23456, 4, 'b', 'm', 1024, '0.0224m'),
			array(23456, 2, 'b', 'k', 1024, '22.91k'),
			array(23456, 0, 'b', 'b', 1024, '23,456b'),
			
			# test decimal kb conversions
			array(23456, 8, 'k', 'g', 1000, '0.02345600g'),
			array(23456, 4, 'k', 'm', 1000, '23.4560m'),
			array(234.56, 0, 'k', 'b', 1000, '234,560b'),
			
        );
        
    }
	

}


/**
 * Format helper test case.
 */
class Format_Bytes_AutoTest extends PHPUnit_Framework_TestCase {
	
    /**
     * @dataProvider provider
     * @covers format_bytes()
     */
    public function testFormat($bytes, $decimals, $from, $kb, $result) {
    	$this->assertEquals($result, format_bytes_auto($bytes, $decimals, $from, $kb));
    }
    
    
    public function provider() {
    	
        return array(
        
        	# test zeros
			array(0, 0, 'b', 1024, '0B'),
			
			array(2.3456, 2, 'g', 1024, '2.35GB'),
			array(2.3456, 2, 'm', 1024, '2.35MB'),
			array(2.3456, 2, 'k', 1024, '2.35KB'),
			array(234,    0, 'b', 1024, '234B'),
			
			array(29758,  2, 'm', 1024, '29.06GB'),
			array(293456, 0, 'k', 1024, '287MB'),
			array(2345678,2, 'k', 1024, '2.24GB'),
        );
        
    }
	

}