<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\ServerAdapter;

use \Inject\Core\AdapterInterface;
use \Inject\Core\Engine;

use \Inject\Web\Util;

/**
 * Acts as an adapter between the server and the application stack.
 */
class Generic implements AdapterInterface
{
	/**
	 * Runs the supplied application with values fetched from the server environment
	 * and sends the output to the browser.
	 * 
	 * @param  \Inject\Core\Engine
	 * @return void
	 */
	public static function run(Engine $app)
	{
		$env = $_SERVER;
		
		$env['inject.version']    = \Inject\Core\Engine::VERSION;
		$env['inject.adapter']    = get_called_class();
		$env['inject.url_scheme'] = (( ! empty($env['HTTPS'])) && $env['HTTPS'] != 'off') ? 'https' : 'http';
		
		// SCRIPT_NAME + PATH_INFO = URI - QUERY_STRING
		$env['SCRIPT_NAME'] == '/'  && $env['SCRIPT_NAME']  = '';
		isset($env['QUERY_STRING']) OR $env['QUERY_STRING'] = '';
		$env['PATH_INFO']            = '/'.trim($env['PATH_INFO'], '/');
		$env['REQUEST_METHOD']       = Util::checkRequestMethod($env['REQUEST_METHOD']);
		
		$env['BASE_URI']             = dirname($env['SCRIPT_NAME']);
		
		if(empty($env['QUERY_STRING']))
		{
			$env['inject.get'] = array();
		}
		else
		{
			parse_str($env['QUERY_STRING'], $env['inject.get']);
		}
		
		// _POST might not be accurate, depends on request type, read from php://input
		parse_str(file_get_contents('php://input'), $env['inject.post']);
		
		static::respondWith($app->stack()->run($env));
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sends the response to the browser.
	 * 
	 * @param  array  array(response_code, array(header_title => header_content), content)
	 * @return void
	 */
	protected static function respondWith(array $response)
	{
		$response_code = $response[0];
		$headers = $response[1];
		$content = $response[2];
		
		header(sprintf('HTTP/1.1 %s %s', $response_code, Util::getHttpStatusText($response_code)));
		
		if( ! isset($headers['Content-Type']))
		{
			$headers['Content-Type'] = 'text/html';
		}
		
		// TODO: Enable length-less responses
		$headers['Content-Length'] = strlen($content);
		
		foreach($headers as $k => $v)
		{
			header($k.': '.$v);
		}
		
		echo $content;
	}
}


/* End of file Generic.php */
/* Location: src/php/Inject/Web/ServerAdapter */