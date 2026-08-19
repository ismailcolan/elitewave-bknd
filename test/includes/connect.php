<?php
error_reporting(0);
session_start();
$conn = mysqli_connect("localhost","root","") or die(mysqli_error());
$db = mysqli_select_db($conn,"gracious_web") or die(mysqli_error());
date_default_timezone_set('Asia/Kolkata');
define('site_path', 'http://graciousexpress.com/web/' );

define('login_check', ' where company_id="'.$_SESSION['company_id'].'"' );
?>
