<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'appMail.php';

echo "<h3>Before sendAppMail()</h3>";

$result = sendAppMail(
    "Test User",
    "muzaffarahmed.g@colanonline.com",
    "SMTP Test",
    "This is from test email."
);

echo "<br><br>";
echo "Result = ";
var_dump($result);

echo "<br>Finished";