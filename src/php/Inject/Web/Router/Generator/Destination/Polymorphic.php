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
	/**
	 * A list of default values for controller requests.
	 * 
	 * @var array(string => string)
	 */
	protected $defaults = array(
			'action' => 'index',
			'format' => 'html'
		);
	
	protected $options;
	
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
	
	public function getCompiled()
	{
		$this->compile();
		
		return new Route\PolymorphicRoute($this->constraints, $this->options, $this->capture_intersect, eval('return '.$this->getUriGenerator().';'), $this->engine, $this->engine->getAvailableControllers());
	}
	
	public function getCacheCode($var_name, $controller_var, $engine_var)
	{
		$this->compile();
		
		return $var_name.' = new Route\PolymorphicRoute('.var_export($this->constraints, true).', '.var_export($this->options, true).', '.var_export($this->capture_intersect, true).', '.$this->getUriGenerator().', '.$engine_var.', '.$controller_var.');';
	}
}


/* End of file Polymorphic.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */