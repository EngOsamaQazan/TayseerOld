<?php
header('Access-Control-Allow-Origin: *');
function curl_load($url) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 3 sec.
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 10 sec.

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
    } 

    if (isset($error_msg)) {
        $result = $error_msg;
    }

    curl_close($ch);

    return $result;

}


$result = curl_load('https://api-'.$_GET['db'].'.aqssat.co/v1/customer-images/index?customer_id=' . $_GET['id']);

$result = json_decode($result);

$count = 0;

foreach ($result as $key) {

  if (!empty($key->url)) {
    $count += 1;
    echo '<img class="img-responsive" src="'.$key->url.'" />'; 
  }

}

if ($count == 0) {
  echo 'لم يتم العثور على اي مرفقات لهذا العميل';
}
