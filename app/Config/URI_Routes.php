<?php
/*
 * Created by Martin Wernståhl on 2010-02-13.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/* @var $this Inject_Request_HTTP_URI_RouterBuilder */

// REST compliant route
$this->matches(
    'rest(/:method)(/:id)',
    array(
        '_controller' => 'rest',
        '_action' => 'handle',
        '_constraints' => array('method' => '[^\d]+', 'id' => '\d+')
        )
    );

$this->matches('(:lang/)welcome/:id', array('_controller' => 'welcome', '_action' => 'page'));

$this->matches(':_controller(/:_action(/):_uri)');

/* End of file Routes.php */
/* Location: ./app/Config */