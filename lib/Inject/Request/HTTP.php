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
	const ALLOWED_CHARACTERS_REGEX = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/i';
	
	/**
	 * Stores the class name for the controller
	 * 
	 * @var string
	 */
	protected $controller = null;
	
	/**
	 * Contains the method name which is the action to call.
	 * 
	 * @var string
	 */
	protected $method = null;
	
	/**
	 * Contains the parameter array.
	 * 
	 * @var array
	 */
	protected $parameters = array();
	
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
	public function setController($class)
	{
		if( ! preg_match(self::ALLOWED_CHARACTERS_REGEX, $class))
		{
			throw new Exception('Disallowed characters in controller name.');
		}
		
		$this->controller = 'Controller_'.ucfirst(str_replace('/(_[a-z])/e', "strtoupper('$1')", strtolower($class)));
	}
	
	// ------------------------------------------------------------------------
	
	public function setMethod($method)
	{
		if( ! preg_match(self::ALLOWED_CHARACTERS_REGEX, $method))
		{
			throw new Exception('Disallowed characters in action name.');
		}
		
		$this->method = $method;
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
	
	public function getType()
	{
		return 'http';
	}
	
	// ------------------------------------------------------------------------
	
	public function getClass()
	{
		return $this->controller;
	}
	
	// ------------------------------------------------------------------------
	
	public function getMethod()
	{
		return $this->method;
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
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER ['HTTP_X_REQUESTED_WITH'])  == 'xmlhttprequest';
	}
}


/* End of file HTTP.php */
/* Location: ./lib/Inject/Request */