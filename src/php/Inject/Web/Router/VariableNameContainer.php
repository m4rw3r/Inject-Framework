<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router;

/**
 * 
 */
class VariableNameContainer implements Generator\VariableNameContainerInterface
{
	public function getPathVariable()
	{
		return '$env[\'PATH_INFO\']';
	}
	
	// ------------------------------------------------------------------------
	
	public function wrapInPathParamsVarAssignment($statement)
	{
		return '$env[\'web.route_params\'] = '.$statement.';';
	}
	
	// ------------------------------------------------------------------------
	
	public function getClosureParamsList()
	{
		return array('$env');
	}
	
	// ------------------------------------------------------------------------
	
	public function getClosureUseList()
	{
		return array('$engine', '$controllers');
	}
	
	// ------------------------------------------------------------------------
	
	public function getEnvVar()
	{
		return '$env';
	}
	
	// ------------------------------------------------------------------------
	
	public function getAvailableControllersVar()
	{
		return '$controllers';
	}
	
	// ------------------------------------------------------------------------
	
	public function getEngineVar()
	{
		return '$engine';
	}
	
	// ------------------------------------------------------------------------
	
	public function getIssetParamCode($name)
	{
		return 'isset($env['.var_export($name, true).'])';
	}
	
	// ------------------------------------------------------------------------
	
	public function getParamCode($name)
	{
		return '$env['.var_export($name, true).']';
	}
}


/* End of file Router.php */
/* Location: src/php/Inject/Web/Router */