<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Middleware;

use \Inject\Core\Middleware\MiddlewareInterface;

use \Inject\Web\Util;

/**
 * Will override the REQUEST_METHOD if a "_method" key is specified in the
 * inject.post array, validates it for valid methods.
 * 
 * This middleware is useful for when dealing with browsers or clients
 * which cannot send DELETE or PUT requests. Then you just specify
 * a _method parameter in the POST form which then will override the
 * POST request method.
 * 
 * The old request method will be saved in "web.old_REQUEST_METHOD".
 */
class MethodOverride implements MiddlewareInterface
{
	protected $next;
	
	// ------------------------------------------------------------------------
	
	public function setNext($callback)
	{
		$this->next = $callback;
	}
	
	// ------------------------------------------------------------------------
	
	public function __invoke($env)
	{
		// Check if we have got a redirect type for the post method,
		// this is to be able to send PUT and DELETE from browser forms
		// (which does not support them, as only XHTML 2.0 does)
		if($env['REQUEST_METHOD'] === 'POST' && ! empty($env['inject.post']['_method']))
		{
			$env['web.old_REQUEST_METHOD'] = $env['REQUEST_METHOD'];
		    $env['REQUEST_METHOD']         = Util::checkRequestMethod($env['POST']['_method'], true);
		}
		
		$callback = $this->next;
		return $callback($env);
	}
}


/* End of file MethodOverride.php */
/* Location: src/php/Inject/Web/Middleware */