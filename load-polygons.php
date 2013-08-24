<?php
require_once('/home/opendatamap/mysql.inc.php');
$params[] = mysql_real_escape_string($_GET['u']);
$params[] = mysql_real_escape_string($_GET['m']);
$q = 'SELECT uri, wkt FROM mappolygons WHERE username = \''.$params[0].'\' AND map = \''.$params[1].'\' order by `name`';
$res = mysql_query($q);
$d = array();
while($row = mysql_fetch_assoc($res))
{
	$d[] = $row;
}
echo json_encode($d);
?>
