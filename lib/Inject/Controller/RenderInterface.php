<?php
/*
 * Created by Martin Wernståhl on 2010-02-26.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

interface Inject_Controller_RenderInterface
{
	public function render($view_name, $data = array(), $file_ext = 'php');
}

/* End of file RenderInterface.php */
/* Location: ./lib/Inject/Controller */