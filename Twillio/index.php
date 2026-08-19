<?php

// Update the path below to your autoload.php,
// see https://getcomposer.org/doc/01-basic-usage.md
require_once 'vendor/autoload.php';
require_once('function.php');
require_once('constant.php');

use Twilio\Rest\Client;

$id = 1;
//check Status
if($id){
    $status = get_status($id);

// Find your Account SID and Auth Token at twilio.com/console
// and set the environment variables. See http://twil.io/secure
$sid = constant("SID");
$token = constant("Auth");
$twilio = new Client($sid, $token);
// print($status);
// exit();
$msg = "Your Consignment is ".$status." \r\n and GRN No - MOHA00001";
// print($msg);
// exit();
$message = $twilio->messages
                  ->create("+918925440676", // to
                           ["body" => $msg, "from" => "+17853776942"]
                  );
                  
print($message->sid);
}else{
    print("Message Not Sent!");
}

