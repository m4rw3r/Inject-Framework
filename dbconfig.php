<?php
/*
 * Created by Martin Wernståhl on 2009-03-13.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

// database configuration is stored here

$active_group = 'default';

$db['default']['hostname'] = "localhost";
$db['default']['username'] = "ci";
$db['default']['password'] = "";
$db['default']['database'] = "test";
$db['default']['dbdriver'] = "mysql";
$db['default']['dbprefix'] = "";
$db['default']['pconnect'] = true;
$db['default']['db_debug'] = true;
$db['default']['cache_on'] = false;
$db['default']['cachedrv'] = 'file';
$db['default']['cacheopt'] = array();
$db['default']['char_set'] = "utf8";
$db['default']['dbcollat'] = "utf8_unicode_ci";
$db['default']['cache_compiled'] = true;

$db['fail']['hostname'] = "localhost";
$db['fail']['username'] = "aaa";
$db['fail']['password'] = "bbb";
$db['fail']['database'] = "test";
$db['fail']['dbdriver'] = "mysql";
$db['fail']['dbprefix'] = "";
$db['fail']['pconnect'] = true;
$db['fail']['db_debug'] = true;
$db['fail']['cache_on'] = false;
$db['fail']['cachedrv'] = 'file';
$db['fail']['cacheopt'] = array();
$db['fail']['char_set'] = "utf8";
$db['fail']['dbcollat'] = "utf8_unicode_ci";
$db['fail']['cache_compiled'] = false;


/* End of file dbconfig.php */
/* Location: ./ */