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
 * Inject_Container::setGlobalService('database', function()
 * {
 *     return new Db(Inject::getConfiguration('database'));
 * });
 * 
 * // Register a global class, which is not a singleton (the false parameter)
 * Inject_Container::SetGlobalService('view', 'Some_View_Lib', false);
 * 
 * 
 * // Create a container instance which can override global values locally
 * $c = new Inject_Container();
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
 * $db = Inject_Container::getGlobalService('database');
 * 
 * 
 * // Create a global service loader which uses dependencies
 * Inject_Container::setGlobalService('session', function()
 * {
 *     return new Session(Inject_Container::getGlobalService('database'));
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
class Inject_Container
{
	/**
	 * Container for object loaders.
	 * 
	 * @var array
	 */
	protected $loaders = array();
	
	/**
	 * List containing the loaded instances which are "singletons".
	 * 
	 * @var array
	 */
	protected $instances = array();
	
	/**
	 * List of objects which only are allowed a single instance.
	 * 
	 * @var array
	 */
	protected $singletons = array();
	
	/**
	 * Global container for the service loaders.
	 * 
	 * @var array
	 */
	protected static $global_loaders = array();
	
	/**
	 * List containing the loaded instances which are "singletons".
	 * 
	 * @var array
	 */
	protected static $global_instances = array();
	
	/**
	 * List of objects which only are allowed a single instance.
	 * 
	 * @var array
	 */
	protected static $global_singletons = array();
	
	// ------------------------------------------------------------------------

	/**
	 * Tries to fetch the requested class instance (global or not)
	 * 
	 * @return 
	 */
	public function getService($service)
	{
		if(isset($this->instances[$service]))
		{
			return $this->instances[$service];
		}
		
		if( ! isset($this->loaders[$service]))
		{
			return self::getGlobalService($service);
		}
		
		if(is_string($this->loaders[$service]))
		{
			$i = new $this->loaders[$service];
		}
		elseif(is_callable($this->loaders[$service]))
		{
			$i = call_user_func($this->loaders[$service], $this);
		}
		else
		{
			throw new Inject_ContainerException('Faulty service loader for the service "'.$service.'".');
		}
		
		if(isset(self::$global_singletons[$service]))
		{
			$this->instances[$service] = $i;
		}
		
		return $i;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public static function getGlobalService($service)
	{
		if(isset(self::$global_instances[$service]))
		{
			return self::$global_instances[$service];
		}
		
		if( ! isset(self::$global_loaders[$service]))
		{
			throw new Inject_ContainerException('Missing value for service "'.$service.'".');
		}
		
		if(is_string(self::$global_loaders[$service]))
		{
			$i = new self::$global_loaders[$service];
		}
		elseif(is_callable(self::$global_loaders[$service]))
		{
			$i = call_user_func(self::$global_loaders[$service]);
		}
		else
		{
			throw new Inject_ContainerException('Faulty service loader for the service "'.$service.'".');
		}
		
		if(isset(self::$global_singletons[$service]))
		{
			self::$global_instances[$service] = $i;
		}
		
		return $i;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Registers a service and a loader.
	 * 
	 * @param  string
	 * @param  string|callback|Closure	Callbacks must be arrays,
	 * 									both Closures and callbacks will receive the
	 * 									container instance as the first parameter
	 */
	public function setService($service, $loader, $shared = true)
	{
		if($shared)
		{
			$this->loaders[$service] = $loader;
			$this->singletons[$service] = true;
		}
		else
		{
			$this->loaders[$service] = $loader;
			unset($this->singletons[$service]);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Registers a service's loader, globally.
	 * 
	 * To make a closure/callback load another required service, let it use
	 * Inject_Container::getGlobalService().
	 * 
	 * @param  string
	 * @param  string|callback|Closure	Callbacks must be arrays
	 */
	public static function setGlobalService($service, $loader, $shared = true)
	{
		if($shared)
		{
			self::$global_loaders[$service] = $loader;
			self::$global_singletons[$service] = true;
		}
		else
		{
			self::$global_loaders[$service] = $loader;
			unset(self::$global_singletons[$service]);
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
	 * @param  Inject_Container
	 * @return void
	 */
	public function copyContainer(Inject_Container $container)
	{
		$data = $container->getContainerData();
		
		$this->loaders = $data['loaders'];
		$this->instances = $data['instances'];
		$this->singletons = $data['singletons'];
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
				'loaders' => $this->container,
				'instances' => $this->instances,
				'singletons' => $this->singletons
			);
	}
}


/* End of file Container.php */
/* Location: ./lib/Inject */