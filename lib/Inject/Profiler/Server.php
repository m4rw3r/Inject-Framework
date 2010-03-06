<?php
/*
 * Created by Martin Wernståhl on 2010-03-05.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Profiler_Server implements Inject_ProfilerInterface
{
	protected $start_time;
	
	protected $end_time;
	
	protected $memory;
	
	protected $memory_limit;
	
	protected $headers = array();
	
	protected $lang;
	
	function __construct()
	{
		$this->start_time = empty($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : microtime(true);
	}
	
	// ------------------------------------------------------------------------
	
	public function prepareData($end_time)
	{
		$this->end_time = $end_time;
		
		$this->allowed_time = ini_get('max_execution_time');
		
		// Fetch memory data
		$prefixes = array('k' => 1024, 'm' => 1048576, 'g' => 1073741824);
		
		$this->memory = memory_get_peak_usage();
		$ml = ini_get('memory_limit');
		
		preg_match('/(\d*)\s*([TGMK]*)/i', $ml, $m);
		
		$prefix = isset($prefixes[strtolower($m[2])]) ? $prefixes[strtolower($m[2])] : 1;
		
		$this->memory_limit = $m[1] * $prefix;
		
		$this->headers = array_merge(array('HTTP/1.1' => Inject::$response_code), Inject::$headers);
		
		$this->lang = new Inject_I18n('Profiler');
	}
	
	// ------------------------------------------------------------------------
	
	public function renderTabContents($id, $on_click)
	{
		return "<li id=\"$id\" $on_click>".number_format(($this->end_time - $this->start_time) * 1000, 4).' ms <span style="display:inline; margin: 0 0 0 10px; color: #fff">'.number_format($this->memory / 1024, 0).' kB</span></li>';
	}
	
	// ------------------------------------------------------------------------
	
	public function renderBoxContents()
	{
		// TODO: Move the GET, POST etc. to properties which have been UTF8 cleaned
		$str = '<div class="IFW-THead">
			<div class="IFW-Cell">
				<strong>'.$this->lang->tot_exec_time .': </strong> '.number_format(($this->end_time - $this->start_time) * 1000, 4) .' ms
			</div>
			
			<div class="IFW-Cell">
				<strong>'.$this->lang->max_exec_time .': </strong> '.$this->allowed_time .' s
			</div>

			<div class="IFW-Cell">
				<strong>'.$this->lang->tot_mem_used .': </strong> '.Inject_Util::humanReadableSize($this->memory) .'
			</div>

			<div class="IFW-Cell">
				<strong>'.$this->lang->max_mem_used .': </strong> '.Inject_Util::humanReadableSize($this->memory_limit) .'
			</div>
			
			<span class="IFW-Clear"></span>
		</div>
		
		<h3>'.$this->lang->headers .'</h3>
		
		';
		
		foreach($this->headers as $k => $v)
		{
			$str .= '<div class="IFW-Row">
			<div class="IFW-Cell" style="width: 160px">'.htmlspecialchars($k, ENT_COMPAT, 'UTF-8') .'</div>
			<div class="IFW-Cell" style="width: 565px">'.htmlspecialchars($v, ENT_COMPAT, 'UTF-8') .'</div>
			<span class="IFW-Clear"></span>
		</div>';
		}
		
		$str .= '<h3>'.$this->lang->server_vars .'</h3>';
		
		foreach($_SERVER as $k => $v)
		{
			$str .= '<div class="IFW-Row">
			<div class="IFW-Cell" style="width: 160px">'.htmlspecialchars($k, ENT_COMPAT, 'UTF-8') .'</div>
			<div class="IFW-Cell" style="width: 565px">'.htmlspecialchars($v, ENT_COMPAT, 'UTF-8') .'</div>
			<span class="IFW-Clear"></span>
		</div>';
		}
		
		$str .= '<h3>GET '.$this->lang->data .'</h3>';
		
		if( ! empty($_GET))
		{
			foreach($_GET as $k => $v)
			{
				$str .= '<div class="IFW-Row">
			<div class="IFW-Cell" style="width: 160px">'.htmlspecialchars($k, ENT_COMPAT, 'UTF-8') .'</div>
			<div class="IFW-Cell" style="width: 565px">'.htmlspecialchars($v, ENT_COMPAT, 'UTF-8') .'</div>
			<span class="IFW-Clear"></span>
		</div>';
			}
		}
		else
		{
			$str .= '<p>
			'.$this->lang->no_get .'
		</p>';
		}
		
		$str .= '<h3>POST '.$this->lang->data .'</h3>';
		
		if( ! empty($_POST))
		{
			foreach($_POST as $k => $v)
			{
				$str .= '<div class="IFW-Row">
			<div class="IFW-Cell" style="width: 160px">'.htmlspecialchars($k, ENT_COMPAT, 'UTF-8') .'</div>
			<div class="IFW-Cell" style="width: 565px">'.htmlspecialchars($v, ENT_COMPAT, 'UTF-8') .'</div>
			<span class="IFW-Clear"></span>
		</div>';
			}
		}
		else
		{
			$str .= '<p>
			'.$this->lang->no_post .'
		</p>';
		}
		
		$str .= '<h3>'.$this->lang->env_vars .'</h3>';
		
		if( ! empty($_ENV))
		{
			foreach($_ENV as $k => $v)
			{
				$str .= '<div class="IFW-Row">
			<div class="IFW-Cell" style="width: 160px">'.htmlspecialchars($k, ENT_COMPAT, 'UTF-8') .'</div>
			<div class="IFW-Cell" style="width: 565px">'.htmlspecialchars($v, ENT_COMPAT, 'UTF-8') .'</div>
			<span class="IFW-Clear"></span>
		</div>	';
			}
		}
		else
		{
			$str .= '<p>
			'.$this->lang->no_env .'
		</p>';
		}
		
		$str .= '<h3>'.$this->lang->cookies .'</h3>';
		
		if( ! empty($_COOKIES))
		{
			foreach($_COOKIES as $k => $v)
			{
				$str .= '<div class="IFW-Row">
			<div class="IFW-Cell" style="width: 160px">'.htmlspecialchars($k, ENT_COMPAT, 'UTF-8') .'</div>
			<div class="IFW-Cell" style="width: 565px">'.htmlspecialchars($v, ENT_COMPAT, 'UTF-8') .'</div>
			<span class="IFW-Clear"></span>
		</div>	';
			}
		}
		else
		{
			$str .= '<p>
			'.$this->lang->no_cookies .'
		</p>';
		}
		
		$str .= '<h3>'.$this->lang->session .'</h3>';
		
		if( ! empty($_SESSION))
		{
			foreach($_SESSION as $k => $v)
			{
				$str .= '<div class="IFW-Row">
			<div class="IFW-Cell" style="width: 160px">'.htmlspecialchars($k, ENT_COMPAT, 'UTF-8') .'</div>
			<div class="IFW-Cell" style="width: 565px">'.htmlspecialchars($v, ENT_COMPAT, 'UTF-8') .'</div>
			<span class="IFW-Clear"></span>
		</div>	';
			}
		}
		else
		{
			$str .= '<p>
			'.$this->lang->no_session .'
		</p>';
		}
		
		return $str;
	}
}


/* End of file Console.php */
/* Location: ./lib/Inject/Profiler */