<?php
/*
 * Created by Martin Wernståhl on 2009-12-28.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Creates a preload file which contains many of the commonly used classes
 * from the framework (fetched from the paths specified).
 */
class Inject_Util_Loader_PreloadFileWriter
{
	/**
	 * The object which is searching for the classes and files.
	 * 
	 * @var Inject_Util_Loader_ClassFinder
	 */
	protected $class_finder;
	
	/**
	 * @param  array  The classes which are to be included in the preload file
	 * @param  array
	 */
	public function __construct(array $classes = array(), array $paths = array())
	{
		if(empty($classes))
		{
			// Default list of classes to include in the file
			$classes = array(
				'Inject',
				'Inject_ApplicationInterface',
				'Inject_Application_Default',
				'Inject_LoggerInterface',
				'Inject_Logger_File',
				'Inject_Dispatcher',
				'Inject_Library',
				'Inject_Request',
				'Inject_Request_HTTP',
				'Inject_Request_HTTP_URI',
				'Inject_Request_HTTP_URI_RouterInterface',
				'Inject_URI',
				'Inject_Response',
				'Inject_Controller_Base',
				'Inject_Controller',
				'Inject_Controller_RenderInterface',
				'Inject_Controller_Renderer_PHP'
			);
		}
		
		if(empty($paths))
		{
			// Array reverse is needed, to let the app paths override in the correct order
			$paths = array_merge(array(Inject::getFrameworkPath()), array_reverse(Inject::getApplicationPaths()));
		}
		
		// Create the class finder which will get the class list
		$this->class_finder = new Inject_Util_Loader_ClassFinder($paths);
		
		// Store the classes
		$this->classes = $classes;
		
		// Use constant() to prevent compiler errors, if not PHP > 5.3 use a random
		// (huge) number to prevent errors:
		$this->namespace_token = version_compare(PHP_VERSION, '5.3', '>=') ? constant('T_NAMESPACE') : 47632389246;
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
		if(array_search($class, $this->classes) === false)
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
		$files = $this->class_finder->getClassFiles();
		$str = '';
		
		foreach($this->classes as $class)
		{
			if(isset($files[$class]))
			{
				$str .= $this->getCleanFileContents($files[$class])."\n"; 
				
				// No need to keep it as it alread is included in the prefetch file
				unset($files[$class]);
			}
			else
			{
				throw new Exception(sprintf('Cannot find class "%s" in the declared paths.', $class));
			}
		}
		
		// Add the framework path, as the preload file might be located
		// somewhere else than in the framework root
		return 'define(\'INJECT_FRAMEWORK_PATH\', \''.Inject::getFrameworkPath().'\');

'.$str.'Inject::setLoaderCache('.var_export($files, true).');';
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
 * Include this file instead of the main Inject.php framework
 * file. Then everything will work as usual.
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
			
			// Remove all but the last newline in the whitespace
			if($v[0] === T_WHITESPACE && ($p = strrpos($v[1], "\n")) !== false)
			{
				$v[1] = substr($v[1], $p);
			}
			
			// Remove open and close PHP tags
			in_array($v[0], array(T_OPEN_TAG, T_CLOSE_TAG)) OR $new_contents .= $v[1];
		}
		
		// Remove remaining whitespace at both ends
		return trim($new_contents);
	}
}


/* End of file PreloadFileWriter.php */
/* Location: ./lib/Inject/Utils/Loader */