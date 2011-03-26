<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core\Middleware;

/**
 * Times the request from the time it passes this middleware until it returns
 * a response through this middleware.
 * 
 * The result is stored in the header X-Runtime. If the RunTimer is named
 * by using the parameter to the constructor, the header will be X-Runtime-$name.
 */
class RunTimer implements MiddlewareInterface
{
	protected $name;
	
	protected $next;
	
	// ------------------------------------------------------------------------

	/**
	 * @param  string  The timer name, if any
	 */
	public function __construct($name = '')
	{
		$this->name = $name;
	}
	
	// ------------------------------------------------------------------------
	
	public function setNext($next)
	{
		$this->next = $next;
	}
	
	// ------------------------------------------------------------------------

	public function __invoke($env)
	{
		$callback = $this->next;
		
		$start_time = microtime(true);
		$ret        = $callback($env);
		$end_time   = microtime(true);
		
		$ret[1]['X-Runtime'.($this->name ? '-'.$this->name : '')] = $end_time - $start_time;
		
		return $ret;
	}
}


/* End of file RunTimer.php */
/* Location: src/php/Inject/Core/Middleware */