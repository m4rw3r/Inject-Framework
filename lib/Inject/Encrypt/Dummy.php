<?php
/*
 * Created by Martin Wernståhl on 2010-01-06.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Dummy encryptor, DO NOT USE WHEN NOT TESTING Access components!
 */
class Inject_Encrypt_Dummy implements Inject_EncryptInterface
{
	public function encryptStr($str)
	{
		return $str;
	}
	public function decryptStr($str)
	{
		return $str;
	}
	public function encryptStrSalted($str, $salt)
	{
		return $str;
	}
	public function decryptStrSalted($str, $salt)
	{
		return $str;
	}
}

/* End of file Dummy.php */
/* Location: ./lib/Inject/Encrypt */