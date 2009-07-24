<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * The path to the Inject Framework directory.
 */
define('INJECT_FRAMEWORK_PATH', dirname(__FILE__));

/**
 * The file extension of the Inject Framework.
 */
define('INJECT_FRAMEWORK_EXT', '.' . pathinfo(__FILE__, PATHINFO_EXTENSION));

/**
 * Fetch the utf8 extension.
 */
require INJECT_FRAMEWORK_PATH . '/utf8' . INJECT_FRAMEWORK_EXT;

/**
 * The main framework class.
 */
abstract class Inject
{
	const ERROR = 1;
	const WARNING = 2;
	const NOTICE = 4;
	const DEBUG = 8;
	
	/**
	 * The configuration.
	 * 
	 * @var array
	 */
	protected static $config = array();
	
	/**
	 * The load paths for the Inject framework.
	 * 
	 * @var array
	 */
	protected static $paths = array();
	
	/**
	 * The application paths.
	 * 
	 * @var array
	 */
	protected static $application = array();
	
	/**
	 * The namespaces for the loader and their respective paths.
	 * 
	 * @var array
	 */
	protected static $namespaces = array
		(
			'inject'		=> 'inject',
			'controller'	=> 'controller',
			'model'			=> 'model',
			'helper'		=> 'helper',
			'record'		=> 'record'
		);
	
	/**
	 * The classes/callables used when instantiating objects for the registry.
	 * 
	 * @var array
	 */
	protected static $classes = array
		(
			'debug'			=> 'Inject_Debug',
			'dispatcher'	=> 'Inject_Dispatcher'
		);
	
	/**
	 * Custom paths for certain classes.
	 * 
	 * @var array
	 */
	protected static $class_paths = array();
	
	/**
	 * The error level which Inject Framework should respect when receiving errors.
	 * 
	 * @var int
	 */
	protected static $error_level = E_ALL;
	
	/**
	 * The error level which Inject Framework will send HTTP 500 errors.
	 * 
	 * This is triggered if $error_level doesn't cover the error,
	 * as it usually is in a production environment ($error_level = 0).
	 * 
	 * @var int
	 */
	protected static $error_level_500 = E_ALL;
	
	/**
	 * The error level which Inject Frameworks should log.
	 * 
	 * @var int
	 */
	protected static $error_level_log = E_ALL;
	
	/**
	 * The main request type, to be able to determine what error messages to show.
	 * 
	 * @var string
	 */
	protected static $request_type = 'generic';
	
	/**
	 * The request which first was sent to Inject Franework during this run.
	 * 
	 * @var Inject_Request
	 */
	public static $main_request = null;
	
	/**
	 * The output buffering level before the first run of Inject::run() was made.
	 * 
	 * @var int
	 */
	protected static $ob_level = 0;
	
	/**
	 * A list of log events.
	 * 
	 * @var array
	 */
	protected static $log_events = array();
	
	/**
	 * A list of registered loggers.
	 * 
	 * @var array
	 */
	protected static $loggers = array();
	
	// ------------------------------------------------------------------------

