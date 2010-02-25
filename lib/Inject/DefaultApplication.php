<?php
/*
 * Created by Martin Wernståhl on 2010-02-25.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Default application settings, used as a template for new applications.
 */
abstract class Inject_DefaultApplication implements Inject_ApplicationInterface
{
	/**
	 * Default namespace mappings.
	 * 
	 * <code>
	 * 'Cli'         => 'Cli'
	 * 'Controller'  => 'Controllers'
	 * 'Model'       => 'Models'
	 * 'Partial'     => 'Partials'
	 * </code>
	 */
	public function getNamespaceMappings()
	{
		return array(
			'Cli'         => 'Cli',
			'Controller'  => 'Controllers',
			'Model'       => 'Models',
			'Partial'     => 'Partials'
		);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Default application path: app.
	 * 
	 * @return array
	 */
	public function getPaths()
	{
		return array('./app/');
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Default folder is in the application folder's Cache folder.
	 * 
	 * @return string
	 */
	public function getCacheFolder()
	{
		return './app/Cache/';
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Creates the default router which is configured with Controller_Welcome->actionIndex
	 * as default controller->action pair, Controller_Welcome->error is default 404 pair.
	 * 
	 * @return Inject_Dispatcher
	 */
	public function getDispatcher()
	{
		$d = new Inject_Dispatcher();
		
		$d->setDefaultHandler('Controller_Welcome', 'actionIndex');
		
		$d->set404Handler('Controller_Welcome', 'error');
		
		return $d;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Default configuration fetcher which searches all application paths
	 * for files called Config/$name.php, merges all hits.
	 * 
	 * @param  string
	 * @return array
	 */
	public function getConfiguration($name)
	{
		$c = array();
		
		// Search all the paths
		foreach($this->getPaths() as $p)
		{
			if(file_exists($p.'Config/'.$name.'.php'))
			{
				// include file and merge it
				$c = array_merge(include $p.'Config/'.$name.'.php', $c);
			}
		}
		
		return $c;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Default route is the application's Config/URI_Routes.php file.
	 * 
	 * @return array
	 */
	public function getUriRouteFiles()
	{
		return array('./app/Config/URI_Routes.php');
	}
}


/* End of file DefaultApplication.php */
/* Location: ./lib/Inject */