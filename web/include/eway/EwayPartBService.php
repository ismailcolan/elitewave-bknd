<?php

require_once __DIR__ . '/EwayNicCrypto.php';
require_once __DIR__ . '/EwayHttpClient.php';
require_once __DIR__ . '/EwayAuthService.php';
require_once __DIR__ . '/partb_validate.php';
require_once __DIR__ . '/partb_errors.php';
require_once __DIR__ . '/eway_schema.php';

class EwayPartBService
{
    private $conn;
    private $settings;
    private $userId;

    public function __construct($conn, array $settings, $userId = 0)
    {
        $this->conn = $conn;
        $this->settings = $settings;
        $this->userId = (int) $userId;
    }

    public function testAuth()
    {
        $auth = new EwayAuthService($this->conn, $this->settings);
        $session = $auth->getSession(true);
        return array(
            'ok' => true,
            'message' => 'Connected to GST E-Way Bill API. Authentication successful.',
            'token_preview' => substr($session['authtoken'], 0, 8) . '…',
        );
    }

    public function loadEwb($ewbNoRaw)
    {
        $ewb = ew_partb_normalize_ewb_no($ewbNoRaw);
        if ($ewb === null) {
            return array('ok' => false, 'message' => 'E-Way Bill number must be exactly 12 digits.');
        }

        $out = array(
            'ok' => true,
            'ewb_no' => $ewb,
            'source' => array(),
            'snapshot' => null,
            'nic' => null,
        );

        $esc = mysqli_real_escape_string($this->conn, $ewb);
        $snapRes = mysqli_query($this->conn, "SELECT * FROM eway_partb_snapshot WHERE ewb_no='$esc' LIMIT 1");
        if ($snapRes && mysqli_num_rows($snapRes) > 0) {
            $out['snapshot'] = mysqli_fetch_assoc($snapRes);
            $out['source'][] = 'local_snapshot';
        }

        try {
            $nic = $this->fetchFromNic($ewb);
            $out['nic'] = $nic;
            $out['source'][] = 'nic_get';
        } catch (Exception $e) {
            $out['nic_error'] = $e->getMessage();
        }

        return $out;
    }

    private function fetchFromNic($ewb)
    {
        $session = $this->getSessionWithRetry();
        $base = rtrim(trim($this->settings['api_base_url'] ?? ''), '/');
        $path = $this->settings['get_eway_path'] ?? '/ewayapi/GetEwayBill';
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        $url = $base . $path . '?ewbNo=' . urlencode($ewb);
        $headers = EwayHttpClient::apiHeaders($this->settings, $session['authtoken']);
        $resp = EwayHttpClient::get($url, $headers);
        $parsed = json_decode($resp['body'], true);
        if (!is_array($parsed)) {
            throw new RuntimeException('Unable to read E-Way Bill details from GST.');
        }

        $vehicleNo = '';
        $validUpto = '';
        if (!empty($parsed['data']) && is_string($parsed['data'])) {
            $innerB64 = EwayNicCrypto::aesDecryptWithSek($parsed['data'], $session['sek']);
            $inner = EwayNicCrypto::decodeInnerJson($innerB64);
            $vehicleNo = trim((string) ($inner['vehicleNo'] ?? $inner['VehNo'] ?? ''));
            $validUpto = trim((string) ($inner['validUpto'] ?? $inner['validTill'] ?? ''));
            return array(
                'vehicle_no' => $vehicleNo,
                'valid_upto' => $validUpto,
                'status' => trim((string) ($inner['status'] ?? '')),
            );
        }

        $vehicleNo = trim((string) ($parsed['vehicleNo'] ?? ''));
        $validUpto = trim((string) ($parsed['validUpto'] ?? ''));
        if ($vehicleNo === '' && $validUpto === '') {
            throw new RuntimeException('GST did not return E-Way Bill details for this number.');
        }
        return array('vehicle_no' => $vehicleNo, 'valid_upto' => $validUpto, 'status' => '');
    }

