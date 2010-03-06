<?php
/*
 * Created by Martin Wernståhl on 2009-12-17.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Request_HMVC extends Inject_Request
{
	protected $method = 'GET';
	
	protected $class_name;
	
	protected $action_name;
	
	protected $parameters = array();
	
	function __construct($class_name, $action_name = false, $parameters = array(), $method = 'GET')
	{
		parent::__construct();
		
		$this->class_name = $class_name;
		$this->action_name = $action_name;
		$this->parameters = $parameters;
		$this->method = $method;
	}
	
	// ------------------------------------------------------------------------
	
	public function getProtocol()
	{
		return 'hmvc';
	}
	
	// ------------------------------------------------------------------------
	
	public function getMethod()
	{
		return $this->method;
	}
	
	// ------------------------------------------------------------------------
	
	public function getControllerClass()
	{
		return $this->class_name;
	}
	
	// ------------------------------------------------------------------------
	
	public function getActionMethod()
	{
		return $this->action_name;
	}
	
	// ------------------------------------------------------------------------
	
	public function getParameter($name, $default = null)
	{
		return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
	}
	
	// ------------------------------------------------------------------------
	
	public function getParameters()
	{
		return $this->parameters;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Dispatches this to the main request.
	 * 
	 * @todo: Maybe dispatch it to a "parent request" instead?
	 */
	public function createCall($controller, $action = null, $parameters = array())
	{
		return Inject::getMainRequest()->createCall($controller, $action, $parameters);
	}
}


/* End of file HMVC.php */
/* Location: ./lib/Inject/Request */