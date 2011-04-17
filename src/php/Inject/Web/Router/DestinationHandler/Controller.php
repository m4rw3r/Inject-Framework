<?php
/*
 * Created by Martin Wernståhl on 2011-03-23.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\DestinationHandler;

use \Inject\Core\Engine as CoreEngine;

use \Inject\RouterGenerator\Mapping;
use \Inject\RouterGenerator\DestinationHandler;
use \Inject\RouterGenerator\VariableNameContainerInterface;

/**
 * 
 */
class Controller extends DestinationHandler
{
	/**
	 * Will match on null, "controller#action", "controller#" and "#action".
	 * 
	 * @param  mixed
	 * @param  \Inject\RouterGenerator\Mapping
	 * @param  mixed
	 * @return DestinationHandlerInterface|false
	 */
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
	
	/**
	 * Default options.
	 * 
	 * @var array(string => string)
	 */
	protected $options = array(
			'action' => 'index'
		);
	
	/**
	 * The controller to call.
	 * 
	 * @var string|null
	 */
	protected $controller = null;
	
	/**
	 * The action to call.
	 * 
	 * @var string|null
	 */
	protected $action = null;
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the controller to be called by the generated code, empty if dynamic.
	 * 
	 * @param  string
	 * @return void
	 */
	public function setController($value)
	{
		$this->controller = $value;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the action to be called by the generated code, empty if dynamic.
	 * 
	 * @param  string
	 * @return void
	 */
	public function setAction($value)
	{
		$this->action = $value;
	}
	
	// ------------------------------------------------------------------------
	
	public function validate(array $validation_params)
	{
		if(empty($this->controller))
		{
			if( ! in_array('controller', $this->tokenizer->getRequiredCaptures()))
			{
				// TODO: Exception
				throw new \Exception(sprintf('The route %s does not have an associated controller option or capture, or the :controller capture is optional.', $this->route->getPathPattern()));
			}
			
			// Build a regex so the path fails faster:
			empty($this->regex_fragments['controller']) && $this->regex_fragments['controller'] = implode('|', array_map('preg_quote', array_keys($validation_params['engine']->getAvailableControllers())));
		}
		else
		{
			$this->controller = $this->translateShortControllerName($validation_params['engine'], $this->controller);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the class name of the short name of the supplied controller.
	 * 
	 * @return string
	 */
	public function translateShortControllerName(CoreEngine $engine, $short_name)
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
	
	// ------------------------------------------------------------------------
	
	public function getCallCode(VariableNameContainerInterface $vars, $matches_var)
	{
		$env_var    = $vars->getEnvVar();
		$engine_var = $vars->getEngineVar();
		$cont_list  = $vars->getAvailableControllersVar();
		$action     = var_export(empty($this->action) ? $this->options['action'] : $this->action, true);
		$pre_code   = '';
		
		if(empty($this->controller))
		{
			$pre_code = <<<EOF
// No need to check if the index exists, the regex only matches available controllers
\$class_name = {$cont_list}[strtolower({$matches_var}['controller'])];

EOF;
			
			$code = <<<EOF
\$class_name::stack($engine_var, empty({$matches_var}['action']) ? $action : {$matches_var}['action'])->run($env_var)
EOF;
		}
		elseif(empty($this->action))
		{
			$code = <<<EOF
$this->controller::stack($engine_var, empty({$matches_var}['action']) ? $action : {$matches_var}['action'])->run($env_var)
EOF;
		}
		else
		{
			$code = <<<EOF
$this->controller::stack($engine_var, $action)->run($env_var)
EOF;
		}
		
		return $pre_code.$vars->wrapInReturnCodeStub($code);
	}
}


/* End of file Callback.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */