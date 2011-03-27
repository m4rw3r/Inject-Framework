<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Core\Dependency;

interface ContainerInterface
{
	public function getEngine();
	
	public function getParameter($name);
	
	public function setParameter($name, $value);
	
	public function hasParameter($name);
}

/* End of file ContainerInterface.php */
/* Location: src/php/Inject/Core/Dependency */