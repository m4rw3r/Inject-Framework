<?php
/*
 * Created by Martin Wernståhl on 2011-03-23.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\RouterGenerator\DestinationHandler;

use \Inject\RouterGenerator\Mapping;
use \Inject\RouterGenerator\Tokenizer;
use \Inject\RouterGenerator\Redirection;
use \Inject\RouterGenerator\DestinationHandler;
use \Inject\RouterGenerator\VariableNameContainerInterface;

/**
 * Destination handler which will redirect the user to a specific URL or a
 * relative URI.
 */
class Redirect extends DestinationHandler
{
	/**
	 * Matches on a Redirection object.
	 * 
	 * @param  mixed
	 * @param  \Inject\RouterGenerator\Mapping
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
	
	/**
	 * Redirect data object.
	 * 
	 * @var \Inject\RouterGenerator\Redirection
	 */
	protected $redirect;
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the redirection object to use.
	 * 
	 * @param  \Inject\RouterGenerator\Redirection
	 * @return void
	 */
	public function setRedirect($value)
	{
		$this->redirect = $value;
	}
	
	// ------------------------------------------------------------------------
	
	public function validate(array $validation_params)
	{
		// Validate that we have all the needed parameters to be able to create the URL/URI
		// All required parameters from the redirect must be either captured by the
		// path pattern or contained in the options array (default data for the path pattern captures)
		$diff = array_diff($this->redirect->getRequiredCaptures(), array_merge($this->tokenizer->getRequiredCaptures(), array_keys($this->options)));
		
		if( ! empty($diff))
		{
			throw new \Exception(sprintf('The route %s does not contain the required capture :%s which is required by the redirect destination.', $this->mapping->getPathPattern(), current($diff)));
		}
	}
	
	// ------------------------------------------------------------------------
	
	public function getCallCode(VariableNameContainerInterface $vars, $matches_var)
	{
		$path = array();
		foreach($this->redirect->getTokens() as $tok)
		{
			switch($tok[0])
			{
				case Tokenizer::CAPTURE:
					// PATH_INFO is not urlencoded, so no need to encode
					$path[] = $matches_var.'['.var_export($tok[1], true).']';
					break;
				case Tokenizer::LITERAL:
					$path[] = var_export($tok[1], true);
			}
		}
		
		return 'return array('.$this->redirect->getRedirectCode().', array(\'Location\' => '.implode('.', $path).'), \'\');';
	}
}


/* End of file Callback.php */
/* Location: src/php/Inject/Web/Router/Generator/Destination */