<?php
require_once __DIR__ . '/include/connect.php';
require_once __DIR__ . '/include/function.php';
require_once __DIR__ . '/include/eway/eway_schema.php';
require_once __DIR__ . '/include/eway/EwayPartBService.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'message' => 'Session expired. Please log in again.'));
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

function ew_partb_json_out($payload, $code = 200)
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

$settings = ew_eway_get_settings($conn);
if (!$settings) {
    ew_partb_json_out(array('ok' => false, 'message' => 'E-Way API settings are not initialized.'), 500);
}

if ($action === 'history') {
    $mod = ew_eway_require_module($conn);
    if (!$mod['ok']) {
        ew_partb_json_out(array('ok' => false, 'message' => $mod['message']), 503);
    }
    $svc = new EwayPartBService($conn, $settings, $userId);
    $filter = isset($_GET['ewb_no']) ? trim($_GET['ewb_no']) : '';
    $rows = $svc->fetchHistory(150, $filter);
    ew_partb_json_out(array('ok' => true, 'rows' => $rows));
}

if ($action === 'load_ewb') {
    $mod = ew_eway_require_module($conn);
    if (!$mod['ok']) {
        ew_partb_json_out(array('ok' => false, 'message' => $mod['message']), 503);
    }
    $ewb = isset($_REQUEST['ewb_no']) ? $_REQUEST['ewb_no'] : '';
    $svc = new EwayPartBService($conn, $settings, $userId);
    try {
        $data = $svc->loadEwb($ewb);
        ew_partb_json_out($data, $data['ok'] ? 200 : 400);
    } catch (Exception $e) {
        ew_partb_json_out(array('ok' => false, 'message' => $e->getMessage()), 500);
    }
}

if ($action === 'update_partb') {
    $mod = ew_eway_require_module($conn);
    if (!$mod['ok']) {
        ew_partb_json_out(array('ok' => false, 'message' => $mod['message']), 503);
    }
    $input = array(
        'ewb_no' => $_POST['ewb_no'] ?? '',
        'vehicle_no' => $_POST['vehicle_no'] ?? '',
        'from_place' => $_POST['from_place'] ?? '',
        'from_state' => $_POST['from_state'] ?? '',
        'reason_code' => $_POST['reason_code'] ?? '',
        'reason_rem' => $_POST['reason_rem'] ?? '',
        'trans_mode' => $_POST['trans_mode'] ?? '',
        'vehicle_type' => $_POST['vehicle_type'] ?? 'R',
        'trans_doc_no' => $_POST['trans_doc_no'] ?? '',
        'trans_doc_date' => $_POST['trans_doc_date'] ?? '',
    );
    $oldVehicle = trim($_POST['current_vehicle'] ?? '');
    $svc = new EwayPartBService($conn, $settings, $userId);
    try {
        $result = $svc->updatePartB($input, $oldVehicle);
        ew_partb_json_out($result, !empty($result['ok']) ? 200 : 400);
    } catch (Exception $e) {
        ew_partb_json_out(array('ok' => false, 'message' => $e->getMessage()), 500);
    }
}

if ($action === 'test_auth') {
    if (($_SESSION['role'] ?? '') !== 'AD') {
        ew_partb_json_out(array('ok' => false, 'message' => 'Only administrators can test API connection.'), 403);
    }
    $svc = new EwayPartBService($conn, $settings, $userId);
    try {
        $result = $svc->testAuth();
        ew_partb_json_out($result);
    } catch (Exception $e) {
        ew_partb_json_out(array('ok' => false, 'message' => $e->getMessage()), 400);
    }
}

ew_partb_json_out(array('ok' => false, 'message' => 'Unknown action.'), 400);
