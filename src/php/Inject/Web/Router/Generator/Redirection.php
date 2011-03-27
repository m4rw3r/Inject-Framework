<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;
use \Inject\Web\Router\CompiledRoute;

/**
 * Object representing a route destination which results in a redirect.
 */
class Redirection
{
	/**
	 * The HTTP response code to send when performing the redirect.
	 * 
	 * @var int
	 */
	protected $redirect_code;
	
	/**
	 * The raw destination pattern.
	 * 
	 * @var string
	 */
	protected $raw_destination;
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct($destination, $code = 301)
	{
		$this->raw_destination = $destination;
		$this->redirect_code   = $code;
		
		$this->compile();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function compile()
	{
		$this->tokenizer = new Tokenizer($this->raw_destination);
		
		foreach($this->tokenizer->getTokens() as $tok)
		{
			if($tok[0] === Tokenizer::OPTBEGIN)
			{
				// TODO: Exception
				throw new \Exception(sprintf('The redirect %s is invalid, redirect patterns cannot contain optional parts.', $this->raw_destination));
			}
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the captures used to create a dynamic redirect.
	 * 
	 * @return array(string)
	 */
	public function getRequiredCaptures()
	{
		return $this->tokenizer->getRequiredCaptures();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the callback which will perform the redirect, used when the routes
	 * are regenerated on each request.
	 * 
	 * @return Closure
	 */
	public function getCallback()
	{
		$tokens = $this->tokenizer->getTokens();
		$code   = $this->redirect_code;
		
		return function($req) use($tokens, $code)
		{
			$path = '';
			foreach($tokens as $tok)
			{
				switch($tok[0])
				{
					case Tokenizer::CAPTURE:
						$path .= $req->getParameter($tok[1]);
						break;
					case Tokenizer::LITERAL:
						$path .= $tok[1];
				}
			}
			
			// TODO: Change, Router is no longer used on the container, find anotehr way to get it
			$router = \Inject\Core\Application::getApplication()->container->getRouter();
			$url = $router->toUrl(array_merge($req->getDefaultUrlOptions(), array('path' => $path)));
			
			return array($code, array('Location' => $url), '');
		};
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a PHP code representation of the callback returned in getCallback(),
	 * used for compiling the routes cache.
	 * 
	 * @return string
	 */
	public function getCallbackCode()
	{
		$path = array();
		foreach($this->tokenizer->getTokens() as $tok)
		{
			switch($tok[0])
			{
				case Tokenizer::CAPTURE:
					$path[] = '$req->getParameter(\''.addcslashes($tok[1], '\'').'\')';
					break;
				case Tokenizer::LITERAL:
					$path[] = '\''.addcslashes($tok[1], '\'').'\'';
			}
		}
		
		return 'function($req)
{			
	// TODO: Change, Router is no longer used on the container, find anotehr way to get it
	$router = \Inject\Core\Application::getApplication()->container->getRouter();
	$url = $router->toUrl(array_merge($req->getDefaultUrlOptions(), array(\'path\' => '.implode('.', $path).')));
	
	return array('.$this->redirect_code.', array(\'Location\' => $url), \'\');
}';
	}
}


/* End of file Redirection.php */
/* Location: src/php/Inject/Web/Router/Generator */