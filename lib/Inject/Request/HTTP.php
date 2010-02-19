<?php
/*
 * Created by Martin Wernståhl on 2009-12-23.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Base class for the HTTP request, contains all the methods common to HTTP requests.
 */
abstract class Inject_Request_HTTP extends Inject_Request
{
	/**
	 * Contains the regex which determines what is allowed in a class/method name.
	 * 
	 * @var string
	 */
	const ALLOWED_CHARACTERS_REGEX = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/iu';
	
	/**
	 * The IP of the client.
	 * 
	 * @var string
	 */
	protected static $ip = null;
	
	/**
	 * The user agent string supplied by the browser.
	 * 
	 * @var string
	 */
	protected static $user_agent = null;
	
	/**
	 * List of formats supported by the browser.
	 * 
	 * @var array
	 */
	protected static $accepted_formats = null;
	
	/**
	 * Stores the class name for the controller
	 * 
	 * @var string
	 */
	protected $controller_class = null;
	
	/**
	 * Contains the method name which is the action to call.
	 * 
	 * @var string
	 */
	protected $action_method = null;
	
	/**
	 * Contains the parameter array.
	 * 
	 * @var array
	 */
	protected $parameters = array();
	
	/**
	 * The protocol, http or https.
	 * 
	 * @var string
	 */
	protected $protocol = 'http';
	
	/**
	 * The request method.
	 * 
	 * @var string
	 */
	protected $method = 'GET';
	
	/**
	 * Variable telling if the request has been made by Ajax or not.
	 * 
	 * @var bool
	 */
	protected $is_ajax = false;
	
	// ------------------------------------------------------------------------

