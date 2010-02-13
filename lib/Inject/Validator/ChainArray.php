<?php
/*
 * Created by Martin Wernståhl on 2010-02-13.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * A validator chain which validates arrays and their contents.
 */
class Inject_Validator_ChainArray extends Inject_Validator_Chain
{
	/**
	 * As $validations, but for validating the whole array instead of a single element.
	 * 
	 * @var array
	 */
	protected $array_validations = array();
	
	/**
	 * If to skip empty array elements.
	 * 
	 * @var bool
	 */
	protected $skip_empty = true;
	
	// ------------------------------------------------------------------------

	/**
	 * Validates the values in the array named $key.
	 * 
	 * @param  array|string
	 * @return array
	 */
	public function validate($data)
	{
		if( ! $this->required && empty($data))
		{
			return array();
		}
		
		// Make sure it is an array
		if( ! is_array($data))
		{
			$data = array($data);
		}
		
		// If we skip empty elements, remove them now
		if($this->skip_empty)
		{
			// Remove empty array elements
			foreach($data as $k => $v)
			{
				if(empty($v))
				{
					unset($data[$k]);
				}
			}
			
			// Fix for empty array, as we don't validate empty elements
			if(empty($data) && $this->required)
			{
				$this->validateIsRequired('');
			}
		}
		
		// Validate the array
		foreach($this->array_validations as $callback)
		{
			$params = array_merge(array($data), $callback[1]);
			$callback = $callback[0];
			
			$r = call_user_func_array($callback, $params);
			
			if( ! (is_bool($r) OR is_null($r)))
			{
				$data = $r;
			}
		}
		
		// Validate individual elements
		foreach($data as $k => $v)
		{
			$data[$k] = parent::validate($v);
		}
		
		return $data;
	}
	
	
	// ------------------------------------------------------------------------

	/**
	 * Sets if this validator chain should omit empty values from the validation,
	 * default: yes.
	 * 
	 * @param  bool
	 * @return self
	 */
	public function skipEmpty($value = true)
	{
		$this->skip_empty = $value;
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validates that the array contains at most $num number of elements.
	 * 
	 * @param  int
	 * @return self
	 */
	public function maxElements($num)
	{
		$this->array_validations[] = array(array($this, 'validateMaxElements'), array($num));
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validates that the array contains at least $num number of elements.
	 * 
	 * @param  int
	 * @return self
	 */
	public function minElements($num)
	{
		$this->array_validations[] = array(array($this, 'validateMinElements'), array($num));
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validator for maxElements().
	 * 
	 * @param  array
	 * @param  int
	 * @return void
	 */
	public function validateMaxElements($array, $num)
	{
		if(count($array) > $num)
		{
			throw new Inject_Validator_ErrorException('maxElements', $num);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Validator for minElements().
	 * 
	 * @param  array
	 * @param  int
	 * @return void
	 */
	public function validateMinElements($array, $num)
	{
		if(count($array) < $num)
		{
			throw new Inject_Validator_ErrorException('minElements', $num);
		}
	}
}


/* End of file ChainArray.php */
/* Location: ./lib/Inject/Validator */