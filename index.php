<?php
include 'functions.inc.php';
require_once('/home/opendatamap/mysql.inc.php');
$username = "";
$editmode = false;
$header = false;
if(isset($_GET['username']))
{
	$username = $_GET['username'];
	outputHeader("Map list for ".$username, "", "GENERIC", true, false);
	$header = true;
	if(isset($_SESSION['username']) && $username == $_SESSION['username'])
	{
		$editmode = true;
	}
}
else
{
	$editmode = true;
}

if($editmode && !$header)
{
	outputHeader("Map list for USERNAME", "", "GENERIC", true, true);
}
if($editmode)
{
	echo '<h3>Your maps</h3>';
	$username = $_SESSION['username'];
}
else
{
	echo '<h3>'.$username.'&apos;s maps</h3>';
}

$params[] = mysql_real_escape_string($username);
$q = 'SELECT maps.mapid, maps.name, COUNT(mappoints.uri) AS points, COUNT(mappolygons.uri) AS polygons FROM maps LEFT JOIN mappoints ON maps.mapid = mappoints.map AND maps.username = mappoints.username LEFT JOIN mappolygons ON maps.mapid = mappolygons.map AND maps.username = mappolygons.username WHERE maps.username = \''.$params[0].'\' GROUP BY maps.mapid, maps.name ORDER BY maps.name';
$res = mysql_query($q);
echo '<table>';
while($row = mysql_fetch_assoc($res))
{
	echo '<tr><td>'.$row['name'].'</td>';
	if($row['points'] + $row['polygons'] > 0)
	{
		echo '<td><a href=\''.$username.'/'.$row['mapid'].'.rdf\'>RDF</a></td>';
		echo '<td><a href=\''.$username.'/'.$row['mapid'].'.kml\'>KML</a></td>';
		if($row['points'] > 0)
		{
			echo '<td><a href=\''.$username.'/'.$row['mapid'].'.points.csv\'>CSV ('.$row['points'].' points)</a></td>';
		}
		else
		{
			echo '<td />';
		}
		if($row['polygons'] > 0)
		{
			echo '<td><a href=\''.$username.'/'.$row['mapid'].'.polygons.csv\'>CSV ('.$row['polygons'].' polygons)</a></td>';
		}
		else
		{
			echo '<td />';
		}
	}
	else
	{
		echo '<td colspan=\'4\' style=\'font-style: italic\'>Empty map</td>';
	}
	if($editmode)
	{
		echo '<td><a href=\''.$username.'/'.$row['mapid'].'/edit\'>Edit</a></td>';
	}
	echo '</tr>';
}
echo '</table>';
if($editmode)
{
?>
	<a href='new'>Add map</a>
<?php
}

outputFooter();
?>

