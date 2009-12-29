<?php
/*
 * Created by Martin Wernståhl on 2009-12-29.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Util
{
	protected static $filesizes = array(' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB');
	
	// ------------------------------------------------------------------------

	/**
	 * Converts an Inject Error constant to an uppercased string with the constant
	 * name.
	 * 
	 * @param  int
	 * @return string
	 */
	public static function errorConstToStr($constant)
	{
		switch($constant)
		{
			case Inject::WARNING:
				$level = 'WARNING';
				break;
			
			case Inject::NOTICE:
				$level = 'NOTICE';
				break;
			
			case Inject::DEBUG:
				$level = 'DEBUG';
				break;
				
			default:
				$level = 'ERROR';
		}
		
		return $level;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Converts a file size in bytes to a human readable size, with suffixes.
	 * 
	 * @param  int
	 * @return string
	 */
	public static function humanReadableSize($bytes)
	{
		return $bytes ? round($bytes/pow(1024, ($i = floor(log($bytes, 1024)))), 2) . self::$filesizes[$i] : '0 Bytes';
	}
}


/* End of file Util.php */
/* Location: ./lib/Inject */