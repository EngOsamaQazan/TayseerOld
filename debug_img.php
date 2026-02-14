<?php
require '/var/www/jadal.aqssat.co/vendor/autoload.php';
require '/var/www/jadal.aqssat.co/common/config/bootstrap.php';
$config = yii\helpers\ArrayHelper::merge(
    require '/var/www/jadal.aqssat.co/common/config/main.php',
    require '/var/www/jadal.aqssat.co/common/config/main-local.php',
    require '/var/www/jadal.aqssat.co/backend/config/main.php',
    require '/var/www/jadal.aqssat.co/backend/config/main-local.php'
);
$app = new yii\web\Application($config);

$contractId = 1616;
$db = Yii::$app->db;

$customerIds = $db->createCommand('SELECT customer_id FROM os_contracts_customers WHERE contract_id = :cid', [':cid' => $contractId])->queryColumn();
echo "Customers: " . json_encode($customerIds) . "\n";

$placeholders = implode(',', array_map('intval', $customerIds));

$direct = $db->createCommand("SELECT id, contractId, fileName, fileHash FROM os_ImageManager WHERE groupName = 'coustmers' AND contractId IN ($placeholders)")->queryAll();
echo "Direct images: " . count($direct) . "\n";
foreach ($direct as $d) echo "  id={$d['id']} cid={$d['contractId']} file={$d['fileName']} hash={$d['fileHash']}\n";

$selectedIds = $db->createCommand("SELECT selected_image FROM os_customers WHERE id IN ($placeholders) AND selected_image IS NOT NULL AND selected_image != '' AND selected_image != '0'")->queryColumn();
echo "Selected IDs: " . json_encode($selectedIds) . "\n";

if (!empty($selectedIds)) {
    $selPlaceholders = implode(',', array_map('intval', $selectedIds));
    $orphanCids = $db->createCommand("SELECT contractId FROM os_ImageManager WHERE id IN ($selPlaceholders) AND groupName = 'coustmers'")->queryColumn();
    echo "Orphan CIDs raw: " . json_encode($orphanCids) . "\n";
    $orphanCids = array_values(array_diff($orphanCids, $customerIds));
    echo "Orphan CIDs after diff: " . json_encode($orphanCids) . "\n";

    if (!empty($orphanCids)) {
        $orphPlaceholders = implode(',', array_map('intval', $orphanCids));
        $orphan = $db->createCommand("SELECT id, contractId, fileName FROM os_ImageManager WHERE groupName = 'coustmers' AND contractId IN ($orphPlaceholders)")->queryAll();
        echo "Orphan images: " . count($orphan) . "\n";
        foreach ($orphan as $o) echo "  id={$o['id']} cid={$o['contractId']}\n";
    }
}

// Combine all and count
$allImgIds = [];
foreach ($direct as $d) $allImgIds[] = $d['id'];
echo "\nTotal unique images: " . count(array_unique($allImgIds)) . "\n";

// Test getImagePath
echo "\n=== getImagePath test ===\n";
echo "imagemanager component: " . get_class(Yii::$app->imagemanager) . "\n";

$testId = 9436;
try {
    $p = Yii::$app->imagemanager->getImagePath($testId);
    echo "getImagePath($testId) = " . var_export($p, true) . "\n";
} catch (\Exception $e) {
    echo "getImagePath($testId) ERROR: " . $e->getMessage() . "\n";
}

// Try manually building the path
$rec = $db->createCommand("SELECT id, fileName, fileHash FROM os_ImageManager WHERE id = $testId")->queryOne();
if ($rec) {
    echo "\nManual check for id=$testId:\n";
    echo "  fileName: {$rec['fileName']}\n";
    echo "  fileHash: {$rec['fileHash']}\n";
    $manualPath = "/images/imagemanager/{$rec['id']}_{$rec['fileHash']}.jpg";
    echo "  Manual path: $manualPath\n";
    $fullPath = "/var/www/jadal.aqssat.co/backend/web" . $manualPath;
    echo "  File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
    
    // Try other extensions
    foreach (['jpg', 'jpeg', 'png', 'gif'] as $ext) {
        $tryPath = "/var/www/jadal.aqssat.co/backend/web/images/imagemanager/{$rec['id']}_{$rec['fileHash']}.$ext";
        if (file_exists($tryPath)) echo "  FOUND: $tryPath\n";
    }
    
    // List files starting with this ID
    $pattern = "/var/www/jadal.aqssat.co/backend/web/images/imagemanager/{$rec['id']}_*";
    $files = glob($pattern);
    echo "  Files matching {$rec['id']}_*: " . json_encode($files) . "\n";
}

// Now test with Active Record model (same as modal code)
echo "\n=== Active Record test ===\n";
$model = backend\modules\imagemanager\models\Imagemanager::find()
    ->where(['groupName' => 'coustmers'])
    ->andWhere(['contractId' => $customerIds])
    ->all();
echo "AR Direct: " . count($model) . "\n";
foreach ($model as $m) {
    echo "  id={$m->id} contractId={$m->contractId}\n";
    try {
        $p = Yii::$app->imagemanager->getImagePath($m->id);
        echo "    getImagePath = " . var_export($p, true) . "\n";
    } catch (\Exception $e) {
        echo "    getImagePath ERROR: " . $e->getMessage() . "\n";
    }
}
