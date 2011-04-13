<?php
/*
 * Created by Martin Wernståhl on 2011-03-23.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator\DestinationHandler;

use \Inject\Core\Engine;
use \Inject\Web\Router\Generator\Tokenizer;
use \Inject\Web\Router\Generator\Mapping;
use \Inject\Web\Router\Generator\DestinationHandlerInterface;

/**
 * 
 */
abstract class Base implements DestinationHandlerInterface
{
	/**
	 * The wrapped Mapping instance.
	 * 
	 * @var \Inject\Web\Router\Generator\Mapping
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
	 * @var \Inject\Web\Router\Generator\Tokenizer
	 */
	protected $tokenizer = null;
	
	/**
	 * A list of regex fragments related to the captures in the path pattern.
	 * 
	 * @var array(string => string)
	 */
	protected $regex_fragments = array();
	
	/**
	 * The capture intersection array to dump into the compiled code which will
	 * filter the regex data.
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
	
	public function getName()
	{
		return $this->mapping->getName();
	}
	
	// ------------------------------------------------------------------------
	
	public function getTokens()
	{
		return $this->tokenizer->getTokens();
	}
	
	// ------------------------------------------------------------------------
	
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
	
	public function compile()
	{
		// Make the longest matcher the first one, then it will usually be faster as
		// the longer the match, the more likely it is to fail
		uasort($this->constraints, function($a, $b)
		{
			return strlen($b) - strlen($a);
		});
		
		$this->capture_intersect = array_flip(array_merge($this->mapping->getConstraintsCaptures(), $this->tokenizer->getCaptures()));
		
		$this->compiled = true;
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
	
	public function getCaptureIntersect()
	{
		return $this->capture_intersect;
	}
	
	// ------------------------------------------------------------------------
	
	public function getOptions()
	{
		return $this->options;
	}
	
	// ------------------------------------------------------------------------
	
	/* abstract */ public function validate(Engine $engine)
	{
		throw new \RuntimeException('Not implemented method '.__METHOD__);
	}
	
	// ------------------------------------------------------------------------
	
	public function getConditions($env_var, $capture_dest_array, $controller_var)
	{
		$conds  = array();
		$tokens = $this->tokenizer->getTokens();
		
		if(count($tokens) == 1 && $tokens[0][0] == Tokenizer::LITERAL)
		{
			if(preg_match('/^[^a-zA-Z]$/', $tokens[0][1]))
			{
				$conds[] = "{$env_var}['PATH_INFO'] == ".var_export($tokens[0][1], true);
			}
			else
			{
				$conds[] = "strtolower({$env_var}['PATH_INFO']) == ".var_export(strtolower($tokens[0][1]), true);
			}
		}
		else
		{
			if($tokens[0][0] == Tokenizer::LITERAL && $tokens[0][1] != '/')
			{
				$conds[] = "stripos({$env_var}['PATH_INFO'], ".var_export($tokens[0][1], true).") === 0";
			}
			
			$conds[] = 'preg_match('.var_export($this->createRegex($this->tokenizer->getTokens(), $this->regex_fragments), true).", {$env_var}['PATH_INFO'], $capture_dest_array)";
		}
		
		// PATH_INFO first, then the other constraints
		foreach($this->constraints as $variable => $pattern)
		{
			$cond = "isset({$env_var}[".var_export($variable, true)."]) && ";
			
			if(@preg_match($pattern, '') !== false)
			{
				// Regex compiles
				$cond .= "preg_match(".var_export($pattern, true).", {$env_var}[".var_export($variable, true)."], $capture_dest_array)";
			}
			else
			{
				$cond .= "{$env_var}[".var_export($variable, true)."] === ".var_export($pattern, true);
			}
			
			$conds[] = $cond;
		}
		
		return $conds;
	}
	
	// ------------------------------------------------------------------------
	
	/* abstract */ public function getCallCode($env_var, $engine_var, $matches_var, $controller_var)
	{
		throw new \RuntimeException('Not implemented method '.__METHOD__);
	}
}


/* End of file Base.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */