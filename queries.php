<?php

    header("Access-Control-Allow-Origin: *");
    require_once("config.inc.php");

    try {

        $dbh = new PDO("mysql:host=" . HOST . "; dbname=" . DBNAME . "; charset=utf8;", DBUSER, DBPASS);
        $product_id = $_POST["product_id"];
        $qty = $_POST["qty"];

        $stmt = $dbh->prepare("SELECT * FROM weights WHERE iid=?");
        $stmt->execute(array($product_id));
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result["quantity"] = $qty;
        echo json_encode($result);

    } catch (PDOException $ex) {
        $ex->getMessage();
    }

?>