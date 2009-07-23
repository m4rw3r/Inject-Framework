<?php
/*
 * Created by Martin Wernståhl on 2009-07-24.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * A HTTP response.
 */
class Inject_Response_HTTP
{
	
	protected $content = '';
	
	protected $headers = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function set_header($header)
	{
		$this->headers[] = $header;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function set_content($string)
	{
		$this->content = $string;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function append_content($string)
	{
		$this->content .= $string;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns the contents of this response, also sends any headers.
	 * 
	 * @return string
	 */
	public function output_content()
	{
		// output headers before we return the content
		foreach($this->headers as $h)
		{
			header($h);
		}
		
		return $this->content;
	}
}


/* End of file http.php */
/* Location: ./Inject/inject/response */