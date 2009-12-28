<?php
/*
 * Created by Martin Wernståhl on 2009-07-18.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Logger_ScreenWriter implements Inject_LoggerInterface
{
	public $newline = "<br />";
	
	public function __construct($newline = "<br />")
	{
		$this->newline = $newline;
	}
	
	// ------------------------------------------------------------------------

	public function addMessage($namespace, $message, $level)
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
		
		echo str_pad('[' . $level . ']', 9) . ' - ' . str_pad($namespace, 10) . ': ' . $message . $this->newline;
	}
}


/* End of file screenwriter.php */
/* Location: ./Inject/inject/logger */