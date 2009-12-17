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
	
	protected $class_name;
	
	protected $action_name;
	
	protected $parameters = array();
	
	function __construct($class_name, $action_name = false, $parameters = array())
	{
		$this->class_name = $class_name;
		$this->action_name = $action_name;
		$this->parameters = $parameters;
	}
	
	// ------------------------------------------------------------------------
	
	public function getType()
	{
		return 'hmvc';
	}
	
	// ------------------------------------------------------------------------
	
	public function getClass()
	{
		return $this->class_name;
	}
	
	// ------------------------------------------------------------------------
	
	public function getMethod()
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
}


/* End of file Request.php */
/* Location: ./Users/m4rw3r/Sites/Inject-Framework/lib/Inject/Request.php */