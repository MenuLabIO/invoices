<?PHP
require 'vendor/autoload.php';
define("DOMPDF_UNICODE_ENABLED", true);
use Dompdf\Dompdf;

getPDF();


function getPDF() {

	$respData = null;
	$html = file_get_contents("./templates/invoice_template_2.htm");
	$htmlProductRow = file_get_contents("./templates/invoice_template_2_product_row.htm");
	
	//  <[|billingyear|]>
	$html = str_replace("<[|billingmonth|]>", date("F"), $html);
	$html = str_replace("<[|billingyear|]>", date("Y"), $html);
	
	try {
		$query = "SELECT invoiceNumber,createDate,companyID,forCompanyName,issueDate,dueDate,summaryCharge,payd,payID,payDate,fileHash
					FROM invoices WHERE rec_id = :rec_id AND fileHash = :fileHash"; //  AND fileHashExpDate >= NOW()
		
		$sthmnt = get_DBC()->prepare($query);
		$sthmnt->execute(array(':rec_id' => $_GET["inv"], ':fileHash' => $_GET["key"]));
		$row = $sthmnt->fetch(PDO::FETCH_ASSOC);
		if($row) {
			foreach($row as $key => $val) {
				$html = str_replace("<[|".$key."|]>", $val, $html);
			}
			$respData = $row;
			$respData["products"] = array();
		}
		
		
		$sthmnt->closeCursor();
	}
	catch (Exception $e) {
		error_log(__FILE__.":".__LINE__." - Caught exception: ". $e->getMessage());
		response(false, null, $e->getMessage(), __FILE__);
		exit;
	}
	
	if($respData != null) {
		try {
			$query = "SELECT productDescription,quantity,unitPrice,productPrice FROM products WHERE invoiceRecId = :rec_id";
			
			$sthmnt = get_DBC()->prepare($query);
			$sthmnt->execute(array(':rec_id' => $_GET["inv"]));
			$productsHTML = "";
			$bg = 1;
			$prCount = 0;
			while($row = $sthmnt->fetch(PDO::FETCH_ASSOC)) {
				$prCount++;
				$tmp = $htmlProductRow;
				foreach($row as $key => $val) {
					$tmp = str_replace("<[|".$key."|]>", $val, $tmp);
				}
				$productsHTML .= "<tr class=\"products_row_tr_".$bg."\">".$tmp."</tr>";
				if($bg == 1)$bg = 2; else $bg = 1;
				//$productsHTML .= "<tr><td>".$row["productDescription"]."</td><td>".$row["quantity"]."</td><td>$".$row["unitPrice"]."</td><td>$".$row["productPrice"]."</td></tr>";
				array_push($respData["products"], $row);
			}
			$html = str_replace("<[|productlisttrs|]>", $productsHTML, $html);
			$productsCount = $prCount." Item".(($prCount > 1)?"s":"");
			$html = str_replace("<[|productsCount|]>", $productsCount, $html);
			// 
			$sthmnt->closeCursor();
		}
		catch (Exception $e) {
			error_log(__FILE__.":".__LINE__." - Caught exception: ". $e->getMessage());
			response(false, null, $e->getMessage(), __FILE__);
			exit;
		}
	}
	
	//response(true, $respData);
	
	
	//echo $html; exit;
	
	$dompdf = new Dompdf();
	$dompdf->loadHtml($html);
	$dompdf->render();


	$fileName = "MenulabIO_inv_".$_GET["inv"]."_".date("Ymdhis").".pdf";
	$dompdf->stream($fileName, array("Attachment" => false));
}
?>