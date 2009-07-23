<?php
/*
 * Created by Martin Wernståhl on 2009-07-18.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * An interface for the objects which handle requests.
 * 
 * TODO: Add methods for assigning content, check how it is good with eg. the JSON, XML-RPC and SOAP responses.
 */
interface Inject_Response
{
	/**
	 * Returns an array of headers, key is the header name.
	 * 
	 * @return array
	 */
	public function get_headers();
	
	/**
	 * Returns the contents of this response.
	 * 
	 * @return string
	 */
	public function get_content();
}


/* End of file response.php */
/* Location: ./Inject/inject */