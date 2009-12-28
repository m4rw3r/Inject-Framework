<?php
/*
 * Created by Martin Wernståhl on 2009-12-28.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Controller_Welcome
{
	function __construct(Inject_Request $req)
	{
		$this->request = $req;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function index()
	{
		echo "all";
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function error()
	{
		echo "404 Error!!!!";
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __call($method, $params = array())
	{
		echo $method;
	}
}


/* End of file Welcome.php */
/* Location: ./app/controllers */