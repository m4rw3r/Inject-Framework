<?php
/*
 * Created by Martin Wernståhl on 2009-07-26.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Creates URLs used to link to the Inject Framework.
 * 
 * Example usage:
 * <code>
 * echo new URL(array('controller' => 'foo', 'action' => 'bar'));
 * 
 * // echos http://example.com/index.php/foo/bar  (depends on the configuration and routes)
 * </code>
 * 
 * If a string is supplied, the controller is expected to be the first segment of the
 * uri path and the action the second. The query string is assumed to be the parameters.
 * 
 * Example:
 * <code>
 * echo new URL('/controller/action?param1=var1&param2=var2');
 * </code>
 * 
 * Parameters can also be set via the second parameter as an array.
 */
class Inject_URL
{
	/**
	 * Contains the current URI.
	 * 
	 * @var string
	 */
	static protected $current_uri;
	
	/**
	 * Contains the path, relative to doc root, to the front controller.
	 * 
	 * @var string
	 */
	static protected $front_controller;
	
	/**
	 * Creates an URL.
	 * 
	 * @param string|array
	 */
	function __construct($params = array(), $parameters = array())
	{
		$default = array
			(
				'scheme'			=> self::get_scheme(),
				'host'				=> self::get_host(),
				'port'				=> self::get_port(),
				'front_controller'	=> self::get_front_controller(),
				'controller'		=> self::get_current_controller(),
				'action'			=> self::get_current_action()
			);
		
		if(is_array($params))
		{
			$this->params = array_merge($default, $params);
			
		}
		else
		{
			// parse the URI
			$parsed = self::parse_url($params);
			
			if(empty($parsed[0]))
			{
				// nothing matching
				$this->params = $default;
				
				return;
			}
			
			$params = array();
			
			foreach(array('scheme', 'host', 'port', 'anchor') as $seg)
			{
				if( ! empty($parsed[$seg]))
				{
					$params[$seg] = $parsed[$seg];
				}
			}
			
			// parse the parameters
			if( ! empty($parsed['arg']))
			{
				$args = explode('&', $parsed['arg']);
				$params['parameters'] = array();
				
				foreach($args as $segment)
				{
					$p = strpos($segment, '=');
					
					$k = substr($segment, 0, $p);
					$v = substr($segment, $p + 1);
					
					$params['parameters'][$k] = $v;
				}
			}
			
			// get the controller and the action
			$path_segs = explode('/', trim($parsed['path'], '/'));
			
			if($c = array_shift($path_segs))
			{
				$params['controller'] = $c;
			}
			
			if($a = array_shift($path_segs))
			{
				$params['action'] = $a;
			}
			
			$this->params = array_merge($default, $params);
		}
		
		// merge in the current parameters
		if($parameters)
		{
			$this->params['parameters'] = array_merge(( ! empty($this->params['parameters'])) ? $this->params['parameters'] : array(), $parameters);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Parses the URL into segments.
	 * 
	 * @param  string
	 * @return array
	 */
	public static function parse_url($url)
	{
		$r	= "!^(?:(?P<scheme>\w+)://)?(?:(?P<login>\w+):(?P<pass>\w+)@)?(?P<host>(?:[-\w\.]+\.)?[-\w]+\.\w+)?(?::(?P<port>\d+))?(?P<path>[\w/]*/(?:\w+(?:\.\w+)?)?)?(?:\?(?P<arg>[\w=&]+))?(?:#(?P<anchor>\w+))?!";
		
		preg_match ( $r, $url, $out );
		
		return $out;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the schema to use, specified by the http.scheme setting, falls back on server variable.
	 * 
	 * @return string
	 */
	public static function get_scheme()
	{
		if( ! $s = Inject::config('http.scheme', false))
		{
			$s = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
			
			Inject::set_config('http.scheme', $s);
		}
		
		return $s;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the host to use, specified by the http.host setting, falls back on server variable.
	 * 
	 * @return string
	 */
	public static function get_host()
	{
		if( ! $h = Inject::config('http.host', false))
		{
			$h = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] :
				(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
			
			Inject::set_config('http.host', $h);
		}
		
		return $h;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 	Returns the host to use, specified by the http.port setting, falls back on server variable.
	 * 
	 * @return string
	 */
	public static function get_port()
	{
		if( ! $p = Inject::config('http.port', false))
		{
			$p = isset($_SERVER['PORT']) && $_SERVER['PORT'] != '80' ? $_SERVER['PORT'] : '';
			
			Inject::set_config('http.port', $p);
		}
		
		return $p;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the current URI used for this request.
	 * 
	 * @return string
	 */
	public static function get_current_uri()
	{
		if(is_null(self::$current_uri))
		{
			// we have not parsed it, parse it
			self::parse_current_uri();
		}
		
		return self::$current_uri;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the path to the front controller, relative to the 
	 * 
	 * @return 
	 */
	public static function get_front_controller()
	{
		if(is_null(self::$current_uri))
		{
			self::parse_current_uri();
		}
		
		return self::$front_controller;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the current controller (minus "Controller_"), defaults to config('dispatcher.default_controller').
	 * 
	 * @return string
	 */
	public static function get_current_controller()
	{
		if(isset(Inject::$main_request))
		{
			return str_replace('Controller_', '', Inject::$main_request->get_controller());
		}
		else
		{
			return str_replace('Controller_', '', Inject::config('dispatcher.default_controller', ''));
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the current controller action, defaults to config('dispatcher.default_action').
	 * 
	 * @return string
	 */
	public static function get_current_action()
	{
		if(isset(Inject::$main_request))
		{
			return Inject::$main_request->get_action();
		}
		else
		{
			return Inject::config('dispatcher.default_action', '');
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the current parameters, defaults to empty.
	 * 
	 * @return array
	 */
	public static function get_current_parameters()
	{
		if(isset(Inject::$main_request))
		{
			return Inject::$main_request->get_parameters();
		}
		else
		{
			return array();
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates the URL.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		$url  = $this->params['scheme'] . '://';
		$url .= $this->params['host'];
		$url .= ( ! empty($this->params['port'])) ? ':' . $this->params['port'] : '';
		$url .= ( ! empty($this->params['front_controller'])) ? $this->params['front_controller'] : '';
		$url .= ( ! empty($this->params['controller'])) ? '/' . $this->params['controller'] : '';
		$url .= ( ! empty($this->params['action'])) ? '/' . $this->params['action'] : '';
		
		if( ! empty($this->params['parameters']))
		{
			foreach($this->params['parameters'] as $k => $v)
			{
				if(is_string($k))
				{
					$url .= "/$k/$v";
				}
				else
				{
					$url .= "/$v";
				}
			}
		}
		
		$url .= ( ! empty($this->params['anchor'])) ? '#' . $this->params['anchor'] : '';
		
		return $url;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Parses the current URI to find the request URI relative to the framework
	 * front controller and the path to the front controller itself.
	 * 
	 * @return void
	 */
	protected static function parse_current_uri()
	{
		if( ! is_null(self::$current_uri))
		{
			return;
		}
		
		// TODO: Move this into a separate CLI request object?
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
			
			if($p = strpos($_SERVER['REQUEST_URI'], '?') !== false)
			{
				/*
				 * remove the query string from the REQUEST URI to create the front controller path
				 * add ?inject_uri= to create the final front controller.
				 */
				self::$front_controller = substr($_SERVER['REQUEST_URI'], 0, $p).'?inject_uri=';
			}
			
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
		
		
		// remove the current script name if there is one
		if(isset($_SERVER['PHP_SELF']) && strpos($current_uri, $_SERVER['PHP_SELF']) === 0)
		{
			// Remove the front controller from the current uri
			$current_uri = (string) substr($current_uri, strlen($_SERVER['PHP_SELF']));
			
			// the PHP_SELF variable is the front controller
			self::$front_controller = $_SERVER['PHP_SELF'];
		}
		// do we have to deduce the front_controller?
		elseif(is_null(self::$front_controller))
		{
			if(isset($_SERVER['REQUEST_URI']))
			{
				// Remove the found uri from the REQUEST URI to create the front controller path.
				self::$front_controller = ($p = strpos($_SERVER['REQUEST_URI'], $current_uri)) !== false ? substr($_SERVER['REQUEST_URI'], 0, $p) : $_SERVER['REQUEST_URI'];
			}
		}
		
		// normalize front controller
		self::$front_controller = '/' . trim(self::$front_controller, '/');
		
		// Remove slashes from the start and end of the URI
		$current_uri = trim($current_uri, '/');
		
		if($current_uri !== '')
		{
			// Reduce multiple slashes into single slashes
			$current_uri = preg_replace('#//+#', '/', $current_uri);
		}
		
		self::$current_uri = $current_uri;
	}
}


/* End of file url.php */
/* Location: ./Inject/inject */