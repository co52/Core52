<?php

require_once 'PHPUnit/Autoload.php';

define('PATH_CORE', '../');
require_once PATH_CORE.'objects/Controller.php';
require_once PATH_CORE.'objects/View.php';
require_once PATH_CORE.'objects/FastView.php';
require_once PATH_CORE.'objects/Form.php';
require_once PATH_CORE.'objects/Session.php';
require_once PATH_CORE.'objects/Model.php';


class User extends Model {}

class TestController extends Controller {
	protected $_view_type = 'FastViewObject';
	protected $_fields = array(array());
	
	protected $_require_auth = FALSE;
	protected $_allowed_users = NULL;
}


/**
 * Controller test case.
 */
class ControllerTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * @var TestController
	 */
	private $Controller;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp();
		$this->Controller = new TestController();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		$this->Controller = null;
		parent::tearDown();
	}
	
	/**
	 * @covers Controller::_load_viewparser()
	 */
	public function test_load_viewparser() {
		
		$class = $this->readAttribute($this->Controller, '_view_type');
		
		$method = new ReflectionMethod('Controller', '_load_viewparser');
		$method->setAccessible(TRUE);
		$method->invoke($this->Controller);
		
		$this->assertAttributeInstanceOf($class, 'view', $this->Controller);
	}
	
	/**
	 * @covers Controllers::_sanitize_input()
	 */
	public function test_sanitize_input() {
		// Skip this test, because it should be covered by
		// FormTest::test__construct()
		$this->assertTrue(TRUE);
	}
	
	/**
	 * @covers Conroller::_load_form()
	 */
	public function test_load_form() {
		
		$method = new ReflectionMethod('Controller', '_load_form');
		$method->setAccessible(TRUE);
		$method->invoke($this->Controller);
		
		$this->assertAttributeInstanceOf('Form', 'form', $this->Controller);
		$this->assertEquals($this->Controller, $this->Controller->form->controller);
	}
	
	/**
	 * @covers Controllers::output_json()
	 */
	public function testOutput_json() {
		// Skip this test, because it should be covered by
		// JsonTest::testJson_send()
		$this->assertTrue(TRUE);
	}
	
	/**
	 * @covers Controllers::db()
	 */
	public function testDb() {
		// Skip this test, because it should be covered by
		// DatabaseConnection::factory()
		$this->assertTrue(TRUE);
	}
		
	/**
	 * @covers Controller::__construct()
	 * @depends test_load_viewparser
	 * @depends test_sanitize_input
	 * @depends test_load_form
	 */
	public function test__construct() {
		$this->assertAttributeInstanceOf('Form', 'form', $this->Controller);
		$this->assertAttributeInstanceOf('FastViewObject', 'view', $this->Controller);
	}

}


