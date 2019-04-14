<?php 
/**
* Fincosult REST api
* test application for employment
* adds information from source (xml) to database and provides full access to database (CRUD) via its API system
* @author Zarzand Mkhitaryan zmkhitaryan88@gmail.com https://mkhitaryan-web.pro https://github.com/zarzandmkh
*/
error_reporting(E_ALL);
mb_internal_encoding('utf-8');
define('ROOT_DIR', dirname(__FILE__));

include ROOT_DIR . '/config.php';

spl_autoload_register(function ($class_name) {
	$file = ROOT_DIR . '/app/' . $class_name . '.php';
    if(is_file($file))include $file;
});

function debug($data){
	print_r($data);
	echo "\r\n\r\n";
}
function debug_html($data){
	echo'<pre>';
	print_r($data);
	echo'</pre>';
	
}

$app = new model($config['dbname'], $config['dbusername'], $config['dbpass']);
if(!empty($_GET['fill_db'])){
	include ROOT_DIR . '/app/fill_db.php';
}else{
	include ROOT_DIR . '/app/response.php';
}

$app->db_disconnect();
$app = null;
?>

	
