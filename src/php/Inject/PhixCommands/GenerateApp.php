<?php
/*
 * Created by Martin Wernståhl on 2011-04-05.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\PhixCommands;

use Phix_Project\Phix\Context;
use Phix_Project\PhixExtensions\CommandInterface;
use Phix_Project\PhixExtensions\CommandBase;

/**
 * 
 */
class GenerateApp extends CommandBase implements CommandInterface
{
	public function getCommandName()
	{
		return 'inject:app';
	}
	
	public function getCommandDesc()
	{
		return 'generates a directory with a sample application skeleton';
	}
	
	public function getCommandArgs()
	{
		return array(
			'<app_name>'  => '<app_name> is the name of the application, needs to be a valid PHP namespace name', 
			'<folder>'   => '<folder> is a path to an existing folder, which you must have permission to write to.'
		);
	}
	
	public function validateAndExecute($args, $argsIndex, Context $context)
	{
		$se = $context->stderr;
		
		if( ! isset($args[$argsIndex]))
		{
			$se->output($context->errorStyle, $context->errorPrefix);
			$se->outputLine(null, 'missing argument <app_name>');
			
			return 1;
		}
		
		if( ! preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*/', $args[$argsIndex]))
		{
			$se->output($context->errorStyle, $context->errorPrefix);
			$se->outputLine(null, '<app_name> must be a valid PHP identifier');
			
			return 1;
		}
		
		if( ! isset($args[$argsIndex + 1]))
		{
			$se->output($context->errorStyle, $context->errorPrefix);
			$se->outputLine(null, 'missing argument <folder>');
			
			return 1;
		}
		
		if( ! is_dir($args[$argsIndex + 1]))
		{
			$se->output($context->errorStyle, $context->errorPrefix);
			$se->outputLine(null, sprintf('folder %s not found', $args[$argsIndex + 1]));
			
			return 1;
		}
		
		if( ! is_writeable($args[$argsIndex + 1]))
		{
			$se->output($context->errorStyle, $context->errorPrefix);
			$se->outputLine(null, sprintf('cannot write to folder %s', $args[$argsIndex + 1]));
			
			return 1;
		}
		
		$this->app_name = $args[$argsIndex];
		$this->folder   = rtrim($args[$argsIndex + 1], '/');
		$this->context  = $context;
		
		$this->createApp();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function createApp()
	{
		$this->context->stdout->output(null, 'Creating folders...');
		$this->createFolders();
		$this->context->stdout->outputLine(null, 'DONE');
		
		$this->context->stdout->output(null, 'Creating files...');
		$this->createFiles();
		$this->context->stdout->outputLine(null, 'DONE');
		
		$this->context->stdout->output(null, 'Changing permissions on folders...');
		$this->changeFolderPermissions();
		$this->context->stdout->outputLine(null, 'DONE');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function changeFolderPermissions()
	{
		$folders = array(
			'/Resources/Cache' => 0777
		);
		
		foreach($folders as $f => $mod)
		{
			$folder = $this->folder.'/'.$this->app_name.$f;
			
			chmod($folder, $mod);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function createFiles()
	{
		$replaces = array(
			'<folder>'    => $this->folder,
			'<namespace>' => $this->app_name,
			'<app_name>'  => $this->app_name,
			'<date>'      => date('Y-m-d H:i:s')
		);
		
		$files = array(
			'/Application.php',
			'/Controller/Blog.php',
			'/Model/Blog.php',
			'/Resources/Config/Config.php',
			'/Resources/Config/Routes.php',
			'/Resources/Views/Blog.php'
		);
		
		foreach($files as $f)
		{
			$file = $this->folder.'/'.$this->app_name.'/'.$f;
			
			$data = file_get_contents(__DIR__.'/AppTemplate'.$f.'.template');
			
			if( ! file_put_contents($file, strtr($data, $replaces)))
			{
				throw new \Exception(sprintf('Couldn\'t write to the file %s', $file));
			}
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function createFolders()
	{
		$folders = array(
			'/',
			'/Controller',
			'/Model',
			'/Resources',
			'/Resources/Assets',
			'/Resources/Cache',
			'/Resources/Config',
			'/Resources/Views'
		);
		
		foreach($folders as $f)
		{
			$folder = $this->folder.'/'.$this->app_name.$f;
			
			if( ! is_dir($folder))
			{
				if( ! mkdir($folder))
				{
					throw new \Exception(sprintf('Unable to create folder %s.', $folder));
				}
			}
		}
	}
}


/* End of file GenerateApp.php */
/* Location: lib/Inject/PhixCommands */