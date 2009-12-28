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
	public function __call($method, $params = array())
	{
		echo "\nWelcome controller\nmethod: $method\nparams:\n";
		
		print_r($this->request->getParameters());
		
		echo "\n";
	}
}


/* End of file Welcome.php */
/* Location: ./app/controllers */