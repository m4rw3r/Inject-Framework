<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Tools;

/**
 * Exception telling that a conflicting class name was discovered
 * by the class finder.
 */
class ClassConflictException extends \RuntimeException implements \Inject\Exception
{
	protected $conflicts = array();
	protected $classes   = array();
	protected $files     = array();
	
	/**
	 * @param  array(array('class' => string, 'file' => string), ...)
	 */
	function __construct(array $conflicts)
	{
		$this->conflicts = $conflicts;
		
		$this->classes = array_map(function($elem)
		{
			return $elem['class'];
		}, $this->conflicts);
		
		$this->files = array_map(function($elem)
		{
			return $elem['file'];
		}, $this->conflicts);
		
		$classlist = implode(', ', $this->classes);
		$filelist  = implode(', ', $this->files);
		
		// TODO: Is this threshold good?
		if(strlen($classlist) > 40)
		{
			$classlist = substr($classlist, 0, 60).'...';
		}
		
		// TODO: Is this threshold good?
		if(strlen($filelist) > 40)
		{
			$filelist = substr($filelist, 0, 60).'...';
		}
		
		parent::__construct('ClassFinder: Found conflicting class(es): '.$classlist.' in files: '.$filelist);
		
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of unique class names which were conflicted.
	 * 
	 * @return array(string)
	 */
	public function getConflictingClasses()
	{
		return array_unique($this->classes);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of files containing conflicting classes.
	 * 
	 * @return array(string)
	 */
	public function getConflictingFiles()
	{
		return array_unique($this->files);
	}
}



/* End of file ClassConflictException.php */
/* Location: src/php/Inject/Tools */