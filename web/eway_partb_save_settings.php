<?php
require_once __DIR__ . '/include/connect.php';
require_once __DIR__ . '/include/function.php';
require_once __DIR__ . '/include/eway/eway_schema.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'AD')) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'message' => 'Access denied.'));
    exit;
}

ew_eway_ensure_schema($conn);
$userId = (int) $_SESSION['user_id'];

$moduleEnabled = isset($_POST['module_enabled']) && $_POST['module_enabled'] === '1' ? 1 : 0;
$environment = in_array($_POST['environment'] ?? '', array('sandbox', 'production'), true) ? $_POST['environment'] : 'sandbox';
$apiBase = trim($_POST['api_base_url'] ?? '');
$authPath = trim($_POST['auth_path'] ?? '/authenticate');
$ewayPath = trim($_POST['eway_api_path'] ?? '/ewaybillapi/EwayApi');
$getPath = trim($_POST['get_eway_path'] ?? '/ewayapi/GetEwayBill');
$gstin = strtoupper(trim($_POST['gstin'] ?? ''));
$clientId = trim($_POST['client_id'] ?? '');
$apiUsername = trim($_POST['api_username'] ?? '');
$defaultTransMode = preg_match('/^[1-4]$/', $_POST['default_trans_mode'] ?? '') ? $_POST['default_trans_mode'] : '1';
$defaultVehicleType = in_array(strtoupper($_POST['default_vehicle_type'] ?? 'R'), array('R', 'O'), true) ? strtoupper($_POST['default_vehicle_type']) : 'R';
$nicPublicKey = trim($_POST['nic_public_key'] ?? '');

$current = ew_eway_get_settings($conn);
$clientSecret = trim($_POST['client_secret'] ?? '');
if ($clientSecret === '') {
    $clientSecret = $current['client_secret'] ?? '';
}
$apiPassword = trim($_POST['api_password'] ?? '');
if ($apiPassword === '') {
    $apiPassword = $current['api_password'] ?? '';
}

$escEnv = mysqli_real_escape_string($conn, $environment);
$escBase = mysqli_real_escape_string($conn, $apiBase);
$escAuth = mysqli_real_escape_string($conn, $authPath);
$escEway = mysqli_real_escape_string($conn, $ewayPath);
$escGet = mysqli_real_escape_string($conn, $getPath);
$escGstin = mysqli_real_escape_string($conn, $gstin);
$escClientId = mysqli_real_escape_string($conn, $clientId);
$escUser = mysqli_real_escape_string($conn, $apiUsername);
$escTrans = mysqli_real_escape_string($conn, $defaultTransMode);
$escVtype = mysqli_real_escape_string($conn, $defaultVehicleType);
$escPub = mysqli_real_escape_string($conn, $nicPublicKey);
$escSec = mysqli_real_escape_string($conn, ew_data_encrypt($clientSecret));
$escPass = mysqli_real_escape_string($conn, ew_data_encrypt($apiPassword));
$now = mysqli_real_escape_string($conn, date('Y-m-d H:i:s'));

$sql = "UPDATE eway_api_settings SET
    module_enabled=$moduleEnabled,
    environment='$escEnv',
    api_base_url='$escBase',
    auth_path='$escAuth',
    eway_api_path='$escEway',
    get_eway_path='$escGet',
    gstin='$escGstin',
    client_id='$escClientId',
    client_secret_enc='$escSec',
    api_username='$escUser',
    api_password_enc='$escPass',
    nic_public_key='$escPub',
    default_trans_mode='$escTrans',
    default_vehicle_type='$escVtype',
    updated_at='$now',
    updated_by=$userId
    WHERE setting_id=1 LIMIT 1";

if (!mysqli_query($conn, $sql)) {
    echo json_encode(array('ok' => false, 'message' => 'Could not save settings.'));
    exit;
}

echo json_encode(array('ok' => true, 'message' => 'E-Way API settings saved.'));
