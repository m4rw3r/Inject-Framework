<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Route;

/**
 * A compiled route pointing to a callback.
 */
class CallbackRoute extends AbstractRoute
{
	protected $callback;
	
	// ------------------------------------------------------------------------
	
	/**
	 * @param  array(string => string)  The regular expression patterns
	 * @param  array(string => string)  List of options to return if this route matches
	 * @param  array(string => int)     List of keys to intersect to get the options from
	 *                                  the regex captures
	 * @param  callback   Callback to call if it matches
	 */
	public function __construct(array $constraints, array $options, array $capture_intersect, $callback)
	{
		parent::__construct($constraints, $options, $capture_intersect);
		
		$this->callback = $callback;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Dispatches the request to the route destination, called by __invoke if
	 * all the route conditions matches.
	 * 
	 * @param  mixed
	 * @return callback
	 */
	protected function dispatch($env)
	{
		return call_user_func($this->callback, $env);
	}
}


/* End of file CallbackRoute.php */
/* Location: src/php/Inject/Web/Router/Route */