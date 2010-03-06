<?php
/*
 * Created by Martin Wernståhl on 2010-03-05.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Profiler_RapidDataMapper implements Inject_ProfilerInterface
{
	protected $database_loaded = false;
	
	protected $queries = array();
	
	protected $lang;
	
	// ------------------------------------------------------------------------
	
	public function prepareData($end_time)
	{
		// Check if it is loaded, both the Db class and Db_Connection classes are
		// needed to connect to the db
		if(class_exists('Db', false) && class_exists('Db_Connection', false))
		{
			$this->database_loaded = true;
			
			foreach(Db::getLoadedConnections() as $conn)
			{
				foreach($conn->queries as $k => $q)
				{
					$this->queries[] = array(
						'time' => $conn->query_times[$k],
						'query' => $q
					);
				}
			}
		}
		
		$this->lang = new Inject_I18n('Profiler');
	}
	
	// ------------------------------------------------------------------------
	
	public function renderTabContents($id, $on_click)
	{
		return "<li id='$id' $on_click>".count($this->queries)." queries</li>";
	}
	
	// ------------------------------------------------------------------------
	
	public function renderBoxContents()
	{
		if($this->database_loaded)
		{
			$str = '<div class="IFW-THead">
				<div class="IFW-Cell" style="width: 60px">'.$this->lang->time.'</div>
				<div class="IFW-Cell" style="width: 665px">'.$this->lang->query.'</div>
				<span class="IFW-Clear"></span>
			</div>';
			
			foreach($this->queries as $q)
			{
				$str .= '<div class="IFW-Row">
					<div class="IFW-Cell" style="width: 60px">'.number_format($q['time'] * 1000, 4).' ms</div>
					<div class="IFW-Cell" style="width: 665px">'.htmlspecialchars($q['query'], ENT_COMPAT, 'UTF-8').'</div>
					<span class="IFW-Clear"></span>
				</div>';
			}
		}
		else
		{
			$str = '<h2>'.$this->lang->db_not_loaded.'</h2>';
		}
		
		return $str;
	}
}


/* End of file Console.php */
/* Location: ./lib/Inject/Profiler */