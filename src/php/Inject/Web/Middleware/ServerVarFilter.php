<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Middleware;

use \Inject\Core\Middleware\MiddlewareInterface;

/**
 * Filters the $_SERVER variable passed as $env to a proper format for web requests.
 * 
 * TODO: Rename?
 * TODO: More validation
 */
class ServerVarFilter implements MiddlewareInterface
{
	protected $next;
	
	public function setNext($callback)
	{
		$this->next = $callback;
	}
	
	public function __invoke($env)
	{
		$uri = '';
		
		// Do we have path info?
		if(isset($env['PATH_INFO']))
		{
			$uri = $env['PATH_INFO'];
			// Make sure to remove the script name from it, IIS rewrite might keep it
			$uri = str_replace($env['SCRIPT_NAME'], '', $env['PATH_INFO']);
		}
		
		// Check for a rewrite, if rewritten, the SCRIPT_NAME will not be included in
		// REQUEST_URI which contains the original, pre-rewrite URI
		$front_controller = strpos($env['REQUEST_URI'], $env['SCRIPT_NAME']) !== 0 ? 
		                    dirname($env['SCRIPT_NAME']) : $env['SCRIPT_NAME'];
		
		
		$env['web.uri']              = '/'.trim($uri, '/');
		$env['web.base_uri']         = dirname($env['SCRIPT_NAME']);
		$env['web.front_controller'] = $front_controller;
		// TODO: Check if accurrate:
		$env['PATH_INFO']            = $env['web.uri'];
		$env['REQUEST_URI']          = $front_controller;
		
		$env['web.protocol'] = (( ! empty($env['HTTPS'])) && $env['HTTPS'] != 'off') ? 'https' : 'http';
		$env['web.host']     = $env['SERVER_NAME'];
		$env['web.port']     = $env['SERVER_PORT'] == '80' ? '' : $env['SERVER_PORT'];
		$env['web.method']   = isset($env['REQUEST_METHOD']) ? strtoupper($env['REQUEST_METHOD']) : 'GET';
		$env['web.xhr']      = isset($env['HTTP_X_REQUESTED_WITH']) && strtolower($env['HTTP_X_REQUESTED_WITH'])  == 'xmlhttprequest';
		
		if($env['web.method'] !== 'GET' && $env['web.method'] !== 'POST')
		{
			// PUT etc. does not parse form data, do it now
			parse_str(file_get_contents('php://input'), $env['web.post_data']);
		}
		else
		{
			// TODO: ???
			$env['web.post_data'] = $_POST;
		}
		
		// Check if we have got a redirect type for the post method,
		// this is to be able to send PUT and DELETE from browser forms
		// (which does not support them, as only XHTML 2.0 does)
		if($env['web.method'] === 'POST' && isset($env['web.post_data']['_method']))
		{
		    $method = strtoupper($env['web.post_data']['_method']);
		    
		    in_array($method, array('PUT', 'DELETE'), true) && $env['web.method'] = $method;
		}
		
		$callback = $this->next;
		return $callback($env);
	}
}


/* End of file ServerVarFilter.php */
/* Location: src/php/Inject/Web/Middleware */