<?php
/*
 * Created by Martin Wernståhl on 2010-02-25.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * This is the sample application configuration file.
 */
class SampleApplication extends Inject_Application_Default
{
	public function isProduction()
	{
		return false;
	}
	
	public function getPaths()
	{
		/*
		 * Set applicaiton paths.
		 * 
		 * Here you define the application paths, with the most important one first
		 * in the array.
		 */
		return array('./app/');
	}
	
	public function getCacheFolder()
	{
		/*
		 * Here you define where the cached files should be stored.
		 */
		return './app/Cache/';
	}
	
	public function configure()
	{
		/*
		 * Set error handling settings.
		 * 
		 * The Inject class also has settings for error handling, if the error should
		 * be shown to the user or if just a static page should be shown and which
		 * error types that Inject should abort execution on.
		 */
		
		// Set error on which execution sholud be aborted
		Inject::setErrorLevel(Inject::ERROR | Inject::WARNING);
		
		/*
		 * Create and add loggers.
		 * 
		 * Inject uses an observer-like variant for the loggers:
		 * 
		 * You create a logger object which is responsible for storing
		 * or printing the log messages somewhere.
		 * Then this logger is passed to Inject::attachLogger(logger_object, [level = Inject::ALL]).
		 * The second parameter is an error constant (from the Inject class)
		 * which adjusts which errors this logger should receive.
		 */

		// Add a logger which prints ALL messages to the screen
		// Inject::attachLogger(new Inject_Logger_Screenwriter("\n"));
		
		// Add a Growl logger (http://growl.info)
		// Inject::attachLogger(new Inject_Logger_Growl('localhost', 'password'), Inject::ERROR | Inject::NOTICE | Inject::WARNING);

		// Add a file logger for ERROR and WARNING, saves the files in log.txt
		Inject::attachLogger(new Inject_Logger_File('/Users/m4rw3r/Sites/Inject-Framework/log.txt'), Inject::ERROR | Inject::WARNING);
		
		/*
		 * Set global instances and object instantiaters.
		 * 
		 * The Inject_Library class is a dependency injection container,
		 * this means that it handles the object instantiation (if needed)
		 * and mapping resource names to classes.
		 * 
		 * For example you can set a global database instance whch is to be
		 * loaded on demand, and not only which class it uses but also how
		 * it is instantiated.
		 */
		/*
		// Register a closure which creates the session object
		Inject_Library::setGlobalResource('session', function()
		{
			return new Inject_Session(Inject_Library::getGlobalResource('database'));
		});
		// Mapping the view resource name to a class, creating new instances all the time
		Inject_Library::setGlobalResource('view', 'Inject_View', false);
		*/
	}
	
	// ------------------------------------------------------------------------
	
	public function getDispatcher()
	{
		/*
		 * Create the standard dispatcher object.
		 *
		 * The dispatcher is the object which is responsible for instantiating
		 * and calling the class->method it receives from the request object.
		 * It is also responsible for any default class/method to run.
		 */
		
		// Use the default dispatcher
		$d = new Inject_Dispatcher();
		
		// Set default controller and action
		$d->setDefaultHandler('Controller_Welcome', 'actionIndex');
		
		// Set the error handlers in case something goes wrong
		$d->set404Handler('Controller_Welcome', 'error');
		
		return $d;
	}
	
	// ------------------------------------------------------------------------
	
	public function getUriRouteFiles()
	{
		return array('./app/Config/URI_Routes.php');
	}
}


/* End of file SampleApplication.php */
/* Location: . */