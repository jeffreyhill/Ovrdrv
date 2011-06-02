<?php defined('OVRDRV') or die('Access denied'); ?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Clickfil Referral</title>
<body leftmargin="10" marginheight="10" marginwidth="10" topmargin="10">
	<div align="center" style="width:640px;">
		<div>
			<img src="<?= BASEURL ?>/img/logo.jpg" />
		</div>
		<div>
			Inquiry from: <?= self::$data['email'] ?><br />
			Wants newsletter: <?= self::$data['list'] == true ? 'Yes' : 'No' ?>
		</div>
	</div>
</body>
</html>