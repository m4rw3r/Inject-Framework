<?php
/*
 * Created by Martin Wernståhl on 2009-12-17.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Base class which acts as a registry, can be used both as a local registry and as a global.
 * 
 * Has two registries, one instance registry and one global (static) registry.
 * The instance registry is searched first, if nothing is found then it tries
 * to search the global registry.
 * 
 * The global registry can also be accessed directly without having to instantiate
 * Inject_Registry.
 * 
 * 
 * Example:
 * 
 * <code>
 * $r = new Inject_Registry;
 * 
 * var_dump($r['db']); // null
 * 
 * $r['db'] = new Db_Instance;
 * 
 * var_dump($r['db']); // dumps the Db_Instance
 * 
 * Inject::setGlobal('db', new Db_Instance2);
 * 
 * var_dump($r['db']); // dumps the Db_Instance
 * 
 * unset($r['db']);
 * 
 * var_dump($r['db']); // dumps the Db_Instance2
 * </code>
 * 
 * 
 * TIP:
 * 
 * The (same) global registry is still accessible by descendants.
 * So if a global instance is set with Inject_Registry;:setGlobal(),
 * then also Inject_Request can read the same instances.
 * 
 * @author Martin Wernståhl <m4rw3r@gmail.com>
 */
class Inject_Registry
{
	/**
	 * Global instance-keys, fallbacks for the local registry if they aren't found.
	 * 
	 * @var array
	 */
	protected static $global_registry = array();
	
	/**
	 * The local registry keys.
	 * 
	 * @var array
	 */
	protected $local_registry = array();
	
	// ------------------------------------------------------------------------

	/**
	 * Sets a local object instance.
	 * 
	 * @param  string
	 * @param  object
	 * @return void
	 */
	public function __set($key, $object)
	{
		$this->local_registry[$key] = $object;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns an object instance.
	 * 
	 * @return object|null
	 */
	public function __get($key)
	{
		return isset($this->local_registry[$key]) ? $this->local_registry[$key] : (isset(self::$global_registry[$key]) ? self::$global_registry[$key] : null);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns true if either a local or a global instance exists.
	 * 
	 * @param  string
	 * @return bool
	 */
	public function __isset($key)
	{
		return isset($this->local_registry[$key]) OR isset(self::$global_registry[$key]);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Removes a local object instance.
	 * 
	 * @param  string
	 * @return void
	 */
	public function __unset($key)
	{
		unset($this->local_registry[$key]);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the internal local data array.
	 * 
	 * @return array
	 */
	public function getLocalInstances()
	{
		return $this->local_registry;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the local instance registry to the supplied data array.
	 * 
	 * @param  array
	 * @return void
	 */
	public function setLocalInstances(array $data)
	{
		$this->local_registry = $data;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds a list of instances to the existing local data array, overwriting existing keys.
	 * 
	 * @param  array
	 * @return void
	 */
	public function addLocalInstances(array $data)
	{
		$this->local_registry = array_merge($this->local_registry, $data);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets a global object instance.
	 * 
	 * @param  string
	 * @return object
	 */
	public static function setGlobal($key, $obj)
	{
		self::$global_registry[$key] = $obj;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a global object instance.
	 * 
	 * @param  string
	 * @return object|null
	 */
	public static function getGlobal($key)
	{
		return isset(self::$global_registry[$key]) ? self::$global_registry[$key] : null;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns true if a global object instance exists.
	 * 
	 * @param  string
	 * @return bool
	 */
	public static function issetGlobal($key)
	{
		return isset(self::$global_registry);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Removes a global object instance.
	 * 
	 * @param  string
	 * @return void
	 */
	public static function unsetGlobal($key)
	{
		unset(self::$global_registry);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the internal global data array.
	 * 
	 * @return array
	 */
	public static function getGlobalInstances()
	{
		return self::$global_registry;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the global instance registry to the supplied data array.
	 * 
	 * @param  array
	 * @return void
	 */
	public static function setGlobalInstances(array $data)
	{
		self::$global_registry = $data;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds a list of instances to the existing global data array, overwriting existing keys.
	 * 
	 * @param  array
	 * @return void
	 */
	public static function addGlobalInstances(array $data)
	{
		self::$global_registry = array_merge(self::$global_registry, $data);
	}
}


/* End of file Registry.php */
/* Location: ./lib/Inject */