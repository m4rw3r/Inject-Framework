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
	/**
	 * A list of default values for controller requests.
	 * 
	 * @var array(string => string)
	 */
	protected $defaults = array(
			'action' => 'index'
		);
	
	protected $options;
	
	protected function doValidation(Tokenizer $tokenizer)
	{
		$this->options = array_merge($this->defaults, $this->route->getOptions());
		
		$to = $this->route->getTo();
		
		$this->controller = $this->translateShortControllerName($to['controller']);
		
		empty($to['action']) OR $this->options['action'] = $to['action'];
	}
	
	public function getCompiled()
	{
		$this->compile();
		
		return new Route\ControllerRoute($this->constraints, $this->options, $this->capture_intersect, eval('return '.$this->getUriGenerator().';'), $this->engine, $this->controller);
	}
	
	public function getCacheCode($var_name, $controller_var, $engine_var)
	{
		$this->compile();
		
		return $var_name.' = new Route\ControllerRoute('.var_export($this->constraints, true).', '.var_export($this->options, true).', '.var_export($this->capture_intersect, true).', '.$this->getUriGenerator().', '.$engine_var.', '.var_export($this->controller, true).');';
	}
}


/* End of file Callback.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */