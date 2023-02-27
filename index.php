<?PHP
$methods = array("create", "get", "getpdf");
if(!isset($_GET["route"]) || !in_array(rtrim($_GET["route"], '//'), $methods)){echo "invoice.menulab.io"; return;}

require_once("config.php");
require_once("shared.php");




$inputJSON = file_get_contents('php://input');
$postData = json_decode($inputJSON, true); // global

$get = $_GET;
unset($get["route"]);
$inputJSON .= json_encode($get);

$logRecId = logRequest($inputJSON, $_GET["route"]); // global



switch(rtrim($_GET["route"], '//')){
	case "create": case "create/": require("create.php"); break;
	case "get": require("get.php"); break;
	case "getpdf": require("getpdf.php"); break;
}



//response(true, null, null, __FILE__, $postData);
?>