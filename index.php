<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

error_reporting(E_ALL | E_DEPRECATED);
ini_set('display_errors', '0');

require './lib/Inject.php';

// Add the autoloader and error handling
Inject::init();

// Set applicaiton paths
Inject::addPaths(array('app'));

// Run a HMVC request, so we specify controller and action
Inject::run(new Inject_Request_HMVC('Controller_Test', 'test_action'));

/* End of file index.php */
/* Location: . */