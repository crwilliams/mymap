<?
function requireLogin()
{
	session_start();
	if(!isset($_SESSION['username']))
	{
		$_SESSION['referer'] = $_SERVER['REQUEST_URI'];
		header('Location: login');
		exit;
	}
}

function internalOnly()
{
	if(substr($_SERVER['REMOTE_ADDR'], 0, 12) == '2001:630:d0:')
		return;
	if(substr($_SERVER['REMOTE_ADDR'], 0, 7) == '152.78.')
		return;
	if($_SERVER['REMOTE_ADDR'] == '188.222.196.170')
		return;
	if(substr($_SERVER['REMOTE_ADDR'], 0, 10) == '128.30.52.')
		return;
	die($_SERVER['REMOTE_ADDR']);
}

function outputHeader($title="", $description="", $keywords=null, $login=false, $requirelogin=false)
{
	if($requirelogin)
	{
		requireLogin();
	}
	else if($login)
	{
		session_start();
	}
	header("Content-type: text/html; charset=utf-8");
	if($title == "" && file_exists('./title.php'))
	{
		$title = trim(file_get_contents('./title.php'));
		if(trim($title) == "Colin R Williams")
		{
			$title = "";
		}
	}
	if($login && $requirelogin)
		$title = str_replace(array("USERNAME"), array($_SESSION['username']), $title);
	if($description == "" && file_exists('./description.php'))
	{
		$description = trim(file_get_contents('./description.php'));
	}
	if($keywords != null)
	{
		$keywords = ",".str_replace("GENERIC", "", $keywords);
	}
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-type" content="text/html;charset=utf-8" />
<meta name="keywords" content="opendatamap,mymap<?= $keywords ?>" />
<? if($description != "") { ?>
<meta name="description" content="<?= $description ?>" />
<? } ?>
<meta name="language" content="en-GB" />
<!--<meta name="google-site-verification" content="_M4ulHThaSjm5gqNXDBcTTKFvom_3kTijaGgSM1VjXQ" />-->
<title>opendatamap: mymap<? if(!empty($title)) echo " | ".$title ?></title>
<link href="css/reset.css" rel="stylesheet" type="text/css"/>
<link href="css/style.css" rel="stylesheet" type="text/css"/>
<link href="css/default.css" rel="stylesheet" type="text/css" title="default" media="screen,handheld,tv,projection"/>
</head>
<body>
<? include_once('../googleanalytics.php'); ?>
<p style='display:none'><a href="#content">Skip to content</a></p>
<!--
<div class="sidebar">
	<div class="sidebarheader" style='font-size:1.5em; position:relative; bottom:10px'><a href='/'>ANAC&nbsp;2012</a></div>
	<br/>
	<div class="sidebarheader">Hosted by:</div>
	<div class="sidebarlink-img"><a href="http://www.soton.ac.uk"><img src="/img/logo/uos.png" alt="University of Southampton" title="University of Southampton" /></a></div>
	<br/>
	<div class="sidebarlink"><a href="#instructions">Instructions</a></div>
	<br/>
	<div class="sidebarlink"><a href="/domains">Domains</a></div>
	<br/>
	<div class="sidebarheader">Previous Competitions</div>
	<div class="sidebarlink"><a href="http://www.itolab.nitech.ac.jp/ANAC2011/">ANAC2011</a></div>
	<div class="sidebarlink"><a href="http://mmi.tudelft.nl/negotiation/index.php/ANAC_2010">ANAC2010</a></div>
	<a style='float:right; position:absolute; bottom:70px; right:25px;' href="http://validator.w3.org/check?uri=referer"><img src="http://www.w3.org/Icons/valid-xhtml10-blue" alt="Valid XHTML 1.0 Strict" height="31" width="88" /></a>
	<a style='float:right; position:absolute; bottom:35px; right:25px;' href="http://jigsaw.w3.org/css-validator/check/referer"><img style="border:0;width:88px;height:31px" src="http://jigsaw.w3.org/css-validator/images/vcss-blue" alt="Valid CSS" /></a>
</div>
-->
<div class='header'>
	<h1><a href='/' style='text-decoration:none'>opendatamap: mymap</a></h1>
</div>
<!--
<div class='breadcrumb'>
	<?
		$parents = "../";
		if(file_exists('../breadcrumb.php'))
			include '../breadcrumb.php';
		if(file_exists('./title.php'))
		{
			echo " &gt; ";
			include './title.php';
		}
	?>
</div>
-->
<?
if(!empty($title))
{
	echo "<h2>".$title;
	if($login && isset($_SESSION['username']))
		echo '<br/><small><a href=\'logout\'>(logout)</a></small>';
	echo "</h2>";
}
?>
<div class="clear">&nbsp;</div>
<div class="content">
	<a name="content"></a>
	<?
}
?>
<?
function outputFooter()
{
	?>
</div>
<div class="footer">
<a href='/mymap/about'>About this tool</a> | &copy; Colin Williams, 2011<!---<?= date('Y')?>-->.
</div>
</body>
</html>
	<?
}

