<?php
/*
 * Created by Martin Wernståhl on 2009-12-27.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_URI
{
	/**
	 * Contains a cache of the current URI for the current request.
	 * 
	 * @var string
	 */
	protected static $current_uri = null;
	
	/**
	 * Contains the URI to the front controller.
	 * 
	 * @var string
	 */
	protected static $front_controller = null;
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the current URI which is for the current request.
	 * 
	 * @return string
	 */
	public static function getCurrentURI()
	{
		if(is_null(self::$current_uri))
		{
			self::parseURI();
		}
		
		return self::$current_uri;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the front controller URI, eg. /index.php.
	 * 
	 * @return string
	 */
	public static function getFrontController()
	{
		if(is_null(self::$front_controller))
		{
			self::parseURI();
		}
		
		return self::$front_controller;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	protected static function parseURI()
	{
		$source = 'None';
		
		if(isset($_GET['inject_uri']))
		{
			// Use the URI defined in the query string
			$current_uri = $_GET['inject_uri'];
			
			// Remove the URI from $_GET
			unset($_GET['inject_uri']);
			
			// Remove the URI from $_SERVER['QUERY_STRING']
			$_SERVER['QUERY_STRING'] = preg_replace('~\binject_uri\b[^&]*+&?~', '', $_SERVER['QUERY_STRING']);
			
			if($p = strpos($_SERVER['REQUEST_URI'], '?') !== false)
			{
				/*
				 * remove the query string from the REQUEST URI to create the front controller path
				 * add ?inject_uri= to create the final front controller.
				 */
				self::$front_controller = substr($_SERVER['REQUEST_URI'], 0, $p).'?inject_uri=';
			}
			
			$source = 'Query String';
		}
		elseif(isset($_SERVER['PATH_INFO']) AND $_SERVER['PATH_INFO'])
		{
			$current_uri = $_SERVER['PATH_INFO'];
			
			$source = 'Path Info';
		}
		elseif(isset($_SERVER['ORIG_PATH_INFO']) AND $_SERVER['ORIG_PATH_INFO'])
		{
			$current_uri = $_SERVER['ORIG_PATH_INFO'];
			
			$source = 'Orig Path Info';
		}
		elseif(isset($_SERVER['PHP_SELF']) AND $_SERVER['PHP_SELF'])
		{
			$current_uri = $_SERVER['PHP_SELF'];
			
			$source = 'PHP_SELF';
		}
		
		
		// remove the current script name if there is one
		if(isset($_SERVER['PHP_SELF']) && strpos($current_uri, $_SERVER['PHP_SELF']) === 0)
		{
			// Remove the front controller from the current uri
			$current_uri = (string) substr($current_uri, strlen($_SERVER['PHP_SELF']));
			
			// the PHP_SELF variable is the front controller
			self::$front_controller = $_SERVER['PHP_SELF'];
		}
		// do we have to deduce the front_controller?
		elseif(is_null(self::$front_controller))
		{
			if(isset($_SERVER['REQUEST_URI']))
			{
				// Remove the found uri from the REQUEST URI to create the front controller path.
				self::$front_controller = ($p = strpos($_SERVER['REQUEST_URI'], $current_uri)) !== false ? substr($_SERVER['REQUEST_URI'], 0, $p) : $_SERVER['REQUEST_URI'];
			}
		}
		
		// normalize front controller
		self::$front_controller = '/' . trim(self::$front_controller, '/');
		
		// Remove slashes from the start and end of the URI
		$current_uri = trim($current_uri, '/');
		
		if($current_uri !== '')
		{
			// Reduce multiple slashes into single slashes
			$current_uri = preg_replace('#//+#', '/', $current_uri);
		}
		
		Inject::log('URI', 'Found URI from source: "'.$source.'".', Inject::DEBUG);
		
		self::$current_uri = urldecode($current_uri);
	}
}


/* End of file URI.php */
/* Location: ./lib/Inject */