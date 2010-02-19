<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

// Store the initial start time here for the profiler to use
$start = microtime(true);


/*
 * Remove error viewing, but let all errors be reported.
 * 
 * This is to let all errors go to Inject, making it handle them all.
 * It makes it possible to selectively log everything or nothing by
 * the custom loggers (which will be responsible for all error
 * displays).
 */
error_reporting(E_ALL | E_DEPRECATED);
ini_set('display_errors', '0');


/*
 * Include Inject core class.
 */
require './lib/Inject.php';


/*
 * Add the autoloader and error handling.
 * 
 * This also starts output buffering which will be terminated
 * by Inject::terminate().
 */
Inject::init();


/*
 * Initialize the profiler.
 * 
 * The profiler is an optional component which helps the coder with
 * debugging and performance optimizations.
 * 
 * IT SHOULD NOT BE ENABLED DURING PRODUCTION.
 * 
 * (Comment out or remove the two lines below if you don't want to use it.)
 */
$p = new Inject_Profiler($start);
Inject::attachLogger($p);


/*
 * Set applicaiton paths.
 * 
 * Here you define the application paths, with the most important one first
 * in the array.
 * 
 * These paths will be searched for configuration for the framework,
 * located in config/inject.php.
 */
Inject::addPaths(array('app'));


/*
 * Run an Inject request.
 * 
 * This is the entry pont for the rest of the framework to do its
 * stuff.
 * The inject request will determine which controller and action
 * to run and the response will be returned from Inject::run(),
 * which means that we have to echo it to the buffers.
 */
echo Inject::run(new Inject_Request_HTTP_URI(Inject_URI::getCurrentURI()))->body;


/*
 * Terminate Inject Framework execution.
 * 
 * This send all headers and will empty all ouput buffer
 * created since Inject::init() and then their content will be
 * filtered through the filter inject.output.
 * It will also trigger the event inject.terminate.
 */
Inject::terminate();

/* End of file index.php */
/* Location: . */