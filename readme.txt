Данное приложение позволяет загрузить данные с указанного сервера в базу данных и предоставить полный доступ к базе данных (CRUD) через api систему

Если данный файл не отображается корректно смотрите документацию здесь https://docs.google.com/document/d/1yR2QGCueSqM5jmWeoyEc0VZAFO3aNI0gyiCWdcPe_cg/edit?usp=sharing

Тестовый экземпляр приложения находиться по адресу http://test3.mybitcode.ru с заполненной БД (примеры с id 1770-1810 точно есть)

Структура таблицы БД:
id (integer primary autoincrement)
purchase_id (integer)
number (integer)
url (text)

Структура приложения:
Все запросы перенаправляются на файл index.php в корневой папке приложения
В config.php в корневой директории находятся конфигурационные настройки приложения (название БД, имя пользователя, пароль, данные для ftp подключения и т.д. см. файл). 
Для корректной работы приложения сначала надо заполнить эти данные
Файлы приложения находятся в директории app:
	- model.php  - основной класс приложения
	- custom_PDO.php класс для работы  с БД
	- response.php - обработка api запроса и отправка ответа
	- fill_db.php - запуск заполнения базы данных из удаленного сервера 
Директория files предназначена для временного хранения файлов

Для загрузки данных вызывается метод model::fill_db с параметрами
	- ftp_source - название удаленного ftp сервера, 
	- ftp_source_dir - папка, где хранятся файлы на удаленном сервере, 
	- ftp_user - имя пользователя 
	- ftp_pass - пароль
ВОЗВРАЩАЕТ количество записанных элементов или false при ошибке
Метод вызывается параметром GET[fill_db] (http://yourdomain.com/?fill_db=1)


Методы api:
Методы api вызываются только методом POST.
Для всех методов обязательнымим параметрами являются:
	- token - авторизационный токен (по умолчанию: Qsft4511122??!lkm47522AASo45%%*ALa)
	- method - название метода
Спмсок методов api системы:

метод	описание		   обязатаельные параметры	  необязательные параметры 			возвращает
----------------------------------------------------------------------------------------------------------------------------------------
 get	возвращает данные объекта   1. id - id объекта в БД 	          нет		   JSON представление массива с данными объекта							
----------------------------------------------------------------------------------------------------------------------------------------
add	добавляет объект в БД	    1. data - массив сданными по          нет	      JSON представление массива c сообщением об успехе
			            следующей структуре: 				и с id добавленного объекта
				    data[purchase_id, number, url].
				    все свойства обязательны и 
				    отсутствие приведет к ошибке 
			             			  	
----------------------------------------------------------------------------------------------------------------------------------------
 edit	редактирует объект	    1. data - массив сданными по той      нет	      JSON представление массива c сообщением об успехе
 			   	    же структуре, что и при методе add
 				    но не все свойства обязательны			  	
	  	 		    2. id редактируемого объекта  		 				 			----------------------------------------------------------------------------------------------------------------------------------------
delete	удаляет объект из БД	    1. id - id удаляемого объекта	  нет         JSON представление массива c сообщением об успехе	
   					
----------------------------------------------------------------------------------------------------------------------------------------

Пример успешного запроса для редактирования объекта
<?php 
$params = array(
	'token' 	=> 'Qsft4511122??!lkm47522AASo45%%*ALa', 
	'method'	=> 'add',
	'data'		=> array(
		'purchase_id'			=> 777,
		'number'			=> 112255555,
		'url'				=> 'http://exampleurl.com',
	),
	'id'		=>  2011
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://test3.mybitcode.ru/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
$out = curl_exec($ch);
curl_close($ch);

echo '<pre>';
	print_r($out);
echo '</pre>';

?>

Запрос возвращает JSON представление:
{"success":{"code":200,"message":"Item hass succesfully edited"}}
------------------------------------------------------------------------------------------------------------------------
Пример запроса с ошибкой ( при add в data не указано свойство url )

<?php 
$params = array(
	'token' 	=> 'Qsft4511122??!lkm47522AASo45%%*ALa', 
	'method'	=> 'add',
	'data'		=> array(
		'purchase_id'			=> 777,
		'number'			=> 112255555	
	)
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://test3.mybitcode.ru/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
$out = curl_exec($ch);
curl_close($ch);

echo '<pre>';
	print_r($out);
echo '</pre>';

?>

Запрос возвращает JSON представление:
{"error":{"code":6,"message":"Cant add.One or more input parameters does not exist. See app documentation"}}
------------------------------------------------------------------------------------------------------------------------
Пример запроса без авторизационного ключа (token)
<?php 
$params = array(
	'method'	=> 'get',
	'id'		=> 2011
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://test3.mybitcode.ru/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
$out = curl_exec($ch);
curl_close($ch);

echo '<pre>';
	print_r($out);
echo '</pre>';

?>

Запрос возвращает JSON представление:
{"error":{"code":401,"message":"Authorization failed.Wrong or empty token."}}
	
