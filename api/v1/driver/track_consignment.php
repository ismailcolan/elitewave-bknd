<?php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../../web/include/connect.php';
require_once '../../../web/include/function.php';

// Check if user is logged in via session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DR') {
    echo json_encode(array('status' => 'error', 'message' => 'Unauthorized. Please login as a driver.'));
    exit;
}

$response = array('status' => 'error', 'message' => 'Invalid request');

// Handle both raw JSON and form-data inputs
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

$grn_no = isset($input['grn_no']) ? $input['grn_no'] : (isset($_REQUEST['grn_no']) ? $_REQUEST['grn_no'] : '');
$tracking_code = isset($input['tracking_code']) ? $input['tracking_code'] : (isset($_REQUEST['tracking_code']) ? $_REQUEST['tracking_code'] : '');

$grn_no = trim($grn_no);
$tracking_code = trim($tracking_code);

$assigned_veh = $_SESSION['assigned_vehicle'];

if ($grn_no != "" && $tracking_code != "") {
    
    // Check if the combination of grn_no and tracking_code is valid in transaction_log
    $log_check_query = "SELECT * FROM transaction_log WHERE grn_no='$grn_no' AND tracking_code='$tracking_code'";
    $log_check_result = mysqli_query($conn, $log_check_query);
    
    if ($log_check_result && mysqli_num_rows($log_check_result) > 0) {
        
        $found = false;
        $query2 = "SELECT * FROM transaction_tbls";
        $result2 = mysqli_query($conn, $query2);
        
        while ($row2 = mysqli_fetch_assoc($result2)) {
            $tbl = "transaction_" . $row2['table_name'];
            $tbl_inv = "transaction_invoice_" . $row2['table_name'];
            
            // Find by grn_no and restrict to driver's assigned vehicle
            $query = "SELECT * FROM $tbl WHERE grn_no='$grn_no' AND truck='$assigned_veh' AND booking_status=''";
            $result = mysqli_query($conn, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $found = true;
                $grnr = mysqli_fetch_array($result);
                
                // Fetch package count
                $transaction_id = $grnr['transaction_id'];
                $query3 = "SELECT sum(no_of_pkge) AS no_of_pkge FROM $tbl_inv WHERE transaction_id='$transaction_id'";
                $result3 = mysqli_query($conn, $query3);
                $row3 = mysqli_fetch_array($result3);
                
                $consignment_details = array(
                    'grn_no' => $grn_no,
                    'tracking_code' => $tracking_code,
                    'consignor' => get_client_name($conn, $grnr['consigner']),
                    'consignee' => get_client_name($conn, $grnr['consignee']),
                    'mode' => get_mode($conn, $grnr['mode_of_transportation']),
                    'grn_date' => $grnr['grn_date'],
                    'no_of_packages' => $row3['no_of_pkge']
                );
                
                // Fetch tracking history
                $tracking_history = array();
                $trans_status_query = "SELECT * FROM `transaction_status` WHERE sheet_id IN (SELECT sheet_id FROM transaction_status_log WHERE grn_no='$grn_no')";
                $res_status = mysqli_query($conn, $trans_status_query);
                
                if ($res_status) {
                    while ($result_status = mysqli_fetch_assoc($res_status)) {
                        $date_data = $result_status['created_at'];
                        if (strlen($date_data) == 20) {
                            $date = substr($date_data, 0, -9);
                            $t = substr($date_data, 11);
                            $time = date('h:i:s A', strtotime($t));
                        } else {
                            $date = $date_data;
                            $time = substr($date_data, 11);
                        }
                        
                        $tracking_history[] = array(
                            'status' => get_trans_status($result_status['status']),
                            'details' => $result_status['remarks'],
                            'date' => $date,
                            'time' => $time
                        );
                    }
                }
                
                // Fetch timeline progression steps
                $status_log = array();
                array_push($status_log, 1);
                $query_log = "SELECT * FROM transaction_status_log WHERE grn_no='$grn_no'";
                $result_log = mysqli_query($conn, $query_log);
                if ($result_log) {
                    while ($rlog = mysqli_fetch_array($result_log)) {
                        if (!in_array($rlog['from_status'], $status_log)) array_push($status_log, $rlog['from_status']);
                        if (!in_array($rlog['to_status'], $status_log)) array_push($status_log, $rlog['to_status']);
                    }
                }
                
                // Add friendly status strings to timeline
                $timeline_steps = array();
                foreach($status_log as $stepId) {
                    $timeline_steps[] = array(
                        'step_id' => $stepId,
                        'status_name' => get_trans_status($stepId)
                    );
                }
                
                $response['status'] = 'success';
                $response['message'] = 'Consignment found successfully';
                $response['data'] = array(
                    'consignment_details' => $consignment_details,
                    'tracking_history' => $tracking_history,
                    'status_timeline' => $timeline_steps,
                    'current_status_id' => empty($status_log) ? 1 : max($status_log),
                    'current_status_name' => get_trans_status(empty($status_log) ? 1 : max($status_log))
                );
                
                break;
            }
        }
        
        if (!$found) {
            $response['message'] = 'Consignment tracking data not found or is not assigned to your vehicle.';
        }
    } else {
        $response['message'] = 'GRN No and Tracking Code do not match. Please check and try again.';
    }
} else {
    $response['message'] = 'Missing grn_no or tracking_code parameters. Both are required.';
}

echo json_encode($response);
exit();
?>
