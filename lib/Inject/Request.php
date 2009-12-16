<?php
/*
 * Created by Martin Wernståhl on 2009-12-16.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Request
{
	/**
	 * Returns the type name of this request, used to determine what to do in the dispatcher.
	 * 
	 * @return string
	 */
	abstract public function getType();
	
	/**
	 * Returns the class name to call.
	 * 
	 * @return string
	 */
	abstract public function getClass();
	
	/**
	 * Returns the method name to call.
	 * 
	 * @return string
	 */
	abstract public function getMethod();
	
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
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the registered instance, false if not found.
	 * 
	 * @return object
	 */
	public function getInstance($key)
	{
		return isset($this->registry[$key]) ? $this->registry[$key] : false;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the object to be stored in a certain registry key.
	 * 
	 * @param  string
	 * @param  object
	 * @return void
	 */
	public function setInstance($key, $object)
	{
		$this->registry[$object];
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the registry array.
	 * 
	 * @return array
	 */
	public function getRegisteredInstances()
	{
		return $this->registry;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the content of the registry.
	 * 
	 * @param  array
	 * @return void
	 */
	public function setRegisteredInstances(array $array)
	{
		$this->registry = $array;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds additional instances, overwriting identical keys.
	 * 
	 * @param  array
	 * @return void
	 */
	public function addRegisteredInstances(array $array)
	{
		$this->registry = array_merge($array, $this->registry);
	}
}


/* End of file Request.php */
/* Location: ./lib/Inject */