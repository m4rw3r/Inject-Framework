<?php
/*
 * Created by Martin Wernståhl on 2009-07-18.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
interface Inject_Logger
{
	/**
	 * Logs a message with this logger.
	 * 
	 * @param  string
	 * @param  string
	 * @param  int
	 * @return void
	 */
	public function add_message($namespace, $message, $level);
	
	/**
	 * Called when Inject Framework ends its execution.
	 * 
	 * @return void
	 */
	public function shutdown();
}


/* End of file logger.php */
/* Location: ./Inject/inject */