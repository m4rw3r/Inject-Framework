<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;
use \Inject\Core\Application\Engine;
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
	// TODO: Implement support for calling condition methods on the request object
	// TODO: Implement support for assigning a specified Response object to return, eg. for redirects
	
	const CONTROLLER_ACTION = 1;
	const ONLY_ACTION = 2;
	const ONLY_CONTROLLER = 3;
	const CALLBACK = 4;
	const APPLICATION = 5;
	const REDIRECT = 6;
	
	/**
	 * A list of default values for controller requests.
	 * 
	 * @var array(string => string)
	 */
	protected $defaults_controller = array(
			'action' => 'index',
			'format' => 'html'
		);
	
	/**
	 * The input pattern.
	 * 
	 * @var string
	 */
	protected $raw_pattern;
	
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
	 * A list of accepted request methods, accepts all if empty.
	 * 
	 * @var array(string)
	 */
	protected $accepted_request_methods = array();
	
	/**
	 * The special regex constraints for certain captures, used for compilation.
	 * 
	 * @var array(string => string)
	 */
	protected $constraints = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct($pattern)
	{
		// Normalize the pattern
		strpos($pattern, '/') === 0 OR $pattern = '/'.$pattern;
		
		$this->raw_pattern = $pattern;
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
	 * @param  array   List of option_name => data
	 * @return \Inject\Web\Router\Connection  self
	 */
	public function to($to)
	{
		$this->to = $to;
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the regular expression constraints for the captures.
	 * 
	 * @param  array   List of capture_name => regular_expression_fragment
	 * @return \Inject\Web\Router\Connection  self
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
	 * @return \Inject\Web\Router\Connection  self
	 */
	public function via($request_method)
	{
		$this->accepted_request_methods = array_map('strtoupper', (array) $request_method);
		
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
	 * Returns the raw pattern of this route.
	 * 
	 * @return string
	 */
	public function getRawPattern()
	{
		return $this->raw_pattern;
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
	 * Returns a list of accepted HTTP methods for this route, always uppercase,
	 * empty if to allow all.
	 * 
	 * @return array(string)
	 */
	public function getAcceptedRequestMethods()
	{
		return $this->accepted_request_methods;
	}
}


/* End of file Mapping.php */
/* Location: src/php/Inject/Web/Router/Generator */