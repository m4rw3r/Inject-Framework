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
	 * The list of constraints to use.
	 * 
	 * @var array(string => regex)
	 */
	protected $constraints;
	
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
	 * @var array(string => int)
	 */
	protected $capture_intersect;
	
	/**
	 * The list of parameters matched by this route when it has matched a route
	 * 
	 * @var array(string => string)
	 */
	protected $params = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @param  array(string => string)  The regular expression patterns
	 * @param  array(string => string)  List of options to return if this route matches
	 * @param  array(string => int)     List of keys to intersect to get the options from
	 *                                  the regex captures
	 */
	public function __construct(array $constraints, array $options, array $capture_intersect, $uri_generator)
	{
		$this->constraints       = $constraints;
		$this->options           = $options;
		$this->capture_intersect = $capture_intersect;
		$this->uri_generator     = $uri_generator;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __invoke($env)
	{
		$capture_data = array();
		
		foreach($this->constraints as $key => $pattern)
		{
			if(isset($env[$key]) && preg_match($pattern, $env[$key], $result))
			{
				$capture_data = array_merge($capture_data, $result);
			}
			else
			{
				// Failure
				return array(404, array('X-Cascade' => 'pass'), '');
			}
		}
		
		$env['web.route'] = clone $this;
		$env['web.route']->setMatchedParameters(array_merge($this->options, $this->filterRegexResult($capture_data)));
		
		return $this->dispatch($env);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function generate(array $options)
	{
		$c = $this->uri_generator;
		return $c($options);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the parameters which this route matched, used internally to store
	 * a copy of the matched route so it will be possible to get matched parameters.
	 * 
	 * @param  array(string => string)
	 * @return void
	 */
	public function setMatchedParameters(array $params)
	{
		$this->params = $params;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the parameter for this matched route, falls back to the $default
	 * parameter if the specified parameter does not exist.
	 * 
	 * @param  string
	 * @return string|null
	 */
	public function param($name, $default = null)
	{
		return isset($this->params[$name]) ? $this->params[$name] : $default;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the parameters for this matched route.
	 * 
	 * @return array(string => string)
	 */
	public function params()
	{
		return $this->params;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Dispatches the request to the route destination, called by __invoke if
	 * all the route conditions matches.
	 * 
	 * @param  mixed
	 * @return callback
	 */
	abstract protected function dispatch($env);
	
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
				// No need to clean them, if $env has been cleaned they are
				// clean as RFC 3875 specifies PATH_INFO as not-URL-encoded
				$r[$k] = $v;
			}
		}
		
		return $r;
	}
}


/* End of file AbstractRoute.php */
/* Location: src/php/Inject/Web/Router/Route */