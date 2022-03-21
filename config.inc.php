<?php
// Veritabanı bilgileri
define("HOST","localhost");
define("DBNAME", "***");
define("DBUSER", "***");
define("DBPASS", "***");

// Mağaza Ideasoft bilgileri
define("CLIENTID", "***");
define("CLIENTSECRET", "***");
define("REDIRECTURI", "***");
define("URL", "***");
define("APIACCESS", URL . "/admin/user/auth?client_id=" . CLIENTID . "&response_type=code&state=***&redirect_uri=" . REDIRECTURI);
?>
