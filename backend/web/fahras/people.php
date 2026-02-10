<?php
header('Access-Control-Allow-Origin: *');

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


if (!isset($_REQUEST['token'])) {
  header('Content-Type: application/json');
  $array = ['error'=>'no token value'];
  $array = json_encode($array);
  echo $array;
  exit();
}

if ($_REQUEST['token'] == 'b83ba7a49b72') {
} else {
  header('Content-Type: application/json');
  $array = ['error'=>'not authorized'];
  $array = json_encode($array);
  echo $array;
  exit();
}

if (!isset($_REQUEST['client'])) {
  header('Content-Type: application/json');
  $array = ['error'=>'no client name value'];
  $array = json_encode($array);
  echo $array;
  exit();
}


$result = $db->run("SELECT * FROM `os_phone_numbers` WHERE `customers_id` = " . $_GET['client'])->fetchAll();

$array = [];

?>

<table class="table table-hover table-bordered table-striped">
  <thead>
    <tr>
      <th>الهاتف</th>
      <th>مالك الهاتف</th>
      <th>صلة القرابة</th>
      <th>فيسبوك</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($result as $key) {
    echo '
    <tr>
      <td>'.$key['phone_number'].'</td>
      <td>'.$key['owner_name'].'</td>
      <td>'.$db->get_var( 'os_cousins', array( 'id'=>$key['phone_number_owner'] ), array('name') ).'</td>
      <td>'.$key['fb_account'].'</td>
    </tr>
    ';

  } ?>
  </tbody>
</table>