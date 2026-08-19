<?php
// Headers for API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database connection and functions
require_once('../../web/include/connect.php');
require_once('../../web/include/function.php');

// Check if user is logged in via session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('status' => 'error', 'message' => 'Unauthorized. Please login first.'));
    exit;
}

// Initialize response array
$response = array();

// Extract parameters
$method = $_SERVER['REQUEST_METHOD'];

// Handle JSON input for POST, else fall back to $_GET/$_POST
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data)) $data = $_POST;
} else {
    $data = $_GET;
}

// 1. Authentication Check (Require user_id)
$user_id = isset($data['user_id']) ? $data['user_id'] : '';
if (empty($user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: user_id is required']);
    exit;
}

// Optional: Validate if user_id exists in DB (simplified security check)
$user_query = "SELECT * FROM users WHERE user_id='" . mysqli_real_escape_string($conn, $user_id) . "' AND status=0";
$user_result = mysqli_query($conn, $user_query);
if (mysqli_num_rows($user_result) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: invalid user_id']);
    exit;
}
$user_row = mysqli_fetch_assoc($user_result);
$created_by = $user_id;
$created_at = date('d-m-Y');

// 2. Action Routing
$action = isset($data['action']) ? $data['action'] : '';

if ($action == 'get_grn' && $method == 'GET') {
    // Mimics fetch_details.php?cmd=get_grn_for_status
    // In the web app, this filters by origin, destination, mode, etc. Here we simplify to GRN No to match the CSV behavior
    $grn_nos_input = isset($data['grn_no']) ? $data['grn_no'] : '';
    
    if (empty($grn_nos_input)) {
        echo json_encode(['status' => 'error', 'message' => 'grn_no is required']);
        exit;
    }

    $grn_list = explode(',', $grn_nos_input);
    $result_data = array();
    $failed_grns = array();
    
    $query2 = "SELECT * FROM transaction_tbls";
    $result2 = mysqli_query($conn, $query2);
    $tables = array();
    while ($row2 = mysqli_fetch_assoc($result2)) {
        $tables[] = $row2['table_name'];
    }

    foreach ($grn_list as $grn_no) {
        $grn_no = trim(mysqli_real_escape_string($conn, $grn_no));
        if (empty($grn_no)) continue;

        $found = false;
        foreach ($tables as $table_name) {
            $trans_table = "transaction_" . $table_name;
            $invoice_table = "transaction_invoice_" . $table_name;

            $query = "SELECT * FROM $trans_table WHERE grn_no='$grn_no' AND booking_status = '' LIMIT 1";
            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $found = true;

                $pkg_query = "SELECT SUM(no_of_pkge) AS pkgs, SUM(gross_weight) AS weight FROM $invoice_table WHERE transaction_id='" . $row['transaction_id'] . "'";
                $pkg_result = mysqli_query($conn, $pkg_query);
                $pkg_row = $pkg_result ? mysqli_fetch_assoc($pkg_result) : array('pkgs' => '', 'weight' => '');

                $result_data[] = array(
                    'transaction_id' => $row['transaction_id'],
                    'grn_id' => $row['grn_id'],
                    'grn_no' => $row['grn_no'],
                    'grn_date' => $row['grn_date'],
                    'weight' => $pkg_row['weight'] ?: '0',
                    'pkgs' => $pkg_row['pkgs'] ?: '0',
                    'mode' => get_mode($conn, $row['mode_of_transportation']),
                    'origin' => get_city_name($conn, $row['origin']),
                    'consignor' => get_client_name($conn, $row['consigner']),
                    'consignee' => get_client_name($conn, $row['consignee']),
                    'destination' => get_city_name($conn, $row['destination']),
                    'current_status' => get_trans_status($row['status']),
                    'status_id' => $row['status'],
                    'tab_name' => $table_name
                );
                break; // Found in this table, no need to check other months
            }
        }
        
        if (!$found) {
            $failed_grns[] = $grn_no;
        }
    }

    $response['status'] = 'success';
    $response['data'] = $result_data;
    if (count($failed_grns) > 0) {
        $response['message'] = 'Some GRNs were not found or cancelled.';
        $response['failed_grns'] = $failed_grns;
    } else {
        $response['message'] = 'All GRNs fetched successfully.';
    }

} elseif ($action == 'get_by_status' && $method == 'GET') {
    // Mimics fetch_details.php?cmd=get_all_bookings_for_status
    $filter_status = isset($data['filter_status']) ? trim($data['filter_status']) : '';

    if ($filter_status === '') {
        echo json_encode(['status' => 'error', 'message' => 'Please provide a filter_status']);
        exit;
    }

    $filter_status = (int)$filter_status;
    $result_data = array();

    $query2 = "SELECT * FROM transaction_tbls";
    $result2 = mysqli_query($conn, $query2);

    while ($row2 = mysqli_fetch_assoc($result2)) {
        $table_name = $row2['table_name'];
        $trans_table = 'transaction_' . $table_name;
        $invoice_table = 'transaction_invoice_' . $table_name;

        // Note: Using error suppression to prevent API break if a table doesn't exist yet
        $query = "SELECT * FROM $trans_table WHERE status = '$filter_status' AND booking_status = '' ORDER BY grn_date DESC, grn_no DESC";
        $result = @mysqli_query($conn, $query);

        if (!$result) {
            continue;
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $pkg_query = "SELECT SUM(no_of_pkge) AS pkgs, SUM(gross_weight) AS weight FROM $invoice_table WHERE transaction_id='" . $row['transaction_id'] . "'";
            $pkg_result = mysqli_query($conn, $pkg_query);
            $pkg_row = $pkg_result ? mysqli_fetch_assoc($pkg_result) : array();

            $result_data[] = array(
                'transaction_id' => $row['transaction_id'],
                'grn_id' => $row['grn_id'],
                'grn_no' => $row['grn_no'],
                'grn_date' => $row['grn_date'],
                'weight' => isset($pkg_row['weight']) ? $pkg_row['weight'] : '0',
                'pkgs' => isset($pkg_row['pkgs']) ? $pkg_row['pkgs'] : '0',
                'mode' => get_mode($conn, $row['mode_of_transportation']),
                'origin' => get_city_name($conn, $row['origin']),
                'consignor' => get_client_name($conn, $row['consigner']),
                'consignee' => get_client_name($conn, $row['consignee']),
                'destination' => get_city_name($conn, $row['destination']),
                'current_status' => get_trans_status($row['status']),
                'status_id' => $row['status'],
                'client_id' => $row['client_id'],
                'tab_name' => $table_name
            );
        }
    }

    $response['status'] = 'success';
    $response['message'] = count($result_data) . ' booking(s) found.';
    $response['data'] = $result_data;

} elseif ($action == 'update_status' && $method == 'POST') {
    // Mimics save_details.php?form_name=bulk_list_status_update & change_grn_status
    $new_status = isset($data['status']) ? (int)$data['status'] : 0;
    $remarks = isset($data['remarks']) ? $data['remarks'] : '';
    $origin = isset($data['origin']) ? mysqli_real_escape_string($conn, $data['origin']) : '';
    $destination = isset($data['destination']) ? mysqli_real_escape_string($conn, $data['destination']) : '';
    $mode = isset($data['mode']) ? mysqli_real_escape_string($conn, $data['mode']) : '';
    $transactions = isset($data['transactions']) ? $data['transactions'] : array(); // Array of {trans_id, grn_id, grn_no, tab_name}

    if (is_string($transactions)) {
        $decoded = json_decode($transactions, true);
        if (is_array($decoded)) {
            $transactions = $decoded;
        }
    }

    if (empty($new_status) || empty($transactions) || !is_array($transactions)) {
        echo json_encode(['status' => 'error', 'message' => 'Status and an array of transactions are required']);
        exit;
    }

    $c_date_full = date('d-m-Y H:i:s A');

    // Generate sheet_no
    $sheetq = "SELECT max(sheet_id) as id FROM transaction_status";
    $sheetres = mysqli_query($conn, $sheetq);
    $sheetr = mysqli_fetch_assoc($sheetres);
    $sheet_id = $sheetr['id'] + 1;
    $sheet_no = 'SN/' . sprintf('%04d', $sheet_id);

    $insq1 = "INSERT INTO `transaction_status`(`sheet_id`,`sheet_no`,`origin`,`destination`,`mode`,`remarks`,`status`,`created_at`,`created_by`) 
              VALUES ('$sheet_id','$sheet_no','$origin','$destination','$mode','" . mysqli_real_escape_string($conn, $remarks) . "','$new_status','$c_date_full','$created_by')";
    mysqli_query($conn, $insq1);

    $success_count = 0;
    $fail_count = 0;
    $failed_items = array();

    // Cache table names to resolve missing tab_name or trans_id
    $t_query = mysqli_query($conn, "SELECT table_name FROM transaction_tbls");
    $db_tables = array();
    if ($t_query) {
        while ($t_row = mysqli_fetch_assoc($t_query)) {
            $db_tables[] = $t_row['table_name'];
        }
    }

    foreach ($transactions as $txn) {
        $trans_id = isset($txn['trans_id']) ? mysqli_real_escape_string($conn, $txn['trans_id']) : '';
        $grn_id = isset($txn['grn_id']) ? mysqli_real_escape_string($conn, $txn['grn_id']) : '';
        $grn_no = isset($txn['grn_no']) ? mysqli_real_escape_string($conn, $txn['grn_no']) : '';
        $tab_name = isset($txn['tab_name']) ? mysqli_real_escape_string($conn, $txn['tab_name']) : '';

        // Auto-resolve missing fields
        if (empty($trans_id) || empty($grn_id) || empty($grn_no) || empty($tab_name)) {
            if (!empty($grn_no)) {
                foreach ($db_tables as $t_name) {
                    $check_res = @mysqli_query($conn, "SELECT transaction_id, grn_id, grn_no FROM transaction_$t_name WHERE grn_no = '$grn_no' LIMIT 1");
                    if ($check_res && mysqli_num_rows($check_res) > 0) {
                        $c_row = mysqli_fetch_assoc($check_res);
                        $trans_id = $c_row['transaction_id'];
                        $grn_id = $c_row['grn_id'];
                        $grn_no = $c_row['grn_no'];
                        $tab_name = $t_name;
                        break;
                    }
                }
            } elseif (!empty($trans_id)) {
                foreach ($db_tables as $t_name) {
                    $check_res = @mysqli_query($conn, "SELECT transaction_id, grn_id, grn_no FROM transaction_$t_name WHERE transaction_id = '$trans_id' LIMIT 1");
                    if ($check_res && mysqli_num_rows($check_res) > 0) {
                        $c_row = mysqli_fetch_assoc($check_res);
                        $trans_id = $c_row['transaction_id'];
                        $grn_id = $c_row['grn_id'];
                        $grn_no = $c_row['grn_no'];
                        $tab_name = $t_name;
                        break;
                    }
                }
            }
        }

        if (empty($trans_id) || empty($tab_name)) {
            $fail_count++;
            $failed_items[] = $txn;
            continue;
        }

        $trans_table = 'transaction_' . $tab_name;

        $row_q = "SELECT status, client_id FROM $trans_table WHERE transaction_id='$trans_id'";
        $row_res = @mysqli_query($conn, $row_q);

        if (!$row_res || mysqli_num_rows($row_res) == 0) {
            $fail_count++;
            $failed_items[] = $txn;
            continue;
        }
        $row = mysqli_fetch_assoc($row_res);

        // Prevent moving backwards in status
        if ((int)$new_status < (int)$row['status']) {
            $fail_count++;
            $txn['error'] = 'Cannot move to a previous status stage';
            $failed_items[] = $txn;
            continue;
        }

        $upd_query = "UPDATE $trans_table SET status='$new_status' WHERE transaction_id='$trans_id'";
        $upd_result = mysqli_query($conn, $upd_query);

        if ($upd_result) {
            $insq = "INSERT INTO `transaction_status_log` 
                     (`sheet_id`,`grn_id`,`grn_no`,`from_status`,`to_status`,`client_id`,`updated_at`,`updated_by`) 
                     VALUES ('$sheet_id','$grn_id','$grn_no','" . $row['status'] . "','$new_status','" . $row['client_id'] . "','$created_at','$created_by')";
            mysqli_query($conn, $insq);
            $success_count++;
        } else {
            $fail_count++;
            $failed_items[] = $txn;
        }
    }

    if ($success_count > 0) {
        $response['status'] = 'success';
        $response['message'] = "Status updated successfully for $success_count booking(s).";
        if ($fail_count > 0) {
            $response['message'] .= " Failed to update $fail_count booking(s).";
            $response['failed_items'] = $failed_items;
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Status update failed for all provided bookings.';
    }

} elseif ($action == 'list_sheets' && $method == 'GET') {
    // List all status sheets (transaction_status)
    $sheets = array();
    $query = "SELECT * FROM transaction_status ORDER BY sheet_id DESC";
    $result = mysqli_query($conn, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $sheets[] = array(
                'sheet_id' => $row['sheet_id'],
                'sheet_no' => $row['sheet_no'],
                'origin' => get_city_name($conn, $row['origin']),
                'destination' => get_city_name($conn, $row['destination']),
                'mode' => get_mode($conn, $row['mode']),
                'status' => get_trans_status($row['status']),
                'status_id' => $row['status'],
                'remarks' => $row['remarks'],
                'created_at' => $row['created_at'],
                'created_by' => $row['created_by']
            );
        }
    }

    $response['status'] = 'success';
    $response['message'] = count($sheets) . ' status sheet(s) found.';
    $response['data'] = $sheets;

} elseif ($action == 'get_sheet_details' && $method == 'GET') {
    // Get details of a specific sheet (by sheet_id or sheet_no)
    $sheet_id_input = isset($data['sheet_id']) ? trim($data['sheet_id']) : '';
    $sheet_no_input = isset($data['sheet_no']) ? trim($data['sheet_no']) : '';

    if (empty($sheet_id_input) && empty($sheet_no_input)) {
        echo json_encode(['status' => 'error', 'message' => 'sheet_id or sheet_no is required']);
        exit;
    }

    $where_clause = "";
    if (!empty($sheet_id_input)) {
        $where_clause = "sheet_id = '" . mysqli_real_escape_string($conn, $sheet_id_input) . "'";
    } else {
        $where_clause = "sheet_no = '" . mysqli_real_escape_string($conn, $sheet_no_input) . "'";
    }

    // Get the sheet header
    $sheet_query = "SELECT * FROM transaction_status WHERE $where_clause LIMIT 1";
    $sheet_result = mysqli_query($conn, $sheet_query);

    if (!$sheet_result || mysqli_num_rows($sheet_result) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Status sheet not found']);
        exit;
    }

    $sheet = mysqli_fetch_assoc($sheet_result);
    $sheet_id = $sheet['sheet_id'];

    // Get the sheet log details
    $logs = array();
    $log_query = "SELECT * FROM transaction_status_log WHERE sheet_id = '$sheet_id'";
    $log_result = mysqli_query($conn, $log_query);

    if ($log_result) {
        while ($log_row = mysqli_fetch_assoc($log_result)) {
            $logs[] = array(
                'grn_id' => $log_row['grn_id'],
                'grn_no' => $log_row['grn_no'],
                'from_status' => get_trans_status($log_row['from_status']),
                'from_status_id' => $log_row['from_status'],
                'to_status' => get_trans_status($log_row['to_status']),
                'to_status_id' => $log_row['to_status'],
                'client_id' => $log_row['client_id'],
                'client_name' => get_client_name($conn, $log_row['client_id']),
                'updated_at' => $log_row['updated_at'],
                'updated_by' => $log_row['updated_by']
            );
        }
    }

    $response['status'] = 'success';
    $response['data'] = array(
        'sheet_id' => $sheet['sheet_id'],
        'sheet_no' => $sheet['sheet_no'],
        'origin' => get_city_name($conn, $sheet['origin']),
        'destination' => get_city_name($conn, $sheet['destination']),
        'mode' => get_mode($conn, $sheet['mode']),
        'status' => get_trans_status($sheet['status']),
        'status_id' => $sheet['status'],
        'remarks' => $sheet['remarks'],
        'created_at' => $sheet['created_at'],
        'created_by' => $sheet['created_by'],
        'consignments' => $logs
    );

} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid action or request method.';
}

echo json_encode($response);
?>
