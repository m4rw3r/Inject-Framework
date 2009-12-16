<?php
/*
 * Created by Martin Wernståhl on 2009-12-15.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject
{
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
	 * The configuration.
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
	 * to a directory alongside the libraries folder.
	 * 
	 * @var string
	 */
	private static $namespaces = array(
									'Inject' => 'Inject'
									);
	
	/**
	 * The error level which Inject Framework should respect when receiving errors.
	 * 
	 * @var int
	 */
	private static $error_level = E_ALL;
	
	/**
	 * The error level which Inject Framework will send HTTP 500 errors.
	 * 
	 * This is triggered if $error_level doesn't cover the error,
	 * as it usually is in a production environment ($error_level = 0).
	 * 
	 * @var int
	 */
	private static $error_level_500 = E_ALL;
	
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
	
	final function __construct()
	{
		throw new RuntimeException('The Inject class is not allowed to be instantiated.');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds an additional path to search for files in (ie. application path).
	 * 
	 * Added at the end of the search-list.
	 * 
	 * @param  string
	 * @return void
	 */
	public function addPath($path)
	{
		self::$paths[] = $path;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Stores a configuration.
	 * 
	 * @param  string|array
	 * @param  mixed
	 * @return void
	 */
	public static function setConfig($key, $value = null)
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
	 * Returns the configuration value stored in the supplied key, null if not present.
	 * 
	 * @param  string
	 * @return mixed|null
	 */
	public function getConfig($key)
	{
		return isset(self::$config[$key]) ? self::$config[$key] : null;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Initializes the error logging
	 * 
	 * @return 
	 */
	public function init()
	{
		self::$fw_path = dirname(__FILE__);
		
		// set the autoloader
		spl_autoload_register('Inject::load');
		
		// set error/exception handlers
		set_error_handler(array('Inject', 'errorHandler'));
		set_exception_handler(array('Inject', 'exceptionHandler'));
		
		// add handler for fatal errors
		register_shutdown_function(array('Inject', 'handleFatalError'));
		
		// create the error conversion table to be used later
		self::$error_conversion_table = array(
				E_ERROR		=> self::ERROR,
				E_WARNING	=> self::WARNING,
				E_NOTICE	=> self::NOTICE,
				E_ALL		=> self::ALL
			);
		
		// Init UTF-8 support
		require self::$fw_path.'/utf8.php';
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the dispatcher object.
	 * 
	 * @param  Inject_Dispatcher
	 */
	public function setDispatcher($disp)
	{
		self::$dispatcher = $disp;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Runs a request by the Inject Framework.
	 * 
	 * @param  Inject_Request
	 * @param  bool				Only applies on nested calls
	 * @return void
	 */
	public function run(Inject_Request $req, $return = false)
	{
		self::$run_level++;
		
		self::log('inject', 'run()['.$run_level.']', self::DEBUG);
		
		if(self::$run_level == 1)
		{
			ob_start();
			
			self::$ob_level = ob_get_level();
			
			// first request, set the request object as error handler
			self::$main_request = $req;
		}
		elseif($return)
		{
			ob_start();
		}
		
		$type = $req->getType();
		
		self::$dispatcher->$type($request);
		
		self::log('inject', 'run()[' . $run_level . '] - DONE', self::DEBUG);
		
		self::$run_level--;
		
		if( ! self::$run_level)
		{
			// clear all the buffers except for the last
			while(ob_get_level() > self::$ob_level)
			{
				ob_end_flush();
			}
			
			// get the contents, so we can add it to the output
			$output = ob_get_contents();
			
			// clear the last buffer
			ob_end_clean();
			
			// output the contents
			echo self::filter('inject.output', self::$main_request->get_response()->output_content() . $output);
		}
		elseif($return)
		{
			$c = ob_get_contents();
			
			ob_end_clean();
			
			return $c;
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
		// fetch the prefix:
		$prefix = ($p = strpos($class, '_')) ? substr($class, 0, $p) : '';
		
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
			$base = 'libraries';
		}
		
		// assemble the path, and convert the class_name to class/name.php
		$path = DIRECTORY_SEPARATOR . $base . DIRECTORY_SEPARATOR . strtr($class, '_\\', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR) . '.php';
		
		// find the file
		foreach(array_merge(self::$paths, array(self::$fw_path)) as $p)
		{
			if(file_exists($p . $path))
			{
				// load the file
				require $p . $path;
				
				// we're done
				return true;
			}
		}
		
		// the file does not exist and it isn't a namespaced file, try to load a core file (check if it exists first)
		// 10 = length of "/libraries"
		if( ! isset(self::$namespaces[$prefix]) && file_exists(self::$fw_path . DIRECTORY_SEPARATOR . 'Inject' . substr($path, 10)))
		{
			self::log('inject', 'load(): Failed to load the class file, resorting to loading core file for the class "'.$class.'".', sefl::DEBUG);
			
			eval('class '.$class.' extends Inject_'.$class.'{}');
			
			return true;
		}
		
		self::log('inject', 'load(): Failed to load "'.$path.'" for the class "'.$class.'".', self::WARNING);
		
		return false;
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
		self::handleError($error_code, 'PHP Error', $message, $file, $line, debug_backtrace());
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
	 * @param  array
	 * @return void
	 */
	public static function handleError($level, $type, $message, $file, $line, $trace = array())
	{
		// Convert the error level
		$level = isset(self::$error_conversion_table[$level]) ? self::$error_conversion_table[$level] : self::ERROR;
		
		// log error first
		self::log($type, $message . ' in file "'.$file.'" on line "'.$line.'".', $level);
		
		if(self::$error_level & $level)
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
		
		// quit
		flush();
		exit((Int) $level);
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