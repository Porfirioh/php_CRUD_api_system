<?php 
if(empty($_GET['fill_db'])){
	header($app->error_descriptions[404]['header']);
	exit('Page not found');
}
$source_server 		= $app->config['ftp_server'];
$server_directory 	= $app->config['ftp_dir'];
$user 				= $app->config['ftp_username'];
$password 			= $app->config['ftp_pass'];

if ( $count = $app->fill_db($source_server, $server_directory, $user, $password) ){
	$message = $count . ' элементов успешно записано в базу данных';
	echo $message;
}else{
	echo 'Ошибка при сохранении элементов';
}


?>