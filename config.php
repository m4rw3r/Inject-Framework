<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

return array
(
	'inject.application' => './application',
	'inject.classes' => array
		(
			'database' => 'Ot_base::get_instance'
		),
	'inject.class_paths' => array
		(
			'database'	=> 'libraries/ot/orm_tools.php',
			'ot_base'	=> 'libraries/ot/orm_tools.php',
			'db'		=> 'libraries/ot/orm_tools.php'
		),
	'inject.error_level'		=> E_ALL,
	'inject.error_level_log'	=> E_ALL,
	'request_http.routes'	=> array
		(
			'admin/(.+?)(/.*)?'	=> 'admin_$1$2'		// routes to controller/admin/$match.php
		)
);

/* End of file config.php */
/* Location: . */