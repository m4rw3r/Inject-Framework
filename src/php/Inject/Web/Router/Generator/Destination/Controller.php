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
class Controller extends AbstractDestination
{
	protected $options_default = array(
			'action' => 'index'
		);
	
	protected function doValidation(Tokenizer $tokenizer)
	{
		$to = $this->route->getTo();
		
		$this->controller = $this->translateShortControllerName($to['controller']);
		
		empty($to['action']) OR $this->options['action'] = $to['action'];
	}
	
	protected function getClosureCode($engine_var, $controller_var)
	{
		$code = <<<'EOF'
function($env) use(%s)
{
	return %s::stack($engine, $env['web.route']->param('action', 'index'))->run($env);
}
EOF;
		
		return sprintf($code, $engine_var, $this->controller);
	}
}


/* End of file Callback.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */