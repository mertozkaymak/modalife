<?php
    header("Content-Type: text/html; charset=utf-8");

    require_once("classes/db.class.php");
    require_once("classes/idea.class.php");
    require_once("classes/user.class.php");
    require_once("config.inc.php");

    $user = new user;
    $status = $user->checkStatus();

    function productController($dbh, $id, $fullname, $slug, $weight){
        
        $stmt = $dbh->prepare("SELECT * FROM weights WHERE iid=?");
        $stmt->execute(array($id));
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(count($result) > 0){
            $stmt = $dbh->prepare("UPDATE weights SET fullname=?, slug=?, weight=? WHERE iid=?");
            $stmt->execute(array($fullname, $slug, $weight, $id));
        }else{
            $stmt = $dbh->prepare("INSERT INTO weights (iid, fullname, slug, weight) VALUES(?, ?, ?, ?)");
            $stmt->execute(array($id, $fullname, $slug, $weight));
        }

    }

    if($status == 1) {

        try {

            $dbh = new PDO("mysql:host=" . HOST . "; dbname=" . DBNAME . "; charset=utf8;", DBUSER, DBPASS);
            $products = $user->getAllIdeaProducts();
            
            for ($index=0; $index < count($products); $index++) {
                productController($dbh, $products[$index]["id"], $products[$index]["fullName"], $products[$index]["slug"], $products[$index]["volumetricWeight"]);
                $user->doFlush();
            }

        } catch (PDOException $ex) {
            $ex->getMessage();
        }

    }
?>