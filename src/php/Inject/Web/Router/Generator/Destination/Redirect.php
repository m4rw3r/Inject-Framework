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
	protected function doValidation(Tokenizer $tokenizer)
	{
		$diff = array_diff($this->route->getTo()->getRequiredCaptures(), $tokenizer->getRequiredCaptures());
		
		if( ! empty($diff))
		{
			throw new \Exception(sprintf('The route %s does not contain the required capture :%s which is required by the redirect destination.', $this->route->getRawPattern(), current($diff)));
		}
	}
	
	public function getCompiled()
	{
		$this->compile();
		
		return array(new Route\CallbackRoute($this->pattern, $this->route->getOptions(), $this->capture_intersect, $this->route->getAcceptedRequestMethods(), $this->route->getTo()->getCallback()));
	}
	
	public function getCacheCode($var_name, $controller_var)
	{
		$this->compile();
		
		return $var_name.' = new Route\CallbackRoute('.var_export($this->pattern, true).', '.var_export($this->route->getOptions(), true).', '.var_export($this->capture_intersect, true).', '.var_export($this->route->getAcceptedRequestMethods(), true).', '.$this->route->getTo()->getCallbackCode().');';
	}
}


/* End of file Callback.php */
/* Location: src/php/Inject/Web/Router/Generator/Destination */