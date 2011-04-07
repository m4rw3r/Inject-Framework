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
class Redirect extends AbstractDestination
{
	protected $redirect;
	
	protected function doValidation(Tokenizer $tokenizer)
	{
		$this->redirect = current($this->route->getTo());
		
		$diff = array_diff($this->redirect->getRequiredCaptures(), $tokenizer->getRequiredCaptures());
		
		if( ! empty($diff))
		{
			throw new \Exception(sprintf('The route %s does not contain the required capture :%s which is required by the redirect destination.', $this->route->getPathPattern(), current($diff)));
		}
	}
	
	public function getCompiled()
	{
		$this->compile();
		
		return new Route\CallbackRoute($this->constraints, $this->route->getOptions(), $this->capture_intersect, eval('return '.$this->getUriGenerator().';'), $this->redirect->getCallback());
	}
	
	public function getCacheCode($var_name, $controller_var, $engine_var)
	{
		$this->compile();
		
		return $var_name.' = new Route\CallbackRoute('.var_export($this->constraints, true).', '.var_export($this->route->getOptions(), true).', '.var_export($this->capture_intersect, true).', '.$this->getUriGenerator().', '.$this->redirect->getCallbackCode().');';
	}
}


/* End of file Callback.php */
/* Location: src/php/Inject/Web/Router/Generator/Destination */