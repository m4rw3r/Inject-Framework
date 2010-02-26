<?php
/*
 * Created by Martin Wernståhl on 2009-12-28.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Controller_Base
{
	/**
	 * The request instance which is currently run.
	 * 
	 * @var Inject_Request
	 */
	public $request;
	
	/**
	 * The response to send to the client.
	 * 
	 * @var Inject_Response
	 */
	public $response;
	
	function __construct(Inject_Request $req)
	{
		$this->request = $req;
		$this->response = $req->response;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Uses the request's dependency injection container to fetch needed stuff.
	 * 
	 * Means that eg. the database is autoloaded on usage:
	 * <code>
	 * $this->db->doSomething();
	 * </code>
	 * will automatically load the database object if it isn't already.
	 * 
	 * @param  string
	 * @return object
	 */
	public function __get($prop)
	{
		return $this->request->getService($prop);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Renders a view using the default template renderer which uses PHP to process
	 * the templates.
	 * 
	 * @param  string	The path to the file, relative to the Views folder, except file extension
	 * @param  array	The data to render, will be extracted to variables in the view
	 * @param  bool		If to return the rendered content to the caller
	 * @param  string	The file extension of the file to render
	 * @return string|void
	 */
	public function render($view_name, $data = array(), $return_rendered = false, $filetype = '.php')
	{
		$__found_file = false;
		
		extract($data);
		
		ob_start();
		
		// Find the view
		foreach(Inject::getApplicationPaths() as $p)
		{
			if(file_exists($p.'Views/'.$view_name.$filetype))
			{
				$__found_file = true;
				
				include $p.'Views/'.$view_name.$filetype;
				
				break;
			}
		}
		
		// get content
		$buffer = ob_get_contents();
		ob_end_clean();
		
		if( ! $__found_file)
		{
			// Replace with appropriate exception
			throw new Exception('Cannot find view "'.$view_name.'" filetype: "'.$filetype.'"');
		}
		
		if($return_rendered)
		{
			return $buffer;
		}
		else
		{
			$this->response->body = $buffer;
		}
	}
}

/* End of file Controller.php */
/* Location: ./lib/Inject */