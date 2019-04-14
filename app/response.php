<?php 
/**
* request handling and response
*/

$table_name = $app->config['tablename'];

//validating request data
$error = $app->validate_request($app->request);

if(!empty($error)){
	$app->output_error($error['message'], $error['code'], $error['header']);
}else{
	//methods
	switch ($app->request['method']) {
		case 'add':
			if(empty($app->request['data']) || count($app->request['data']) == 0){
				$app->output_error('Empty data parameter. Add method needs data parameter. See app documentation', 4);
				exit();
			}

			$add_data = $app->validate_input_data($app->request['data']);

			if(empty($add_data['purchase_id']) || empty($add_data['number']) || empty($add_data['url'])){
				$app->output_error('Cant add.One or more input parameters does not exist. See app documentation', 6);
				exit();
			}

			if($added_id = $app->add($table_name, $add_data)){
				$success = array(
					'success' => array(
						'code' 		=> 200,
						'message'	=> 'Item (ID: ' . $added_id . ')hass succesfully added'
					)
				);
				$app->output($success);
			}else{
				$app->output_error('Databse error. Add method error. See app documentation or contact service provider', 5);
			}


			break;
		case 'get':

			$item = $app->get($table_name, $app->request['id']);
			if($item){
				$app->output($item);
			}else{
				$app->output_error('Item not found. Wrong id.', 404, $app->error_descriptions[404]['header']);
			}
			break;

		case 'edit':

			if(empty($app->request['data']) || count($app->request['data']) == 0){
				$app->output_error('Empty data parameter. Edit method needs data parameter. See app documentation', 4);
				exit();
			}

			if(!$app->get($table_name, $app->request['id'])){
				$app->output_error('Can\'t edit.Item not found', 404, $app->error_descriptions[404]['header']);
				exit();
			}

			$edit_data = $app->validate_input_data($app->request['data']);

			if( $app->edit($table_name, $app->request['id'], $edit_data)){
				$success = array(
					'success' => array(
						'code' 		=> 200,
						'message'	=> 'Item hass succesfully edited'
					)
				);
				$app->output($success);
			}else{
				$app->output_error('Databse error. Edit method error. See app documentation or contact service provider', 5);
			}

		break;
		case 'delete':
			if(!$app->get($table_name, $app->request['id'])){
				$app->output_error('Can\'t delete.Item not found', 404, $app->error_descriptions[404]['header']);
				exit();
			}
			if($app->delete($table_name, $app->request['id'])){
				$success = array(
					'success' => array(
						'code' 		=> 200,
						'message'	=> 'Item hass succesfully deleted'
					)
				);
				$app->output($success);
			}else{
				$app->output_error('Databse error. Delete method error. See app documentation or contact service provider', 5);
			}
		break;
		default:
			$app->output_error($app->error_descriptions[404]['message'], 404, $app->error_descriptions[404]['header']);
			break;
	}

}
?>