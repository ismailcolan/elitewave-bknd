<?php
session_start();
$query = (isset($_GET['data'])) ? $_GET['data'] : '';
$output = [];
parse_str($query, $output);
$unserialize = unserialize($output['aParam']);

$grn_no = isset($unserialize['grn_no']) ? implode("', '", $unserialize['grn_no']) : '';

if ($grn_no != '') {
    header('location:http://localhost/graciousexpress/verify_paylink2.php?data=' . urlencode($query));
} else {
    // echo "<script>alert('Invalid user payment');</script>";
        $_SESSION['msg'] = "Process Failed, Something went wrong";
        $_SESSION['response'] = "failed";
        $_SESSION['paymentId'] = "";
        header('location: http://localhost/graciousexpress');
}
?>