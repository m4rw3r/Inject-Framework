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
	 * Version of InjectFramework.
	 * 
	 * Compatible with php's version_compare().
	 * 
	 * @var string
	 */
	const VERSION = '0.1.0-dev';
	
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
	 * A list of paths to different resources associated with this Engine instance.
	 * 
	 * @var array(string => string)
	 */
	public $paths = array();
	
	/**
	 * The configuration for this Engine.
	 * 
	 * @var array(string => mixed)
	 */
	public $config = array();
	
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
	
	/**
	 * A cached list of available controllers, short_name => classname.
	 * 
	 * @var array(string => string)
	 */
	protected $available_controllers = false;
	
	// ------------------------------------------------------------------------
	
	/**
	 * Creates a new application instance.
	 */
	protected function __construct()
	{
		self::$engines[]   = $this;
		
		$this->engine_root = $this->registerRootDir();
		$this->paths       = $this->initPaths();
		$this->config      = $this->initConfig();
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
	 * Initializes the engine configuration, default configuration is loaded
	 * from the return value of $this->paths['config'].'Config.php'.
	 * 
	 * @return array(string => mixed)
	 */
	protected function initConfig()
	{
		if(file_exists($this->paths['config'].'Config.php'))
		{
			 return include $this->paths['config'].'Config.php';
		}
		else
		{
			// TODO: Are these sane defaults?
			return array(
					'debug' => true
				);
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
	 * Creates the application stack and returns it, used to call this Engine
	 * by calling the stack instance's run($env) method.
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
	 * TODO: Move to a separate ControllerLocator class?
	 * 
	 * @return array(string => string)
	 */
	public function getAvailableControllers()
	{
		if($this->available_controllers !== false)
		{
			return $this->available_controllers;
		}
		
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
		
		return $this->available_controllers = $controllers;
	}
}


/* End of file Engine.php */
/* Location: src/php/Inject/Core */