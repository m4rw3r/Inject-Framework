<?php
/*
 * Created by Martin Wernståhl on 2009-12-30.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Searches trough a set of paths for classes, returns a list of classes
 * and in which file they are located.
 */
class Inject_Util_Loader_ClassFinder
{
	/**
	 * The regex determining which files to search.
	 * 
	 * @var string
	 */
	protected $file_regex;
	
	/**
	 * The token constant for T_NAMESPACE, filled with a dummy value for PHP < 5.3.
	 * 
	 * @var int
	 */
	protected $namespace_token = false;
	
	/**
	 * A list of paths to search
	 */
	protected $paths = array();
	
	function __construct($paths = '.', $file_regex = '/\.php$/')
	{
		$this->paths = (Array) $paths;
		$this->file_regex = $file_regex;
		
		// Use constant() to prevent compiler errors, if not PHP > 5.3 use a random
		// (huge) number to prevent errors:
		$this->namespace_token = version_compare(PHP_VERSION, '5.3', '>=') ? constant('T_NAMESPACE') : 476389246;
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
		
		Inject::log('LoaderWriter', 'Scanning folders for classes.', Inject::DEBUG);
		
		foreach($this->paths as $path)
		{
			$len = strlen($path);
			
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
			
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
		
		Inject::log('LoaderWriter', 'Found '.($c = count($this->list)).' files.', Inject::DEBUG);
		
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
				switch($token)
				{
					case '{':
						$indentation++;
						break;
						
					case '}':
						$indentation--;
						break;
				}
				
				$is_classname = false;
				$is_namespace = false;
				
				continue;
			}
			
			switch($token[0])
			{
				case T_WHITESPACE:
					break;
					
				case T_CLASS:
				case T_INTERFACE:
					$is_classname = true;
					break;
					
				// case T_NAMESPACE:
				case $this->namespace_token:
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
						$classes[] = ($inside_namespace && ! empty($current_ns) ? '\\'.$current_ns.'\\' : '').$token[1];
					}
					// namespaces cannot be within indentation
					elseif($is_namespace && $indentation == 0)
					{
						$current_ns = $token[1];
					}
					
				default:
					$is_classname = false;
					$is_namespace = false;
			}
		}
		
		return $classes;
	}
}


/* End of file CacheWriter.php */
/* Location: ./Users/m4rw3r/Sites/Inject-Framework/lib/Inject/Util/Loader/CacheWriter.php */