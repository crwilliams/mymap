<?
session_start();
require_once('/home/opendatamap/mysql.inc.php');
$items = explode('||', $HTTP_RAW_POST_DATA);
$i = 0;

if(!isset($_SESSION['username']))
{
	header("HTTP/1.0 403 Forbidden");
	echo "Failed to save as you are not logged in.";
	die();
}
else if($_SESSION['username'] != $_GET['username'])
{
	header("HTTP/1.0 403 Forbidden");
	echo "Failed to save as this map belongs to ".$_GET['username']." whilst you are logged in as ".$_SESSION['username'].".";
	die();
}

$username = mysql_real_escape_string($_SESSION['username']);
$map = mysql_real_escape_string($_GET['map']);

foreach($items as $item)
{
	if(trim($item) == "")
		continue;
	$d = explode('|', $item);
	$d[0] = mysql_real_escape_string($d[0]);
	if($d[1] == 'WKT')
	{
		$d[2] = mysql_real_escape_string($d[2]);
		$q = 'INSERT INTO mappolygons (map, username, uri, wkt, source) VALUES (\''.$map.'\', \''.$username.'\', \''.$d[0].'\', \''.$d[2].'\', \'OS\') ON DUPLICATE KEY UPDATE wkt = \''.$d[2].'\', source = \'OS\';';
	}
	else
	{
		$d[1] = (float)$d[1];
		$d[2] = (float)$d[2];
		$d[3] = mysql_real_escape_string($d[3]);
		$d[4] = mysql_real_escape_string($d[4]);
		$q = 'INSERT INTO mappoints (map, username, uri, lat, lon, source, name, icon) VALUES (\''.$map.'\', \''.$username.'\', \''.$d[0].'\', '.$d[1].', '.$d[2].', \'OS\', \''.$d[3].'\', \''.$d[4].'\') ON DUPLICATE KEY UPDATE lat = '.$d[1].', lon = '.$d[2].', source = \'OS\', name = \''.$d[3].'\', icon = \''.$d[4].'\';';
	}
	mysql_query($q) or die(mysql_error());
	$i++;
}
echo $i . ' locations saved';
?>
