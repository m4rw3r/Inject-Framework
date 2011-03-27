<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Route;

/**
 * A compiled route.
 */
abstract class AbstractRoute
{
	// TODO: Allow reverse routing
	// TODO: Store result for a matched route to be able to reuse those matches in creating the new url
	
	/**
	 * The regular expression pattern to use.
	 * 
	 * @var string
	 */
	protected $pattern;
	
	/**
	 * Options to merge with matches from the pattern and return to the router.
	 *
	 * @var array(string => string)
	 */
	protected $options = array();
	
	/**
	 * Array used to compute the key intersection of the regular expression
	 * matching results, this to remove the integer keys from the regex result.
	 * 
	 * @var array(string => string)
	 */
	protected $capture_intersect;
	
	/**
	 * A list of accepted request methods, accepts all if empty.
	 * 
	 * @var array(string)
	 */
	protected $accepted_request_methods = array();
	
	/**
	 * Options after matching.
	 * 
	 * @var array(string => string)
	 */
	protected $parsed_options = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @param  string  The regular expression pattern
	 * @param  array(string => string)  List of options to return if this route matches
	 * @param  array(string => int)  List of keys to intersect to get the options from
	 *                               the regex captures
	 * @param  array(string)  List of accepted HTTP request methods
	 */
	public function __construct($pattern, array $options, array $capture_intersect, array $accepted_request_methods)
	{
		$this->pattern = $pattern;
		$this->options = $options;
		$this->capture_intersect = $capture_intersect;
		$this->accepted_request_methods = $accepted_request_methods;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __invoke($env)
	{
		// TODO: Do other constrains checking
		if(empty($this->accepted_request_methods) OR in_array($env['web.request'], $this->accepted_request_methods))
		{
			if(preg_match($this->pattern, $env['web.uri'], $result))
			{
				$env['web.path_parameters'] = array_merge($this->options, $this->filterRegexResult($result));
				
				return $this->dispatch($env);
			}
		}
		
		return array(404, array('X-Cascade' => 'pass'), '');
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns a callback which is to be run by the application, this
	 * method is called after matches() has returned true.
	 * 
	 * @param  mixed
	 * @return callback
	 */
	abstract public function dispatch($env);
	
	// ------------------------------------------------------------------------
	
	/**
	 * Filters out the empty regex captures as well as the not used captures,
	 * ie. numeric indices.
	 * 
	 * @param  array(mixed => mixed)
	 * @return array(string => string)
	 */
	protected function filterRegexResult(array $data)
	{
		$r = array();
		
		foreach(array_intersect_key($data, $this->capture_intersect) as $k => $v)
		{
			if( ! empty($v))
			{
				// TODO: urlclean?
				$r[$k] = $v;
			}
		}
		
		return $r;
	}
}


/* End of file AbstractRoute.php */
/* Location: src/php/Inject/Web/Router/Route */