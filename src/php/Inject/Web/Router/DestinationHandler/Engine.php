<?php
/*
 * Created by Martin Wernståhl on 2011-03-23.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\DestinationHandler;

use \Inject\RouterGenerator\Mapping;
use \Inject\RouterGenerator\Tokenizer;
use \Inject\RouterGenerator\DestinationHandler;
use \Inject\RouterGenerator\VariableNameContainerInterface;

/**
 * 
 */
class Engine extends DestinationHandler
{
	/**
	 * Matches on class names, the class must be an instance of \Inject\Core\Engine
	 * or an exception will be thrown on validate().
	 * 
	 * @param  mixed
	 * @param  \Inject\RouterGenerator\Mapping
	 * @param  mixed
	 * @return DestinationHandlerInterface|false
	 */
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
	
	/**
	 * The engine class to call.
	 * 
	 * @var string
	 */
	protected $engine = null;
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the engine class to call when the route matches.
	 * 
	 * @param  string
	 * @return void
	 */
	public function setEngine($value)
	{
		$this->engine = $value;
	}
	
	// ------------------------------------------------------------------------
	
	public function validate(array $validation_params)
	{
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
	
	// ------------------------------------------------------------------------
	
	public function getCallCode(VariableNameContainerInterface $vars, $matches_var)
	{
		$env_var    = $vars->getEnvVar();
		$engine_var = $vars->getEngineVar();
		
		$code = <<<EOF
\$uri  = {$matches_var}['uri'];
\$path = empty(\$uri) ? {$env_var}['PATH_INFO'] : substr({$env_var}['PATH_INFO'], - strlen(\$uri));

// Move one step deeper in the directory structure
{$env_var}['SCRIPT_NAME']   = {$env_var}['SCRIPT_NAME'].\$path;
{$env_var}['BASE_URI']      = {$env_var}['BASE_URI'].\$path;
{$env_var}['PATH_INFO']     = '/'.trim(\$uri, '/');
{$env_var}['web.old_route_params'] = {$env_var}['web.route_params'];

\$engine = new {$this->engine}();

EOF;
		
		return $code.$vars->wrapInReturnCodeStub("\$engine->stack()->run($env_var)");
	}
}


/* End of file Callback.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */