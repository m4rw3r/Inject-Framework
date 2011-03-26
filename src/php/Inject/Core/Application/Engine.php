<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core\Application;

use \Inject\Core\Application;
use \Inject\Core\MiddlewareStack;
use \Inject\Core\Dependency\Container as DIContainer;
use \Inject\Core\Dependency\CascadingContainer as CascadingDIContainer;

/**
 * Base class for the application
 */
abstract class Engine
{
	/**
	 * List of loaded engines.
	 * 
	 * @var array(\Inject\Application\Engine)
	 */
	private static $engines = array();
	
	// ------------------------------------------------------------------------

	/**
	 * Instantiates the engine, or returns an instance if already instantiated.
	 * 
	 * @return \Inject\Application\Engine
	 */
	public static function instance()
	{
		$class = get_called_class();
		
		return isset(self::$engines[$class]) ? self::$engines[$class] : self::$engines[$class] = new $class();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns an array containing the loaded engines.
	 * 
	 * @return array(\Inject\Application\Engine)
	 */
	public static function getLoadedEngines()
	{
		return self::$engines;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * The root directory of this application engine.
	 * 
	 * @var string
	 */
	protected $engine_root = '';
	
	/**
	 * If this engine should act as its own entity, or reuse components from
	 * the main request Application.
	 * 
	 * @var boolean
	 */
	protected $isolated = false;
	
	/**
	 * A list of paths to different resources associated with this Engine instance.
	 * 
	 * @var array(string => string)
	 */
	public $paths = array();
	
	/**
	 * The dependency injection container for the application.
	 * 
	 * @var \Inject\Dependency\ContainerInterface
	 */
	public $container;
	
	/**
	 * The list of initialized middleware, cached result from initMiddleware().
	 * 
	 * @var array(\Inject\Core\Middleware\MiddlewareInterface)
	 */
	protected $middleware = array();
	
	/**
	 * Cache of the endpoint from initEndpoint().
	 * 
	 * @var callback
	 */
	protected $endpoint = null;
	
	// ------------------------------------------------------------------------
	
	/**
	 * Creates a new application instance.
	 */
	protected function __construct()
	{
		self::$engines[]       = $this;
		
		$this->engine_root     = $this->registerRootDir();
		$this->paths           = $this->initPaths();
		$this->container       = $this->initContainer();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Should return the root directory for the application managed by this
	 * engine instance.
	 * 
	 * @return string
	 */
	abstract protected function registerRootDir();
	
	// ------------------------------------------------------------------------

	/**
	 * Initializes the paths for the different assets related to the application.
	 * 
	 * Controllers and Models are not included as their loading is governed
	 * by the autoloader.
	 * 
	 * Default paths:
	 * - assets: ENGINE_ROOT/Resources/Assets/
	 * - cache:  ENGINE_ROOT/Resources/Cache/
	 * - config: ENGINE_ROOT/Resources/Config/
	 * - engine: ENGINE_ROOT/
	 * - lang:   ENGINE_ROOT/Resources/I18n/
	 * - views:  ENGINE_ROOT/Resources/Views/
	 * 
	 * @return array(string => string)
	 */
	protected function initPaths()
	{
		return array(
				'assets' => $this->engine_root.'/Resources/Assets/',
				'cache'  => $this->engine_root.'/Resources/Cache/',
				'config' => $this->engine_root.'/Resources/Config/',
				'engine' => $this->engine_root.'/',
				'lang'   => $this->engine_root.'/Resources/I18n/',
				'views'  => $this->engine_root.'/Resources/Views/'
			);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Initializes the dependency injection container, override in child class
	 * to provide a more application specific implementation.
	 * 
	 * @return \Inject\DependencyInjection\ContainerInterface
	 */
	protected function initContainer()
	{
		if($this->isolated)
		{
			return new DIContainer($this);
		}
		else
		{
			return new CascadingDIContainer($this, Application::getApplication()->container);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	protected function initMiddleware()
	{
		return array();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	protected function initEndpoint()
	{
		return null;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function getMiddleware()
	{
		if(empty($this->middleware))
		{
			return $this->middleware = $this->initMiddleware();
		}
		
		return $this->middleware;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function getEndpoint()
	{
		if(empty($this->endpoint))
		{
			return $this->endpoint = $this->initEndpoint();
		}
		
		return $this->endpoint;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates the application stack and returns it.
	 * 
	 * @return \Inject\Core\MiddlewareStack
	 */
	public function stack()
	{
		return new MiddlewareStack($this->getMiddleware(), $this->getEndpoint());
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of controller identifiers and controller classes.
	 * 
	 * The identifier is the key, and is lowercase. The default mapping between
	 * identifier and class name is \<PackageName>\Controller\<Identifier>.
	 * 
	 * NOTE:
	 * Cache the result of this, this uses a directory iterator to iterate the
	 * filesystem which makes it too slow to run on every request.
	 * 
	 * @return array(string => string)
	 */
	public function getAvailableControllers()
	{
		$controllers = array();
		$path = $this->engine_root.'/Controller';
		
		// Create a fully qualified namespace prefix for the controllers
		$reflection = new \ReflectionClass($this);
		$namespace  = '\\'.$reflection->getNamespaceName().'\\Controller';
		
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::CHILD_FIRST) as $file)
		{
			// Only PHP files
			if( ! $file->isDir() && substr($file->getBasename(), -4) == '.php')
			{
				$relative_path = preg_replace('#^'.preg_quote($path, '#').'#', '', $file->getPath()).'/'.$file->getBasename('.php');
				$class = $namespace.str_replace('/', '\\', $relative_path);
				
				// TODO: UTF-8:
				$identifier = trim(strtolower($relative_path), '/');
				
				$controllers[$identifier] = $class;
			}
		}
		
		return $controllers;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Instantiates a controller with the given name (not necessarily the class
	 * name) override in child classes to create controllers in different namespaces.
	 * 
	 * @param  string
	 * @return Object
	 */
	public function createController($name)
	{
		return new $name($this);
	}
}


/* End of file Engine.php */
/* Location: src/php/Inject/Core/Application */