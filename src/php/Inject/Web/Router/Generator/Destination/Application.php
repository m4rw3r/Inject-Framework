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
class Application extends AbstractDestination
{
	protected function doValidation(Tokenizer $tokenizer)
	{
		$options = $this->route->getOptions();
		$this->app_class = current($this->route->getTo());
		
		try
		{
			$ref = new \ReflectionClass($this->app_class);
		}
		catch(\Exception $e)
		{
			// TODO: Exception
			throw new \Exception(sprintf('The class %s does not exist and can therefore not be used as a route destination.', $ref->getName()));
		}
		
		if( ! $ref->isSubclassOf('\\Inject\\Core\\Engine') OR $ref->isAbstract())
		{
			// TODO: Exception
			throw new \Exception(sprintf('The class %s does not inherit \\Inject\\Core\\Engine and can therefore not be used as a route destination.', $ref->getName()));
		}
		
		if( ! isset($options['uri']) && ! in_array('uri', $tokenizer->getCaptures()))
		{
			// TODO: Exception
			throw new \Exception(sprintf('The route %s does not have an associated *uri capture or a uri option, this is required when mounting sub-applications.', $this->route->getPathPattern()));
		}
		
		$tokens = $tokenizer->getTokens();
		$tok    = end($tokens);
		
		if( ! isset($options['uri']) && ($tok[0] !== Tokenizer::CAPTURE OR $tok[1] !== 'uri'))
		{
			// TODO: Exception
			throw new \Exception(sprintf('The route %s must have its *uri capture at the end of the pattern as it routes to a sub-application.', $this->route->getPathPattern()));
		}
	}
	
	public function getCompiled()
	{
		$this->compile();
		
		return array(new Route\ApplicationRoute($this->constraints, $this->route->getOptions(), $this->capture_intersect, $this->app_class));
	}
	
	public function getCacheCode($var_name, $controller_var, $engine_var)
	{
		$this->compile();
		
		return $var_name.' = new Route\ApplicationRoute('.var_export($this->constraints, true).', '.var_export($this->route->getOptions(), true).', '.var_export($this->capture_intersect, true).', '.var_export($this->app_class, true).');';
	}
}


/* End of file Application.php */
/* Location: src/php/Inject/Web/Router/Generator/Destination */