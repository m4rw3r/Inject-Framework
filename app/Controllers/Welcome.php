<?php
/*
 * Created by Martin Wernståhl on 2009-12-28.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Controller_Welcome extends Inject_Controller
{
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __call($method, $params = array())
	{
		//$cw = new Inject_Util_Loader_CacheWriter();
		//$cw->writeIndex('index.php');
		
		$this->request->response = "\nWelcome controller\nmethod: $method\nparams:\n".
		print_r($this->request->getParameters(), true)."\n";
	}
}


/* End of file Welcome.php */
/* Location: ./app/controllers */