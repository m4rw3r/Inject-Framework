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
	protected $file_regex = '/\.php$/';
	
	protected $paths = array();
	
	protected $list = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @param  array
	 */
	public function __construct(array $paths)
	{
		$this->paths = $paths;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Scans the directories for classes and constructs an internal array.
	 * 
	 * @return int
	 */
	function scan()
	{
		foreach($this->paths as $path)
		{
			$len = strlen($path);
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
			
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
		
		return count($this->list);
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
		$classes = array();
		$code = file_get_contents($filepath);
		$tokens = token_get_all($code);
		$is_classname = false;
		
		foreach($tokens as $token)
		{
			if( ! is_array($token))
			{
				$is_classname = false;
				
				continue;
			}
			
			switch($token[0])
			{
				case T_WHITESPACE:
					break;
				case T_CLASS:
					$is_classname = true;
					break;
				case T_STRING:
					if($is_classname)
					{
						$classes[] = $token[1];
					}
				default:
					$is_classname = false;
			}
		}
		
		return $classes;
	}
}


/* End of file URI.php */
/* Location: ./Users/m4rw3r/Sites/Inject-Framework/lib/Inject/URI.php */