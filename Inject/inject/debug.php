<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Debug
{
	
	public $sections = array();
	
	public $extra_data = array();
	
	// ------------------------------------------------------------------------

	/**
	 * Sets a header
	 * 
	 * @param  string
	 * @param  mixed
	 * @return void
	 */
	public function set($header, $value)
	{
		$this->sections[$header] = $value;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function output()
	{
		// TOOD: Make it handle different request types
		$str = "<pre>===============<br />== DEBUGGING ==<br />===============<br /><br />";
		
		foreach($this->sections as $h => $v)
		{
			if( ! is_scalar($v))
			{
				$str .= "$h : ";
				$str .= htmlentities(print_r($v, true));
			}
			else
			{
				$str .= "$h : $v<br />";
			}
		}
		
		foreach($this->extra_data as $h => $v)
		{
			if( ! is_scalar($v))
			{
				$str .= "$h : ";
				$str .= htmlentities(print_r($v, true));
			}
			else
			{
				$str .= "$h : $v<br />";
			}
		}
		
		$str .= 'Included files : ' . htmlentities(print_r(get_included_files(), true));
		
		return $str;
	}
}


/* End of file debug.php */
/* Location: ./Inject/inject */