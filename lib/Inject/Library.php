<?php
/*
 * Created by Martin Wernståhl on 2009-12-28.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Dependency Injection container.
 * 
 * Preferably used by PHP 5.3, but also works with PHP 5.2.
 * 
 * PHP 5.3 usage:
 * <code>
 * // Register a global resource loader:
 * Inject_Library::setGlobalResource('database', function()
 * {
 *     return new Db(Inject::getConfiguration('database'));
 * });
 * 
 * // Register a global class, which is not a singleton (the false parameter)
 * Inject_Library::SetGlobalResource('view', 'Some_View_Lib', false);
 * 
 * 
 * // Create a container instance which can override global values locally
 * $c = new Inject_Library();
 * 
 * // Get the database object
 * $db = $c->getResource('database');
 * 
 * // Instantiate a view object
 * $v = $c->getResource('view');
 * $v2 = $c->getResource('view');
 * var_dump($v === $v2);
 * 
 * // Override the view class locally
 * $c->setResource('view', 'Another_View', false);
 * 
 * // Get an instance of the new view class
 * $v = $c->getResource('view');
 * 
 * 
 * // Use the global container to get the database object
 * $db = Inject_Library::getGlobalResource('database');
 * 
 * 
 * // Create a global resource loader which uses dependencies
 * Inject_Library::setGlobalResource('session', function()
 * {
 *     return new Session(Inject_Library::getGlobalResource('database'));
 * });
 * 
 * // Create a local resource loader which uses dependencies
 * $c->setResource('access', function($container)
 * {
 *     return new Access($container->getResource('session'));
 * });
 * </code>
 * 
 * For PHP 5.2 you have to move the code which is located in closures into
 * separate methods/functions and then set their callbacks there instead of
 * the closures themselves (the callbacks must be wrapped in arrays, it is
 * more convenient when deciding what to do).
 */
class Inject_Library
{
	/**
	 * Container for object loaders.
	 * 
	 * @var array
	 */
	protected $_loaders = array();
	
	/**
	 * List containing the loaded instances which are "singletons".
	 * 
	 * @var array
	 */
	protected $_instances = array();
	
	/**
	 * List of objects which only are allowed a single instance.
	 * 
	 * @var array
	 */
	protected $_singletons = array();
	
	/**
	 * Global container for the resource loaders.
	 * 
	 * @var array
	 */
	protected static $_global_loaders = array();
	
	/**
	 * List containing the loaded instances which are "singletons".
	 * 
	 * @var array
	 */
	protected static $_global_instances = array();
	
	/**
	 * List of objects which only are allowed a single instance.
	 * 
	 * @var array
	 */
	protected static $_global_singletons = array();
	
	// ------------------------------------------------------------------------

	/**
	 * Tries to fetch the requested class instance (global or not)
	 * 
	 * @param  string
	 * @return object
	 */
	public function getResource($resource)
	{
		if(isset($this->_instances[$resource]))
		{
			return $this->_instances[$resource];
		}
		
		if( ! isset($this->_loaders[$resource]))
		{
			return self::getGlobalResource($resource);
		}
		
		if(is_string($this->_loaders[$resource]))
		{
			$i = new $this->_loaders[$resource];
		}
		elseif(is_callable($this->_loaders[$resource]))
		{
			$i = call_user_func($this->_loaders[$resource], $this);
		}
		else
		{
			throw new Inject_LibraryException('Faulty resource loader for the resource "'.$resource.'".');
		}
		
		if(isset(self::$_global_singletons[$resource]))
		{
			$this->_instances[$resource] = $i;
		}
		
		return $i;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a global resource instance.
	 * 
	 * @param  string
	 * @return object
	 */
	public static function getGlobalResource($resource)
	{
		if(isset(self::$_global_instances[$resource]))
		{
			return self::$_global_instances[$resource];
		}
		
		if( ! isset(self::$_global_loaders[$resource]))
		{
			throw new Inject_LibraryException('Missing value for resource "'.$resource.'".');
		}
		
		if(is_string(self::$_global_loaders[$resource]))
		{
			$i = new self::$_global_loaders[$resource];
		}
		elseif(is_callable(self::$_global_loaders[$resource]))
		{
			$i = call_user_func(self::$_global_loaders[$resource]);
		}
		else
		{
			throw new Inject_LibraryException('Faulty resource loader for the resource "'.$resource.'".');
		}
		
		if(isset(self::$_global_singletons[$resource]))
		{
			self::$_global_instances[$resource] = $i;
		}
		
		return $i;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Registers a resource and a loader.
	 * 
	 * @param  string
	 * @param  string|callback|Closure	Callbacks must be arrays,
	 * 									both Closures and callbacks will receive the
	 * 									container instance as the first parameter
	 */
	public function setResource($resource, $loader, $shared = true)
	{
		if($shared)
		{
			$this->_loaders[$resource] = $loader;
			$this->_singletons[$resource] = true;
		}
		else
		{
			$this->_loaders[$resource] = $loader;
			unset($this->_singletons[$resource]);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Registers a resource's loader, globally.
	 * 
	 * To make a closure/callback load another required resource, let it use
	 * Inject_Library::getGlobalResource().
	 * 
	 * @param  string
	 * @param  string|callback|Closure	Callbacks must be arrays
	 */
	public static function setGlobalResource($resource, $loader, $shared = true)
	{
		if($shared)
		{
			self::$_global_loaders[$resource] = $loader;
			self::$_global_singletons[$resource] = true;
		}
		else
		{
			self::$_global_loaders[$resource] = $loader;
			unset(self::$_global_singletons[$resource]);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Copies the data of a container into this container.
	 * 
	 * NOTE
	 * Overwrites the current container data.
	 * Does not affect the global resources.
	 * 
	 * @param  Inject_Library
	 * @return void
	 */
	public function copyContainer(Inject_Library $container)
	{
		$data = $container->getContainerData();
		
		$this->_loaders = $data['loaders'];
		$this->_instances = $data['instances'];
		$this->_singletons = $data['singletons'];
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the container data, which is used by copyContainer.
	 * 
	 * @return array
	 */
	public function getContainerData()
	{
		return array(
				'loaders' => $this->_container,
				'instances' => $this->_instances,
				'singletons' => $this->_singletons
			);
	}
}


/* End of file Container.php */
/* Location: ./lib/Inject */