    public function updatePartB(array $input, $oldVehicleHint = '')
    {
        $validated = ew_partb_validate_update_payload($input);
        if (!$validated['ok']) {
            return array('ok' => false, 'message' => implode(' ', $validated['errors']), 'errors' => $validated['errors']);
        }

        $ewb = $validated['ewb_no'];
        $display = $validated['display'];
        $oldVehicle = trim((string) $oldVehicleHint);
        if ($oldVehicle === '') {
            $esc = mysqli_real_escape_string($this->conn, $ewb);
            $snapRes = mysqli_query($this->conn, "SELECT vehicle_no FROM eway_partb_snapshot WHERE ewb_no='$esc' LIMIT 1");
            if ($snapRes && ($row = mysqli_fetch_assoc($snapRes))) {
                $oldVehicle = trim($row['vehicle_no'] ?? '');
            }
        }

        $nicPayload = $validated['nic_payload'];
        $rawResponse = '';
        $nicStatus = '0';
        $inner = null;

        try {
            $result = $this->callVehewb($nicPayload);
            $rawResponse = $result['raw'];
            $nicStatus = $result['status'];
            $inner = $result['inner'];
        } catch (Exception $e) {
            $this->writeLog($ewb, $oldVehicle, $display, 'fail', $nicStatus, '', $e->getMessage(), '', '', $rawResponse);
            return array('ok' => false, 'message' => $e->getMessage());
        }

        if ($nicStatus !== '1') {
            $err = ew_partb_extract_nic_error(is_array($inner) ? $inner : array());
            $this->writeLog($ewb, $oldVehicle, $display, 'fail', $nicStatus, $err['code'], $err['message'], '', '', $rawResponse);
            return array('ok' => false, 'message' => $err['message'], 'error_code' => $err['code']);
        }

        $vehUpdDate = trim((string) ($inner['vehUpdDate'] ?? ''));
        $validUpto = trim((string) ($inner['validUpto'] ?? ''));

        $dbOk = $this->persistSuccess($ewb, $display['vehicle_no'], $vehUpdDate, $validUpto, $oldVehicle, $display, $nicStatus, $rawResponse);
        if (!$dbOk) {
            return array(
                'ok' => false,
                'message' => 'Part-B was updated on GST, but saving the audit record failed. Contact support with E-Way Bill number ' . $ewb . '.',
                'nic_success' => true,
                'veh_upd_date' => $vehUpdDate,
                'valid_upto' => $validUpto,
            );
        }

        return array(
            'ok' => true,
            'message' => 'Part-B / vehicle number updated successfully on the E-Way Bill system.',
            'ewb_no' => $ewb,
            'old_vehicle' => $oldVehicle,
            'new_vehicle' => $display['vehicle_no'],
            'veh_upd_date' => $vehUpdDate,
            'valid_upto' => $validUpto,
        );
    }

    private function callVehewb(array $nicPayload)
    {
        $session = $this->getSessionWithRetry();
        $json = json_encode($nicPayload, JSON_UNESCAPED_UNICODE);
        $b64 = base64_encode($json);
        $enc = EwayNicCrypto::aesEncryptWithSek($b64, $session['sek']);

        $body = json_encode(array('action' => 'VEHEWB', 'data' => $enc));
        $base = rtrim(trim($this->settings['api_base_url'] ?? ''), '/');
        $path = $this->settings['eway_api_path'] ?? '/ewaybillapi/EwayApi';
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        $url = $base . $path;
        $headers = EwayHttpClient::apiHeaders($this->settings, $session['authtoken']);
        $resp = EwayHttpClient::postJson($url, $headers, $body);
        $raw = $resp['body'];
        $parsed = json_decode($raw, true);
        if (!is_array($parsed)) {
            throw new RuntimeException('Invalid response from GST E-Way Bill API.');
        }

        $status = (string) ($parsed['status'] ?? '0');
        $inner = null;
        if (!empty($parsed['data']) && is_string($parsed['data'])) {
            try {
                $innerB64 = EwayNicCrypto::aesDecryptWithSek($parsed['data'], $session['sek']);
                $inner = EwayNicCrypto::decodeInnerJson($innerB64);
            } catch (Exception $e) {
                if ($status !== '1') {
                    $inner = array('errorMessage' => 'Could not read error details from GST.');
                } else {
                    throw $e;
                }
            }
        }

        return array('status' => $status, 'inner' => $inner, 'raw' => ew_eway_sanitize_log_json($raw));
    }

    private function getSessionWithRetry()
    {
        $auth = new EwayAuthService($this->conn, $this->settings);
        try {
            return $auth->getSession(false);
        } catch (Exception $e) {
            return $auth->getSession(true);
        }
    }

