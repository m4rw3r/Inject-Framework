<?php
/*
 * Created by Martin Wernståhl on 2009-12-30.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Console\Tools;

/**
 * Searches trough a set of paths for classes, returns a list of classes
 * and in which file they are located.
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
	 * A list of paths to search
	 */
	protected $paths = array();
	
	function __construct($paths = '.', $file_regex = '/\.php$/')
	{
		$this->paths = (Array) $paths;
		$this->file_regex = $file_regex;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Searches the supplied paths for the files and returns a list of classes
	 * and in which file they're located.
	 * 
	 * @return array(class => file)
	 */
	public function getClassFiles()
	{
		if( ! empty($this->list))
		{
			return $this->list;
		}
		
		foreach($this->paths as $path)
		{
			$path = realpath($path);
			
			// Search the folder
			$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
			
			foreach($files as $name => $file)
			{
				if(preg_match($this->file_regex, $name))
				{
					foreach($this->getClasses($name) as $class)
					{
						$this->list[$class] = $name;
					}
				}
			}
		}
		
		return $this->list;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Tokenizes the file and iterates all tokens in search of classes.
	 * 
	 * @param  string
	 * @return array
	 */
	protected function getClasses($filepath)
	{
		$tokens = token_get_all(file_get_contents($filepath));
		
		$classes = array();
		$is_classname = false;
		$is_namespace = false;
		$inside_namespace = false;
		$indentation = 0;
		$current_ns = null;
		
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
				case T_NS_SEPARATOR:
					// Allow multple levels of namespaces
					if($is_namespace && $indentation == 0)
					{
						$current_ns .= $token[1];
						continue;
					}
				default:
					// Something else, not a namespace or class
					$is_classname = false;
					$is_namespace = false;
			}
		}
		
		return $classes;
	}
}


/* End of file CacheWriter.php */
/* Location: src/php/Inject/Console/Tools */