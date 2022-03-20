<?php
header("Content: text/html; charset=utf-8");
header("Access-Control-Allow-Origin: *");
date_default_timezone_set('Europe/Istanbul');
session_start();

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 1);

// Gerekli sınıflar içeri alınıyor
require_once("classes/db.class.php");
require_once("classes/idea.class.php");
require_once("classes/user.class.php");

$user = new user;

if(isset($_GET["code"])) {
	
	// Ideasoft üzerinden dönen "access", "refresh" token istek kodu
	$code = $_GET["code"];

	$response = $user->firstAccess($code);
	
	echo "API bağlantısı kuruldu.";

}
else {

	if(!isset($_POST["action"]) || !is_numeric($_POST["action"])) {
		exit();
	}

	$status = $user->checkStatus();

	$action = $_POST["action"];

	if($action == 1) {
		echo json_encode($user->getVariantImages($_POST["product_id"]));
	}

}
?>