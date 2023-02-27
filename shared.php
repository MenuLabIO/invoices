<?PHP
require_once("config.php");
//------------------------------------------------------------------------------------------------------------------------
function response($success, $data, $errormessage=null, $src=null, $rsvd=null) {
	$dbc = null;
	$resultObj = array("success" => $success);
	if($data != null)$resultObj["data"] = $data;
	if($errormessage != null)$resultObj["errorMessage"] = $errormessage;
	if($src != null)$resultObj["src"] = $src;
	//if($rsvd != null)$resultObj["rsvd"] = $rsvd;
	header('Content-Type: application/json');
	$resp = json_encode($resultObj);
	logResponse($resp);
	echo $resp;
}
//------------------------------------------------------------------------------------------------------------------------
function logRequest($requestData, $methodName=null)
{
	try{
		$query = "INSERT INTO `request_log` (methodName, reqDate, requset, fromIP) VALUES (:methodName, NOW(), :requset, :fromIP)";
		$sthmnt = get_DBC()->prepare($query);
		$sthmnt->execute(array(':methodName' => $methodName,':requset' => $requestData, ':fromIP' => $_SERVER["REMOTE_ADDR"]));
		$rec_id = get_DBC()->lastInsertId();
		$sthmnt->closeCursor();
		return $rec_id;
	}
	catch (Exception $e) {
		error_log(__FILE__.":".__LINE__." - Caught exception: ". $e->getMessage());
		return 0;
	}
}
//------------------------------------------------------------------------------------------------------------------------
function logResponse($responseData)
{
	global $logRecId;
	try{
		$query = "UPDATE `request_log` SET respDate = NOW(), response = :response WHERE rec_id = :rec_id";
		$sthmnt = get_DBC()->prepare($query);
		$sthmnt->execute(array(':response' => $responseData, ':rec_id' => $logRecId));
		$sthmnt->closeCursor();
	}
	catch (Exception $e) {
		error_log(__FILE__.":".__LINE__." - Caught exception: ". $e->getMessage());
	}
	return;
}
//------------------------------------------------------------------------------------------------------------------------
function checkAppID($appID, $appKey){
	global $postData, $logRecId;
	try{
		$query = "SELECT COUNT(*) AS c FROM appSettings WHERE paramName = 'appID' AND param1Value = :appID AND param2Value = :appKey";
		$sthmnt = get_DBC()->prepare($query);
		$sthmnt->execute(array(':appID' => $appID, ':appKey' => $appKey));
		$row = $sthmnt->fetch(PDO::FETCH_ASSOC);
		$sthmnt->closeCursor();

		return $row["c"];
	}
	catch (Exception $e) {
		error_log(__FILE__.":".__LINE__." - Caught exception: ". $e->getMessage());
		response(false, null, $e->getMessage(), __FILE__, $rsvd=$postData);
		exit;
	}
}
//------------------------------------------------------------------------------------------------------------------------
function guidv4($data = null) {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
//------------------------------------------------------------------------------------------------------------------------
?>