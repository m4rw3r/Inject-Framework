<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Route;

use \Inject\Core\Application\Engine;

/**
 * A compiled route pointing to a controller class.
 */
class ControllerRoute extends AbstractRoute
{
	protected $engine;
	
	protected $controller;
	
	// ------------------------------------------------------------------------
	
	/**
	 * @param  string  The regular expression pattern
	 * @param  array(string => string)  List of options to return if this route matches
	 * @param  array(string => int)  List of keys to intersect to get the options from
	 *                               the regex captures
	 * @param  array(string)  List of accepted HTTP request methods
	 * @param  string    Fully qualified controller class name
	 */
	public function __construct($pattern, array $options, array $capture_intersect, array $accepted_request_methods, Engine $engine, $controller)
	{
		parent::__construct($pattern, $options, $capture_intersect, $accepted_request_methods);
		
		$this->engine     = $engine;
		$this->controller = $controller;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns a callback which is to be run by the application, this
	 * method is called after matches() has returned true.
	 * 
	 * @param  mixed
	 * @return callback
	 */
	public function dispatch($env)
	{
		$controller_name = $this->controller;
		$c = $controller_name::stack($this->engine, $env['web.path_parameters']['action']);
		
		return $c->run($env);
	}
}


/* End of file ControllerRoute.php */
/* Location: src/php/Inject/Web/Router/Route */