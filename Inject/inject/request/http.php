<?php
/*
 * Created by Martin Wernståhl on 2009-07-18.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * The request object for an HTTP request.
 */
class Inject_Request_HTTP implements Inject_Request
{
	/**
	 * The controller class to use, includes "Controller_".
	 * 
	 * @var string
	 */
	protected $controller = false;
	
	/**
	 * The action (method) to call.
	 * 
	 * @var string
	 */
	protected $action = false;
	
	/**
	 * An array with key => value parameters, extracted from the uri.
	 * 
	 * @var array
	 */
	protected $parameters = null;
	
	/**
	 * Contains all the segments for the current request.
	 * 
	 * @var array
	 */
	protected $segments = array();
	
	// ------------------------------------------------------------------------

	public function __construct()
	{
		Inject::log('inject', 'HTTP request initializing', Inject::DEBUG);
		$uri = $this->get_uri();
		
		if( ! empty($uri))
		{
			$this->route($uri);
		}
		
		if(is_null($this->parameters))
		{
			$this->parameters = array();
		}
	}
	
	// ------------------------------------------------------------------------

	public function get_type()
	{
		return 'http';
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the URI used for this request.
	 * 
	 * @return string
	 */
	public function get_uri()
	{
		static $final_uri;
		
		if( ! is_null($final_uri))
		{
			// we have already parsed it, return it
			return $final_uri;
		}
		
		if(PHP_SAPI === 'cli')
		{
			if(isset($_SERVER['argv'][1]))
			{
				$current_uri = $_SERVER['argv'][1];

				// Remove GET string from segments
				if(($query = strpos($current_uri, '?')) !== FALSE)
				{
					list($current_uri, $query) = explode('?', $current_uri, 2);

					// Parse the query string into $_GET
					parse_str($query, $_GET);

					// Convert $_GET to UTF-8
					$_GET = utf8::clean($_GET);
				}
			}
			
			$source = 'Command Line Interface';
		}
		elseif(isset($_GET['inject_uri']))
		{
			// Use the URI defined in the query string
			$current_uri = $_GET['inject_uri'];

			// Remove the URI from $_GET
			unset($_GET['inject_uri']);

			// Remove the URI from $_SERVER['QUERY_STRING']
			$_SERVER['QUERY_STRING'] = preg_replace('~\binject_uri\b[^&]*+&?~', '', $_SERVER['QUERY_STRING']);
			
			$source = 'Query String';
		}
		elseif(isset($_SERVER['PATH_INFO']) AND $_SERVER['PATH_INFO'])
		{
			$current_uri = $_SERVER['PATH_INFO'];
			
			$source = 'Path Info';
		}
		elseif(isset($_SERVER['ORIG_PATH_INFO']) AND $_SERVER['ORIG_PATH_INFO'])
		{
			$current_uri = $_SERVER['ORIG_PATH_INFO'];
			
			$source = 'Orig Path Info';
		}
		elseif(isset($_SERVER['PHP_SELF']) AND $_SERVER['PHP_SELF'])
		{
			$current_uri = $_SERVER['PHP_SELF'];
			
			$source = 'PHP_SELF';
		}
		
		if(Inject::config('inject.front_controller', '') && ($fc_pos = strpos($current_uri, Inject::config('inject.front_controller', ''))) !== false)
		{
			// Remove the front controller from the current uri
			$current_uri = (string) substr($current_uri, $fc_pos + strlen(Inject::config('inject.front_controller', '')));
		}
		
		// Remove slashes from the start and end of the URI
		$current_uri = trim($current_uri, '/');
		
		if($current_uri !== '')
		{
			// Reduce multiple slashes into single slashes
			$current_uri = preg_replace('#//+#', '/', $current_uri);
		}
		
		return $final_uri = $current_uri;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Routes the URI, then calls set_uri() to set the controller, action and parameters.
	 * 
	 * @param  string
	 * @return void
	 */
	public function route($uri)
	{
		$routes = Inject::config('request.http.routes', array());
		
		// literal match, has highest priority
		if(isset($routes[$uri]))
		{
			Inject::log('request', 'HTTP request routed to "'.$routes[$uri].'".', Inject::DEBUG);
			
			$this->set_uri($routes[$uri]);
			
			return;
		}
		
		foreach($routes as $key => $val)
		{
			if(is_numeric($key))
			{
				// we have a callable, check its validity
				if(is_callable($val))
				{
					// function routing_function(string $uri, Inject_Router $rtr)
					
					// Let it reroute the URI or set controller, action and parameters.
					// Depends on if it calls $rtr->set_controller($str) or $rtr->set_uri(),
					// return false to not route the URI
					// If the uri is routed to its final destination, use the $rtr->set_uri()
					// with a valid uri (which sets a controller)
					$uri = ($r = call_user_func($val, $uri, $this)) ? $r : $uri;
					
					// did we get a controller?
					if( ! empty($this->controller))
					{
						Inject::log('request', 'HTTP request routed by callable to controller "'.$this->controller.'".', Inject::DEBUG);
						
						// we're done, the callable has set the controller, action, parameters and segment
						return;
					}
				}
			}
			else
			{
				// we have a match to do
				
				// Trim slashes
				$key = trim($key, '/');
				$val = trim($val, '/');
				
				if(preg_match('#^'.$key.'$#u', $uri))
				{
					if(strpos($val, '$') !== false)
					{
						// regex routing
						$uri = preg_replace('#^'.$key.'$#u', $val, $uri);
					}
					else
					{
						// Standard routing
						$uri = $val;
					}
					
					Inject::log('request', 'HTTP request routed by regex to "'.$uri.'".', Inject::DEBUG);
					
					// A valid route has been found
					break;
				}
			}
		}
		
		// $uri is the routed uri
		$this->set_uri($uri);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Splits the received uri into the controller, action and parameters.
	 * 
	 * @param  string
	 * @return bool
	 */
	public function set_uri($uri)
	{
		if( ! empty($this->controller))
		{
			return false;
		}
		
		$uri = trim($uri, '/ ');
		
		// empty uri, is successful already here :P
		if(empty($uri))
		{
			return true;
		}
		
		$segments = explode('/', $uri);
		
		// get the controller from the first segment
		if(isset($segments[0]))
		{
			$this->set_controller($segments[0]);
			array_shift($segments);
		}
		
		// get the action from the second one, if there is one
		if(isset($segments[0]))
		{
			$this->set_action($segments[0]);
			array_shift($segments);
		}
		
		$this->set_extra_segments($segments);
		
		return true;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the segments which are following the action in the URI.
	 * 
	 * @param  array
	 * @return void
	 */
	public function set_extra_segments(array $segments)
	{
		// save the relative segments for the user to parse
		$this->segments = $segments;
		
		// parse the parameters
		$this->parameters = $this->parse_segments_to_params($segments);
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
	public function parse_segments_to_params(array $segments)
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
	 * Sets the controller to use for this request.
	 * 
	 * @param  string
	 * @return bool
	 */
	public function set_controller($value)
	{
		if(empty($this->controller))
		{
			$this->controller = 'Controller_' . $value;
			
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the controller to use for this request.
	 * 
	 * @param  string
	 * @return bool
	 */
	public function set_action($value)
	{
		if(empty($this->action))
		{
			// TODO: Namespace the actions?
			$this->action = $value;
			
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the controller to use for this request.
	 * 
	 * @param  string
	 * @return bool
	 */
	public function set_parameters($value)
	{
		if(is_null($this->parameters))
		{
			$this->parameters = (Array) $value;
			
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// ------------------------------------------------------------------------

	public function get_controller()
	{
		return $this->controller;
	}
	
	// ------------------------------------------------------------------------

	public function get_action()
	{
		return $this->action;
	}
	
	// ------------------------------------------------------------------------

	public function get_parameter($name, $default = null)
	{
		return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the segments following the action in the URI.
	 * 
	 * @param  int			If a certain element is to be fetched
	 * @return array|string
	 */
	public function get_segments($num = false)
	{
		if($num !== false)
		{
			return isset($this->segments[$num]) ? $this->segments[$num] : false;
		}
		else
		{
			return $this->segments;
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns true if this is an XMLHttpRequest (ie. Javascript).
	 * 
	 * This requires a special header to be sent from the JS
	 * (usually the Javascript frameworks' Ajax/XHR methods add it automatically):
	 * 
	 * X-Requested-With: XMLHttpRequest
	 * 
	 * @return bool
	 */
	public function is_xhr()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER ['HTTP_X_REQUESTED_WITH'])  == 'xmlhttprequest';
	}
}


/* End of file http.php */
/* Location: ./Inject/inject/request */