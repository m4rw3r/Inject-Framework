<?php
/*
 * Created by Martin Wernståhl on 2011-04-05.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\PhixCommands;

use Phix_Project\Phix\Context;
use Phix_Project\PhixExtensions\CommandBase;
use Phix_Project\PhixExtensions\CommandInterface;

use Gradwell\CommandLineLib\DefinedSwitches;
use Gradwell\CommandLineLib\CommandLineParser;
use Gradwell\ValidationLib\MustBeValidFile;
use Gradwell\ValidationLib\MustBeWriteable;

use \Inject\Tools\ClassFinder;
use \Inject\Tools\PreloadFileWriter;

/**
 * 
 */
class GeneratePreloadFile extends CommandBase implements CommandInterface
{
	public function getCommandName()
	{
		return 'inject:preload-file';
	}
	
	public function getCommandDesc()
	{
		return 'Generates a preload file which contains a specified set of classes of the core framework to allow faster class loading.';
	}
	
	public function getCommandOptions()
	{
		$options = new DefinedSwitches();
		
		$options->addSwitch('paths', 'specifies a list of paths separated by ";" to search for classes')
		        ->setWithLongSwitch('paths')
		        ->setwithRequiredArg('<paths>', 'a list of paths separated by ";" to search for classes')
		        ->setArgHasDefaultValueOf('');
		
		$options->addSwitch('classes', 'specifies a list of classes separated by ";" to include in the preload file')
		        ->setWithLongSwitch('classes')
		        ->setwithRequiredArg('<classes>', 'a list of classes separated by ";" to search for classes')
		        ->setArgHasDefaultValueOf('');
		
		$options->addSwitch('file', 'specifies a file to write to')
		        ->setWithLongSwitch('file')
		        ->setwithRequiredArg('<file>', 'path to the preload file to write to')
		        ->setArgHasDefaultValueOf('preload.php')
		        ->setArgValidator(new MustBeValidFile())
		        ->setArgValidator(new MustBeWriteable());
		
		$options->addSwitch('no_default', 'if set to "true", tells preload-file to avoid adding default paths and classes')
		        ->setWithLongSwitch('no_default')
		        ->setwithRequiredArg('<no_default>', 'if set to "true" tells preload file to not include default paths and classes')
		        ->setArgHasDefaultValueOf('false');
		
		return $options;
	}
	
	public function validateAndExecute($args, $argsIndex, Context $context)
	{
		$options  = $this->getCommandOptions();
		$parser   = new CommandLineParser();
		list($parsedSwitches, $argsIndex) = $parser->parseSwitches($args, $argsIndex, $options);
		
		$errors = $parsedSwitches->validateSwitchValues();
		if (count($errors) > 0)
		{
			// validation failed
			foreach ($errors as $errorMsg)
			{
				$context->stderr->output($context->errorStyle, $context->errorPrefix);
				$context->stderr->outputLine(null, $errorMsg);
			}
			
			return 1;
		}
		
		$file_param    = $parsedSwitches->getFirstArgForSwitch('file');
		$classes_param = $parsedSwitches->getFirstArgForSwitch('classes');
		$paths_param   = $parsedSwitches->getFirstArgForSwitch('paths');
		$no_default    = $parsedSwitches->getFirstArgForSwitch('no_default');
		
		$paths   = array();
		$classes = array();
		
		if($no_default != 'true')
		{
			$paths[] = realpath(dirname(__DIR__));
			$classes = array_merge($classes, array(
				'Inject\Autoloader',
				'Inject\Exception',
				'Inject\Core\Engine',
				'Inject\Core\Application',
				'Inject\Core\MiddlewareStack',
				'Inject\Core\AdapterInterface',
				'Inject\Core\CascadeEndpoint',
				'Inject\Core\Middleware\MiddlewareInterface',
				'Inject\Core\Middleware\NoEndpointException',
				'Inject\Core\Middleware\RunTimer',
				'Inject\Core\Middleware\Utf8Filter',
				'Inject\Core\Controller\AbstractController',
				'Inject\Web\Middleware\ExceptionCatcher',
				'Inject\Web\Middleware\MethodOverride',
				'Inject\Web\Middleware\NotFoundCatcher',
				'Inject\Web\ServerAdapter\Generic',
				'Inject\Web\RouterEndpoint',
				'Inject\Web\Util'
			));
		}
		
		$new_paths   = trim($paths_param, '; ');
		$new_classes = trim($classes_param, '; ');
		
		empty($new_paths)   OR $paths   = array_merge($paths,   explode(';', $new_paths));
		empty($new_classes) OR $classes = array_merge($classes, explode(';', $new_classes));
		
		$finder = new ClassFinder($paths);
		
		$writer = new PreloadFileWriter($classes, $finder->getClassFiles());
		
		$num = $writer->writeFile($file_param);
		
		$context->stdout->outputLine(null, 'Wrote '.$num.' bytes to '.$file_param);
		
		return 0;
	}
}


/* End of file GenerateApp.php */
/* Location: lib/Inject/PhixCommands */