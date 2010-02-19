<?php
/*
 * Created by Martin Wernståhl on 2010-02-19.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Response
{
	/**
	 * The parent response, the headers will be forwarded to this.
	 * 
	 * @var Inject_Response
	 */
	protected $parent = null;
	
	/**
	 * Response code.
	 * 
	 * @var int
	 */
	public $response_code = 200;
	
	/**
	 * The list of HTTP headers which will be sent by header().
	 * 
	 * @var array
	 */
	public $headers = array();
	
	/**
	 * The response body.
	 * 
	 * @var string
	 */
	public $body = '';
	
	/**
	 * Creates a new response object.
	 * 
	 * @param  Inject_Response The response object to use when setting response
	 *                         code and headers, used in nested calls (HMVC)
	 */
	function __construct(Inject_Response $parent = null)
	{
		$this->parent = $parent;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the HTTP response code which will be returned to the client.
	 * 
	 * @param  int
	 * @return self
	 */
	public function setResponseCode($code)
	{
		isset($this->parent) ? $this->parent->setResponseCode($code) : $this->response_code = $code;
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets a response header, overwrites any existing header with the same name.
	 * 
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function setHeader($header, $value)
	{
		isset($this->parent) ? $this->parent->setHeader($header, $value) : $this->headers[$header] = $value;
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sends all response headers which has been set.
	 * 
	 * @return void
	 */
	public function sendHeaders()
	{
		foreach($this->headers as $k => $v)
		{
			header($k.': '.$v);
		}
		
		// Send response code
		header('HTTP/1.1 '.$this->response_code);
	}
}


/* End of file Request.php */
/* Location: ./Users/m4rw3r/Sites/Inject-Framework/lib/Inject/Request.php */