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
	protected $parameters = array();
	
	/**
	 * Contains all the segments for the current request.
	 * 
	 * @var array
	 */
	protected $segments = array();
	
	/**
	 * The response object tied to this instance.
	 * 
	 * @var Inject_Response_HTTP
	 */
	protected $response;
	
	// ------------------------------------------------------------------------

	public function __construct($uri = null)
	{
		Inject::log('inject', 'HTTP request initializing', Inject::DEBUG);
		
		// do we have an URI? if not get the current one from the URL object
		$this->uri = is_null($uri) ? URL::get_current_uri() : $uri;
		
		if( ! empty($this->uri))
		{
			if(($uri = $this->route($this->uri)) !== false)
			{
				// $uri is the routed uri
				$this->set_uri($uri);
			}
		}
	}
	
	// ------------------------------------------------------------------------

	public function get_type()
	{
		return 'http';
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Routes the URI, then calls set_uri() to set the controller, action and parameters.
	 * 
	 * Named regexes:
	 * The named captures of the regular expressions will be set as parameters with the
	 * capture name as the key.
	 * Unnamed captures will be ignored, and the resulting uri string will be parsed
	 * into controller/action/extra parameters
	 * 
	 * @param  string
	 * @return void
	 */
	public function route($uri)
	{
		$routes = Inject::config('http.routes', array());
		
		// literal match, has highest priority
		if(isset($routes[$uri]))
		{
			Inject::log('request', 'HTTP request routed to "'.$routes[$uri].'".', Inject::DEBUG);
			
			return $routes[$uri];
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
						return false;
					}
				}
			}
			else
			{
				// we have a match to do
				
				// Trim slashes
				$key = trim($key, '/');
				$val = trim($val, '/');
				
				if(preg_match('#^'.$key.'$#u', $uri, $m))
				{
					// get parameters from the regex, step 1: clean it from junk
					foreach($m as $k => $v)
					{
						// skip numeric
						if(is_numeric($k))
						{
							unset($m[$k]);
						}
					}
					
					$this->set_parameters($m);
					
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
		
		return $uri;
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
		$this->parameters = array_merge($this->parameters, $this->parse_segments_to_params($segments));
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
	 * Sets the parameters to use for this request.
	 * 
	 * @param  array
	 * @return bool
	 */
	public function set_parameters($value)
	{
		$this->parameters = array_merge($this->parameters, $value);
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
	
	public function get_parameters()
	{
		return $this->parameters;
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
	
	public function get_response()
	{
		return $this->response ? $this->response : $this->response = new Inject_Response_HTTP;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns true if this is an XMLHttpRequest (ie. Javascript).
	 * 
	 * This requires a special header to be sent from the JS
	 * (usually the Javascript frameworks' Ajax/XHR methods add it automatically):
	 * 
	 * <code>
	 * X-Requested-With: XMLHttpRequest
	 * </code>
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