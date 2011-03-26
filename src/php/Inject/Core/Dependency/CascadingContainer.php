<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core\Dependency;

use \Inject\Core\Application\Engine;

/**
 * Dynamic dependency injection container, cascades calls for
 * missing dependencies to a parent container, also contains default services.
 */
class CascadingContainer extends DefaultContainer
{
	// TODO: More documentation
	
	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct(Engine $engine, ContainerInterface $parent)
	{
		$this->engine     = $engine;
		$this->parent     = $parent;
		$this->parameters = $parent->getParameters();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Imitates get<ServiceName>() for the dynamic dependencies.
	 * 
	 * @param  string
	 * @param  array
	 * @return mixed
	 */
	public function __call($method, array $args = array())
	{
		switch(strtolower(substr($method, 0, 3)))
		{
			case 'get':
				// Does not use $args, as get<ServiceName>() does not take parameters
				return $this->parent->$method();
		}
		
		throw new \RuntimeException(sprintf('Call to undefined method %s::%s', get_class($this), $method));
	}
}

/* End of file CascadingContainer.php */
/* Location: php/src/Inject/Core/Dependency */