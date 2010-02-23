<?php
/*
 * Created by Martin Wernståhl on 2010-02-08.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * A validator chain which validates string values.
 */
class Inject_Validator_Chain
{
	/**
	 * A list containing callbacks to execute for validation.
	 * 
	 * Callback format:
	 * <code>
	 * array(array(object, 'methodname'), array(param1, param2, ...))
	 * array(array('class', 'methodname'), array(param1, param2, ...))
	 * array(array(object, 'methodname'), array())
	 * array(array('class', 'methodname'), array())
	 * array('function', array([params]))
	 * array(Closure, array([params]))
	 * </code>
	 * 
	 * @var array
	 */
	protected $validations = array();
	
	/**
	 * Property to tell if we should require this value, if not and it is empty,
	 * skip validation.
	 */
	protected $required = false;
	
	/**
	 * The parent Inject_Validator object, only populated during validation.
	 * 
	 * @var Inject_Validator
	 */
	protected $parent = null;
	
	// ------------------------------------------------------------------------

	/**
	 * Validates the value of the $key.
	 * 
	 * @param  string
	 * @param  Inject_Validator
	 * @return string
	 */
	public function validate($data, Inject_Validator $parent)
	{
		if( ! $this->required && empty($data))
		{
			return $data;
		}
		
		$this->parent = $parent;
		
		foreach($this->validations as $callback)
		{
			// Get parameters, and add the value first
			$params = array_merge(array($data), $callback[1]);
			
			$callback = $callback[0];
			
			// Do call
			$r = call_user_func_array($callback, $params);
			
			// If it is string, then it will be the new data
			if( ! (is_bool($r) OR is_null($r)))
			{
				$data = $r;
			}
		}
		
		$this->parent = null;
		
		return $data;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validates that the field contains something.
	 * 
	 * @return self
	 */
	public function isRequired()
	{
		$this->required = true;
		
		$this->validations[] = array(array($this, 'validateIsRequired'), array());
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validates that the field contains a natural number [0-9].
	 * 
	 * @return self
	 */
	public function isNaturalNumber()
	{
		$this->validations[] = array(array($this, 'validateIsNaturalNumber'), array());
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validates that the field only contains [a-zA-Z].
	 * 
	 * @return self
	 */
	public function isAlpha()
	{
		$this->validations[] = array(array($this, 'validateIsAlpha'), array());
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validates that the field only contains [a-zA-Z0-9].
	 * 
	 * @return self
	 */
	public function isAlphaNumeric()
	{
		$this->validations[] = array(array($this, 'validateIsAlphaNumeric'), array());
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validates that the field contains a (syntactically) valid email address.
	 * 
	 * @return self
	 */
	public function isValidEmail()
	{
		$this->validations[] = array(array($this, 'validateIsValidEmail'), array());
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validates that the field is at maximum $num characters long.
	 * 
	 * @param  int
	 * @return self
	 */
	public function maxLength($num)
	{
		$this->validations[] = array(array($this, 'validateMaxLength'), array($num));
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validates that the field is at least $num characters long.
	 * 
	 * @param  int
	 * @return self
	 */
	public function minLength($num)
	{
		$this->validations[] = array(array($this, 'validateMinLength'), array($num));
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validates that the field has the exact length $num.
	 * 
	 * @param  int
	 * @return self
	 */
	public function exactLength($num)
	{
		$this->validations[] = array(array($this, 'validateExactLength'), array($num));
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Trims the contents of the field.
	 * 
	 * @return self
	 */
	public function trim()
	{
		$this->validations[] = array('trim', array());
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validates that the field's contents is identical to another field.
	 * 
	 * NOTE: The other field must be validated first.
	 * 
	 * @param  string
	 * @return self
	 */
	public function matches($fieldname)
	{
		$this->validations[] = array(array($this, 'validateMatches'), array($fieldname));
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds a callback to process the field value.
	 * 
	 * To make it validate the value, throw an
	 * Inject_Validator_ErrorException if the validation fails.
	 * 
	 * If no string is returned from the callback, then the value will be unmodified.
	 * 
	 * @param  callback|Closure
	 * @param  array       Extra parameters to send to the callback, the callback
	 *                     will receive the field value first, then the extra parameters.
	 * @return self
	 */
	public function callback($callback, $params = array())
	{
		$this->validations[] = array($callback, $params);
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validator for isAlpha().
	 * 
	 * @return void
	 */
	protected function validateIsAlpha($value)
	{
		if( ! preg_match('/^[a-z]+$/i', $value))
		{
			throw new Inject_Validator_ErrorException('isAlpha');
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validator for isAlphaNumeric().
	 * 
	 * @return void
	 */
	protected function validateIsAlphaNumeric($value)
	{
		if( ! preg_match('/^[a-z0-9]+$/i', $value))
		{
			throw new Inject_Validator_ErrorException('isAlphaNumeric');
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validator for isNaturalNumber().
	 * 
	 * @return void
	 */
	protected function validateIsNaturalNumber($value)
	{
		if( ! preg_match('/^[0-9]+$/', $value))
		{
			throw new Inject_Validator_ErrorException('isNaturalNumber');
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validator for isValidEmail().
	 * 
	 * @return void
	 */
	protected function validateIsValidEmail($value)
	{
		if( ! preg_match('/^[A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+(?:[A-Z]{2}|com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum)$/i', $value))
		{
			throw new Inject_Validator_ErrorException('isValidEmail');
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validator for isRequired().
	 * 
	 * @return void
	 */
	protected function validateIsRequired($value)
	{
		if(empty($value))
		{
			throw new Inject_Validator_ErrorException('isRequired');
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validator for maxLength().
	 * 
	 * @param  string
	 * @param  int
	 * @return void
	 */
	protected function validateMaxLength($value, $len)
	{
		if(Utf8::strlen($value) > $len)
		{
			throw new Inject_Validator_ErrorException('maxLength', $len);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validator for minLength().
	 * 
	 * @param  string
	 * @param  int
	 * @return void
	 */
	protected function validateMinLength($value, $len)
	{
		if(Utf8::strlen($value) < $len)
		{
			throw new Inject_Validator_ErrorException('minLength', $len);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validator for exactLength().
	 * 
	 * @param  string
	 * @param  int
	 * @return void
	 */
	protected function validateExactLength($value, $len)
	{
		if(Utf8::strlen($value) == $len)
		{
			throw new Inject_Validator_ErrorException('minLength', $len);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validator for matches().
	 * 
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function validateMatches($field, $fieldname)
	{
		// Forward to Inject_Validator as it has the field contents
		$this->parent->validateMatches($field, $fieldname);
	}
}


/* End of file Validator.php */
/* Location: ./Users/m4rw3r/Sites/Inject-Framework/lib/Inject/Validator.php */