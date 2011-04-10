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
class Polymorphic extends AbstractDestination
{
	protected $options_default = array(
			'action' => 'index',
			'format' => 'html'
		);
	
	protected function doValidation(Tokenizer $tokenizer)
	{
		$this->options = array_merge($this->defaults, $this->route->getOptions());
		
		$to = $this->route->getTo();
		
		if( ! empty($to['action']))
		{
			$this->options['action'] = $to['action'];
		}
		
		if( ! in_array('controller', $tokenizer->getRequiredCaptures()))
		{
			// TODO: Exception
			throw new \Exception(sprintf('The route %s does not have an associated controller option, or the :controller capture is optional.', $this->route->getPathPattern()));
		}
		
		// Build a regex so the path fails faster:
		empty($this->regex_fragments['controller']) && $this->regex_fragments['controller'] = implode('|', array_map('preg_quote', array_keys($this->engine->getAvailableControllers())));
	}
	
	protected function getClosureCode($engine_var, $controller_var)
	{
		$code = <<<'EOF'
function($env) use(%s, %s)
{
	$short_name = strtolower($env['web.route']->param('controller'));
	
	if( ! isset(%s[$short_name]))
	{
		return array(404, array('X-Cascade' => 'pass'), '');
	}
	
	$class_name = %s[$short_name];
	
	return $class_name::stack(%s, $env['web.route']->param('action', 'index'))->run($env);
}
EOF;
		
		return sprintf($code, $engine_var, $controller_var, $controller_var, $controller_var, $engine_var);
	}
}


/* End of file Polymorphic.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */