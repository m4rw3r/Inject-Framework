<?php
/*
 * Created by Martin Wernståhl on 2009-12-29.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
abstract class Inject_Profiler
{
	/**
	 * If to enable the profiler, set to false to prevent it from displaying.
	 * 
	 * This is useful for eg. JSON or XML requests when additional HTML
	 * might ruin the parsing.
	 * 
	 * @var bool
	 */
	public static $enabled = true;
	
	/**
	 * The profile parts which will be shown in the profiler.
	 * 
	 * @var array
	 */
	protected static $parts = array();
	
	/**
	 * This class is not meant to be instantiated.
	 */
	private final function __construct(){}
	
	/**
	 * Activates the profiler.
	 * 
	 * @param  array  A list of additional classnames to instantiate as profiler parts
	 * @param  array  A list of instances which will be added as profiler parts
	 */
	public static function start(array $classes = array(), array $instances = array())
	{
		static $run = false;
		
		if($run)
		{
			throw new Exception("The Inject_Profiler::start() method has already been called.");
		}
		
		$classes = array_merge($classes, array(
			'Inject_Profiler_Console',
			// 'Inject_Profiler_Request',
			'Inject_Profiler_Server',
			'Inject_Profiler_RapidDataMapper',
			'Inject_Profiler_Files',
			)
		);
		
		foreach($classes as $cls)
		{
			self::$parts[] = new $cls();
		}
		
		self::$parts = array_merge(self::$parts, $instances);
		
		Inject::log('Inject', 'Done loading core', Inject::DEBUG);
		
		// Add shutdown function for the profiler, in case of errors
		register_shutdown_function(array('Inject_Profiler', 'display'));
		
		// This will be triggered before the shutdown function, but only if there
		// weren't any fatal errors
		Inject::addFilter('inject.output', array('Inject_Profiler', 'display'));
		
		$run = true;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds a part to be rendered by the profiler.
	 * 
	 * @param  string|Inject_ProfilerInterface
	 * @return void
	 */
	public static function addPart($class)
	{
		if(is_object($class))
		{
			self::$parts[] = $class;
		}
		else
		{
			self::$parts[] = new $class();
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Displays the profiler and gathers data.
	 * 
	 * @param  string
	 * @return void
	 */
	public static function display($output = null)
	{
		static $called = false;
		
		if( ! self::$enabled)
		{
			// Not enabled, do not render
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
		
		$end_time = microtime(true);
		
		foreach(self::$parts as $p)
		{
			$p->prepareData($end_time);
		}
		
		self::render();
		
		if( ! is_null($output))
		{
			// We're called by the filter, we have to remove the trailing </html> tag
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
	protected static function render()
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

function IFWshowTab(classname)
{
	e = document.getElementById(classname);
	
	// Check if the class is hidden
	if( ! e.className || e.className.toUpperCase().indexOf('IFW-HIDDEN') != -1)
	{
		// Hidden, change tab
		<?php foreach(self::$parts as $i => $p): ?>
		addClassName(document.getElementById('IFW-part<?php echo $i ?>'), 'IFW-Hidden', true);
		removeClassName(document.getElementById('IFW-part<?php echo $i ?>-Tab'), 'IFW-Selected', true);
		<?php endforeach; ?>
		
		removeClassName(document.getElementById(classname), 'IFW-Hidden', true);
		
		// Change tab selection
		addClassName(document.getElementById(classname + '-Tab'), 'IFW-Selected', true);
	}
	else
	{
		// Not hidden, hide it
		IFWhide();
	}
}
function IFWhide()
{
	<?php foreach(self::$parts as $i => $p): ?>
	addClassName(document.getElementById('IFW-part<?php echo $i ?>'), 'IFW-Hidden', true);
	removeClassName(document.getElementById('IFW-part<?php echo $i ?>-Tab'), 'IFW-Selected', true);
	<?php endforeach; ?>
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
			<li id="IFW-No-Tab" onClick="IFWhide();" style="color: #fff">Inject Framework <?php echo Inject::VERSION ?></li>
			
			<?php foreach(self::$parts as $i => $p): ?>
				<?php echo $p->renderTabContents("IFW-part$i-Tab", 'onClick="IFWshowTab(\'IFW-part'.$i.'\');"'); ?>
			<?php endforeach; ?>
		</ul>
	</div>
	
	<?php foreach(self::$parts as $i => $p): ?>
	<div id="<?php echo "IFW-part$i" ?>" class="IFW-Panel IFW-Hidden">
		<?php echo $p->renderBoxContents(); ?>
	</div>
	<?php endforeach; ?>
</div><?php
	}
}


/* End of file	*/
/* Location: . */