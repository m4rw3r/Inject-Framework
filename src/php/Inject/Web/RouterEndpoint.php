<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web;

use \Inject\Core\Application\Engine;

/**
 * Endpoint trying to match the request to a specific controller action through
 * rules specified in the Routes.php config file.
 */
class RouterEndpoint
{
	/**
	 * The application which this objects routes for.
	 * 
	 * @var \Inject\Application\Engine
	 */
	protected $app_engine;
	
	/**
	 * Compiled matchers.
	 * 
	 * @var array(\Inject\Web\Router\Route\AbstractRoute)
	 */
	protected $matchers = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct(Engine $app_engine, $debug = false)
	{
		$this->app_engine = $app_engine;
		
		$route_config = $this->app_engine->paths['config'].'Routes.php';
		$route_cache  = $this->app_engine->paths['cache'] .'Routes.php';
		
		if( ! $debug && file_exists($route_cache))
		{
			// Load cache
			$this->matchers = include $route_cache;
		}
		elseif(file_exists($route_config))
		{
			$g = new Router\Generator\Generator($this->app_engine);
			$g->loadFile($route_config);
			
			$this->matchers = $g->getCompiledRoutes();
			
			if( ! $debug)
			{
				$g->writeCache($route_cache);
			}
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Routes the supplied request to the proper controller.
	 * 
	 * @param  \Inject\Request\RequestInterface
	 * @return callback
	 */
	public function __invoke($env)
	{
		$ret = array(404, array('X-Cascade' => 'pass'), '');
		
		foreach($this->matchers as $m)
		{
			$ret = $m($env, $this->app_engine);
			
			if( ! (isset($ret[1]['X-Cascade']) && $ret[1]['X-Cascade'] === 'pass'))
			{
				return $ret;
			}
		}
		
		return $ret;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Constructs a URL froma set of options.
	 * 
	 * TODO: Move?
	 * 
	 * @param  array
	 * @return string
	 */
	public function toUrl(array $options)
	{
		if( ! (isset($options['host']) OR isset($options['only_path']) && $options['only_path']))
		{
			// TODO: Exception
			throw new \Exception('No host to link to, please set $default_url_options[\'host\'], $options[\'host\'] or $options[\'only_path\'].');
		}
		
		if(preg_match('#^\w+://#u', $options['path']))
		{
			return $options['path'];
		}
		
		$rewritten_url = '';
		
		if( ! (isset($options['only_path']) && $options['only_path']))
		{
			$rewritten_url .= isset($options['protocol']) ? $options['protocol'] : 'http';
			// TODO: Add authentication?
			$rewritten_url = trim($rewritten_url, '://').'://'.$options['host'];
			
			if(isset($options['port']) && ! empty($options['port']))
			{
				$rewritten_url .= ':'.$options['port'];
			}
		}
		
		$rewritten_url .= (isset($options['front_controller']) ? $options['front_controller'] : '').'/'.ltrim($options['path'], '/');
		
		// TODO: GET options?
		
		if(isset($options['anchor']))
		{
			$rewritten_url .= '#'.urlencode($options['anchor']);
		}
		
		return $rewritten_url;
	}
}


/* End of file RouterEndpoint.php */
/* Location: lib/Inject/Web */