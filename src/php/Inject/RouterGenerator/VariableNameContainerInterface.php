<?php
/*
 * Created by Martin Wernståhl on 2011-05-14.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\RouterGenerator;

/**
 * 
 */
interface VariableNameContainerInterface
{
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the variable name of the variable which contains the URI to match
	 * against.
	 * 
	 * @return string
	 */
	public function getPathVariable();
	
	// ------------------------------------------------------------------------

	/**
	 * Wraps the supplied PHP code in an assignment which assigns it to the path
	 * parameter variable/key, must return a semicolon-terminated line, or empty
	 * if they aren't used.
	 * 
	 * @param  string
	 * @return string
	 */
	public function wrapInPathParamsVarAssignment($statement);
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of parameters to be put in the parameter list of the closure.
	 * 
	 * @return array(string)
	 */
	public function getClosureParamsList();
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of variables to be put in the use() part.
	 * 
	 * @return array(string)
	 */
	public function getClosureUseList();
	
	// ------------------------------------------------------------------------

	/**
	 * Returns an isset() fragment returning true if the specified parameter value
	 * is set, empty if no need to validate.
	 * 
	 * @param  string  The name of the variable.
	 * @return string
	 * @throws Exception   If the $name parameter is not allowed
	 */
	public function getIssetParamCode($name);
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a PHP fragment returning the value of the specified parameter.
	 * 
	 * @param  string  The name of the variable.
	 * @return string
	 * @throws Exception   If the $name parameter is not allowed
	 */
	public function getParamCode($name);
}


/* End of file VariableNameContainerInterface.php */
/* Location: src/php/Inject/Web/Router/Generator */