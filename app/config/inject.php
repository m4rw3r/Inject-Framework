<?php

/*
 * Create and add loggers.
 * 
 * Inject uses an observer-like variant for the loggers:
 * 
 * You create a logger object which is responsible for storing
 * or printing the log messages somewhere.
 * Then this logger is passed to Inject::attachLogger(logger_object, [level = ALL]).
 * The second parameter is an error constant (from the Inject class)
 * which adjusts which errors this logger should log.
 */

// Add a logger which prints all messages to the screen
Inject::attachLogger(new Inject_Logger_Screenwriter("\n"));

// Add a file logger for ERROR and WARNING, saves the files in log.txt
Inject::attachLogger(new Inject_Logger_File('/Users/m4rw3r/Sites/Inject-Framework/log.txt'), Inject::ERROR | Inject::WARNING);




/*
 * Create the standard dispatcher object.
 *
 * The dispatcher is the object which is responsible for instantiating
 * and calling the class->method it receives from the request object.
 * It is also responsible for any default class/method to run.
 */
$d = new Inject_Dispatcher();

// Set default controller and action
$d->setDefaultControllerClass('Controller_Welcome');
$d->setDefaultControllerAction('index');

// set the error handlers in case something goes wrong
$d->set404Handler('Class', 'Method');

// Tell Inject to use the configured dispatcher
Inject::setDispatcher($d);




/*
 * Set global instances.
 * 
 * All the instances assigned using Inject_Registry::setGlobal() will be
 * available to all children of Inject_Registry. This also includes all
 * Inject_Request objects.
 * 
 * The globals can also locally (in an object instance) be overridden,
 * so that a specific instance uses other values.
 */
//Inject_Registry::setGlobal('database', new Db);

