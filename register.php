<?
error_reporting(0);
include 'functions.inc.php';
outputHeader("Register an account", "", "GENERIC");
$post = false;
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$post = true;
	list($errors, $bad) = verify_registration_fields($_POST);
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
<form action='register' method='post'>
	<table>
		<tr class='comp <?= $post && $bad['username'] ? "bad" : "" ?>'><td><label for='username'>Username:</label></td><td><input id='username' name='username' style='width:10em' value='<?= $_REQUEST['username'] ?>' /></td></tr>
<?
if(!isset($_REQUEST['resetcode']))
?>
		<tr class='comp <?= $post && $bad['email'] ? "bad" : "" ?>'><td><label for='email'>Email Address:</label></td><td><input id='email' name='email' style='width:20em' value='<?= $_POST['email'] ?>' /></td></tr>
<?
}
?>
		<tr><td colspan='2'><hr /></td></tr>
		<tr class='comp <?= $post && $bad['password1'] ? "bad" : "" ?>'><td><label for='password1'>Password:</label></td><td><input type='password' id='password1' name='password1' style='width:10em' value='<?= $_POST['password1'] ?>' /></td></tr>
		<tr class='comp <?= $post && $bad['password2'] ? "bad" : "" ?>'><td><label for='password2'>Confirm Password:</label></td><td><input type='password' id='password2' name='password2' style='width:10em' value='<?= $_POST['password2'] ?>' /></td></tr>
		<tr><td /><td><input type='submit' /></td></tr>
<?
if(isset($_REQUEST['resetcode']))
?>
		<tr class='comp <?= $post && $bad['resetcode'] ? "bad" : "" ?>'><td><label for='resetcode'>Reset Code:</label></td><td><input disabled='disabled' id='resetcode' name='resetcode' style='width:10em' value='<?= $_REQUEST['resetcode'] ?>' /></td></tr>
		<tr><td /><td><input type='submit' /></td></tr>
	</table>
</form>
<?
}
else
{
	session_start();
	require_once('/home/opendatamap/mysql.inc.php');
	if(isset($_POST['resetcode']))
	{
		$res = reset_user($_POST['username'], $_POST['password1'], $_POST['resetcode']);
		if(!$res)
		{
			echo 'Failed to reset password.';
		}
		else
		{
			$_SESSION['username'] = $_POST['username'];
			echo "Your password has been changed and you are now logged in as ".$_SESSION['username'].".  <a href='..'>View your map list</a>.";
		}
	}
	else
	{
		$res = register_user($_POST['username'], $_POST['email'], $_POST['password1']);
		if(!$res)
		{
			echo 'Failed to register user.';
		}
		else
		{
			$_SESSION['username'] = $_POST['username'];
			echo "You are now logged in as ".$_SESSION['username'].".  <a href='new'>Create a new map</a>.";
		}
	}
}
outputFooter();
function register_user($username, $email, $password)
{
	$params = array();
	$params[] = "'".mysql_real_escape_string($username)."'";
	$params[] = "'".mysql_real_escape_string($email)."'";
	$params[] = "'".md5($password)."'";
	$q = "INSERT INTO users VALUES (".implode(',', $params).")";
	return mysql_query($q);
}
function reset_user($username, $password, $resetcode)
{
	$params = array();
	$params[] = "'".mysql_real_escape_string($username)."'";
	$params[] = "'".mysql_real_escape_string($resetcode)."'";
	$params[] = "'".md5($password)."'";
	$q = "UPDATE users SET password = ".$params[2].", resetcode = NULL, resettime = NULL WHERE username = ".$params[0]." AND resetcode = ".$params[1]." AND resettime > DATE_SUB(NOW(), INTERVAL 24 HOURS)";
	return mysql_query($q);
}
function verify_registration_fields($fields)
{
	$errors = array();
	$bad = array();
	if(trim($fields['username']) == "")
	{
		$errors[] = 'Username not set';
		$bad['username'] = true;
	}
	if(!isset($fields['resetcode']) && trim($fields['email']) == "")
	{
		$errors[] = 'Email address not set';
		$bad['email'] = true;
	}
	if(trim($fields['password1']) == "")
	{
		$errors[] = 'Password not set';
		$bad['password1'] = true;
	}
	elseif($fields['password1'] != $fields['password2'])
	{
		$errors[] = 'Passwords do not match';
		$bad['password2'] = true;
	}
	return array($errors, $bad);
}
?>

