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
	const SUCCESS = 1;
	const MISSING_CLASS = 2;
	const MISSING_ACTION = 3;
	
	/**
	 * The default class to call.
	 * 
	 * @var string
	 */
	protected $default_class;
	
	/**
	 * The default action method.
	 * 
	 * @var string
	 */
	protected $default_action;
	
	/**
	 * The class which is to be called in case of a class->method which cannot be called.
	 * 
	 * @var string
	 */
	protected $missing_class;
	
	/**
	 * The method which is to be called in case of a class->method which cannot be called.
	 * 
	 * @var string
	 */
	protected $missing_action;
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the default controller class and action.
	 * 
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function setDefaultHandler($class, $method)
	{
		$this->default_class = $class;
		$this->default_action = $method;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the controller and method which is to be called if the class and/or method cannot be found/called.
	 * 
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function set404Handler($class_name, $method_name)
	{
		$this->missing_class = $class_name;
		$this->missing_action = $method_name;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Handles a HTTP request.
	 * 
	 * @param  Inject_Request_HTTP
	 * @return void
	 */
	public function http(Inject_Request_HTTP $req)
	{
		// get the controller
		($class = $req->getControllerClass()) OR ($class = $this->default_class);
		
		// get the action
		$action = ($m = $req->getActionMethod()) ? $m : $this->default_action;
		
		switch($this->run($req, $class, $action))
		{
			case Inject_Dispatcher::SUCCESS:
				return;
			case Inject_Dispatcher::MISSING_CLASS:
			case Inject_Dispatcher::MISSING_ACTION:
				Inject::log('Dispatcher', '404 Error on class: "'.$class.'", action: "'.$action.'", going to the 404 handler.', Inject::NOTICE);

				// nope, show an error
				$class = $this->missing_class;
				$action = $this->missing_action;

				switch($this->run($req, $class, $action))
				{
					case Inject_Dispatcher::SUCCESS:
						return;
					case Inject_Dispatcher::MISSING_CLASS:
						throw new Inject_Dispatcher_ClassException('Controller class "'.$class.'" cannot be found.');
					case Inject_Dispatcher::MISSING_ACTION:
						throw new Inject_Dispatcher_MethodException('Action method "'.$action.'" cannot be called');
				}
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Handles a CLI request.
	 *
	 * @param Inject_Request_CLI
	 * @return void
	 */
	public function cli(Inject_Request_CLI $req)
	{
		// get the controller
		$class = $req->getControllerClass();
		
		// get the action
		$action = ($m = $req->getActionMethod()) ? $m : $this->default_action;
		
		// does the class exists? (enable autoload, so the autoloader(s) can search for it)
		switch($this->run($req, $class, $action))
		{
			case Inject_Dispatcher::MISSING_ACTION:
				echo '
ERROR: Action '.$class.'::'.$action.' cannot be called!
';
				
				$req->showHelp();
				
				break;
				
			case Inject_Dispatcher::MISSING_CLASS:
				echo '
ERROR: Controller '.$class.' not found!
';
			
			$req->showHelp();
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Dispatcher method for the Inject_Request_HMVC
	 * 
	 * @param  Inject_Request_HMVC
	 * @return void
	 */
	public function hmvc(Inject_Request_HMVC $req)
	{
		// get the controller
		($class = $req->getControllerClass()) OR ($class = $this->default_class);
		
		// get the action
		$action = ($m = $req->getActionMethod()) ? $m : $this->default_action;
		
		try
		{
			$this->run($req, $class, $action);
		}
		catch(Inject_DispatcherException $e)
		{
			Inject::log('Dispatcher', 'HMVC: 404 Error on class: "'.$class.'", action: "'.$action.'".', Inject::WARNING);
			
			// HMVC should not cause errors or call a 404 controller, just a warning
			return;
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * The method doing the grunt work of trying to run the request.
	 * 
	 * @return void
	 */
	protected function run(Inject_Request $req, $controller, $action)
	{
		Inject::log('Dispatcher', 'Loading controller class "'.$controller.'".', Inject::DEBUG);
		
		// If to fallback to the catch all __actionCall($method) method
		$fallback = false;
		
		// Validate again, to make sure it hasn't gone FUBAR
		if( ! class_exists($controller, false) && ! Inject::load($controller, Inject::NOTICE))
		{
			return Inject_Dispatcher::MISSING_CLASS;
		}
		
		// Check if the method can be called
		$r = new ReflectionClass($controller);
		
		if(( ! $r->hasMethod($action) OR ! $m = $r->getMethod($action) OR ! $m->isPublic() OR $m->isStatic()))
		{
			if( ! $r->hasMethod('__actionCall'))
			{
				return Inject_Dispatcher::MISSING_ACTION;
			}
			else
			{
				$fallback = true;
			}
		}
		
		// Load the controller
		$controller = new $controller($req);
		
		if( ! $fallback)
		{
			// Call the action
			Inject::log('Dispatcher', 'Calling action "'.$action.'".', Inject::DEBUG);
			$controller->$action();
		}
		else
		{
			// Call fallback
			Inject::log('Dispatcher', 'Calling fallback action (__actionCall) with "'.$action.'".', Inject::DEBUG);
			$controller->__actionCall($action);
		}
		
		
		return Inject_Dispatcher::SUCCESS;
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