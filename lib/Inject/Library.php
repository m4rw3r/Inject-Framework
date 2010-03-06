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
 * // Register a global service loader:
 * Inject_Library::setGlobalService('database', function()
 * {
 *     return new Db(Inject::getConfiguration('database'));
 * });
 * 
 * // Register a global class, which is not a singleton (the false parameter)
 * Inject_Library::getGlobalService('view', 'Some_View_Lib', false);
 * 
 * 
 * // Create a container instance which can override global values locally
 * $c = new Inject_Library();
 * 
 * // Get the database object
 * $db = $c->getService('database');
 * 
 * // Instantiate a view object
 * $v = $c->getService('view');
 * $v2 = $c->getService('view');
 * var_dump($v === $v2);
 * 
 * // Override the view class locally
 * $c->setService('view', 'Another_View', false);
 * 
 * // Get an instance of the new view class
 * $v = $c->getService('view');
 * 
 * 
 * // Use the global container to get the database object
 * $db = Inject_Library::getGlobalService('database');
 * 
 * 
 * // Create a global service loader which uses dependencies
 * Inject_Library::setGlobalService('session', function()
 * {
 *     return new Session(Inject_Library::getGlobalService('database'));
 * });
 * 
 * // Create a local service loader which uses dependencies
 * $c->setService('access', function($container)
 * {
 *     return new Access($container->getService('session'));
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
	 * Global container for the service loaders.
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
	 * Returns true if a service with the name $service exists.
	 * 
	 * @param  string
	 * @return bool
	 */
	public function hasService($service)
	{
		return isset($this->_loaders[$service]) OR isset(self::$_global_loaders[$service]);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Tries to fetch the requested class instance (global or not)
	 * 
	 * @param  string
	 * @return object
	 */
	public function getService($service)
	{
		if(isset($this->_instances[$service]))
		{
			return $this->_instances[$service];
		}
		
		if( ! isset($this->_loaders[$service]))
		{
			return self::getGlobalService($service);
		}
		
		if(is_string($this->_loaders[$service]))
		{
			$i = new $this->_loaders[$service];
		}
		elseif(is_callable($this->_loaders[$service]))
		{
			$i = call_user_func($this->_loaders[$service], $this);
		}
		else
		{
			throw new Inject_LibraryException('Faulty service loader for the service "'.$service.'".');
		}
		
		if(isset(self::$_global_singletons[$service]))
		{
			$this->_instances[$service] = $i;
		}
		
		return $i;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a global service instance.
	 * 
	 * @param  string
	 * @return object
	 */
	public static function getGlobalService($service)
	{
		if(isset(self::$_global_instances[$service]))
		{
			return self::$_global_instances[$service];
		}
		
		if( ! isset(self::$_global_loaders[$service]))
		{
			throw new Inject_LibraryException('No registered service with the name "'.$service.'" has been registered, cannot load service.');
		}
		
		if(is_string(self::$_global_loaders[$service]))
		{
			$i = new self::$_global_loaders[$service];
		}
		elseif(is_callable(self::$_global_loaders[$service]))
		{
			$i = call_user_func(self::$_global_loaders[$service]);
		}
		else
		{
			throw new Inject_LibraryException('Faulty service loader for the service "'.$service.'".');
		}
		
		if(isset(self::$_global_singletons[$service]))
		{
			self::$_global_instances[$service] = $i;
		}
		
		return $i;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Registers a service and a loader.
	 * 
	 * @param  string
	 * @param  string|callback|Closure Callbacks must be arrays,
     *                                  both Closures and callbacks will receive the
     *                                  container instance as the first parameter
	 * @param  bool If the service should be registered as a shared service,
	 *              ie. a singleton (for this Library instance only)
	 * @return void
	 */
	public function setService($service, $loader, $shared = true)
	{
		if($shared)
		{
			$this->_loaders[$service] = $loader;
			$this->_singletons[$service] = true;
		}
		else
		{
			$this->_loaders[$service] = $loader;
			unset($this->_singletons[$service]);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Registers a service's loader, globally.
	 * 
	 * To make a closure/callback load another required service, let it use
	 * Inject_Library::getGlobalService().
	 * 
	 * @param  string
	 * @param  string|callback|Closure	Callbacks must be arrays
	 * @param  bool  If this service should be registered as a shared service,
	 *               ie. a singleton
	 * @return void
	 */
	public static function setGlobalService($service, $loader, $shared = true)
	{
		if($shared)
		{
			self::$_global_loaders[$service] = $loader;
			self::$_global_singletons[$service] = true;
		}
		else
		{
			self::$_global_loaders[$service] = $loader;
			unset(self::$_global_singletons[$service]);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Copies the data of a container into this container.
	 * 
	 * NOTE
	 * Overwrites the current container data.
	 * Does not affect the global services.
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


/* End of file Library.php */
/* Location: ./lib/Inject */