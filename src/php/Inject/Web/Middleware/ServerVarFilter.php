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
 * Filters the $_SERVER variable passed as $env to a proper format for web requests,
 * also filters for invalid UTF-8 chars.
 * 
 * Filters for:
 * - PATH_INFO:      Corrects PATH_INFO so it always starts with a "/" and it also
 *                   trims trailing slashes.
 * - SCRIPT_NAME:    Fixes so URL rewriting will result in SCRIPT_NAME without the
 *                   script filename (or should do at least, needs testing).
 * - BASE_URI:       Determines the URI to the script (front controller) without
 *                   the filename, to be used when referring to assets.
 * - REQUEST_METHOD: Validates the method used and uppercases it,
 *                   If it is a POST request and the "_method" parameter is set,
 *                   then that will be assumed as the REQUEST_METHOD (also validated).
 * - POST:           Puts the POST data into the "POST" key.
 * - GET:            Puts the GET data into the "GET" key.
 * 
 * NOTE:
 * You don not have to use the \Inject\Core\Middleware\Utf8Filter combined with
 * this middleware, it is just a waste of processing power as it will be filtered
 * twice.
 */
class ServerVarFilter extends Utf8Filter implements MiddlewareInterface
{
	/**
	 * List of valid HTTP/1.1 request methods.
	 * 
	 * @var array(string)
	 */
	static protected $request_methods = array(
			'CONNECT',
			'DELETE',
			'GET',
			'HEAD',
			'OPTIONS',
			'POST',
			'PUT',
			'TRACE'
		);
	
	/**
	 * Next callback.
	 * 
	 * @var callback
	 */
	protected $next;
	
	/**
	 * If to filter for invalid Utf8.
	 * 
	 * @var boolean
	 */
	protected $filter_utf8 = true;
	
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
			$env['GET'] = array();
		}
		else
		{
			// _GET not trusted
			parse_str($env['QUERY_STRING'], $env['GET']);
		}
		
		// _POST might not be accurate, depends on request type, read from php://input
		parse_str(file_get_contents('php://input'), $env['POST']);
		
		// UTF8 Filtering
		if($this->filter_ut8)
		{
			$env = $this->cleanUtf8($env);
		}
		
		// PATH_INFO, SCRIPT_NAME and BASE_URI
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
		
		// TODO: Check if accurrate:
		$env['PATH_INFO']            = '/'.trim($uri, '/');
		$env['BASE_URI']             = dirname($env['SCRIPT_NAME']);
		$env['SCRIPT_NAME']          = $front_controller;
		
		$env['REQUEST_PROTOCOL']     = (( ! empty($env['HTTPS'])) && $env['HTTPS'] != 'off') ? 'https' : 'http';
		$env['REQUEST_METHOD']       = $this->checkMethod($env['REQUEST_METHOD']);
		
		// Check if we have got a redirect type for the post method,
		// this is to be able to send PUT and DELETE from browser forms
		// (which does not support them, as only XHTML 2.0 does)
		if($env['REQUEST_METHOD'] === 'POST' && ! empty($env['POST']['_method']))
		{
		    $env['REQUEST_METHOD'] = $this->checkMethod($env['POST']['_method'], true);
		}
		
		$callback = $this->next;
		return $callback($env);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validates the HTTP request method, so it contains a valid method, throws
	 * exception if it is not valid.
	 * 
	 * @param  string
	 * @param  boolean Set to true if this is from a post override, then the
	 *                 exception will reflect that if it is casted
	 * @return string
	 */
	protected function checkMethod($request_method, $post_override = false)
	{
		$request_method = strtoupper($request_method);
		
		if( ! in_array($request_method, self::$request_methods))
		{
			$allowed_methods = implode(', ', array_slice(self::$request_methods, 0, -1)).(($m = end(self::$request_methods)) ? ' and '.$m : '');
			
			if($post_override)
			{
				// TODO: Exception
				throw new \Exception(sprintf('Unknown HTTP request method %s specified by "_method" in POST data, accepted HTTP methods are: %s.', $request_method, $allowed_methods));
			}
			else
			{
				// TODO: Exception
				throw new \Exception(sprintf('Unknown HTTP request method %s, accepted HTTP methods are: %s.', $request_method, $allowed_methods));
			}
		}
		
		return $request_method;
	}
}


/* End of file ServerVarFilter.php */
/* Location: src/php/Inject/Web/Middleware */