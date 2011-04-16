<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\RouterGenerator;

/**
 * Base class creating routes, the routes are based on a template, default
 * route template is an empty template.
 * 
 * 
 */
class Scope
{
	/**
	 * Contains the template with the default options for the routes
	 * created by this scope.
	 * 
	 * @var \Inject\RouterGenerator\Mapping
	 */
	protected $base;
	
	/**
	 * Route definitions.
	 * 
	 * @var array(Mapping)
	 */
	protected $definitions = array();
	
	/**
	 * @param  \Inject\RouterGenerator\Mapping  Initial template, usually just
	 *         an unmodified instance of Mapping.
	 */
	public function __construct(Mapping $parent)
	{
		$this->base   = clone $parent;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Attempts to load a route configuration file and parse its contents into
	 * this Scope object.
	 * 
	 * @param  string
	 * @return void
	 */
	public function loadFile($route_config)
	{
		if(file_exists($route_config))
		{
			// Load routes:
			include $route_config;
		}
		else
		{
			// TODO: Exception
			throw new \Exception(sprintf('Router generator cannot load the file %s.', $route_config));
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a matcher which will attempt to match the specified path pattern,
	 * see the Mapping class for more settings for the matchers.
	 * 
	 * The parameters of this method is passed to the created Mapping's
	 * path() method.
	 * 
	 * If the $path parameter is empty, it will attempt to match the root of the
	 * current scope (ie. not call path() on the Mapping).
	 * 
	 * @see \Inject\RouterGenerator\Mapping::path()
	 * @param  string  If empty it tries to match the root of the current scope.
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\RouterGenerator\Mapping
	 */
	public function match($path = '', array $segment_constraints = array())
	{
		$this->definitions[] = $m = clone $this->base;
		empty($path) OR $m->path($path, $segment_constraints);
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a matcher which will attempt to match the root ("/"), see the
	 * Mapping class for more settings for the matchers.
	 * 
	 * This should preferably be placed first in the Routes.php file as this
	 * is the most popular route of the site.
	 * 
	 * @param  string
	 * @return \Inject\RouterGenerator\Mapping
	 */
	public function root()
	{
		return $this->match('/');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shorthand for match($path, $segment_constraints)->via('GET').
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\RouterGenerator\Mapping
	 */
	public function get($path = '', array $segment_constraints = array())
	{
		return $this->match($path, $segment_constraints)->via('GET');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shorthand for match($path, $segment_constraints)->via('POST').
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\RouterGenerator\Mapping
	 */
	public function post($path = '', array $segment_constraints = array())
	{
		return $this->match($path, $segment_constraints)->via('POST');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shorthand for match($path, $segment_constraints)->via('PUT').
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\RouterGenerator\Mapping
	 */
	public function put($path = '', array $segment_constraints = array())
	{
		return $this->match($path, $segment_constraints)->via('PUT');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shorthand for match($path, $segment_constraints)->via('DELETE').
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\RouterGenerator\Mapping
	 */
	public function delete($path = '', array $segment_constraints = array())
	{
		return $this->match($path, $segment_constraints)->via('DELETE');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shorthand for match($path, $segment_constraints)->via('HEAD').
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\RouterGenerator\Mapping
	 */
	public function head($path = '', array $segment_constraints = array())
	{
		return $this->match($path, $segment_constraints)->via('HEAD');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a new Scope which is based on this scope's template, allows for
	 * creating a lot of similar routes with little code.
	 * 
	 * TODO: Documentation
	 * 
	 * @return \Inject\RouterGenerator\Scope
	 */
	public function scope()
	{
		$this->definitions[] = $s = new Scope($this->base);
		
		return $s;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a RESTful route.
	 * 
	 * It is essentially creating all the following routes:
	 * - GET      $name          =>  #index
	 * - POST     $name          =>  #create
	 * - GET      $name/new      =>  #newform
	 * - GET      $name/:id      =>  #show
	 * - PUT      $name/:id      =>  #update
	 * - DELETE   $name/:id      =>  #destroy
	 * - GET      $name/:id/edit =>  #edit
	 * 
	 * The Resource instance returned also acts as a Scope with the predefined
	 * path $name/:id which makes it possible to nest both more routes and other
	 * resources on this resource.
	 * 
	 * The :id capture is actually named ":$name"."_id" to avoid naming conflicts
	 * when nesting resources (eg. resources('posts')->resources('comments')).
	 * 
	 * Options:
	 * - controller: The controller name to route to, if different from $name
	 * - path:       The path to use when routing, if different from $name
	 * 
	 * TODO: More documentation
	 * 
	 * @param  string
	 * @param  array(string => string)
	 * @return \Inject\RouterGenerator\Resource
	 */
	public function resources($name, array $options = array())
	{
		$this->definitions[] = $r = new Resource($this->base, $name, $options);
		
		return $r;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a redirect destination with the URI/URL specified in $uri_pattern,
	 * should be passed to to().
	 * 
	 * You can use the same pattern syntax as mach() does, but optional parts are
	 * not allowed. The captures will take the parameter read by match() and inject
	 * that into the specified part of the URI/URL given to redirect().
	 * 
	 * Example:
	 * <code>
	 * $this->match('posts/show/:id')->to($this->redirect('posts/:id'));
	 * </code>
	 * 
	 * @param  string  A uri, url and/or pattern
	 * @param  int     The redirect code
	 * @return \Inject\RouterGenerator\Redirect
	 */
	public function redirect($uri_pattern, $redirect_code = 301)
	{
		return new Redirection($uri_pattern, $redirect_code);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the Mapping template of this Scope instance.
	 * 
	 * @return \Inject\RouterGenerator\Mapping
	 */
	public function getTemplate()
	{
		return $this->base;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns an array containing all route definitions.
	 * 
	 * @return array(\Inject\RouterGenerator\Mapping)
	 */
	public function getDefinitions()
	{
		$arr = array();
		
		foreach($this->definitions as $d)
		{
			if($d instanceof Scope)
			{
				$arr = array_merge($arr, $d->getDefinitions());
			}
			else
			{
				$arr[] = $d;
			}
		}
		
		return $arr;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Acts as a proxy for the template, modifies the template of this scope and
	 * then returns the scope instance to allow chaining.
	 * 
	 * Example:
	 * <code>
	 * // Create a new scope for a specific path:
	 * $u = $this->scope()->path(':user_id');
	 * $u->match(...)...;
	 * // ...
	 * </code>
	 * 
	 * @param  string  method name
	 * @param  array   method parameters
	 * @return \Inject\RouterGenerator\Scope  self
	 */
	public function __call($method, array $args)
	{
		if(method_exists($this->base, $method))
		{
			call_user_func_array(array($this->base, $method), $args);
			
			return $this;
		}
		
		throw new \RuntimeException(sprintf('Method %s::%s does not exist.', get_class($this), $method));
	}
}


/* End of file Scope.php */
/* Location: src/php/Inject/Web/Router/Generator */