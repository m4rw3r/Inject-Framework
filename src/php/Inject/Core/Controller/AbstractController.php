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
	 * Creates a MiddlewareStack instance with middleware supplied from initMiddleware()
	 * and a closure instantiating the controller and calling its action as the endpoint.
	 * 
	 * @param  \Inject\Core\Engine           Application engine
	 * @param  string                        Controller action to call,
	 *                                       will be suffixd by "Action"
	 * @return \Inject\Core\MiddlewareStack  The middleware stack to run
	 */
	public static function stack(Engine $app, $action)
	{
		$class = get_called_class();
		
		return new MiddlewareStack(static::initMiddleware($app, $action), function($env) use($app, $class, $action)
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
	 * @param  \Inject\Core\Engine           Application engine
	 * @param  string                        Controller action to call
	 * @return array(\Inject\Core\Middleware\MiddlewareInterface)
	 */
	protected static function initMiddleware(Engine $app, $action)
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
	 * Prepares the controller for handling a request and then dispatches
	 * it to the proper action method.
	 * 
	 * @param  string  Action name
	 * @param  mixed   The request to run
	 * @return array   Return value from the controller
	 */
	public function __invoke($action, $env)
	{
		if(method_exists($this, $action) OR method_exists($this, '__call'))
		{
			return $this->$action($env);
		}
		else
		{
			return array(404, array('X-Cascade' => 'pass'), '');
		}
	}
}


/* End of file AbstractController.php */
/* Location: src/php/Inject/Core/Controller */