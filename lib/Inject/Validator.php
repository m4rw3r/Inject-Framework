<?php
/*
 * Created by Martin Wernståhl on 2010-02-08.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Validator implements ArrayAccess
{
	/**
	 * A list of validator chains which validate a single field each,
	 * the key of this array is the field name.
	 * 
	 * @var array
	 */
	protected $chains = array();
	
	/**
	 * A list of Inject_Validator_ErrorException exceptions which were raised
	 * during validation.
	 * 
	 * @var array
	 */
	protected $errors = array();
	
	// ------------------------------------------------------------------------

	/**
	 * A way of adding a custom validator chain.
	 * 
	 * @param  string
	 * @param  Inject_Validator_Chain
	 * @return void
	 */
	public function addChain($key_name, Inject_Validator_Chain $chain)
	{
		$this->chains[$key_name] = $chain;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a validator chain for $key_name.
	 * 
	 * @param  string
	 * @return Inject_Validator_Chain
	 */
	public function chain($key_name)
	{
		if(isset($this->chains[$key_name]))
		{
			return $this->chains[$key_name];
		}
		
		return $this->chains[$key_name] = new Inject_Validator_Chain();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a validator chain to validate $key_name as a list of values.
	 * 
	 * @param  string
	 * @return Inject_Validator_ChainArray
	 */
	public function chainArray($key_name)
	{
		if(isset($this->chains[$key_name]))
		{
			return $this->chains[$key_name];
		}
		
		return $this->chains[$key_name] = new Inject_Validator_ChainArray();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validates the data in the array $data.
	 * 
	 * The result of the validation will be returned by this method while
	 * the processed values can be fetched via ArrayAccess or getProcessedData().
	 * 
	 * @return bool
	 */
	public function validate(array $data)
	{
		$status = true;
		$this->data = array();
		$this->errors = array();
		
		foreach($this->chains as $key => $chain)
		{
			$v = empty($data[$key]) ? null : $data[$key];
			
			try
			{
				$this->data[$key] = $chain->validate($v);
			}
			catch(Inject_Validator_ErrorException $e)
			{
				$status = false;
				
				$this->errors[$key] = $e;
			}
		}
		
		return $status;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the array with the processed data.
	 * 
	 * @return array
	 */
	public function getProcessedData()
	{
		return $this->data;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the error list, consists of the field name as key and the
	 * Inject_Validator_ErrorException as the value.
	 * 
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Processes the error array and uses the supplied Inject_I18n object to
	 * translate the error messages.
	 * 
	 * The messages are named as the validator method which is called
	 * (eg. isRequired) and they must use the sprintf() string format.
	 * The first string is the field name, the following are the parameters
	 * sent to the validator.
	 * 
	 * Example usage:
	 * <code>
	 * $v = new Inject_Validator();
	 * 
	 * // Add rules
	 * 
	 * if( ! $v->validate($list))
	 * {
	 *     $msgs = new Inject_I18n('Validator');
	 *     
	 *     $errors = '<ul><li>'.
	 *         implode('</li><li>', $v->createErrorMessages($msgs)).
	 *         '</li></ul>';
	 *     
	 *     echo $errors;
	 * }
	 * </code>
	 * 
	 * @param  Inject_I18n  The object containing error message translations
	 * @return array        List of messages
	 */
	public function createErrorMessages(Inject_I18n $messages)
	{
		$proc = array();
		
		foreach($this->errors as $f => $e)
		{
			$error = $e->getValidator();
			
			$proc[] = vsprintf($messages->$error, array_merge(array($f), $e->getParameters()));
		}
		
		return $proc;
	}
	
	// ------------------------------------------------------------------------

	public function offsetExists($key)
	{
		return isset($this->data[$key]);
	}
	
	// ------------------------------------------------------------------------

	public function offsetGet($key)
	{
		return $this->data[$key];
	}
	
	// ------------------------------------------------------------------------

	public function offsetSet($key, $value)
	{
		return false;
	}
	
	// ------------------------------------------------------------------------

	public function offsetUnset($key)
	{
		return false;
	}
}


/* End of file Inject.php */
/* Location: ./Users/m4rw3r/Sites/Inject-Framework/lib/Inject.php */