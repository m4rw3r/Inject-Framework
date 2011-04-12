<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;

/**
 * 
 */
interface DestinationHandlerInterface
{
	/**
	 * Parses the $to value given to a Mapping and returns false if this
	 * destination does not match, and an array containing the new value if it does.
	 * 
	 * @param  mixed
	 * @param  mixed
	 * @return DestinationHandler
	 */
	public static function parseTo($new, Mapping $mapping, $old);
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct(Mapping $mapping);
	
	public function getConditions($env_var, $capture_dest_array, $controller_var);
}


/* End of file DestinationHandler.php */
/* Location: src/php/Inject/Web/Router/Generator */