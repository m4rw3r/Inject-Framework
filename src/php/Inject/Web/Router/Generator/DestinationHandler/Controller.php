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
class Controller extends Base implements DestinationHandlerInterface
{
	public static function parseTo($new, Mapping $mapping, $old)
	{
		if(is_null($new))
		{
			// If we have an instance, modify that
			$ret = $old instanceof self ? $old : new Controller($mapping);
			
			$ret->setController(null);
			$ret->setAction(null);
			
			return $ret;
		}
		elseif(is_string($new) && preg_match('/^((?:\\\\)?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*)?#([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)?$/', $new, $matches))
		{
			// If we have an instance, modify that
			$ret = $old instanceof self ? $old : new Controller($mapping);
			
			empty($matches[1]) OR $ret->setController($matches[1]);
			empty($matches[2]) OR $ret->setAction($matches[2]);
			
			return $ret;
		}
		else
		{
			return false;
		}
	}
	
	protected $controller = null;
	
	protected $action = 'index';
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function setController($value)
	{
		$this->controller = $value;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function setAction($value)
	{
		$this->action = $value;
	}
	public function validate(Engine $engine)
	{
		if(empty($this->controller))
		{
			if( ! in_array('controller', $this->tokenizer->getRequiredCaptures()))
			{
				// TODO: Exception
				throw new \Exception(sprintf('The route %s does not have an associated controller option or capture, or the :controller capture is optional.', $this->route->getPathPattern()));
			}
			
			// Build a regex so the path fails faster:
			empty($this->regex_fragments['controller']) && $this->regex_fragments['controller'] = implode('|', array_map('preg_quote', array_keys($engine->getAvailableControllers())));
		}
		else
		{
			$this->controller = $this->translateShortControllerName($engine, $this->controller);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the class name of the short name of the supplied controller.
	 * 
	 * @return string
	 */
	public function translateShortControllerName(Engine $engine, $short_name)
	{
		if(strpos($short_name, '\\') === 0)
		{
			// Fully qualified class name
			return $short_name;
		}
		
		$controllers = $engine->getAvailableControllers();
		
		$short_name = strtolower($short_name);
		
		if(isset($controllers[$short_name]))
		{
			return $controllers[$short_name];
		}
		
		throw new \Exception(sprintf('The short controller name "%s" could not be translated into a fully qualified class name, check the return value of %s->getAvailableControllers().', $short_name, get_class($engine)));
	}
	
	public function getCallCode($env_var, $engine_var, $matches_var, $controller_var)
	{
		$action = var_export($this->action, true);
		
		if(empty($this->controller))
		{
			$code = <<<EOF
// No need to check if the index exists, the regex only matches available controllers
\$class_name = {$controller_var}[strtolower({$matches_var}['controller'])];

return \$class_name::stack($engine_var, empty({$env_var}['web.route_params']['action']) ? $action : {$env_var}['web.route_params']['action'])->run($env_var);
EOF;
		}
		elseif(empty($this->action))
		{
			$code = <<<EOF
return $this->controller::stack($engine_var, empty({$env_var}['web.route_params']['action']) ? $action : {$env_var}['web.route_params']['action'])->run($env_var);
EOF;
		}
		else
		{
			$code = <<<EOF
return $this->controller::stack($engine_var, $action)->run($env_var);
EOF;
		}
		
		return $code;
	}
}


/* End of file Callback.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */