<?php
header("Content-Type: text/html; charset=utf-8");

require_once("classes/db.class.php");
require_once("classes/idea.class.php");
require_once("classes/user.class.php");

$user = new user;

$status = $user->checkStatus();

if($status == 1) {
	echo "API bağlantısı kuruldu.";
}
else {
	echo '<a href="' . $status . '">API bağlantısını sağla</a>';
}
?>