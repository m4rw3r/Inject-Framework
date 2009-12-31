<?php
/*
 * Created by Martin Wernståhl on 2009-12-16.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
abstract class Inject_Request extends Inject_Container
{
	/**
	 * Returns the protocol of this request, used to determine what to do in the dispatcher.
	 * 
	 * Eg. http, cli, hmvc etc.
	 * 
	 * @return string	lowercase
	 */
	abstract public function getProtocol();
	
	/**
	 * Returns the request method, GET, PUT, POST, DELETE etc.
	 * 
	 * @return string	uppercase
	 */
	abstract public function getMethod();
	
	/**
	 * Returns the class name of the controller.
	 * 
	 * @return string
	 */
	abstract public function getControllerClass();
	
	/**
	 * Returns the method name of the action to call.
	 * 
	 * @return string
	 */
	abstract public function getActionMethod();
	
	/**
	 * Returns a parameter of this request, if not present $default will be returned.
	 * 
	 * @param  string
	 * @param  mixed
	 * @return mixed
	 */
	abstract public function getParameter($name, $default = null);
	
	/**
	 * Returns the parameter array from this request object.
	 * 
	 * @return array
	 */
	abstract public function getParameters();
	
	/**
	 * Handles a generic error and prints error information.
	 * 
	 * @param  int
	 * @param  string
	 * @param  string
	 * @param  int
	 * @param  array
	 * @return void
	 */
	public function showError($level, $type, $message, $file, $line, $trace)
	{
		// TODO: Make the standard trace easier to read
		echo '
An error has occurred: '.$type.':

'.$message.'

in file "'.$file.'" at line '.$line.'

Trace:
';
		
		print_r($trace);
	}
	
	/**
	 * Handles a fatal error and prints minimum error information.
	 * 
	 * This is for production, when you don't want to expose errors to the world.
	 * 
	 * @param  int
	 * @param  string
	 * @param  string
	 * @param  int
	 * @param  array
	 * @return void
	 */
	public function showError500($level, $type, $message, $file, $line, $trace)
	{
		// Default view
		echo '
! A Fatal Error occurred !
==========================
';
	}
}


/* End of file Request.php */
/* Location: ./lib/Inject */