<?php
/*
 * Created by Martin Wernståhl on 2009-12-15.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * The main framework class.
 * 
 * This class handles the following:
 * 
 *  - Global error and exception handling
 *  - Output buffering
 *  - Events and filters
 *  - Class loading, with "namespace" support
 *  - Configuration fetching
 *  - Request running
 *  - Logging
 * 
 * @author Martin Wernståhl <m4rw3r at gmail dot com>
 */
final class Inject
{
	/**
	 * Constant telling the current framework core version.
	 * 
	 * @var string
	 */
	const VERSION = '0.1dev';
	
	/**
	 * Error constant for ERROR in the Inject Framework.
	 * 
	 * @var int
	 */
	const ERROR = 1;
	/**
	 * Error constant for WARNING in the Inject Framework.
	 * 
	 * @var int
	 */
	const WARNING = 2;
	/**
	 * Error constant for NOTICE in the Inject Framework.
	 * 
	 * @var int
	 */
	const NOTICE = 4;
	/**
	 * Error constant for DEBUG in the Inject Framework.
	 * 
	 * @var int
	 */
	const DEBUG = 8;
	/**
	 * Error constant for ALL in the Inject Framework.
	 * 
	 * @var int
	 */
	const ALL = 15;
	
	/**
	 * A list which is used to convert PHP error constants to Inject error constants.
	 * 
	 * @var array
	 */
	private static $error_conversion_table = array();
	
	/**
	 * Output buffer level on which Inject was started.
	 * 
	 * @var int
	 */
	private static $ob_level = 0;
	
	/**
	 * Nesting of the Inject::run() method.
	 */
	private static $run_level = 0;
	
	/**
	 * The configuration cache.
	 * 
	 * @var array
	 */
	private static $config = array();
	
	/**
	 * The load paths for the Inject framework.
	 * 
	 * @var array
	 */
	private static $paths = array();
	
	/**
	 * The framework path, to use as a system directory.
	 * 
	 * @var string
	 */
	private static $fw_path;
	
	/**
	 * A "namespacing" feature which enables the user to move a set of classes
	 * to a directory alongside the Libraries folder.
	 * 
	 * @var string
	 */
	private static $namespaces = array(
	                                  'Cli'         => 'Cli',
	                                  'Controller'  => 'Controllers',
	                                  'Inject'      => 'Inject',
	                                  'Model'       => 'Models',
	                                  'Partial'     => 'Partials'
	                                  );
	
	/**
	 * A cache of all the classes and their respective files.
	 * 
	 * @var array
	 */
	private static $loader_cache = array();
	
	/**
	 * The error level which Inject Framework should respect when receiving errors.
	 * 
	 * 15 = self::ALL
	 * 
	 * @var int
	 */
	private static $error_level = 15;
	
	/**
	 * If this framework should run in production mode.
	 * 
	 * @var bool
	 */
	private static $production = false;
	
	/**
	 * The dispatcher object used.
	 * 
	 * @var Inject_Dispatcher
	 */
	private static $dispatcher = null;
	
	/**
	 * The request which first was sent to Inject Framework during this run.
	 * 
	 * This request gets to handle error reporting.
	 * 
	 * @var Inject_Request
	 */
	private static $main_request = null;
	
	/**
	 * A list of registered loggers.
	 * 
	 * @var array
	 */
	private static $loggers = array();
	
	/**
	 * A list containing all the registered filter listeners.
	 * 
	 * @var array
	 */
	private static $filters = array();
	
	/**
	 * A list containing all registered event listeners.
	 * 
	 * @var array
	 */
	private static $events = array();
	
