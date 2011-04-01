<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Console\Controller;

use \Inject\Core\Engine;
use \Inject\Core\Controller\AbstractController;

/**
 * 
 */
class Generate extends AbstractController
{
	public function __construct(Engine $app)
	{
		parent::__construct($app);
		
		$this->addGenerators($app);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function addGenerators(Engine $app)
	{
		// TODO: Load stuff from other applications too
		$r = new \ReflectionClass($app);
		$app_namespace = $r->getNamespaceName();
		
		foreach(glob($app->paths['engine'].'/Generators/*.php') as $file)
		{
			$name = $app_namespace.'\\Generators\\'.basename($file, '.php');
			$this->generators[] = new $name($this);
		}
		
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function indexAction($env)
	{
		$this->helpAction($env);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function helpAction($env)
	{
		echo "
Inject CLI Generate Controller

";
		
		$params = $env['cli.parameters'];
		
		if( ! empty($params))
		{
			foreach($params as $k => $v)
			{
				if(is_numeric($k))
				{
					foreach($this->generators as $g)
					{
						if($g->getConsoleCommand() == $v)
						{
							echo $g->getConsoleCommand()." Help:\n\n";
							
							echo $g->getHelpText()."\n\n";
							
							return;
						}
					}
				}
			}
			
			echo "No help found for the supplied parameters: ".print_r($params, true)."\n\n";
			return;
		}
		
		echo "Available Tasks:

";

		foreach($this->generators as $g)
		{
			echo $g->getConsoleCommand().":\n";

			$help = explode("\n", $g->getShortHelp());

			$help = array_map(function($line)
			{
				return '    '.$line;
			}, $help);

			echo implode("\n", $help);
		}

		echo "\n\n";
	}
	
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __call($method, $params = array())
	{
		$method = str_replace('Action', '', $method);
		$env    = current($params);
		
		foreach($this->generators as $g)
		{
			if($method === $g->getConsoleCommand())
			{
				return $g->generate($env);
			}
		}
		
		// TODO: Exception
		throw new \Exception("Generate Controller ERROR: Command with name $method not found\n\n");
	}
}


/* End of file Generate.php */
/* Location: src/php/Inject/Console/Controller */