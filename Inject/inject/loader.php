<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Loader
{
	protected $instance;
	
	/**
	 * A list of the properties which has been loaded.
	 * 
	 * @var array
	 */
	protected $loaded = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct($params = array())
	{
		$this->instance = Inject::parameter('instance', $params);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function model($name, $params = array(), $prop = false)
	{
		if( ! $prop)
		{
			$prop = $name;
		}
		
		$class = 'Model_' . $name;
		
		return $this->assign_class($class, $params, $prop);
	}
	
	/**
	 * Loads a generic library for the associated instance.
	 * 
	 * @param  string
	 * @param  array
	 * @param  string
	 * @param  
	 */
	public function library($name, $params = array(), $prop = false)
	{
		if( ! $prop)
		{
			$prop = $name;
		}
		
		return $this->assign_class($name, $params, $prop);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @param  string
	 * @param  array
	 * @param  string
	 * @return bool
	 */
	protected function assign_class($class, $params, $prop)
	{
		if(isset($this->loaded[$prop]) && $this->loaded[$prop] == strtolower($class))
		{
			return true;
		}
		
		if(isset($this->instance->$prop))
		{
			// TODO: Exception
			die("The property '$prop' is already set on the controller, cannot load the model '$class'.");
		}
		
		$this->instance->$prop = Inject::create($class, $params);
		
		$this->loaded[$prop] = strtolower($class);
		
		return true;
	}
}


/* End of file loader.php */
/* Location: ./Inject/inject */