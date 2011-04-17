<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\RouterGenerator;

/**
 * Represents an if() condition in the generated router code.
 */
class ConditionSegment implements \ArrayAccess
{
	/**
	 * The raw PHP code for the condition.
	 * 
	 * @var string
	 */
	protected $condition;
	
	/**
	 * The raw PHP destination code, run if this ConditionSegment matches and
	 * no sub-segments matches.
	 * 
	 * @var string
	 */
	protected $destination;
	
	/**
	 * A list of sub-segments ConditionSegment:s, will be nested inside this
	 * if() and before this segment's destination, if any.
	 * 
	 * @var array(ConditionSegment)
	 */
	protected $children = array();
	
	// ------------------------------------------------------------------------

	/**
	 * @param  string  The raw PHP code for the condition inside the if()
	 */
	public function __construct($condition = null)
	{
		$this->condition = $condition;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates the PHP code for this condition.
	 * 
	 * @return string
	 */
	public function createCode()
	{
		$code = array();
		
		foreach($this->children as $child)
		{
			$code[] = $child->createCode();
		}
		
		empty($this->destination) OR $code[] = $this->destination;
		
		$ret = CodeGenerator::indentCode(implode("\n\n", $code));
		
		if( ! empty($this->condition))
		{
			if(preg_match('/\bpreg_match\(/u', $this->condition))
			{
				$ret = "\t\$matches[] = \$match;\n$ret\n\tarray_pop(\$matches);";
			}
			
			$ret = "if({$this->condition})\n{\n{$ret}\n}";
		}
		
		return $ret;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Sets the destination code for this condition.
	 * 
	 * @param  string  Raw PHP code to be run if this condition matches and no
	 *                 sub-conditions does
	 */
	public function setDestination($dest)
	{
		$this->destination = $dest;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns the destination code.
	 * 
	 * @return string
	 */
	public function getDestination()
	{
		return $this->destination;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns true if it has a destination.
	 * 
	 * @return boolean
	 */
	public function hasDestination()
	{
		return ! empty($this->destination);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Accessor to interact with sub-segments.
	 * 
	 * @return boolean
	 */
	public function offsetExists($key)
	{
		return isset($this->children[$key]);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Accessor to interact with sub-segments.
	 * 
	 * @return ConditionSegment
	 */
	public function offsetGet($key)
	{
		return $this->children[$key];
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Accessor to interact with sub-segments.
	 * 
	 * @param  string
	 * @param  ConditionSegment
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		if( ! $value instanceof ConditionSegment)
		{
			throw new \RuntimeException('ConditionSegment[]= must receive a ConditionSegment instance.');
		}
		
		$this->children[$key] = $value;
	}
	
	// ------------------------------------------------------------------------
	
	public function offsetUnset($key)
	{
		throw new \RuntimeException('Cannot remove ConditionSegments.');
	}
}


/* End of file CodeGenerator.php */
/* Location: src/php/Inject/Web/Router/Generator */