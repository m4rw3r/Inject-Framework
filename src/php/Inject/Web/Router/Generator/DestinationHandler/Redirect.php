<?php
/*
 * Created by Martin Wernståhl on 2011-03-23.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator\DestinationHandler;

use \Inject\Core\Engine as CoreEngine;

use \Inject\Web\Router\Generator\Mapping;
use \Inject\Web\Router\Generator\Redirection;
use \Inject\Web\Router\Generator\DestinationHandlerInterface;

/**
 * 
 */
class Redirect extends Base implements DestinationHandlerInterface
{
	/**
	 * Matches on a Redirection object.
	 * 
	 * @param  mixed
	 * @param  \Inject\Web\Router\Generator\Mapping
	 * @param  mixed
	 * @return DestinationHandlerInterface|false
	 */
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
	 * Sets the redirection object to use.
	 * 
	 * @param  \Inject\Web\Router\Generator\Redirection
	 * @return void
	 */
	public function setRedirect($value)
	{
		$this->redirect = $value;
	}
	
	// ------------------------------------------------------------------------
	
	public function validate(CoreEngine $engine)
	{
		$diff = array_diff($this->redirect->getRequiredCaptures(), $this->tokenizer->getRequiredCaptures());
		
		if( ! empty($diff))
		{
			throw new \Exception(sprintf('The route %s does not contain the required capture :%s which is required by the redirect destination.', $this->mapping->getPathPattern(), current($diff)));
		}
	}
	
	// ------------------------------------------------------------------------
	
	public function getCallCode($env_var, $engine_var, $matches_var, $controller_var)
	{
		return $this->redirect->getCallbackCode($env_var);
	}
}


/* End of file Callback.php */
/* Location: src/php/Inject/Web/Router/Generator/Destination */