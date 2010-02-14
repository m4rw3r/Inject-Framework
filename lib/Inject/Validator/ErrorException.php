<?php
/*
 * Created by Martin Wernståhl on 2010-02-08.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Validation error, contains data about which validator it was thrown from and
 * which parameters were sent to the validator.
 */
class Inject_Validator_ErrorException extends Exception implements Inject_Exception
{
	/**
	 * Contains the validator name, eg. isReuqired.
	 * 
	 * @var string
	 */
	protected $validator;
	
	/**
	 * Contains the list of parameters given to the validator.
	 * 
	 * @var array
	 */
	protected $parameters;
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a new ErrorException object for $validator which got the parameters
	 * $parameters.
	 */
	public function __construct($validator, $parameters = array())
	{
		parent::__construct($validator.'('.implode(', ', (Array) $parameters).')');
		
		$this->parameters = (Array) $parameters;
		$this->validator = $validator;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns validator name.
	 * 
	 * @return string
	 */
	public function getValidator()
	{
		return $this->validator;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns parameters given to the validator.
	 * 
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}
}


/* End of file ErrorException.php */
/* Location: ./lib/Inject/Validator */