<?php
/*
 * Created by Martin Wernståhl on 2010-02-23.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

interface Inject_Request_HTTP_URI_RouterInterface
{
	/**
	 * Returns the appropriate routing configuration.
	 * 
	 * @param  string
	 * @return array
	 */
	public function matches($uri);
	
	/**
	 * Returns the appropriate reverse route of the supplied parameters.
	 * 
	 * @param  array
	 * @return string
	 */
	public function reverseRoute(array $params);
}

/* End of file RouterInterface.php */
/* Location: ./lib/Inject/Request/HTTP/URI */