<?php
/*
 * Created by Martin Wernståhl on 2009-12-28.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Controller_Íñtërnâtiônàlizætiøn extends Inject_Controller
{
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __call($method, $params = array())
	{
		echo "\nÍñtërnâtiônàlizætiøn controller\nmethod: $method\nparams:\n";
		
		print_r($this->request->getParameters());
		
		echo "\n";
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function åöä($value='')
	{
		echo "foobar, åäö\n";
		print_r($this->request->getParameters());
	}
}

/* End of file Íñtërnâtiônàlizætiøn.php */
/* Location: ./app/controllers */