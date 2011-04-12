<?php
/*
 * Created by Martin Wernståhl on 2011-03-23.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator\DestinationHandler;

use \Inject\Core\Engine as CoreEngine;

use \Inject\Web\Router\Route;
use \Inject\Web\Router\Generator\Tokenizer;
use \Inject\Web\Router\Generator\Mapping;
use \Inject\Web\Router\Generator\Redirection;

use \Inject\Web\Router\Generator\DestinationHandlerInterface;

/**
 * 
 */
class Redirect extends Base implements DestinationHandlerInterface
{
	public static function parseTo($new, Mapping $mapping, $old)
	{
		if(is_object($new) && $new instanceof Redirection)
		{
			$ret = new Redirect($mapping);
			$ret->setRedirect($new);
			
			return $ret;
		}
		else
		{
			return false;
		}
	}
	
	protected $redirect;
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function setRedirect($value)
	{
		$this->redirect = $value;
	}
	public function validate(CoreEngine $engine)
	{
		
	}
	protected function doValidation(Tokenizer $tokenizer)
	{
		$this->redirect = current($this->route->getTo());
		
		$diff = array_diff($this->redirect->getRequiredCaptures(), $tokenizer->getRequiredCaptures());
		
		if( ! empty($diff))
		{
			throw new \Exception(sprintf('The route %s does not contain the required capture :%s which is required by the redirect destination.', $this->route->getPathPattern(), current($diff)));
		}
	}
	
	public function getCallCode($env_var, $engine_var, $matches_var, $controller_var)
	{
		return $this->redirect->getCallbackCode();
	}
}


/* End of file Callback.php */
/* Location: src/php/Inject/Web/Router/Generator/Destination */