<?php
/*
 * Created by Martin Wernståhl on 2009-07-18.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
interface Inject_LoggerInterface
{
	/**
	 * Logs a message with this logger.
	 * 
	 * @param  string
	 * @param  string
	 * @param  int		InjectFramework error constant
	 * @return void
	 */
	public function addMessage($namespace, $message, $level);
}


/* End of file LoggerInterface.php */
/* Location: ./lib/Inject */