<?php
/*
 * Created by Martin Wernståhl on 2009-07-21.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Exception_MissingFile extends Inject_Exception
{
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct($file, $code = 0, $previous = null)
	{
		parent::__construct('Missing file: ' . $file, $code, $previous);
	}
}


/* End of file config.php */
/* Location: ./Inject/inject/exception */