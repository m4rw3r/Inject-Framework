<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Console\Generators;

/**
 * 
 */
class PreloadFile implements \Inject\Console\GeneratorInterface
{
	/**
	 * 
	 * 
	 * @return 
	 */
	public function getShortHelp()
	{
		return <<<'EOF'
Generates a preload file which contains a specified set of classes
of the core framework to allow faster class loading.
EOF;
	}
	
	// ------------------------------------------------------------------------
	
	public function getHelpText()
	{
		return <<<'EOF'
Generates a preload file which contains a specified set of classes
of the core framework to allow faster class loading.

Usage:

bin/inject generate preload_file (--path [additional_classpaths]) (--classes [classnames]) (--no_default=true) (--file [file])

--paths parameter takes paths separated by ";" and parses them for classes.
--classes parameter takes a list of specific classes separated by ";" to include from the paths.
--no_default tells preload_file to not add the default paths and classes.
--file specifies a file to write to, default is STDOUT
EOF;
	}
	
	// ------------------------------------------------------------------------
	
	public function getConsoleCommand()
	{
		return 'preload_file';
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function generate($req)
	{
		if( ! isset($req['cli.parameters']['no_deafault']) OR $req['cli.parameters']['no_deafault'] == false)
		{
			$paths = array(dirname(dirname(dirname(__DIR__))));
			// TODO: Move to a config file:
			$classes = array(
					'Inject\Autoloader',
					'Inject\Exception',
					'Inject\Core\Engine',
					'Inject\Core\Application',
					'Inject\Core\MiddlewareStack',
					'Inject\Core\CascadeEndpoint',
					'Inject\Core\Middleware\MiddlewareInterface',
					'Inject\Core\Middleware\NoEndpointException',
					'Inject\Core\Middleware\Utf8Filter',
					'Inject\Core\Controller\AbstractController',
					'Inject\Web\Middleware\ServerVarFilter',
					'Inject\Web\Middleware\ExceptionCatcher',
					'Inject\Web\Middleware\NotFoundCatcher',
					'Inject\Web\RouterEndpoint',
					'Inject\Web\Router\Route\AbstractRoute',
					'Inject\Web\Router\Route\ApplicationRoute',
					'Inject\Web\Router\Route\CallbackRoute',
					'Inject\Web\Router\Route\ControllerRoute',
					'Inject\Web\Router\Route\PolymorphicRoute',
					'Inject\Web\Responder'
				);
		}
		else
		{
			$paths = array();
			$classes = array();
		}
		
		$new_paths   = isset($req['cli.parameters']['paths'])   ? trim($req['cli.parameters']['paths'], '; ')   : '';
		$new_classes = isset($req['cli.parameters']['classes']) ? trim($req['cli.parameters']['classes'], '; ') : '';
		
		empty($new_paths)   OR $paths   = array_merge($paths,   explode(';', $new_paths));
		empty($new_classes) OR $classes = array_merge($classes, explode(';', $new_classes));
		
		$finder = new \Inject\Console\Tools\ClassFinder($paths);
		
		$writer = new \Inject\Console\Tools\PreloadFileWriter($classes, $finder->getClassFiles());
		
		if(isset($req['cli.parameters']['file']) && ($path = $req['cli.parameters']['file']))
		{
			$writer->writeFile($path);
		}
		else
		{
			echo '<?php
/*
 * Preload file generated for Inject Framework.
 * 
 * Include this file instead of the main Inject framework
 * files. Then everything will work as usual.
 * 
 * Generated on '.date('Y-m-d H:i:s').'
 */
'.$writer->getPhp();
		}
	}
	
}


/* End of file PreloadFile.php */
/* Location: src/php/Inject/Console/Generators */