<?php
/*
 * Created by Martin Wernståhl on 2009-12-29.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Profiler implements Inject_LoggerInterface
{
	/**
	 * The microtime this script was started.
	 * 
	 * @var float
	 */
	protected $start_time; 
	
	/**
	 * The microtime this script was ended, ie. when display() is called.
	 * 
	 * @var float
	 */
	protected $end_time;
	
	/**
	 * The maximum time a script is allowed to execute.
	 * 
	 * @var int
	 */
	protected $allowed_time = 0;
	
	/**
	 * Log messages.
	 * 
	 * @param array
	 */
	protected $log = array();
	
	/**
	 * A list of included files and their sizes.
	 * 
	 * @var array
	 */
	protected $files = array();
	
	/**
	 * The total size of the files in bytes.
	 * 
	 * @var int
	 */
	protected $files_total_size = 0;
	
	/**
	 * Creates a new Inject_Profiler.
	 * 
	 * @param  float	microtime(true) when the app was started.
	 */
	function __construct($start_time = 0)
	{
		if($start_time != 0)
		{
			$this->start_time = $start_time;
			
			$this->addMessage('Inject', 'Done loading core', Inject::DEBUG);
		}
		else
		{
			$this->start_time = microtime(true);
		}
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

	/**
	 * 
	 * 
	 * @return 
	 */
	protected function getFileData()
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
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	protected function getMemoryData()
	{
		$this->memory = memory_get_peak_usage();
		$this->memory_limit = ini_get('memory_limit');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function display()
	{
		$this->end_time = microtime(true);
		$this->allowed_time = ini_get('max_execution_time');
		
		$this->getFileData();
		$this->getMemoryData();
		
		$this->render();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	protected function render()
	{
		?>
<style type="text/css">
#IFW-Profiler
{
	position: absolute;
	bottom: 0;
	left: 0;
	background: transparent;
	z-index: 0;
	width: 100%;
	margin: 0;
	padding: 0;
	font-size: 12px;
}
#IFW-Profiler.hidden
{
	height: 30px;
	overflow: hidden;
}
#IFW-Profiler *
{
	color: #ccc;
}
#IFW-Profiler .IFW-Container
{
	position: relative;
	background: #333;
	color: #ccc;
	width: 600px;
	height: 400px;
	margin: auto;
	float: none;
	padding: 10px;
	-moz-border-radius-topleft: 20px;
	-webkit-border-top-left-radius: 20px;
	-moz-border-radius-topright: 20px;
	-webkit-border-top-right-radius: 20px;
}
#IFW-Profiler .IFW-Pane
{
	height: 320px;
	overflow: auto;
}
#IFW-Profiler .IFW-Hidden
{
	display: none;
}
</style>
<script type="text/javascript" charset="utf-8">
//http://www.bigbold.com/snippets/posts/show/2630
function addClassName(objElement, strClass, blnMayAlreadyExist)
{
	if(objElement.className)
	{
		var arrList = objElement.className.split(' ');

		if(blnMayAlreadyExist)
		{
			var strClassUpper = strClass.toUpperCase();

			for(var i = 0; i < arrList.length; i++)
			{
				if(arrList[i].toUpperCase() == strClassUpper)
				{
					arrList.splice(i, 1);
					i--;
				}
			}
		}

		arrList[arrList.length] = strClass;
		objElement.className = arrList.join(' ');
	}
	else
	{  
		objElement.className = strClass;
	}
}

//http://www.bigbold.com/snippets/posts/show/2630
function removeClassName(objElement, strClass)
{
	if(objElement.className)
	{
		var arrList = objElement.className.split(' ');
		var strClassUpper = strClass.toUpperCase();

		for(var i = 0; i < arrList.length; i++)
		{
			if(arrList[i].toUpperCase() == strClassUpper)
			{
				arrList.splice(i, 1);
				i--;
			}
		}
		objElement.className = arrList.join(' ');
	}
}

//http://ejohn.org/projects/flexible-javascript-events/
function addEvent(obj, type, fn)
{
	if(obj.attachEvent)
	{
		obj["e"+type+fn] = fn;
		obj[type+fn] = function()
		{
			obj["e"+type+fn](window.event)
		};
		
		obj.attachEvent("on"+type, obj[type+fn]);
	} 
	else
	{
		obj.addEventListener( type, fn, false );	
	}
}

function activateTab(classname)
{
	addClassName(document.getElementById('IFW-Console'), 'IFW-Hidden', true);
	addClassName(document.getElementById('IFW-Exec'), 'IFW-Hidden', true);
	addClassName(document.getElementById('IFW-Files'), 'IFW-Hidden', true);
	removeClassName(document.getElementById(classname), 'IFW-Hidden', true);
}
function hideIFW()
{
	var p = document.getElementById('IFW-Profiler');
	
	if(p.className.indexOf('hidden') != -1)
	{
		removeClassName(p, 'hidden');
	}
	else
	{
		addClassName(p, 'hidden', true);
	}
}
</script>
<div id="IFW-Profiler">
	<div class="IFW-Container">
		<div>
			<span><a onClick="hideIFW();">Hide</a></span>
		</div>
		<ul>
			<li><a onClick="activateTab('IFW-Console');"><strong>Console</strong></a></li>
			<li><a onClick="activateTab('IFW-Exec');"><strong>Execution info</strong> <span><?php echo number_format(($this->end_time - $this->start_time) * 1000, 4) ?> ms</span></a></li>
			<li><a onClick="activateTab('IFW-Files');"><strong><?php echo count($this->files) ?> Files</strong> <span>included</span></a></li>
		</ul>
	
		<div class="panes">
			<div id="IFW-Console" class="IFW-Pane">
				<table border="0" cellspacing="0" cellpadding="0">
					<?php foreach($this->log as $row): ?>
					<tr>
						<td class="<?php echo $s = Inject_Util::errorConstToStr($row['level']) ?>"><?php echo $s ?></td>
						<td><?php echo number_format($row['time'] * 1000, 4) ?> ms</td>
						<td><?php echo $row['name'] ?></td>
						<td><?php echo $row['message'] ?></td>
					</tr>
					<?php endforeach; ?>
				</table>
			</div>
			
			<div id="IFW-Exec" class="IFW-Pane IFW-Hidden">
				<p>
					<strong>Total Execution time: </strong> <?php echo number_format(($this->end_time - $this->start_time) * 1000, 4) ?> ms
				</p>
				
				<p>
					<strong>Maximum memory used: </strong> <?php echo Inject_Util::humanReadableSize($this->memory) ?>
				</p>
				
				<p>
					<strong>Maximum memory allowed: </strong> <?php echo $this->memory_limit ?>
				</p>
			</div>
			
			<div id="IFW-Files" class="IFW-Pane IFW-Hidden">
				<table border="0" cellspacing="0" cellpadding="0">
					<?php foreach($this->files as $file): ?>
					<tr>
						<td><?php echo $file['file'] ?></td>
						<td><?php echo Inject_Util::humanReadableSize($file['size']) ?></td>
					</tr>
					<?php endforeach; ?>
				</table>
			</div>
		</div>
	</div>
</div><?php
	}
}


/* End of file  */
/* Location: . */