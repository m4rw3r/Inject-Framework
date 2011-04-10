<?php
/*
 * Created by Martin Wernståhl on 2010-02-14.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\CLI;

use \Inject\Core\Engine;
use \Inject\Core\Middleware\MiddlewareInterface;

/**
 * 
 */
class CLIEndpoint
{
	protected $engine;
	
	function __construct(Engine $engine)
	{
		$this->engine = $engine;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __invoke($env)
	{
		$env = $this->parseArgv($env);
		
		$controllers = $this->engine->getAvailableControllers();
		
		if(isset($env['cli.controller']) && isset($controllers[$env['cli.controller']]))
		{
			$class = $controllers[$env['cli.controller']];
			
			return $class::stack($this->engine, $env['cli.action'])->run($env);
		}
		
		return array(404, array('X-Cascade' => 'pass'), '

Inject Framework CLI Interface

Controller not found.

');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Parses the ARGV parameters from the CLI SAPI.
	 * 
	 * @return void
	 */
	protected function parseArgv($env)
	{
		$env['cli.script_name'] = $env['PHP_SELF'];
		
		$argv = $env['argv'];
		
		// Remove script name if present
		if(isset($argv[0]) && $argv[0] == $env['PHP_SELF'])
		{
			array_shift($argv);
		}
		
		$key = null;
		$run_path = array();
		$env['cli.parameters'] = array();
		
		// Parse parameter list
		while( ! empty($argv))
		{
			$v = array_shift($argv);
			
			// Is it a parameter?
			if(strpos($v, '-') === 0)
			{
				// Do we have a started parameter already?
				if( ! is_null($key))
				{
					// Yes, finish it
					$env['cli.parameters'][$key] = true;
				}
				
				// Is it 2 or 1 dash at the start? remove them
				if(strpos($v, '--') === 0)
				{
					$key = substr($v, 2);
				}
				else
				{
					$key = substr($v, 1);
				}
			}
			// Do we have a parameter value
			elseif( ! is_null($key))
			{
				// Yes
				$env['cli.parameters'][$key] = $v;
				
				// Done processing that parameter
				$key = null;
			}
			else
			{
				// Not tied to a parameter, add it to the "address"
				$run_path[] = $v;
			}
		}
		
		// Do we have an orphaned parameter?
		if( ! is_null($key))
		{
			// Yes, add it
			$env['cli.parameters'][$key] = true;
		}
		
		// Do we have a path, or the help flags?
		if(empty($run_path) OR count(array_intersect(array('h', 'help', '?'), array_keys($env['cli.parameters']))))
		{
			return $this->showHelp($env);
		}
		
		// Controller
		if( ! empty($run_path))
		{
			$class = array_shift($run_path);
			
			$env['cli.controller'] = strtolower($class);
		}
		
		// Action
		$env['cli.action'] = empty($run_path) ? 'index' : array_shift($run_path);
		
		// Add the extra parameters which doesn't have a key
		$env['cli.parameters'] = array_merge($env['cli.parameters'], $run_path);
		
		return $env;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shows the invocation help for the CLI request.
	 * 
	 * @return void
	 */
	public function showHelp($env)
	{
		echo '
Inject Framework CLI Interface

Usage:
    php '.$env['cli.script_name'].' [controller] ([action_name]) ([-parameter] ([parameter_value]))

Example:
    php '.$env['cli.script_name'].' welcome say_hello -name "Martin"

    Will call the \Inject\Console\Controller\Welcome controller\'s method actionSay_hello
    with the parameters array(\'name\' => \'Martin W\').

To print this help again, use the --help, -help, -h or -? options.

';
	}
}


/* End of file CLIEndpoint.php */
/* Location: src/php/Inject/CLI */