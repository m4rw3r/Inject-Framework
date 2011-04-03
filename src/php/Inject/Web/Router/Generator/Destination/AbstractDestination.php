<?php
/*
 * Created by Martin Wernståhl on 2011-03-23.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator\Destination;

use \Inject\Core\Engine;
use \Inject\Web\Router\Generator\Mapping;
use \Inject\Web\Router\Generator\Tokenizer;


/**
 * Abstract class having the common logic to tokenize and compile routing patterns,
 * generated stuff by getCompiled() and getCacheCode() depends on the destination.
 */
abstract class AbstractDestination
{
	/**
	 * The Mapping object mapping the route.
	 * 
	 * @var \Inject\Web\Router\Generator\Mapping
	 */
	protected $route;
	
	/**
	 * True if the regular expression of this route has been generated.
	 * 
	 * @var boolean
	 */
	protected $compiled = false;
	
	/**
	 * Array used to compute the key intersection of the regular expression
	 * matching results, this to remove the integer keys from the regex result.
	 * 
	 * @var array(string => string)
	 */
	protected $capture_intersect;
	
	/**
	 * The special regex constraints for certain captures, used for compilation.
	 * 
	 * @var array(string => string)
	 */
	protected $constraints = array();
	
	/**
	 * The engine instance this Mapping is generating routes for.
	 * 
	 * @var \Inject\Core\Engine
	 */
	protected $engine;
	
	/**
	 * Custom regular expression patterns, passed to createRegex().
	 * 
	 * @var array(string => regex_fragment)
	 */
	protected $regex_fragments = array();
	
	public function __construct(Mapping $route, Engine $engine)
	{
		$this->route  = $route;
		$this->engine = $engine;
	}
	
	protected function compile()
	{
		if($this->compiled)
		{
			return;
		}
		
		$tokenizer = new Tokenizer($this->route->getRawPattern());
		
		$this->regex_fragments = array_merge($tokenizer->getRegexFragments(), $this->route->getRegexFragments());
		
		$this->doValidation($tokenizer);
		
		$this->constraints = array_merge(
				$this->route->getConstraints(), 
				array('PATH_INFO' => $this->createRegex($tokenizer->getTokens(), $this->regex_fragments))
			);
		
		$this->constraints = $this->cleanConstraints($this->constraints);
		
		// Make the longest match the first one, then it will usually be faster as
		// the longer the match, the more likely it is to fail
		uasort($this->constraints, function($a, $b)
		{
			return strlen($b) - strlen($a);
		});
		
		// Check if the regular expressions parse:
		foreach($this->constraints as $key => $val)
		{
			if(@preg_match($val, '') === false)
			{
				// TODO: Exception
				throw new \Exception(sprintf('The route %s has a faulty regex for the constraint %s: "%s".', $this->route->getRawPattern(), $key, $val));
			}
		}
		
		// TODO: Allow captures from constraints too:
		$this->capture_intersect = array_flip($tokenizer->getCaptures());
		
		$this->compiled = true;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Converts the constraints which aren't strings into regexes, so it is
	 * possible to write 'authenticated' => true.
	 * 
	 * @param  array(string => mixed)
	 * @return array(string => string)
	 */
	public function cleanConstraints(array $constraints)
	{
		$arr = array();
		
		foreach($constraints as $k => $v)
		{
			switch(gettype($v))
			{
				case 'boolean':
					// ((String) true) === '1', ((String) false) === ''
					$v = $v ? '/^1$/' : '/^$/';
					break;
				case 'integer':
					$v = "/^$v\$/";
					break;
				case 'float':
				case 'double':
					$v = preg_quote($v);
					$v = "/^$v$/";
					break;
				case 'NULL':
					$v = '/^$/';
					break;
			}
			
			$arr[$k] = $v;
		}
		
		return $arr;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates the regex for the mapping.
	 * 
	 * @param  array
	 * @return string
	 */
	protected function createRegex($token_list, $regex_fragments)
	{
		// Start to create the regex
		$regex = '';
		
		// TODO: UTF-8ize the generated regex?, \w does not match all of the proper characters in UTF-8
		
		foreach($token_list as $t)
		{
			list($type, $data) = $t;
			
			switch($type)
			{
				case Tokenizer::LITERAL:
					// escape the literal so we won't have junk in our regexes
					$regex .= preg_quote($data, '#');
					break;
				
				case Tokenizer::CAPTURE:
					// Normal captures: \w+, _uri special capture: .*
					$rule = '\w+';
					
					// Override the capture rule if constraint is present
					if(isset($regex_fragments[$data]))
					{
						$rule = $regex_fragments[$data];
					}
					
					$regex .= '(?<'.preg_quote($data, '#').'>'.$rule.')';
					break;
				
				// Optional section start
				case Tokenizer::OPTBEGIN:
					$regex .= '(?:';
					break;
				
				// Optional section end
				case Tokenizer::OPTEND:
					$regex .= ')?';
					break;
			}
		}
		
		return '#^'.$regex.'$#ui';
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the class name of the short name of the supplied controller.
	 * 
	 * @return string
	 */
	public function translateShortControllerName($short_name)
	{
		if(strpos($short_name, '\\') === 0)
		{
			// Fully qualified class name
			return $short_name;
		}
		
		$short_name = strtolower($short_name);
		
		$controllers = $this->engine->getAvailableControllers();
		
		if(isset($controllers[$short_name]))
		{
			return $controllers[$short_name];
		}
		
		throw new \Exception(sprintf('The short controller name "%s" could not be translated into a fully qualified class name, check the return value of %s->getAvailableControllers().', $short_name, get_class($this->engine)));
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Cleans a route literal from escape characters "\".
	 * 
	 * @param  string
	 * @return string
	 */
	protected function cleanLiteral($string)
	{
		return preg_replace('/\\\\(\\\\|:|\\(|\\))/', '$1', $string);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Validates this destination object for valid destination
	 * and required captures/capture options etc.
	 * 
	 * @throws Exception
	 */
	abstract protected function doValidation(Tokenizer $tokenizer);
	
	/**
	 * Returns a list of compiled routes for this mapping.
	 * 
	 * @return array(RouteInterface)
	 */
	abstract public function getCompiled();
	
	/**
	 * Returns a string of PHP code which will create the compiled routes for
	 * this mapping when run.
	 * 
	 * @param  string   The variable to assign the compiled routes to, must end with a []
	 * @param  string   The variable name of the variable containing the array
	 *                  of available controllers array(short_name => class)
	 * @param  string   The variable name of the variable containing the current engine
	 * @return string   PHP code
	 */
	abstract public function getCacheCode($var_name, $controller_var, $engine_var);
}


/* End of file AbstractDestination.php */
/* Location: src/php/Inject/Web/Router/Generator/Destination */