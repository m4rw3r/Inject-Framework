<?php
/*
 * Created by Martin Wernståhl on 2009-12-17.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Dispatcher
{
	protected $default_class;
	
	protected $default_action;
	
	protected $missing_controller;
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function setDefaultControllerClass($str)
	{
		$this->default_class = $str;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function setDefaultControllerAction($str)
	{
		$this->default_action = $str;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function setMissingClassHandlerController($class_name)
	{
		$this->missing_controller = $class_name;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Handles a HTTP request.
	 * 
	 * @param  Inject_Request
	 * @return void
	 */
	public function http($req)
	{
		// get the controller
		($class = $req->getClass()) OR ($class = $this->default_class);
		
		// get the action
		$action = ($m = $req->getMethod()) ? $m : $this->default_action;
		
		// does the class exists? (enable autoload, so the autoloader(s) can search for it)
		if( ! class_exists($class) OR ! method_exists($class, $action))
		{
			Inject::log('Inject', '404 Error on URI: "'. new URL() . '", class: "'.$class.'", action: "'.$action.'".', Inject::WARNING);
			
			// nope, show an error
			$class = $this->missing_controller;
		}
		
		Inject::log('Inject', 'Loading controller class "'.$class.'".', Inject::DEBUG);
		
		// load the controller
		$controller = new $class($req);
		
		Inject::log('Inject', 'Calling action "'.$action.'".', Inject::DEBUG);
		
		// call the action
		$controller->$action();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Dispatcher method for the Inject_Request_HMVC
	 * 
	 * @return 
	 */
	public function hmvc(Inject_Request_HMVC $req)
	{
		// get the controller
		($class = $req->getClass()) OR ($class = $this->default_class);
		
		// get the action
		$action = ($m = $req->getMethod()) ? $m : $this->default_action;
		
		// does the class exists? (enable autoload, so the autoloader(s) can search for it)
		if( ! class_exists($class) OR ! method_exists($class, $action))
		{
			Inject::log('Inject', 'HMVC: 404 Error on class: "'.$class.'", action: "'.$action.'".', Inject::WARNING);
			
			// HMVC should not cause errors, just a warning
			return;
		}
		
		Inject::log('Inject', 'Loading controller class "'.$class.'".', Inject::DEBUG);
		
		// load the controller
		$controller = new $class($req);
		
		Inject::log('Inject', 'Calling action "'.$action.'".', Inject::DEBUG);
		
		// call the action
		$controller->$action();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Raises an error for an invalid request type.
	 * 
	 * @return void
	 */
	public function __call($method, $params = array())
	{
		throw new Inject_Dispatcher_InvalidRequestTypeException($method);
	}
}


/* End of file Dispatcher.php */
/* Location: ./lib/Inject */