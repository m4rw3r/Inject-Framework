<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;
use \Inject\Web\Router\CompiledRoute;

/**
 * Object tokenizing the supplied pattern into parts.
 */
class Tokenizer
{
	/**
	 * A token containing text to match.
	 */
	const LITERAL = 'LITERAL';
	/**
	 * A token containing a capture name.
	 */
	const CAPTURE = 'CAPTURE';
	/**
	 * A token telling that an optional match begins.
	 */
	const OPTBEGIN = 'OPTBEGIN';
	/**
	 * A token telling that an optional match ends.
	 */
	const OPTEND = 'OPTEND';
	
	/**
	 * The input pattern.
	 * 
	 * @var string
	 */
	protected $raw_pattern;
	
	/**
	 * A list of tokens.
	 * 
	 * @var array(CONSTANT => string)
	 */
	protected $tokens = array();
	
	/**
	 * A list of captures which are used by the pattern, used during compilation.
	 * 
	 * @var array(string)
	 */
	protected $used_captures = array();
	
	/**
	 * A list of mandatory captures, used for validation during compilation.
	 * 
	 * @var array(string)
	 */
	protected $required_captures = array();
	
	/**
	 * The special regex patterns for certain captures, used for compilation.
	 * 
	 * @var array(string => string)
	 */
	protected $regex_patterns = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct($pattern)
	{
		$this->raw_pattern = $pattern;
		
		$this->tokens = $this->tokenize();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the raw pattern tokenized by this tokenizer instance.
	 * 
	 * @return string
	 */
	public function getRawPattern()
	{
		return $this->raw_pattern;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of all the parsed tokens.
	 * 
	 * @return array(CONSTANT => string)
	 */
	public function getTokens()
	{
		return $this->tokens;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of all the captures encountered in the pattern.
	 * 
	 * @return array(string)
	 */
	public function getCaptures()
	{
		return $this->used_captures;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns an array of required captures.
	 * 
	 * @return array(string)
	 */
	public function getRequiredCaptures()
	{
		return $this->required_captures;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the regex patterns which are dictated by the pattern (currently only
	 * the "*" capture).
	 * 
	 * @return array(string => string)   Key = capture name, value = regex part
	 */
	public function getRegexFragments()
	{
		return $this->regex_patterns;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Tokenizes the pattern into a token list.
	 * 
	 * @param  string
	 * @return array
	 */
	protected function tokenize()
	{
		$pattern = $this->raw_pattern;
		// Build token list
		$list = array();
		// Number of optional segments
		$num_opt = 0;
		
		// Nowdoc to avoid PHP's \\ escaping occuring in string literals, only PHP 5.3
		// TODO: UTF-8ize this regex, \w and \W does not match the proper characters for UTF-8
		$regex = <<<'EOP'
/^([\w\W]*?)(?:(?<!\\)(?:(:|\*)(\w+)|(\(|\))))([\w\W]*)$/u
EOP;
		
		while(preg_match($regex, $pattern, $matches))
		{
			list(, $literal, $capture_type, $capture, $operator, $pattern) = $matches;
			
			if( ! empty($literal))
			{
				$list[] = array(self::LITERAL, $this->cleanLiteral($literal));
			}
			
			if( ! empty($capture))
			{
				if($capture_type == '*')
				{
					// Wildcard capture, set different constraint on that:
					$this->regex_patterns[$capture] = '.*?';
				}
				
				$this->used_captures[] = $capture;
				// Store if it is mandatory:
				$num_opt == 0 && $capture_type == ':' && $this->required_captures[] = $capture;
				
				$list[] = array(self::CAPTURE, $capture);
			}
			elseif( ! empty($operator))
			{
				if($operator == '(')
				{
					$num_opt++;
					
					$list[] = array(self::OPTBEGIN, '(');
				}
				elseif($operator == ')')
				{
					$num_opt--;
					
					// Check parse error
					if($num_opt < 0)
					{
						// TODO: Exception
						throw new \Exception(sprintf('Missing start parenthesis in pattern "%s".', $this->raw_pattern));
					}
					
					$list[] = array(self::OPTEND, ')');
				}
			}
		}
		
		// Don't forget the trailing literals!
		if( ! empty($pattern))
		{
			$list[] = array(self::LITERAL, $this->cleanLiteral($pattern));
		}
		
		// Check parse error
		if($num_opt > 0)
		{
			// TODO: Exception
			throw new \Exception(sprintf('Missing end parenthesis in pattern "%s".', $this->raw_pattern));
		}
		
		return $list;
	}
	
	/**
	 * Cleans a route literal from escape characters "\".
	 * 
	 * @param  string
	 * @return string
	 */
	protected function cleanLiteral($string)
	{
		return preg_replace('/\\\\(\\\\|:|\\*|\\(|\\))/', '$1', $string);
	}
}


/* End of file Tokenizer.php */
/* Location: src/php/Inject/Web/Router/Generator */