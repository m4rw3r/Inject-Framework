<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

error_reporting(E_ALL | E_DEPRECATED);
ini_set('display_errors', '0');

require './Inject/inject.php';

// Add the autoloader and error handling
Inject::init();

// Configure Inject Framework
Inject::set_config_file('./config.php');
Inject::set_config('inject.front_controller', basename(__FILE__));

//Inject::attach_logger(new Inject_Logger_Screenwriter);
Inject::attach_logger(new Inject_Logger_File(dirname(__FILE__) . '/log.txt'));

Inject::run(new Inject_Request_HTTP);

/* End of file index.php */
/* Location: . */