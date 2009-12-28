<?php
/*
 * Created by Martin Wernståhl on 2009-12-28.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Controller
{
	/**
	 * The request instance which is currently run.
	 * 
	 * @var Inject_Request
	 */
	public $request;
	
	function __construct(Inject_Request $req)
	{
		$this->request = $req;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Uses the request's dependency injection container to fetch needed stuff.
	 * 
	 * Means that the database is autoloaded on usage:
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
}

/* End of file Controller.php */
/* Location: ./lib/Inject */