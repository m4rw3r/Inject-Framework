<?php
/*
 * Created by Martin Wernståhl on 2011-04-03.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;

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
	public function __construct($parent, $name, array $options = array())
	{
		parent::__construct($parent);
		
		// TODO: Check default route names
		// TODO: Make the route names customizable
		// TODO: How to do with REQUEST_METHOD for reverse routing?
		
		$this->name = $name;
		$path       = empty($options['path'])       ? $name : $options['path'];
		$controller = empty($options['controller']) ? $name : $options['controller'];
		
		// Defaults, will cascade down to sub-resources
		$this->path($path);
		$this->to($controller.'#');
		
		$this->match()->via('GET') ->to('#index')  ->name($name);
		$this->match()->via('POST')->to('#create') ->name('create_'.$name);
		$this->match('new')        ->to('#newform')->name('new_'.$name);
		
		// With :id capture, will cascade down to the sub-resources
		$this->path('/:'.$name.'_id');
		
		$this->match()->via('GET')   ->to('#show')   ->name('show_'.$name);
		$this->match()->via('PUT')   ->to('#update') ->name('update_'.$name);
		$this->match()->via('DELETE')->to('#destroy')->name('delete_'.$name);
		
		$this->match('/edit')->to('#edit')->name('edit_'.$name);
	}
}


/* End of file Resource.php */
/* Location: src/php/Inject/Web/Router/Generator */