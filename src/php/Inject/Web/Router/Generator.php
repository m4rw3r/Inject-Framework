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
	protected $dest_handlers = array();
	
	protected $generator;
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct(Engine $engine, $mapping = null)
	{
		parent::__construct($engine, $mapping);
		$this->generator = new Generator\CodeGenerator($this->engine);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function registerDestinationHandler($class)
	{
		$ref = new \ReflectionClass($class);
		
		if($ref->isSubclassOf('Inject\Web\Router\Generator\DestinationHandlerInterface'))
		{
			// Only allow a single instance per class
			in_array($class, $this->dest_handlers) OR $this->dest_handlers[] = $class;
		}
		else
		{
			// TODO: Exception
			throw new \Exception(sprintf('The class %s is not a valid route destination handler, it must implement \Inject\Web\Route\Generator\DestinationHandlerInterface', $class));
		}
	}
	
	// ------------------------------------------------------------------------
	
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
	 * 
	 * 
	 * @return 
	 */
	public function getDefinitions()
	{
		$defs = parent::getDefinitions();
		
		$arr = array();
		
		foreach($defs as $def)
		{
			$handler = null;
			
			foreach($def->getToArray() as $to_val)
			{
				foreach($this->dest_handlers as $dh)
				{
					if($tmp = $dh::parseTo($to_val, $def, $handler))
					{
						$handler = $tmp;
					}
				}
			}
			
			if( ! $handler)
			{
				// TODO: Exception
				throw new \Exception(sprintf('The route %s does not have a compatible to() value.', $def->getPathPattern()));
			}
			
			$arr[] = $handler;
		}
		
		return $arr;
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
		$engine = $this->engine;
		return eval($this->generator->generateCode($this->getDefinitions()));
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
		
		$code = '<?php
/**
 * Route cache file generated on '.date('Y-m-d H:i:s').' by Inject Framework Router
 * (\Inject\Web\Router\Generator).
 */

namespace Inject\Web\Router;

';
		
		$code .= $this->generator->generateCode($this->getDefinitions());
		
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