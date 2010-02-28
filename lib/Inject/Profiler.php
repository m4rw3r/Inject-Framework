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
	 */
	function __construct()
	{
		$this->start_time = empty($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : microtime(true);
		
		$this->addMessage('Inject', 'Done loading core', Inject::DEBUG);
		
		// Add shutdown function for the profiler, in case of errors
		register_shutdown_function(array(&$this, 'display'));
		
		// This will be triggered before the shutdown function, but only if there
		// weren't any fatal errors
		Inject::addFilter('inject.output', array(&$this, 'display'));
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
		
		$this->headers = array_merge(array('HTTP/1.1' => Inject::$response_code), Inject::$headers);
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
	public function display($output = null)
	{
		static $called = false;
		
		if( ! self::$enabled)
		{
			return;
		}
		
		// Have we been called?
		if($called)
		{
			// Yes, only one call per request
			return;
		}
		else
		{
			$called = true;
		}
		
		if( ! is_null($output))
		{
			ob_start();
		}
		
		$this->end_time = microtime(true);
		$this->allowed_time = ini_get('max_execution_time');
		
		$this->lang = new Inject_I18n('Profiler');
		
		$this->getConsoleData();
		$this->getFileData();
		$this->getMemoryData();
		$this->getFrameworkData();
		$this->getQueryData();
		
		$this->render();
		
		if( ! is_null($output))
		{
			if(($p = strripos($output, '</html>')) !== false)
			{
				$output = substr($output, 0, $p).ob_get_contents().substr($output, $p);
			}
			else
			{
				$output .= ob_get_contents();
			}
			
			ob_end_clean();
			
			return $output;
		}
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

function activatePanel(classname)
{
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
	addClassName(document.getElementById('IFW-Console'), 'IFW-Hidden', true);
	addClassName(document.getElementById('IFW-Exec'), 'IFW-Hidden', true);
	addClassName(document.getElementById('IFW-Db'), 'IFW-Hidden', true);
	addClassName(document.getElementById('IFW-Files'), 'IFW-Hidden', true);
	
	removeClassName(document.getElementById('IFW-Console-Tab'), 'IFW-Selected', true);
	removeClassName(document.getElementById('IFW-Exec-Tab'), 'IFW-Selected', true);
	removeClassName(document.getElementById('IFW-Db-Tab'), 'IFW-Selected', true);
	removeClassName(document.getElementById('IFW-Files-Tab'), 'IFW-Selected', true);
}
</script>
<style type="text/css">
#IFW-Profiler
{
	position: fixed !important;
	bottom: 0;
	left: 0;
	height: 30px;
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
#IFW-Profiler .toolbar
{
	position: relative;
	background: #000;
	width: 780px;
	height: 22px;
	margin: auto;
	padding: 8px 10px 0;
	font-weight: bold;
	-moz-border-radius-topleft: 5px;
	-webkit-border-top-left-radius: 5px;
	-moz-border-radius-topright: 5px;
	-webkit-border-top-right-radius: 5px;
}
#IFW-Profiler .toolbar li
{
	display: block;
	float: left;
	color: #fff;
	padding: 0 10px 5px;
}
#IFW-Profiler .toolbar .IFW-Selected
{
	background: #333;
	-moz-border-radius-botleft: 5px;
	-webkit-border-bottom-left-radius: 5px;
	-moz-border-radius-botright: 5px;
	-webkit-border-bottom-right-radius: 5px;
}
#IFW-Profiler .IFW-Panel
{
	position: relative;
	bottom: 446px;
	margin: auto;
	width: 780px;
	padding: 10px;
	height: 400px;
	background: #000;
	overflow: auto;
	-moz-border-radius-topleft: 5px;
	-webkit-border-top-left-radius: 5px;
	-moz-border-radius-topright: 5px;
	-webkit-border-top-right-radius: 5px;
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
#IFW-Profiler .IFW-ERROR, #IFW-Profiler .toolbar .IFW-ERROR
{
	color: #f00;
}
#IFW-Profiler .IFW-WARNING, #IFW-Profiler .toolbar .IFW-WARNING
{
	color: #ff9;
}
#IFW-Profiler .IFW-NOTICE, #IFW-Profiler .toolbar .IFW-NOTICE
{
	color: #99c;
}
#IFW-Profiler .IFW-DEBUG, #IFW-Profiler .toolbar .IFW-DEBUG
{
	color: #fff;
}
.IFW-Clear
{
	clear: both;
	display: block;
}
#IFW-Profiler .IFW-Hidden
{
	display: none;
}
</style>
<div id="IFW-Profiler">
	<div class="toolbar">
		<ul>
			<li id="IFW-No-Tab" onClick="hideIFW();" style="color: #fff">Inject Framework <?php echo Inject::VERSION ?></li>
			
			<?php
$str = '';
foreach(array(Inject::DEBUG => $this->lang->dbg_msgs,
		  Inject::NOTICE => $this->lang->notices,
		  Inject::WARNING => $this->lang->warnings,
		  Inject::ERROR => $this->lang->errors) as $c => $s)
{
if( ! empty($this->log_summary[$c]))
{
	$str = '<li id="IFW-Console-Tab" onClick="activatePanel(\'IFW-Console\');" class="IFW-'.strtoupper(Inject_Util::errorConstToStr($c)).'">'.$this->log_summary[$c].' '.$s.'</li>';
}
}

echo $str;
		?>
		
		<li id="IFW-Exec-Tab" onClick="activatePanel('IFW-Exec');"><?php echo number_format(($this->end_time - $this->start_time) * 1000, 4) ?> ms <span style="display:inline; margin: 0 0 0 10px; color: #fff"><?php echo number_format($this->memory / 1024, 0) ?> kB</span></li>
		
		<li id="IFW-Db-Tab" onClick="activatePanel('IFW-Db');"><?php echo count($this->queries) ?> queries</li>
		
		<li id="IFW-Files-Tab" onClick="activatePanel('IFW-Files');"><?php echo count($this->files) ?> <?php echo $this->lang->files ?></li>
	</div>
	
	<div id="IFW-Console" class="IFW-Panel IFW-Hidden">
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
	
	<div id="IFW-Exec" class="IFW-Panel IFW-Hidden">
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
	
	<div id="IFW-Db" class="IFW-Panel IFW-Hidden">
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
	
	<div id="IFW-Files" class="IFW-Panel IFW-Hidden">
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
</div><?php
	}
}


/* End of file	*/
/* Location: . */