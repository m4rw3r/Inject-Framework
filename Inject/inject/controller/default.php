<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * TODO: Make it produce a welcome message for Inject Framework, with a few tidbits about it.
 */
class Inject_Controller_Default extends Inject_Controller
{
	function __construct()
	{
		parent::__construct();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __call($method, $params)
	{
		Inject::set_output('<h1>Default Controller Loaded</h1><p>Inject Framework</p>');
	}
}


/* End of file default.php */
/* Location: ./Inject/inject/controller */