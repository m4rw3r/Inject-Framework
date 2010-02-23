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
	 * If to enable the profiler, set to false to prevent it from displaying.
	 * 
	 * This is useful for eg. JSON or XML requests when additional HTML
	 * might ruin the parsing.
	 * 
	 * @var bool
	 */
	static public $enabled = true;
	
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
	 * The maximum amount of memory used by this run, bytes.
	 * 
	 * @var int
	 */
	protected $memory = 0;
	
	/**
	 * Maximum size of the memory which is allowed for the PHP binary, bytes.
	 * 
	 * @var int
	 */
	protected $allowed_memory = 0;
	
	/**
	 * Log messages.
	 * 
	 * @var array
	 */
	protected $log = array();
	
	/**
	 * Summary of the logs.
	 * 
	 * @var array
	 */
	protected $log_summary;
	
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
	 * The path in which the framework is stored.
	 * 
	 * @var string
	 */
	protected $fw_path;
	
	/**
	 * A list of the application paths.
	 * 
	 * @var array
	 */
	protected $app_paths = array();
	
	/**
	 * A list of the headers sent to the client.
	 * 
	 * @var array
	 */
	protected $headers = array();
	
	/**
	 * Flag telling if the database is loaded.
	 * 
	 * @var bool
	 */
	protected $database_loaded = false;
	
	/**
	 * A list of queries to print and their times.
	 * 
	 * @var array
	 */
	protected $queries = array();
	
	/**
	 * Language translation object.
	 * 
	 * @var Inject_I18n
	 */
	protected $lang;
	
	/**
	 * Creates a new Inject_Profiler.
	 * 
	 * @param  float	microtime(true) when the app was started.
	 */
	function __construct($start_time = 0, $use_inject_event = false)
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
		
		if( ! $use_inject_event)
		{
			// Add shutdown function for the profiler
			register_shutdown_function(array(&$this, 'display'));
		}
		else
		{
			Inject::onEvent('inject.terminate', array(&$this, 'display'));
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
	 * Makes a summary of the different levels of console messages.
	 * 
	 * @return void
	 */
	protected function getConsoleData()
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
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Fetches data about included files.
	 * 
	 * @return void
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
	 * Fetches data about memory usage.
	 * 
	 * @return void
	 */
	protected function getMemoryData()
	{
		$prefixes = array('k' => 1024, 'm' => 1048576, 'g' => 1073741824);
		
		$this->memory = memory_get_peak_usage();
		$ml = ini_get('memory_limit');
		
		preg_match('/(\d*)\s*([TGMK]*)/i', $ml, $m);
		
		$prefix = isset($prefixes[strtolower($m[2])]) ? $prefixes[strtolower($m[2])] : 1;
		
		$this->memory_limit = $m[1] * $prefix;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Fetches data about framework settings.
	 * 
	 * @return void
	 */
	protected function getFrameworkData()
	{
		$this->fw_path = Inject::getFrameworkPath();
		$this->app_paths = Inject::getApplicationPaths();
		
		$r = Inject::getMainRequest();
		
		if( ! empty($r->response))
		{
			$this->headers = array_merge(array('HTTP/1.1' => $r->response->response_code), $r->response->headers);
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Gathers information about the queries which were issued during the run.
	 * 
	 * @return void
	 */
	protected function getQueryData()
	{
		// Assume RapidDataMapper
		
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
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Displays the profiler and gathers data.
	 * 
	 * @return void
	 */
	public function display()
	{
		if( ! self::$enabled)
		{
			return;
		}
		
		$this->end_time = microtime(true);
		$this->allowed_time = ini_get('max_execution_time');
		
		$this->getConsoleData();
		$this->getFileData();
		$this->getMemoryData();
		$this->getFrameworkData();
		$this->getQueryData();
		
		$this->lang = new Inject_I18n('Profiler');
		
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

function activateTab(classname)
{
	// always show the whole profiler
	removeClassName(document.getElementById('IFW-Profiler'), 'IFW-Hidden', true);
	
	// change the arrow to a down arrow
	var a = document.getElementById('IFW-HideBtn');
	if(a.nodeType == 3)
	{
		a.data = '\u25BC';
	}
	if(a.nodeType == 1)
	{
		a.firstChild.data = '\u25BC';
	}
	
	// change tab
	addClassName(document.getElementById('IFW-Console'), 'IFW-Hidden', true);
	addClassName(document.getElementById('IFW-Exec'), 'IFW-Hidden', true);
	addClassName(document.getElementById('IFW-Db'), 'IFW-Hidden', true);
	addClassName(document.getElementById('IFW-Files'), 'IFW-Hidden', true);
	removeClassName(document.getElementById(classname), 'IFW-Hidden', true);
	
	// change tab selection
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
	
	if(p.className.indexOf('IFW-Hidden') != -1)
	{
		removeClassName(p, 'IFW-Hidden');
		txt = '\u25BC';
	}
	else
	{
		addClassName(p, 'IFW-Hidden', true);
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
	position: fixed !important;
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
	text-align: left;
	text-decoration: none;
	font-weight: normal;
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
#IFW-Profiler.IFW-Hidden
{
	height: 90px;
	overflow: hidden;
}
#IFW-Profiler .IFW-Container
{
	position: relative;
	background: #555;
	color: #ccc;
	width: 800px;
	height: 500px;
	margin: auto;
	float: none;
	padding: 10px;
	border: 1px solid #111;
	border-bottom: 0;
	-moz-border-radius-topleft: 10px;
	-webkit-border-top-left-radius: 10px;
	-moz-border-radius-topright: 10px;
	-webkit-border-top-right-radius: 10px;
}
#IFW-Profiler ul
{
	height: 60px;
}
#IFW-Profiler ul li
{
	display: block;
	width: 176px;
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
	margin: 0;
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
	color: #aa3;
}
#IFW-Db-Tab:hover, #IFW-Db-Tab:hover *
{
	background: #aa3;
}
#IFW-Files-Tab.IFW-Selected, #IFW-Files-Tab.IFW-Selected *
{
	color: #ccc;
}
#IFW-Files-Tab:hover, #IFW-Files-Tab:hover *
{
	background: #ccc;
}
#IFW-Profiler .IFW-Panes
{
	position: relative;
	clear: both;
	top: 0;
}
#IFW-Profiler .IFW-Pane
{
	height: 399px;
	background: #000;
	overflow:auto;
	padding: 15px;
	border: 0;
}
#IFW-Profiler .IFW-LeftCorner
{
	-moz-border-radius-topleft: 5px;
	-webkit-border-top-left-radius: 5px;
}
#IFW-Profiler .IFW-RightCorner
{
	-moz-border-radius-topright: 5px;
	-webkit-border-top-right-radius: 5px;
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
	font-weight: bold;
}
#IFW-Profiler h3
{
	width: 100%;
	padding: 10px 0 0;
	font-size: 16px;
	font-weight: bold;
}
#IFW-Profiler strong
{
	font-weight: bold;
	margin-top: 10px;
}
#IFW-Profiler p
{
	padding: 5px 0;
	border: 0;
}
#IFW-Profiler .IFW-Row
{
	border-bottom: 1px solid #333;
	padding: 5px 0;
	width: 755px;
	clear: both;
}
#IFW-Profiler .IFW-THead
{
	border: 0;
	padding: 5px 0 10px;
	width: 755px;
	clear: both;
}
#IFW-Profiler .IFW-Cell
{
	display: block;
	float: left;
	padding: 2px 0 2px 20px;
	overflow: hidden;
	word-wrap: break-word;
}
#IFW-Profiler .IFW-Row :first-child, #IFW-Profiler .IFW-THead :first-child
{
	padding-left: 0;
}
#IFW-Profiler .IFW-ERROR, #IFW-Profiler .IFW-ERROR strong
{
	color: #f00;
}
#IFW-Profiler .IFW-WARNING, #IFW-Profiler .IFW-WARNING strong
{
	color: #ff9;
}
#IFW-Profiler .IFW-NOTICE, #IFW-Profiler .IFW-NOTICE strong
{
	color: #fff;
}
#IFW-Profiler .IFW-DEBUG, #IFW-Profiler .IFW-DEBUG strong
{
	color: #99c;
}
.IFW-Clear
{
	clear: both;
}
</style>
<div id="IFW-Profiler" class="IFW-Hidden">
	<div class="IFW-Container">
		<a id="IFW-HideBtn" onClick="hideIFW();">&#x25B2;</a>
		<ul>
			<li id="IFW-Console-Tab" onClick="activateTab('IFW-Console');" class="IFW-Selected"><strong><?php echo $this->lang->console ?></strong><?php
$str = '';
foreach(array(Inject::DEBUG => $this->lang->dbg_msgs,
              Inject::NOTICE => $this->lang->notices,
              Inject::WARNING => $this->lang->warnings,
              Inject::ERROR => $this->lang->errors) as $c => $s)
{
	if( ! empty($this->log_summary[$c]))
	{
		$str = '<span class="IFW-'.strtoupper(Inject_Util::errorConstToStr($c)).'">'.$this->log_summary[$c].' '.$s.'</span>';
	}
}

echo $str;
			?></li>
			<li id="IFW-Exec-Tab" onClick="activateTab('IFW-Exec');"><strong><?php echo number_format(($this->end_time - $this->start_time) * 1000, 4) ?> ms</strong> <span><?php echo $this->lang->exec_info ?></span></li>
			<li id="IFW-Db-Tab" onClick="activateTab('IFW-Db');"><strong><?php echo count($this->queries) ?> <?php echo $this->lang->queries ?></strong> <span><?php echo $this->lang->database ?></span></li>
			<li id="IFW-Files-Tab" onClick="activateTab('IFW-Files');"><strong><?php echo count($this->files) ?> <?php echo $this->lang->files ?></strong> <span><?php echo $this->lang->included ?></span></li>
		</ul>
	
		<div class="IFW-panes">
			<div id="IFW-Console" class="IFW-Pane IFW-RightCorner">
				<div class="IFW-THead">
					<div class="IFW-Cell<? echo empty($this->log_summary[Inject::ERROR]) ? '' : ' IFW-ERROR' ?>" style="width: 120px"><strong><?php echo $this->lang->errors ?>: </strong> <?php echo $this->log_summary[Inject::ERROR] ?></div>
					<div class="IFW-Cell<? echo empty($this->log_summary[Inject::WARNING]) ? '' : ' IFW-WARNING' ?>" style="width: 120px"><strong><?php echo $this->lang->warnings ?>: </strong> <?php echo $this->log_summary[Inject::WARNING] ?></div>
					<div class="IFW-Cell<? echo empty($this->log_summary[Inject::NOTICE]) ? '' : ' IFW-NOTICE' ?>" style="width: 120px"><strong><?php echo $this->lang->notices ?>: </strong> <?php echo $this->log_summary[Inject::NOTICE] ?></div>
					<div class="IFW-Cell<? echo empty($this->log_summary[Inject::DEBUG]) ? '' : ' IFW-DEBUG' ?>" style="width: 120px"><strong><?php echo $this->lang->dbg_msgs ?>: </strong> <?php echo $this->log_summary[Inject::DEBUG] ?></div>
					<span class="IFW-Clear"></span>
				</div>
				
				<h3><?php echo $this->lang->log ?></h3>
				
				<div class="IFW-THead">
					<div class="IFW-Cell" style="width: 60px"><?php echo $this->lang->level ?></div>
					<div class="IFW-Cell" style="width: 70px"><?php echo $this->lang->time ?></div>
					<div class="IFW-Cell" style="width: 70px"><?php echo $this->lang->source ?></div>
					<div class="IFW-Cell" style="width: 485px"><?php echo $this->lang->message ?></div>
					<span class="IFW-Clear"></span>
				</div>
				
				<?php foreach($this->log as $row): ?>
				<div class="IFW-Row">
					<div class="IFW-Cell IFW-<?php echo $s = Inject_Util::errorConstToStr($row['level']) ?>" style="width: 60px"><?php echo $s ?></div>
					<div class="IFW-Cell" style="width: 70px"><?php echo number_format($row['time'] * 1000, 4) ?> ms</div>
					<div class="IFW-Cell" style="width: 70px"><?php echo $row['name'] ?></div>
					<div class="IFW-Cell" style="width: 485px"><?php echo htmlspecialchars($row['message'], ENT_COMPAT, 'UTF-8') ?></div>
					<span class="IFW-Clear"></span>
				</div>
				<?php endforeach; ?>
			</div>
			
			<div id="IFW-Exec" class="IFW-Pane IFW-Hidden IFW-LeftCorner IFW-RightCorner">
				<div class="IFW-THead">
					<div class="IFW-Cell">
						<strong><?php echo $this->lang->tot_exec_time ?>: </strong> <?php echo number_format(($this->end_time - $this->start_time) * 1000, 4) ?> ms
					</div>
					
					<div class="IFW-Cell">
						<strong><?php echo $this->lang->max_exec_time ?>: </strong> <?php echo $this->allowed_time ?> s
					</div>

					<div class="IFW-Cell">
						<strong><?php echo $this->lang->tot_mem_used ?>: </strong> <?php echo Inject_Util::humanReadableSize($this->memory) ?>
					</div>

					<div class="IFW-Cell">
						<strong><?php echo $this->lang->max_mem_used ?>: </strong> <?php echo Inject_Util::humanReadableSize($this->memory_limit) ?>
					</div>
					
					<span class="IFW-Clear"></span>
				</div>
				
				<h3><?php echo $this->lang->headers ?></h3>
				
				<?php foreach($this->headers as $k => $v): ?>
				<div class="IFW-Row">
					<div class="IFW-Cell" style="width: 160px"><?php echo htmlspecialchars($k, ENT_COMPAT, 'UTF-8') ?></div>
					<div class="IFW-Cell" style="width: 565px"><?php echo htmlspecialchars($v, ENT_COMPAT, 'UTF-8') ?></div>
					<span class="IFW-Clear"></span>
				</div>
				<?php endforeach; ?>
				
				<h3><?php echo $this->lang->server_vars ?></h3>
				
				<?php foreach($_SERVER as $k => $v): ?>
				<div class="IFW-Row">
					<div class="IFW-Cell" style="width: 160px"><?php echo htmlspecialchars($k, ENT_COMPAT, 'UTF-8') ?></div>
					<div class="IFW-Cell" style="width: 565px"><?php echo htmlspecialchars($v, ENT_COMPAT, 'UTF-8') ?></div>
					<span class="IFW-Clear"></span>
				</div>
				<?php endforeach; ?>
				
				<h3>GET <?php echo $this->lang->data ?></h3>
				
				<?php if( ! empty($_GET)): ?>
				<?php foreach($_GET as $k => $v): ?>
				<div class="IFW-Row">
					<div class="IFW-Cell" style="width: 160px"><?php echo htmlspecialchars($k, ENT_COMPAT, 'UTF-8') ?></div>
					<div class="IFW-Cell" style="width: 565px"><?php echo htmlspecialchars($v, ENT_COMPAT, 'UTF-8') ?></div>
					<span class="IFW-Clear"></span>
				</div>
				<?php endforeach; ?>
				<?php else:?>
				<p>
					<?php echo $this->lang->no_get ?>
				</p>
				<?php endif; ?>
				
				<h3>POST <?php echo $this->lang->data ?></h3>
				
				<?php if( ! empty($_POST)): ?>
				<?php foreach($_POST as $k => $v): ?>
				<div class="IFW-Row">
					<div class="IFW-Cell" style="width: 160px"><?php echo htmlspecialchars($k, ENT_COMPAT, 'UTF-8') ?></div>
					<div class="IFW-Cell" style="width: 565px"><?php echo htmlspecialchars($v, ENT_COMPAT, 'UTF-8') ?></div>
					<span class="IFW-Clear"></span>
				</div>
				<?php endforeach; ?>
				<?php else:?>
				<p>
					<?php echo $this->lang->no_post ?>
				</p>
				<?php endif; ?>
				
				<h3><?php echo $this->lang->env_vars ?></h3>
				
				<?php if( ! empty($_ENV)): ?>
				<?php foreach($_ENV as $k => $v): ?>
				<div class="IFW-Row">
					<div class="IFW-Cell" style="width: 160px"><?php echo htmlspecialchars($k, ENT_COMPAT, 'UTF-8') ?></div>
					<div class="IFW-Cell" style="width: 565px"><?php echo htmlspecialchars($v, ENT_COMPAT, 'UTF-8') ?></div>
					<span class="IFW-Clear"></span>
				</div>
				<?php endforeach; ?>
				<?php else:?>
				<p>
					<?php echo $this->lang->no_env ?>
				</p>
				<?php endif; ?>
				
				<h3><?php echo $this->lang->cookies ?></h3>
				
				<?php if( ! empty($_COOKIE)): ?>
				<?php foreach($_COOKIE as $k => $v): ?>
				<div class="IFW-Row">
					<div class="IFW-Cell" style="width: 160px"><?php echo htmlspecialchars($k, ENT_COMPAT, 'UTF-8') ?></div>
					<div class="IFW-Cell" style="width: 565px"><?php echo htmlspecialchars($v, ENT_COMPAT, 'UTF-8') ?></div>
					<span class="IFW-Clear"></span>
				</div>
				<?php endforeach; ?>
				<?php else:?>
				<p>
					<?php echo $this->lang->no_cookies ?>
				</p>
				<?php endif; ?>
				
				<h3><?php echo $this->lang->session ?></h3>
				
				<?php if( ! empty($_SESSION)): ?>
				<?php foreach($_SESSION as $k => $v): ?>
				<div class="IFW-Row">
					<div class="IFW-Cell" style="width: 160px"><?php echo htmlspecialchars($k, ENT_COMPAT, 'UTF-8') ?></div>
					<div class="IFW-Cell" style="width: 565px"><?php echo htmlspecialchars($v, ENT_COMPAT, 'UTF-8') ?></div>
					<span class="IFW-Clear"></span>
				</div>
				<?php endforeach; ?>
				<?php else:?>
				<p>
					<?php echo $this->lang->no_session ?>
				</p>
				<?php endif; ?>
			</div>
			
			<div id="IFW-Db" class="IFW-Pane IFW-Hidden IFW-LeftCorner IFW-RightCorner">
				<?php if($this->database_loaded): ?>
					<div class="IFW-THead">
						<div class="IFW-Cell" style="width: 60px"><?php echo $this->lang->time ?></div>
						<div class="IFW-Cell" style="width: 665px"><?php echo $this->lang->query ?></div>
						<span class="IFW-Clear"></span>
					</div>
					<?php foreach($this->queries as $q): ?>
						<div class="IFW-Row">
							<div class="IFW-Cell" style="width: 60px"><?php echo number_format($q['time'] * 1000, 4) ?> ms</div>
							<div class="IFW-Cell" style="width: 665px"><?php echo htmlspecialchars($q['query'], ENT_COMPAT, 'UTF-8') ?></div>
							<span class="IFW-Clear"></span>
						</div>
					<?php endforeach; ?>
				<?php else: ?>
				<h2>
					<?php echo $this->lang->db_not_loaded ?>
				</h2>
				<?php endif; ?>
			</div>
			
			<div id="IFW-Files" class="IFW-Pane IFW-Hidden IFW-LeftCorner IFW-RightCorner">
				<div class="IFW-THead">
					<div class="IFW-Cell">
						<strong><?php echo $this->lang->fw_path ?>: </strong> <?php echo $this->fw_path ?>
					</div>
					
					<div class="IFW-Cell">
						<strong><?php echo $this->lang->app_paths ?>:</strong>
						
						<ol>
							<?php foreach($this->app_paths as $p): ?>
							<li><?php echo $p ?></li>
							<?php endforeach; ?>
						</ol>
					</div>
					
					<span class="IFW-Clear"></span>
				</div>
				
				<h3><?php echo $this->lang->inc_files ?></h3>
				
				<?php foreach($this->files as $file): ?>
				<div class="IFW-Row">
					<div class="IFW-Cell" style="width: 655px;"><?php echo $file['file'] ?></div>
					<div class="IFW-Cell" style="width: 80px; text-align: right;"><?php echo Inject_Util::humanReadableSize($file['size']) ?></div>
					<span class="IFW-Clear"></span>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div><?php
	}
}


/* End of file  */
/* Location: . */