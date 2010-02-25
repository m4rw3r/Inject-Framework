<?php
/*
 * Created by Martin Wernståhl on 2010-02-23.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * The route builder for a regex route.
 */
class Inject_Request_HTTP_URI_RouteBuilder_Regex extends Inject_Request_HTTP_URI_RouteBuilder_Abstract
{
	/**
	 * A token containing text to match.
	 */
	const LITERAL = 1;
	/**
	 * A token containing a capture name.
	 */
	const CAPTURE = 2;
	/**
	 * A token telling that an optional match begins.
	 */
	const OPTBEGIN = 3;
	/**
	 * A token telling that an optional match ends.
	 */
	const OPTEND = 4;
	
	/**
	 * A list of names which are disallowed to be used as capture names.
	 * 
	 * @var array
	 */
	protected static $disallowed_captures = array('_class');
	
	/**
	 * The generated regular expression to match the URI.
	 * 
	 * @var string
	 */
	protected $regex;
	
	/**
	 * A list of the parameters which are required by the pattern,
	 * used by the reverse routing.
	 * 
	 * @var array
	 */
	protected $required_keys = array();
	
	/**
	 * A list of captures which are used by the pattern.
	 * 
	 * @var array
	 */
	protected $used_captures = array();
	
	/**
	 * The PHP code which will assemble the reverse route from the $options array.
	 * 
	 * @var string
	 */
	protected $reverse_code;
	
	public function __construct($pattern, $options)
	{
		parent::__construct($pattern, $options);
		
		// Create a list of tokens to convert
		$tokens = $this->tokenize($pattern);
		
		$this->regex = $this->createRegex($tokens);
		
		list($required, $reverse) = $this->createReverseCode($tokens);
		
		// The _uri segment is not needed to match as it is dynamic
		$this->required_keys = array_diff($required, array('_uri'));
		$this->reverse_code = $reverse;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Tokenizes the pattern into a token list.
	 * 
	 * @param  string
	 * @return array
	 */
	public function tokenize($pattern)
	{
		// Build token list
		$list = array();
		// Number of optional segments
		$num_opt = 0;
		
		// Nowdoc to avoid PHP's \\ escaping occuring in string literals, only PHP 5.3
		/* $regex = <<<'EOP'
/^([\w\W]*?(?:\\\\|\\:|\\\(|\\\))?)(?::(\w*)|(\(|\)))([\w\W]*)$/u
EOP;*/
		
		$regex = '/^([\w\W]*?(?:\\\\\\\\|\\\\:|\\\\\\(|\\\\\\))?)(?::(\w*)|(\\(|\\)))([\w\W]*)$/';
		
		while(preg_match($regex, $pattern, $matches))
		{
			list(, $literal, $capture, $operator, $pattern) = $matches;
			
			if( ! empty($literal))
			{
				$list[] = array(self::LITERAL, self::cleanLiteral($literal));
			}
			
			if( ! empty($capture))
			{
				// Check for invalid captures:
				if(in_array($capture, self::$disallowed_captures))
				{
					throw new Exception(sprintf('The capture "%s" is not allowed to be used as a capture name, in pattern "%s".', $capture, $this->pattern));
				}
				
				$this->used_captures[] = $capture;
				
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
						throw new Exception(sprintf('Missing start parenthesis in route "%s".', $this->pattern));
					}
					
					$list[] = array(self::OPTEND, ')');
				}
				
				
			}
		}
		
		// Don't forget the trailing literals!
		if( ! empty($pattern))
		{
			$list[] = array(self::LITERAL, self::cleanLiteral($pattern));
		}
		
		// Check parse error
		if($num_opt > 0)
		{
			throw new Exception(sprintf('Missing end parenthesis in route "%s".', $this->pattern));
		}
		
