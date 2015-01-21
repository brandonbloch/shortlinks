<?php require 'functions.php' ?>
<?php the_header() ?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta name="robots" content="none">
	<title><?php the_title() ?></title>
	<link rel="canonical" href="http://o.bloch.ca/">
	<link rel="stylesheet" type="text/css" href="style.css">
	<script type="text/javascript" src="js/functions.js"></script>
	<script type="text/javascript" src="js/viewport-units-buggyfill.js"></script>
	<script type="text/javascript" src="js/bookmarklet.js"></script>
	<script type="text/javascript">
		window.viewportUnitsBuggyfill.init();
	</script>
</head>
<body onload="clearButton()">
	<h1><a href="./"><?php the_title() ?></a></h1>
	<?php the_form() ?>
</body>
</html>