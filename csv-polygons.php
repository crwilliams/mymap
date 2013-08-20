<?php
require_once('/home/opendatamap/mysql.inc.php');
$params[] = mysql_real_escape_string($_GET['u']);
$params[] = mysql_real_escape_string($_GET['m']);
$q = 'SELECT uri, wkt, source FROM mappolygons WHERE username = \''.$params[0].'\' AND map = \''.$params[1].'\'';
$res = mysql_query($q);
$data = array();
$colnames = array('uri', 'wkt', 'source');
while($row = mysql_fetch_assoc($res))
{
	$data[] = $row;
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
foreach($colnames as $colname)
{
	$qcns[] = '"'.str_replace('"', '\"', $colname).'"';
}
echo implode(",", $qcns)."\n";
foreach($data as $row)
{
	$qrow = array();
	foreach($colnames as $colname)
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
