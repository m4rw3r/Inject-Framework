<?php
/*
 * Created by Martin Wernståhl on 2010-02-26.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Controller extends Inject_Controller_Base
{
	protected $renderer;
	
	protected $default_template_prefix;
	
	function __construct(Inject_Request $req)
	{
		parent::__construct($req);
		
		// Assign default template prefix, by default the controller name
		$this->default_template_prefix = str_replace('<Controller_', '', '<'.get_class($this));
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Initializes the template renderer if it isn't already initialized.
	 * 
	 * @return void
	 */
	public function initRenderer()
	{
		if(empty($this->renderer))
		{
			$this->renderer = new Inject_Controller_Renderer_PHP($this->request);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Renders a view using the renderer initialized by initRenderer().
	 * 
	 * @param  string       The path to the file, if it starts with a slash ("/"),
	 *                      then it is relative to the Views directory, otherwise
	 *                      it is relative to the folder Views/$controllername
	 * @param  array        The data to render, will be extracted to variables
	 *                      in the view
	 * @param  bool         If to return the rendered content to the caller
	 * @param  string       The file extension of the file to render (no dot)
	 * @return string|void
	 */
	public function render($view_name, $data = false, $return = false, $type = 'php')
	{
		$this->initRenderer();
		
		if(strpos($view_name, '/') !== 0)
		{
			$view_name = $this->default_template_prefix.'/'.$view_name;
		}
		
		$buffer = $this->renderer->render($view_name, empty($data) ? array() : $data, $type);
		
		if($return)
		{
			return $buffer;
		}
		else
		{
			$this->response->body = $buffer;
		}
	}
}


/* End of file Controller.php */
/* Location: ./lib/Inject */