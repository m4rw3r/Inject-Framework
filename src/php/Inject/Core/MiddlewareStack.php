<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core;

use \Inject\Core\Middleware\MiddlewareInterface;

/**
 * A stack which creates a chain for dealing with requests, middleware can modify the
 * request, return a response or do other actions before the request reaches the endpoint.
 */
class MiddlewareStack
{
	/**
	 * The endpoint for this stack.
	 * 
	 * @var Callback
	 */
	protected $endpoint;
	
	/**
	 * The middleware to call before calling the endpoint.
	 * 
	 * @var array(\Inject\Core\Middleware\MiddlewareInterface)
	 */
	protected $middleware = array();
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a new MiddlewareStack with the supplied middleware and endpoint.
	 * 
	 * @param  array(\Inject\Core\Middleware\MiddlewareInterface)
	 * @param  callback
	 */
	public function __construct(array $middleware = array(), $endpoint = null)
	{
		$this->middleware = $middleware;
		$this->endpoint   = $endpoint;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the endpoint for this MiddlewareStack.
	 * 
	 * @param  callback
	 * @return void
	 */
	public function setEndpoint($endpoint)
	{
		$this->endpoint = $endpoint;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds a middleware at the end of the stack.
	 * 
	 * @param  MiddlewareInterface
	 * @return void
	 */
	public function addMiddleware(MiddlewareInterface $middleware)
	{
		$this->middleware[] = $middleware;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates the middleware chain and then calls the first middleware with the
	 * supplied parameters.
	 * 
	 * @param  mixed
	 * @return mixed
	 */
	public function run($env)
	{
		if(empty($this->endpoint))
		{
			// TODO: Exception
			throw new \Exception('Endpoint missing.');
		}
		
		$callback = array_reduce(array_reverse($this->middleware), function($callback, $middleware)
		{
			$middleware->setNext($callback);
			
			return $middleware;
		}, $this->endpoint);
		
		return $callback($env);
	}
}


/* End of file MiddlewareStack.php */
/* Location: src/php/Inject/Core */