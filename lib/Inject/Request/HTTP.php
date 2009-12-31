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
	 * 
	 * 
	 * @return 
	 */
	public function __construct()
	{
		// Request is UTF-8, need a header
		// TODO: Move to a response object?
		header('Content-Type: text/html;charset=UTF-8');
		
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
		if( ! preg_match(self::ALLOWED_CHARACTERS_REGEX, $class))
		{
			throw new Exception('Disallowed characters in controller name.');
		}
		
		$this->controller_class = 'Controller_'.ucfirst(str_replace('/(_[a-z])/e', "strtoupper('$1')", strtolower($class)));
	}
	
	// ------------------------------------------------------------------------
	
	public function setActionMethod($method)
	{
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
		// TODO: Code
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the user IP address.
	 * 
	 * @return string
	 */
	public function getUserIp()
	{
		// TODO: Code
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
}


/* End of file HTTP.php */
/* Location: ./lib/Inject/Request */