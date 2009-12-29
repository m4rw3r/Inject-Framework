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
	
	protected $queries = array();
	
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
	addClassName(document.getElementById('IFW-Db'), 'IFW-Hidden', true);
	addClassName(document.getElementById('IFW-Files'), 'IFW-Hidden', true);
	removeClassName(document.getElementById(classname), 'IFW-Hidden', true);
	removeClassName(document.getElementById('IFW-Console-Tab'), 'IFW-Selected', true);
	removeClassName(document.getElementById('IFW-Exec-Tab'), 'IFW-Selected', true);
	removeClassName(document.getElementById('IFW-Db-Tab'), 'IFW-Selected', true);
	removeClassName(document.getElementById('IFW-Files-Tab'), 'IFW-Selected', true);
	addClassName(document.getElementById(classname + '-Tab'), 'IFW-Selected', true);
}
function hideIFW()
{
	var p = document.getElementById('IFW-Profiler');
	var a = document.getElementById('IFW-HideBtn');
	var txt = '';
	
	if(p.className.indexOf('hidden') != -1)
	{
		removeClassName(p, 'hidden');
		txt = '\u25BC';
	}
	else
	{
		addClassName(p, 'hidden', true);
		txt = '\u25B2';
	}
	
	if(a.nodeType == 3)
	{
		a.data = txt;
	} 
	if(a.nodeType == 1)
	{
		a.firstChild.data = txt;
	} 
	
}
</script>
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
	font: 12px Tahoma, Arial, sans-serif;
}
#IFW-Profiler *
{
	display: block;
	color: #ccc;
	padding: 0;
	margin: 0;
	border: 0;
}
#IFW-HideBtn
{
	display: block;
	width: 100%;
	padding: 0 5px 5px;
	text-align: center;
	font-weight: bolder;
}
#IFW-HideBtn:hover
{
	color: #000;
}
#IFW-Profiler.hidden
{
	height: 30px;
	overflow: hidden;
}
#IFW-Profiler .IFW-Container
{
	position: relative;
	background: #555;
	color: #ccc;
	width: 800px;
	height: 400px;
	margin: auto;
	float: none;
	padding: 10px;
	border: 1px solid #111;
	border-bottom: 0;
	-moz-border-radius-topleft: 20px;
	-webkit-border-top-left-radius: 20px;
	-moz-border-radius-topright: 20px;
	-webkit-border-top-right-radius: 20px;
}
#IFW-Profiler ul li
{
	display: block;
	width: 20%;
	float: left;
	height: 40px;
	font-size: 16px;
	padding: 10px;
	text-align: center;
	-moz-border-radius-topleft: 10px;
	-webkit-border-top-left-radius: 10px;
	-moz-border-radius-topright: 10px;
	-webkit-border-top-right-radius: 10px;
}
#IFW-Profiler ul li:hover, #IFW-Profiler ul li:hover *
{
	color: #000;
}
#IFW-Profiler ul li strong, #IFW-Profiler ul li span
{
	display: block;
	width: 100%;
}
.IFW-Selected, .IFW-Selected *
{
	background: #000;
}
#IFW-Console-Tab.IFW-Selected, #IFW-Console-Tab.IFW-Selected *
{
	color: #3a3;
}
#IFW-Console-Tab:hover, #IFW-Console-Tab:hover *
{
	background: #3a3;
}
#IFW-Exec-Tab.IFW-Selected, #IFW-Exec-Tab.IFW-Selected *
{
	color: #55a;
}
#IFW-Exec-Tab:hover, #IFW-Exec-Tab:hover *
{
	background: #55a;
}
#IFW-Db-Tab.IFW-Selected, #IFW-Db-Tab.IFW-Selected *
{
	color: #a5a;
}
#IFW-Db-Tab:hover, #IFW-Db-Tab:hover *
{
	background: #a5a;
}
#IFW-Files-Tab.IFW-Selected, #IFW-Files-Tab.IFW-Selected *
{
	color: #a33;
}
#IFW-Files-Tab:hover, #IFW-Files-Tab:hover *
{
	background: #a33;
}
#IFW-Profiler .IFW-Panes
{
	clear: both;
	background: #000;
}
#IFW-Profiler .IFW-Pane
{
	height: 310px;
	overflow: auto;
	padding: 15px 5px 5px;
}
#IFW-Profiler .IFW-Hidden
{
	display: none;
}
#IFW-Profiler h2
{
	width: 100%;
	padding: 10px 0;
	font-size: 20px;
	text-align: center;
}
#IFW-Profiler table
{
	font-size: 12px;
	border-collapse: collapse;
	width: 100%;
}
#IFW-Profiler tr
{
	display: table-row;
	border-bottom: 1px solid #333;
	padding: 5px;
}
#IFW-Profiler td
{
	display: block;
	padding: 5px 10px;
}
#IFW-Profiler td.IFW-ERROR
{
	color: #f00;
}
#IFW-Profiler td.IFW-WARNING
{
	color: #ff9;
}
#IFW-Profiler td.IFW-NOTICE
{
	color: #fff;
}
#IFW-Profiler td.IFW-DEBUG
{
	color: #99c;
}
</style>
<div id="IFW-Profiler">
	<div class="IFW-Container">
		<a id="IFW-HideBtn" onClick="hideIFW();">&#x25BC;</a>
		<ul>
			<li id="IFW-Console-Tab" onClick="activateTab('IFW-Console');" class="IFW-Selected"><strong>Console</strong></li>
			<li id="IFW-Exec-Tab" onClick="activateTab('IFW-Exec');"><strong>Execution info</strong> <span><?php echo number_format(($this->end_time - $this->start_time) * 1000, 4) ?> ms</span></li>
			<li id="IFW-Db-Tab" onClick="activateTab('IFW-Db');"><strong>Database</strong> <span><?php echo count($this->queries) ?> queries</span></li>
			<li id="IFW-Files-Tab" onClick="activateTab('IFW-Files');"><strong><?php echo count($this->files) ?> Files</strong> <span>included</span></li>
		</ul>
	
		<div class="IFW-panes">
			<div id="IFW-Console" class="IFW-Pane">
				<table border="0" cellspacing="0" cellpadding="0">
					<?php foreach($this->log as $row): ?>
					<tr>
						<td class="IFW-<?php echo $s = Inject_Util::errorConstToStr($row['level']) ?>"><?php echo $s ?></td>
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
			
			<div id="IFW-Db" class="IFW-Pane IFW-Hidden">
				<?php if($this->database_loaded): ?>
					
				<?php else: ?>
				<h2>
					Database is not loaded
				</h2>
				<?php endif; ?>
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