<?php
class idea extends db {
	
	private $counterr = 1;
	private $total = 0;
	
	// Sınıf oluşturulduğun anda veritabanı bağlantısı sağlanıyor
	
	public function __construct() {
		
		parent::connect();
		
	}
	
	// Sınıf kapatıldığı anda veritabanı bağlantısı iptal ediliyor
	
	public function __destruct() {
	
		parent::close();
	
	}
	
	// Ideasoft API bağlantı kontrolü
	
	public function checkStatus() {
	
		$stmt = $this->db->prepare("SELECT access_token, refresh_token FROM ideasoft WHERE access_token IS NOT NULL AND refresh_token IS NOT NULL LIMIT 1");
		$stmt->execute();
		$result = $stmt->get_result();
		
		if($result->num_rows == 1) {
			
			$row = $result->fetch_object();
			$this->access_token = $row->access_token;
			$this->refresh_token = $row->refresh_token;
			
			$stmt->close();
			return 1;
			
		}
		else {
		
			$stmt->close();
			return $this->api_access;
		
		}
	
	}

	// Ideasoft API ile ilk bağlantıyı oluştur
	
	public function firstAccess($code) {
			
		$fields = array('grant_type'=>'authorization_code','client_id'=>$this->client_id,'client_secret'=>$this->client_secret,'code'=>$code,'redirect_uri'=>$this->redirect_uri);
		$postvars = $this->createPostvars($fields);
		
		$response = $this->callAPI("POST", "/oauth/v2/token", $postvars);
		
		return $this->updateToken($response);
	
	}
	
	// Ideasoft API "access", "refresh" token yenileme
	
	public function refreshToken() {
	
		$fields = array( 'grant_type'=>'refresh_token','client_id'=>$this->client_id,'client_secret'=>$this->client_secret,'refresh_token'=>$this->refresh_token);
		$postvars = $this->createPostvars($fields);
		
		$response = $this->callAPI("POST", "/oauth/v2/token", $postvars);
		
		return $this->updateToken($response);
	
	}
	
	// Değişkenleri POST için uygun formata dönüştürme
	
	private function createPostvars($fields) {
	
		$postvars = '';
		
		foreach($fields as $key=>$value) {
			$postvars .= $key . "=" . $value . "&";
		}
		
		$postvars = rtrim($postvars, '&');
		
		return $postvars;
	
	}
	
	// Token veritabanı güncelleme
	
	private function updateToken($response) {
	
		$stmt = $this->db->prepare("UPDATE ideasoft SET access_token = ?, refresh_token = ? WHERE id = 1");
		$stmt->bind_param("ss", $response["access_token"], $response["refresh_token"]);
		
		if($stmt->execute()) {
			
			$this->access_token = $response["access_token"];
			$this->refresh_token = $response["refresh_token"];
			
			$stmt->close();
			return 1;
			
		}
		else {
		
			$stmt->close();
			return 0;
		
		}
	
	}
	
	// Farklı metodlar ile CURL istekleri
	
	public function callAPI($method, $url, $data = "") {

	    $curl = curl_init();

	    switch ($method) {
		  
			case "GET":
				break;
			
			case "POST":
		  
				curl_setopt($curl, CURLOPT_POST, 1);
			
				if ($data) {
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				}
			
				break;
		  
			case "PUT":
		  
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
			
				if ($data) {
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);	
				}
			
				break;
			
			case "DELETE":
		  
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");					
				break;

		}

