<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Console;

/**
 * 
 */
class Application extends \Inject\Core\Application
{
	protected function registerRootDir()
	{
		return __DIR__;
	}
	protected function initMiddleware()
	{
		return array(
			new \Inject\CLI\Middleware\BlockNonCLIAccess()
		);
	}
	protected function initEndpoint()
	{
		return new \Inject\CLI\CLIEndpoint($this);
	}
}

/* End of file Application.php */
/* Location: src/php/Inject/Console */