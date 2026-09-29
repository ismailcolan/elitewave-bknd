<?php

function ew_partb_normalize_ewb_no($raw)
{
    $digits = preg_replace('/\D/', '', (string) $raw);
    if (strlen($digits) !== 12) {
        return null;
    }
    return $digits;
}

function ew_partb_normalize_vehicle($raw)
{
    $v = strtoupper(trim(preg_replace('/\s+/', '', (string) $raw)));
    if ($v === '') {
        return '';
    }
    if (strlen($v) < 7 || strlen($v) > 15) {
        return null;
    }
    return $v;
}

function ew_partb_validate_update_payload(array $in)
{
    $errors = array();

    $ewb = ew_partb_normalize_ewb_no($in['ewb_no'] ?? $in['ewbNo'] ?? '');
    if ($ewb === null) {
        $errors[] = 'E-Way Bill number must be exactly 12 digits.';
    }

    $vehicle = ew_partb_normalize_vehicle($in['vehicle_no'] ?? $in['vehicleNo'] ?? '');
    if ($vehicle === null || $vehicle === '') {
        $errors[] = 'Vehicle number is required (7–15 characters, valid format).';
    }

    $fromPlace = trim((string) ($in['from_place'] ?? $in['fromPlace'] ?? ''));
    if ($fromPlace === '' || strlen($fromPlace) > 50) {
        $errors[] = 'From place is required (max 50 characters).';
    }

    $fromState = (int) ($in['from_state'] ?? $in['fromState'] ?? 0);
    if ($fromState < 1 || $fromState > 99) {
        $errors[] = 'From state is required.';
    }

    $reasonCode = trim((string) ($in['reason_code'] ?? $in['reasonCode'] ?? ''));
    if (!preg_match('/^[1-4]$/', $reasonCode)) {
        $errors[] = 'Reason code must be 1, 2, 3, or 4.';
    }

    $reasonRem = trim((string) ($in['reason_rem'] ?? $in['reasonRem'] ?? ''));
    if ($reasonRem === '' || strlen($reasonRem) > 50) {
        $errors[] = 'Reason remarks are required (max 50 characters).';
    }

    $transMode = trim((string) ($in['trans_mode'] ?? $in['transMode'] ?? '1'));
    if (!preg_match('/^[1-4]$/', $transMode)) {
        $errors[] = 'Transport mode must be 1 (Road), 2 (Rail), 3 (Air), or 4 (Ship).';
    }

    $vehicleType = strtoupper(trim((string) ($in['vehicle_type'] ?? $in['vehicleType'] ?? 'R')));
    if ($vehicleType === '') {
        $vehicleType = 'R';
    }
    if (!in_array($vehicleType, array('R', 'O'), true)) {
        $errors[] = 'Vehicle type must be R (Regular) or O (ODC).';
    }

    $transDocNo = trim((string) ($in['trans_doc_no'] ?? $in['transDocNo'] ?? ''));
    $transDocDate = trim((string) ($in['trans_doc_date'] ?? $in['transDocDate'] ?? ''));

    if ($transMode === '1') {
        if ($vehicle === '') {
            $errors[] = 'Vehicle number is required for road transport.';
        }
    } else {
        if ($transDocNo === '' || strlen($transDocNo) > 15) {
            $errors[] = 'Transport document number is required for this mode (max 15 characters).';
        }
        if ($transDocDate === '' || !preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/20[0-9]{2}$/', $transDocDate)) {
            $errors[] = 'Transport document date is required (dd/mm/yyyy).';
        }
    }

    if (!empty($errors)) {
        return array('ok' => false, 'errors' => $errors);
    }

    $payload = array(
        'ewbNo' => (int) $ewb,
        'vehicleNo' => $vehicle,
        'fromPlace' => $fromPlace,
        'fromState' => $fromState,
        'reasonCode' => $reasonCode,
        'reasonRem' => $reasonRem,
        'transMode' => $transMode,
        'vehicleType' => $vehicleType,
    );
    if ($transDocNo !== '') {
        $payload['transDocNo'] = $transDocNo;
    }
    if ($transDocDate !== '') {
        $payload['transDocDate'] = $transDocDate;
    }

    return array(
        'ok' => true,
        'ewb_no' => $ewb,
        'nic_payload' => $payload,
        'display' => array(
            'vehicle_no' => $vehicle,
            'from_place' => $fromPlace,
            'from_state' => $fromState,
            'reason_code' => $reasonCode,
            'reason_rem' => $reasonRem,
            'trans_mode' => $transMode,
            'vehicle_type' => $vehicleType,
            'trans_doc_no' => $transDocNo,
            'trans_doc_date' => $transDocDate,
        ),
    );
}
