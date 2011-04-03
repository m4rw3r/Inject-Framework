<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;

use \Inject\Core\Engine;

/**
 * 
 */
class Generator extends Scope
{
	// TODO: Add route dumper
	
	/**
	 * Attempts to load a route configuration file and parse its contents.
	 * 
	 * @param  string
	 * @return void
	 */
	public function loadFile($route_config)
	{
		if(file_exists($route_config))
		{
			// Load routes:
			include $route_config;
		}
		else
		{
			// TODO: Exception
			throw new \Exception(sprintf('Router generator cannot load the file %s.', $route_config));
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of instances of \Inject\Web\Router\Route\AbstractRoute
	 * which route according to the loaded config files.
	 * 
	 * @return array(\Inject\Web\Router\Route\AbstractRoute)
	 */
	public function getCompiledRoutes()
	{
		$arr = array();
		foreach($this->getDestinations() as $d)
		{
			$arr = array_merge($arr, $d->getCompiled());
		}
		
		return $arr;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Writes the router cache file.
	 * 
	 * @param  string   The file to write to
	 */
	public function writeCache($path)
	{
		$file = tempnam(dirname($path), basename($path));
		
		$destinations = $this->getDestinations();
		
		// TODO: Replace count($this->definitions) with something which asks the Mappings,
		// TODO: cont. to allow for multiple routes per destination (for eg. resources)
		$code = '<?php
/**
 * Route cache file generated on '.date('Y-m-d H:i:s').' by Inject Framework Router.
 */

namespace Inject\Web\Router;

$available_controllers = '.var_export($this->engine->getAvailableControllers(), true).';

$definitions = new \SplFixedArray('.count($destinations).');

';
		$arr = array();
		
		$i = 0;
		foreach($destinations as $m)
		{
			$arr[] = $m->getCacheCode('$definitions['.$i++.']', '$available_controllers', '$engine');
		}
		
		$code = $code.implode("\n\n", $arr);
		$code .= '

return $definitions;';
		
		if(@file_put_contents($file, $code))
		{
			if(@rename($file, $path))
			{
				chmod($path, 0644);
				
				return;
			}
		}
		
		throw new \Exception(sprintf('Cannot write to the %s directory', basename($path)));
	}
}


/* End of file Router.php */
/* Location: src/php/Inject/Web/Router */