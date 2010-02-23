<?php
/*
 * Created by Martin Wernståhl on 2010-02-23.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
abstract class Inject_Request_HTTP_URI_RouteBuilderAbstract
{
	protected $controller_class;
	
	protected $controller_action;
	
	protected $parameters = array();
	
	protected $options = array();
	
	protected $pattern;
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct($pattern, array $options)
	{
		$this->pattern = $pattern;
		$this->options = $options;
		
		$this->parameters = array_diff_key($options, array('_class' => true, '_action' => true, '_controller' => true));
		
		if(isset($options['_controller']))
		{
			$this->controller_class = 'Controller_'.$options['_controller'];
			unset($options['_controller']);
		}
		elseif(isset($options['_class']))
		{
			$this->controller_class = $options['_class'];
		}
		
		if(isset($options['_action']))
		{
			$this->controller_action = $options['_action'];
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Should assemble the match code against $uri which also return the proper
	 * parameter array.
	 * 
	 * @return string
	 */
	abstract public function getMatchCode();
	
	// ------------------------------------------------------------------------
	
	/**
	 * Should validate the parameters and then assemble the proper code.
	 * 
	 * @return string
	 */
	abstract public function getReverseMatchCode();
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns true if this route has a controller class specified.
	 * 
	 * @return string
	 */
	public function hasClass()
	{
		return ! empty($this->controller_class);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns true if this route has an action specified.
	 * 
	 * @return bool
	 */
	public function hasAction()
	{
		return ! empty($this->controller_action);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns the lowercase name of the class which this route matches to.
	 * 
	 * @return string
	 */
	public function getClass()
	{
		return strtolower($this->controller_class);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns the lowercase name of the action which this route matches to.
	 * 
	 * @return string
	 */
	public function getAction()
	{
		return strtolower($this->controller_action);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the list of parameters which this route uses.
	 * 
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the options array which was specified for this parameter,
	 * used as return to Inject_Request_HTTP_URI.
	 * 
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}
}


/* End of file RouteBuilderInterface.php */
/* Location: ./lib/Inject/Request/HTTP/URI */