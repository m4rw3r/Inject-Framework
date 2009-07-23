<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * TODO: Make it produce proper 404 errors.
 */
class Inject_Controller_404 extends Inject_Controller
{
	function __construct(Inject_Request $req)
	{
		parent::__construct($req);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __call($method, $params)
	{
		Inject::set_output('<h1>404 Controller Loaded</h1><p>URI: '.$this->request->get_uri().'</p><p>Inject Framework</p>');
	}
}


/* End of file default.php */
/* Location: ./Inject/inject/controller */