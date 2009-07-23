<?php
header('HTTP/1.1 500 Internal Server Error');
echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Inject Framework - HTTP/1.1 Internal Server Error</title>
<style type="text/css" media="screen">

body
{
	background: #111;
	color: #ccc;
}

#error_container
{
	background: #333;
	color: #ccc;
	font-family: sans-serif;
	border: 1px solid #555;
	width: 45em;
	margin: 2em auto;
	padding: 2em;
	text-align: left;
	-moz-border-radius-topleft:			2em;
	-webkit-border-top-left-radius:		2em;
    -moz-border-radius-topright:		2em;
	-webkit-border-top-right-radius:	2em;
    -moz-border-radius-bottomleft:		2em;
	-webkit-border-bottom-left-radius:	2em;
    -moz-border-radius-bottomright:		2em;
	-webkit-border-bottom-right-radius:	2em;
}

h3
{
	font-variant: small-caps;
	font-size: 1.5em;
	border-bottom: 1px solid #aaa;
	margin: 0;
	font-family: serif;
}

.error
{
	background: #211;
	padding: 1em;
	border: 1px solid #555;
	margin: 1em 2em;
	-moz-border-radius-topleft:			1em;
	-webkit-border-top-left-radius:		1em;
    -moz-border-radius-topright:		1em;
	-webkit-border-top-right-radius:	1em;
    -moz-border-radius-bottomleft:		1em;
	-webkit-border-bottom-left-radius:	1em;
    -moz-border-radius-bottomright:		1em;
	-webkit-border-bottom-right-radius:	1em;
}

.error strong
{
	display: block;
	margin: 0.25em 0em 0.1em;
}

.location
{
	margin: 1em 3em;
}

.trace
{
	border: 1.5px solid #555;
	margin: 1.5em 3em;
	padding: 1em;
	background: #222;
	font: 0.75em Monaco, "DejaVu Sans Mono", "Courier New", Verdana, Sans-serif;
	-moz-border-radius-topleft:			1em;
	-webkit-border-top-left-radius:		1em;
    -moz-border-radius-topright:		1em;
	-webkit-border-top-right-radius:	1em;
    -moz-border-radius-bottomleft:		1em;
	-webkit-border-bottom-left-radius:	1em;
    -moz-border-radius-bottomright:		1em;
	-webkit-border-bottom-right-radius:	1em;
}

.copy
{
	text-align: center;
	font-size: 0.75em;
	margin: 1em;
}
</style>
</head>

<body>
	
	<!-- TODO: i18n -->
	
	<div id="error_container">
		<h3>Inject Framework<br />HTTP/1.1 500 Internal Server Error</h3>
		
		<p class="error">
			<strong>Error:</strong><br />
			<?php echo $type .': ' . htmlentities($message); ?>
		</p>
		
		<p class="location">
			<strong>File: </strong> <?php echo htmlentities($file); ?><br />
			<strong>Line: </strong> <?php echo htmlentities($line); ?>
		</p>
		
		<?php if( ! empty($trace)): ?>
		<h3>Trace</h3>
		<!-- TODO: Make the trace nicer -->
		<div class="trace">
<?php echo str_replace(array("\t", '    '), '&nbsp;&nbsp;&nbsp;&nbsp;', nl2br(htmlentities(print_r($trace, true)))); ?>
		</div>
		<?php endif; ?>
		
		<p class="copy">
			Copyright &copy; 2009 Martin Wernstahl
		</p>
	</div>
</body>
</html>
