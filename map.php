<?php
include 'functions.inc.php';
session_start();
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<title>OpenDataMap mymap: Geo Data Set Editor</title>
		<link rel="stylesheet" type="text/css" href="../../css/jquery-ui.css" />
		<link rel="stylesheet" type="text/css" href="../../map.css" />
<?php
$data = getData();

if(is_null($data))
{
?>
	</head>
	<body>
		Map not found.
	</body>
<?php
}
else
{
?>
		<style type="text/css">
<?php
$col['Nature'] = '128e4d';
$col['Industry'] = '265cb2';
$col['Offices'] = '3875d7';
$col['Stores'] = '5ec8bd';
$col['Tourism'] = '66c547';
$col['Restaurants-and-Hotels'] = '8c4eb8';
$col['Transportation'] = '9d7050';
$col['Media'] = 'a8a8a8';
$col['Events'] = 'c03638';
$col['Culture-and-Entertainment'] = 'c259b5';
$col['Health'] = 'f34648';
$col['Sports'] = 'ff8a22';
$col['Education'] = 'ffc11f';
$col['Suggestions'] = '333333';
foreach($col as $name => $colour)
{
?>
li#tab-<?php echo $name ?> {
	background:#<?php echo $colour ?>;
}
#tab-<?php echo $name ?> span {
	color:white;
}
<?php
}
?>
		</style>

		<script src="../../lib/proj4js/proj4js.js"></script>
		<script src="../../lib/OpenLayers-2.11/OpenLayers.js"></script>
		<script src="../../lib/jquery-1.6.2.min.js"></script>
		<script src="../../lib/jquery-ui-1.8.16.min.js"></script>
		<script src="../../map.js"></script>
		<script src="../../lib/OpenLayers-2.11/lib/OpenLayers/Format/WKT.js"></script>
		<script type="text/javascript">
var param_username = "<?= $_REQUEST['u'] ?>";
var param_map = "<?= $_REQUEST['m'] ?>";

function loadFeatures(features) {
    var ll = [];
<?php
foreach($data as $uri => $point)
{
	if($point['lat'] == '' || $point['lon'] == '')
		continue;
	echo "    ll['$uri'] = new OpenLayers.LonLat(".$point['lon'].", ".$point['lat'].");\n";
	echo "    ll['$uri'].transform(wgs84, map.getProjectionObject());\n";
	$opacity = 0.5;
	if($point['source'] == 'OS')
		$opacity = 1.0;
	echo "    p['$uri'] = new OpenLayers.Feature.Vector(\n";
	echo "        new OpenLayers.Geometry.Point(ll['$uri'].lon, ll['$uri'].lat),\n";
	echo "        '$uri',\n";
	echo "        {\n";
	echo "            externalGraphic: icons['$uri'],\n";
	echo "            graphicWidth: 32,\n";
	echo "            graphicHeight: 37,\n";
	echo "            graphicXOffset: -16,\n";
	echo "            graphicYOffset: -37,\n";
	echo "            graphicTitle: label['$uri'],\n";
	echo "            graphicOpacity : $opacity\n";
	echo "    });\n";
	echo "    p['$uri'].fid = '$uri';\n";
	echo "    features.push(p['$uri']);\n";
}
?>
}

function loadInfo() {
<?php
$iconcounts = array();
foreach($data as $uri => $item)
{
	echo "    label['$uri'] = '".$item['label']."';\n";
	echo "    icons['$uri'] = '".$item['icon']."';\n";
	@$iconcounts[$item['icon']]++;
}
foreach($iconcounts as $k => $v)
{
	echo "    iconCounts['$k'] = $v;\n";
}
?>
}
		</script>
	</head>
	<body onload="init()">
		<div id='listheader'>
			<form>
				<ul id='links'>
					<li><input type='radio' name='mode' id='mode-points' onclick='enableDragPoints();'><label for='mode-points'>Drag marker icons</label></li>
					<li><input type='radio' name='mode' id='mode-polygons' onclick='enableDragPolygons();'><label for='mode-polygons'>Drag outline corners</label></li>
					<li><a href='../../<?= $_REQUEST['u'] ?>'>Back to map list <img src='../../icons/map.png' /></a></li>
					<li><a href='../<?= $_REQUEST['m'] ?>.rdf'>View RDF <img src='../../icons/page_white_code.png' /></a></li>
					<li><a href='../<?= $_REQUEST['m'] ?>.kml'>View KML <img src='../../icons/page_white_code.png' /></a></li>
					<li id='save_link' style='display: none'><a href='#' onclick='save();'>Save <img src='../../icons/disk.png' /></a></li>
				</ul>
			</form>
		</div>
		<div id="controls">
			<div id='list'>
				<ul id='points'>
					<li id='_new_'>
						<img class='draggable' src='http://opendatamap.ecs.soton.ac.uk/img/icon/Media/blank.png'
							style='z-index:1000; float:left; margin-right:5px' />New Point<br />
						<span class='small'>Drag to location to add new point.</span>
					</li>
					<li id='_newpolygon_'>
						<img class='draggable' src='http://opendatamap.ecs.soton.ac.uk/img/icon/Media/blank.png'
							style='z-index:1000; float:left; margin-right:5px' />New Polygon<br />
						<span class='small'>Drag to location to add new polygon.</span>
					</li>
