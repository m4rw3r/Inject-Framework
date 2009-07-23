<?php
/*
 * Created by Martin Wernståhl on 2009-07-22.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

require_once 'PHPUnit/Framework.php';

require_once dirname(__FILE__).'/../../Inject/inject/controller.php';

/**
 * Tests the Controller base object for the Inject Framework.
 */
class Inject_ControllerTest extends PHPUnit_Framework_TestCase
{
	
	// ------------------------------------------------------------------------

	/**
	 * We need that property to store the request
	 */
	public function testControllerHasRequestProp()
	{
		$this->assertClassHasAttribute('request', 'Inject_Controller');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Is the request object assigned properly?
	 * 
	 * @covers __construct
	 */
	public function testSettingRequest()
	{
		$r = $this->getMock('Inject_Request');
		
		$c = new Inject_Controller($r);
		
		$this->assertObjectHasAttribute('request', $c);
		
		$this->assertSame($r, $c->request);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Should not accept other classes than instances of Inject_Request.
	 * 
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testSettingWrongRequestObject()
	{
		$r = $this->getMock('Some_Wrong_Class');
		
		$c = new Inject_Controller($r);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Tests what happens if the request object is null, should not be allowed by the type hint.
	 * 
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testSettingNullRequest()
	{
		$c = new Inject_Controller(null);
	}
}


/* End of file controllerTest.php */
/* Location: ./test/inject */