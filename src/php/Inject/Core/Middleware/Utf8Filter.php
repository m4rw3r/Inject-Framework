<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core\Middleware;

/**
 * Filters $env and strips all non-UTF-8 characters from its strings, prevents
 * injection attacks and similar by confusing the escaping functions with bad UTF-8.
 * 
 * TODO: Currently only filters $env, how should we do with $_GET, $_POST etc.?
 * TODO: cont. maybe add those into $env, either here or in another middleware.
 */
class Utf8Filter implements MiddlewareInterface
{
	protected $next;
	
	// ------------------------------------------------------------------------
	
	public function setNext($next)
	{
		$this->next = $next;
	}
	
	// ------------------------------------------------------------------------

	public function __invoke($env)
	{
		$env = $this->clean($env);
		
		$callback = $this->next;
		return $callback($env);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Cleans the value's strings from invalid Utf-8 characters, provided they
	 * don't reside in an object.
	 * 
	 * @param  mixed
	 * @return mixed
	 */
	public function clean($value)
	{
		if(is_array($value))
		{
			// Create new array, to prevent keeping of old non-utf-8 keys
			$arr = array();
			
			foreach($value as $k => $v)
			{
				$arr[$this->clean($k)] = $this->clean($v);
			}
			
			return $arr;
		}
		elseif(is_string($value))
		{
			if( ! $this->compliant($value))
			{
				$value = iconv('UTF-8', 'UTF-8//IGNORE', $value);
			}
		}
		
		return $value;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns true if the supplied string is UTF8 compliant.
	 * 
	 * @param  string
	 * @return boolean
	 */
	public static function compliant($str)
	{
		if(strlen($str) == 0)
		{
			return true;
		}
		
		// If even just the first character can be matched, when the /u
		// modifier is used, then it's valid UTF-8. If the UTF-8 is somehow
		// invalid, nothing at all will match, even if the string contains
		// some valid sequences
		return preg_match('/^.{1}/us', $str, $ar) == 1;
	}
}


/* End of file Utf8Filter.php */
/* Location: src/php/Inject/Core/Middleware */