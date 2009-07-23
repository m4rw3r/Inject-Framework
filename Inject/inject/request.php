<?php
/*
 * Created by Martin Wernståhl on 2009-07-18.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * An interface for the objects which handle requests.
 */
interface Inject_Request
{
	/**
	 * Returns the class name of the controller, false if none is defined.
	 * 
	 * @return string|false
	 */
	public function get_controller();
	
	/**
	 * Returns the name of the action to call, false if none is defined.
	 * 
	 * @return string|false
	 */
	public function get_action();
	
	/**
	 * Returns the requested parameter, $default is returned if the parameter isn't found.
	 * 
	 * @param  string
	 * @return string|null
	 */
	public function get_parameter($name, $default = null);
	
	/**
	 * Returns the URI used by this request (ie. everything after the front controller name).
	 * 
	 * @return string
	 */
	public function get_uri();
	
	/**
	 * Returns the request type.
	 * 
	 * @return string
	 */
	public function get_type();
	
	/**
	 * Returns the response object, needs to be the same for a single Request instance.
	 * 
	 * @return Inject_Response
	 */
	public function get_response();
}


/* End of file request.php */
/* Location: ./Inject/inject */