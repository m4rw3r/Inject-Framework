<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core;

use \Inject\Core\Middleware\MiddlewareInterface;

class MiddlewareStackTest extends \PHPUnit_Framework_TestCase
{
	public function testInstantiate()
	{
		$m = new MiddlewareStack();
		
		$this->assertTrue($m instanceof MiddlewareStack);
	}
	
	/**
	 * @expectedException \Inject\Core\Middleware\NoEndpointException
	 */
	public function testNoEndpointException()
	{
		$m = new MiddlewareStack();
		
		$m->run('DATA');
	}
	
	/**
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testFaultyParameter()
	{
		$m = new MiddlewareStack(new \stdClass);
	}
	
	public function testOnlyEndpoint()
	{
		$m = new MiddlewareStack();
		
		$run = false;
		
		$m->setEndpoint(function($data) use(&$run)
		{
			$run = $data;
			
			return 'RETURN';
		});
		
		$r = $m->run('TESTING!');
		
		$this->assertEquals($run, 'TESTING!');
		$this->assertEquals($r, 'RETURN');
	}
	
	public function testAddMiddleware()
	{
		$endpoint = function($env)
		{
			return $env.'HANDLED';
		};
		
		$middleware = $this->getMock('Inject\\Core\\Middleware\\MiddlewareInterface');
		
		$middleware->expects($this->once())->method('setNext')->with($endpoint);
		$middleware->expects($this->once())->method('__invoke')->with('TESTDATA')->will($this->returnCallback(function($env) use($endpoint)
		{
			return $endpoint($env);
		}));
		
		$m = new MiddlewareStack();
		
		$m->addMiddleware($middleware);
		$m->setEndpoint($endpoint);
		
		$r = $m->run('TESTDATA');
		
		$this->assertEquals($r, 'TESTDATAHANDLED');
	}
	
	public function testAddMultipleMiddleware()
	{
		$endpoint = function($env)
		{
			return $env.'HANDLED';
		};
		
		$middleware2 = $this->getMock('Inject\\Core\\Middleware\\MiddlewareInterface');
		
		$middleware2->expects($this->once())->method('setNext')->with($endpoint);
		$middleware2->expects($this->once())->method('__invoke')->with('1TESTDATA')->will($this->returnCallback(function($env) use($endpoint)
		{
			return $endpoint('2'.$env).'2';
		}));
		
		$middleware = $this->getMock('Inject\\Core\\Middleware\\MiddlewareInterface');
		
		$middleware->expects($this->once())->method('setNext')->with($middleware2);
		$middleware->expects($this->once())->method('__invoke')->with('TESTDATA')->will($this->returnCallback(function($env) use($middleware2)
		{
			return $middleware2('1'.$env).'1';
		}));
		
		
		$m = new MiddlewareStack();
		
		$m->addMiddleware($middleware);
		$m->addMiddleware($middleware2);
		$m->setEndpoint($endpoint);
		
		$r = $m->run('TESTDATA');
		
		$this->assertEquals($r, '21TESTDATAHANDLED21');
	}
	
	public function testAddMiddlewareAlternateSyntax()
	{
		$endpoint = function($env)
		{
			return $env.'HANDLED';
		};
		
		$middleware = $this->getMock('Inject\\Core\\Middleware\\MiddlewareInterface');
		
		$middleware->expects($this->once())->method('setNext')->with($endpoint);
		$middleware->expects($this->once())->method('__invoke')->with('TESTDATA')->will($this->returnCallback(function($env) use($endpoint)
		{
			return $endpoint($env);
		}));
		
		$m = new MiddlewareStack(array($middleware), $endpoint);
		
		$r = $m->run('TESTDATA');
		
		$this->assertEquals($r, 'TESTDATAHANDLED');
	}
}


/* End of file MiddlewareStack.php */
/* Location: src/tests/unit-tests/php/Inject/Core */