    private function persistSuccess($ewb, $vehicle, $vehUpdDate, $validUpto, $oldVehicle, array $display, $nicStatus, $rawResponse)
    {
        mysqli_begin_transaction($this->conn);
        try {
            $this->writeLog($ewb, $oldVehicle, $display, 'success', $nicStatus, '', '', $vehUpdDate, $validUpto, $rawResponse);

            $escEwb = mysqli_real_escape_string($this->conn, $ewb);
            $escVeh = mysqli_real_escape_string($this->conn, $vehicle);
            $escValid = mysqli_real_escape_string($this->conn, $validUpto);
            $escVehUpd = mysqli_real_escape_string($this->conn, $vehUpdDate);
            $now = mysqli_real_escape_string($this->conn, date('Y-m-d H:i:s'));

            $sql = "INSERT INTO eway_partb_snapshot (ewb_no, vehicle_no, valid_upto, veh_upd_date, last_success_at, updated_at)
                VALUES ('$escEwb','$escVeh','$escValid','$escVehUpd','$now','$now')
                ON DUPLICATE KEY UPDATE vehicle_no='$escVeh', valid_upto='$escValid', veh_upd_date='$escVehUpd',
                last_success_at='$now', updated_at='$now'";
            if (!mysqli_query($this->conn, $sql)) {
                throw new RuntimeException(mysqli_error($this->conn));
            }
            mysqli_commit($this->conn);
            return true;
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            $this->writeLog($ewb, $oldVehicle, $display, 'fail', $nicStatus, 'db', 'NIC success but local audit save failed.', $vehUpdDate, $validUpto, $rawResponse);
            return false;
        }
    }

    private function writeLog($ewb, $oldVehicle, array $display, $status, $nicStatus, $errCode, $errMsg, $vehUpdDate, $validUpto, $rawResponse)
    {
        $escEwb = mysqli_real_escape_string($this->conn, $ewb);
        $escOld = mysqli_real_escape_string($this->conn, $oldVehicle);
        $escNew = mysqli_real_escape_string($this->conn, $display['vehicle_no'] ?? '');
        $escPlace = mysqli_real_escape_string($this->conn, $display['from_place'] ?? '');
        $fromState = (int) ($display['from_state'] ?? 0);
        $escReason = mysqli_real_escape_string($this->conn, $display['reason_code'] ?? '');
        $escRem = mysqli_real_escape_string($this->conn, $display['reason_rem'] ?? '');
        $escMode = mysqli_real_escape_string($this->conn, $display['trans_mode'] ?? '');
        $escVtype = mysqli_real_escape_string($this->conn, $display['vehicle_type'] ?? '');
        $escDoc = mysqli_real_escape_string($this->conn, $display['trans_doc_no'] ?? '');
        $escDocDt = mysqli_real_escape_string($this->conn, $display['trans_doc_date'] ?? '');
        $now = mysqli_real_escape_string($this->conn, date('Y-m-d H:i:s'));
        $uid = (int) $this->userId;
        $escStatus = mysqli_real_escape_string($this->conn, $status);
        $escNic = mysqli_real_escape_string($this->conn, (string) $nicStatus);
        $escCode = mysqli_real_escape_string($this->conn, (string) $errCode);
        $escErr = mysqli_real_escape_string($this->conn, substr((string) $errMsg, 0, 512));
        $escVehUpd = mysqli_real_escape_string($this->conn, $vehUpdDate);
        $escValid = mysqli_real_escape_string($this->conn, $validUpto);
        $escRaw = mysqli_real_escape_string($this->conn, ew_eway_sanitize_log_json($rawResponse));

        mysqli_query($this->conn, "INSERT INTO eway_partb_update_log
            (ewb_no, old_vehicle_no, new_vehicle_no, from_place, from_state, reason_code, reason_rem, trans_mode, vehicle_type,
            trans_doc_no, trans_doc_date, request_at, created_by, status, nic_status, error_code, error_message,
            veh_upd_date, valid_upto, response_json)
            VALUES ('$escEwb','$escOld','$escNew','$escPlace',$fromState,'$escReason','$escRem','$escMode','$escVtype',
            '$escDoc','$escDocDt','$now',$uid,'$escStatus','$escNic','$escCode','$escErr','$escVehUpd','$escValid','$escRaw')");
    }

    public function fetchHistory($limit = 100, $ewbFilter = '')
    {
        $limit = max(1, min(500, (int) $limit));
        $where = '1=1';
        if ($ewbFilter !== '') {
            $ewb = ew_partb_normalize_ewb_no($ewbFilter);
            if ($ewb !== null) {
                $esc = mysqli_real_escape_string($this->conn, $ewb);
                $where = "ewb_no='$esc'";
            }
        }
        $rows = array();
        $res = mysqli_query($this->conn, "SELECT log_id, ewb_no, old_vehicle_no, new_vehicle_no, from_place, from_state,
            reason_code, status, error_message, veh_upd_date, valid_upto, request_at, created_by
            FROM eway_partb_update_log WHERE $where ORDER BY log_id DESC LIMIT $limit");
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            $rows[] = $r;
        }
        return $rows;
    }
}
