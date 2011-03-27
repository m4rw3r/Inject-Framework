<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core\Middleware;

/**
 * Exception telling the user that an endpoint is missing on a MiddlewareStack.
 */
class NoEndpointException extends \RuntimeException implements \Inject\Exception
{
	function __construct()
	{
		// TODO: Error code:
		parent::__construct('Missing endpoint in MiddlewareStack.');
	}
}

/* End of file NoEndpointException.php */
/* Location: src/php/Inject/Core/Middleware */