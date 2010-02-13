<?php
header('HTTP/1.1 500 Internal Server Error'); ?>
Inject Framework - Internal Server Error

Error:

<?php echo $type .": \n" . $message; ?>


File: <?php echo $file; ?>

Line: <?php echo $line; ?>


<?php if( ! empty($trace)): ?>
Trace:
<?php echo print_r($trace, true); ?>
<?php endif; ?>

Inject Framework - Copyright (c) 2009 Martin Wernstahl