<?php
 						pointsList($data)
?>
				</ul>
			</div>
		</div>
		<div id="dialog-modal" style="display:none" title="Add Location">
			<form>
				<table style='margin-left:auto; margin-right:auto;'>
					<tr>
						<td><label for='name'>Title:</label></td>
						<td><input id='name' name='name' onchange='processName()' onkeyup='processName()' /></td>
					</tr>
					<tr>
						<td><label for='uri'>ID:</label></td>
						<td><input id='uri' name='uri' /></td>
					</tr>
					<tr>
						<td><label for='icon'>Icon:</label></td>
						<td>
							<img id='selected-icon' src='' title='Selected icon' style='width:32px; height:37px;'/><br />
							<input id='icon' name='icon' style='display:none'/>
							<div id="icon-classes">
								<ul>
									<li id="tab-Suggestions"><a href="#suggestions"><span>Suggestions</span></a></li>
<?php
									outputIconCategories($col);
?>
								</ul>
								<div id='suggestions'>
<?php
									outputSuggestions($iconcounts);
?>
								</div>
							</div>
						</td>
					</tr>
				</table>
			</form>
		</div>
		<div id="polygondialog-modal" style="display:none" title="Add Location">
			<form>
				<table style='margin-left:auto; margin-right:auto;'>
					<tr>
						<td><label for='polygon-uri'>ID:</label></td>
						<td><input id='polygon-uri' name='polygon-uri' /></td>
					</tr>
				</table>
			</form>
		</div>
		<div id="map" class="smallmap"></div>
	</body>
<?php
}
?>
</html>
<?php
function getData()
{
	$data = null;
	if($_REQUEST['m'] == 'iss-wifi')
	{
		foreach(array('A', 'B', 'C', 'D', 'E', 'F', 'G') as $l)
		{
			$data[$l] = array('label' => 'Access Point '.$l, 'icon' => 'http://data.southampton.ac.uk/map-icons/Offices/wifi.png');
		}
	}
	else
	{
		require_once('/home/opendatamap/mysql.inc.php');
		$params[] = mysql_real_escape_string($_REQUEST['u']);
		$params[] = mysql_real_escape_string($_REQUEST['m']);
		$q = 'SELECT uri, lat, lon, source, name, icon FROM mappoints WHERE username = \''.$params[0].'\' AND map = \''.$params[1].'\'';
		$res = mysql_query($q);
		$location = array();
		while($row = mysql_fetch_assoc($res))
		{
			$location[$row['uri']] = array($row['lat'], $row['lon'], $row['source'], $row['name'], $row['icon']);
		}
		$q = 'SELECT source FROM maps WHERE username = \''.$params[0].'\' AND mapid = \''.$params[1].'\'';
		$res = mysql_query($q);
		if($row = mysql_fetch_assoc($res))
		{
			if(substr($row['source'], 0, 7) == 'http://' || substr($row['source'], 0, 8) == 'https://' || $row['source'] == '')
			{
				list($colnames, $data) = loadCSV(
					$row['source'], '', 'code', 'name', 'icon', 'latitude', 'longitude', 'postcode', 'building', null, $location);
			}
			else
			{
				$data = null;
			}
		}
		else
		{
			$data = null;
		}
	}
	return $data;
}

function pointsList($data)
{
	foreach($data as $uri => $item)
	{
		if(isset($item['lat']) && isset($item['lon']) && $item['lat'] != '' && $item['lon'] != '')
			continue;
		echo "<li id='$uri' onclick=\"focusPoint('$uri');\">";
		echo "<img class='draggable' style='z-index:1000; float:left; margin-right:5px' src='".$item['icon']."' />".$item['label']."<br/>";
		echo "<span class='small' id='loc_$uri'>";
		echo "Location not set.";
		echo "</span></li>\n";
	}

	foreach($data as $uri => $item)
	{
		if(!(isset($item['lat']) && isset($item['lon']) && $item['lat'] != '' && $item['lon'] != ''))
			continue;
		echo "<li id='$uri' onclick=\"focusPoint('$uri');\">";
		echo "<img class='draggable' style='float:left; margin-right:5px' src='".$item['icon']."' />".$item['label']."<br/>";
		echo "<span class='small' id='loc_$uri'>";
		echo round($item['lat'], 6).'/'.round($item['lon'], 6).' ('.$item['source'].')';
		echo "</span></li>\n";
	}
}

function outputIconCategories($col)
{
	ksort($col);
	foreach(array_keys($col) as $cat)
	{
		if($cat == 'Suggestions')
			continue;
		echo "\t\t\t\t\t\t\t\t\t".'<li id="tab-'.$cat.'"><a href="../../icons.php?cat='.$cat.'"><span>'.$cat.'</span></a></li>';
	}
}

function outputSuggestions($iconcounts)
{
	arsort($iconcounts);
	foreach($iconcounts as $file => $count)
	{
		echo "\t\t\t\t\t\t\t\t".'<!-- '.$file.' -->';
		$parts = explode('/', $file);
		$filename = array_pop($parts);
		$filename = substr($filename, 0, -4);
		echo "\t\t\t\t\t\t\t\t<img id='img-$filename' src='$file' alt='$filename icon' title='$filename' onclick='selectIcon(\"$file\")' />";
	}
}
?>
