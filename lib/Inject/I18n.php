<?php
/*
 * Created by Martin Wernståhl on 2010-01-01.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Internationalization class
 * 
 * Usage:
 * 
 * <code>
 * // Set language:
 * Inject_I18n::setLang('sv-se');
 * 
 * // Static usage:
 * echo Inject_I18n::get('form', 'name'); // Echoes swedish translation from file: Namn
 * 
 * // Instance usage:
 * $i = new Inject_I18n('form');
 * echo $i->name;
 * </code>
 */
class Inject_I18n
{
	protected static $lang = 'en-us';
	
	protected static $cache = array();
	
	protected $data = array();
	
	protected $domain = array();
	
	/**
	 * @param  The domain for which the translation should be fetched.
	 */
	function __construct($domain)
	{
		$this->domain = $domain;
		
		$this->data = self::load($domain);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __get($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : $this->domain.'.'.$key;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public static function get($domain, $key)
	{
		if( ! isset(self::$cache[self::$lang][$domain]))
		{
			self::load($domain);
		}
		
		return isset(self::$cache[self::$lang][$domain][$key]) ? self::$cache[self::$lang][$domain][$key] : $domain.'.'.$key;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public static function setLang($lang_code)
	{
		self::$lang = strtolower($lang_code);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public static function load($domain)
	{
		if(isset(self::$cache[self::$lang][$domain]))
		{
			return self::$cache[self::$lang][$domain];
		}
		
		$data = array();
		
		$filepath = 'I18n/'.self::$lang.'/'.$domain.'.php';
		
		foreach(array_reverse(Inject::getApplicationPaths()) as $path)
		{
			if(file_exists($path.$filepath))
			{
				$data = array_merge($data, include $path.$filepath);
			}
		}
		
		if(file_exists(Inject::getFrameworkPath().$filepath))
		{
			$data = array_merge($data, include Inject::getFrameworkPath().$filepath);
		}
		
		if( ! empty($data))
		{
			Inject::log('I18n', 'Loaded "'.$domain.'" strings.', Inject::DEBUG);
		}
		else
		{
			Inject::log('I18n', 'Cannot load "'.$domain.'" strings for the language "'.self::$lang.'".', Inject::WARNING);
		}
		
		return self::$cache[self::$lang][$domain] = $data;
	}
}


/* End of file I18n.php */
/* Location: ./lib/Inject */