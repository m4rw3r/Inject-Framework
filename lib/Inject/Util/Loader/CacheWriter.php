<?php
/*
 * Created by Martin Wernståhl on 2009-12-28.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Creates a cache for the Inject::load() autoloader.
 * 
 * Usage:
 * <code>
 * $cw = new Inject_Util_Loader_Cachewriter();
 * 
 * // Getting the code to paste in index.php:
 * echo $cw->getPHP();
 * 
 * // Writing to an external file
 * $cw->writeFile('file_path_name');
 * 
 * // Modifying the index.php file automatically:
 * $cw->writeIndex('path/to/index.php');
 * </code>
 */
class Inject_Util_Loader_CacheWriter
{
	/**
	 * The object which is searching for the classes and files.
	 * 
	 * @var Inject_Util_Loader_ClassFinder
	 */
	protected $class_finder;
	
	/**
	 * @param  array
	 */
	public function __construct(array $paths = array())
	{
		if(empty($paths))
		{
			// Array reverse is needed, to let the app paths override in the correct order
			$paths = array_merge(array(Inject::getFrameworkPath()), array_reverse(Inject::getApplicationPaths()));
		}
		
		// Create the class finder which will get the class list
		$this->class_finder = new Inject_Util_Loader_ClassFinder($paths);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the PHP code which is adding the cache to the Inject loader.
	 * 
	 * @return string
	 */
	public function getPHP()
	{
		return 'Inject::setLoaderCache('.var_export($this->class_finder->getClassFiles(), true).');';
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Writes a PHP cache file which contains the class => file mappings and assigns
	 * it to the Inject class.
	 * 
	 * Overwrites existing files.
	 * 
	 * @param  string
	 * @return int		Number of bytes written
	 */
	public function writeFile($filename)
	{
		return file_put_contents($filename, '<?php
/*
 * Autoloader cache for Inject::load().
 * Generated on '.date('Y-m-d H:i:s').'
 */

'.$this->getPHP());
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds the code to the index file, replaces existing loader cache if present,
	 * the data is added just below the Inject::init() method call.
	 * 
	 * @param  string
	 * @return int
	 */
	public function writeIndex($index_file)
	{
		$c = file_get_contents($index_file);
		
		if(empty($c))
		{
			throw new Exception('Cannot read file "'.$index_path.'", file is either empty or missing.');
		}
		
		$c = $this->processIndex($c);
		
		Inject::log('LoaderCache', 'Writing index file "'.$index_file.'".', Inject::DEBUG);
		
		file_put_contents($index_file, $c);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Removes existing Inject::setLoaderCache() call and adds a new one.
	 * 
	 * @param  string
	 * @return string
	 */
	public function processIndex($string)
	{
		Inject::log('LoaderCache', 'Processing index file.', Inject::DEBUG);
		
		$tokens = token_get_all($string);
		$filtered = array();
		
		// filter for existing cache
		for($i = 0, $c = count($tokens); $i < $c; $i++)
		{
			if( ! is_array($tokens[$i]))
			{
				// Not to be filtered
				$filtered[] = $tokens[$i];
			}
			elseif($tokens[$i][0] == T_STRING && $tokens[$i][1] == 'Inject' && 
				isset($tokens[$i+1][0]) && $tokens[$i+1][0] == T_DOUBLE_COLON &&
				isset($tokens[$i+2][0]) && $tokens[$i+2][0] == T_STRING &&
				$tokens[$i+2][1] == 'setLoaderCache')
			{
				// Found the Inject::setLoaderCache call
				
				// remove previous whitespace, if any
				$e = end($filtered);
				if(isset($e[0]) && $e[0] == T_WHITESPACE)
				{
					array_pop($filtered);
				}
				
				// step forward so we scan the next token
				$i += 3;
				
				// skip all tokens until next ";"
				while(is_array($tokens[$i]) OR $tokens[$i] != ';')
				{
					$i++;
				}
			}
			else
			{
				// Not to be filtered
				$filtered[] = $tokens[$i];
			}
		}
		
		$result = '';
		
		// Assemble the new file content
		while(list(, $v) = each($filtered))
		{
			if( ! is_array($v))
			{
				$result .= $v;
			}
			elseif($v[0] == T_STRING && $v[1] == 'Inject')
			{	
				// got Inject
				$result .= 'Inject';
				
				if((list(, $v2) = each($filtered)) && isset($v2[0]) && $v2[0] == T_DOUBLE_COLON)
				{
					// got Inject::
					$result .= '::';
					
					if((list(, $v3) = each($filtered)) && 
						isset($v3[0]) && $v3[0] == T_STRING &&
						$v3[1] == 'init')
					{
						// got Inject::init
						$result .= 'init';
						
						// add everything to the next ";"
						while((list(, $v4) = each($filtered)) && is_array($v4) OR $v4 != ';')
						{
							$result .= $v4;
						}
						
						// Add the ";"
						$result .= ';';
						
						// Insert the PHP code + whitespace
						$result .= "\n\n".$this->getPHP();
					}
					else
					{
						// no match
						$result .= is_array($v3) ? $v3[1] : $v3;
					}
				}
				else
				{
					// not a match
					$result .= is_array($v2) ? $v2[1] : $v2;
				}
			}
			else
			{
				$result .= $v[1];
			}
		}
		
		Inject::log('LoaderCache', 'Processing index file - DONE', Inject::DEBUG);
		
		return $result;
	}
}


/* End of file Writer.php */
/* Location: ./lib/Inject/Utils/LoaderWriter */