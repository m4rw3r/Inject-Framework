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
		if(strpos($this->route->getTo(), '::'))
		{
			$ref = new \ReflectionMethod($this->route->getTo());
			
			if( ! $ref->isStatic())
			{
				// TODO: Exception
				throw new \Exception(sprintf('The route "%s" has an invalid callback "%s", the method must be static.', $this->route->getRawPattern(), $this->route->getTo()));
			}
		}
		else
		{
			$ref = new \ReflectionFunction($this->route->getTo());
		}
		
		if($ref->getNumberOfRequiredParameters() > 1)
		{
			// TODO: Exception
			throw new \Exception(sprintf('The route "%s" has an invalid callback "%s", the method/function requires too many parameters, only one required parameter is allowed.', $this->route->getRawPattern(), $this->route->getTo()));
		}
		
	}
	
	public function getCompiled()
	{
		$this->compile();
		
		return array(new Route\CallbackRoute($this->pattern, $this->route->getOptions(), $this->capture_intersect, $this->route->getAcceptedRequestMethods(), $this->route->getTo()));
	}
	
	public function getCacheCode($var_name, $controller_var)
	{
		$this->compile();
		
		return $var_name.' = new Route\CallbackRoute('.var_export($this->pattern, true).', '.var_export($this->route->getOptions(), true).', '.var_export($this->capture_intersect, true).', '.var_export($this->route->getAcceptedRequestMethods(), true).', '.var_export($this->route->getTo(), true).');';
	}
}


/* End of file Callback.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */