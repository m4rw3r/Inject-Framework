<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;

use \Inject\Web\Router\CompiledRoute;
use \Inject\Web\Router\CompiledApplicationRoute;
use \Inject\Web\Router\CompiledCallbackRoute;

/**
 * Object returned by the router's match() method, used to represent a route
 * before it is compiled and/or cached.
 * 
 * Syntax for the patterns is the same as Ruby on Rails 3.0
 */
class Mapping
{
	// TODO: Named Routes
	// TODO: Implement another compiled class for routes without matches, ie. static routes
	
	/**
	 * The input pattern.
	 * 
	 * @var string
	 */
	protected $raw_pattern = '*url';
	
	/**
	 * Regular expression pattern fragments for the pattern.
	 * 
	 * @var string
	 */
	protected $regex_fragments = array();
	
	/**
	 * Options to merge with matches from the pattern and return to the router.
	 *
	 * @var array(string => string)
	 */
	protected $options = array();
	
	/**
	 * The pre-compile representation of the routing destination.
	 * 
	 * @var mixed
	 */
	protected $to;
	
	/**
	 * The special regex constraints for certain captures, used for compilation.
	 * 
	 * @var array(string => string)
	 */
	protected $constraints = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\Web\Router\Generator\Mapping  self
	 */
	public function path($pattern, array $regex_patterns = array())
	{
		// Normalize the pattern
		strpos($pattern, '/') === 0 OR $pattern = '/'.$pattern;
		
		$this->raw_pattern     = $pattern;
		$this->regex_fragments = $regex_patterns;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the resulting data array for this connection, will be used to both
	 * determine action and controller, and also to set the options passed to the
	 * controller.
	 * 
	 * The controller and action keys are the names of the controller and
	 * action passed to the router instance. The controller name will be passed
	 * to the application which then will chose which controller to instantiate.
	 * 
	 * The controller and action can also be set using captures in the pattern.
	 * 
	 * @param  string|Redirect
	 * @return \Inject\Web\Router\Generator\Mapping  self
	 */
	public function to($to)
	{
		$this->to = $to;
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the regular expression constraints for a specified $env parameter.
	 * 
	 * @param  array(string => mixed)  List of environment keys and their
	 *         conditions, will usually be a regular expression, but can also
	 *         be an integer, double, boolean or null value.
	 * @return \Inject\Web\Router\Generator\Mapping  self
	 */
	public function constraints(array $options)
	{
		$this->constraints = array_merge($this->constraints, $options);
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the accepted request methods for this route, defaults to all.
	 * 
	 * @param  string|array  a request method or list of request methods this route accepts
	 * @return \Inject\Web\Router\Generator\Mapping  self
	 */
	public function via($request_method)
	{
		// Creates a regex:
		$this->constraints['web.method'] = '/'.implode('|'.array_map('strtoupper', (array) $request_method)).'/';
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the destination of this route.
	 * 
	 * @return string|Object
	 */
	public function getTo()
	{
		return $this->to;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the options array of this route.
	 * 
	 * @return string
	 */
	public function getOptions()
	{
		return $this->options;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the constraints array for this route.
	 * 
	 * @return array(string => string)
	 */
	public function getConstraints()
	{
		return $this->constraints;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function getRawPattern()
	{
		return $this->raw_pattern;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function getRegexFragments()
	{
		return $this->regex_fragments;
	}
}


/* End of file Mapping.php */
/* Location: src/php/Inject/Web/Router/Generator */