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
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the HTTP response code which will be returned to the client.
	 * 
	 * @param  int
	 * @return self
	 */
	public function setResponseCode($code)
	{
		$this->response_code = $code;
		
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
		$this->headers[$header] = $value;
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sends this request to the client.
	 * 
	 * @return void
	 */
	public function send()
	{
		// Store the headers until they are sent just at the end, so parent requests
		// can override them when their send() methods are triggered
		Inject::addHeaders($this->headers);
		Inject::setResponseCode($this->response_code);
		
		// Echo the content now
		echo $this->body;
	}
}


/* End of file Request.php */
/* Location: ./Users/m4rw3r/Sites/Inject-Framework/lib/Inject/Request.php */