<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;

use \Inject\Core\Engine;

/**
 * 
 */
interface DestinationHandlerInterface
{
	/**
	 * Parses the $to value given to a Mapping and returns false if this
	 * destination does not match, and a DestinationHandlerInterface object if it does.
	 * 
	 * @param  mixed    The new to() value
	 * @param  Mapping  The mapping
	 * @param  mixed    The old to() value
	 * @return DestinationHandlerInterface
	 */
	public static function parseTo($new, Mapping $mapping, $old);
	
	// ------------------------------------------------------------------------

	/**
	 * @param  Mapping
	 */
	public function __construct(Mapping $mapping);
	
	/**
	 * Prepares the destination handler for validation, called first after
	 * __construct().
	 * 
	 * @return void
	 */
	public function prepare();
	
	/**
	 * Validates the settings supplied on the Mapping object, check with the Engine
	 * if controllers exist etc. , run after prepare().
	 * 
	 * @param  \Inject\Core\Engine
	 * @return void
	 */
	public function validate(Engine $engine);
	
	/**
	 * Prepares the contents of this DestinationHandlerInterface for all the other
	 * operations.
	 * 
	 * @return void
	 */
	public function compile();
	
	/**
	 * Returns the name of the wrapped route, run after prepare().
	 * 
	 * @return string
	 */
	public function getName();
	
	/**
	 * Returns the tokens used for the URI assembler, ie. the tokens directly
	 * from the tokenization of the path pattern, run after prepare().
	 * 
	 * @return array  List of tokens from Tokenizer->getTokens()
	 */
	public function getTokens();
	
	/**
	 * Returns the capture intersection array from the wrapped mapping object,
	 * run after prepare().
	 * 
	 * @var array(string => int)
	 */
	public function getCaptureIntersect();
	
	/**
	 * Returns the route options which will be added to $env['web.route_params'],
	 * run after prepare().
	 * 
	 * @var array(string => string)
	 */
	public function getOptions();
	
	/**
	 * Returns an array of PHP-code conditions in their order of importance all of
	 * which must match for the route to match, run after prepare().
	 * 
	 * TODO: Document more thoroughly?
	 * 
	 * @param  string  Variable name of the Environment variable ($env)
	 * @param  string  Variable the preg captures should be placed in, no need to
	 *                 consider existing keys, this variable will be merged into
	 *                 another anyway
	 * @param  string  Variable name of the available controller mappings array,
	 *                 this array contains a list of short controller names as
	 *                 the key, and the class name of that controller as value.
	 * @return array(string)  Array of condition strings, to be inserted into if():s
	 */
	public function getConditions($env_var, $capture_dest_array, $controller_var);
	
	/**
	 * Returns the code to be run which will return the response from the attached destination,
	 * run after prepare().
	 * 
	 * @param  string  The variable name of the $env var  (contains Environment hash)
	 * @param  string  The variable name of the $engine var (contains Engine instance)
	 * @param  string  The variable containing the regular expression matches from preg_match
	 * @param  string  The variable containing a hash with short_controller_name => class_name
	 * @return string
	 */
	public function getCallCode($env_var, $engine_var, $matches_var, $controller_var);
}


/* End of file DestinationHandler.php */
/* Location: src/php/Inject/Web/Router/Generator */