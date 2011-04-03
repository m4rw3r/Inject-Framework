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
	protected $raw_pattern = '';
	
	/**
	 * Regular expression pattern fragments for the pattern.
	 * 
	 * @var array(string => string)
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
	protected $to = array();
	
	/**
	 * A list of accepted HTTP request methods, empty equals all.
	 * 
	 * @var array
	 */
	protected $via = array();
	
	/**
	 * The special regex constraints for certain captures, used for compilation.
	 * 
	 * @var array(string => string)
	 */
	protected $constraints = array();
	
	/**
	 * TODO: Documentation
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
		
		$this->raw_pattern     = $this->raw_pattern.$pattern;
		$this->regex_fragments = array_merge($this->regex_fragments, $regex_patterns);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the destination for this route.
	 * 
	 * Possible destinations:
	 * 
	 * - Controller and action:
	 *     'controller_name#action'
	 *     '\Controller\Class\Name#action'
	 *     
	 *     Routes to a specific controller and action, if the specified
	 *     controller is not an existing class, the controller class
	 *     is decided by the associated Engine's getAvailableControllers()
	 *     method, which is usually just the lowercase class+namespace name
	 *     of the controller which is following the "Controller\" namespace.
	 *     
	 *     Example:
	 *     'foo#lol' => \MyApp\Controller\Foo->lolAction()
	 *     '\AnotherPackage\SomeController#test' => \AnotherPackage\SomeController->testAction()
	 * 
	 * - Controller (action decided by pattern):
	 *     'controller_name#'
	 *     '\Controller\Class\Name#'
	 *     
	 *     Routes to a specific controller, if the specified
	 *     controller is not an existing class, the controller class
	 *     is decided by the associated Engine's getAvailableControllers()
	 *     method, which is usually just the lowercase class+namespace name
	 *     of the controller which is following the "Controller\" namespace.
	 * 
	 * - Callback:
	 *     'callback::string'  (can only be a string because of compiling)
	 *     
	 *     The callback string must point to either a static method or a 
	 *     function which has at most one required parameter.
	 *     This method/function will receive the $env var as its sole parameter.
	 * 
	 * - Application engine:
	 *     '\Engine\Class\Name'
	 *     
	 *     This class must extend the \Inject\Core\Engine class.
	 *     If the route matches then its MiddlewareStack will be run with a slightly modified
	 *     $env hash:
	 *     PATH_INFO   = *uri capture (or uri option)
	 *     SCRIPT_NAME = SCRIPT_NAME + (PATH_INFO - *uri capture)
	 *     BASE_URI    = BASE_URI    + (PATH_INFO - *uri capture)
	 *     
	 *     The old route will be stored in $env['web.old_route'].
	 * 
	 * - Redirect:
	 *     $this->redirect('some_destination', 301)
	 *     
	 *     Will perform a redirect with a HTTP header, see
	 *     \Inject\Web\Router\Generator\Generator->redirect()
	 *     for more information on usage.
	 * 
	 * @param  string|Redirect
	 * @return \Inject\Web\Router\Generator\Mapping  self
	 */
	public function to($to)
	{
		if(is_object($to) && $to instanceof Redirection)
		{
			$this->to = array('redirect' => $to);
		}
		elseif(is_callable($to))
		{
			$this->to = array('callback' => $to);
		}
		elseif(class_exists($to))
		{
			$this->to = array('engine'   => $to);
		}
		elseif(preg_match('/^((?:\\\\)?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*)?#([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)?$/', $to, $matches))
		{
			$data = array();
			
			empty($matches[1]) OR $data['controller'] = $matches[1];
			empty($matches[2]) OR $data['action']     = $matches[2];
			
			if(empty($data))
			{
				// TODO: Exception
				throw new \Exception(sprintf('The route %s does not have a compatible To value, expected "controller#action", "controller#" or "#action".', $this->getRawPattern()));
			}
			
			$this->to = array_merge(array_intersect_key($this->to, array('controller' => 1, 'action' => 1)), $data);
		}
		else
		{
			// TODO: Exception
			throw new \Exception(sprintf('The route %s does not have a compatible To value, expected controller#action or controller#.', $this->getRawPattern()));
		}
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the regular expression constraints for specified $env parameters.
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
	 * @param  string|array|false  a request method or list of request methods
	 *                             this route accepts, false to allow all (default).
	 * @return \Inject\Web\Router\Generator\Mapping  self
	 */
	public function via($request_method)
	{
		if($request_method === false)
		{
			$this->via = array();
		}
		else
		{
			$this->via = array_merge($this->via, (Array)$request_method);
		}
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Specifies the default values for the optional captures, these will also be
	 * passed on even if there are no captures with that name.
	 * 
	 * @param  array(string => string)
	 * @return \Inject\Web\Router\Generator\Mapping  self
	 */
	public function defaults(array $defaults)
	{
		$this->options = array_merge($this->options, $defaults);
		
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
	public function getVia()
	{
		return $this->via;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the raw pattern for this route.
	 * 
	 * @return string
	 */
	public function getRawPattern()
	{
		return $this->raw_pattern;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns regular expression fragments specified to be used for the captures
	 * in the specified pattern.
	 * 
	 * @return array(string => string)  capture_name => regex_fragment
	 */
	public function getRegexFragments()
	{
		return $this->regex_fragments;
	}
}


/* End of file Mapping.php */
/* Location: src/php/Inject/Web/Router/Generator */