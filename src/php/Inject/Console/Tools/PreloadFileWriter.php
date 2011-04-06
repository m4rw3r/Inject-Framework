<?php
/*
 * Created by Martin Wernståhl on 2009-12-28.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Console\Tools;

/**
 * Creates a preload file which contains many of the commonly used classes
 * from the framework (fetched from the paths specified).
 */
class PreloadFileWriter
{
	/**
	 * List of classes to include, includes all if empty.
	 * 
	 * @var array(string)
	 */
	protected $classes = array();
	
	/**
	 * List of class => file mappings.
	 * 
	 * @var array(string => string)
	 */
	protected $files = array();
	
	/**
	 * @param  array(string)  The classes which are to be included in the preload file
	 * @param  array(string => string)  Class => Filepath mappings
	 */
	public function __construct(array $classes = array(), array $files = array())
	{
		// Store the classes
		$this->classes = $classes;
		$this->files = $files;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds an additional class to the list of classes to add to the preload file.
	 * 
	 * @param  string
	 * @return self
	 */
	public function addClass($class)
	{
		if( ! in_array($class, $this->classes))
		{
			$this->classes[] = $class;
		}
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds a list of classes to the list of classes to add to the preload file.
	 * 
	 * @param  string
	 * @return self
	 */
	public function addClasses(array $classes)
	{
		foreach($classes as $class)
		{
			$this->addClass($class);
		}
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the PHP code which is adding the cache to the Inject loader.
	 * 
	 * @return string
	 */
	public function getPHP()
	{
		$str = '';
		
		if(empty($this->classes))
		{
			foreach($this->files as $class => $file)
			{
				$str .= $this->getCleanFileContents($file)."\n";
			}
		}
		else
		{
			$files = array();
			
			foreach($this->classes as $class)
			{
				if(isset($this->files[$class]))
				{
					in_array($this->files[$class], $files) OR $files[] = $this->files[$class];
				}
				else
				{
					throw new \Exception(sprintf('Cannot find class "%s" in the declared paths.', $class));
				}
			}
			
			foreach($files as $file)
			{
				$str .= $this->getCleanFileContents($file)."\n"; 
			}
		}
		
		return $str;
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
 * Preload file generated for Inject Framework.
 * 
 * Include this file instead of the main Inject framework
 * files. Then everything will work as usual.
 *
 * Generated on '.date('Y-m-d H:i:s').'
 */

'.$this->getPHP());
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Removes the comments, empty lines, start and end tags from the files
	 * 
	 * @param  string
	 * @return string
	 */
	private function getCleanFileContents($filename)
	{
		$file_contents = str_replace("\r\n", "\n", file_get_contents($filename));
		$tokens = token_get_all($file_contents);
		$new_contents = '';
		
		// Get rid of all the comments
		while(list(, $v) = each($tokens))
		{
			if( ! is_array($v))
			{
				$new_contents .= $v;
				
				continue;
			}
			
			if( ! in_array($v[0], array(T_COMMENT, T_DOC_COMMENT)))
			{
				$new_contents .= $v[1];	
			}
			else
			{
				// This fixes newlines as they sometimes are at the end of comments
				if(substr($v[1], -1) === "\n")
				{
					$new_contents .= "\n";
				}
			}
		}
		
		// Reparse, to merge all the new whitespace we've got from removing
		// the comments
		$tokens = token_get_all($new_contents);
		$new_contents = '';
		
		// Now take care of the newlines, only one consecutive newline allowed
		while(list(, $v) = each($tokens))
		{
			if( ! is_array($v))
			{
				$new_contents .= $v;
				
				continue;
			}
			
			switch($v[0])
			{
				case T_WHITESPACE:
					// Remove all but the last newline in the whitespace
					if(($p = strrpos($v[1], "\n")) !== false)
					{
						$v[1] = substr($v[1], $p);
					}
					$new_contents .= $v[1];
					break;
				
				case T_OPEN_TAG:
				case T_CLOSE_TAG:
					// Ignore
					break;
					
				case T_FILE:
					// Let the __FILE__ constant refer to the original file
					$new_contents .= '\''.addcslashes($filename, '\'').'\'';
					break;
					
				case T_DIR:
					// Let the __DIR__ constant refer to the original directory
					$new_contents .= '\''.addcslashes(dirname($filename), '\'').'\'';
					break;
					
				default:
					$new_contents .= $v[1];
			}
		}
		
		// Remove remaining whitespace at both ends
		return trim($new_contents);
	}
}


/* End of file PreloadFileWriter.php */
/* Location: src/php/Inject/Console/Tools */