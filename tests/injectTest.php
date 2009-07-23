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
	 * Assure that we call ob_start()
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
}


/* End of file injectTest.php */
/* Location: ./test */