<?php
/*
 * Created by Martin Wernståhl on 2009-12-30.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Tools;

/**
 * Searches trough a set of paths for classes, returns a list of classes
 * and in which file they are located, does NOT include the files.
 * 
 * Throws a PHP exception if duplicate files are found, but the classes
 * can still be found by calling getClassFiles() again but won't contain
 * the duplicates (only the first occurrence of the class).
 * 
 * Example usage:
 * <code>
 * $finder = new \Inject\Tools\ClassFinder(array('.', '../src'));
 * 
 * try
 * {
 *     $classes = $finder->getClassFiles();
 * }
 * catch(\Inject\Tools\ClassConflictException $e)
 * {
 *     // Take note of the error:
 *     echo "Conflicting classes!";
 *     
 *     // Let the operation continue, but might contain the wrong class-file:
 *     $classes = $finder->getClassFiles();
 * }
 * </code>
 */
class ClassFinder
{
	
	/**
	 * The list containing classes and their filenames.
	 * 
	 * @var array(string => string)  Class => filename
	 */
	protected $list = array();
	
	/**
	 * The regex determining which files to search.
	 * 
	 * @var string
	 */
	protected $file_regex;
	
	/**
	 * A list of paths to search.
	 * 
	 * @var array(string)
	 */
	protected $paths = array();
	
	// ------------------------------------------------------------------------
	
	/**
	 * @param  string|array
	 * @param  string
	 * @param  int
	 */
	function __construct($paths = '.', $file_regex = '/\.php$/')
	{
		foreach((Array) $paths as $p)
		{
			$this->paths[] = realpath($p);
		}
		
		// array_unique() will fix problems with eg. PHP's include path
		$this->paths      = array_unique($this->paths);
		$this->file_regex = $file_regex;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Searches the supplied paths for the files and returns a list of classes
	 * and in which file they're located.
	 * 
	 * @return array(class => file)
	 * @throws ClassConflictException if class names conflict. But by calling
	 *         this method again, you will receive all the classes excluding the
	 *         duplicated ones (the first occurrence is still in the return value)
	 */
	public function getClassFiles()
	{
		if( ! empty($this->list))
		{
			return $this->list;
		}
		
		$conflicts = array();
		
		foreach($this->paths as $path)
		{
			// Search the folder
			$diriter  = new \RecursiveDirectoryIterator($path);
			$iteriter = new \RecursiveIteratorIterator($diriter, \RecursiveIteratorIterator::LEAVES_ONLY);
			$files    = new \RegexIterator($iteriter, $this->file_regex);
			
			foreach($files as $name => $file)
			{
				foreach($this->getClasses($name) as $class)
				{
					if(isset($this->list[$class]))
					{
						$conflicts[] = array('class' => $class, 'file' => $name);
					}
					
					$this->list[$class] = $name;
				}
			}
		}
		
		if( ! empty($conflicts))
		{
			throw new ClassConflictException($conflicts);
		}
		
		return $this->list;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Tokenizes the file and iterates all tokens in search of classes.
	 * 
	 * @param  string         Path to file
	 * @return array(string)  List of classnames
	 */
	protected function getClasses($filepath)
	{
		$tokens = token_get_all(file_get_contents($filepath));
		
		$classes          = array();
		$is_classname     = false;
		$is_namespace     = false;
		$inside_namespace = false;
		$indentation      = 0;
		$current_ns       = null;
		
		foreach($tokens as $token)
		{
			if( ! is_array($token))
			{
				// we're only interested in brackets
				switch($token)
				{
					case '{':
						$indentation++;
						break;
						
					case '}':
						$indentation--;
						break;
				}
				
				// no class name or namespace name can follow brackets
				$is_classname = false;
				$is_namespace = false;
				
				continue;
			}
			
			switch($token[0])
			{
				case T_WHITESPACE:
					// No need to count
					break;
					
				case T_CLASS:
				case T_INTERFACE:
					// Next is a classname
					$is_classname = true;
					break;
					
				case T_NAMESPACE:
					// Next is a namespace and we're inside it
					$is_namespace = true;
					$inside_namespace = true;
					
					// reset so we're sure that we get an empty namespace if the user decides
					// to create one ("namespace;"):
					$current_ns = '';
					break;
					
				case T_STRING:
					if($is_classname)
					{
						// Found a class, add the namespace if we have one (which isn't the global, "empty" namespace)
						$classes[] = ($inside_namespace && ! empty($current_ns) ? $current_ns.'\\' : '').$token[1];
					}
					// namespaces cannot be within indentation
					elseif($is_namespace && $indentation == 0)
					{
						$current_ns .= $token[1];
						continue;
					}
					// Intentionally no break
					
				case T_NS_SEPARATOR:
					// Allow multple levels of namespaces
					if($is_namespace && $indentation == 0)
					{
						$current_ns .= $token[1];
						continue;
					}
					// Intentionally no break
					
				default:
					// Something else, not a namespace or class
					$is_classname = false;
					$is_namespace = false;
			}
		}
		
		return $classes;
	}
}


/* End of file ClassFinder.php */
/* Location: src/php/Inject/Tools */