<?php

function ew_partb_user_message($code, $fallback = '')
{
    $map = array(
        '100' => 'Invalid login credentials for the E-Way Bill API.',
        '101' => 'Authentication failed. Check API settings.',
        '102' => 'Invalid or expired authentication token.',
        '217' => 'E-Way Bill number is invalid.',
        '240' => 'This E-Way Bill cannot be updated (cancelled, expired, or not eligible).',
        '304' => 'Invalid vehicle number format.',
        '305' => 'You are not authorized to update this E-Way Bill.',
        '328' => 'Transport document details are required for this mode of transport.',
    );
    $code = trim((string) $code);
    if ($code !== '' && isset($map[$code])) {
        return $map[$code];
    }
    if ($fallback !== '') {
        return $fallback;
    }
    if ($code !== '') {
        return 'GST E-Way Bill system rejected the update (code ' . $code . ').';
    }
    return 'Vehicle update failed. Please verify the details and try again.';
}

function ew_partb_extract_nic_error(array $inner)
{
    if (isset($inner['errorCodes'])) {
        $codes = $inner['errorCodes'];
        if (is_array($codes)) {
            $codes = implode(',', $codes);
        }
        $msg = isset($inner['errorDesc']) ? (string) $inner['errorDesc'] : '';
        return array('code' => (string) $codes, 'message' => ew_partb_user_message(explode(',', (string) $codes)[0], $msg));
    }
    if (isset($inner['errorCode'])) {
        $c = (string) $inner['errorCode'];
        $m = isset($inner['errorMessage']) ? (string) $inner['errorMessage'] : '';
        return array('code' => $c, 'message' => ew_partb_user_message($c, $m));
    }
    return array('code' => '', 'message' => 'Vehicle update failed.');
}
