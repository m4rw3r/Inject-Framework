<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web;

use \Inject\Core\Application\Engine;
use \Inject\Core\CascadeEndpoint;

/**
 * Endpoint trying to match the request to a specific controller action through
 * rules specified in the Routes.php config file.
 */
class RouterEndpoint extends CascadeEndpoint
{
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct(Engine $engine, $debug = false)
	{
		$route_config = $engine->paths['config'].'Routes.php';
		$route_cache  = $engine->paths['cache'] .'Routes.php';
		
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
	
	// ------------------------------------------------------------------------

	/**
	 * Constructs a URL froma set of options.
	 * 
	 * TODO: Move
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