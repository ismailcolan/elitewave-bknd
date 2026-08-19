<?php
// Headers for API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database connection (this also starts the session via connect.php)
require_once('../../../web/include/connect.php');

// Initialize response array
$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Unset all of the session variables
    $_SESSION = array();

    // Destroy the session
    if (session_id() != '' || isset($_COOKIE[session_name()])) {
        session_destroy();
    }

    $response['status'] = 'success';
    $response['message'] = 'Logout successful';
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method. Please use POST.';
}

echo json_encode($response);
?>
