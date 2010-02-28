#!/usr/bin/php
<?php
/*
 * Created by Martin Wernståhl on 2010-02-14.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */


/*
 * Remove error viewing, but let all errors be reported.
 * 
 * This is to let all errors go to Inject, making it handle them all.
 * It makes it possible to selectively log everything or nothing by
 * the custom loggers (which will be responsible for all error
 * displays except for fatal errors).
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
 * Create the application instance and load it into Inject.
 * 
 * The application instance will provide configuration, paths,
 * namespace mappings and the dispatcher.
 */
require './SampleApplication.php';
Inject::loadApplication(new SampleApplication());


/*
 * Run an Inject request.
 * 
 * This is the entry pont for the rest of the framework to do its
 * stuff.
 * The inject request will determine which controller and action
 * to run and the response will be returned from Inject::run(),
 * which means that we have to echo it to the buffers.
 * A Inject_Response object is returned by the run() method, which
 * has the send() method that sends all headers and then echos the
 * contents.
 */
Inject::run(new Inject_Request_CLI)->send();


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