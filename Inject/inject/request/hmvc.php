<?php
/*
 * Created by Martin Wernståhl on 2009-07-18.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * A HMVC Request, to use when calling a controller from another controller.
 * 
 * Example:
 * <code>
 * $req = new Inject_Request_HMVC();
 * $req->set_controller('Controller_TestHmvc');
 * $req->set_action('foobar');
 * 
 * Inject::run($req);
 * </code>
 */
class Inject_Request_HMVC implements Inject_Request
{
	protected $controller = false;
	
	protected $action = false;
	
	protected $parameters = array();
	
	public function get_controller()
	{
		return $this->controller;
	}
	
	public function get_action()
	{
		return $this->action;
	}
	
	public function get_parameter($name, $default = null)
	{
		return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
	}
	
	public function get_uri()
	{
		return '';
	}
	
	public function get_type()
	{
		return 'HMVC';
	}
	
	/**
	 * Sets the controller class name, INCLUDING "Controller_".
	 * 
	 * @param  string
	 * @return self
	 */
	public function set_controller($class_name)
	{
		$this->controller = $class_name;
		
		return $this;
	}
	
	/**
	 * Sets the name of the method to call.
	 * 
	 * @param  string
	 * @return self
	 */
	public function set_action($method_name)
	{
		$this->action = $this->method_name;
		
		return $this;
	}
	
	/**
	 * Sets the parameters for this request.
	 * 
	 * @param  array
	 * @return self
	 */
	public function set_parameters(Array $params)
	{
		$this->parameters = $params;
		
		return $this;
	}
	
	/**
	 * Sets a single parameter for this request.
	 * 
	 * @param  string
	 * @param  string
	 * @return self
	 */
	public function set_paramter($name, $value)
	{
		$this->parameters[$name] = $value;
		
		return $this;
	}
}


/* End of file http.php */
/* Location: ./Inject/inject/request */