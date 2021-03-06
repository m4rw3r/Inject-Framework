<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core;

/**
 * 
 */
abstract class Application extends Engine
{
	/**
	 * The main application instance.
	 * 
	 * @var \Inject\Core\Application
	 */
	private static $application;
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the Application object used for this PHP instance.
	 * 
	 * @return \Inject\Core\Application
	 */
	public static function getApplication()
	{
		if(empty(self::$application))
		{
			// TODO: Exception
			throw new \Exception('No Application has yet been instantiated.');
		}
		
		return self::$application;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	protected function __construct()
	{
		if(isset(self::$application))
		{
			// TODO: Exception
			throw new \Exception('You cannot have more than one \Inject\Application during a single request.');
		}
		
		self::$application = $this;
		
		parent::__construct();
	}
}


/* End of file Application.php */
/* Location: src/php/Inject/Core */