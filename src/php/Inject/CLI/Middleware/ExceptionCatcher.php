<?php
/*
 * Created by Martin Wernståhl on 2010-02-14.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\CLI\Middleware;

use \Inject\Core\Middleware\MiddlewareInterface;

/**
 * Catches exceptions and outputs them on php://stderr.
 */
class ExceptionCatcher implements MiddlewareInterface
{
	protected $next;
	
	public function setNext($callback)
	{
		$this->next = $callback;
	}
	
	public function __invoke($env)
	{
		try
		{
			$callback = $this->next;
			return $callback($env);
		}
		catch(\Exception $e)
		{
			$f = fopen('php://stderr', 'w');
			
			fwrite($f, sprintf('
Inject CLI - Uncatched Exception:

%s: %s

Trace:
%s', get_class($e), $e->getMessage(), $e->getTraceAsString()));

			fclose($f);
		}
	}
}


/* End of file ExceptionCatcher.php */
/* Location: src/php/Inject/CLI/Middleware */