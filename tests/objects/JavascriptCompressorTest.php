<?php

require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Extensions/OutputTestCase.php';

define('PATH_CORE', '../');
require_once PATH_CORE.'objects/Javascript_Compressor.php';

/**
 * Javascript test case
 */
class ControllerJavascript extends PHPUnit_Extensions_OutputTestCase {

	/**
	 * @covers Javascript_Compressor::add_inline()
	 * @dataProvider inlineJavascriptProvider
	 */
	public function testAddingInlineJavascriptWorks($inline) {
				
		// Add inline 
		Javascript_Compressor::add_inline($inline);
		
		// The _code static variable must contain the javascript code 
		$this->assertAttributeContains($inline, '_code', Javascript_Compressor);

	}
	
	/**
	 * @covers Javascript_Compressor::get_inline()
	 */
	public function testInlineJavascriptIsSurroundedByScriptTags() {

		// Get inline 
		$inline = Javascript_Compressor::get_inline();
		
		$this->assertStringStartsWith('<script type="text/javascript">', $inline);
		$this->assertContains('</script>', $inline);

	}
	
	
	/**
	 * @covers Javascript_Compressor::get_inline()
	 */
	public function testInlineJavascriptIsInOrderItWasAdded() {

		// Get inline 
		$inline = Javascript_Compressor::get_inline();
		
		// Figure out what is expected based on inline javascript provider 
		$data = $this->inlineJavascriptProvider();
		$expected = "";
		foreach ($data as $line) {
			$expected .= $line[0] . "\n";
		}
		
		$this->assertContains($expected, $inline);

		return $inline;
	}
	
	/** 
	 * @covers Javascript
	 */
	public function testJavascriptBaseConstantIsDefined() {
		
		// Make sure the constant is defined 
		$this->assertTrue(defined('JAVASCRIPT_BASE'));
		
	}
	
	/** 
	 * @covers Javascript_Compressor::add_remote()
	 */
	public function testAddingRemoteJavascriptWorks() { 
		
		$url = 'https://ajax.googleapis.com/ajax/libs/jquery/1.6.0/jquery.min.js';
		
		// Add remote 
		Javascript_Compressor::add_remote($url);

		// The _remote static variable must contain the URL
		$this->assertAttributeContains($url, '_remote', Javascript_Compressor);
			
		return $url;
	}
	
	/** 
	 * @covers Javascript_Compressor::add()
	 */
	public function testAddingLocalJavascriptWorks() { 
		
		$path = 'jquery.min.js';
		
		// Add  
		Javascript_Compressor::add($path);

		// The _script static variable must contain the URL
		$this->assertAttributeContains($path, '_script', Javascript_Compressor);
	
		return $path;
	}
	
	/** 
	 * @covers 	Javascript_Compressor::get()
	 * @depends testInlineJavascriptIsInOrderItWasAdded
	 * @depends testAddingLocalJavascriptWorks
	 * @depends testAddingRemoteJavascriptWorks
	 */
	public function testGetJavascriptIncludeInlineParameterWorks($inline, $local, $remote) { 

		// Get with inline
		$js = Javascript_Compressor::get(true);

		// Check if the inline javascript is there 
		$this->assertContains($inline, $js);
		
		// Get without inline
		$js = Javascript_Compressor::get(false);
		$this->assertNotContains($inline, $js);

		// Make sure the default argument is false
		$this->assertEquals(Javascript_Compressor::get(), $js);

	}


	/** 
	 * @covers Javascript_Compressor::publish_inline()
	 * @depends testInlineJavascriptIsInOrderItWasAdded
	 */
	public function testPublishInlineWorks($inline) { 

		// Expect that the inline code will be printed 
		$this->expectOutputString($inline);

		// Add  
		Javascript_Compressor::publish_inline();
	}


	/** 
	 * Some inline javascript for testing
	 */
	public function inlineJavascriptProvider() {
		
		return array(
			array("alert('test');"),
			array('alert("test");'),
			array('var test = "test123";'),
		);
	}


}


