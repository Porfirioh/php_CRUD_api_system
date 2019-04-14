<?php 
/**
* finconsult test task main application class
*/

class model {
	private $db_name;										// database name
	private $db_username;									// database access username
	private $db_password;									// database access password
	public  $token = 'Qsft4511122??!lkm47522AASo45%%*ALa'; 	// authentication token
	public 	$dbh;											// database handle
	public 	$db_tablename; 									// database table name
	public 	$request; 										// array with request data
	public  $response;
	public  $api_methods;									// list of api methods
	public  $error_descriptions = array(); 					// request errors
	public 	$table;											// database structure (available fields)
	public 	$config;

	function __construct($db_name, $db_username, $db_password){
		$this->db_name 			= $db_name; 
		$this->db_username 		= $db_username;
		$this->db_password 		= $db_password;

		//Setting connection to database
		$this->db_connect();

		//setting current api methods
		$this->set_api_methods();

		//collecting and sanityzing request data
		$this->set_request_data();

		//errors
		$this->set_error_descriptions();

		//set db structure
		$this->set_db_table_structure();

		//set application cinfigurations
		$this->set_config();
	}

	function __destruct(){
		$this->db_disconnect();
	}

	/**
	* sets configuration
	*/
	private function set_config(){
		if(!is_file(ROOT_DIR . '/config.php'))exit('Missing configuration file');
		global $config;
		$this->config = $config;
	}

	/**
	* listing current api methods names
	*/
	private function set_api_methods(){
		$this->api_methods = array(
			'add', 		//create item
			'get',		//get item data
			'edit', 	//edit item
			'delete'	//delete item
		);
	}

	/**
	* collecting request data in $this->request
	*/
	private function set_request_data(){

		if(!empty($_POST) && count($_POST) > 0){
			foreach ($_POST as $key => &$value) {
				if(!is_array($value)){
					$this->request[$key] = strip_tags($value);
					continue;
				}else{
					foreach ($value as $key1 => $value1) {
						if(!is_array($value1))$this->request[$key][$key1] = strip_tags($value1);
					}
				}
			}
		}
	}

	/**
	* setting error messages codes and headers
	*/
	private function set_error_descriptions(){
		$this->error_descriptions = array(
			401 => array(
				'message' 	=> 'Authorization failed.Wrong or empty token.', 
				'header' 	=> 'HTTP/1.1 401 Unauthorized'
			),
			404 => array(
				'message' 	=> 'Unknown method. See app documentation for current method list.',
				'header'  	=> 'HTTP/1.0 404 Not Found' 
			)
		);
	}

	/**
	* listing database available fields
	*/
	private function set_db_table_structure(){
		$this->table = array(
			'purchase_id',
			'number',
			'url'
		);
	}

	/**
	* create database connection
	*/
	private function db_connect(){
		$dsn = 'mysql:host=localhost;dbname=' . $this->db_name;
		$this->dbh = new custom_PDO($dsn, $this->db_username, $this->db_password);
	}

	/**
	* close database connection
	*/
	public function db_disconnect(){
		$this->dbh = null;
	}

	/**
	* output data in JSON
	* @param mixed $data data to be output
	* @param string header config
	*/
	public function output($data, $header = 'Content-Type: application/json'){
		header($header);
		echo json_encode($data);
	}

	/**
	* output error code and message in JSON
	*/
	public function output_error($error_text, $error_code, $header = ''){
		$this->output(
			array(
				'error'=>array(
					'code'=>$error_code, 
					'message' => $error_text
				)
			),
			$header
		);
	}

	/**
	* validation of request
	*/
	public function validate_request($request){
		$error = '';
		if(empty($request['token']) || $request['token'] != $this->token){
			$error = array(
				'code' 		=> 401,
				'message'	=> $this->error_descriptions[401]['message'],
				'header'	=> $this->error_descriptions[401]['header']
			);
		}else if(empty($request['method']) || !in_array($request['method'], $this->api_methods)){
			$error = array(
				'code' 		=> 404,
				'message'	=> $this->error_descriptions[404]['message'],
				'header'	=> $this->error_descriptions[404]['header']
			);
		}else if($request['method'] == 'get' || $request['method'] == 'edit' || $request['method'] == 'delete'){
			if(empty($request['id'])){
				$error = array(
					'code' 		=> 404,
					'message'	=> 'Item not found or empty ID. Method ' . $request['method'] . ' requires correct item ID',
					'header'	=> $this->error_descriptions[404]['header']
				);
			}
		}else{
			$error = '';
		}
		return $error;
	}


	/**
	* Create database item
	* @param string $tablename database table name
	* @param array $data array with data (structure: key=>db col name value=> col value)
	* @return integer last insert row id or falsee if there is an error  
	*/
	public function add($table_name = '', $data){
		if(empty($table_name) || !is_array($data))return false;
        $into = '';
        $values = '';
        $i = 0;//counter
        foreach($data as $key => $value){
            if(count($data) == 1 || $i == (count($data) - 1)){
                $into .= '`' . $key . '` ';
                $values .= $this->dbh->escape($value) . ' ';
            }else{
                $into .= '`' . $key . '`, ';
                $values .= $this->dbh->escape($value) . ', ';
            }
            $i++;
        }
        $sql = "INSERT INTO `" . $table_name . "`(" . $into . ") VALUES (" . $values . ")";
        $d = $this->dbh->exec($sql);
        if($d){
        	$id = $this->dbh->lastInsertId();
            return $this->dbh->lastInsertId();
        }else{
            return false;
        }
	}


