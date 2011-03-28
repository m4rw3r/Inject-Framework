<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core\Controller;

class AbstractControllerTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		if( ! class_exists('Inject\\Core\\Controller\\AbstractControllerTestDouble'))
		{
			eval('namespace Inject\Core\Controller;
class AbstractControllerTestDouble extends AbstractController
{
	static public $got_engine;
	
	static public $__invoke;
	
	public function __construct(\Inject\Core\Engine $engine)
	{
		parent::__construct($engine);
		self::$got_engine = $engine;
	}
	
	protected function someAction($env)
	{
		return \'processed\'.$env;
	}
	
	public function __invoke($action, $env)
	{
		if(self::$__invoke)
		{
			return call_user_func(self::$__invoke, $env);
		}
		else
		{
			return parent::__invoke($action, $env);
		}
	}
}');
		}
		
		if( ! class_exists('Inject\\Core\\Controller\\AbstractControllerTestDoubleWCall'))
		{
			eval('namespace Inject\Core\Controller;
class AbstractControllerTestDoubleWCall extends AbstractControllerTestDouble
{
	public function __call($method, $params)
	{
		return print_r(array($method, $params), true);
	}
}');
		}
		
		AbstractControllerTestDouble::$got_engine = null;
		AbstractControllerTestDouble::$__invoke = null;
	}
	public function testIsAbstract()
	{
		$m = new \ReflectionClass('Inject\\Core\\Controller\\AbstractController');
		
		$this->assertTrue($m->isAbstract());
	}
	public function testStackMethod()
	{
		$case = $this;
		AbstractControllerTestDouble::$__invoke = function($env) use($case)
		{
			$case->assertEquals($env, 'params');
			
			return 'Success!';
		};
		
		$engine = $this->getMockForAbstractClass('Inject\Core\Engine', array(), '', false);
		
		$stack = AbstractControllerTestDouble::stack($engine, 'myaction');
		
		$this->assertTrue($stack instanceof \Inject\Core\MiddlewareStack);
		
		$this->assertEquals($stack->run('params'), 'Success!');
		$this->assertEquals(AbstractControllerTestDouble::$got_engine, $engine);
	}
	public function testMissingAction()
	{
		$engine = $this->getMockForAbstractClass('Inject\Core\Engine', array(), '', false);
		
		$stack = AbstractControllerTestDouble::stack($engine, 'myaction');
		
		$this->assertTrue($stack instanceof \Inject\Core\MiddlewareStack);
		
		$this->assertEquals($stack->run('params'), array(404, array('X-Cascade' => 'pass'), ''));
		$this->assertEquals(AbstractControllerTestDouble::$got_engine, $engine);
	}
	public function testAction()
	{
		$engine = $this->getMockForAbstractClass('Inject\Core\Engine', array(), '', false);
		
		$stack = AbstractControllerTestDouble::stack($engine, 'some');
		
		$this->assertTrue($stack instanceof \Inject\Core\MiddlewareStack);
		
		$this->assertEquals($stack->run('params'), 'processedparams');
		$this->assertEquals(AbstractControllerTestDouble::$got_engine, $engine);
	}
	public function testActionWCall()
	{
		$engine = $this->getMockForAbstractClass('Inject\Core\Engine', array(), '', false);
		
		$stack = AbstractControllerTestDoubleWCall::stack($engine, 'some');
		
		$this->assertTrue($stack instanceof \Inject\Core\MiddlewareStack);
		
		$this->assertEquals($stack->run('params'), 'processedparams');
		$this->assertEquals(AbstractControllerTestDouble::$got_engine, $engine);
	}
}


/* End of file AbstractControllerTest.php */
/* Location: src/tests/unit-tests/php/Inject/Core/Controller */