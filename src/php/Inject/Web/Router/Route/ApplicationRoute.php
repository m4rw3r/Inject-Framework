<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Route;

use \Inject\Core\Application\Engine;

/**
 * A compiled route pointing to an Application or Engine.
 */
class ApplicationRoute extends AbstractRoute
{
	protected $app_name;
	
	// ------------------------------------------------------------------------
	
	/**
	 * @param  string  The regular expression pattern
	 * @param  array(string => string)  List of options to return if this route matches
	 * @param  array(string => int)  List of keys to intersect to get the options from
	 *                               the regex captures
	 * @param  array(string)  List of accepted HTTP request methods
	 * @param  string    Fully qualified Application/Engine class name
	 */
	public function __construct($pattern, array $options, array $capture_intersect, array $accepted_request_methods, $app_name)
	{
		parent::__construct($pattern, $options, $capture_intersect, $accepted_request_methods);
		
		$this->app_name = $app_name;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns a callback which is to be run by the application, this
	 * method is called after matches() has returned true.
	 * 
	 * @param  \Inject\Core\Application\Engine
	 * @return callback
	 */
	public function dispatch($env, Engine $engine)
	{
		$app_class = $this->app_name;
		
		$uri  = isset($this->parsed_options['uri']) ? $this->parsed_options['uri'] : '';
		$path = substr($env['web.uri'], - strlen($uri));
		
		$env['web.front_controller'] = $env['web.front_controller'].$path;
		$env['web.base_uri']         = $env['web.base_uri'].$path;
		$env['web.uri']              = $uri;
		$env['REQUEST_URI']          = $uri;
		$env['PATH_INFO']            = $uri;
		$env['web.path_params_old']  = $env['web.path_params_old'];
		
		return $app_class::instance()->stack()->run($env);
	}
}


/* End of file ApplicationRoute.php */
/* Location: src/php/Inject/Web/Router/Route */