<?php
/**
 * Fahras API — البحث عن العملاء وبيانات العقود
 * يُستدعى من نظام الفهرس المركزي
 *
 * ملاحظة مهمة: مكتبة smplPDO لا تعيد تعيين $this->bind في flush()
 * لذلك يجب تعيين $db->bind = [] يدوياً قبل كل استدعاء run() مباشر
 * لمنع تلوث الـ bind parameters من استدعاءات get_var/get_count السابقة
 */
date_default_timezone_set("Asia/Amman");

// إخفاء الأخطاء في الإنتاج — تُسجَّل في log فقط
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require('db.php');
$db_host = 'localhost';
$db_user = 'osama';
$db_pass = 'O$amaDaTaBase@123';

// تحويل اسم الشركة لاسم قاعدة البيانات الصحيح
// الفهرس يرسل db=erp لنماء و db=jadal لجدل
$dbMap = [
  'jadal' => 'namaa_jadal',
  'namaa' => 'namaa_erp',
  'erp'   => 'namaa_erp',
];

// تحويل معرف DB إلى اسم الحساب المعروض
$accountMap = [
  'jadal' => 'جدل',
  'namaa' => 'نماء',
  'erp'   => 'نماء',
];

$requestDb = $_REQUEST['db'] ?? '';
$db_name = $dbMap[$requestDb] ?? null;

if (!$db_name) {
  echo json_encode(['error' => 'invalid db parameter']);
  exit();
}

try {
  $db = new smplPDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
} catch (Exception $e) {
  echo json_encode(['error' => 'database connection failed']);
  exit();
}

if (!isset($_REQUEST['token']) || $_REQUEST['token'] != 'b83ba7a49b72') {
  echo json_encode(['error' => 'not authorized']);
  exit();
}

if (!isset($_REQUEST['search']) || trim($_REQUEST['search']) === '') {
  echo json_encode(['error' => 'no client name value']);
  exit();
}

$search = $_REQUEST['search'];
// حماية من SQL Injection
$search = addslashes($search);

// البحث في العملاء — يدعم البحث بالاسم ورقم الهوية ورقم الهاتف
$db->bind = [];
$stmt = $db->run("SELECT * FROM os_customers WHERE name LIKE '%{$search}%' OR id_number LIKE '%{$search}%' OR primary_phone_number LIKE '%{$search}%' LIMIT 100");
$result = $stmt ? $stmt->fetchAll() : [];

$array = [];
$accountLabel = $accountMap[$requestDb] ?? $requestDb;

for ($i = 0; $i < count($result); ++$i) {

  $row = $result[$i];
  $custId = (int)$row['id'];

  $array[$i]['account'] = $accountLabel;
  $array[$i]['cid'] = $custId;
  $array[$i]['name'] = $row['name'] ?? '';
  $array[$i]['national_id'] = $row['id_number'] ?? '';
  $array[$i]['phone'] = $row['primary_phone_number'] ?? '';

  // ─── العقود المرتبطة (يجب أن تأتي قبل get_var لأن get_var تلوث $db->bind) ───
  $contacts_ids = '';
  try {
    $db->bind = [];
    $stmtContracts = $db->run("SELECT GROUP_CONCAT(contract_id SEPARATOR ',') FROM os_contracts_customers WHERE customer_id = " . $custId);
    $contacts_ids = ($stmtContracts && is_object($stmtContracts)) ? ($stmtContracts->fetchColumn() ?: '') : '';
  } catch (Exception $e) { $contacts_ids = ''; }

  if ($contacts_ids === false || $contacts_ids === null) $contacts_ids = '';
  $array[$i]['id'] = $contacts_ids;

  // ─── الوظيفة ───
  $jobId = $row['job_title'] ?? 0;
  $jobName = '';
  if (!empty($jobId)) {
    try {
      $jobName = $db->get_var('os_jobs', ['id' => $jobId], ['name']) ?: '';
    } catch (Exception $e) { $jobName = ''; }
  }
  $array[$i]['work'] = $jobName;

  // ─── العناوين (يجب تنظيف bind قبل كل run) ───
  try {
    $db->bind = [];
    $stmtHome = $db->run("SELECT GROUP_CONCAT(address SEPARATOR '##-##') FROM os_address WHERE address_type = 2 AND customers_id = " . $custId);
    $array[$i]['home_address'] = ($stmtHome && is_object($stmtHome)) ? ($stmtHome->fetchColumn() ?: '') : '';
  } catch (Exception $e) { $array[$i]['home_address'] = ''; }

  try {
    $db->bind = [];
    $stmtWork = $db->run("SELECT GROUP_CONCAT(address SEPARATOR '##-##') FROM os_address WHERE address_type = 1 AND customers_id = " . $custId);
    $array[$i]['work_address'] = ($stmtWork && is_object($stmtWork)) ? ($stmtWork->fetchColumn() ?: '') : '';
  } catch (Exception $e) { $array[$i]['work_address'] = ''; }

  // ─── حالة العقود وتاريخ البيع والقضايا ───
  $check_court = 0;
  $array[$i]['status'] = '';
  $array[$i]['sell_date'] = '';

  if (!empty($contacts_ids)) {
    $safeIds = implode(',', array_map('intval', explode(',', $contacts_ids)));

    // حالة العقود
    try {
      $db->bind = [];
      $stmtStatus = $db->run("SELECT GROUP_CONCAT(status SEPARATOR ',') FROM os_contracts WHERE id IN ({$safeIds})");
      $array[$i]['status'] = ($stmtStatus && is_object($stmtStatus)) ? ($stmtStatus->fetchColumn() ?: '') : '';
    } catch (Exception $e) {}

    // تاريخ البيع
    try {
      $db->bind = [];
      $stmtSale = $db->run("SELECT GROUP_CONCAT(Date_of_sale SEPARATOR ',') FROM os_contracts WHERE id IN ({$safeIds})");
      $array[$i]['sell_date'] = ($stmtSale && is_object($stmtSale)) ? ($stmtSale->fetchColumn() ?: '') : '';
    } catch (Exception $e) {}

    // القضايا
    try {
      $db->bind = [];
      $stmtCourt = $db->run("SELECT COUNT(*) FROM `os_judiciary` WHERE `contract_id` IN ({$safeIds}) AND `is_deleted` = 0");
      $check_court = ($stmtCourt && is_object($stmtCourt)) ? (int)$stmtCourt->fetchColumn() : 0;
    } catch (Exception $e) { $check_court = 0; }
  }

  $array[$i]['court_status'] = ($check_court > 0) ? 'مشتكى عليه' : 'غير مشتكى عليه';

  // ─── عدد المرفقات (صور العميل) ───
  $attCount = 0;
  try {
    $attCount = (int)$db->get_count('os_ImageManager', ['customer_id' => $custId]);
  } catch (Exception $e) {
    try {
      $attCount = (int)$db->get_count('os_ImageManager', ['contractId' => $custId]);
    } catch (Exception $e2) { $attCount = 0; }
  }
  $array[$i]['attachments'] = $attCount;
}

echo json_encode($array, JSON_UNESCAPED_UNICODE);
