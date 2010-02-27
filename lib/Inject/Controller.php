<?php
/*
 * Created by Martin Wernståhl on 2010-02-26.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * The standard controller class which also contains logic for
 * rendering templates.
 */
class Inject_Controller extends Inject_Controller_Base
{
	protected $renderer;
	
	protected $default_template_prefix;
	
	// ------------------------------------------------------------------------

	/**
	 * Initializes the template renderer, default renderer is the
	 * Inject_Controller_Renderer_PHP.
	 * 
	 * @return void
	 */
	public function initRenderer()
	{
		$this->renderer = new Inject_Controller_Renderer_PHP($this->request);
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
		if(empty($this->renderer))
		{
			$this->initRenderer();
			
			// Do we have a default template prefix?
			if(empty($this->default_template_prefix))
			{
				// Fix the controller name prefix used by the templates
				$class = strtolower(get_class($this));
				$this->default_template_prefix = strtr(strpos($class, 'controller_') === 0 ? substr($class, 11) : $class, '\\_', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
			}
		}
		
		// Is it relative?
		if(strpos($view_name, '/') !== 0)
		{
			// Yes
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