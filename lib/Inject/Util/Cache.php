<?php
/*
 * Created by Martin Wernståhl on 2010-02-23.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Util_Cache
{
	protected static $file_metadata = false;
	
	// ------------------------------------------------------------------------

	/**
	 * Loads a cached file.
	 * 
	 * @param  string
	 * @return bool
	 */
	public static function load($file)
	{
		$f = Inject::getCacheFolder();
		
		if(file_exists($f.$file))
		{
			require $f.$file;
			
			return true;
		}
		else
		{
			return false;
		}
	}
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public static function checkAge($file)
	{
		# code...
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Checks if a cache file is current compared to the files it was made of.
	 * 
	 * @param  string  Name of the cache file, relative to self::getFolder()
	 * @param  array   Files which are to be included in the cache file, full paths
	 * @return bool
	 */
	public static function isCurrent($cache_file, array $files)
	{
		self::loadMetadata();
		
		if(isset(self::$file_metadata[$cache_file]) && file_exists(Inject::getCacheFolder().$cache_file) && ! array_diff($files, array_keys(self::$file_metadata[$cache_file])))
		{
			foreach(self::$file_metadata[$cache_file] as $file => $time)
			{
				if(file_exists($file))
				{
					$stat = stat($file);
					
					if(self::$file_metadata[$cache_file][$file] !== $stat['mtime'])
					{
						return false;
					}
				}
				else
				{
					return false;
				}
			}
		}
		else
		{
			return false;
		}
		
		return true;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Registers that a cache file has been updated and adds the appropriate metadata
	 * to later be able to tell when to update it.
	 * 
	 * @param  string  Cache file, relative to cache folder
	 * @param  array   List of files used in creating the cache file, full paths
	 * @return void
	 */
	public static function registerCacheFile($cache_file, array $part_files)
	{
		self::$file_metadata[$cache_file] = array();
		
		foreach($part_files as $file)
		{
			if(file_exists($file))
			{
				$stat = stat($file);
				
				self::$file_metadata[$cache_file][$file] = $stat['mtime'];
			}
		}
		
		self::writeMetadata();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Removes the metadata about the specified cache file.
	 * 
	 * @param  string  Filename relative to cache folder
	 * @return void
	 */
	public static function removeCacheFile($cache_file)
	{
		unset(self::$file_metadata[$cache_file]);
		
		self::writeMetadata();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Loads the metadata file, containing change dates as well as filenames.
	 * 
	 * @return void
	 */
	protected static function loadMetadata()
	{
		if(self::$file_metadata === false && file_exists(Inject::getCacheFolder().'cachemeta.php'))
		{
			self::$file_metadata = include Inject::getCacheFolder().'cachemeta.php';
		}
		else
		{
			self::$file_metadata = array();
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Stores the updated variant of the metadata.
	 * 
	 * @return void
	 */
	protected static function writeMetadata()
	{
		$str = '<?php

return '.var_export(self::$file_metadata, true).';';
		
		file_put_contents(Inject::getCacheFolder().'cachemeta.php', $str);
	}
}


/* End of file Metadata.php */
/* Location: ./lib/Inject/Util */