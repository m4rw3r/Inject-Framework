<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject;

/**
 * Basic autoloader for the Inject Framework, allows a base path for generic
 * classes and specific paths for different primary namespaces.
 * 
 * Adds Inject as a default package
 */
class Autoloader
{
	/**
	 * A list of namespaces and their respective directories.
	 * 
	 * @var array(string => string)
	 */
	protected $packages = array();
	
	/**
	 * The path to look for classes which aren't in a defined package.
	 * 
	 * @var string
	 */
	protected $base_path;
	
	/**
	 * Creates a new autoloader which loads the classes for the supplied packages
	 * in addition to the Inject package.
	 * 
	 * @param  array   array(package_name => package_path)
	 */
	function __construct($base_path, array $packages = array())
	{
		$this->base_path = realpath($base_path);
		$this->packages = $packages;
		
		// Register the inject namespace:
		$this->packages['Inject'] = __DIR__;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Registers a package (the first segment of the namespace) with a path.
	 * 
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function registerPackage($package, $path)
	{
		$this->packages[$package] = $path;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Loads the file containing $class.
	 * 
	 * @param  string
	 * @return boolean
	 */
	public function load($class)
	{
		$ns   = explode('\\', trim($class, '\\'));
		$ns[] = strtr(array_pop($ns), '_', DIRECTORY_SEPARATOR).'.php';
		
		if(isset($ns[0]) && isset($this->packages[$ns[0]]))
		{
			$p = array_shift($ns);
			$file = $this->packages[$p].DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $ns);
			
			if(file_exists($file))
			{
				require $file;
			}
		}
		else
		{
			$file = $this->base_path.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $ns);
			
			if(file_exists($file))
			{
				require $file;
			}
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Registers this autoloader with PHP.
	 * 
	 * @return void
	 */
	public function register()
	{
		spl_autoload_register(array($this, 'load'), true);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Unregisters this autoloader with PHP.
	 * 
	 * @return void
	 */
	public function unregister()
	{
		spl_autoload_unregister(array($this, 'load'));
	}
}


/* End of file Autoloader.php */
/* Location: lib/Inject/ */