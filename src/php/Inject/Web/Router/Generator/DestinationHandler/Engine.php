<?php
/*
 * Created by Martin Wernståhl on 2011-03-23.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator\DestinationHandler;

use \Inject\Core\Engine as CoreEngine;

use \Inject\Web\Router\Generator\Mapping;
use \Inject\Web\Router\Generator\Tokenizer;
use \Inject\Web\Router\Generator\DestinationHandlerInterface;

/**
 * 
 */
class Engine extends Base implements DestinationHandlerInterface
{
	public static function parseTo($new, Mapping $mapping, $old)
	{
		if(is_string($new) && class_exists($new))
		{
			$ret = new Engine($mapping);
			$ret->setEngine($new);
			
			return $ret;
		}
		else
		{
			return false;
		}
	}
	
	protected $engine = null;
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function setEngine($value)
	{
		$this->engine = $value;
	}
	public function validate(CoreEngine $engine)
	{
		$this->app_class = current($this->route->getTo());
		
		try
		{
			$ref = new \ReflectionClass($this->engine);
		}
		catch(\Exception $e)
		{
			// TODO: Exception
			throw new \Exception(sprintf('The class %s does not exist and can therefore not be used as a route destination.', $this->engine));
		}
		
		if( ! $ref->isSubclassOf('\\Inject\\Core\\Engine') OR $ref->isAbstract())
		{
			// TODO: Exception
			throw new \Exception(sprintf('The class %s does not inherit \\Inject\\Core\\Engine and can therefore not be used as a route destination.', $ref->getName()));
		}
		
		if( ! isset($this->options['uri']) && ! in_array('uri', $this->tokenizer->getCaptures()))
		{
			// TODO: Exception
			throw new \Exception(sprintf('The route %s does not have an associated *uri capture or a uri option, this is required when mounting sub-applications.', $this->mapping->getPathPattern()));
		}
		
		$tokens = $this->tokenizer->getTokens();
		$tok    = end($tokens);
		
		if( ! isset($this->options['uri']) && ($tok[0] !== Tokenizer::CAPTURE OR $tok[1] !== 'uri'))
		{
			// TODO: Exception
			throw new \Exception(sprintf('The route %s must have its *uri capture at the end of the pattern as it routes to a sub-application.', $this->mapping->getPathPattern()));
		}
	}
	public function getCallCode($env_var, $engine_var, $matches_var, $controller_var)
	{
		$code = <<<EOF
\$uri  = {$env_var}['web.route_params']['uri'];
\$path = empty(\$uri) ? {$env_var}['PATH_INFO'] : substr({$env_var}['PATH_INFO'], - strlen(\$uri));

// Move one step deeper in the directory structure
{$env_var}['SCRIPT_NAME']   = {$env_var}['SCRIPT_NAME'].$path;
{$env_var}['BASE_URI']      = {$env_var}['BASE_URI'].$path;
{$env_var}['PATH_INFO']     = '/'.trim(\$uri, '/');
{$env_var}['web.old_route_params'] = {$env_var}['web.route_params'];

return $this->engine::instance()->stack()->run($env_var);
EOF;
		
		return $code;
	}
}


/* End of file Callback.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */