<?php
class db {
	
	// Veritabanı ve mağazanın ideasoft bilgileri için gerekli değişkenleri oluşturuyoruz.
	
	public $db;
	protected $client_id;
	protected $client_secret;
	protected $api_access;
	protected $url;
	protected $redirect_uri;
	protected $refresh_token;
	protected $access_token;
	
	// Veritabanı bağlantısı
	
	protected function connect() {
		
		require_once(__DIR__ . "/../config.inc.php");
		
		$this->client_id = CLIENTID;
		$this->client_secret = CLIENTSECRET;
		$this->api_access = APIACCESS;
		$this->url = URL;
		$this->redirect_uri = REDIRECTURI;
		
		$this->db = new mysqli(HOST, DBUSER, DBPASS, DBNAME);

		if ($this->db->connect_error) {
			die("Connection failed: " . $this->db->connect_error);
		}
		
		$this->db->set_charset("utf8");
		
	}
	
	// Veritabanı bağlantı iptali
	
	protected function close() {
		
		$this->db->close();
		
	}

}
?>