<?php
/*
 * Created by Martin Wernståhl on 2010-03-05.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
interface Inject_ProfilerInterface
{
	public function prepareData($end_time);
	
	/**
	 * Renders the contents of the tab which will show the box containing
	 * the detailed data.
	 * 
	 * Should be rendered as a <li></li> element with contents.
	 * 
	 * @param  string  The class which this tab should have
	 * @param  string  The onclick attribute this tab should have
	 * @return string
	 */
	public function renderTabContents($id, $on_click);
	
	/**
	 * Renders the detailed data which goes in the box which is shown by clicking
	 * on the tab.
	 * 
	 * @return string
	 */
	public function renderBoxContents();
}


/* End of file ProfilerInterface.php */
/* Location: ./lib/Inject */