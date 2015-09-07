<?php
	global $title;
	global $site_root;
?>
<title><?php echo $title ?></title>
<link rel="stylesheet" href="<?php echo $site_root ?>/styles/basic.css" type="text/css">
<link rel="stylesheet" href="<?php echo $site_root ?>/styles/loader.css" type="text/css">
<link rel="stylesheet" href="<?php echo $site_root ?>/styles/sharebutton.css" type="text/css">
<script src="js/ShareButton.js"></script>
<script src="js/analytics.js"></script>
<script>
	var gaCookie = '<?php echo $gaCookie; ?>';
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	ga('create', gaTag, 'auto');
	ga('require', 'linkid', {
		'cookieName': gaCookie,
		'duration': 5,
		'levels': 5});
	ga('send', 'pageview');
</script>