<?php
header('HTTP/1.1 500 Internal Server Error');
echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Inject Framework - HTTP/1.1 Internal Server Error</title>
</head>

<body>
	
	<!-- TODO: i18n -->
	
	<div id="error_container">
		<h3>Inject Framework<br />HTTP/1.1 500 Internal Server Error</h3>
		
		<p class="error">
			<strong>Error:</strong><br />
			<?php echo $type .': ' . htmlspecialchars($message); ?>
		</p>
		
		<p class="location">
			<strong>File: </strong> <?php echo htmlspecialchars($file); ?><br />
			<strong>Line: </strong> <?php echo htmlspecialchars($line); ?>
		</p>
		
		<?php if( ! empty($trace)): ?>
		<h3>Trace</h3>
		<!-- TODO: Make the trace nicer -->
		<div class="trace">
<?php echo str_replace(array("\t", '    '), '&nbsp;&nbsp;&nbsp;&nbsp;', nl2br(htmlspecialchars(print_r($trace, true)))); ?>
		</div>
		<?php endif; ?>
		
		<p class="copy">
			Inject Framework - Copyright &copy; 2009 Martin Wernstahl
		</p>
	</div>
</body>
</html>
