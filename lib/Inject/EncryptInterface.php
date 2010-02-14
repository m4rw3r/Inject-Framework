<?php
/*
 * Created by Martin Wernståhl on 2010-01-06.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

interface Inject_EncryptInterface
{
	/**
	 * Encrypts a string.
	 * 
	 * Should be secure; use at least a salt and an encryption key.
	 * 
	 * @param  string
	 * @return string
	 */
	public function encryptStr($str);
	/**
	 * Decrypts a string made by encryptStr().
	 * 
	 * @param  string
	 * @return string
	 */
	public function decryptStr($str);
	/**
	 * Encrypts a string with an extra salt which is added to the encrypt key,
	 * or which affects the encryption in some way.
	 * 
	 * @param  string
	 * @param  string
	 * @return string
	 */
	public function encryptStrSalted($passwd, $salt);
	/**
	 * Decrypts a string with an extra salt which is somehow added to the encrypt key.
	 * 
	 * @param  string
	 * @param  string
	 * @return string
	 */
	public function decryptStrSalted($passwd, $salt);
}

/* End of file EncryptInterface.php */
/* Location: ./lib/Inject */