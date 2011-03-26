<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Route;

use \Inject\Core\Application\Engine;

/**
 * A compiled route pointing to a callback.
 */
class CallbackRoute extends AbstractRoute
{
	protected $callback;
	
	// ------------------------------------------------------------------------
	
	/**
	 * @param  string  The regular expression pattern
	 * @param  array(string => string)  List of options to return if this route matches
	 * @param  array(string => int)  List of keys to intersect to get the options from
	 *                               the regex captures
	 * @param  array(string)  List of accepted HTTP request methods
	 * @param  callback   Callback to call if it matches
	 */
	public function __construct($pattern, array $options, array $capture_intersect, array $accepted_request_methods, $callback)
	{
		parent::__construct($pattern, $options, $capture_intersect, $accepted_request_methods);
		
		$this->callback = $callback;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns a callback which is to be run by the application, this
	 * method is called after matches() has returned true.
	 * 
	 * @param  \Inject\Core\Application\Engine
	 * @return callback
	 */
	public function dispatch($env, Engine $engine)
	{
		$c = $this->callback;
		
		return $c($env);
	}
}


/* End of file CallbackRoute.php */
/* Location: src/php/Inject/Web/Router/Route */