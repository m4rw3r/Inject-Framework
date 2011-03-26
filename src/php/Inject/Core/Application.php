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
abstract class Application extends Application\Engine
{
	/**
	 * The main application instance.
	 * 
	 * @var \Inject\Core\Application
	 */
	private static $application;
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the Application, that is, the first Engine to be instantiated.
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
		
		$this->isolated = true;
		
		parent::__construct();
	}
}


/* End of file Application.php */
/* Location: src/php/Inject/Core */