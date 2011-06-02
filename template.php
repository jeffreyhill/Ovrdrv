<?php
defined('OVRDRV') or die('Access denied');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<title>Sign up with Clickfil</title>
<style type="text/css" media="all">
  @import url(style.css);
</style>
<!--[if IE 6]>
	<link rel="stylesheet" type="text/css" href="ie6.css" />
<![endif]-->
<body>
	<?php if(self::$data['message']) : ?>
	<div id="message">
		<**MESSAGE**>
	</div>
	<?php endif; ?>
	<div id="dialog">
		<**CONTENT**>
	</div>
</body>