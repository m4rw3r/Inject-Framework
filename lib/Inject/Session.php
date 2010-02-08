<?php
/*
 * Created by Martin Wernståhl on 2010-01-14.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Base session class.
 */
abstract class Inject_Session
{
	/**
	 * The main session instance.
	 * 
	 * @var Inject_Session
	 */
	protected static $main_instance;
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public static function getInstance()
	{
		if(empty(self::$main_instance))
		{
			throw new Exception('Missing main Session instance.');
		}
		
		return self::$main_instance;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public static function setInstance($instance)
	{
		self::$main_instance = $instance;
	}
	
	/**
	 * The session data, change to update session data.
	 * 
	 * @var array
	 */
	public $data = array();
	
	/**
	 * The cookie data.
	 * 
	 * @var array
	 */
	protected $cdata = array();
	
	/**
	 * The encryptor which will encrypt the cookie.
	 * 
	 * @var Inject_EncryptInterface
	 */
	protected $enc;
	
	/**
	 * The request with the user data.
	 * 
	 * @var Inject_Request
	 */
	protected $request;
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct(array $config = array())
	{
		$this->config = Inject::getConfiguration('Session', array(
				'cookie_name'   => 'ifw_cookie',
				'cookie_domain' => '',
				'cookie_path'   => '',
				'expire_time'   => 3600
			));
		
		$this->config = array_merge($this->config, $config);
		
		// Use main request, unless we have a specific one to extract data from
		$this->request = empty($this->config['request']) ? Inject::getMainRequest() : $this->config['request'];
		
		// TODO: Replace this ASAP!
		$this->enc = new Inject_Encrypt_Dummy();
		
		$this->start();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Starts the session, calls getCookieData() and if that fails,
	 * newCookie() and writeCookie().
	 * 
	 * Example code:
	 * <code>
	 * if($d = $this->getCookieData() && $this->tokenInDb($d['token']))
	 * {
	 *     $this->cdata = $d;
	 *     $this->data = $this->getDbRow();
	 * }
	 * else
	 * {
	 *     $this->newCookie();
	 *     $this->writeCookie();
	 *     
	 *     $this->createDbRow($d['token']);
	 * }
	 * </code>
	 * 
	 * @return bool
	 */
	abstract public function start();
	
	// ------------------------------------------------------------------------

	/**
	 * Destroys the session, calls destroyCookie() and deletes all local data.
	 * 
	 * @return bool
	 */
	abstract public function destroy();
	
	// ------------------------------------------------------------------------

	/**
	 * Fetches the cookie data.
	 * 
	 * ! ATTENTION !
	 * DOES NOT SET THE $this->cdata VARIABLE!
	 * THE startSession() METHOD MUST DO THAT.
	 * 
	 * @return array|false
	 */
	protected function getCookieData()
	{
		if( ! empty($_COOKIE[$this->config['cookie_name']]))
		{
			$cdata = $_COOKIE[$this->config['cookie_name']];
			
			// Decrypt cookie
			$cdata = $this->extractData($this->enc->decryptStr(base64_decode($cdata)));
			
			// Validate data keys
			if(count(array_intersect(array_keys($cdata), array('token', 'ip', 'user_agent', 'expire'))) === 4)
			{
				// Validate time, ip and user agent with the current ones
				if(time() < $cdata['expire'] &&
					$cdata['ip'] === $this->request->getUserIp() &&
					$cdata['user_agent'] === $this->request->getUserAgent()
					)
				{
					return $cdata;
				}
			}
		}
		
		return false;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Updates the cookie data, run writeCookie() to set the data.
	 * 
	 * @return void
	 */
	protected function newCookie()
	{
		// Set new cookie data
		$this->cdata = array(
				'token' => $this->createToken(),
				'ip' => $this->request->getUserIp(),
				'user_agent' => $this->request->getUserAgent(),
				'expire' => time() + $this->config['expire_time']
			);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Writes the cookie data to the cookie.
	 * 
	 * @return void
	 */
	protected function writeCookie(array $data = array(), $update_expire = true)
	{
		$time = $update_expire ? time() + $this->config['expire_time'] : $this->cdata['expire'];
		
		// Merge with cookie data
		$cdata = array_merge($data, $this->cdata);
		$cdata['expire'] = $time;
		
		// Encrypt cookie
		$cdata = base64_encode($this->enc->encryptStr($this->compactData($cdata)));
		
		setcookie($this->config['cookie_name'], $cdata, $time, $this->config['cookie_path'], $this->config['cookie_domain']);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Destroys the session.
	 * 
	 * @return void
	 */
	protected function destroyCookie()
	{
		setcookie($this->config['cookie_name'], '', time() - 31500000, $this->config['cookie_path'], $this->config['cookie_domain']);
		
		// Just to be sure
		$this->cdata = array();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Extracts the data from a string into a one-dimensional array.
	 * 
	 * Safe implementation of a subset of unserialize's features.
	 * 
	 * @param  string
	 * @return array
	 */
	protected function extractData($str)
	{
		$ret = array();
		
		$parts = explode('$', $str);
		
		foreach($parts as $part)
		{
			$pair = explode(':', $part);
			
			if(count($pair) == 2)
			{
				$ret[base64_decode($pair[0])] = base64_decode($pair[1]);
			}
		}
		
		return $ret;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Compacts a one-dimensional array into a string.
	 * 
	 * @param  string
	 * @return string
	 */
	protected function compactData(array $array)
	{
		$str = array();
		
		foreach($array as $k => $v)
		{
			$str[] = base64_encode($k).':'.base64_encode($v);
		}
		
		return implode('$', $str);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a unique session token.
	 * 
	 * @return string
	 */
	public static function createToken()
	{
		// Type 4 UUID
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
            
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
            
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
            
			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}
}


/* End of file Session.php */
/* Location: ./lib/Inject */