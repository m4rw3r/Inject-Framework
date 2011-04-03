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
class Generator
{
	// TODO: Add scope() method
	// TODO: Add resource() method
	// TODO: Add route dumper
	
	/**
	 * The application engine.
	 * 
	 * @var \Inject\Core\Engine
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
	 * Creates a matcher which will attempt to match the specified path pattern,
	 * see the Mapping class for more settings for the matchers.
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\Web\Router\Generator\Mapping
	 */
	public function match($path = '', array $segment_constraints = array())
	{
		$this->definitions[] = $m = new Mapping();
		$m->path($path, $segment_constraints);
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a matcher which will attempt to match the root ("/"), see the
	 * Mapping class for more settings for the matchers.
	 * 
	 * This should preferably be placed first in the Routes.php file as this
	 * is the most popular route of the site.
	 * 
	 * @param  string
	 * @return \Inject\Web\Router\Generator\Mapping
	 */
	public function root()
	{
		$this->definitions[] = $m = new Mapping();
		$m->path('/');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shorthand for match($path)->via('GET').
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\Web\Router\Generator\Mapping
	 */
	public function get($path, array $segment_constraints = array())
	{
		$this->definitions[] = $m = new Mapping();
		$m->path($path, $segment_constraints);
		$m->via('GET');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shorthand for match($path)->via('POST').
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\Web\Router\Generator\Mapping
	 */
	public function post($path, array $segment_constraints = array())
	{
		$this->definitions[] = $m = new Mapping();
		$m->path($path, $segment_constraints);
		$m->via('POST');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shorthand for match($path)->via('PUT').
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\Web\Router\Generator\Mapping
	 */
	public function put($path, array $segment_constraints = array())
	{
		$this->definitions[] = $m = new Mapping();
		$m->path($path, $segment_constraints);
		$m->via('PUT');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shorthand for match($path)->via('DELETE').
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\Web\Router\Generator\Mapping
	 */
	public function delete($path, array $segment_constraints = array())
	{
		$this->definitions[] = $m = new Mapping();
		$m->path($path, $segment_constraints);
		$m->via('DELETE');
		
		return $m;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function mount($path, $app_name, array $segment_constraints = array())
	{
		$this->definitions[] = $m = new Mapping();
		$m->path($path, $segment_constraints);
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
	 * that into the specified part of the URI/URL given to redirect().
	 * 
	 * @param  string  A uri, url and/or pattern
	 * @param  int     The redirect code
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
			
			if(isset($to['redirect']))
			{
				$arr[] = new Destination\Redirect($d, $this->engine);
			}
			elseif(isset($to['callback']))
			{
				$arr[] = new Destination\Callback($d, $this->engine);
			}
			elseif(isset($to['engine']))
			{
				$arr[] = new Destination\Application($d, $this->engine);
			}
			elseif(isset($to['controller']))
			{
				$arr[] = new Destination\Controller($d, $this->engine);
			}
			else
			{
				$arr[] = new Destination\Polymorphic($d, $this->engine);
			}
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
		
		// TODO: Replace count($this->definitions) with something which asks the Mappings,
		// TODO: cont. to allow for multiple routes per destination (for eg. resources)
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