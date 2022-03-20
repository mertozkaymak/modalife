<?php
class user extends idea {
	
	public function __construct() {
		
		parent::connect();
		
	}
	
	public function __destruct() {
	
		parent::close();
	
	}

	public function getVariantImages($product_id) {

		$stmt = $this->db->prepare("SELECT parent_id FROM products WHERE iid = ?");
		$stmt->bind_param("i", $product_id);
		$stmt->execute();
		$result = $stmt->get_result();

		$row = $result->fetch_assoc();

		if($row["parent_id"] != 0) {
			$product_id = $row["parent_id"];
		}
		
		$stmt = $this->db->prepare("SELECT iid, image FROM products WHERE parent_id = ?");
		$stmt->bind_param("i", $product_id);
		$stmt->execute();

		$images = array();

		$result = $stmt->get_result();

		while($row = $result->fetch_assoc()) {
			if(!is_null($row["image"])) {
				$images[$row["iid"]] = rawurlencode($row["image"]);	
			}		
		}

		return $images;

	}

	public function addNewProduct($product) {

		$stmt = $this->db->prepare("SELECT iid FROM products WHERE iid = ?");
		$stmt->bind_param("i", $product["id"]);
		$stmt->execute();
		$result = $stmt->get_result();

		$brand = is_array($product["brand"]) ? $product["brand"]["name"] : NULL;

		if($result->num_rows == 0) {

			$stmt = $this->db->prepare("INSERT INTO products (iid, brand) VALUES (?,?)");
			$stmt->bind_param("is", $product["id"], $brand);
			$stmt->execute();

		}
		else {

			$stmt = $this->db->prepare("UPDATE products SET brand = ?, updated = 1 WHERE iid = ?");
			$stmt->bind_param("si", $brand, $product["id"]);
			$stmt->execute();

		}

	}

	public function prepareProductUpdate($value) {

		if($value == 0) {

			$stmt = $this->db->prepare("UPDATE products SET updated = 0");
			$stmt->execute();

		}
		else {
			$stmt = $this->db->prepare("DELETE FROM products WHERE updated = 0");
			$stmt->execute();
		}

	}

	public function saveOrder($orderInfo) {

		//Ürünü Ayırdıktan Sonra Veritabanına Yazma

		$stmt = $this->db->prepare("SELECT id FROM orders WHERE order_id = ?");
		$stmt->bind_param("i", $orderInfo["id"]);
		$stmt->execute();
		$result = $stmt->get_result();

		if($result->num_rows == 0){

			$stmt = $this->db->prepare("INSERT INTO orders(order_id, orderItem_id, status) VALUES(?, ?, ?)");
			$stmt->bind_param("iis", $orderInfo["id"], $orderInfo["orderItems"][0]["product"]["id"], $orderInfo["status"]);
			$stmt->execute();

		}

	}