		return $list;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates the regex for this rule.
	 * 
	 * @param  array
	 * @return string
	 */
	public function createRegex($token_list)
	{
		// Start to create the regex
		$regex = '';
		$num_opt = 0;
		
		foreach($token_list as $t)
		{
			list($type, $data) = $t;
			
			switch($type)
			{
				case self::LITERAL:
					// escape the literal so we won't have junk in our regexes
					$regex .= addcslashes(preg_quote($data, '#'), '\'\\');
					break;
				
				case self::CAPTURE:
					// Capture and then also check for regex matching constraints
					
					// Normal captures: \w+, _uri special capture: .*
					$rule = $data == '_uri' ? '.*' : '\w+';
					
					// Override the capture rule if constraint is present
					if(isset($this->options['_constraints'][$data]))
					{
						$rule = addcslashes($this->options['_constraints'][$data], '\'\\');
					}
					
					$regex .= '(?<'.addcslashes(preg_quote($data, '#'), '\'\\').'>'.$rule.')';
					break;
				
				// Optional section start
				case self::OPTBEGIN:
					$regex .= '(?:';
					break;
				
				// Optional section end
				case self::OPTEND:
					$regex .= ')?';
					break;
			}
		}
		
		return $regex;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates the reverse route code which renders the URI.
	 * 
	 * @param  array
	 * @return array(array required_keys, string code)
	 */
	public function createReverseCode(&$token_list)
	{
		// Code parts to be concatenated together
		$code = array();
		// Keys that are required for this part
		$required_keys = array();
		
		// List of non-special captures that has been used
		$not_special = array_diff_key($this->used_captures, self::$special_options);
		$not_special_in_keys = empty($not_special) ? array() : array_combine($not_special, array_pad(array(), count($not_special), true));
		
		while( ! empty($token_list))
		{
			list($type, $data) = array_shift($token_list);
			
			switch($type)
			{
				case self::LITERAL:
					
					$code[] = '\''.addcslashes($data, '\'\\').'\'';
					
					break;
				
				case self::CAPTURE:
					
					if($data == '_uri')
					{
						// The "magic" _uri key!
						
						if(empty($not_special))
						{
							$code[] = 'Inject_Request_HTTP_URI::createParameterList($params)';
						}
						else
						{
							$code[] = 'Inject_Request_HTTP_URI::createParameterList($params, '.var_export($not_special_in_keys, true).')';
						}
						
						$required_keys[] = $data;
					}
					else
					{
						// Normal captures print a value
						$required_keys[] = $data;
						$code[] = '$options[\''.$data.'\']';
					}
					
					break;
				
				// Optional section start
				case self::OPTBEGIN:
					
					// Render subpattern
					list($keys, $data) = $this->createReverseCode($token_list);
					
					// Just visual sugar on the URI, no need to render
					if(empty($keys))
					{
						continue;
					}
					
					// Magic comes here (_uri)!
					if(in_array('_uri', $keys))
					{
						unset($keys[array_search('_uri', $keys)]);
						
						// Normal conditions for extra required captures
						$condition = array();
						foreach($keys as $key)
						{
							$condition[] = 'isset($options[\''.$key.'\'])';
						}
						
						// No extra captures
						if(empty($condition))
						{
							// Let it check if there are some other parameters there
							if( ! empty($not_special))
							{
								$condition = array('array_diff_key($params, '.var_export($not_special_in_keys, true).')');
							}
							else
							{
								// Faster as we don't need diff
								$condition = array('( ! empty($params))');
							}
						}
					}
					// Default:
					else
					{
						// Create conditionals to tell if we should render the optional subpart
						$condition = array();
						foreach($keys as $key)
						{
							$condition[] = 'isset($options[\''.$key.'\'])';
						}
					}
					
					$code[] = '('.implode(' && ', $condition).' ? '.$data.' : \'\')';
					
					break;
				
				// Optional section end
				case self::OPTEND:
					
					// We're done with this part
					break 2;
			}
		}
		
		return array($required_keys, implode('.', $code));
	}
	
	// ------------------------------------------------------------------------
	
	public function getMatchCode()
	{
		return 'if(preg_match(\'#^'.addcslashes($this->regex, "'\\").'$#u\', $uri, $m))
		{
			// '.$this->pattern.'
			return '.(empty($this->options) ? '$m;' : 'array_merge('.var_export($this->options, true).', $m);').'
		}';
	}
	
	// ------------------------------------------------------------------------
	
	public function getReverseMatchCode()
	{
		if(empty($this->required_keys))
		{
			// No need to check for parameters, all are optional
			if( ! $this->hasAction() OR ! $this->hasClass())
			{
				return '// '.$this->pattern.'
				return '.$this->reverse_code.';';
			}
			else
			{
				// Check for action
				return 'if($options[\'_action\'] === \''.$this->getAction().'\')
					{
						// '.$this->pattern.'
						return '.$this->reverse_code.';
					}';
			}
		}
		else
		{
			// Determine which variable to look in, $options also contains _controller etc.
			$check_var = array_intersect($this->required_keys, self::$special_options) ? '$options' : '$params';
			
			// Check if there are any parameters which are missing
			$param_check = ' ! array_diff_key('.var_export(array_combine($this->required_keys, array_pad(array(), count($this->required_keys), true)), true).', '.$check_var.')';
			
			// No action, no action check, no class, action check already done
			if( ! $this->hasAction() OR ! $this->hasClass())
			{
				return 'if('.$param_check.')
				{
					// '.$this->pattern.'
					return '.$this->reverse_code.';
				}';
			}
			else
			{
				// Check for action
				return 'if($options[\'_action\'] === \''.$this->getAction().'\' &&'.$param_check.')
				{
					// '.$this->pattern.'
					return '.$this->reverse_code.';
				}';
			}
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns true if the class is dynamically determined.
	 * 
	 * @return bool
	 */
	public function hasDynamicClass()
	{
		return in_array('_controller', $this->used_captures);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns true if the action is dynamically determined.
	 * 
	 * @return bool
	 */
	public function hasDynamicAction()
	{
		return in_array('_action', $this->used_captures);
	}
}


/* End of file RouteBuilderRegex.php */
/* Location: ./lib/Inject/Request/HTTP/URI */