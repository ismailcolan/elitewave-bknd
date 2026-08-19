<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once('../../../web/include/connect.php');
require_once('../../../web/include/function.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DR') {
    echo json_encode(array('status' => 'error', 'message' => 'Unauthorized. Please login as a driver.'));
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (empty($data)) {
    $data = $_POST;
}

$status = isset($data['status']) ? trim($data['status']) : '';
$remarks = isset($data['remarks']) ? trim($data['remarks']) : '';
$origin = isset($data['origin']) ? trim($data['origin']) : '0';
$destination = isset($data['destination']) ? trim($data['destination']) : '0';
$mode = isset($data['mode']) ? trim($data['mode']) : '0';

// Build a list of GRNs to process
$grn_list = array();
if (isset($data['transactions']) && is_array($data['transactions'])) {
    foreach ($data['transactions'] as $txn) {
        if (isset($txn['grn_no']) && trim($txn['grn_no']) !== '') {
            $grn_list[] = trim($txn['grn_no']);
        }
    }
} elseif (isset($data['grn_no']) && trim($data['grn_no']) !== '') {
    $grn_list[] = trim($data['grn_no']);
}

if (empty($grn_list) || empty($status)) {
    echo json_encode(array('status' => 'error', 'message' => 'grn_no (or transactions array) and status are required.'));
    exit;
}

$assigned_veh = $_SESSION['assigned_vehicle'];

// Variables for processing
$c_date = date('d-m-Y H:i:s A');
$created_at = $c_date;
$created_by = $_SESSION['user_name'];
$success_count = 0;
$errors = array();

// Process each GRN
foreach ($grn_list as $grn_no) {
    $found = false;
    $target_table = '';
    $client_id = '';
    $current_status = '';
    $grn_id = '';

    $result2 = mysqli_query($conn, 'SELECT * FROM transaction_tbls');
    while ($row2 = mysqli_fetch_assoc($result2)) {
        $tbl = 'transaction_' . $row2['table_name'];
        // Look for it assigned to this driver's truck
        $res = mysqli_query($conn, "SELECT * FROM $tbl WHERE grn_no='$grn_no' AND truck='$assigned_veh' LIMIT 1");
        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            
            $found = true;
            $target_table = $row2['table_name'];
            $client_id = $row['client_id'];
            $current_status = $row['status'];
            $grn_id = $row['grn_id'];
            break;
        }
    }

    if (!$found) {
        $errors[] = "Consignment $grn_no not found or not assigned to your vehicle.";
        continue;
    }

    if ($current_status >= $status) {
        $errors[] = "Consignment $grn_no: Cannot change status to a previous or same state.";
        continue;
    }

    // Generate sheet id
    $sheetq = 'SELECT max(sheet_id) AS id FROM transaction_status';
    $sheetres = mysqli_query($conn, $sheetq);
    $sheetr = mysqli_fetch_array($sheetres);
    $sheet_id = $sheetr['id'] + 1;
    $sheet_no = 'SN/' . sprintf('%04d', $sheet_id);

    // Insert status
    $insq1 = "INSERT INTO `transaction_status`(`sheet_id`,`sheet_no`, `origin`, `destination`, `mode`,`remarks`, `status`, `created_at`, `created_by`) VALUES ('$sheet_id','$sheet_no','$origin','$destination','$mode','$remarks','$status','$c_date','$created_by')";
    $insr1 = mysqli_query($conn, $insq1);

    if ($insr1) {
        $insq = "INSERT INTO `transaction_status_log`(`sheet_id`,`grn_id`, `grn_no`, `from_status`, `to_status`,`client_id`,`updated_at`, `updated_by`) VALUES ('$sheet_id','$grn_id','$grn_no','$current_status','$status','$client_id','$created_at','$created_by')";
        $insr = mysqli_query($conn, $insq);

        $query1 = "UPDATE transaction_" . $target_table . " SET status='$status' WHERE grn_no='$grn_no' AND client_id='$client_id'";
        $update_res = mysqli_query($conn, $query1);

        if ($update_res) {
            $success_count++;
        } else {
            $errors[] = "Failed to update status for $grn_no in target table.";
        }
    } else {
        $errors[] = "Failed to create transaction sheet for $grn_no.";
    }
}

// Prepare final response
if ($success_count > 0 && empty($errors)) {
    echo json_encode(array('status' => 'success', 'message' => "$success_count status(es) updated successfully."));
} elseif ($success_count > 0 && !empty($errors)) {
    echo json_encode(array('status' => 'partial', 'message' => "Successfully updated $success_count consignment(s), but encountered errors with others.", 'errors' => $errors));
} else {
    echo json_encode(array('status' => 'error', 'message' => 'Failed to update any consignments.', 'errors' => $errors));
}
?>
