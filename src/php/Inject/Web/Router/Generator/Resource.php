<?php
/*
 * Created by Martin Wernståhl on 2011-04-03.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;

use \Inject\Core\Engine;

/**
 * Object returned by the router's resource() method, used to represent a route
 * before it is compiled and/or cached.
 * 
 * 
 * TODO: Make it possible to exclude or rename REST actions
 */
class Resource extends Scope
{
	
	protected $name;
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct(Engine $engine, $parent, $name, array $options = array())
	{
		parent::__construct($engine, $parent);
		
		$this->name = $name;
		$path       = empty($options['path'])       ? $name : $options['path'];
		$controller = empty($options['controller']) ? $name : $options['controller'];
		
		// Defaults, will cascade down to sub-resources
		$this->path($path);
		$this->to($controller.'#');
		
		$this->match()->via('GET') ->to('#index');
		$this->match()->via('POST')->to('#create');
		$this->match('new')        ->to('#newform');
		
		// With :id capture, will cascade down to the sub-resources
		$this->path('/:'.$name.'_id');
		
		// TODO: Merge into a larger route to prevent running preg_match for the same PATH_INFO multiple times?
		// TODO: cont. requires a specific route or controller class :(
		$this->match()->via('GET')   ->to('#show');
		$this->match()->via('PUT')   ->to('#update');
		$this->match()->via('DELETE')->to('#destroy');
		
		$this->match('/edit')->to('#edit');
	}
	
}


/* End of file Resource.php */
/* Location: src/php/Inject/Web/Router/Generator */