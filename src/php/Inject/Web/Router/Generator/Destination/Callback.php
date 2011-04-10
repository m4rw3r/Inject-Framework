<?php
/*
 * Created by Martin Wernståhl on 2011-03-23.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator\Destination;

use \Inject\Web\Router\Route;
use \Inject\Web\Router\Generator\Tokenizer;

/**
 * 
 */
class Callback extends AbstractDestination
{
	protected function doValidation(Tokenizer $tokenizer)
	{
		$this->callback = current($this->route->getTo());
		
		try
		{
			if(strpos($this->callback, '::'))
			{
				$ref = new \ReflectionMethod($this->callback);
				
				if( ! $ref->isStatic())
				{
					// TODO: Exception
					throw new \Exception(sprintf('The route "%s" has an invalid callback "%s", the method must be static.', $this->route->getPathPattern(), $this->callback));
				}
			}
			else
			{
				$ref = new \ReflectionFunction($this->route->getTo());
			}
		}
		catch(\ReflectionException $e)
		{
			// TODO: Exception
			throw new \Exception(sprintf('The callback "%s" cannot be found for the route "%s".', $this->callback, $this->route->getPathPattern()));
		}
		
		if($ref->getNumberOfRequiredParameters() > 1)
		{
			// TODO: Exception
			throw new \Exception(sprintf('The route "%s" has an invalid callback "%s", the method/function requires too many parameters, only one required parameter is allowed.', $this->route->getPathPattern(), $this->route->getTo()));
		}
		
	}
	
	public function getClosureCode($engine_var, $controller_var)
	{
		$code = <<<'EOF'
function($env)
{
	return call_user_func('%s', $env);
}
EOF;
		
		return sprintf($code, $this->callback);
	}
}


/* End of file Callback.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */