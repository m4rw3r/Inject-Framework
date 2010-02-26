<?php
/*
 * Created by Martin Wernståhl on 2010-02-26.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Controller_Renderer_PHP implements Inject_Controller_RenderInterface
{
	protected $_helper_mappings = array();
	
	protected $_helpers = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct(Inject_Request $req)
	{
		$this->request = $req;
		
		$this->_helper_mappings['uri'] = 'Helper_Uri';
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Adds an already instantiated helper object to this renderer.
	 * 
	 * @param  string
	 * @param  object
	 * @return self
	 */
	public function addHelper($key, $helper)
	{
		$this->_helpers[$key] = $helper;
		
		$this->$key = $helper;
		
		return $this;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Loads the helper $key, as if this method is triggered, then it is not loaded.
	 * 
	 * @param  string
	 * @return object
	 */
	public function __get($key)
	{
		return $this->loadHelper($key);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Loads a helper.
	 * 
	 * @param  string
	 * @return object
	 */
	public function loadHelper($key)
	{
		if(isset($this->_helper_mappings[$key]))
		{
			// TODO: Add a way for dependency injection of helpers, so they are loaded *properly* when needed
			
			$cls = $this->_helper_mappings[$key];
			$this->$key = $this->_helpers[$key] = new $cls();
			
			return $this->_helpers[$key];
		}
		else
		{
			throw new Exception(sprintf('Helper key "%s" does not have an associated helper.', $key));
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Renders a view using the default template renderer which uses PHP to process
	 * the templates.
	 * 
	 * @param  string	The path to the file, relative to the Views folder, except file extension
	 * @param  array	The data to render, will be extracted to variables in the view
	 * @param  bool		If to return the rendered content to the caller
	 * @param  string	The file extension of the file to render
	 * @return string|void
	 */
	public function render($view_name, $data = array(), $file_ext = 'php')
	{
		$__found_file = false;
		
		extract($data);
		
		ob_start();
		
		// Find the view
		foreach(Inject::getApplicationPaths() as $p)
		{
			if(file_exists($p.'Views/'.$view_name.'.'.$file_ext))
			{
				$__found_file = true;
				
				include $p.'Views/'.$view_name.'.'.$file_ext;
				
				break;
			}
		}
		
		// get content
		$buffer = ob_get_contents();
		ob_end_clean();
		
		if( ! $__found_file)
		{
			// Replace with appropriate exception
			throw new Exception('Cannot find view "'.$view_name.'" filetype: "'.$file_ext.'"');
		}
		
		return $buffer;
	}
}


/* End of file PHP.php */
/* Location: ./lib/Inject/Controller/Renderer */