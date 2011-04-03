<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;

use \Inject\Core\Engine;

/**
 * Base class creating routes, the routes are based on a template, default
 * route template is an empty template.
 * 
 * 
 */
class Scope
{
	/**
	 * The application engine.
	 * 
	 * @var \Inject\Core\Engine
	 */
	protected $engine;
	
	/**
	 * Contains the template with the default options for the routes
	 * created by this scope.
	 * 
	 * @var \Inject\Web\Router\Generator\Mapping
	 */
	protected $base;
	
	/**
	 * Route definitions.
	 * 
	 * @var array(Mapping)
	 */
	protected $definitions = array();
	
	/**
	 * @param  \Inject\Core\Engine  The engine instance which is responsible for
	 *                              the controllers in the routes
	 * @param  \Inject\Web\Router\Generator\Mapping  Initial template
	 */
	public function __construct(Engine $engine, $parent = null)
	{
		if( ! (is_object($parent) && $parent instanceof Mapping))
		{
			$parent = new Mapping();
		}
		
		$this->engine = $engine;
		$this->base   = clone $parent;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a matcher which will attempt to match the specified path pattern,
	 * see the Mapping class for more settings for the matchers.
	 * 
	 * The parameters of this method is passed to the created Mapping's
	 * path() method.
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\Web\Router\Generator\Mapping
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
	 * @return \Inject\Web\Router\Generator\Mapping
	 */
	public function root()
	{
		$this->definitions[] = $m = clone $this->base;
		$m->path('/');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shorthand for match($path)->via('GET').
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\Web\Router\Generator\Mapping
	 */
	public function get($path, array $segment_constraints = array())
	{
		$this->definitions[] = $m = clone $this->base;
		empty($path) OR $m->path($path, $segment_constraints);
		$m->via('GET');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shorthand for match($path)->via('POST').
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\Web\Router\Generator\Mapping
	 */
	public function post($path, array $segment_constraints = array())
	{
		$this->definitions[] = $m = clone $this->base;
		empty($path) OR $m->path($path, $segment_constraints);
		$m->via('POST');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shorthand for match($path)->via('PUT').
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\Web\Router\Generator\Mapping
	 */
	public function put($path, array $segment_constraints = array())
	{
		$this->definitions[] = $m = clone $this->base;
		empty($path) OR $m->path($path, $segment_constraints);
		$m->via('PUT');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shorthand for match($path)->via('DELETE').
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\Web\Router\Generator\Mapping
	 */
	public function delete($path, array $segment_constraints = array())
	{
		$this->definitions[] = $m = clone $this->base;
		empty($path) OR $m->path($path, $segment_constraints);
		$m->via('DELETE');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * TODO: Documentation
	 * 
	 * @return \Inject\Web\Router\Generator\Mapping
	 */
	public function mount($path, $app_name, array $segment_constraints = array())
	{
		$this->definitions[] = $m = clone $this->base;
		empty($path) OR $m->path($path, $segment_constraints);
		$m->to($app_name);
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a new Scope which is based on this scope's template, allows for
	 * creating a lot of similar routes with little code.
	 * 
	 * TODO: Documentation
	 * 
	 * @return \Inject\Web\Router\Generator\Scope
	 */
	public function scope()
	{
		$this->definitions[] = $s = new Scope($this->engine, $this->base);
		
		return $s;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a RESTful route.
	 * 
	 * It is essentially creating all the following routes:
	 * - GET      $name        =>  #index
	 * - POST     $name        =>  #create
	 * - GET      $name/new    =>  #newform
	 * - GET      $name/:id    =>  #show
	 * - PUT      $name/:id    =>  #update
	 * - DELETE   $name/:id    =>  #destroy
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
	 * @return \Inject\Web\Router\Generator\Resource
	 */
	public function resources($name, array $options = array())
	{
		$this->definitions[] = $r = new Resource($this->engine, $this->base, $name, $options);
		
		return $r;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a redirect destination with the URI/URL specified in $uri_pattern,
	 * should be passed to to().
	 * 
	 * You can use the same syntax as mach() does, but optional parts are not
	 * allowed. The captures will take the parameter read by match() and inject
	 * that into the specified part of the URI/URL given to redirect().
	 * 
	 * Example:
	 * <code>
	 * $this->match('posts/show/:id')->to($this->redirect('posts/:id'));
	 * </code>
	 * 
	 * @param  string  A uri, url and/or pattern
	 * @param  int     The redirect code
	 * @return \Inject\Web\Router\Generator\Redirect
	 */
	public function redirect($uri_pattern, $redirect_code = 301)
	{
		return new Redirection($uri_pattern, $redirect_code);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the Mapping template of this Scope instance.
	 * 
	 * @return \Inject\Web\Router\Generator\Mapping
	 */
	public function getTemplate()
	{
		return $this->base;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns all route generators (Destination\*) for this Scope instance and
	 * also all sub-scopes.
	 * 
	 * @return array(\Inject\Web\Router\Generator\Destination\AbstractDestination)
	 */
	public function getDestinations()
	{
		$arr = array();
		
		foreach($this->definitions as $d)
		{
			if($d instanceof Scope)
			{
				$arr = array_merge($arr, $d->getDestinations());
			}
			else
			{
				$to = $d->getTo();
				
				if(isset($to['redirect']))
				{
					$arr[] = new Destination\Redirect($d, $this->engine);
				}
				elseif(isset($to['callback']))
				{
					$arr[] = new Destination\Callback($d, $this->engine);
				}
				elseif(isset($to['engine']))
				{
					$arr[] = new Destination\Application($d, $this->engine);
				}
				elseif(isset($to['controller']))
				{
					$arr[] = new Destination\Controller($d, $this->engine);
				}
				else
				{
					$arr[] = new Destination\Polymorphic($d, $this->engine);
				}
			}
		}
		
		return $arr;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Acts as a proxy for the template, modifies the template and then returns
	 * the scope instance.
	 * 
	 * @param  string  method name
	 * @param  array   method parameters
	 * @return \Inject\Web\Router\Generator\Scope  self
	 */
	public function __call($method, array $args)
	{
		if(method_exists($this->base, $method))
		{
			call_user_func_array(array($this->base, $method), $args);
			
			return $this;
		}
		
		throw new \Exception(sprintf('Method %s::%s does not exist.', get_class($this), $method));
	}
}


/* End of file Scope.php */
/* Location: src/php/Inject/Web/Router/Generator */