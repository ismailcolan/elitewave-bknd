<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once('../../../web/include/connect.php');
require_once('../../../web/include/function.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DR') {
    echo json_encode(array('status' => 'error', 'message' => 'Unauthorized. Please login as a driver.'));
    exit;
}

$user_id = $_SESSION['user_id'];
$assigned_veh = $_SESSION['assigned_vehicle'];

if (empty($assigned_veh)) {
    echo json_encode(array('status' => 'error', 'message' => 'No vehicle assigned to this driver.', 'data' => []));
    exit;
}

$consignments = [];
$seen_grn = [];

$result2 = mysqli_query($conn, 'SELECT * FROM transaction_tbls');
if ($result2) {
    while ($row2 = mysqli_fetch_assoc($result2)) {
        $tbl = 'transaction_' . $row2['table_name'];
        // ALL consignments for this vehicle, newest first
        $res = mysqli_query($conn, "SELECT * FROM $tbl WHERE truck='$assigned_veh' AND active_status=0 ORDER BY str_to_date(grn_date,'%d-%m-%Y') DESC");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                if (in_array($row['grn_no'], $seen_grn)) {
                    continue;
                }
                $seen_grn[] = $row['grn_no'];
                $row['table_name'] = $row2['table_name']; // Send the base table name
                
                // Fetch tracking code for this GRN
                $current_grn = $row['grn_no'];
                $log_query = mysqli_query($conn, "SELECT tracking_code FROM transaction_log WHERE grn_no='$current_grn' LIMIT 1");
                if ($log_query && mysqli_num_rows($log_query) > 0) {
                    $log_row = mysqli_fetch_assoc($log_query);
                    $row['tracking_code'] = $log_row['tracking_code'];
                } else {
                    $row['tracking_code'] = '';
                }
                
                $consignments[] = $row;
            }
        }
    }
}

echo json_encode(array('status' => 'success', 'data' => $consignments));
?>
