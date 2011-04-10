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
	
	protected function getClosureCode($engine_var, $controller_var)
	{
		return $this->redirect->getCallbackCode();
	}
}


/* End of file Callback.php */
/* Location: src/php/Inject/Web/Router/Generator/Destination */