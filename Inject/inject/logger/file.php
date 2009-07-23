<?php
/*
 * Created by Martin Wernståhl on 2009-07-18.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Logger_File implements Inject_Logger
{
	protected $lines = array();
	
	public $newline = "\n";
	
	function __construct($file)
	{
		$this->file = $file;
	}
	
	function __destruct()
	{
		if(file_exists($this->file))
		{
			file_put_contents($this->file, $this->newline . implode($this->newline, $this->lines), FILE_APPEND);
		}
		else
		{
			file_put_contents($this->file, implode($this->newline, $this->lines));
		}
	}
	
	// ------------------------------------------------------------------------

	public function add_message($namespace, $message, $level)
	{
		switch($level)
		{
			case Inject::ERROR:
				$level = 'ERROR';
				break;
			
			case Inject::WARNING:
				$level = 'WARNING';
				break;
			
			case Inject::NOTICE:
				$level = 'NOTICE';
				break;
			
			case Inject::DEBUG:
				$level = 'DEBUG';
				break;
		}
		
		$this->lines[] = str_pad('[' . $level . ']', 9) . ' - ' . str_pad($namespace, 10) . ': ' . $message;
	}
	
	public function shutdown(){}
}


/* End of file file.php */
/* Location: ./Inject/inject/logger */