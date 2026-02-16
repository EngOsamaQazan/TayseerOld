<?php
/**
 * Fahras Client Attachments — مرفقات العميل (صور)
 * يعرض صور العميل من os_ImageManager مباشرة من قاعدة البيانات
 */
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Amman");

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

require('db.php');
$db_host = 'localhost';
$db_user = 'osama';
$db_pass = 'O$amaDaTaBase@123';

// الفهرس يرسل db=jadal أو db=namaa للمرفقات
$dbMap = [
  'jadal' => 'namaa_jadal',
  'namaa' => 'namaa_erp',
  'erp'   => 'namaa_erp',
];

// عنوان URL الأساسي لكل شركة
$baseUrlMap = [
  'jadal' => 'https://jadal.aqssat.co/images/imagemanager/',
  'namaa' => 'https://namaa.aqssat.co/images/imagemanager/',
  'erp'   => 'https://namaa.aqssat.co/images/imagemanager/',
];

$requestDb = $_GET['db'] ?? '';
$db_name = $dbMap[$requestDb] ?? null;
$baseUrl = $baseUrlMap[$requestDb] ?? '';

if (!$db_name) {
  echo '<div class="alert alert-danger">معرّف قاعدة البيانات غير صحيح</div>';
  exit();
}

$custId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($custId <= 0) {
  echo '<div class="alert alert-warning">لم يتم تحديد العميل</div>';
  exit();
}

try {
  $db = new smplPDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
} catch (Exception $e) {
  echo '<div class="alert alert-danger">فشل الاتصال بقاعدة البيانات</div>';
  exit();
}

// جلب صور العميل — نبحث بـ customer_id أولاً ثم contractId كـ fallback
$images = [];

try {
  $db->bind = [];
  $stmt = $db->run("SELECT fileName FROM os_ImageManager WHERE customer_id = " . $custId . " ORDER BY id DESC LIMIT 50");
  if ($stmt && is_object($stmt)) {
    $images = $stmt->fetchAll();
  }
} catch (Exception $e) {
  // customer_id column might not exist, fallback to contractId
  try {
    $db->bind = [];
    $stmt = $db->run("SELECT fileName FROM os_ImageManager WHERE contractId = " . $custId . " ORDER BY id DESC LIMIT 50");
    if ($stmt && is_object($stmt)) {
      $images = $stmt->fetchAll();
    }
  } catch (Exception $e2) {
    $images = [];
  }
}

$count = 0;

if (!empty($images)) {
  echo '<div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;">';
  foreach ($images as $img) {
    $fileName = $img['fileName'] ?? '';
    if (!empty($fileName)) {
      $count++;
      $imgUrl = $baseUrl . rawurlencode($fileName);
      echo '<div style="text-align:center;margin-bottom:10px;">';
      echo '<a href="' . htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank">';
      echo '<img style="max-width:300px;max-height:300px;border:1px solid #ddd;border-radius:4px;padding:3px;" src="' . htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') . '" />';
      echo '</a>';
      echo '</div>';
    }
  }
  echo '</div>';
}

if ($count == 0) {
  echo '<div class="alert alert-info">لم يتم العثور على أي مرفقات لهذا العميل</div>';
}
