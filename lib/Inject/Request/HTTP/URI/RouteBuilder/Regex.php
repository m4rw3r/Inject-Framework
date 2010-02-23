<?php
/*
 * Created by Martin Wernståhl on 2010-02-23.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * The route builder for a regex route.
 * 
 * TODO: Implement support for :_uri setting in reverse routing, so the extra parameters will end up at the correct place of the route
 * TODO: Add support for nested optionals?
 */
class Inject_Request_HTTP_URI_RouteBuilder_Regex extends Inject_Request_HTTP_URI_RouteBuilder_Abstract
{
	protected $regex;
	
	protected $keys = array();
	
	protected $required_keys = array();
	
	protected $reverse_code;
	
	public function __construct($pattern, $options)
	{
		parent::__construct($pattern, array_diff_key($options, array('_constraints' => true)));
		
		// Add optional segments
		$regex = str_replace(array('(', ')'), array('(?:', ')?'), $pattern);
		
		// Get catches
		preg_match_all('/(?<!\?):(\w+)/', $regex, $matches, PREG_SET_ORDER);

		foreach($matches as $m)
		{
			// Replace catches with their match code (can come from _constraints parameter)
			$regex = str_replace(':'.$m[1], '(?<'.$m[1].'>'.(isset($options['_constraints'][$m[1]]) ? $options['_constraints'][$m[1]] : '\w+').')', $regex);
		}
		
		// Done with regex generation
		$this->regex = $regex;
		
		// Build reverse routing code
		$code = array();
		
		// Find all segments and also the optional blocks
		while(preg_match('/([\w\W]*?)(?::([\w]*)|\\((.*?)\\))([\w\W]*)/', $pattern, $matches))
		{
			list(, $pre, $required, $opt, $pattern) = $matches;
			
			// Literal string, add
			if( ! empty($pre))
			{
				$code[] = "'".addcslashes($pre, "'")."'";
			}
			
			// No required segment, use optional routing
			if(empty($required))
			{
				// Get all captures in the optional segment
				preg_match_all('/(?<!\?):(\w+)/', $opt, $captures, PREG_SET_ORDER);
				
				$condition = array();
				
				// Create conditions, because of the segment needs all the matching params
				foreach($captures as $c)
				{
					$condition[] = 'isset($options[\''.$c[1].'\'])';
				}
				
				// No condition, no need to add anything
				if(empty($condition))
				{
					continue;
				}
				
				// Merge conditions
				$str = '('.implode(' && ', $condition).' ? ';
				$inner_code = array();
				
				// Replace the captures with code which prints the segment data
				while(preg_match('/([\w\W]*?)(?<!\?):(\w+)([\W\w]*)/', $opt, $keys))
				{
					list(, $pre_opt, $key, $opt) = $keys;
					
					// Literal preceeding the optional segment, add
					if( ! empty($pre_opt))
					{
						$inner_code[] = "'".addcslashes($pre_opt, "'")."'";
					}
					
					$inner_code[] = '$options[\''.$key.'\']';
				}
				
				// Literal after the optional segment, add
				if( ! empty($opt))
				{
					$inner_code[] = "'".addcslashes($opt, "'")."'";
				}
				
				// Add the optional segment
				$code[] = $str.implode('.', $inner_code).' : \'\')';
			}
			else
			{
				// Required segment, should already have been validated
				$this->required_keys[] = $required;
				
				$code[] = '$options[\''.$required.'\']';
			}
		}
		
		// Literal after all matches, add
		if( ! empty($pattern))
		{
			$code[] = "'".addcslashes($pattern, "'")."'";
		}
		
		// Implode with concatenation operator
		$this->reverse_code = implode('.', $code);
	}
	
	public function getMatchCode()
	{
		return 'if(preg_match(\'#^'.addcslashes($this->regex, "'").'$#u\', $uri))
		{
			return array_merge($m, '.var_export($this->options, true).');
		}';
	}
	
	public function getReverseMatchCode()
	{
		if(empty($this->required_keys))
		{
			// No need to check for parameters, all are optional
			if( ! $this->hasAction() OR ! $this->hasClass())
			{
				return 'return '.$this->reverse_code.';';
			}
			else
			{
				// Check for action
				return 'if($options[\'_action\'] === \''.$this->getAction().'\')
					{
						return '.$this->reverse_code.';
					}';
			}
		}
		else
		{
			// Check if there are any parameters which are missing
			$param_check = ' ! array_diff_key('.var_export(array_combine($this->required_keys, array_pad(array(), count($this->required_keys), true)), true).', $params)';
			
			// No action, no action check, no class, action check already done
			if( ! $this->hasAction() OR ! $this->hasClass())
			{
				return 'if('.$param_check.')
				{
					return '.$this->reverse_code.';
				}';
			}
			else
			{
				// Check for action
				return 'if($options[\'_action\'] === \''.$this->getAction().'\' &&'.$param_check.')
				{
					return '.$this->reverse_code.';
				}';
			}
		}
	}
}


/* End of file RouteBuilderRegex.php */
/* Location: ./lib/Inject/Request/HTTP/URI */