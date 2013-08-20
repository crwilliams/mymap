<?php

error_reporting(E_ALL);

$filename = 'cache/'.md5(implode('&', $_GET));

if(!file_exists($filename))
{
	$params = $_GET;
	$params['TOKEN'] = 'YOUR-API-TOKEN';
	$params['FORMAT'] = urlencode('image/png');
	$params['LAYERS'] = 'osopendata';
	$params['CACHE'] = 'true';
	
	foreach($params as $key => $value)
	{
		$qs[] = $key.'='.$value;
	}
	$wmsurl = 'http://openstream.edina.ac.uk/openstream/wms?'.implode('&', $qs);
	file_put_contents($filename, file_get_contents($wmsurl));
}
header('Content-type: image/png');
header('Cache-Control: max-age=604800');
error_reporting(0);
fpassthru(fopen($filename, 'r'));
?>
