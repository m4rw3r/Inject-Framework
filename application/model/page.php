<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Model_Page
{
	function __construct()
	{
		Inject::load('database');
	}
	
	public function get_content($page)
	{
		return array();
		return Db::find('Record_Page', array('path' => $page));
	}
}


/* End of file doc.php */
/* Location: ./application/controller */