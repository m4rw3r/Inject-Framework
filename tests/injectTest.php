<?php
/*
 * Created by Martin Wernståhl on 2009-07-22.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

require_once 'PHPUnit/Framework.php';

require_once dirname(__FILE__).'/../Inject/inject.php';

/**
 * Tests the Controller base object for the Inject Framework.
 */
class InjectTest extends PHPUnit_Framework_TestCase
{
	// The Inject class is a singleton
	public $runTestInSeparateProcess = true;
	
	// ------------------------------------------------------------------------

	/**
	 * Make sure that it is abstract, so we cannot instantiate it.
	 */
	public function testStaticClassIsAbstract()
	{
		$reflection = new ReflectionClass('Inject');
		
		$this->assertTrue($reflection->isAbstract());
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Check that Inject::load() is registered as an autoloader.
	 */
	public function testInitAutoload()
	{
		Inject::init();
		
		$this->assertContains(array('Inject', 'load'), spl_autoload_functions());
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Make sure that we call ob_start()
	 */
	public function testObStart()
	{
		Inject::init();
		
		// assert that we increased the ob_level
		$this->assertEquals(ob_get_level(), 1);
	}
	
	public function testObStart2()
	{
		ob_start();
		
		Inject::init();
		
		// assert that we increased the ob_level
		$this->assertEquals(ob_get_level(), 2);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Make sure that Inject::create() defaults to the given class name.
	 * 
	 * @covers create
	 */
	public function testCreateClass()
	{
		// create a class:
		eval('class A_Class_Name {}');
		
		$this->assertEquals(Inject::create('A_Class_Name'), new A_Class_Name);
	}
	
	/**
	 * Make sure that it uses the string as a class name, if it isn't a callable.
	 * 
	 * @covers create
	 */
	public function testCreateOtherClass()
	{
		// create a class:
		eval('class A_Class_Name {}');
		
		// set a class to use
		Inject::set_class('test_string', 'A_Class_Name');
		
		$this->assertEquals(Inject::create('test_string'), new A_Class_Name);
	}
	
	/**
	 * Make sure that it uses a set object in a singleton-like manner.
	 * 
	 * @covers create
	 */
	public function testCreateSingletonClass()
	{
		// create a class:
		eval('class A_Class_Name {}');
		
		// set a class to use
		Inject::set_class('test_string', $o = new A_Class_Name);
		
		$o->test = 'test string';
		
		$this->assertSame(Inject::create('test_string'), $o);
		$this->assertSame(Inject::create('test_string')->test, $o->test);
	}
	
	/**
	 * Make sure that it handles PHP 5.3 closures (ie. all objects which have the method __invoke()).
	 * 
	 * @covers create
	 */
	public function testCreateWithClosure()
	{
		// set a closure to use
		Inject::set_class('test_string', function()
		{
			static $i = 0;
			
			$i++;
			
			return "Test '$i'";
		});
		
		$this->assertSame(Inject::create('test_string'), 'Test \'1\'');
		$this->assertSame(Inject::create('test_string'), 'Test \'2\'');
		
		
		// create object which acts like a closure
		eval('class Test { function __invoke() { return "returned from Test"; } }');
		
		$this->assertEquals(Inject::create('Test'), new Test);
		
		// make Inject::create() call it instead of creating an instance
		Inject::set_class('Test', new Test);
		
		$this->assertSame(Inject::create('Test'), 'returned from Test');
	}
	
	/**
	 * Make sure that it handles string and array callables.
	 * 
	 * @covers create
	 */
	public function testCreateWithCallable()
	{
		function create_some_string()
		{
			return 'testing the function callable';
		}
		
		// set a callable to use
		Inject::set_class('test_string', 'create_some_string');
		
		$this->assertEquals(Inject::create('test_string'), 'testing the function callable');
		
		
		eval('class Test {
			function test_m() { return "testing array callable"; }
			static function test_m2() { return "testing static callable"; }
		}');
		
		Inject::set_class('test_array', array(new Test, 'test_m'));
		Inject::set_class('static_string', 'Test::test_m2');
		
		$this->assertEquals(Inject::create('test_array'), "testing array callable");
		$this->assertEquals(Inject::create('static_string'), 'testing static callable');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Tests the run method, makes sure that the dispatcher is called.
	 */
	public function testRun()
	{
		return;
		$req = $this->getMock('Inject_Request', array('get_type', 'get_response'));
		$disp = $this->getMock('Inject_Dispatcher', array('http'));
		$resp = $this->getMock('Inject_Response', array('output_content'));
		
		$req->expects($this->atLeastOnce())->method('get_type')->will($this->returnValue('http'));
		$req->expects($this->once())->method('get_response')->will($this->returnValue($resp));
		$disp->expects($this->once())->method('http');
		
		Inject::set_class('dispatcher', $disp);
		
		Inject::run($req);
		
		$this->assertSame(Inject::$main_request, $req);
	}
}


/* End of file injectTest.php */
/* Location: ./test */