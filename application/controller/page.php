<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Controller_Page extends Inject_Controller
{
	function __construct(Inject_Request $req)
	{
		parent::__construct($req);
		
		$this->page = Inject::create('Model_Page');
	}
	
	public function index()
	{
		$page = isset($this->parameters['p']) ? $this->parameters['p'] : 'index';
		
		$data['page'] = $this->page->get_content($page);
		
		$data['nav'] = $this->page->get_navigation();
		
		$v = new View('template');
		
		$v->render($data);
	}
}


/* End of file doc.php */
/* Location: ./application/controller */