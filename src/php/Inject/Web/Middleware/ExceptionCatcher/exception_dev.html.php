<?php
// TODO: Move file?
echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Inject Framework - HTTP/1.1 500 Internal Server Error</title>
	
	<style type="text/css">
		.trace {
			background: #ccc;
			padding: 1em;
			-moz-border-radius-topleft: 5px;
			-webkit-border-top-left-radius: 5px;
			-moz-border-radius-topright: 5px;
			-webkit-border-top-right-radius: 5px;
			-moz-border-radius-bottomleft: 5px;
			-webkit-border-bottom-left-radius: 5px;
			-moz-border-radius-bottomright: 5px;
			-webkit-border-bottom-right-radius: 5px;
		}
		.trace h3 {
			float: left;
		}
		h3.trace_help {
			float: right;
			color: #777;
			font-weight: normal;
			font-size: 1em;
		}
		.trace .trace_header {
			color: #555;
			padding-bottom: 0.5em;
		}
		.trace .trace_header strong {
			color: #333;
			padding-left: 1em;
		}
		.trace div table {
			color: #c7bcdd;
			font: 9pt "Andale Mono", "Courier New", Courier, Lucidatypewriter, Fixed, monospace;
			background: #000;
			width: 100%;
			-moz-border-radius-topleft: 5px;
			-webkit-border-top-left-radius: 5px;
			-moz-border-radius-topright: 5px;
			-webkit-border-top-right-radius: 5px;
			-moz-border-radius-bottomleft: 5px;
			-webkit-border-bottom-left-radius: 5px;
			-moz-border-radius-bottomright: 5px;
			-webkit-border-bottom-right-radius: 5px;
		}
		.trace div table .line_no {
			background: #252225;
			width: 2.5em;
			padding-left: 5px;
		}
		.trace div table .current {
			background: #252225;
		}
		.trace div table td:first-child{
			-moz-border-radius-topleft: 5px;
			-webkit-border-top-left-radius: 5px;
		}
		.trace div table tr:last-child td{
			-moz-border-radius-bottomleft: 5px;
			-webkit-border-bottom-left-radius: 5px;
		}
		.trace .trace_header strong span.parameter {
			color: #555;
			font-weight: normal;
		}
		.trace div div.code {
			display: none;
			padding-bottom: 1em;
		}
		.trace div:hover .code {
			display: block;
		}
		.clear {
			clear: both;
		}
	</style>
	
</head>

<body>
	
	<!-- TODO: i18n -->
	
	<div id="error_container">
		<h1>Inject Framework<br />HTTP/1.1 500 Internal Server Error</h1>
		<h3>Error message:</h3>
		<p>
			<?php echo $type .': ' . htmlspecialchars($message); ?>
		</p>
		
		<p class="location">
			<strong>File: </strong> <?php echo htmlspecialchars($file); ?><br />
			<strong>Line: </strong> <?php echo htmlspecialchars($line); ?>
		</p>
		
		<?php if( ! empty($trace)): ?>
		<div class="trace">
			<h3>Trace</h3>
			
			<h3 class="trace_help">Hover over a line to see the source code.</h3>
			<div class="clear"></div>
<?php
foreach($trace as $data)
{
	?><div>
		<div class="trace_header"><strong><?php echo htmlentities(empty($data['class']) ? $data['function'] : $data['class'].$data['type'].$data['function']); ?>(<span class="parameter"><?php
			
		$a = array();
		empty($data['args']) && $data['args'] = array();
		
		foreach($data['args'] as $d)
		{
			$a[] = is_object($d) ? get_class($d) : (is_array($d) ? 'Array('.count($d).')' : gettype($d).'('.$d.')');
		}
		
		echo implode(', ', $a);
		
		?></span>)</strong><?php 
		
	if(isset($data['file'])): ?> &mdash; <?php
	print htmlentities($data['file'].':').$data['line']; 
	
	?></div>
		<div class="code"><?php echo $this->extractCode($data['file'], $data['line']); ?></div>
	<?php else: ?>
		</div>
	<?php endif; ?>
	</div>
<?php
}
?>
		</div>
		<?php endif; ?>
		</div>
		<p class="copy">
			Inject Framework - Copyright &copy; 2011 Martin Wernstahl
		</p>
	</div>
</body>
</html>
