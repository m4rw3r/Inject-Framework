<?php
/*
 * Created by Martin Wernståhl on 2011-03-23.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\RouterGenerator;

/**
 * Handles the parsing of a Mapping's to() method and also the code generation
 * for most of the code to match and execute the target destination.
 */
abstract class DestinationHandler
{
	/**
	 * Parses the $to value given to a Mapping and returns false if this
	 * destination does not match, and a DestinationHandlerInterface object if it does.
	 * 
	 * @param  mixed    The new to() value
	 * @param  Mapping  The mapping
	 * @param  mixed    The old to() value
	 * @return DestinationHandlerInterface
	 */
	public static function parseTo($new, Mapping $mapping, $old)
	{
		throw new \Exception(sprintf('The method %s::parseTo has not been implemented.', get_called_class()));
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * The wrapped Mapping instance.
	 * 
	 * @var \Inject\RouterGenerator\Mapping
	 */
	protected $mapping;
	
	/**
	 * The list of final constraints to be included in the output from
	 * getConditions().
	 * 
	 * @var array(string => mixed)  value can be: regex, string or any other scalar
	 */
	protected $constraints = array();
	
	/**
	 * The list of default options to merge with the captures.
	 * 
	 * @var array(string => scalar)
	 */
	protected $options = array();
	
	/**
	 * The tokenizer instance tokenizing the path pattern from the mapping.
	 * 
	 * @var \Inject\RouterGenerator\Tokenizer
	 */
	protected $tokenizer = null;
	
	/**
	 * A list of regex fragments related to the captures in the path pattern.
	 * 
	 * Will be used by the createRegex() to create a regex for the PATH_INFO on
	 * the getConditions() stage.
	 * 
	 * @var array(string => string)
	 */
	protected $regex_fragments = array();
	
	/**
	 * The capture intersection array to dump into the compiled code which will
	 * filter the regex data.
	 * 
	 * array_intersect_key() will be used with this array and the one containing
	 * the captures so that only the correct captures will be passed on.
	 * 
	 * @var array(string => int)
	 */
	protected $capture_intersect = array();
	
	// ------------------------------------------------------------------------
	
	public function __construct(Mapping $mapping)
	{
		$this->mapping = $mapping;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns the name of the wrapped route, run after prepare(), for reverse URI
	 * routing.
	 * 
	 * @return string
	 */
	public function getName()
	{
		return $this->mapping->getName();
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns the tokens used for the URI assembler, usually the tokens directly
	 * from the tokenization of the path pattern, run after prepare().
	 * 
	 * @return array  List of tokens from Tokenizer->getTokens()
	 */
	public function getTokens()
	{
		return $this->tokenizer->getTokens();
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns the capture intersection array from the wrapped mapping object,
	 * run after prepare().
	 * 
	 * This array will be array_intersect_key()ed with the regular expression
	 * capture array.
	 * 
	 * @var array(string => int)
	 */
	public function getCaptureIntersect()
	{
		return $this->capture_intersect;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns the route options which will be added to the regex captures,
	 * run after prepare().
	 * 
	 * @var array(string => string)
	 */
	public function getOptions()
	{
		return $this->options;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Prepares the destination handler for validation, called first after
	 * __construct().
	 * 
	 * @return void
	 */
	public function prepare()
	{
		$via             = $this->mapping->getVia();
		// Clean regex from trailing slashes and duplicated ones
		$path_pattern    = preg_replace('#(?<!^)/$|(/)/+#', '$1', $this->mapping->getPathPattern());
		$this->tokenizer = new Tokenizer($path_pattern);
		
		$this->constraints = $this->mapping->getConstraints();
		
		if( ! empty($via) && empty($this->constraints['REQUEST_METHOD']))
		{
			// Creates a regex matching the appropriate REQUEST_METHOD
			$this->constraints['REQUEST_METHOD'] = count($via) > 1 ? '/^(?:'.implode('|', array_map('strtoupper', $via)).')$/'
				: strtoupper($via[0]);
		}
		
		$this->regex_fragments = array_merge($this->tokenizer->getRegexFragments(), $this->mapping->getRegexFragments());
		$this->options         = array_merge($this->options, $this->mapping->getOptions());
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Prepares the contents of this DestinationHandlerInterface for all the other
	 * operations.
	 * 
	 * @return void
	 */
	public function compile()
	{
		// Make the longest matcher the first one, then it will usually be faster as
		// the longer the match, the more likely it is to fail
		// Also sort so non-regex matches are fisrt, as they are faster to match
		uasort($this->constraints, function($a, $b)
		{
			$amatch = @preg_match($a, '');
			$bmatch = @preg_match($b, '');
			
			if( ! $amatch && $bmatch)
			{
				// $a not a regex, sort that before
				return -1;
			}
			elseif($amatch && ! $bmatch)
			{
				// $b not a regex, sort that before
				return 1;
			}
			
			// Both regexes or both non-regex, normal sortion
			return strlen($b) - strlen($a);
		});
		
		$this->capture_intersect = array_flip(array_merge($this->mapping->getConstraintsCaptures(), $this->tokenizer->getCaptures()));
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
	 * Returns an array of PHP-code conditions in their order of importance all of
	 * which must match for the route to match, run after compile().
	 * 
	 * TODO: Document more thoroughly?
	 * 
	 * @param
	 * @param  string  Variable the preg captures should be placed in, no need to
	 *                 consider existing keys, this variable will be merged into
	 *                 another anyway
	 * @return array(string)  Array of condition strings, to be inserted into if():s
	 */
	public function getConditions(VariableNameContainerInterface $vars, $capture_dest_array)
	{
		$conds  = array();
		$tokens = $this->tokenizer->getTokens();
		$path   = $vars->getPathVariable();
		
		if(count($tokens) == 1 && $tokens[0][0] == Tokenizer::LITERAL)
		{
			if(preg_match('/^[^a-zA-Z]$/', $tokens[0][1]))
			{
				$conds[] = "$path == ".var_export($tokens[0][1], true);
			}
			else
			{
				// TODO: How to prevent multiple strtolower()s?
				$conds[] = "strtolower($path) == ".var_export(strtolower($tokens[0][1]), true);
			}
		}
		else
		{
			if($tokens[0][0] == Tokenizer::LITERAL && $tokens[0][1] != '/')
			{
				$conds[] = "stripos($path, ".var_export($tokens[0][1], true).") === 0";
			}
			
			$conds[] = 'preg_match('.var_export($this->createRegex($this->tokenizer->getTokens(), $this->regex_fragments), true).", $path, $capture_dest_array)";
		}
		
		// PATH_INFO first, then the other constraints
		foreach($this->constraints as $variable => $pattern)
		{
			$cond = $vars->getIssetParamCode($variable);
			
			empty($cond) OR $cond .=' && ';
			
			if(@preg_match($pattern, '') !== false)
			{
				// Regex compiles
				$cond .= "preg_match(".var_export($pattern, true).", ".$vars->getParamCode($variable).", $capture_dest_array)";
			}
			else
			{
				$cond .= $vars->getParamCode($variable).' === '.var_export($pattern, true);
			}
			
			$conds[] = $cond;
		}
		
		return $conds;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Validates the settings supplied on the Mapping object, check with the supplied params
	 * if controllers exist etc. , run after prepare() but before compile().
	 * 
	 * @param  array(mixed)  Array of passed parameters from CodeGenerator
	 * @return void
	 */
	abstract public function validate(array $validation_params);
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns the code to be run which will return the response from the attached destination,
	 * run after prepare().
	 * 
	 * @param  string  The variable name container, use this to generate appropriate
	 *                 variable names in the generated code
	 * @param  string  The variable containing the regular expression matches from preg_match
	 * @return string
	 */
	abstract public function getCallCode(VariableNameContainerInterface $variable_names, $matches_var);
}


/* End of file DestinationHandler.php */
/* Location: lib/Inject/RouterGenerator */