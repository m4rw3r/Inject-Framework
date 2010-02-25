<?php
/*
 * Created by Martin Wernståhl on 2010-02-25.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

interface Inject_ApplicationInterface
{
	/**
	 * Returns a boolean telling if this application is in production mode.
	 * 
	 * If production mode is enabled then Inject Framework will forgo
	 * update checks on some caches and also skip a few checks which
	 * only are made during development.
	 * 
	 * It will also adjust if the fatal error message should contain the
	 * error message or not (on production, errors are not shown to the
	 * user).
	 * 
	 * @return bool
	 */
	public function isProduction();
	
	/**
	 * Returns a list of namespace mappings, "first segment of classname" => "folder",
	 * the folder is located in the application paths or in the framework path.
	 * 
	 * @return array
	 */
	public function getNamespaceMappings();
	
	/**
	 * Returns the path to the cache folder, TRAILING SLASH.
	 * 
	 * @return string
	 */
	public function getCacheFolder();
	
	/**
	 * Returns a list of paths which will be searched for application components,
	 * same structure as the lib dir, TRAILILING SLASH.
	 * 
	 * @return array
	 */
	public function getPaths();
	
	/**
	 * Configures Inject Framework appropriately, run for each page load.
	 * 
	 * @return void
	 */
	public function configure();
	
	/**
	 * Returns the object which is to act like the dispatcher.
	 * 
	 * @return object
	 */
	public function getDispatcher();
	
	/**
	 * Returns the list of configuration options for a configuration name,
	 * false or empty array if no configuration options can be found.
	 * 
	 * @param  string
	 * @return array|false
	 */
	public function getConfiguration($name);
	
	/**
	 * Returns an array of files containing PHP code which tells the URI
	 * request object how to route the requests.
	 * 
	 * @return array
	 */
	public function getUriRouteFiles();
}

/* End of file ApplicationInterface.php */
/* Location: ./lib/Inject */