<?php
/*
 * Created by Martin Wernståhl on 2009-07-21.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Exception extends Exception
{
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct($message, $code = 0, $previous = null)
	{
		parent::__construct('Inject Framework - ' . $message, $code, $previous);
	}
}


/* End of file exception.php */
/* Location: ./Inject/inject */