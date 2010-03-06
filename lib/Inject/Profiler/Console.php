<?php
/*
 * Created by Martin Wernståhl on 2010-03-05.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Profiler_Console implements Inject_ProfilerInterface, Inject_LoggerInterface
{
	protected $start_time;
	
	protected $lang;
	
	protected $log = array();
	
	protected $log_summary = array();
	
	function __construct()
	{
		$this->start_time = empty($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : microtime(true);
		
		Inject::attachLogger($this);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds a log message, with the time it was issued.
	 * 
	 * @param  string
	 * @param  string
	 * @param  int
	 * @return void
	 */
	public function addMessage($namespace, $message, $level)
	{
		$this->log[] = array(
			'time' => microtime(true) - $this->start_time,
			'name' => $namespace,
			'message' => $message,
			'level' => $level
		);
	}
	
	// ------------------------------------------------------------------------
	
	public function prepareData($end_time)
	{
		$this->log_summary = array(
				Inject::ERROR => 0,
				Inject::WARNING => 0,
				Inject::NOTICE => 0,
				Inject::DEBUG => 0
			);
        
		foreach($this->log as $msg)
		{
			$this->log_summary[$msg['level']]++;
		}
		
		$this->lang = new Inject_I18n('Profiler');
	}
	
	// ------------------------------------------------------------------------
	
	public function renderTabContents($id, $on_click)
	{
		$str = '';
		
		foreach(array(Inject::DEBUG => $this->lang->dbg_msgs,
			  Inject::NOTICE => $this->lang->notices,
			  Inject::WARNING => $this->lang->warnings,
			  Inject::ERROR => $this->lang->errors) as $c => $s)
		{
			if( ! empty($this->log_summary[$c]))
			{
				$str = '<li id="'.$id.'" class="IFW-'.strtoupper(Inject_Util::errorConstToStr($c)).'" '.$on_click.'>'.$this->log_summary[$c].' '.$s.'</li>';
			}
		}
		
		return $str;
	}
	
	// ------------------------------------------------------------------------
	
	public function renderBoxContents()
	{
		
		$str = '<div class="IFW-THead">
			<div class="IFW-Cell'.(empty($this->log_summary[Inject::ERROR]) ? '' : ' IFW-ERROR').'" style="width: 120px"><strong>'.$this->lang->errors.': </strong> '.$this->log_summary[Inject::ERROR].'</div>
			<div class="IFW-Cell'.(empty($this->log_summary[Inject::WARNING]) ? '' : ' IFW-WARNING').'" style="width: 120px"><strong>'.$this->lang->warnings.': </strong> '.$this->log_summary[Inject::WARNING].'</div>
			<div class="IFW-Cell'.(empty($this->log_summary[Inject::NOTICE]) ? '' : ' IFW-NOTICE').'" style="width: 120px"><strong>'.$this->lang->notices.': </strong> '.$this->log_summary[Inject::NOTICE].'</div>
			<div class="IFW-Cell'.(empty($this->log_summary[Inject::DEBUG]) ? '' : ' IFW-DEBUG').'" style="width: 120px"><strong>'.$this->lang->dbg_msgs.': </strong> '.$this->log_summary[Inject::DEBUG].'</div>
			<span class="IFW-Clear"></span>
		</div>
		
		<h3>'.$this->lang->log.'</h3>
		
		<div class="IFW-THead">
			<div class="IFW-Cell" style="width: 60px">'.$this->lang->level.'</div>
			<div class="IFW-Cell" style="width: 70px">'.$this->lang->time.'</div>
			<div class="IFW-Cell" style="width: 70px">'.$this->lang->source.'</div>
			<div class="IFW-Cell" style="width: 485px">'.$this->lang->message.'</div>
			<span class="IFW-Clear"></span>
		</div>
		';
		
		foreach($this->log as $row)
		{
			$str .= '<div class="IFW-Row">
			<div class="IFW-Cell IFW-'.($s = Inject_Util::errorConstToStr($row['level'])).'" style="width: 60px">'.$s.'</div>
			<div class="IFW-Cell" style="width: 70px">'.number_format($row['time'] * 1000, 4).' ms</div>
			<div class="IFW-Cell" style="width: 70px">'.$row['name'].'</div>
			<div class="IFW-Cell" style="width: 485px">'.htmlspecialchars($row['message'], ENT_COMPAT, 'UTF-8').'</div>
			<span class="IFW-Clear"></span>
		</div>';
		}
		
		return $str;
	}
}


/* End of file Console.php */
/* Location: ./lib/Inject/Profiler */