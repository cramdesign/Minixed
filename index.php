<?php

// MINIXED is a minimal but nice-looking PHP directory indexer.
// More at https://github.com/lorenzos/Minixed



	// Configuration
	$browseDirectories = true; // Navigate into sub-folders
	$title = 'Index of {{path}}';
	$subtitle = '{{files}} objects in this folder, {{size}} total'; // Empty to disable
	$showParent = false; // Display a (parent directory) link
	$showDirectories = true;
	$showDirectoriesFirst = true; // Lists directories first when sorting by name
	$showHiddenFiles = false; // Display files starting with "." too
	$showIcons = true;
	$dateFormat = 'd/m/y';
	$sizeDecimals = 1;
	$robots = 'noindex, nofollow';
	$openIndex = $browseDirectories && true; // Open index files present in the current directory if browseDirectories is enabled



	// Who am I?
	$_self = basename($_SERVER['PHP_SELF']);
	$_path = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
	$_total = 0;
	$_total_size = 0;



	// Directory browsing
	$_browse = null;
	if ($browseDirectories) {
		$_GET['b'] = trim(str_replace('\\', '/', @$_GET['b']), '/ ');
		$_GET['b'] = str_replace(array('/..', '../'), '', @$_GET['b']); // Avoid going up into filesystem
		if (!empty($_GET['b']) && $_GET['b'] != '..' && is_dir($_GET['b'])) $_browse = $_GET['b'];
	}



	// Index open
	if (!empty($_browse) && $openIndex) {
		$_index = null;
		if (file_exists($_browse . "/index.htm")) $_index = "/index.htm";
		if (file_exists($_browse . "/index.html")) $_index = "/index.html";
		if (file_exists($_browse . "/index.php")) $_index = "/index.php";
		if (!empty($_index)) {
			header('Location: ' . $_browse . $_index);
			exit();
		}
	}



	// I'm not sure this function is really needed...
	function ls($path, $show_folders = false, $show_hidden = false) {
		global $_self, $_total, $_total_size;
		$ls = array();
		$ls_d = array();
		if (($dh = @opendir($path)) === false) return $ls;
		if (substr($path, -1) != '/') $path .= '/';
		while (($file = readdir($dh)) !== false) {
			if ($file == $_self) continue;
			if ($file == '.' || $file == '..') continue;
			if (!$show_hidden) if (substr($file, 0, 1) == '.') continue;
			$isdir = is_dir($path . $file);
			if (!$show_folders && $isdir) continue;
			$item = array('name' => $file, 'isdir' => $isdir, 'size' => $isdir ? 0 : filesize($path . $file), 'time' => filemtime($path . $file));
			if ($isdir) $ls_d[] = $item; else $ls[] = $item;
			$_total++;
			$_total_size += $item['size'];
		}
		return array_merge($ls_d, $ls);
	}



	// Get the list of files
	$items = ls('.' . (empty($_browse) ? '' : '/' . $_browse), $showDirectories, $showHiddenFiles);



	// Sort it
	function sortByName($a, $b) { global $showDirectoriesFirst; return ($a['isdir'] == $b['isdir'] || !$showDirectoriesFirst ? strtolower($a['name']) > strtolower($b['name']) : $a['isdir'] < $b['isdir']); }
	function sortBySize($a, $b) { return ($a['isdir'] == $b['isdir'] ? $a['size'] > $b['size'] : $a['isdir'] < $b['isdir']); }
	function sortByTime($a, $b) { return ($a['time'] > $b['time']); }
	switch (@$_GET['s']) {
		case 'size': $_sort = 'size'; usort($items, 'sortBySize'); break;
		case 'time': $_sort = 'time'; usort($items, 'sortByTime'); break;
		default    : $_sort = 'name'; usort($items, 'sortByName'); break;
	}


	// Reverse?
	$_sort_reverse = (@$_GET['r'] == '1');
	if ($_sort_reverse) $items = array_reverse($items);


	// Add parent
	if ($showParent && $_path != '/' && empty($_browse)) array_unshift($items, array(
		'name' => '..',
		'isparent' => true,
		'isdir' => true,
		'size' => 0,
		'time' => 0
	));



	// Add parent in case of browsing a sub-folder
	if (!empty($_browse)) array_unshift($items, array(
		'name' => '..',
		'isparent' => false,
		'isdir' => true,
		'size' => 0,
		'time' => 0
	));



	// 37.6 MB is better than 39487001
	function humanizeFilesize($val, $round = 0) {
		$unit = array('','K','M','G','T','P','E','Z','Y');
		do { $val /= 1024; array_shift($unit); } while ($val >= 1000);
		return sprintf('%.'.intval($round).'f', $val) . ' ' . array_shift($unit) . 'B';
	}



	// Titles parser
	function getTitle($title) {
		global $_path, $_browse, $_total, $_total_size, $sizeDecimals;
		$path = $_path;
		if (!empty($_browse)) $path = $path . ($path != '/' ? '/' : '') . $_browse;
		return str_replace(array('{{path}}', '{{files}}', '{{size}}'), array($path, $_total, humanizeFilesize($_total_size, $sizeDecimals)), $title);
	}



	// Link builder
	function buildLink($changes) {
		global $_self;
		$params = $_GET;
		foreach ($changes as $k => $v) if (is_null($v)) unset($params[$k]); else $params[$k] = $v;
		foreach ($params as $k => $v) $params[$k] = urlencode($k) . '=' . urlencode($v);
		return empty($params) ? $_self : $_self . '?' . implode($params, '&');
	}

