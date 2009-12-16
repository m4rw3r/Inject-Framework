<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

error_reporting(E_ALL | E_DEPRECATED);
//ini_set('display_errors', '0');

require './Inject/inject.php';

// Add the autoloader and error handling
Inject::init();

Inject::setDispatcher(new Inject_Dispatcher);

//Inject::attach_logger(new Inject_Logger_Screenwriter);
//Inject::attach_logger(new Inject_Logger_File(dirname(__FILE__) . '/log.txt'));

/*echo "<pre>";
echo new URL('post/show', array('year' => 2008, 'month' => 34, 'day' => 4));*/


Inject::run(new Inject_Request_HTTP);

/* End of file index.php */
/* Location: . */