	/**
	 * Initializes an HTTP request object, loads $_SERVER data to get request information.
	 */
	public function __construct()
	{
		parent::__construct();
		
		// Add text/html content type and also charset.
		$this->headers['Content-Type'] = 'text/html;charset=UTF-8';
		
		$this->protocol = (( ! empty($_SERVER['HTTPS'])) && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http';
		$this->method = isset($_SERVER['REQUEST_METHOD']) ? $method = $_SERVER['REQUEST_METHOD'] : 'GET';
		$this->is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER ['HTTP_X_REQUESTED_WITH'])  == 'xmlhttprequest';
		
		Inject::log('Request', 'Request is '.strtoupper($this->protocol).' '.$this->method, Inject::DEBUG);
		
		if($this->method !== 'GET' && $this->method !== 'POST')
		{
			Inject::log('Request', 'Reloading form data, PHP has not loaded $_POST.', Inject::DEBUG);
			
			// PUT etc. does not parse form data, do it now
			parse_str(file_get_contents('php://input'), $_POST);
			
			Utf8::clean($_POST);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the controller to use during this request, automatically adjusts casing.
	 * 
	 * Converts a_class_name to A_Class_Name, and aclassname to Aclassname.
	 * 
	 * Lowercases all first.
	 * 
	 * @param  string
	 * @return void
	 */
	public function setControllerClass($class)
	{
		// Important to get the correct controller file
		$class = 'Controller_'.Utf8::ucfirst(preg_replace('/(_\w)/eu', "Utf8::strtoupper('$1')", Utf8::strtolower($class)));
		
		if( ! preg_match(self::ALLOWED_CHARACTERS_REGEX, $class))
		{
			throw new Exception('Disallowed characters in controller name.');
		}
		
		$this->controller_class = $class;
	}
	
	// ------------------------------------------------------------------------
	
	public function setActionMethod($method)
	{
		// Case does not matter for the methods, as PHP is case insensitive and
		// the class is already loaded
		$method = 'action'.$method;
		
		if( ! preg_match(self::ALLOWED_CHARACTERS_REGEX, $method))
		{
			throw new Exception('Disallowed characters in action name.');
		}
		
		$this->action_method = $method;
	}
	
	// ------------------------------------------------------------------------

	public function setParameters(array $hash, $use_urldecode = false)
	{
		if( ! $use_urldecode)
		{
			$this->parameters = $hash;
		}
		else
		{
			// decode the values (converts all %## notations)
			foreach($hash as $k => $v)
			{
				$this->parameters[urldecode($k)] = urldecode($v);
			}
		}
	}
	
	// ------------------------------------------------------------------------
	
	public function getProtocol()
	{
		return $this->protocol;
	}
	
	// ------------------------------------------------------------------------
	
	public function getMethod()
	{
		return $this->method;
	}
	
	// ------------------------------------------------------------------------
	
	public function getControllerClass()
	{
		return $this->controller_class;
	}
	
	// ------------------------------------------------------------------------
	
	public function getActionMethod()
	{
		return $this->action_method;
	}
	
	// ------------------------------------------------------------------------
	
	public function getParameters()
	{
		return $this->parameters;
	}
	
	// ------------------------------------------------------------------------
	
	public function getParameter($name, $default = null)
	{
		return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the user agent name.
	 * 
	 * @return string
	 */
	public function getUserAgent()
	{
		if(isset(self::$user_agent))
		{
			return self::$user_agent;
		}
		else
		{
			return self::$user_agent = empty($_SERVER['HTTP_USER_AGENT']) ? false : $_SERVER['HTTP_USER_AGENT'];
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the user IP address.
	 * 
	 * @return string
	 */
	public function getUserIp()
	{
		if(isset(self::$ip))
		{
			return self::$ip;
		}
		
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			self::$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		elseif(isset($_SERVER['HTTP_CLIENT_IP']))
		{
			self::$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif(isset($_SERVER['REMOTE_ADDR']))
		{
			self::$ip = $_SERVER['REMOTE_ADDR'];
		}
		
		return self::$ip;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of the formats the browser accepts, ordered by preference.
	 * 
	 * @return array
	 */
	public function getAcceptedFormats()
	{
		if(isset(self::$accepted_formats))
		{
			return self::$accepted_formats;
		}
		
		$types = array();
		
		foreach(explode(',', $_SERVER['HTTP_ACCEPT']) as $type)
		{
			$parts = array_map('trim', explode(';', $type));
			
			$type = array_shift($parts);
			$q = empty($parts) ? null : array_shift($parts);
			
			$q = substr($q, 0, 2) == 'q=' ? floatval(substr($q, 2)) : 1;
			
			if($q <= 0)
			{
				continue;
			}
			
			$types[$type] = $q;
		}
		
		// sort from highest to lowest q value
		arsort($types);
		
		return self::$accepted_formats = $types;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the topmost format from $allowed_formats which the browser accepts.
	 * 
	 * @param  array  List of lowercase file extensions
	 * @return string
	 */
	public function getPreferredFormat(array $allowed_formats)
	{
		return current(array_intersect($allowed_formats, array_map('Inject_MIME::mime2ext', array_keys($this->getAcceptedFormats()))));
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns true if this is an Ajax request (ie. Javascript request).
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
	public function isAjax()
	{
		return $this->is_ajax;
	}
	
	// ------------------------------------------------------------------------
	
	public function showError($level, $type, $message, $file, $line, $trace)
	{
		$_found = false;
		
		foreach(array_merge(array(Inject::getFrameworkPath()), Inject::getApplicationPaths()) as $p)
		{
			if(file_exists($p.'Errors/http_general.php'))
			{
				$_found = true;
				
				include $p.'Errors/http_general.php';
			}
		}
		
		if( ! $_found)
		{
			parent::showError($level, $type, $message, $file, $line, $trace);
		}
	}
	
	// ------------------------------------------------------------------------
	
	public function showError500($level, $type, $message, $file, $line, $trace)
	{
		$_found = false;
		
		foreach(array_merge(array(Inject::getFrameworkPath()), Inject::getApplicationPaths()) as $p)
		{
			if(file_exists($p.'Errors/http_500.php'))
			{
				$_found = true;
				
				include $p.'Errors/http_500.php';
			}
		}
		
		if( ! $_found)
		{
			parent::showError($level, $type, $message, $file, $line, $trace);
		}
	}
}


/* End of file HTTP.php */
/* Location: ./lib/Inject/Request */