		curl_setopt($curl, CURLOPT_URL, $this->url . $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		if($method != "POST" && $url != "/oauth/v2/token") {
			curl_setopt($curl, CURLOPT_HEADER, 1);
		}
		
		if(strpos($url, 'token') === false) {
			
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Authorization: Bearer ' . $this->access_token,
			  'Content-Type: application/json'
			));
			  
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			
		}

		$result = curl_exec($curl);

		if($method != "POST" && $method != "PUT" && $method != 'DELETE' && $url != "/oauth/v2/token" && strpos($result, "mode=block") !== false) {
			
			$content = explode("mode=block", $result);
			$header = trim($content[0]) . "mode=block";			
			$content = trim($content[1]);
			
			if($this->total == 0) {
				$total2 = explode("total_count: ", $header);
				if(count($total2) > 0) {
					$total2 = explode(" ", $total2[1]);
					$this->total = ceil((float)$total2[0] / 100);	
				}
			}

			if(strpos($header, "504") !== false) {
				return 0;
			}
			
			if(strpos($header, "429") !== false) {
				return 1;
			}
			
			$this->counterr++;

			curl_close($curl);
		   
			return json_decode($content, true);
			
		}
		else if($method == 'DELETE') {
			
			if(strpos($result, '204') !== false) {
				return true;
			}
			else {
				return false;
			}
			
		}
		else {
		
			curl_close($curl);
			
			return json_decode($result, true);
		
		}
					   
	}
	
	public function readXML($url, $username, $pass) {
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'ws_user: ' . $username,
			'ws_pass: ' . $pass,
			'ws_type: xml',
			'ws_startIndex: 1',
			'ws_finishIndex: 35000'
		));

		$result = curl_exec($ch);
		if(!$result){die("Connection Failure");}
		curl_close($ch);

		$xml = simplexml_load_string($result);

		return $xml;
			
	}
	
	public function getAllIdeaCurrencies() {
		
		$ideacurrencies = array();
		$counter = -1;
		
		$currencies = $this->callApi("GET", "/api/currencies?status=1");
		
		for($i = 0; $i < count($currencies); $i++) {
			$counter++;
			$ideacurrencies[$counter] = $currencies[$i];
		}
		
		$this->total = 0;
		
		return $ideacurrencies;
	
	}

	public function getAllIdeaProductSpecs() {
	
		$page = 1;
		$stop = 0;
		
		$ideaspecifications = array();
		$counter = -1;

		while($stop == 0) {

			$specifications = $this->callApi("GET", "/api/selection_to_products?limit=100&page=" . $page);

			if($specifications == 0) {
											
				sleep(1);
				
			}
			else if($specifications == 1) {
								
				sleep(5);
				
			}
			else {
				
				for($i = 0; $i < count($specifications); $i++) {
					$counter++;
					$ideaspecifications[$counter] = $specifications[$i];
				}
				
				$page++;
				sleep(1);
				
			}
			
			if($this->total != 0 && $this->total < $page) {
				$this->total = 0;
				$this->counterr = 1;
				$stop = 1;
			}
			
		}
		
		return $ideaspecifications;
	
	}
	
	public function getAllIdeaDetails() {
	
		$page = 1;
		$stop = 0;
		
		$ideadetails = array();
		$counter = -1;

		while($stop == 0) {
	
			$details = $this->callApi("GET", "/api/product_details?limit=100&page=" . $page);			
	
			if($details == 0) {
	
				sleep(1);
				
			}
			else if($details == 1) {
				
				sleep(5);
				
			}
			else {
				
				if($this->total == "0") {
					$stop = 1;
					break;
				}
				
				for($i = 0; $i < count($details); $i++) {
					$counter++;
					$ideadetails[$counter] = $details[$i];
				}
				
				$page++;
				sleep(1);
				
			}
			
			if($this->total != 0 && $this->total < $page) {
				$this->total = 0;
				$this->counterr = 1;
				$stop = 1;
			}
			
		}
		
		return $ideadetails;
	
	}
	
	public function getIdeaProductsByIds($ids) {
	
		$page = 1;
		$stop = 0;
		
		$ideaproducts = array();
		$counter = -1;

		while($stop == 0) {
				
			$products = $this->callApi("GET", "/api/products?ids=" . $ids . "&limit=100&page=" . $page);

				
			if($products == 0) {
			
				sleep(1);
				
			}
			else if($products == 1) {
				
				sleep(5);
				
			}
			else {
				
				for($i = 0; $i < count($products); $i++) {
					$counter++;
					$ideaproducts[$counter] = $products[$i];
				}
				
				$page++;
				sleep(1);
				
			}
			
			if($this->total != 0 && $this->total < $page) {
				$this->total = 0;
				$this->counterr = 1;
				$stop = 1;
			}
			
		}
		
		return $ideaproducts;
	
	}
	
	public function getAllIdeaProducts() {
	
		$page = 1;
		$stop = 0;
		
		$ideaproducts = array();
		$counter = -1;

		while($stop == 0) {
	
				
			$products = $this->callApi("GET", "/api/products?limit=100&page=" . $page);
	
				
			if($products == 0) {
				
				sleep(1);
				
			}
			else if($products == 1) {
				
				sleep(5);
				
			}
			else {
				
				for($i = 0; $i < count($products); $i++) {
					$counter++;
					$ideaproducts[$counter] = $products[$i];
				}
				
				$page++;
				sleep(1);
				
			}
			
			if($this->total != 0 && $this->total < $page) {
				$this->total = 0;
				$this->counterr = 1;
				$stop = 1;
			}
			
		}
		
		return $ideaproducts;
	
	}
	
	public function httpPost($url, $params) { 

		$ch = curl_init(); 

		curl_setopt($ch,CURLOPT_URL,$url); 
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  'Content-Type: application/json'
		));
		
		if($params == "") {
		}
		else {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params)); 
		}
		
		$output = curl_exec($ch); 

		curl_close($ch); 
		return $output; 
		
	}
	
	public function getIdeaOrderById($id) {

		return $this->callApi("GET", "/api/orders?ids=" . $id);

	}

	public function updateIdeaOrderById($order) {

		return $this->callApi("PUT", "/api/orders/" . $order["id"], json_encode($order));

	}

	public function getIdeaOrderByTransactionId($id) {

		$order = array();
		while(!isset($order[0])) {
			$order = $this->callApi("GET", "/api/orders?transactionId=" . $id);
			sleep(1);
		}			
		return $order[0];		

	}
	
	public function getCustomerIdeaOrders($cid = "") {
	
		$page = 1;
		$stop = 0;
		
		$ideaorders = array();
		$counter = -1;

		while($stop == 0) {
			
			$orders = $this->callApi("GET", "/api/orders?sort=-id&limit=100&page=" . $page);
	
			if($orders == 0) {

				sleep(1);
				
			}
			else if($orders == 1) {

				sleep(5);
				
			}
			else {
				
				if($this->total == 0) {
					$stop = 1;
					break;
				}
				
				for($i = 0; $i < count($orders); $i++) {
					$counter++;
					$ideaorders[$counter] = $orders[$i];
				}
				
				$page++;
				sleep(1);
				
			}
			
			if($this->total != 0 && $this->total < $page) {
				$this->total = 0;
				$this->counterr = 1;
				$stop = 1;
			}
			
		}

		return $ideaorders;
	
	}
	
	public function getAllIdeaImages() {
	
		$page = 1;
		$stop = 0;
		
		$ideaimages = array();
		$counter = -1;

		while($stop == 0) {

			$images = $this->callApi("GET", "/api/product_images?limit=100&page=" . $page);

			if($images == 0) {
						
				sleep(1);
				
			}
			else if($images == 1) {

				sleep(5);
				
			}
			else {
				
				if($this->total == "0") {
					$stop = 1;
					break;
				}
				
				for($i = 0; $i < count($images); $i++) {
					$counter++;
					$ideaimages[$counter] = $images[$i];
				}
				
				$page++;
				sleep(1);
				
			}
			
			if($this->total != 0 && $this->total < $page) {
				$this->total = 0;
				$this->counterr = 1;
				$stop = 1;
			}
			
		}
		
		return $ideaimages;
	
	}
	
	public function getAllIdeaProvinces() {
	
		$page = 1;
		$stop = 0;
		
		$ideaprovinces = array();
		$counter = -1;

		while($stop == 0) {
	
			$provinces = $this->callApi("GET", "/api/locations?country=1&limit=100&page=" . $page);

			if($provinces == 0) {
							
				sleep(1);
				
			}
			else if($provinces == 1) {
					
				sleep(5);
				
			}
			else {
				
				for($i = 0; $i < count($provinces); $i++) {
					$counter++;
					$ideaprovinces[$counter] = $provinces[$i];
				}
				
				$page++;
				sleep(1);
				
			}
			
			if($this->total != 0 && $this->total < $page) {
				$this->total = 0;
				$this->counterr = 1;
				$stop = 1;
			}
			
		}
		
		return $ideaprovinces;
	
	}
	
	public function getAllIdeaCounties() {
	
		$page = 1;
		$stop = 0;
		
		$ideacounties = array();
		$counter = -1;

		while($stop == 0) {
	
			$counties = $this->callApi("GET", "/api/towns?country=1&limit=100&page=" . $page);
	
			if($counties == 0) {
					
				sleep(1);
				
			}
			else if($counties == 1) {
						
				sleep(5);
				
			}
			else {
				
				for($i = 0; $i < count($counties); $i++) {
					$counter++;
					$ideacounties[$counter] = $counties[$i];
				}
				
				$page++;
				sleep(1);
				
			}
			
			if($this->total != 0 && $this->total < $page) {
				$this->total = 0;
				$this->counterr = 1;
				$stop = 1;
			}
			
		}
		
		return $ideacounties;
	
	}
	
	public function getAllIdeaAddresses() {
	
		$page = 1;
		$stop = 0;
		
		$ideaaddresses = array();
		$counter = -1;

		while($stop == 0) {
	
			$addresses = $this->callApi("GET", "/api/member_addresses?limit=100&page=" . $page);				
				
			if($addresses == 0) {
						
				sleep(1);
				
			}
			else if($addresses == 1) {
							
				sleep(5);
				
			}
			else {
				
				if($this->total == "0") {
					$stop = 1;
					break;
				}
				
				for($i = 0; $i < count($addresses); $i++) {
					$counter++;
					$ideaaddresses[$counter] = $addresses[$i];
				}
				
				$page++;
				sleep(1);
				
			}
			
			if($this->total != 0 && $this->total < $page) {
				$this->total = 0;
				$this->counterr = 1;
				$stop = 1;
			}
			
		}
		
		return $ideaaddresses;
	
	}
	
	public function getAllIdeaCustomerGroups() {
	
		$groups = $this->callApi("GET", "/api/member_groups?limit=100");
	
		return $groups;
		
	}
	
	public function getAllIdeaCustomers() {
	
		$page = 1;
		$stop = 0;
		
		$ideacustomers = array();
		$counter = -1;

		while($stop == 0) {
	
			$customers = $this->callApi("GET", "/api/members?limit=100&page=" . $page);

			if($customers == 0) {
						
				sleep(1);
				
			}
			else if($customers == 1) {
							
				sleep(5);
				
			}
			else {
				
				if($this->total == "0") {
					$stop = 1;
					break;
				}
				
				for($i = 0; $i < count($customers); $i++) {
					$counter++;
					$ideacustomers[$counter] = $customers[$i];
				}
				
				$page++;
				sleep(1);
				
			}
			
			if($this->total != 0 && $this->total < $page) {
				$this->total = 0;
				$this->counterr = 1;
				$stop = 1;
			}
			
		}
		
		return $ideacustomers;
	
	}
	
	public function getIdeaCustomerByMobilephone($phone) {

		$customer = $this->callApi("GET", "/api/members?mobilePhoneNumber=" . $phone);
			
		if(count($customer) == 1) {
			
			return $customer[0];

		}
		else {
			
			return 0;
			
		}

	}
	
	public function deleteIdeaProduct($id) {
	
		return $this->callApi("DELETE", "/api/products/" . $id);
	
	}
	
	public function getAllIdeaBrands() {
		
		$page = 1;
		$stop = 0;
		
		$ideabrands = array();
		$counter = -1;

		while($stop == 0) {
				
			$brands = $this->callApi("GET", "/api/brands?limit=100&page=" . $page);
							
			if(count($brands) > 0) {
				
				for($i = 0; $i < count($brands); $i++) {
					$counter++;
					$ideabrands[$counter] = $brands[$i];
				}
				
				$page++;
				usleep(100000);

			}
			else {
				
				$stop = 1;
				
			}

		}
		
		return $ideabrands;
		
	}
	
	public function getAllIdeaCategories() {
	
		$page = 1;
		$stop = 0;
		
		$ideacategories = array();
		$counter = -1;

		while($stop == 0) {

			$categories = $this->callApi("GET", "/api/categories?limit=100&page=" . $page);			
			
			
			if($categories == 0) {
				
				sleep(1);
				
			}
			else if($categories == 1) {
							
				sleep(5);
				
			}
			else {
				
				if($this->total == "0") {
					$stop = 1;
					break;
				}
				
				for($i = 0; $i < count($categories); $i++) {
					$counter++;
					$ideacategories[$counter] = $categories[$i];
				}
				
				$page++;
				sleep(1);
				
			}
			
			if($this->total != 0 && $this->total < $page) {
				$this->total = 0;
				$this->counterr = 1;
				$stop = 1;
			}
			
		}
		
		return $ideacategories;
	
	}
	
	public function addIdeaBrand($name) {
		
		$data = array();
		$data["name"] = $name;
		$data["slug"] = $name;
		$data["sortOrder"] = 999;
		$data["status"] = 1;
		$data["distributorCode"] = $name;
		$data["distributor"] = $name;
		$data["imageFile"] = "";
		$data["showcaseContent"] = $name . " markalı tüm ürünler";
		$data["displayShowcaseContent"] = 1;
		$data["metaKeywords"] = $name;
		$data["metaDescription"] = $name . " markalı tüm ürünler";
		$data["pageTitle"] = $name;
		
		$data = json_encode($data);
		
		return $this->callApi("POST", "/api/brands", $data);
		
	}
	
	public function addIdeaProductToCategory($productid, $categoryid) {
		
		$data = array();
		$data["sortOrder"] = 9999;
		$data["product"] = array();
		$data["product"]["id"] = $productid;
		$data["category"] = array();
		$data["category"]["id"] = $categoryid;
		
		$data = json_encode($data);
		
		return $this->callApi("POST", "/api/product_to_categories", $data);
	
	}
	
	public function addIdeaCategory($name) {
		
		$data = array();
		$data["name"] = $name;
		$data["slug"] = $name;
		$data["status"] = 1;
		$data["sortOrder"] = 999;
		$data["percent"] = 1;
		$data["distributorCode"] = $name;
		$data["displayShowcaseContent"] = 1;
		$data["showcaseContent"] = $name . " araç parçaları";
		$data["displayShowcaseContent"] = 1;
		$data["showcaseContentDisplayType"] = 3;
		$data["metaKeywords"] = $name;
		$data["metaDescription"] = $name . " araç parçaları";
		$data["pageTitle"] = $name . " araç parçaları";
		
		$data = json_encode($data);
		
		return $this->callApi("POST", "/api/categories", $data);
		
	}
	
	public function checkIfIdeaBrandExists(array $brands, $name) {
	
		$response = false;
		
		if(count($brands) > 0) {
		
			for($i = 0; $i < count($brands); $i++) {
				
				if($brands[$i]["name"] == $name) {
					$response = $brands[$i]["id"];
					break;
				}
			
			}
		
		}
		
		return $response;
	
	}

	public function checkIfIdeasoftUserExists($email) {

		$user = $this->callApi("GET", "/api/members?email=" . $email . "&sort=-id&limit=1");

		return count($user);

	}
	
	public function updateIdeaUser($old_email, $new_email) {

		$user = $this->callApi("GET", "/api/members?email=" . $old_email . "&sort=-id&limit=1");
		$user[0]["email"] = $new_email;
		
		$this->callApi("PUT", "/api/members/" . $user[0]["id"], json_encode($user[0]));
		
	}

	public function getIdeasoftUserId($email) {

		$user = $this->callApi("GET", "/api/members?email=" . $email . "&sort=-id&limit=1");

		return $user[0]["id"];

	}

	public function deleteIdeasoftUserById($id) {

		$this->callApi("DELETE", "/api/members/" . $id);

		return 1;

	}
	
	public function checkIfIdeaCategoryExists(array $categories, $name) {
	
		$response = false;
		
		if(count($categories) > 0) {
		
			for($i = 0; $i < count($categories); $i++) {
				
				if(mb_strtolower($categories[$i]["name"]) == mb_strtolower($name)) {
					$response = $categories[$i]["id"];
					break;
				}
			
			}
		
		}
		
		return $response;
	
	}
	
	public function checkIfIdeaProductExists(array $products, $sku) {
	
		$response = false;
		
		if(count($products) > 0) {
		
			for($i = 0; $i < count($products); $i++) {
				
				if($products[$i]["sku"] == $sku) {
					$response = $products[$i]["id"];
					break;
				}
			
			}
		
		}
		
		return $response;
	
	}
	
	public function checkForDelete($products, $xml) {
	
		$response = "";
		
		if(count($products) > 0) {
			
			foreach($xml->urunler->urun as $urun) {
			
				for($i = 0; $i < count($products); $i++) {
					
					$response = $products[$i];
					
					if(trim($products[$i]["sku"]) == trim((string)$urun->kod)) {
						$response = false;
						break;
					}
				
				}
				
				if($response !== false) {
				
					// $this->deleteIdeaProduct($response);

					$response["stockAmount"] = 0;
					$this->updateIdeaProduct($response);
					
				}
					
			}			
			
		}
		
		return $response;
	
	}
	
	public function getIdeaBrandId($name) {
		
		$brand = $this->callApi("GET", "/api/brands?name=" . $name);
		return $brand[0]["id"];
		
	}
	
	public function getIdeaCategoryId($name) {
		
		$category = $this->callApi("GET", "/api/categories?name=" . $name);
		return $category[0]["id"];
		
	}
	
	public function addIdeaProduct($name, $sku, $distributorcode, $price, $distributor, $brand, $stock, $currency = 3) {
		
		$data = array();
		$data["name"] = $name;
		$data["slug"] = $name;
		$data["fullName"] = $name;
		$data["sku"] = $sku;
		$data["barcode"] = $distributorcode;
		$data["price1"] = $price;
		$data["warranty"] = 24;
		$data["tax"] = 18;
		$data["stockAmount"] = $stock;
		$data["stockTypeLabel"] = "Piece";
		$data["status"] = 1;
		$data["marketPriceDetail"] = $distributor;
		$data["taxIncluded"] = 1;
		$data["distributor"] = $distributor;
		$data["metaKeywords"] = $name;
		$data["metaDescription"] = $name . " online sipariş";
		$data["pageTitle"] = $name;
		$data["hasOption"] = 0;
		$data["searchKeywords"] = $name;
		$data["brand"] = array();
		$data["brand"]["id"] = $brand;
		$data["currency"] = array();
		$data["currency"]["id"] = $currency;
		
		$data = json_encode($data);
		
		return $this->callApi("POST", "/api/products", $data);
		
	}
	
	public function addIdeaProductImage($url, $productid, $counter) {
		
		$data = array();
		
		$data["filename"] = time() . "_" . mt_rand(111111, 999999);
		$data["extension"] = "jpg";
		$data["sortOrder"] = $counter;
		$data["product"] = array();
		$data["product"]["id"] = $productid;
		
		$image = file_get_contents($url);
		$data["attachment"] = 'data:image/jpg;base64,' . base64_encode($image);
		
		$data = json_encode($data);
		
		return $this->callApi("POST", "/api/product_images", $data);
	
	}
	
	public function getIdeaProductId($name) {
		
		$product = $this->callApi("GET", "/api/products?name=" . $name);
		return $product[0]["id"];
		
	}
	
	public function getIdeaProductBySku($sku) {
		
		$product = $this->callApi("GET", "/api/products?sku=" . $sku);
		return $product[0];
		
	}
	
	public function updateIdeaProduct($product) {
	
		return $this->callApi("PUT", "/api/products/" . $product["id"], json_encode($product));
	
	}
	
	public function doFlush() {
		
		if (!headers_sent()) {
			
			ini_set('zlib.output_compression', 0);			
			header('Content-Encoding: none');
		}
		
		echo str_pad('', 4 * 1024);
		
		do {
			$flushed = @ob_end_flush();
		} while ($flushed);
		@ob_flush();
		flush();
		
	}
	
	public function checkForEmail($data) {

		$response = $this->callApi("GET", "/api/members?email=" . $data["email"]);
		
		if(count($response) == 0) {		
						
			$post = $this->callApi("POST", "/api/members", json_encode($data));			
			return $post;
		
		}
		else {
			return false;
		}
		
	}
	
	public function removeIdeaUserById($id) {
			
		$response = $this->callApi("DELETE", "/api/members/" . $id);
		return $response;
		
	}
	
	public function addIdeaAddress($data) {

		$address = $this->callApi("POST", "/api/member_addresses", json_encode($data));
		
		if(count($address) > 0) {
			return $address;
		}
		else {
			return false;
		}
		
	}
	
	public function updateIdeaCustomer($data, $district) {

		$customer = $this->callApi("GET", "/api/members?ids=" .  $_SESSION["id"]);
		
		$customer[0]["address"] = $data["address"];
		$customer[0]["district"] = $district;
		$customer[0]["country"]["id"] = 1;
		$customer[0]["location"]["id"] = $data["location"]["id"];

		$response = $this->callApi("PUT", "/api/members/" .  $_SESSION["id"], json_encode($customer[0]));
	
		if(count($response) > 0) {
			return true;
		}
		else {
			return false;
		}
		
	}
	
	public function updateIdeaCustomer2($phone, $phone2, $email, $company) {

		$customer = $this->callApi("GET", "/api/members?ids=" .  $_SESSION["id"]);
		
		$customer[0]["phoneNumber"] = $phone;
		$customer[0]["mobilePhoneNumber"] = $phone2;
		$customer[0]["commercialName"] = $company;
		$customer[0]["email"] = $email;

		$response = $this->callApi("PUT", "/api/members/" .  $_SESSION["id"], json_encode($customer[0]));
	
		if(count($response) > 0) {
			return true;
		}
		else {
			return false;
		}
		
	}
	
	public function updateIdeaAddress($aid, $subject, $firstname, $lastname, $phone, $province, $county, $address2, $phone2) {
	
		$address = $this->callApi("GET", "/api/member_addresses?ids=" .  $aid);
		
		$address[0]["name"] = $subject;
		$address[0]["firstname"] = $firstname;
		$address[0]["lastname"] = $lastname;
		$address[0]["phoneNumber"] = "+90 (" . substr($phone,1,3) . ") " . substr($phone, 4, 7);
		$address[0]["mobilePhoneNumber"] = "+90 (" . substr($phone2,1,3) . ") " . substr($phone2, 4, 7);
		$address[0]["address"] = $address2;
		$address[0]["location"]["id"] = $province;
		$address[0]["sublocation"]["id"] = $county;

		$response = $this->callApi("PUT", "/api/member_addresses/" .  $aid, json_encode($address[0]));
	
		if(count($response) > 0) {
			return true;
		}
		else {
			return false;
		}
	
	}
	
	public function removeIdeaAddress($aid) {
	
		return $this->callApi("DELETE", "/api/member_addresses/" .  $aid);
		
	}
	
	public function addOrder($data) {

		$data = json_encode($data);
		return $this->callApi("POST", "/api/orders", $data);
	
	}

	public function editOrder($data) {
		
		return $this->callApi("PUT", "/api/orders/" . $data["id"], json_encode($data));
	
	}

}
?>