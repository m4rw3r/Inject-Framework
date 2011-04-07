<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Route;

/**
 * A compiled route pointing to an Application or Engine.
 */
class ApplicationRoute extends AbstractRoute
{
	protected $app_name;
	
	// ------------------------------------------------------------------------
	
	/**
	 * @param  array(string => string)  The regular expression patterns
	 * @param  array(string => string)  List of options to return if this route matches
	 * @param  array(string => int)     List of keys to intersect to get the options from
	 *                                  the regex captures
	 * @param  string    Fully qualified Application/Engine class name
	 */
	public function __construct(array $constraints, array $options, array $capture_intersect, $uri_generator, $app_name)
	{
		parent::__construct($constraints, $options, $capture_intersect, $uri_generator);
		
		$this->app_name = $app_name;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Dispatches the request to the route destination, called by __invoke if
	 * all the route conditions matches.
	 * 
	 * @param  mixed
	 * @return callback
	 */
	protected function dispatch($env)
	{
		$uri  = $env['web.route']->param('uri', '/');
		$path = $uri == '/' ? substr($env['PATH_INFO'], - strlen($uri)) : $env['PATH_INFO'];
		
		// Move one step deeper in the directory structure
		$env['SCRIPT_NAME']   = $env['SCRIPT_NAME'].$path;
		$env['BASE_URI']      = $env['BASE_URI'].$path;
		$env['REQUEST_URI']   = $uri; // TODO: Check REQUEST_URI and how it is used??
		$env['PATH_INFO']     = $uri;
		$env['web.old_route'] = $env['web.route'];
		
		$app_class = $this->app_name;
		return $app_class::instance()->stack()->run($env);
	}
}


/* End of file ApplicationRoute.php */
/* Location: src/php/Inject/Web/Router/Route */