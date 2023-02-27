<?PHP
require_once("shared.php");

$config = parse_ini_file('/var/www/menulab.io/webconfig/invoice.menulab.io.ini');

$dsn = 'mysql:dbname='.get_config_value("dbName").';host='.get_config_value("dbHost");
$user = get_config_value("dbUser");
$password = get_config_value("dbPass");

try{
//error_log("DSN: ".$dsn." USER: ".$user." PASS: ".$password);

	$dbc = new PDO($dsn, $user, $password);	
}
catch (Exception $e) {
	response(false, null, $e->getMessage()."[".$dsn."][".$user."][".$password."]", __FILE__, null);
	exit;
}


function get_DBC(){global $dbc; return $dbc;}

function get_config_value($configName)
{
	global $config;
	if(isset($config[$configName]))return trim(explode("#", $config[$configName])[0]);
	else return null;
}



?>