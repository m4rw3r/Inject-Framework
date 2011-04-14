<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\RouterGenerator;

/**
 * 
 */
class ConditionSegment implements \ArrayAccess
{
	protected $condition;
	
	protected $destination;
	
	protected $children = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct($condition = null)
	{
		$this->condition = $condition;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates the code for this condition.
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
				$ret = "\t\$matches[] = \$match;\n$ret";
				
				empty($this->destination) && $ret .= "\n\tarray_pop(\$matches);";
			}
			
			$ret = "if({$this->condition})\n{\n{$ret}\n}";
		}
		
		return $ret;
	}
	
	// ------------------------------------------------------------------------
	
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
	
	public function offsetExists($key)
	{
		return isset($this->children[$key]);
	}
	
	// ------------------------------------------------------------------------
	
	public function offsetGet($key)
	{
		return $this->children[$key];
	}
	
	// ------------------------------------------------------------------------
	
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