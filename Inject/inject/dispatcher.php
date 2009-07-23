<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * A class which sends the request to the proper controllers/classes.
 */
class Inject_Dispatcher
{
	
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
		$class = $req->get_controller() OR $c = Inject::config('dispatcher.default_controller', false);
		
		// get the action
		$action = ($m = $req->get_action()) ? $m : Inject::config('dispatcher.default_action', 'index');
		
		// does the class exists? (enable autoload, so the autoloader(s) can search for it)
		if( ! class_exists($class) OR ! method_exists($class, $action))
		{
			Inject::log('inject', '404 Error on URI: "'.$req->get_uri() . '", class: "'.$class.'", action: "'.$action.'".', Inject::WARNING);
			
			// nope, show an error
			$class = Inject::config('dispatcher.404_controller', 'Inject_controller_404');
		}
		
		Inject::log('inject', 'Loading controller class "'.$class.'".', Inject::DEBUG);
		
		// load the controller
		$controller = new $class($req);
		
		Inject::log('inject', 'Calling action "'.$action.'".', Inject::DEBUG);
		
		// call the action
		$controller->$action();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Dispatcher for the HMVC request type, does not raise 404 errors.
	 * 
	 * @param  Inject_Request
	 * @return void
	 */
	public function hmvc($req)
	{
		// get the controller
		$class = $req->get_controller() OR $c = Inject::config('dispatcher.default_controller', false);
		
		// get the action
		$action = ($m = $req->get_action()) ? $m : Inject::config('dispatcher.default_action', 'index');
		
		// does the class exists? (enable autoload, so the autoloader(s) can search for it)
		if( ! class_exists($class) OR ! method_exists($class, $action))
		{
			Inject::log('inject', 'HMVC: 404 Error on class: "'.$class.'", action: "'.$action.'".', Inject::WARNING);
			
			return;
		}
		
		Inject::log('inject', 'Loading controller class "'.$class.'".', Inject::DEBUG);
		
		// load the controller
		$controller = new $class($req);
		
		Inject::log('inject', 'Calling action "'.$action.'".', Inject::DEBUG);
		
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
		throw new Inject_Exception_InvalidRequestType($method);
	}
}


/* End of file dispatcher.php */
/* Location: ./Inject/inject */