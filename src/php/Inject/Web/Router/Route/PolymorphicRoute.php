<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Route;

use \Inject\Core\Engine;

/**
 * A compiled route leading to a dynamically specified controller.
 */
class PolymorphicRoute extends AbstractRoute
{
	protected $engine;
	
	protected $available_controllers = array();
	
	// ------------------------------------------------------------------------
	
	/**
	 * @param  array(string => string)  The regular expression patterns
	 * @param  array(string => string)  List of options to return if this route matches
	 * @param  array(string => int)     List of keys to intersect to get the options from
	 *                                  the regex captures
	 * @param  array(string => classname)  List of available controllers and their classnames
	 */
	public function __construct(array $constraints, array $options, array $capture_intersect, Engine $engine, array $available_controllers)
	{
		parent::__construct($constraints, $options, $capture_intersect);
		
		$this->engine                = $engine;
		$this->available_controllers = $available_controllers;
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
		$short_name = strtolower($env['web.path_parameters']['controller']);
		
		if( ! isset($this->available_controllers[$short_name]))
		{
			return array(404, array('X-Cascade' => 'pass'), '');
		}
		
		$class_name = $this->available_controllers[$short_name];
		
		$c = $class_name::stack($this->engine, $env['web.path_parameters']['action']);
		
		return $c->run($env);
	}
}


/* End of file PolymorficRoute.php */
/* Location: src/php/Inject/Web/Router/Route */