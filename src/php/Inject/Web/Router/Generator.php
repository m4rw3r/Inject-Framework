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
	/**
	 * List of associated DestinationHandlerInterface classes.
	 * 
	 * @var array(string)
	 */
	protected $dest_handlers = array(
			'Inject\Web\Router\Generator\DestinationHandler\Controller',
			'Inject\Web\Router\Generator\DestinationHandler\Callback',
			'Inject\Web\Router\Generator\DestinationHandler\Engine',
			'Inject\Web\Router\Generator\DestinationHandler\Redirect'
		);
	
	/**
	 * The CodeGenerator instance used by this Generator.
	 * 
	 * @var \Inject\Web\Router\Generator\CodeGenerator
	 */
	protected $generator;
	
	// ------------------------------------------------------------------------
	
	public function __construct(Engine $engine, $mapping = null)
	{
		parent::__construct($engine, $mapping);
		
		$this->generator = new Generator\CodeGenerator($this->engine);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Registers a specific destination handler class which will generate code
	 * for routing based on the Mapping objects created by the user.
	 * 
	 * @param  string  Class implementing Inject\Web\Router\Generator\DestinationHandlerInterface
	 * @return void
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
	 * Will clear the list of classes for DestinationHandlers, useful when
	 * overriding default DestinationHandlers.
	 * 
	 * @return void
	 */
	public function clearDestinationHandlers()
	{
		$this->dest_handlers = array();
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
	 * Returns an array of compiled DestinationHandlers which wrap the Mapping
	 * objects of this generator.
	 * 
	 * @return array(Inject\Web\Router\Generator\DestinationHandlerInterface)
	 */
	public function getDestinationHandlers()
	{
		$defs = $this->getDefinitions();
		
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
			
			// Compile the contents of the DestinationHandlers
			$handler->prepare();
			$handler->validate($this->engine);
			$handler->compile();
			
			$arr[] = $handler;
		}
		
		return $arr;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns an array containing the router closure and an array with reverse
	 * router closures.
	 * 
	 * @return array(Closure, array(string => Closure))
	 */
	public function getCompiledRoutes()
	{
		$engine = $this->engine;
		
		return eval($this->generator->generateCode($this->getDestinationHandlers()));
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Writes the router cache file.
	 * 
	 * @param  string   The file to write to
	 * @return void
	 */
	public function writeCache($path)
	{
		$file = tempnam(dirname($path), basename($path));
		
		$code = '<?php
/**
 * Route cache file generated on '.date('Y-m-d H:i:s').' by Inject Framework Router
 * ('.__CLASS__.').
 */

namespace Inject\Web\Router;

';
		
		$code .= $this->generator->generateCode($this->getDestinationHandlers());
		
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