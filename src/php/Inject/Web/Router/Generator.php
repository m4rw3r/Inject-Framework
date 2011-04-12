<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router;

use \Inject\Core\Engine;

/**
 * 
 */
class Generator extends Generator\Scope
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
		$def   = array();
		$named = array();
		
		foreach($this->getDestinations() as $d)
		{
			$r     = $d->getCompiled();
			$def[] = $r;
			
			if($d->getName())
			{
				$named[$d->getName()] = $r;
			}
		}
		
		return array($def, $named);
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
		
		$code = '<?php
/**
 * Route cache file generated on '.date('Y-m-d H:i:s').' by Inject Framework Router
 * (\Inject\Web\Router\Generator).
 */

namespace Inject\Web\Router;

$available_controllers = '.var_export($this->engine->getAvailableControllers(), true).';

$def   = array();
$named = array();

';
		$arr = array();
		
		foreach($destinations as $m)
		{
			$name  = $m->getName() ? '$named['.var_export($m->getname(), true).'] = ' : ''; 
			$arr[] = $m->getCacheCode($name.'$def[]', '$available_controllers', '$engine');
		}
		
		$code = $code.implode("\n\n", $arr);
		$code .= '

return array($def, $named);';
		
		if(@file_put_contents($file, $code))
		{
			if(@rename($file, $path))
			{
				chmod($path, 0644);
				
				return;
			}
		}
		
		// TODO: Exception
		throw new \Exception(sprintf('Cannot write to the %s directory', basename($path)));
	}
}


/* End of file Router.php */
/* Location: src/php/Inject/Web/Router */