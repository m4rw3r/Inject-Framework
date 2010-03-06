<?php
/*
 * Created by Martin Wernståhl on 2010-03-05.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Profiler_Files implements Inject_ProfilerInterface
{
	protected $fw_path = '';
	
	protected $app_paths = array();
	
	protected $files_total_size = 0;
	
	protected $files = array();
	
	protected $lang;
	
	// ------------------------------------------------------------------------
	
	public function prepareData($end_time)
	{
		foreach(get_included_files() as $file)
		{
			$size = filesize($file);
			
			$this->files_total_size += $size;
			
			$this->files[] = array(
				'file' => $file,
				'size' => $size
			);
		}
		
		$this->fw_path = Inject::getFrameworkPath();
		$this->app_paths = Inject::getApplicationPaths();
		
		$this->lang = new Inject_I18n('Profiler');
	}
	
	// ------------------------------------------------------------------------
	
	public function renderTabContents($id, $on_click)
	{
		return "<li id='$id' $on_click>".count($this->files).' '.$this->lang->files."</li>";
	}
	
	// ------------------------------------------------------------------------
	
	public function renderBoxContents()
	{
		$str = '<div class="IFW-THead">
			<div class="IFW-Cell">
				<strong>'.$this->lang->fw_path.': </strong> '.$this->fw_path.'
			</div>
			
			<div class="IFW-Cell">
				<strong>'.$this->lang->app_paths.':</strong>
				
				<ol>';
		
		foreach($this->app_paths as $p)
		{
			$str .= '<li>'.$p.'</li>';
		}
		
		$str .= '		</ol>
			</div>
			
			<span class="IFW-Clear"></span>
		</div>
		
		<h3>'.$this->lang->inc_files.'<span style="float:right; font-size: 0.75em">Total: '.Inject_Util::humanReadableSize($this->files_total_size).'</h3>';
		
		
		foreach($this->files as $file)
		{
			$str .= '<div class="IFW-Row">
			<div class="IFW-Cell" style="width: 655px;">'.$file['file'].'</div>
			<div class="IFW-Cell" style="width: 80px; text-align: right;">'.Inject_Util::humanReadableSize($file['size']).'</div>
			<span class="IFW-Clear"></span>
		</div>';
		}
		
		return $str;
	}
}


/* End of file Console.php */
/* Location: ./lib/Inject/Profiler */