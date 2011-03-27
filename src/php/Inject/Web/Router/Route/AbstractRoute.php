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
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @param  array(string => string)  The regular expression patterns
	 * @param  array(string => string)  List of options to return if this route matches
	 * @param  array(string => int)     List of keys to intersect to get the options from
	 *                                  the regex captures
	 */
	public function __construct(array $constraints, array $options, array $capture_intersect)
	{
		$this->constraints       = $constraints;
		$this->options           = $options;
		$this->capture_intersect = $capture_intersect;
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
			if(preg_match($pattern, $env[$key], $result))
			{
				$capture_data = array_merge($capture_data, $result);
			}
			else
			{
				// Failure
				return array(404, array('X-Cascade' => 'pass'), '');
			}
		}
		
		$env['web.path_parameters'] = array_merge($this->options, $this->filterRegexResult($capture_data));
		
		return $this->dispatch($env);
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