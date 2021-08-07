<?php

error_reporting(0);

if(empty($_GET['token'])){
    $result = array(
        'status' => '409',
        'detail' => '$token is required'
    );
    echo json_encode($result);
    exit();
}

$curl = curl_init();

curl_setopt_array($curl, array(
   CURLOPT_URL => 'https://api.appstoreconnect.apple.com/v1/certificates?limit=200',
   CURLOPT_RETURNTRANSFER => true,
   CURLOPT_ENCODING => '',
   CURLOPT_MAXREDIRS => 10,
   CURLOPT_TIMEOUT => 0,
   CURLOPT_FOLLOWLOCATION => true,
   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
   CURLOPT_CUSTOMREQUEST => 'GET',
   CURLOPT_HTTPHEADER => array(
      'Authorization: Bearer '.$_GET['token']
   ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;

exit();