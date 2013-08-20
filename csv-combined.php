<?php
include 'functions.inc.php';
require_once('/home/opendatamap/mysql.inc.php');
$params[] = mysql_real_escape_string($_GET['u']);
$params[] = mysql_real_escape_string($_GET['m']);
$q = 'SELECT uri, lat, lon, source, name, icon FROM mappoints WHERE username = \''.$params[0].'\' AND map = \''.$params[1].'\'';
$res = mysql_query($q);
$location = array();
while($row = mysql_fetch_assoc($res))
{
	$location[$row['uri']] = array($row['lat'], $row['lon'], $row['source'], $row['name'], $row['icon']);
}
$q = 'SELECT name, username as corrections, base, source FROM maps WHERE username = \''.$params[0].'\' AND mapid = \''.$params[1].'\'';
$res = mysql_query($q);
if($row = mysql_fetch_assoc($res))
{
	$name = $row['name'];
	$corrections = $row['corrections'];
	$base = $row['base'];
	if($base == '')
		$base = "http://opendatamap.ecs.soton.ac.uk/mymap/".$_GET['u']."/".$_GET['m']."#";
	header('Content-Type: text/csv');
	if(substr($row['source'], 0, 7) == 'http://' || substr($row['source'], 0, 8) == 'https://' || $row['source'] == '')
	{
		list($colnames, $data) = loadCSV($row['source'], '', 'code', 'x1', 'x2', 'latitude', 'longitude', 'x3', 'x4', 'source', $location, true, false);
	}
	else
	{
		$data = null;
	}
}
else
{
	header('HTTP/1.0 404 Not Found');
	die('Map not found.');
}
?>
*COMMENT, <?php echo $name ?> 
*COMMENT, Corrections: <?php echo $corrections ?> 
*COMMENT, License: http://creativecommons.org/licenses/by-sa/3.0/
*COMMENT, Contains Ordnance Survey data. Crown copyright and database right 2011 http://www.ordnancesurvey.co.uk/opendata/licence
<?php
$qcns = array();
foreach(array_keys($colnames) as $colname)
{
	$qcns[] = '"'.str_replace('"', '\"', $colname).'"';
}
echo implode(",", $qcns)."\n";
foreach(array_values($data) as $row)
{
	$qrow = array();
	foreach(array_keys($colnames) as $colname)
	{
		$qrow[] = '"'.str_replace('"', '\"', $row[$colname]).'"';
	}
	echo implode(",", $qrow)."\n";
}
/*
$q = 'SELECT uri, name, icon, lat, lon FROM mappoints WHERE username = \''.$params[0].'\' AND map = \''.$params[1].'\' order by `name`';
$res = mysql_query($q);
while($row = mysql_fetch_assoc($res))
{
	echo $base.$row['uri'];
	echo ",";
	echo $row['name'];
	echo ",";
	echo $row['icon'];
	echo ",";
	echo $row['lat'];
	echo ",";
	echo $row['lon'];
	echo "\n";
}
*/
?>
