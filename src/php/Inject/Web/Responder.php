<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web;

/**
 * Passes the response from the middleware stack to the browser, use on the
 * return value of \Inject\Core\MiddlewareStack->run().
 */
class Responder
{
	/**
	 * The HTTP status codes and their corresponding textual representation.
	 * 
	 * @var array(string => string)
	 */
	static public $status_texts = array(
		'100' => 'Continue',
		'101' => 'Switching Protocols',
		'200' => 'OK',
		'201' => 'Created',
		'202' => 'Accepted',
		'203' => 'Non-Authoritative Information',
		'204' => 'No Content',
		'205' => 'Reset Content',
		'206' => 'Partial Content',
		'300' => 'Multiple Choices',
		'301' => 'Moved Permanently',
		'302' => 'Found',
		'303' => 'See Other',
		'304' => 'Not Modified',
		'305' => 'Use Proxy',
		'307' => 'Temporary Redirect',
		'400' => 'Bad Request',
		'401' => 'Unauthorized',
		'402' => 'Payment Required',
		'403' => 'Forbidden',
		'404' => 'Not Found',
		'405' => 'Method Not Allowed',
		'406' => 'Not Acceptable',
		'407' => 'Proxy Authentication Required',
		'408' => 'Request Timeout',
		'409' => 'Conflict',
		'410' => 'Gone',
		'411' => 'Length Required',
		'412' => 'Precondition Failed',
		'413' => 'Request Entity Too Large',
		'414' => 'Request-URI Too Long',
		'415' => 'Unsupported Media Type',
		'416' => 'Requested Range Not Satisfiable',
		'417' => 'Expectation Failed',
		'418' => 'I\'m a teapot',
		'444' => 'No Response',   // Nginx, closes connection without responding
		'500' => 'Internal Server Error',
		'501' => 'Not Implemented',
		'502' => 'Bad Gateway',
		'503' => 'Service Unavailable',
		'504' => 'Gateway Timeout',
		'505' => 'HTTP Version Not Supported',
		);
	
	// ------------------------------------------------------------------------

	/**
	 * Sends the response to the browser.
	 * 
	 * @param  array  array(response_code, array(header_title => header_content), content)
	 * @return void
	 */
	public static function respondWith(array $response)
	{
		$response_code = $response[0];
		$headers = $response[1];
		$content = $response[2];
		
		header(sprintf('HTTP/1.1 %s %s', $response_code, self::$status_texts[$response_code]));
		
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


/* End of file Responder.php */
/* Location: lib/Inject/Web */