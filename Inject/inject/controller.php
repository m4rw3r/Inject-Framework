<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Controller
{
	/**
	 * The parameters sent to this controller.
	 * 
	 * @var Inject_Request
	 */
	public $request;
	
	/**
	 * The response which will be sent to the browser.
	 * 
	 * @var Inject_Response
	 */
	public $response;
	
	function __construct(Inject_Request $req)
	{
		$this->request = $req;
		
		$this->response = $req->get_response();
	}
}


/* End of file controller.php */
/* Location: ./application/controller */