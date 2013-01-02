<?php

require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Extensions/OutputTestCase.php';

define('PATH_CORE', '../');
require_once PATH_CORE.'objects/Javascript.php';

/**
 * Javascript test case
 */
class ControllerJavascript extends PHPUnit_Extensions_OutputTestCase {

	/**
	 * @covers Javascript::add_inline()
	 * @dataProvider inlineJavascriptProvider
	 */
	public function testAddingInlineJavascriptWorks($inline) {
				
		// Add inline 
		Javascript::add_inline($inline);
		
		// The _code static variable must contain the javascript code 
		$this->assertAttributeContains($inline, '_code', Javascript);

	}
	
	/**
	 * @covers Javascript::get_inline()
	 */
	public function testInlineJavascriptIsSurroundedByScriptTags() {

		// Get inline 
		$inline = Javascript::get_inline();
		
		$this->assertStringStartsWith('<script type="text/javascript">', $inline);
		$this->assertContains('</script>', $inline);

	}
	
	
	/**
	 * @covers Javascript::get_inline()
	 */
	public function testInlineJavascriptIsInOrderItWasAdded() {

		// Get inline 
		$inline = Javascript::get_inline();
		
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
	 * @covers Javascript::add_remote()
	 */
	public function testAddingRemoteJavascriptWorks() { 
		
		$url = 'https://ajax.googleapis.com/ajax/libs/jquery/1.6.0/jquery.min.js';
		
		// Add remote 
		Javascript::add_remote($url);

		// The _remote static variable must contain the URL
		$this->assertAttributeContains($url, '_remote', Javascript);
			
		return $url;
	}
	
	/** 
	 * @covers Javascript::add()
	 */
	public function testAddingLocalJavascriptWorks() { 
		
		$path = 'jquery.min.js';
		
		// Add  
		Javascript::add($path);

		// The _script static variable must contain the URL
		$this->assertAttributeContains($path, '_script', Javascript);
	
		return $path;
	}
	
	/** 
	 * @covers 	Javascript::get()
	 * @depends testInlineJavascriptIsInOrderItWasAdded
	 * @depends testAddingLocalJavascriptWorks
	 * @depends testAddingRemoteJavascriptWorks
	 */
	public function testGetJavascriptIncludeInlineParameterWorks($inline, $local, $remote) { 

		// Get with inline
		$js = Javascript::get(true);

		// Check if the inline javascript is there 
		$this->assertContains($inline, $js);
		
		// Get without inline
		$js = Javascript::get(false);
		$this->assertNotContains($inline, $js);

		// Make sure the default argument is false
		$this->assertEquals(Javascript::get(), $js);

	}


	/** 
	 * @covers Javascript::publish_inline()
	 * @depends testInlineJavascriptIsInOrderItWasAdded
	 */
	public function testPublishInlineWorks($inline) { 

		// Expect that the inline code will be printed 
		$this->expectOutputString($inline);

		// Add  
		Javascript::publish_inline();
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