	final function __construct()
	{
		throw new RuntimeException('The Inject class is not allowed to be instantiated.');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Initializes the error logging
	 * 
	 * @return void
	 */
	public static function init()
	{
		self::$fw_path = dirname(__FILE__).DIRECTORY_SEPARATOR;
		
		// set the autoloader
		spl_autoload_register('Inject::load');
		
		// set error/exception handlers
		set_error_handler(array('Inject', 'errorHandler'));
		set_exception_handler(array('Inject', 'exceptionHandler'));
		
		// add handler for fatal errors
		register_shutdown_function(array('Inject', 'handleFatalError'));
		
		// create the error conversion table to be used later by the error handler
		self::$error_conversion_table = array(
				E_ERROR				=> self::ERROR,
				E_WARNING			=> self::WARNING,
				E_PARSE				=> self::ERROR,
				E_COMPILE_ERROR		=> self::ERROR,
				E_COMPILE_WARNING	=> self::WARNING,
				E_NOTICE			=> self::NOTICE,
				E_USER_ERROR		=> self::ERROR,
				E_USER_WARNING		=> self::WARNING,
				E_USER_NOTICE		=> self::NOTICE,
				E_STRICT			=> self::NOTICE,
				E_RECOVERABLE_ERROR	=> self::WARNING,
				E_ALL				=> self::ALL
			);
		
		if(defined('E_DEPRECATED'))
		{
			// We have PHP 5.3, add the new error constants
			self::$error_conversion_table[constant('E_DEPRECATED')]			= self::NOTICE;
			self::$error_conversion_table[constant('E_USER_DEPRECATED')]	= self::NOTICE;
		}
		
		// Start output buffering
		ob_start();
		
		// Save level
		self::$ob_level = ob_get_level();
		
		// Init UTF-8 support
		require self::$fw_path.'Utf8.php';
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Terminates this run of Inject, sends all the headers and also fetches the
	 * output buffer contents and runs them through the inject.output filter.
	 * 
	 * @return void
	 */
	public static function terminate()
	{
		// Send the headers
		if(isset(self::$main_request))
		{
			self::$main_request->response->sendHeaders();
		}
		
		// clear all the buffers except for the last
		while(ob_get_level() > self::$ob_level)
		{
			ob_end_flush();
		}
		
		self::event('inject.terminate');
		
		// get the contents, so we can add it to the output
		$output = ob_get_contents();
		
		// clear the last buffer
		ob_end_clean();
		
		// output the contents
		echo self::filter('inject.output', $output);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds application paths for the framework.
	 * 
	 * @param  array
	 * @return void
	 */
	public static function addPaths(array $paths)
	{
		foreach($paths as $p)
		{
			$p = realpath($p).DIRECTORY_SEPARATOR;
			
			// do not add twice
			if(in_array($p, self::$paths))
			{
				continue;
			}
			
			self::$paths[] = $p;
			
			// does the path have a configuration file
			if(file_exists($p.'Config/Inject.php'))
			{
				self::log('Inject', 'Loading framework configuration from "'.$p.'Config/Inject.php".', self::DEBUG);
				include $p.'Config/Inject.php';
			}
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Loads the requested class, primarily used as an autoloader
	 * 
	 * @param  string
	 * @param  int		The level to log the error with, default is Inject::WARNING
	 * @return bool
	 */
	public static function load($class, $error_level = false)
	{
		// Remove namespace separators which can make a file load twice
		$class = trim($class, '\\');
		
		// Check if we have a cache
		if(isset(self::$loader_cache[$class]))
		{
			require_once self::$loader_cache[$class];
			
			return true;
		}
		
		$org_class = $class;
		
		// convert namespace\class_name to namespace/class/name
		$class = strtr($class, '_\\', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
		
		// fetch the prefix (also respects namespaces)
		$prefix = ($p = strpos($class, '/')) ? substr($class, 0, $p) : '';
		
		// do not search in the libraries folder for the following class types:
		if(isset(self::$namespaces[$prefix]))
		{
			// get folder / path
			$base = self::$namespaces[$prefix];
			
			// remove the prefix, as the namespace tells us what to place instad
			$class = substr($class, $p + 1);
		}
		else
		{
			$base = 'Libraries';
		}
		
		// assemble the path
		$path = $base.DIRECTORY_SEPARATOR.$class.'.php';
		
		// find the file
		foreach(array_merge(self::$paths, array(self::$fw_path)) as $p)
		{
			if(file_exists($p . $path))
			{
				self::log('Load', 'Loading "'.$org_class.'".', self::DEBUG);
				
				// load the file
				require $p . $path;
				
				if( ! (class_exists($org_class, false) OR interface_exists($org_class, false)))
				{
					// File did not contain the requested class/interface
					continue;
				}
				else
				{
					// We're done
					return true;
				}
			}
		}
		
		// The file does not exist and it isn't a namespaced file, try to load a core file (check if it exists first)
		// 10 = length of "/Libraries"
		if( ! isset(self::$namespaces[$prefix]) && file_exists(self::$fw_path.'Inject/'.substr($path, 10)))
		{
			self::log('Load', 'Failed to load the class file, resorting to loading core file for the class "'.$org_class.'".', self::DEBUG);
			
			eval('class '.$class.' extends Inject_'.$class.'{}');
			
			return true;
		}
		
		self::log(
				'Load',
				'Failed to load "'.$path.'" for the class "'.$org_class.'".',
				$error_level ? $error_level : self::WARNING
			);
		
		return false;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the error constant for which errors should be displayed.
	 * 
	 * @param  int
	 * @return void
	 */
	public static function setErrorLevel($error_level)
	{
		self::$error_level = $error_level;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the production switch.
	 * 
	 * @param  bool
	 * @return void
	 */
	public static function setIsProduction($value)
	{
		self::$production = (bool) $value;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the cache array to use when loading files.
	 * 
	 * @param  array
	 * @return void
	 */
	public static function setLoaderCache(array $list)
	{
		self::$loader_cache = $list;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the folder for the files beginning on $prefix, so they are stored in
	 * $folder alongside the library folder in the app folder (or system folder).
	 * 
	 * Example:
	 * <code>
	 * Inject::setNamespace('Foobar', 'Foobars');
	 * // Foobar_Baz now resides in app/Foobars/Baz.php instead of app/Libraries/Foobar/Baz.php.
	 * </code>
	 * 
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public static function setNamespace($prefix, $folder)
	{
		self::$namespaces[$prefix] = $folder;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the dispatcher object.
	 * 
	 * @param  Inject_Dispatcher|objcet
	 */
	public static function setDispatcher($disp)
	{
		self::$dispatcher = $disp;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Stores a configuration.
	 * 
	 * @param  string
	 * @param  array
	 * @return void
	 */
	public static function setConfiguration($name, array $value)
	{
		self::$config[$key] = $value;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the framework path.
	 * 
	 * @return string
	 */
	public static function getFrameworkPath()
	{
		return self::$fw_path;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns an array of all the application paths.
	 * 
	 * @return string
	 */
	public static function getApplicationPaths()
	{
		return self::$paths;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the namespace mappings.
	 * 
	 * @return array
	 */
	public static function getNamespaceMappings()
	{
		return self::$namespaces;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the production flag, true if the site is in production mode.
	 * 
	 * @return bool
	 */
	public function getIsProduction()
	{
		return self::$production;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a configuration for a certain name, loads the configuration if not present.
	 * 
	 * Searches all the registered paths for configuration files with the name $name.php.
	 * 
	 * Configuration format:
	 * <code>
	 * <?php
	 * // do some stuff here, usually just create an array like this:
	 * 
	 * $config = array(
	 *     'cache_path' => '/some/path',
	 *     'use_cache' => true
	 * );
	 * 
	 * // then return the resulting config:
	 * return $config;
	 * ?>
	 * </code>
	 * 
	 * 
	 * @param  string
	 * @param  mixed
	 * @return array|false
	 */
	public static function getConfiguration($name, $default = false)
	{
		if(isset(self::$config[$name]))
		{
			return self::$config[$name];
		}
		
		$c = array();
		
		foreach(self::$paths as $p)
		{
			if(file_exists($p.'Config/'.$name.'.php'))
			{
				// include file and merge it
				$c = array_merge(include $p.'Config/'.$name.'.php', $c);
			}
		}
		
		return self::$config[$name] = empty($c) ? $default : $c;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Runs a request by the Inject Framework.
	 * 
	 * @param  Inject_Request
	 * @param  bool				Only applies on nested calls
	 * @return string
	 */
	public static function run(Inject_Request $request)
	{
		self::$run_level++;
		
		self::log('Inject', 'run()['.self::$run_level.']', self::DEBUG);
		
		if(self::$run_level == 1)
		{
			// first request, set the request object as error handler
			self::$main_request = $request;
		}
		
		$protocol = $request->getProtocol();
		
		self::$dispatcher->$protocol($request);
		
		self::log('Inject', 'run()['.self::$run_level.'] - DONE', self::DEBUG);
		
		self::$run_level--;
		
		return $request->response;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the current topmost request.
	 * 
	 * @return Inject_Request
	 */
	public static function getMainRequest()
	{
		return self::$main_request;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Triggers an event.
	 * 
	 * @param  string
	 * @param  array
	 * @return void
	 */
	public static function event($name, $params = array())
	{
		if( ! empty(self::$events[$name]))
		{
			foreach(self::$events[$name] as $call)
			{
				call_user_func_array($call, $params);
			}
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets a callable which will be triggered on the specified event.
	 * 
	 * @param  string
	 * @param  callable
	 * @return void
	 */
	public static function onEvent($name, $callable)
	{
		self::$events[$name][] = $callable;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Filters the string through the callables associated with the filter name.
	 * 
	 * @param  string
	 * @param  string
	 * @param  array
	 * @return string
	 */
	public static function filter($name, $string, $params = array())
	{
		if( ! empty(self::$filters[$name]))
		{
			foreach(self::$filters[$name] as $filter)
			{
				// make the filter string be the first
				$args = array_merge(array($string), $params);
				
				if($res = call_user_func_array($filter, $args))
				{
					// not evalated to false, assume that it is the new string
					$string = $res;
				}
			}
		}
		
		return $string;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds a callable to filter a certain string.
	 * 
	 * @param  string
	 * @param  callable
	 * @return void
	 */
	public static function addFilter($name, $callable)
	{
		self::$filters[$name][] = $callable;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * The exception handler, disassembles the exception and calls handle_error().
	 * 
	 * @param  Exception
	 * @return void
	 */
	public static function exceptionHandler($e)
	{
		self::handleError(E_ERROR, get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace());
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
	public static function errorHandler($error_code, $message = '', $file = '', $line = 0)
	{
		self::handleError($error_code, 'PHP Error', $message, $file, $line, array_slice(debug_backtrace(), 1));
	}
	
	// ------------------------------------------------------------------------

	/**
	 * A function which is registered as a shutdown function, it catches all fatal errors and logs them.
	 * 
	 * @return void
	 */
	public static function handleFatalError()
	{
		if(is_null($e = error_get_last()) === false &&
			$e['type'] & (E_ERROR | E_COMPILE_ERROR | E_CORE_ERROR | E_PARSE | E_USER_ERROR)) 
		{
			// We've got a fatal error
			self::handleError($e['type'], 'PHP Error', $e['message'], $e['file'], $e['line'], false);
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
	 * @param  array|false		False if this is a fatal error and PHP is shutting down
	 * @return void
	 */
	public static function handleError($level, $type, $message, $file, $line, $trace = array())
	{
		// Convert the error level
		$level = isset(self::$error_conversion_table[$level]) ? self::$error_conversion_table[$level] : self::ERROR;
		
		// log error first
		self::log($type, $message . ' in file "'.$file.'" on line "'.$line.'".', $level);
		
		if(self::$error_level & $level && ! self::$production)
		{
			if(self::$main_request)
			{
				self::$main_request->showError($level, $type, $message, $file, $line, $trace);
			}
			else
			{
				// Default renderer
				echo '
An error has occurred: '.$type.':

'.$message.'

in file "'.$file.'" at line '.$line.'

Trace:
';

				print_r($trace);
			}
		}
		elseif(self::$production && self::$error_level & $level)
		{
			// clear the output buffers, to avoid displaying page fragments
			// before the 500 error
			while(ob_get_level())
			{
				ob_end_clean();
			}
			
			header('HTTP/1.1 500');
			
			// add the output handler again, to add compression and the like
			ob_start('Inject::parse_output');
			
			if(self::$main_request)
			{
				self::$main_request->showError500($level, $type, $message, $file, $line, $trace);
			}
			else
			{
				// Default renderer
				echo '
! A Fatal Error occurred !
==========================
';
			}
		}
		else
		{
			// we did not output an error page, do not quit, just log it
			return;
		}
		
		// send the output to the browser
		while(ob_get_level())
		{
			ob_end_flush();
		}
		
		if($trace !== false)
		{
			// Not a fatal error, quit so we let the other shutdown functions run
			flush();
			exit((Int) $level);
		}
		
		// Already in an exit process, do not call exit yet (if we call exit now,
		// no other shutdown functions will run)
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Logs a certain message.
	 * 
	 * @param  string
	 * @param  string
	 * @param  int		Inject error constant
	 * @return void
	 */
	public static function log($namespace, $message, $level = false)
	{
		$level = $level ? $level : self::WARNING;
		
		foreach(self::$loggers as $pair)
		{
			list($log_level, $logger) = $pair;
			
			if($level & $log_level)
			{
				$logger->addMessage($namespace, $message, $level);
			}
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Attaches a logging object, which will receive the log messages.
	 * 
	 * @return void
	 */
	public static function attachLogger(Inject_LoggerInterface $log_obj, $level = false)
	{
		$level OR $level = self::ALL;
		
		self::$loggers[] = array($level, $log_obj);
	}
}


/* End of file Inject.php */
/* Location: ./lib */