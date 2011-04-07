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
	protected $named_routes = array();
	
	protected $routes = array();
	
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
			list($this->apps, $this->named_routes) = include $route_cache;
		}
		elseif(file_exists($route_config))
		{
			$g = new Router\Generator\Generator($engine);
			$g->loadFile($route_config);
			
			list($this->apps, $this->named_routes) = $g->getCompiledRoutes();
			
			if( ! $debug)
			{
				$g->writeCache($route_cache);
			}
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __invoke($env)
	{
		$env['web.router'] = $this;
		
		return parent::__invoke($env);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function generate($route_name, array $options = array())
	{
		if(isset($this->named_routes[$route_name]))
		{
			// If return value is an array, then something went wrong and the array contains required captures
			if(is_array($r = $this->named_routes[$route_name]->generate($options)))
			{
				// TODO: Exception
				throw new \Exception(sprintf('Cannot generate URI, route "%s" requires the "%s" parameter(s).', $route_name, implode(', ', array_diff($r, array_keys($options)))));
			}
			else
			{
				return $r;
			}
		}
		
		return null;
	}
}


/* End of file RouterEndpoint.php */
/* Location: lib/Inject/Web */