<?PHP
createInvoice();

function createInvoice(){
	global $postData;
	
	if(checkAppID($postData["appID"], $postData["appKey"]) == 0){
		response(false, null, "Authorization failed", __FILE__, $postData);
		exit;
	}

	if(!isset($postData["companyID"])
		|| !isset($postData["forCompanyName"])
		|| !isset($postData["issueDate"])
		|| !isset($postData["summaryCharge"])
		|| !isset($postData["products"])
		|| !isset($postData["products"])
		|| count($postData["products"]) == 0
		){
			response(false, null, "Incorrect invoice data", __FILE__, $postData);
			exit;
		}

	foreach($postData["products"] as $product){
		if(!isset($product["description"])
			|| !isset($product["quantity"])
			|| !isset($product["unitPrice"])
			|| !isset($product["productPrice"])
			){
				response(false, null, "Incorrect product data", __FILE__, $postData);
				exit;
			}
	}



	try{
		$query = "lock tables invoices write";
		$sthmnt = get_DBC()->prepare($query);
		$sthmnt->execute();
	}
	catch (Exception $e) {
		error_log(__FILE__.":".__LINE__." - Caught exception: ". $e->getMessage());
		response(false, null, $e->getMessage(), __FILE__, $rsvd=$postData);
		exit;
	}




	$invNumber = 0;
	try{
		$query = "SELECT (IFNULL(MAX(invoiceNumber), 0)+1) as invNumber FROM invoices";
		$sthmnt = get_DBC()->prepare($query);
		$sthmnt->execute();
		$row = $sthmnt->fetch(PDO::FETCH_ASSOC);
		$sthmnt->closeCursor();
		$invNumber = $row["invNumber"];
	}
	catch (Exception $e) {
		error_log(__FILE__.":".__LINE__." - Caught exception: ". $e->getMessage());
		response(false, null, $e->getMessage(), __FILE__, $rsvd=$postData);
		exit;
	}

	if($invNumber == 0){
		response(false, null, "Failed to generate invoice number", __FILE__, $rsvd=$postData);
		exit;
	}

	try{
		$query = "INSERT INTO invoices (invoiceNumber, createDate, companyID, forCompanyName, issueDate, dueDate, summaryCharge, createdBy, fromIP)
						VALUES (:invoiceNumber, NOW(), :companyID, :forCompanyName, :issueDate, :dueDate, :summaryCharge, :createdBy, :fromIP)";
		$sthmnt = get_DBC()->prepare($query);
		$sthmnt->execute(array(':invoiceNumber' => $invNumber, ':companyID' => $postData["companyID"], ':forCompanyName' => $postData["forCompanyName"], ':issueDate' => $postData["issueDate"], ':dueDate' => $postData["dueDate"], ':summaryCharge' => $postData["summaryCharge"], ':createdBy' => $postData["appID"], ':fromIP' => $_SERVER["REMOTE_ADDR"]));
		$invRecId = get_DBC()->lastInsertId();
		$sthmnt->closeCursor();
	}
	catch (Exception $e) {
		error_log(__FILE__.":".__LINE__." - Caught exception: ". $e->getMessage());
		response(false, null, $e->getMessage(), __FILE__, $rsvd=$postData);
		exit;
	}
	
	
	try{
		$query = "unlock tables";
		$sthmnt = get_DBC()->prepare($query);
		$sthmnt->execute();
	}
	catch (Exception $e) {
		error_log(__FILE__.":".__LINE__." - Caught exception: ". $e->getMessage());
	}
	
	reset($postData["products"]);
	
	foreach($postData["products"] as $product){
		try{
			$query = "INSERT INTO products (invoiceRecId, productDescription, quantity, unitPrice, productPrice)
							VALUES (:invoiceRecId, :productDescription, :quantity, :unitPrice, :productPrice)";
			$sthmnt = get_DBC()->prepare($query);
			$sthmnt->execute(array(':invoiceRecId' => $invRecId, ':productDescription' => $product["description"], ':quantity' => $product["quantity"], ':unitPrice' => $product["unitPrice"], ':productPrice' => $product["productPrice"]));
			$sthmnt->closeCursor();
		}
		catch (Exception $e) {
			error_log(__FILE__.":".__LINE__." - Caught exception: ". $e->getMessage());
			response(false, null, $e->getMessage(), __FILE__, $rsvd=$postData);
			exit;
		}
	}
	
	response(true, array("invoiceRecordID" => $invRecId));
	
}


?>