?>
<!DOCTYPE HTML>
<html>
<head>

	<meta name="robots" content="<?php echo htmlentities($robots) ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title><?php echo htmlentities(getTitle($title)) ?></title>

	<link href="https://fonts.googleapis.com/css?family=Cutive+Mono&display=swap" rel="stylesheet">

	<style type="text/css">

		* {
			margin: 0;
			padding: 0;
			border: none;
			font-weight: normal;
		}

		body {
			font-family: 'Cutive Mono', monospace;
			font-size: 14px;
			color: #111;
		}

		#wrapper {
			width: 88%;
			max-width: 888px;
			margin: 0 auto;
		}
		
		h1, h2, h3, h4 {
			font-weight: 500;
			margin: 0.5em 0;
		}

		h1 {
			font-size: 1.5em;
		}

		h2 {
			font-size: 1.25em;
			color: #999999;
		}

		a {
			text-decoration: none;
			color: #26a;
		}

		a:hover {
			text-decoration: underline;
		}



		table {
			width: 100%;
			text-align: left;
			border-collapse: collapse;
		}
		
		th, td {
			padding: 0.75em;
			color: #999;
			border-bottom: 1px solid #eee;
		}
		
		th {
			font-weight: normal;
		}

		tbody tr:hover {
			background-color: #fafafa;
		}

		th a {
			color: #111;
		}



		.asc, .desc {
			position: relative;
		}

		.asc:before, .desc:before {
			display: block;
			position: absolute;
			right: -1em;
			content: "\025BE";
		}

		.desc:before {
			content: "\025B4";
		}

		.directory, .file, .levelup {
			padding-left: 1.5em;
			background-position: left center;
			background-repeat: no-repeat;
		}

		.levelup {
			background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><path fill="#333" d="m73 40-20-23-20 23h14v26h-13l-9 12h34v-38z"/></svg>');
		}

		.directory {
			background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><path fill="#333" d="m42 32v-9h-26v50h64v-41z"/></svg>');
		}

		.file {
			background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><path fill="#333" d="m52 39h21v39h-50v-60h29zm5-5v-16l16 16z"/></svg>');
		}


	</style>

</head>
<body>

	<div id="wrapper">

		<h1><?php echo htmlentities(getTitle($title)) ?></h1>
		<h2><?php echo htmlentities(getTitle($subtitle)) ?></h2>

		<table class="dir">

			<thead>
				<tr>
					<th><a href="<?php echo buildLink(array('s' =>  null , 'r' => (!$_sort_reverse && $_sort == 'name') ? '1' : null)) ?>" class="name <?php if ($_sort == 'name') echo $_sort_reverse ? 'desc' : 'asc' ?>">Name</a></th>
					<th><a href="<?php echo buildLink(array('s' => 'time', 'r' => (!$_sort_reverse && $_sort == 'time') ? '1' : null)) ?>" class="date <?php if ($_sort == 'time') echo $_sort_reverse ? 'desc' : 'asc' ?>">Date</a></th>
					<th><a href="<?php echo buildLink(array('s' => 'size', 'r' => (!$_sort_reverse && $_sort == 'size') ? '1' : null)) ?>" class="size <?php if ($_sort == 'size') echo $_sort_reverse ? 'desc' : 'asc' ?>">Size</a></th>
				</tr>
			</thead>

			<tbody>
			<?php foreach ($items as $item): ?>

			<tr class="item">

				<?php
					if ($item['isdir'] && $browseDirectories && !@$item['isparent']) {
						if ($item['name'] == '..') {
							$itemURL = buildLink(array('b' => substr($_browse, 0, strrpos($_browse, '/'))));
							$class = "levelup";
						} else {
							$itemURL = buildLink(array('b' => (empty($_browse) ? '' : (string)$_browse . '/') . $item['name']));
							$class = "directory";
						}
					} else {
						$itemURL = (empty($_browse) ? '' : (string)$_browse . '/') . $item['name'];
						$target  = "";
						$class = "file";
					}
				?>

				<td><a href="<?php echo htmlentities($itemURL) ?>" class="name <?php echo $class ?>" target="<? if( $target ) echo $target ?>"><?php echo htmlentities($item['name']) . ($item['isdir'] ? ' /' : '') ?></a></td>

				<td class="date"><?php echo (@$item['isparent'] || empty($item['time'])) ? '-' : date($dateFormat, $item['time']) ?></td>

				<td class="size"><?php echo $item['isdir'] ? '-' : humanizeFilesize($item['size'], $sizeDecimals) ?></td>

			</tr>

			<?php endforeach; ?>
			</tbody>

		</table>

	</div>

</body>
</html>
