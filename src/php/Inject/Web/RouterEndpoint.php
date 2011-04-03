<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web;

use \Inject\Core\Engine;
use \Inject\Core\CascadeEndpoint;

/**
 * Endpoint trying to match the request to a specific controller action through
 * rules specified in the Routes.php config file.
 * 
 * Default file paths:
 * - Config:  $engine->paths['config'].'Routes.php'
 * - Cache:   $engine->paths['cache'] .'Routes.php'
 */
class RouterEndpoint extends CascadeEndpoint
{
	// ------------------------------------------------------------------------

	/**
	 * @param  \Inject\Core\Engine  The engine used to create controller instances
	 * @param  boolean              If to generate a cache file or not
	 * @param  string|false         Override for the default route config file
	 * @param  string|false         Override for the default route cache file
	 */
	public function __construct(Engine $engine, $debug = false, $route_config = false, $route_cache = false)
	{
		$route_config = empty($route_config) ? $engine->paths['config'].'Routes.php' : $route_config;
		$route_cache  = empty($route_cache)  ? $engine->paths['cache'] .'Routes.php' : $route_cache;
		
		if( ! $debug && file_exists($route_cache))
		{
			// Load cache
			$this->apps = include $route_cache;
		}
		elseif(file_exists($route_config))
		{
			$g = new Router\Generator\Generator($engine);
			$g->loadFile($route_config);
			
			$this->apps = $g->getCompiledRoutes();
			
			if( ! $debug)
			{
				$g->writeCache($route_cache);
			}
		}
	}
}


/* End of file RouterEndpoint.php */
/* Location: lib/Inject/Web */