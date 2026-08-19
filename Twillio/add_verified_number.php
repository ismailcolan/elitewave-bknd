<?php

// Update the path below to your autoload.php,
// see https://getcomposer.org/doc/01-basic-usage.md
require_once 'vendor/autoload.php';
require_once('constant.php');


use Twilio\Rest\Client;

// Find your Account SID and Auth Token at twilio.com/console
// and set the environment variables. See http://twil.io/secure
$sid = constant("SID");
$token = constant("Auth");
$twilio = new Client($sid, $token);

$validation_request = $twilio->validationRequests
                             ->create("+918754765121", //phone number 
                               ["friendlyName" => "Test Number" ]
                            );

print($validation_request->friendlyName);