	public function splitOrder($order) {
		
		$currencies = json_decode($order["currencyRates"], true);
		$shippingAmount = (float)$order["shippingAmount"];
		$couponDiscount = (float)$order["couponDiscount"];
		
		if(count($order["orderItems"]) > 1) {

			$ids = array();

			foreach($order["orderItems"] as $orderItem) {

				if(!isset($ids[$orderItem["product"]["id"]])) {
					$ids[$orderItem["product"]["id"]] = array();
				}

				array_push($ids[$orderItem["product"]["id"]], $orderItem["product"]["id"]);

			}

			if(count($ids) > 1) {

				$orders = array();

				foreach($ids as $key => $id) {
					$orders[$key] = $order;
					$orders[$key]["shippingAmount"] = $shippingAmount == 0 ? 0 : round($shippingAmount / count($ids),5);
					// $orders[$key]["status"] = "approved";
				}

				$cr = 0;

				foreach($orders as $key => $o) {

					foreach($o["orderDetails"] as $key3 => $oDetail) {

						if(isset($oDetail["id"]) && $cr != 0) {
							unset($oDetail["id"]);
						}

					}

					foreach($o["orderItems"] as $key2 => $oItem) {

						if(!in_array($oItem["product"]["id"], $ids[$key])) {
							unset($orders[$key]["orderItems"][$key2]);
						}

					}

					$orders[$key]["orderItems"] = array_values($orders[$key]["orderItems"]);

					$cr++;

				}

				foreach($orders as $key => $o) {

					$amount = 0;
					$tax = 0;
					$promotionDiscount = 0;
					$couponDiscount = 0;

					foreach($o["orderItems"] as $key2 => $oItem) {
						$amount += round(($oItem["productPrice"] - $oItem["productDiscount"]) * $currencies[$oItem["productCurrency"]][1] * $oItem["productQuantity"], 5);
						$tax += round(($oItem["productPrice"] - $oItem["productDiscount"]) * $currencies[$oItem["productCurrency"]][1] * $oItem["productQuantity"] * ($oItem["productTax"] / 100), 5);
						$promotionDiscount += $oItem["isProductPromotioned"] == 0 ? 0 : round(round($oItem["discount"],3) * (1 + $oItem["productTax"] / 100) * $oItem["productQuantity"],3);
						$couponDiscount += $oItem["isProductPromotioned"] == 0 && $oItem["discount"] > 0 ? $oItem["discount"] * $oItem["productQuantity"] : 0;
					}

					$orders[$key]["amount"] = $amount;
					$orders[$key]["taxAmount"] = $tax;
					$orders[$key]["generalAmount"] = $amount + $tax - $promotionDiscount;
					$orders[$key]["finalAmount"] = $orders[$key]["generalAmount"] + $orders[$key]["shippingAmount"];
					$orders[$key]["promotionDiscount"] = $promotionDiscount;
					$orders[$key]["couponDiscount"] = $couponDiscount;
					
					if($couponDiscount > 0) {
						$orders[$key]["taxAmount"] = round(($orders[$key]["amount"] + $orders[$key]["taxAmount"]) / $orders[$key]["amount"] * ($orders[$key]["amount"] - $orders[$key]["couponDiscount"]) - $orders[$key]["amount"] + $orders[$key]["couponDiscount"], 5);
					}

				}

				$counter = 1;

				foreach($orders as $key => $o) {
	
					$o["transactionId"] = $o["transactionId"] . "-" . $counter;	

					if($counter == 1) {
						// $o["status"] = "waiting_for_approval";
						$response = $this->editOrder($o);
						$this->saveOrder($o);
						sleep(1);
					}
					else {
						unset($o["id"]);
						unset($o["billingAddress"]["id"]);
						unset($o["shippingAddress"]["id"]);
						$response = $this->addOrder($o);
						$this->saveOrder($response);
						sleep(1);
						if(isset($response["id"])) {
							// $response["status"] = "waiting_for_approval";
							$response = $this->editOrder($response);
							$this->saveOrder($response);
						}
					}

					$counter++;

				}
				
			}
			else {
				// $order["status"] = "waiting_for_approval";
				$response = $this->editOrder($order);
				$this->saveOrder($order);
				sleep(1);
			}

		}
		else {
			// $order["status"] = "waiting_for_approval";
			$response = $this->editOrder($order);
			$this->saveOrder($order);
			sleep(1);
		}
		
	}

	public function checkOrderStatus($order_id){

		$response = $this->getIdeaOrderById($order_id);

		$response = $response[0]["status"];
		return $response;

	}

	public function updateOrderStatus($order_id, $status) {

		$response = $this->getIdeaOrderById($order_id);
		$response[0]["status"] = $status;

		$this->updateIdeaOrderById($response[0]);

	}

	public function sendEmail($email, $name, $body, $attachment = null) {
			
		try {
			$mail = new PHPMailer();
			$mail->IsSMTP();

			$mail->SMTPAuth = true;
			$mail->Host = 'mail.kampanyum.com';
			$mail->Port = 587;
			$mail->Username = 'noreply@kampanyum.com';
			$mail->Password = '559c0a3f15e47aec0537c90bc4bec5f0';

			$mail->SetFrom("noreply@kampanyum.com", 'Digital Fikirler - Servis');
			$mail->AddAddress($email, $name);
			$mail->CharSet = 'UTF-8';
			$mail->Subject = 'Digital Fikirler - Servis';
			$mail->IsHTML(true);
			$mail->MsgHTML($body);
	
			if(is_array($attachment)) {
				
				for($i = 0; $i < count($attachment); $i++) {
					
					$mail->AddAttachment($attachment[$i]["path"], $attachment[$i]["name"], 'base64', $attachment[$i]["type"]);
	
				}
	
			}
	
			if($mail->Send()) {
				
				return 1;
						
			} 
			else {
						
				return 0;
						
			}
		
		}
		catch (phpmailerException $e) {
			
		  return $e->errorMessage();
		  
		} 
		catch (Exception $e) {
			
		  return $e->getMessage(); 
		  
		}
		
	}

}