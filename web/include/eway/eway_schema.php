<?php

require_once __DIR__ . '/../encryption.php';

function ew_eway_ensure_schema($conn)
{
    $queries = array(
        "CREATE TABLE IF NOT EXISTS eway_api_settings (
            setting_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            module_enabled TINYINT(1) NOT NULL DEFAULT 0,
            environment VARCHAR(16) NOT NULL DEFAULT 'sandbox',
            api_base_url VARCHAR(255) NOT NULL DEFAULT '',
            auth_path VARCHAR(128) NOT NULL DEFAULT '/authenticate',
            eway_api_path VARCHAR(128) NOT NULL DEFAULT '/ewaybillapi/EwayApi',
            get_eway_path VARCHAR(128) NOT NULL DEFAULT '/ewayapi/GetEwayBill',
            gstin VARCHAR(20) NOT NULL DEFAULT '',
            client_id VARCHAR(128) NOT NULL DEFAULT '',
            client_secret_enc TEXT,
            api_username VARCHAR(128) NOT NULL DEFAULT '',
            api_password_enc TEXT,
            nic_public_key TEXT,
            default_trans_mode CHAR(1) NOT NULL DEFAULT '1',
            default_vehicle_type CHAR(1) NOT NULL DEFAULT 'R',
            updated_at DATETIME NULL,
            updated_by INT UNSIGNED NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS eway_auth_cache (
            cache_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            gstin VARCHAR(20) NOT NULL,
            authtoken VARCHAR(64) NOT NULL DEFAULT '',
            sek_enc TEXT,
            app_key_enc TEXT,
            token_expiry DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_gstin (gstin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS eway_partb_update_log (
            log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ewb_no VARCHAR(12) NOT NULL,
            old_vehicle_no VARCHAR(20) NOT NULL DEFAULT '',
            new_vehicle_no VARCHAR(20) NOT NULL DEFAULT '',
            from_place VARCHAR(50) NOT NULL DEFAULT '',
            from_state INT UNSIGNED NOT NULL DEFAULT 0,
            reason_code CHAR(1) NOT NULL DEFAULT '',
            reason_rem VARCHAR(50) NOT NULL DEFAULT '',
            trans_mode CHAR(1) NOT NULL DEFAULT '',
            vehicle_type CHAR(1) NOT NULL DEFAULT '',
            trans_doc_no VARCHAR(15) NOT NULL DEFAULT '',
            trans_doc_date VARCHAR(12) NOT NULL DEFAULT '',
            request_at DATETIME NOT NULL,
            created_by INT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(16) NOT NULL DEFAULT 'fail',
            nic_status VARCHAR(8) NOT NULL DEFAULT '',
            error_code VARCHAR(32) NOT NULL DEFAULT '',
            error_message VARCHAR(512) NOT NULL DEFAULT '',
            veh_upd_date VARCHAR(64) NOT NULL DEFAULT '',
            valid_upto VARCHAR(64) NOT NULL DEFAULT '',
            response_json TEXT,
            KEY idx_ewb (ewb_no),
            KEY idx_request_at (request_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS eway_partb_snapshot (
            ewb_no VARCHAR(12) NOT NULL PRIMARY KEY,
            vehicle_no VARCHAR(20) NOT NULL DEFAULT '',
            valid_upto VARCHAR(64) NOT NULL DEFAULT '',
            veh_upd_date VARCHAR(64) NOT NULL DEFAULT '',
            last_success_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    );

    foreach ($queries as $sql) {
        mysqli_query($conn, $sql);
    }

    $chk = mysqli_query($conn, 'SELECT setting_id FROM eway_api_settings LIMIT 1');
    if ($chk && mysqli_num_rows($chk) === 0) {
        mysqli_query($conn, "INSERT INTO eway_api_settings (module_enabled, environment, api_base_url) VALUES (0, 'sandbox', '')");
    }
}

function ew_eway_get_settings($conn)
{
    ew_eway_ensure_schema($conn);
    $res = mysqli_query($conn, 'SELECT * FROM eway_api_settings ORDER BY setting_id ASC LIMIT 1');
    if (!$res || mysqli_num_rows($res) === 0) {
        return null;
    }
    $row = mysqli_fetch_assoc($res);
    $row['client_secret'] = ew_data_decrypt($row['client_secret_enc'] ?? '');
    $row['api_password'] = ew_data_decrypt($row['api_password_enc'] ?? '');
    unset($row['client_secret_enc'], $row['api_password_enc']);
    return $row;
}

function ew_eway_module_enabled($conn)
{
    $s = ew_eway_get_settings($conn);
    return $s && (int) ($s['module_enabled'] ?? 0) === 1;
}

function ew_eway_require_module($conn)
{
    if (!ew_eway_module_enabled($conn)) {
        return array('ok' => false, 'message' => 'E-Way Part-B module is disabled. Enable it under Tools → E-Way API settings.');
    }
    return array('ok' => true);
}

function ew_eway_sanitize_log_json($raw)
{
    if ($raw === null || $raw === '') {
        return '';
    }
    if (strlen($raw) > 8000) {
        $raw = substr($raw, 0, 8000) . '…';
    }
    return $raw;
}
