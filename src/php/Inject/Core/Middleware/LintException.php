<?php
/*
 * Created by Martin Wernståhl on 2011-04-10.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core\Middleware;

/**
 * Exception telling the user that an endpoint is missing on a MiddlewareStack.
 */
class LintException extends \RuntimeException implements \Inject\Exception
{
	function __construct($message)
	{
		// TODO: Error code:
		parent::__construct('MiddlewareLint: '.$message);
	}
}

/* End of file LintException.php */
/* Location: src/php/Inject/Core/Middleware */