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
	
	/**
	 * Code fragment assembling the URI.
	 * 
	 * The fragment will return null in the event that required parameters are missing.
	 * 
	 * @var string
	 */
	protected $uri_assembler;
	
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
		
		$via         = $this->route->getVia();
		// Clean regex from trailing slashes and duplicated ones
		$path_pattern = preg_replace('#(?<!^)/$|(/)/+#', '$1', $this->route->getPathPattern());
		$tokenizer   = new Tokenizer($path_pattern);
		
		$this->constraints = $this->route->getConstraints();
		
		if( ! empty($via) && empty($this->constraints['REQUEST_METHOD']))
		{
			// Creates a regex matching the appropriate REQUEST_METHOD
			$this->constraints['REQUEST_METHOD'] = '/^(?:'.implode('|', array_map('strtoupper', $via)).')$/';
		}
		
		$this->regex_fragments = array_merge($tokenizer->getRegexFragments(), $this->route->getRegexFragments());
		
		$this->doValidation($tokenizer);
		
		$this->constraints['PATH_INFO'] = $this->createRegex($tokenizer->getTokens(), $this->regex_fragments);
		
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
				throw new \Exception(sprintf('The route %s has a faulty regex for the constraint %s: "%s".', $this->route->getPathPattern(), $key, $val));
			}
		}
		
		// TODO: Allow captures from constraints too:
		$this->capture_intersect = array_flip(array_merge($this->route->getConstraintsCaptures(), $tokenizer->getCaptures()));
		
		$this->uri_assembler = $this->createUriAssembler($tokenizer);
		
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
	 * 
	 * 
	 * @return 
	 */
	public function createUriAssembler(Tokenizer $tokenizer)
	{
		$tokens = $tokenizer->getTokens();
		$code   = '';
		$end    = count($tokens);
		$concat = false;
		
		for($i = 0; $i < $end; $i++)
		{
			list($type, $data) = $tokens[$i];
			
			switch($type)
			{
				case Tokenizer::LITERAL:
					$code  .= ($concat ? '.' : '').var_export($data, true);
					$concat = true;
					break;
					
				case Tokenizer::CAPTURE:
					$code  .= ($concat ? '.' : '').'$options['.var_export($data, true).']';
					$concat = true;
					break;
					
				case Tokenizer::OPTBEGIN:
					$conds = array();
					
					// Check for nested captures
					$ind = 1;
					for($j = $i + 1; $j < $end && $ind > 0; $j++)
					{
						if($tokens[$j][0] == Tokenizer::CAPTURE && $ind == 1)
						{
							$conds[] = 'empty($options['.var_export($tokens[$j][1], true).'])';
						}
						elseif($tokens[$j][0] == Tokenizer::OPTBEGIN)
						{
							$ind++;
						}
						elseif($tokens[$j][0] == Tokenizer::OPTEND)
						{
							$ind--;
						}
					}
					
					// Do we have any nested conditions?
					if( ! empty($conds))
					{
						$code  .= ($concat ? '.' : '').'('.implode(' OR ', $conds).' ? \'\' : ';
						$concat = false;
					}
					else
					{
						// Nope, scroll past the conditional pattern
						$indent = 1;
						
						for($j = 0;$j < $end && $indent > 0; $j++)
						{
							if($tokens[$j][0] == Tokenizer::OPTEND)
							{
								$indent--;
							}
							elseif($tokens[$j][0] == Tokenizer::OPTBEGIN)
							{
								$indent++;
							}
						}
						
						$i = $j;
					}
					
					break;
					
				case Tokenizer::OPTEND:
					$code  .= ')';
					$concat = true;
			}
		}
		
		// Find required matches:
		$required = array();
		$captures = array();
		$indent   = 0;
		
		foreach($tokens as $tok)
		{
			list($type, $data) = $tok;
			
			switch($type)
			{
				case Tokenizer::CAPTURE:
					if($indent == 0)
					{
						$captures[] = $data;
						$required[] = 'empty($options['.var_export($data, true).'])';
					}
					break;
				
				// Optional section start
				case Tokenizer::OPTBEGIN:
					$indent++;
					break;
				
				// Optional section end
				case Tokenizer::OPTEND:
					$indent--;
					break;
			}
		}
		
		if( ! empty($required))
		{
			$code = '('.implode(' OR ', $required).' ? '.var_export($captures, true).' : '.$code.')';
		}
		
		return $code;
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
	 * 
	 * 
	 * @return 
	 */
	public function getUriGenerator()
	{
		return 'function($options) { return '.$this->uri_assembler.'; }';
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the name of the route this destination leads to, null if no name.
	 * 
	 * @return string|null
	 */
	public function getName()
	{
		return $this->route->getName();
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
	 * Returns a compiled route for this destination and mapping.
	 * 
	 * @return RouteInterface
	 */
	abstract public function getCompiled();
	
	/**
	 * Returns a string of PHP code which will create the compiled routes for
	 * this mapping when run.
	 * 
	 * @param  string   The variable to assign the compiled routes to,
	 *                  must end with a []
	 * @param  string   The variable name of the variable containing the array
	 *                  of available controllers array(short_name => class)
	 * @param  string   The variable name of the variable containing the current engine
	 * @return string   PHP code
	 */
	abstract public function getCacheCode($var_name, $controller_var, $engine_var);
}


/* End of file AbstractDestination.php */
/* Location: src/php/Inject/Web/Router/Generator/Destination */