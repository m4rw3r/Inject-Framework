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
		
		if( ! in_array('controller', $tokenizer->getRequiredCaptures()))
		{
			// TODO: Exception
			throw new \Exception(sprintf('The route %s does not have an associated controller option, or the :controller capture is optional.', $this->route->getRawPattern()));
		}
	}
	
	public function getCompiled()
	{
		$this->compile();
		
		return array(new Route\PolymorphicRoute($this->pattern, $this->options, $this->capture_intersect, $this->route->getAcceptedRequestMethods(), $this->engine, $this->engine->getAvailableControllers()));
	}
	
	public function getCacheCode($var_name, $controller_var, $engine_var)
	{
		$this->compile();
		
		return $var_name.' = new Route\PolymorphicRoute('.var_export($this->pattern, true).', '.var_export($this->options, true).', '.var_export($this->capture_intersect, true).', '.var_export($this->route->getAcceptedRequestMethods(), true).', '.$engine_var.', '.$controller_var.');';
	}
}


/* End of file Polymorphic.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */