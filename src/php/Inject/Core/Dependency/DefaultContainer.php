<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core\Dependency;

/**
 * Default container containing default services.
 */
class DefaultContainer extends Container
{
	protected $router;
	
	/**
	 * Default router.
	 * 
	 * TODO: Needed?
	 */
	public function getRouter()
	{
		if( ! $this->router)
		{
			$this->router = new \Inject\Router\Router($this->getEngine(), $this->parameters['debug']);
		}
		
		return $this->router;
	}
}

/* End of file DefaultContainer.php */
/* Location: src/php/Inject/Core/Dependency */