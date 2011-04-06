<?php
/*
 * Created by Martin Wernståhl on 2011-04-05.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\PhixCommands;

use Phix_Project\Phix\Context;
use Phix_Project\PhixExtensions\CommandInterface;

/**
 * 
 */
class Version implements CommandInterface
{
	public function getCommandName()
	{
		return 'inject:version';
	}
	
	public function getCommandDesc()
	{
		return 'show which version of InjectFramework is installed';
	}
	
	public function getCommandArgs()
	{
		return array();
	}
	
	public function validateAndExecute($args, $argsIndex, Context $context)
	{
		$context->stdout->outputLine(null, \Inject\Core\Application::VERSION);
	}
	
	public function outputHelp(Context $context)
	{
		$so = $context->stdout;
		
		$so->outputLine($context->commandStyle, $context->argvZero . ' ' . $this->getCommandName());
		$so->addIndent(4);
		$so->outputLine(null, "Show the currently installed version of InjectFramework");
		$so->addIndent(-4);
		$so->outputBlankLine();
	}
}


/* End of file Version.php */
/* Location: lib/Inject/PhixCommands */