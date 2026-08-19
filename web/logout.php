<?php
session_start();
session_unset();
session_destroy();
 setcookie('persistID', '', time()+(30 * 24 * 60 * 60), '/'); 
unset($_COOKIE['persistID']);
echo "<script> window.location = './index.php'; </script>";
?>