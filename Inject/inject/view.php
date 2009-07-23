<?php
/*
 * Created by Martin Wernståhl on 2009-07-12.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_View
{
	/**
	 * The data array with the data to send to the view.
	 */
	protected $data = array();
	
	/**
	 * The path to the file to include.
	 * 
	 * @var string
	 */
	protected $file;
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct($path)
	{
		// TODO: Content type
		
		$path = DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . str_replace(INJECT_FRAMEWORK_EXT, '', $path) . INJECT_FRAMEWORK_EXT;
		
		foreach(Inject::get_paths() as $p)
		{
			if(file_exists($p . $path))
			{
				$this->file = $p . $path;
				
				break;
			}
		}
		
		if(empty($this->file))
		{
			throw new Inject_Exception_MissingFile($path);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function set_data($data = array())
	{
		$this->data = array_merge($this->data, $data);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Renders this view.
	 * 
	 * @param  array
	 * @param  bool
	 * @return string|void
	 */
	public function render($__data = array(), $__return = false)
	{
		extract(array_merge($this->data, $__data));
		
		ob_start();
		
		include $this->file;
		
		$__buffer = ob_get_contents();
		ob_end_clean();
		
		if($__return)
		{
			return $__buffer;
		}
		else
		{
			Inject::append_output($__buffer);
		}
	}
}


/* End of file view.php */
/* Location: ./Inject/inject */