function mklink($url, $text="", $title="")
{
	if($text == "")
	{
		if(preg_match('/^([a-z-]+\/)+$/', $url) && file_exists($url.'title.php'))
		{
			$text = file_get_contents($url.'title.php');
		}
		else
		{
			$text = $url;
		}
	}
	if($title == "")
	{
		$title = $text;
	}
	return "<a href='$url' title='$title'>".str_replace(" ", "&nbsp;", $text)."</a>";
}

function getLatLongFromPostcode($postcode)
{
	require_once('/home/opendatamap/mysql.inc.php');
	$params[] = mysql_real_escape_string(str_replace(' ', '', $postcode));
	$q = 'SELECT latitude AS lat, longitude AS lon FROM postcode WHERE code = \''.$params[0].'\'';
	$res = mysql_query($q);
	if($row = mysql_fetch_assoc($res))
	{
		return $row;
	}
	return null;
}

function getLatLongFromBuildingNumber($bno)
{
	require_once('../inc/sparqllib.php');
	$data = sparql_get("http://sparql.data.southampton.ac.uk", "
	SELECT ?lat ?lon WHERE {
		<http://id.southampton.ac.uk/building/$bno> <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
		<http://id.southampton.ac.uk/building/$bno> <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?lon .
	}
	", '../');
	if(count($data) == 1)
		return $data[0];
	else
		return null;
}

function loadCSV($filename, $base="", $idcolname, $namecolname, $iconcolname, $latcolname, $loncolname, $pccolname, $bnocolname, $srccolname, $location, $matchcolnames = false, $includenoncsv = true)
{
	$colnames = null;
	$data = array();
	if ($filename != '' && ($handle = fopen($filename, "r")) !== FALSE) {
		while (($row = @fgetcsv($handle, 1000, ",")) !== FALSE) {
			if($row[0] == '*COMMENT' || $row[0] == '')
				continue;
			$num = count($row);
			if($colnames == null)
			{
				$outcolnames[strtolower($namecolname)] = 'label';
				$outcolnames[strtolower($iconcolname)] = 'icon';
				$outcolnames[strtolower($latcolname )] = 'lat';
				$outcolnames[strtolower($loncolname )] = 'lon';
				$outcolnames[strtolower($pccolname  )] = 'pc';
				$outcolnames[strtolower($bnocolname )] = 'bno';
				$outcolnames[strtolower($srccolname )] = 'src';
				for ($c=0; $c < $num; $c++) {
					$colnames[strtolower($row[$c])] = $c;
					$outcolnames[strtolower($row[$c])] = $row[$c];
				}
				if(!$matchcolnames)
				{
					$outcolnames[strtolower($namecolname)] = 'label';
					$outcolnames[strtolower($iconcolname)] = 'icon';
					$outcolnames[strtolower($latcolname )] = 'lat';
					$outcolnames[strtolower($loncolname )] = 'lon';
					$outcolnames[strtolower($pccolname  )] = 'pc';
					$outcolnames[strtolower($bnocolname )] = 'bno';
					$outcolnames[strtolower($srccolname )] = 'src';
				}
			}
			else
			{
				@$data[$base.$row[$colnames[$idcolname]]] = array();
				if($matchcolnames)
				{
					foreach($colnames as $colname => $c)
					{
						$data[$base.$row[$colnames[$idcolname]]][$outcolnames[$colname]] = $row[$c];
					}
				}
				if(isset($colnames[$namecolname])) $data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($namecolname)]] = $row[$colnames[$namecolname]];
				if(isset($colnames[$iconcolname])) $data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($iconcolname)]] = $row[$colnames[$iconcolname]];
				if(isset($colnames[$latcolname ])) $data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($latcolname )]] = $row[$colnames[$latcolname ]];
				if(isset($colnames[$loncolname ])) $data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($loncolname )]] = $row[$colnames[$loncolname ]];
				if(isset($colnames[$pccolname  ])) $data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($pccolname  )]] = $row[$colnames[$pccolname  ]];
				if(isset($colnames[$bnocolname ])) $data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($bnocolname )]] = $row[$colnames[$bnocolname ]];
				if(
					isset($data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($latcolname )]]) &&
					'' != $data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($latcolname )]] &&
					isset($data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($loncolname )]]) &&
					'' != $data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($loncolname )]]
				)
				{
					if(isset($colnames[$srccolname]))
					{
						$data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($srccolname  )]] = $row[$colnames[$srccolname]];
					}
					else
					{
						$data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($srccolname  )]] = 'CSV';
					}
				}
				else if(isset($data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($pccolname  )]]) && '' != $data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($pccolname  )]])
				{
					$ll = getLatLongFromPostcode($data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($pccolname  )]]);
					if(!is_null($ll))
					{
						$data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($latcolname )]] = $ll['lat'];
						$data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($loncolname )]] = $ll['lon'];
						$data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($srccolname )]] = '<em>'.$data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($pccolname  )]].'</em>';
					}
				}
				else if(isset($data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($bnocolname )]]) && '' != $data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($bnocolname )]])
				{
					$ll = getLatLongFromBuildingNumber($data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($bnocolname )]]);
					if(!is_null($ll))
					{
						$data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($latcolname )]] = $ll['lat'];
						$data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($loncolname )]] = $ll['lon'];
						$data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($srccolname )]] = '<em>B'.$data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($bnocolname )]].'</em>';
					}
				}
				if(isset($location[$row[$colnames[$idcolname]]]))
				{
					$data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($latcolname )]] = $location[$row[$colnames[$idcolname]]][0];
					$data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($loncolname )]] = $location[$row[$colnames[$idcolname]]][1];
					$data[$base.$row[$colnames[$idcolname]]][$outcolnames[strtolower($srccolname )]] = $location[$row[$colnames[$idcolname]]][2];
				}
			}
		}
		fclose($handle);
	}
	
	if($includenoncsv)
	{
		foreach($location as $id => $r)
		{
			if(!isset($data[$id]))
			{
				$data[$id] = array(
					$outcolnames[strtolower($namecolname)] => $r[3],
					$outcolnames[strtolower($iconcolname)] => $r[4],
					$outcolnames[strtolower($latcolname )] => $r[0],
					$outcolnames[strtolower($loncolname )] => $r[1],
					$outcolnames[strtolower($srccolname )] => $r[2]
				);
			}
		}
	}

	$cns;
	foreach($colnames as $colname => $c)
	{
		$cns[$outcolnames[strtolower($colname)]] = $c;
	}
	return array($cns, $data);
}
?>