	/**
	* get item from database
	* @param string $tablename database table name
	* @param string $where_param db col name
	* @param string $where_value select value
	* @param string $where_operator matching operator in where condition
	* @return array with item data false if there is an error  
	*/
	public function get($table_name = '', $where_value = '', $where_param = 'id', $where_operator = '='){
		if(empty($table_name) || empty($where_param) || empty($where_value) || empty($where_operator) )return false;
		$where = ' WHERE `' . $where_param . '` ' . $where_operator . ' ' . $this->dbh->escape($where_value) . ' ';
		$sql = 'SELECT * FROM `' . $table_name . '` ' . $where . ' ORDER BY `id` DESC';
		$item = $this->dbh->row($sql);
		return $item;
	}

	/**
	* edit item 
	* @param string $tablename database table name
	* @param string $where_param db col name
	* @param string $where_value select value
	* @param array  $data editing data (structure: keyy=>db col name, value=> col value)
	* @param string $where_operator matching operator in where condition
	* @return bool true/false  success/error 
	*/
	public function edit($table_name = '', $where_value = '', $data, $where_param = 'id', $where_operator = '='){
		if(empty($table_name) || empty($where_param) || empty($where_value) || empty($where_operator) || !is_array($data) || count($data) == 0)return false;
		$where = ' WHERE `' . $where_param . '` ' . $where_operator . ' ' . $where_value . '';
        $set = '';
        $i = 0;
        foreach($data as $key => $value){
            if(count($data) == 1 || $i == (count($data) - 1)){
                $set .=  $key . ' = '  . $this->dbh->escape($value) ;
            }else{
                $set .=  $key . ' = '  . $this->dbh->escape($value) . ', ';
            }
            $i++;
        }
        $sql = 'UPDATE ' . $table_name . ' SET ' . $set . ' ' . $where ;
        return $this->dbh->exec($sql);
	}

	/**
	* deleting item by id
	* @param string $tablename database table name
	* @param integer $id  database item id
	* @return bool true/false 
	*/
	public function delete($table_name = '', $id = 0){
		if(empty($table_name) || empty($id) || !is_numeric($id)) return false;
		$sql = 'DELETE FROM `' . $table_name . '` WHERE `id` = ' . $this->dbh->escape($id);
		return $this->dbh->exec($sql);
	}

	/**
	* Validate data from requeat
	* @param array $data input data
	* @return array checked data or array with error description 
	*/

	public function validate_input_data($data){
		$error = '';
		if(empty($data) || !is_array($data) || count($data) == 0)return array();
		foreach ($data as $key => $value) {
			$value = strip_tags($value);
			if(!$this->validate_field($key)){
				$this->output_error('There is no such field in table: ' . $key, 8);
				exit();
			}
		}
		return $data;
	}
	/**
	* check if there is such property in database
	* @param string $field_name database fileld name to be checked
	*/
	private function validate_field($field_name = ''){
		return in_array($field_name, $this->table);
	}


	/**
	* filles db with information from source
	* @param string $ftp_source  - ftp server name
	* @param string $ftp_source_dir - directory on server where files are located
	* @param string $ftp_user ftp server access username
	* @param string $ftp_pass ftp server access password
	* @return integer number of inserted items or false if there is error
	*/

	public function fill_db($ftp_source, $ftp_source_dir, $ftp_user, $ftp_pass){

		//temporary dir for files
		$dir = ROOT_DIR . '/files/' . date('Y_m_d');
		if(!is_dir($dir))mkdir($dir);

		$zip = new ZipArchive;

		// connecting to server where files are located
		$ftp= ftp_connect($ftp_source);
		$r = ftp_login($ftp, $ftp_user, $ftp_pass);

		//list of files on server
		$contents = ftp_nlist($ftp, $ftp_source_dir);

		$items = array();//here we collect all items
		
		foreach ($contents as $key => $file) {

			$local_filename = $dir . '/' . 'file_' . rand() . '.zip';
			$server_filename = $file;

			//saving file, listing all xml files in archive, collecting information from files
			if(ftp_get($ftp, $local_filename,  $server_filename, FTP_BINARY)){
				
				//opening archive
				$zip->open($local_filename);

				// files in archive
				for ($i = 0;  $i < $zip->numFiles; $i++) {

					$xml_file =  $zip->getNameIndex($i);

					//we collect info only from xml files
					if(pathinfo($xml_file, PATHINFO_EXTENSION) != 'xml')continue;

					if($xml_file){
						$xml_string = @file_get_contents('zip://' . $local_filename .'#' . $xml_file);
					}else{
						continue;
					}

					if(empty($xml_string))continue;

					$xml = simplexml_load_string($xml_string);

					$data = json_decode(json_encode($xml->xpath('//ns2:*')));	
	
					$data = !empty($data[1])?$data[1]:array();
					$item = array(
						'purchase_id' 		=> (!empty($data->id)?$data->id:''),
						'number' 			=> (!empty($data->purchaseNumber)?$data->purchaseNumber:''),
						'url' 				=> (!empty($data->href)?$data->href:''),

					);

					//skip item if it fully empty
					if(empty($item['purchase_id']) && empty($item['number']) && empty($item['url'])) continue;

					$items[] = $item;
				}
				
			}else{
				echo $server_filename . ' unable to save' . "\r\n";
			}
		}

		//deleting files 
		$files = glob($dir . '/*'); 
		foreach($files as $file){ 
		  if(is_file($file))
		    unlink($file); 
		}

		//saving items to db
		foreach ($items as $key => $item) {
			$result = $this->add($this->config['tablename'], $item);
		}

		if($result){
			return count($items);
		}else{
			return false;
		}

	}

}

?>