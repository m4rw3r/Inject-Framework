<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\RouterGenerator;

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
	 * @param  Destination pattern, same format as the one used by
	 *         Generator->match() but may not contain optional segments
	 * @param  Code to send the browser when redirecting
	 */
	public function __construct($destination, $code = 301)
	{
		$this->raw_destination = $destination;
		$this->redirect_code   = $code;
		
		$this->compile();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Tokenizes the destination string and throws an exception if it contains
	 * an optional part.
	 * 
	 * @return void
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
	 * Returns a PHP code representation of the callback returned in getCallback(),
	 * used for compiling the routes cache.
	 * 
	 * @return string
	 */
	public function getCallbackCode($env_var)
	{
		$path = array();
		foreach($this->tokenizer->getTokens() as $tok)
		{
			switch($tok[0])
			{
				case Tokenizer::CAPTURE:
					// PATH_INFO is not urlencoded, so no need to encode
					$path[] = $env_var.'[\'web.route_params\'][\''.addcslashes($tok[1], '\'').']';
					break;
				case Tokenizer::LITERAL:
					$path[] = '\''.addcslashes($tok[1], '\'').'\'';
			}
		}
		
		return '// TODO: How to inject class used for Request->getDefaultUrlOptions()?
// TODO: Remove the lines below if the generated path is a full URL
$req = new \Inject\Web\Request('.$env_var.');
$url = \Inject\Web\Request::urlFor(array_merge($req->getDefaultUrlOptions(), array(\'path\' => '.implode('.', $path).')));

return array('.$this->redirect_code.', array(\'Location\' => $url), \'\');';
	}
}


/* End of file Redirection.php */
/* Location: src/php/Inject/Web/Router/Generator */