	/**
	 * Loads a configuration file.
	 * 
	 * @param  string
	 * @return void
	 */
	public static function set_config_file($path)
	{
		if( ! empty($config))
		{
			// do not allow loading a configuration again
			throw new Inject_Exception_ConfigAlreadyLoaded();
		}
		
		// get the file data
		self::$config = require $path;
		
		// define the application cascade
		if( ! empty(self::$config['inject.application']))
		{
			self::$application = (Array) self::$config['inject.application'];
		}
		else
		{
			self::$application = array('./application');
		}
		
		// set paths
		self::$paths = self::$application;
		self::$paths[] = INJECT_FRAMEWORK_PATH;
		
		// get the dependency injected classes
		if( ! empty(self::$config['inject.classes']))
		{
			self::$classes = array_merge( (Array) self::$config['inject.classes'], self::$classes);
		}
		
		// get dependency injected class paths, to be able to replace a class yet let it be used with the same name
		if( ! empty(self::$config['inject.class_paths']))
		{
			self::$class_paths = array_merge( (Array) self::$config['inject.class_paths'], self::$class_paths);
		}
		
		// namespaces which reroutes include paths for certain prefixes
		if( ! empty(self::$config['inject.namespaces']))
		{
			self::$namespaces = array_merge( (Array) self::$config['inject.namespaces'], self::$namespaces);
		}
		
		// error levels for logging and displaying errors
		self::$error_level = isset(self::$config['inject.error_level']) ? self::$config['inject.error_level'] : E_ALL;
		self::$error_level_500 = isset(self::$config['inject.error_level_500']) ? self::$config['inject.error_level_500'] : E_ERROR;
		self::$error_level_log = isset(self::$config['inject.error_level_log']) ? self::$config['inject.error_level_log'] : E_ALL;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets a certain configuration value, or merges in a configuration array.
	 * 
	 * @param  string|array
	 * @param  mixed
	 * @return void
	 */
	public static function set_config($key, $value = null)
	{
		if(is_array($key))
		{
			self::$config = array_merge(self::$config, $key);
		}
		else
		{
			self::$config[$key] = $value;
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Initializes the Inject Framework: registers autoloader and error handlers, also adds output buffering.
	 * 
	 * @return void
	 */
	public static function init()
	{
		// set the autoloader
		spl_autoload_register('Inject::load');
		
		// set error/exception handlers
		set_error_handler(array('Inject', 'error_handler'));
		set_exception_handler(array('Inject', 'exception_handler'));
		
		// add handler for fatal errors
		register_shutdown_function(array('Inject', 'handle_fatal_error'));
		
		// get buffer level, so we don't end any enclosing buffers
		self::$ob_level = ob_get_level();
		
		// start output buffering, and send it through the output handler
		ob_start('Inject::parse_output');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets a custom path for a certain class.
	 * 
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public static function set_class_path($class, $path)
	{
		$class = strtolower($class);
		
		// check if the class exists, and if it doesn't do NOT load it
		if(class_exists($class, false))
		{
			throw new Inject_Exception_ClassLoaded($class);
		}
		
		self::$class_paths[$class] = $path;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Runs the framework for a request.
	 * 
	 * @return void
	 */
	public static function run(Inject_Request $request)
	{
		static $calls = 0;
		
		$first_end = false;
		
		// get the first request type to be able to produce proper error messages
		if( ! $calls)
		{
			// save the main request , so we know which error files to load and other things
			self::$main_request = $request;
			self::$request_type = $request->get_type();
			
			// define that we're the first one and that we need it to end the buffering
			$first_end = true;
		}
		
		// increase the call counter, to be able to debug for HMVC
		// and to avoid starting buffering again
		$calls++;
		
		// save debug information, also save the call number for "exit message"
		$real_calls = $calls;
		self::log('inject', 'run()[' . $real_calls . '] - Request Type: ' . $request->get_type(), self::DEBUG);
		
		// create a dispatcher
		$disp = self::create('Dispatcher');
		
		// call it with the desired request type
		$type = $request->get_type();
		$disp->$type($request);
		
		self::log('inject', 'run()[' . $real_calls . '] - DONE', self::DEBUG);
		
		// should we end buffering?
		if($first_end)
		{
			// output the contents
			echo self::$main_request->get_response()->output_content();
			
			// clear all the buffers and finally let Inject Framework parse the result
			while(ob_get_level() > self::$ob_level)
			{
				ob_end_flush();
			}
			
			// let the loggers write, here is their chance to do shutdown before __destruct()
			self::terminate_loggers();
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Loads the requested class, primarily used as an autoloader
	 * 
	 * @return bool
	 */
	public static function load($class)
	{
		// normalize
		$file = strtolower($class);
		
		// fetch the prefix:
		$prefix = ($p = strpos($file, '_')) ? substr($file, 0, $p) : '';
		
		if(isset(self::$class_paths[$file]))
		{
			// we have an injected path, load it instead
			$path = DIRECTORY_SEPARATOR . self::$class_paths[$file];
		}
		else
		{
			// create the path using the default algorithm
			
			// do not search in the libraries folder for the following class types:
			if(isset(self::$namespaces[$prefix]))
			{
				// get folder / path
				$base = self::$namespaces[$prefix];
				
				// remove the prefix, as the namespace tells us what to place instad
				$file = substr($file, $p + 1);
			}
			else
			{
				$base = 'libraries';
			}
			
			// assemble the path, and convert the class_name to class/name.php
			$path = DIRECTORY_SEPARATOR . $base . DIRECTORY_SEPARATOR . strtr($file, '_\\', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR) . INJECT_FRAMEWORK_EXT;
		}
		
		// find the file
		foreach(self::$paths as $p)
		{
			if(file_exists($p . $path))
			{
				// load the file
				require $p . $path;
				
				// we're done
				return true;
			}
		}
		
		// TODO: What are the implications of doing this?
		
		// the file does not exist and it isn't a namespaced file, try to load a core file (check if it exists first)
		if( ! isset(self::$namespaces[$prefix]) && file_exists(end(self::$paths) . DIRECTORY_SEPARATOR . 'inject' . substr($path, 10)))
		{
			self::log('inject', 'load(): Failed to load the class file, resorting to loading core file for the class "'.$class.'".', self::NOTICE);
			
			eval('class '.$class.' extends Inject_'.$class.'{}');
			
			return true;
		}
		
		self::log('inject', 'load(): Failed to load "'.$path.'" for the class "'.$class.'".', self::DEBUG);
		
		return false;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the search paths for framework files.
	 * 
	 * @return array
	 */
	public static function get_paths()
	{
		return self::$paths;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets a callable, class name or object to use when create() is called.
	 * 
	 * If an object is registered, it will behave like it is a singleton,
	 * because all calls to Inject::create('foo') will return the same instance.
	 * 
	 * @param  string
	 * @param  string|object
	 * @return void
	 */
	public static function set_class($class, $instance)
	{
		$class_key = strtolower($class);
		
		self::$classes[$class_key] = $instance;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a new instance of the specified component.
	 * 
	 * @param  string
	 * @param  array
	 * @return object
	 */
	public static function create($class, $parameters = array())
	{
		$class_key = strtolower($class);
		
		// do we have a dependency injection?
		if(isset(self::$classes[$class_key]))
		{
			$c = self::$classes[$class_key];
			
			// do we have a callable, an instance or a class name?
			if(is_callable($c))
			{
				return call_user_func($c, $parameters);
			}
			elseif(is_object($c))
			{
				// already created object ("singleton")
				return $c;
			}
			else
			{
				// remapped class name
				$class = $c;
			}
		}
		
		return new $class($parameters);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the configuration option from the loaded configuration.
	 * 
	 * @param  string
	 * @param  mixed
	 * @return mixed
	 */
	public static function config($name, $default = null)
	{
		return isset(self::$config[$name]) ? self::$config[$name] : $default;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Produces a localized string of a certain phrase.
	 * 
	 * @param  string
	 * @param  string
	 * @param  array
	 * @return string
	 */
	public static function t($namespace, $phrase, $variables = array())
	{
		# code...
	}
	
	// ------------------------------------------------------------------------

	/**
	 * The method which handles the output of Inject Framework, used with ob_start().
	 * 
	 * @param  string
	 * @return string
	 */
	public static function parse_output($string)
	{
		// TODO: Add hooks and gzip compression
		return $string;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Outputs debug information about the current request.
	 * 
	 * @param  bool
	 * @return string|void
	 */
	public static function debug($direct_output = false)
	{
		foreach(self::$log_events as $event)
		{
			echo $event['level'] . ' - ' . $event['namespace'] . ': ' . $event['message'] . '<br />';
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * The exception handler, disassembles the exception and calls handle_error().
	 * 
	 * @param  Exception
	 * @return void
	 */
	public static function exception_handler($e)
	{
		self::handle_error(E_ERROR, get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace());
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Calls handle_error() to let it handle displaying and logging of the error.
	 * 
	 * @param  int
	 * @param  string
	 * @param  string
	 * @param  int
	 * @return void
	 */
	public static function error_handler($error_code, $message = '', $file = '', $line = 0)
	{
		self::handle_error($error_code, 'PHP Error', $message, $file, $line, debug_backtrace());
	}
	
	// ------------------------------------------------------------------------

	/**
	 * A function which is registered as a shutdown function, it catches all fatal errors and logs them.
	 * 
	 * @return void
	 */
	public static function handle_fatal_error()
	{
		if(is_null($e = error_get_last()) === false &&
			$e['type'] & (E_ERROR | E_COMPILE_ERROR | E_CORE_ERROR | E_PARSE | E_USER_ERROR)) 
		{
			// We've got a fatal error
			self::handle_error($e['type'], 'PHP Error', $e['message'], $e['file'], $e['line'], false);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * The error handler, it prints the errors and sends them to logging.
	 * 
	 * @param  int
	 * @param  string
	 * @param  string
	 * @param  int
	 * @param  array
	 * @return void
	 */
	public static function handle_error($level, $type, $message, $file, $line, $trace = array())
	{
		if(self::$error_level & $level)
		{
			// We have an error to display
			foreach(self::$paths as $p)
			{
				if(file_exists($p . '/error/'.self::$request_type.'_general' . INJECT_FRAMEWORK_EXT))
				{
					include $p . '/error/'.self::$request_type.'_general' . INJECT_FRAMEWORK_EXT;
					break;
				}
			}
		}
		elseif(self::$error_level_500 & $level)
		{
			// clear the output buffers, to avoid displaying page fragments
			// before the 500 error
			while(ob_get_level())
			{
				ob_end_clean();
			}
			
			// add the output handler again, to add compression and the like
			ob_start('Inject::parse_output');
			
			// We have a fatal error, and we shouldn't display that one,
			// make a http 500 error
			foreach(self::$paths as $p)
			{
				if(file_exists($p . '/error/'.self::$request_type.'_500' . INJECT_FRAMEWORK_EXT))
				{
					include $p . '/error/'.self::$request_type.'_500' . INJECT_FRAMEWORK_EXT;
					break;
				}
			}
		}
		
		if(self::$error_level_log & $level)
		{
			// TODO: Modify the error level to an Inject Framework error constant
			self::log($type, $message . ' in file "'.$file.'" on line "'.$line.'".', self::ERROR);
		}
		
		self::terminate_loggers();
		
		// send the output to the browser
		while(ob_get_level())
		{
			ob_end_flush();
		}
		
		flush();
		exit;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Logs a certain message.
	 * 
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public static function log($namespace, $message, $level = false)
	{
		$level = $level ? $level : self::WARNING;
		
		// save it to the local event list:
		self::$log_events[] = array('namespace' => $namespace, 'message' => $message, 'level' => $level);
		
		foreach(self::$loggers as $pair)
		{
			list($log_level, $logger) = $pair;
			
			if($level <= $log_level)
			{
				$logger->add_message($namespace, $message, $level);
			}
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Attaches a logging object, which will receive the log messages.
	 * 
	 * @return void
	 */
	public static function attach_logger(Inject_logger $log_obj, $level = false)
	{
		$level OR $level = self::DEBUG;
		
		self::$loggers[] = array($level, $log_obj);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Calls the shutdown() method of all the attached loggers.
	 * 
	 * @return void
	 */
	protected static function terminate_loggers()
	{
		foreach(self::$loggers as $pair)
		{
			list(,$logger) = $pair;
			
			$logger->shutdown();
		}
		
		self::$loggers = array();
	}
}


/* End of file inject.php */
/* Location: . */