<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;

use \Inject\Core\Application\Engine;

/**
 * 
 */
class Generator
{
	// TODO: Add scope() method
	// TODO: Add resource() method
	// TODO: Add route dumper
	
	/**
	 * The application engine.
	 * 
	 * @var \Inject\Core\Application\Engine
	 */
	protected $engine;
	
	/**
	 * Route definitions.
	 * 
	 * @var array(Mapping)
	 */
	protected $definitions = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct(Engine $engine)
	{
		$this->engine = $engine;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
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
	public function match($path)
	{
		$this->definitions[] = $m = new Mapping($path, $this->engine);
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function root()
	{
		$this->definitions[] = $m = new Mapping('/', $this->engine);
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function get($path)
	{
		$this->definitions[] = $m = new Mapping($path, $this->engine);
		$m->via('GET');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function post($path)
	{
		$this->definitions[] = $m = new Mapping($path, $this->engine);
		$m->via('POST');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function put($path)
	{
		$this->definitions[] = $m = new Mapping($path, $this->engine);
		$m->via('PUT');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function delete($path)
	{
		$this->definitions[] = $m = new Mapping($path, $this->engine);
		$m->via('DELETE');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function mount($path, $app_name)
	{
		$this->definitions[] = $m = new Mapping($path, $this->engine);
		$m->to($app_name);
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a redirect destination with the URI/URL specified in $uri_pattern,
	 * should be passed to to().
	 * 
	 * You can use the same syntax as mach() does, but optional parts are not
	 * allowed. The captures will take the parameter read by match() and inject
	 * that into the specified part of the URI/URL.
	 * 
	 * @param  string  A uri, url and/or pattern
	 * @return \Inject\Web\Router\Generator\Redirect
	 */
	public function redirect($uri_pattern, $redirect_code = 301)
	{
		return new Redirection($uri_pattern, $redirect_code);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function getDestinations()
	{
		$arr = array();
		
		foreach($this->definitions as $d)
		{
			$to = $d->getTo();
			
			if(is_object($to) && $to instanceof Redirection)
			{
				$arr[] = new Destination\Redirect($d, $this->engine);
				continue;
			}
			
			if(is_callable($to))
			{
				$arr[] = new Destination\Callback($d, $this->engine);
				continue;
			}
			
			if(class_exists($to))
			{
				$arr[] = new Destination\Application($d, $this->engine);
				continue;
			}
			
			if( ! empty($to))
			{
				$arr[] = new Destination\Controller($d, $this->engine);
				continue;
			}
			
			$arr[] = new Destination\Polymorphic($d, $this->engine);
		}
		
		return $arr;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function getCompiledRoutes()
	{
		$arr = array();
		foreach($this->getDestinations() as $d)
		{
			$arr = array_merge($arr, $d->getCompiled());
		}
		
		var_dump($arr);
		
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
		
		// TODO: Replace count($this->definitions) with something which asks the Mappings
		$code = '<?php
/**
 * Route cache file generated on '.date('Y-m-d H:i:s').' by Inject Framework Router.
 */

namespace Inject\Web\Router;

$available_controllers = '.var_export($this->engine->getAvailableControllers(), true).';

$definitions = new \SplFixedArray('.count($this->definitions).');

';
		$arr = array();
		
		$i = 0;
		foreach($this->getDestinations() as $m)
		{
			$arr[] = $m->getCacheCode('$definitions['.$i++.']', '$available_controllers');
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