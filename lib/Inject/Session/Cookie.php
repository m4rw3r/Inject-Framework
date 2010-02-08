<?php
/*
 * Created by Martin Wernståhl on 2010-01-30.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Cookie session, stores the data in an encrypted cookie.
 */
class Inject_Session_Cookie extends Inject_Session
{
	public function __construct(array $config = array())
	{
		parent::__construct($config);
		
		// Write the cookie once, to avoid large HTTP headers
		Inject::onEvent('inject.terminate', array($this, 'saveData'));
	}
	
	// ------------------------------------------------------------------------
	
	public function start()
	{
		if($d = $this->getCookieData())
		{
			$this->data = $d;
			
			// Only fetch the proper cdata
			$this->cdata = array_intersect_key($d, array('token' => '', 'ip' => '', 'user_agent' => '', 'expire' => ''));
		}
		else
		{
			$this->newCookie();
		}
	}
	
	// ------------------------------------------------------------------------
	
	public function destroy()
	{
		$this->destroyCookie();
		
		$this->data = array();
	}
	
	// ------------------------------------------------------------------------
	
	public function saveData()
	{
		$this->writeCookie($this->data);
	}
}


/* End of file Cookie.php */
/* Location: ./lib/Inject/Session */