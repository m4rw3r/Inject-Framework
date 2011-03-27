<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core;

/**
 * Base class for the application
 */
abstract class Engine
{
	/**
	 * List of loaded engines.
	 * 
	 * @var array(\Inject\Core\Engine)
	 */
	private static $engines = array();
	
	// ------------------------------------------------------------------------

	/**
	 * Instantiates the engine, or returns an instance if already instantiated.
	 * 
	 * @return \Inject\Core\Engine
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
	 * @return array(\Inject\Core\Engine)
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
	 * @var \Inject\Core\Dependency\ContainerInterface
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
	 * @return \Inject\Core\DependencyInjection\ContainerInterface
	 */
	protected function initContainer()
	{
		if($this->isolated)
		{
			return new Dependency\Container($this);
		}
		else
		{
			return new Dependency\CascadingContainer($this, Application::getApplication()->container);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Initializes the middleware for this engine.
	 * 
	 * @return array(\Inject\Core\Middleware\MiddlewareInterface)
	 */
	protected function initMiddleware()
	{
		return array();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Initializes the endpoint callback for this engine.
	 * 
	 * @return callback
	 */
	protected function initEndpoint()
	{
		return null;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates the application stack and returns it.
	 * 
	 * @return \Inject\Core\MiddlewareStack
	 */
	public function stack()
	{
		return new MiddlewareStack(
				empty($this->middleware) ? $this->middleware = $this->initMiddleware() : $this->middleware,
				empty($this->endpoint)   ? $this->endpoint   = $this->initEndpoint()   : $this->endpoint
			);
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
}


/* End of file Engine.php */
/* Location: src/php/Inject/Core */