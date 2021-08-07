<?php

error_reporting(0);

require './jwt.php';

$iss=$_GET['iss'];
$kid=$_GET['kid'];

if(empty($iss)){
    $result = array(
        'status' => '409',
        'detail' => '$iss is required'
    );
    echo json_encode($result);
    exit();
}elseif(empty($kid)){
    $result = array(
        'status' => '409',
        'detail' => '$kid is required'
    );
    echo json_encode($result);
    exit();
}elseif(!file_exists('/www/wwwroot/asc.isign.ren/AuthKey/AuthKey_'.$kid.'.p8')){
    $result = array(
        'status' => '409',
        'detail' => 'cannot find p8 file in server, please upload your p8 file'
    );
    echo json_encode($result);
    exit();
}

$key = file_get_contents('/www/wwwroot/asc.isign.ren/AuthKey/AuthKey_'.$kid.'.p8');

$header = [
    'alg' => 'ES256',
    'kid' => $kid,
    'typ' => 'JWT',
];

$payload = [
    'iss' => $iss,
    'exp' => time() + 1200,
    'aud' => 'appstoreconnect-v1'
];

$token =  ECSign::sign($payload, $header, $key);

$result = array(
    'status'=>'200',
    'expiration'=>time() + 1200,
    'token' => $token
);

echo json_encode($result);

exit();