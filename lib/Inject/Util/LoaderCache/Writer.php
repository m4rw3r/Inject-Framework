<?php
/*
 * Created by Martin Wernståhl on 2009-12-28.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Util_LoaderCache_Writer
{
	/**
	 * The regex determining which files to search.
	 * 
	 * @var string
	 */
	protected $file_regex = '/\.php$/';
	
	/**
	 * A list of paths to search, the most important last.
	 * 
	 * @var array
	 */
	protected $paths = array();
	
	/**
	 * The list of found classes, class => file.
	 * 
	 * @var array
	 */
	protected $list = array();
	
	/**
	 * The token constant for T_NAMESPACE, filled with a dummy value for PHP < 5.3.
	 * 
	 * @var int
	 */
	protected $namespace_token = false;
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @param  array
	 */
	public function __construct(array $paths)
	{
		$this->paths = $paths;
		
		// Use constant() to prevent compiler errors, if not PHP > 5.3 use a random
		// (huge) number to prevent errors:
		$this->namespace_token = version_compare(PHP_VERSION, '5.3', '>=') ? constant('T_NAMESPACE') : 476389246;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Scans the directories for classes and constructs an internal array.
	 * 
	 * @return int		The number of found classes
	 */
	function scan()
	{
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
		
		return $c;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the PHP code which is adding the cache to the Inject loader.
	 * 
	 * @return string
	 */
	public function getPHP()
	{
		return 'Inject::setLoaderCache('.var_export($this->list, true).');';
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


/* End of file Writer.php */
/* Location: ./lib/Inject/Utils/LoaderWriter */