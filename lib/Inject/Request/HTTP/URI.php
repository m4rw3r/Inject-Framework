<?php
/*
 * Created by Martin Wernståhl on 2009-12-23.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * A URI based request, where the URI determines what should be done.
 */
class Inject_Request_HTTP_URI extends Inject_Request_HTTP
{
	protected $routes = array();
	
	protected $patterns = array();
	
	function __construct($uri = '', $routes = array())
	{
		Inject::log('Request', 'HTTP URI request initializing, URI: "'.$uri.'".', Inject::DEBUG);
		
		parent::__construct();
		
		// load the routes config if we don't have a supplied routes array
		if(empty($routes))
		{
			foreach(Inject::getApplicationPaths() as $p)
			{
				if(file_exists($p.'Config/URI_Routes.php'))
				{
					include $p.'Config/URI_Routes.php';
				}
			}
		}
		else
		{
			$this->routes = $routes;
		}
		
		$this->setUri($this->route($uri));
	}
	
	/**
	 * Creates a rule for the specified pattern and path.
	 * 
	 * @param  string
	 * @param  string
	 * @param  array
	 */
	public function matches($pattern, array $to)
	{
		$this->routes[] = $o = new Inject_Request_HTTP_URI_Route($pattern, $to);
		
		$reverse_match = strtolower((isset($to['controller']) ? $to['controller'] : '').'#'.(isset($to['action']) ? $to['action'] : ''));
		
		$this->patterns[$reverse_match][] = $o;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Performs the routing based on the routes property.
	 * 
	 * @param  string
	 * @return string
	 */
	public function route($uri)
	{
		// literal match, has highest priority
		if(isset($this->routes[$uri]))
		{
			Inject::log('Request', 'HTTP URI request routed to "'.$this->routes[$uri].'".', Inject::DEBUG);
			
			return $this->routes[$uri];
		}
		
		foreach($this->routes as $route)
		{
			if($m = $route->matchUri($uri))
			{
				// Reset uri as we have a match
				$uri = '';
				
				isset($m['controller']) && $this->setControllerClass($m['controller']);
				isset($m['action']) && $this->setActionMethod($m['action']);
				isset($m['uri']) && $uri = $m['uri'];
				
				// step 2: assign
				$this->setParameters($m);
				
				Inject::log('Request', 'HTTP URI request routed by regex to "'.$this->getControllerClass().'::'.$this->getActionMethod().'".', Inject::DEBUG);

				// A valid route has been found
				break;
			}
		}
		
		return str_replace('//', '/', $uri);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Splits the received uri into the controller, action and parameters.
	 * 
	 * @param  string
	 * @return bool
	 */
	public function setUri($uri)
	{
		$uri = trim($uri, '/ ');
		
		// empty uri, is successful already here :P
		if(empty($uri))
		{
			return true;
		}
		
		$segments = explode('/', $uri);
		
		// get the controller from the first segment
		if( ! empty($segments))
		{
			$this->setControllerClass(array_shift($segments));
		}
		
		// get the action from the second one, if there is one
		if( ! empty($segments))
		{
			$this->setActionMethod(array_shift($segments));
		}
		
		$this->setExtraSegments($segments);
		
		return true;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the segments which are following the action in the URI.
	 * 
	 * @param  array
	 * @return void
	 */
	public function setExtraSegments(array $segments)
	{
		// save the relative segments for the user to parse
		$this->segments = $segments;
		
		// parse the parameters
		$this->parameters = array_merge($this->parameters, $this->parseSegmentsToParams($segments));
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Parses a segment array into an associative array.
	 * 
	 * Format:
	 * array(key, value, key2, value2);
	 * 
	 * Result:
	 * array(key => value, key2 => value2);
	 * 
	 * @param  array
	 * @return void
	 */
	public function parseSegmentsToParams(array $segments)
	{
		$parameters = array();
		
		// format: /key/value/key2/value2/...
		while($e = array_shift($segments))
		{
			if( ! empty($segments))
			{
				$parameters[$e] = array_shift($segments);
			}
			else
			{
				// no value, just add it to the array
				$parameters[] = $e;
			}
		}
		
		return $parameters;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a segment following the action in the URI.
	 * 
	 * @param  int			If a certain element is to be fetched
	 * @return string|false
	 */
	public function getSegment($num)
	{
		return isset($this->segments[$num]) ? $this->segments[$num] : false;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns all segments which follow the action in the URI.
	 * 
	 * @return array
	 */
	public function getSegments()
	{
		return $this->segments;
	}
	
	// ------------------------------------------------------------------------
	
	public function createCall($controller, $action = null, $parameters = array())
	{
		if(is_array($controller))
		{
			throw new Exception('CODE NOT WRITTEN!');
		}
		
		$controller = strtolower($controller);
		$action = strtolower($action);
		
		// Remove controller
		if(strpos($controller, 'controller_') === 0)
		{
			$controller = substr($controller, 11);
		}
		
		// Do we have a matching Inject_Request_HTTP_URI_Route?
		if(isset($this->patterns[$controller.'#'.$action]))
		{
			// Check if they really match (number of parameters):
			foreach($this->patterns[$controller.'#'.$action] as $pattern)
			{
				if($u = $pattern->reverseRoute($controller, $action, $parameters))
				{
					// Match
					return Inject_URI::getFrontController().'/'.$u;
				}
			}
		}
		
		$uri = $controller;
		
		if( ! empty($action))
		{
			$uri .= '/'.$action;
		}
		
		foreach($parameters as $k => $v)
		{
			if( ! is_numeric($k))
			{
				$uri .= '/'.$k;
			}
			
			$uri .= '/'.$v;
		}
		
		return Inject_URI::getFrontController().(empty($uri) ? '' : '/'.$uri);
	}
}


/* End of file URI.php */
/* Location: ./lib/Inject/Request/HTTP */