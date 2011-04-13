<?php
/*
 * Created by Martin Wernståhl on 2011-03-23.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator\DestinationHandler;

use \Inject\Core\Engine;

use \Inject\Web\Router\Generator\Mapping;
use \Inject\Web\Router\Generator\DestinationHandlerInterface;

/**
 * 
 */
class Callback extends Base implements DestinationHandlerInterface
{
	/**
	 * Matches on string callbacks, must be valid callbacks.
	 * 
	 * @param  mixed
	 * @param  \Inject\Web\Router\Generator\Mapping
	 * @param  mixed
	 * @return DestinationHandlerInterface|false
	 */
	public static function parseTo($new, Mapping $mapping, $old)
	{
		if(is_string($new) && is_callable($new))
		{
			$ret = new Callback($mapping);
			$ret->setCallback($new);
			
			return $ret;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * The callback to call when the route matches.
	 * 
	 * @var string
	 */
	protected $callback = null;
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the callback to call for a match.
	 * 
	 * @param  string
	 * @return void
	 */
	public function setCallback($value)
	{
		$this->callback = $value;
	}
	
	// ------------------------------------------------------------------------
	
	public function validate(Engine $engine)
	{
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
	
	// ------------------------------------------------------------------------
	
	public function getCallCode($env_var, $engine_var, $matches_var, $controller_var)
	{
		return 'return call_user_func('.var_export($this->callback, true).", $env_var);";
	}
}


/* End of file Callback.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */