<?php 
	$site_root = $_SERVER['SERVER_NAME'];
	$serverRoot = $_SERVER['DOCUMENT_ROOT'];
	
	spl_autoload_register(function ($class) {
		global $serverRoot;
		require_once $serverRoot . 'classes/' . $class . '.class.php';
	});
	
	session_start();
	require_once ($serverRoot . 'include/connections.php');

	$parser = new Parser;

	$params = $parser->getParameters($_SERVER['REQUEST_URI']);

	$title = 'Futhark Power Generator 1.5.2';
	
	/*
	 * Rudmentary controller. I should get / make someting better
	 */
	switch ($params[0]) {
		/*
		 * $params is everything past the first / in the URL
		 * Use the following format to control where each request goes:
		 * 
		 * 'NameOfTheParam': // www.example.com/NameOfTheParam
			$useHeader = true; // (bool) Include the contents of a <head> for this page
			$useHeadBar = true; // (bool) Render a header div for this page 
			$bePretty = true; // (bool) Include CSS for this page
			$content = $parser->buildOutput('pages/file.php'); // actual page to include
			$useFooter = true; // (bool) Render a footer for this page 
			break; // to signify the end of the page
		 * 
		 * Leave default at the bottom of the list
		 */

		default:
			$useHeader = true;
			$useHeadBar = true;
			$bePretty = true;
			$content = $parser->buildOutput('pages/main.php');
			$useFooter = true;
			break;
		}
		
	$header = '';
	$footer = '';
	
?>
<html>
	<head>

		<?php
			if ($useHeader) {
				$header = $parser->buildOutput('include/header.php');
				echo $header;
			}
		?>

	</head>
	<body>
		<?php
			if ($useHeadBar) {
				$headbar = $parser->buildOutput('include/headbar.php');
				echo $headbar;
			}

			if ($bePretty) {
				echo '<div class="content">';
			}

			echo $content;

			if ($bePretty) {
				echo '</div>';
			}

			if ($useFooter) {
				$footer = $parser->buildOutput('include/footer.php');
				echo $footer;
			}
		?>
	</body>
</html>