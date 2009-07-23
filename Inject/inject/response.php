<?php
/*
 * Created by Martin Wernståhl on 2009-07-18.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * An interface for the objects which handle requests.
 */
interface Inject_Response
{
	/**
	 * Returns the contents of this response.
	 * 
	 * @return string
	 */
	public function output_content();
}


/* End of file response.php */
/* Location: ./Inject/inject */