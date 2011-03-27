<?php
/*
 * Created by Martin Wernståhl on 2010-02-14.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\CLI\Middleware;

use \Inject\Core\Middleware\MiddlewareInterface;

/**
 * Returns a 403 Forbidden if the php SAPI is not CLI.
 */
class BlockNonCLIAccess implements MiddlewareInterface
{
	protected $next;
	
	public function setNext($callback)
	{
		$this->next = $callback;
	}
	
	public function __invoke($env)
	{
		if(strtolower(PHP_SAPI) !== 'cli')
		{
			return array(403, array(), 'Only CLI access.');
		}
		
		$callback = $this->next;
		return $callback($env);
	}
}


/* End of file BlockNonCLIAccess.php */
/* Location: src/php/Inject/CLI/Middleware */