<?PHP
getInvoice();


function getInvoice() {
	global $postData;
	
	$respData = null;
	$fileHashExpDays = 3;
	
	if(checkAppID($postData["appID"], $postData["appKey"]) == 0){
		response(false, null, "Authorization failed", __FILE__, $postData);
		exit;
	}
	
	if(!isset($postData["invoiceID"])){
		response(false, null, "Incorrect invoice data", __FILE__, $postData);
		exit;
	}
	
	$fileHashKey = hash("sha256", $postData["invoiceID"]."|".time()."|".rand()."|".guidv4()."|"."3i5H1#f9!TzB", false);
	
	
	try {
		$query = "UPDATE invoices SET fileHash = '".$fileHashKey."', fileHashExpDate = DATE_ADD(NOW(), INTERVAL ".$fileHashExpDays." DAY) WHERE rec_id = :rec_id";
	
		$sthmnt = get_DBC()->prepare($query);
		$sthmnt->execute(array(':rec_id' => $postData["invoiceID"]));
		$sthmnt->closeCursor();
	}
	catch (Exception $e) {
		error_log(__FILE__.":".__LINE__." - Caught exception: ". $e->getMessage());
		response(false, null, $e->getMessage(), __FILE__, $rsvd=$postData);
		exit;
	}
	
	try {
		$query = "SELECT invoiceNumber,createDate,companyID,forCompanyName,issueDate,dueDate,summaryCharge,payd,payID,payDate,fileHash FROM invoices WHERE rec_id = :rec_id";
		
		$sthmnt = get_DBC()->prepare($query);
		$sthmnt->execute(array(':rec_id' => $postData["invoiceID"]));
		$row = $sthmnt->fetch(PDO::FETCH_ASSOC);
		if(!$row) {
			$respData = "Not found";
		}
		else {
			$respData = $row;
			$respData["fileHashExpDays"] = $fileHashExpDays;
			$respData["products"] = array();
		}
		
		$sthmnt->closeCursor();
	}
	catch (Exception $e) {
		error_log(__FILE__.":".__LINE__." - Caught exception: ". $e->getMessage());
		response(false, null, $e->getMessage(), __FILE__, $rsvd=$postData);
		exit;
	}
	
	if($respData != null) {
		try {
			$query = "SELECT productDescription,quantity,unitPrice,productPrice FROM products WHERE invoiceRecId = :rec_id";
			
			$sthmnt = get_DBC()->prepare($query);
			$sthmnt->execute(array(':rec_id' => $postData["invoiceID"]));
			while($row = $sthmnt->fetch(PDO::FETCH_ASSOC)) {
				array_push($respData["products"], $row);
			}
			$sthmnt->closeCursor();
		}
		catch (Exception $e) {
			error_log(__FILE__.":".__LINE__." - Caught exception: ". $e->getMessage());
			response(false, null, $e->getMessage(), __FILE__, $rsvd=$postData);
			exit;
		}
	}
	
	
	
	response(true, $respData);
	
}
?>