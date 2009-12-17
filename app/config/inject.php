<?php

// Add a logger
Inject::attachLogger(new Inject_Logger_Screenwriter("\n"));

// Use the standard dispatcher
Inject::setDispatcher(new Inject_Dispatcher);

// Autoload some instances (ie. set global instances
// Inject_Registry::setGlobal('database', new Db);

// Not implemented settings:

// Set Root URL
// URL::setBase('http://localhost');