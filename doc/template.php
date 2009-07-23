<?php
/*
 * Created by Martin Wernståhl on 2009-04-18.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */



/* End of file template.php */
/* Location: ./doc */?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title>ORM Tools - <?php echo $p->title; ?></title>
	<link rel="stylesheet" href="<?php echo $p->base ?>css/master.css" type="text/css" media="screen" title="Main Stylesheet" charset="utf-8" />
	<link rel="stylesheet" href="<?php echo $p->base ?>css/print.css" type="text/css" media="print" charset="utf-8" />
</head>
<body>
	<div id="container">
		<div id="header">
			<img src="<?php echo $p->base ?>css/logo.png" id="logo" alt="ORM Tools - An efficient PHP database abstraction" />
			<ul id="nav">
				<?php echo $p->nav; ?>
				<a href="<?php echo $p->base ?>api">API Reference</a>
			</ul>
		</div>
		<div id="content">
			<div id="spacer"></div>
			<h1><?php echo $p->title; ?></h1>
			<?php echo $p->content; ?>
			<div id="spacer_footer"></div>
		</div>
	</div>
	<div class="clear"></div>
	<div id="footer">
		<div id="inner_footer">
			<p>
				Copyright (c) 2009 Martin Wernst&aring;hl
			</p>
		</div>
	</div>
</body>
</html>