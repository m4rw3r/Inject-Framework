<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\RouterGenerator;

/**
 * Object representing a route destination which results in a redirect.
 * 
 * Requires a DestinationHandler which handles this type of object and
 * generates the appropriate response.
 * 
 * See Inject\RouterGenerator\DestinationHandler\Redirect for an example
 * which redirects to specific URLs or a relative URI.
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
	 * Returns the tokens from the tokenization of the redirect URI/URL pattern.
	 * 
	 * @return array(mixed, string)  List of tokens from Tokenizer
	 */
	public function getTokens()
	{
		return $this->tokenizer->getTokens();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the HTTP code used by this redirect.
	 * 
	 * @return int
	 */
	public function getRedirectCode()
	{
		return $this->redirect_code;
	}
}


/* End of file Redirection.php */
/* Location: src/php/Inject/Web/Router/Generator */