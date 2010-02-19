<?php
/*
 * Created by Martin Wernståhl on 2010-02-19.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Represents a URI route.
 */
class Inject_Request_HTTP_URI_Route
{
	protected $pattern;
	
	protected $options = array();
	
	function __construct($pattern, array $options)
	{
		$this->pattern = $pattern;
		$this->options = $options;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Matches an uri and returns an array
	 * 
	 * @return 
	 */
	public function matchUri($uri)
	{
		// Do we have the compiled regex?
		if( ! isset($this->regex))
		{
			$regex = str_replace(array('(', ')'), array('(?:', ')?'), $this->pattern);

			preg_match_all('/(?<!\?):(\w+)/', $regex, $matches, PREG_SET_ORDER);

			foreach($matches as $m)
			{
				$regex = str_replace(':'.$m[1], '(?<'.$m[1].'>'.(isset($to['constraints'][$m[1]]) ? $to['constraints'][$m[1]] : '\w+').')', $regex);
			}
			
			$this->regex = $regex;
		}
		
		if(preg_match('#^'.$this->regex.'$#u', $uri, $m))
		{
			// get parameters from the regex, step 1: clean it from junk
			foreach($m as $k => $v)
			{
				// skip numeric
				if(is_numeric($k))
				{
					unset($m[$k]);
				}
			}
			
			// Merge with 
			return array_merge($this->options, $m);
		}
		else
		{
			return false;
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Reverse routes this rule with the supplied parameters.
	 * 
	 * @param  string
	 * @param  string
	 * @param  array
	 * @return string
	 */
	public function reverseRoute($controller, $action = null, $parameters = array())
	{
		$ret = $this->pattern;
		
		foreach($parameters as $k => $v)
		{
			if(strpos($ret, ':'.$k) !== false)
			{
				$ret = str_replace(':'.$k, urlencode($v), $ret);
			}
		}
		
		if(strpos($ret, ':action') !== false)
		{
			if(empty($action))
			{
				return false;
			}
			
			$ret = str_replace(':action', $action, $ret);
		}
		
		$ret = preg_replace('/\(.*?:.*?\)/', '', $ret);
		
		if(strpos(':', $ret) !== false)
		{
			return false;
		}
		
		return str_replace(array('(', ')'), array(), $ret);
	}
}


/* End of file Route.php */
/* Location: ./lib/Inject/Request/HTTP/URI */