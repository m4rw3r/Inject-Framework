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
	/**
	 * A list of Inject_Request_HTTP_URI_Route objects.
	 * 
	 * @var Inject_Request_HTTP_URI_RouterInterface
	 */
	protected $router = null;
	
	/**
	 * Creates a new URI based request.
	 * 
	 * @param  string
	 * @param  array
	 */
	function __construct($uri = '', Inject_Request_HTTP_URI_RouterInterface $routes = null)
	{
		Inject::log('Request', 'HTTP URI request initializing, URI: "'.$uri.'".', Inject::DEBUG);
		
		parent::__construct();
		
		// load the routes config if we don't have a supplied routes array
		if(empty($routes))
		{
			$this->loadCache();
		}
		elseif( ! empty($routes))
		{
			$this->router = $routes;
		}
		
		// Step 1: Match to a route
		if($m = $this->router->matches($uri))
		{
			// Step 2:
			// Check if we have a class or a controller name
			isset($m['_class']) && $this->setRawControllerClass($m['_class']) ||
				isset($m['_controller']) && $this->setControllerClass($m['_controller']);
			
			// Do we have an action?
			isset($m['_action']) && $this->setActionMethod($m['_action']);
			
			// Step 3: Assign parameters
			$this->setParameters($m);
        	
			// Step 4: Any remaining dynamic URI parameters?
			isset($m['_uri']) && $this->setExtraSegments(explode('/', $m['_uri']));
		}
		else
		{
			// Fallback
			$this->setUri($uri);
		}
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
		// TODO: Support for the _class parameter, as it can be used too
		if( ! is_array($controller))
		{
			$controller = array_merge($parameters, array('_controller' => $controller, '_action' => $action));
		}
		
		if($m = $this->router->reverseRoute($controller))
		{
			return Inject_URI::getFrontController().(empty($m) ? '' : '/'.$m);
		}
		
		// Default algorithm:
		$uri = strtolower($controller['_controller']);
		
		if( ! empty($controller['_action']))
		{
			$uri .= '/'.strtolower($controller['_action']);
		}
		
		foreach(array_diff_key($parameters, array('_class' => true, '_action' => true, '_controller' => true)) as $k => $v)
		{
			if( ! is_numeric($k))
			{
				$uri .= '/'.$k;
			}
			
			$uri .= '/'.$v;
		}
		
		return Inject_URI::getFrontController().(empty($uri) ? '' : '/'.$uri);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Tries to load the cached router, if not found, creates a new router.
	 * 
	 * @return bool
	 */
	protected function loadCache()
	{
		$f = current(Inject::getApplicationPaths()).'Cache/URI_Router.php';
		
		if( ! file_exists($f) OR ! Inject::getIsProduction())
		{
			// No prod, check if we have an old one:
			
			$files = array();
			foreach(Inject::getApplicationPaths() as $p)
			{
				if(file_exists($p.'Config/URI_Routes.php'))
				{
					$files[] = $p.'Config/URI_Routes.php';
				}
			}
			
			if( ! Inject_Util_Cache::isCurrent('URI_Router.php', $files))
			{
				$this->createRouterBuilder($files)->writeCache('URI_Router.php');
			}
		}
		
		require $f;
		
		$this->router = new Inject_Request_HTTP_URI_CachedRouter();
		
		return true;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates the new RouterBuilder instance.
	 * 
	 * @return Inject_Request_HTTP_URI_RouterBuilder
	 */
	public function createRouterBuilder(array $files)
	{
		return new Inject_Request_HTTP_URI_RouterBuilder('Inject_Request_HTTP_URI_CachedRouter', $files);
	}
}


/* End of file URI.php */
/* Location: ./lib/Inject/Request/HTTP */