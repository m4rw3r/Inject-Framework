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
	
	protected $file_format = 'html';
	
	function __construct($class_name, $action_name = false, $parameters = array(), $method = 'GET', $format = 'html')
	{
		$this->class_name = $class_name;
		$this->action_name = $action_name;
		$this->parameters = $parameters;
		$this->method = $method;
		$this->file_format = $format;
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
	
	public function getFormat()
	{
		return $this->file_format;
	}
}


/* End of file HMVC.php */
/* Location: ./lib/Inject/Request */