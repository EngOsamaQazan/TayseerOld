<?php

date_default_timezone_set("Asia/Amman");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require('db.php');
$db_host = 'localhost';
$db_name = 'namaa_' . $_REQUEST['db'];
$db_user = 'osama';
$db_pass = 'O$amaDaTaBase@123';

$db = new smplPDO( "mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass );


header('Content-Type: application/json');

if (!isset($_REQUEST['token'])) {
  $array = ['error'=>'no token value'];
  $array = json_encode($array);
  echo $array;
  exit();
}

if ($_REQUEST['token'] == 'b83ba7a49b72') {
} else {

  $array = ['error'=>'not authorized'];
  $array = json_encode($array);
  echo $array;
  exit();
}

if (!isset($_REQUEST['search'])) {
  $array = ['error'=>'no client name value'];
  $array = json_encode($array);
  echo $array;
  exit();
}


$result = $db->run("SELECT * FROM os_customers WHERE name LIKE '%".$_REQUEST['search']."%' OR id_number LIKE '%".$_REQUEST['search']."%' OR primary_phone_number LIKE '%".$_REQUEST['search']."%' LIMIT 100")->fetchAll();

$array = [];

for ($i = 0; $i < count($result); ++$i) {
  
  if ($_REQUEST['db'] == 'jadal') {
  	$array[$i]['account'] = 'جدل';
  } else {
  	$array[$i]['account'] = 'نماء';
  }
  

  $contacts_ids = $db->run("SELECT GROUP_CONCAT(contract_id SEPARATOR ',') FROM os_contracts_customers WHERE customer_id = " . $result[$i]['id'])->fetchColumn();


  $array[$i]['id'] = $contacts_ids;

  $check_court = $db->get_count( 'os_judiciary', array( 'contract_id'=>$result[$i]['id'] ) );
  
  if (!empty($contacts_ids)) {
    $check_court = $db->run("SELECT * FROM `os_judiciary` WHERE `contract_id` IN (".$contacts_ids.")")->fetchColumn();
  }
  

  if ($check_court != '0') {
    $court_status = 'مشتكى عليه';
  } else {
    $court_status = 'غير مشتكى عليه';
  }

  $array[$i]['cid'] = $result[$i]['id'];
  $array[$i]['name'] = $result[$i]['name'];
  $array[$i]['national_id'] = $result[$i]['id_number'];

  $array[$i]['work'] = $db->get_var( 'os_jobs', array( 'id'=>$result[$i]['job_title'] ), array('name') );
  
  $array[$i]['home_address'] = $db->run("SELECT GROUP_CONCAT(address SEPARATOR '##-##') FROM os_address WHERE address_type = 2 AND customers_id = " . $result[$i]['id'])->fetchColumn();
  $array[$i]['work_address'] = $db->run("SELECT GROUP_CONCAT(address SEPARATOR '##-##') FROM os_address WHERE address_type = 1 AND customers_id = " . $result[$i]['id'])->fetchColumn();

  $array[$i]['phone'] = $result[$i]['primary_phone_number'];

  if (!empty($contacts_ids)) {
    $array[$i]['status'] = $db->run("SELECT GROUP_CONCAT(status SEPARATOR ',') FROM os_contracts WHERE id IN (".$contacts_ids.") ")->fetchColumn(); 
    $array[$i]['sell_date'] = $db->run("SELECT GROUP_CONCAT(Date_of_sale SEPARATOR ',') FROM os_contracts WHERE id IN (".$contacts_ids.") ")->fetchColumn(); 
  } else {
    $array[$i]['status'] = '';
  }

  $array[$i]['court_status'] = $court_status;
  $array[$i]['attachments'] = $db->get_count( 'os_ImageManager', array( 'contractId'=>$result[$i]['id'] ) );

}

$array = json_encode($array);
echo $array;