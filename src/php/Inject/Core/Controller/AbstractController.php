<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core\Controller;

use \Inject\Core\MiddlewareStack;
use \Inject\Core\Engine;

/**
 * The bare bones Controller class.
 * 
 * To call $action on the SampleController controller, use this syntax:
 * <code>
 * $app instanceof \Inject\Core\Engine
 * $action is string
 * 
 * $stack = SampleController::stack($app, $action);
 * 
 * $ret = $stack->run($param);
 * </code>
 */
abstract class AbstractController
{
	/**
	 * Default MiddlewareStack creator for calling actions on this Controller.
	 * 
	 * @param  \Inject\Core\Engine           Application engine
	 * @param  string                        Controller action to call
	 * @return \Inject\Core\MiddlewareStack  The middleware stack to run
	 */
	public static function stack($app, $action)
	{
		$class = get_called_class();
		
		return new MiddlewareStack(static::initMiddleware(), function($env) use($app, $class, $action)
		{
			$callback = new $class($app);
			
			return $callback($action.'Action', $env);
		});
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns an array of middleware to be used by this controller's stack()
	 * method.
	 * 
	 * @return array(\Inject\Core\Middleware\MiddlewareInterface)
	 */
	public static function initMiddleware()
	{
		return array();
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * The main application object associated with this controller.
	 * 
	 * @var \Inject\Core\Engine
	 */
	protected $app;
	
	/**
	 * The current request.
	 * 
	 * @var \Inject\Core\Request\RequestInterface
	 */
	protected $request;
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a new controller instance.
	 */
	public function __construct(Engine $app)
	{
		$this->app = $app;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Internal: Prepares the controller for handling a request and then dispatches
	 * it to the proper action method.
	 * 
	 * @param  string  Action name
	 * @param  \Inject\Request\RequestInterface  The request to run
	 * @return \Inject\Request\Response|mixed  Return value from the controller
	 */
	public function __invoke($action, $req)
	{
		// TODO: Create request object?
		$this->request = $req;
		
		if(method_exists($this, $action) OR method_exists($this, '__call'))
		{
			return $this->$action();
		}
		else
		{
			return array(404, array('X-Cascade' => 'pass'), '');
		}
	}
}


/* End of file AbstractController.php */
/* Location: src/php/Inject/Core/Controller */