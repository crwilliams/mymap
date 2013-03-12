<?
error_reporting(0);
include 'functions.inc.php';
outputHeader("Reset an account password", "", "GENERIC");
$post = false;
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$post = true;
	list($errors, $bad) = verify_reset_fields($_POST);
	if(count($errors) > 0)
	{
		echo '<div style="background-color:#FF9999; margin:10px; padding:10px; text-align:center">';
		foreach($errors as $error)
		{
			echo $error.'<br />';
		}
		echo '</div>';
	}
}
if(!$post || count($errors) > 0)
{
?>
<form action='reset' method='post'>
	<table>
		<tr class='comp <?= $post && $bad['identity'] ? "bad" : "" ?>'><td><label for='identity'>Username or Email Address:</label></td><td><input id='identity' name='identity' style='width:10em' value='<?= $_POST['identity'] ?>' /></td></tr>
		<tr><td /><td><input type='submit' /></td></tr>
	</table>
</form>
<?
}
else
{
	session_start();
	require_once('/home/opendatamap/mysql.inc.php');
	$res = reset_user($_POST['identity']);
	echo 'Please check your email for a password reset link.';
}
outputFooter();
function reset_user($identity)
{
	$param = "'".mysql_real_escape_string($identity)."'";
	if(strpos($identity, '@') === false)
		$q = 'SELECT * FROM users WHERE username = '.$param;
	else
		$q = 'SELECT * FROM users WHERE email = '.$param;
	$res = mysql_query($q);
	$userdetails = mysql_fetch_assoc($res);
	if($userdetails)
	{
		$userdetails['resetcode'] = bin2hex(openssl_random_pseudo_bytes(16));
		$q = "UPDATE users SET resetcode = '".$userdetails['resetcode']."', resettime = NOW() WHERE username = '".mysql_real_escape_string($userdetails['username'])."'";
		mysql_query($q);
		mail($userdetails['email'], "Password Reset for opendatamap mymap", "To reset your password, visit http://".$_SERVER['SERVER_NAME']."/mymap/register?username=".urlencode($userdetails['username'])."&resetcode=".$userdetails['resetcode']." within the next 24 hours.");
	}
}
function verify_reset_fields($fields)
{
	$errors = array();
	$bad = array();
	if(trim($fields['identity']) == "")
	{
		$errors[] = 'Username or email address not set';
		$bad['identity'] = true;
	}
	return array($errors, $bad);
}
?>

