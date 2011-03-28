<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Middleware;

use \Inject\Core\Middleware\MiddlewareInterface;
use \Inject\Core\Middleware\Utf8Filter;

/**
 * Filters the $_SERVER variable passed as $env to a proper format for web requests.
 * 
 * NOTE:
 * You don't have to use the \Inject\Core\Middleware\Utf8Filter combined with
 * this middleware.
 * 
 * TODO: Rename?
 * TODO: More validation
 */
class ServerVarFilter extends Utf8Filter implements MiddlewareInterface
{
	protected $next;
	
	
	// ------------------------------------------------------------------------

	/**
	 * @param  boolean  If to filter for bad UTF-8 characters
	 */
	public function __construct($filter_ut8 = true)
	{
		$this->filter_ut8 = $filter_ut8;
	}
	
	// ------------------------------------------------------------------------
	
	public function setNext($callback)
	{
		$this->next = $callback;
	}
	
	// ------------------------------------------------------------------------
	
	public function __invoke($env)
	{
		// PROCESS POST and GET, important to do this as faulty UTF-8 can be
		// encoded in the URI and therefore we have to unpack it here
		if(empty($env['QUERY_STRING']))
		{
			$env['web.get_data'] = array();
		}
		else
		{
			// _GET not trusted
			parse_str($env['QUERY_STRING'], $env['web.get_data']);
		}
		
		// _POST might not be accurate, depends on request type, read from php://input
		parse_str(file_get_contents('php://input'), $env['web.post_data']);
		
		if($this->filter_ut8)
		{
			$env = $this->cleanUtf8($env);
		}
		
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
		
		
		// SCRIPT_NAME + PATH_INFO = URI - QUERY_STRING
		
		$env['web.uri']              = '/'.trim($uri, '/');
		$env['web.base_uri']         = dirname($env['SCRIPT_NAME']);
		$env['web.front_controller'] = $front_controller;
		// TODO: Check if accurrate:
		$env['PATH_INFO']            = $env['web.uri'];
		$env['SCRIPT_NAME']          = $front_controller;
		
		$env['web.protocol'] = (( ! empty($env['HTTPS'])) && $env['HTTPS'] != 'off') ? 'https' : 'http';
		$env['web.host']     = $env['SERVER_NAME'];
		$env['web.port']     = $env['SERVER_PORT'] == '80' ? '' : $env['SERVER_PORT'];
		$env['web.method']   = isset($env['REQUEST_METHOD']) ? strtoupper($env['REQUEST_METHOD']) : 'GET';
		$env['web.xhr']      = isset($env['HTTP_X_REQUESTED_WITH']) && strtolower($env['HTTP_X_REQUESTED_WITH'])  == 'xmlhttprequest';
		
		$env['web.remote_ip'] = $this->getRemoteIp($env);
		
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
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function getRemoteIp($env)
	{
		if(isset($env['REMOTE_ADDR']))
		{
			return trim($env['REMOTE_ADDR']);
		}
		
		if(isset($env['HTTP_CLIENT_IP']))
		{
			return trim(array_shift(explode(',', $env['REMOTE_ADDR'])));
		}
		
		if(isset($env['HTTP_X_FORWARDED_FOR']))
		{
			return trim(array_shift(explode(',', $env['REMOTE_ADDR'])));
		}
	}
}


/* End of file ServerVarFilter.php */
/* Location: src/php/Inject/Web/Middleware */