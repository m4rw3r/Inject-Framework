<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core\Dependency;

use \Inject\Core\Engine;

/**
 * 
 */
class Container implements ContainerInterface
{
	// TODO: Usage documentation
	
	/**
	 * The application engine, reachable through getEngine().
	 * 
	 * @var \Inject\Core\Engine
	 */
	protected $engine;
	
	/**
	 * The parameters for different dependencies.
	 * 
	 * @var array(string => mixed)
	 */
	protected $parameters = array();
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct(Engine $engine, $config_filename = 'Container.php')
	{
		$this->engine = $engine;
		
		if(file_exists($engine->paths['config'].$config_filename))
		{
			$this->parameters = include $engine->paths['config'].$config_filename;
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function getEngine()
	{
		return $this->engine;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @param  string
	 * @return mixed
	 */
	public function getParameter($key)
	{
		if( ! isset($this->parameters[$key]))
		{
			// TODO: Exception class
			throw new \Exception(sprintf('The parameter "%s" is not specified in this container.', $key));
		}
		
		return $this->parameters[$key];
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function getParameters()
	{
		return $this->parameters;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @param  string
	 * @param  mixed
	 * @return void
	 */
	public function setParameter($key, $value)
	{
		$this->parameters[$key] = $value;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns true if the parameter exists.
	 * 
	 * @param  string
	 * @return bool
	 */
	public function hasParameter($key)
	{
		return isset($this->parameters[$key]);
	}
}

/* End of file Container.php */
/* Location: src/php/Inject/Core/Dependency */