<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core\Dependency;

/**
 * Dynamic dependency injection container.
 * 
 * NOTE: Do not use the dynamic setService() method in production,
 * instead implement the getter directly into a descendant class.
 * 
 * The way you fetch dependencies is to call
 * get<DependencyName>(), because then it will be easy to replace
 * the stored value and/or supplied closure with a method which does
 * everything faster in a child class.
 * 
 * Example of creating a service which is a singleton:
 * <code>
 * $app->container->setService('A.Singleton', function()
 * {
 *     static $o;
 *     
 *     if( ! $o)
 *     {
 *         $o = new stdClass();
 *     }
 *     
 *     return $o;
 * });
 * </code>
 * 
 * Example of a service which depends on another:
 * <code>
 * $container->setService('TheDependency', new stdClass());
 * 
 * $container->setService('User', function($container)
 * {
 *     return new Someclass($container->getTheDependency())
 * });
 * </code>
 */
class DynamicContainer extends Container
{
	/**
	 * The contents of this container.
	 * 
	 * @var array(string => object|callable)
	 */
	protected $services = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @param  string
	 * @param  object|callable
	 * @return void
	 */
	public function setService($key, $value)
	{
		$this->services[$key] = $value;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Imitates get<ServiceName>() for the dynamic dependencies.
	 * 
	 * @param  string
	 * @param  array
	 * @return mixed
	 */
	public function __call($method, array $args = array())
	{
		switch(strtolower(substr($method, 0, 3)))
		{
			case 'get':
				$key = substr($method, 3);
				
				if( ! isset($this->services[$key]))
				{
					// TODO: Exception class
					throw new \Exception(sprintf('The service "%s" is not specified in this container.', $key));
				}
				
				return is_callable($this->services[$key]) ? $this->services[$key]($this) : $this->services[$key];
			case 'set':
				return $this->setService(substr($method, 3));
		}
		
		throw new \RuntimeException(sprintf('Call to undefined method %s::%s', get_class($this), $method));
	}
}

/* End of file DynamicContainer.php */
/* Location: src/php/Inject/Core/Dependency */