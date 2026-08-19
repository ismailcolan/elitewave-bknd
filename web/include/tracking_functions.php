<?php

function get_client_phone($conn, $id)
{
    if (!$id) return '';
    $q = "SELECT contact_no FROM client WHERE client_id='$id'";
    $r = mysqli_query($conn, $q);
    $row = mysqli_fetch_assoc($r);
    return $row ? $row['contact_no'] : '';
}

function get_client_address($conn, $id)
{
    if (!$id) return '';
    $q = "SELECT address1, address2 FROM client WHERE client_id='$id'";
    $r = mysqli_query($conn, $q);
    $row = mysqli_fetch_assoc($r);
    if (!$row) return '';
    $addr = $row['address1'];
    if (!empty($row['address2'])) $addr .= ', ' . $row['address2'];
    return $addr;
}

function get_expected_delivery($conn, $origin, $destination, $mode, $grn_date)
{
    if (empty($grn_date)) return '';

    $orig_city = get_city_name($conn, $origin);
    $dest_city = get_city_name($conn, $destination);

    if (empty($orig_city) || empty($dest_city)) return '';

    $q = "SELECT surface, express, train, air FROM expectded_delivery
          WHERE origin LIKE '%$orig_city%' AND destination LIKE '%$dest_city%'
          LIMIT 1";
    $r = mysqli_query($conn, $q);
    $row = mysqli_fetch_assoc($r);

    if (!$row) return '';

    $days = '';
    $mode_lower = strtolower($mode);
    if (strpos($mode_lower, 'surface') !== false || strpos($mode_lower, 'road') !== false) {
        $days = $row['surface'];
    } elseif (strpos($mode_lower, 'express') !== false) {
        $days = $row['express'];
    } elseif (strpos($mode_lower, 'train') !== false) {
        $days = $row['train'];
    } elseif (strpos($mode_lower, 'air') !== false) {
        $days = $row['air'];
    } else {
        $days = $row['surface'];
    }

    if (empty($days) || !is_numeric($days)) return '';

    $parts = explode('-', $grn_date);
    if (count($parts) !== 3) return '';
    $ts = strtotime($parts[1] . '-' . $parts[0] . '-' . $parts[2]);
    if (!$ts) return '';
    $expected = strtotime("+$days days", $ts);
    return date('d-m-Y', $expected);
}

function get_tracking_data($conn, $grn_no, $tracking_code)
{
    $log_check_query = "SELECT * FROM transaction_log WHERE grn_no='$grn_no' AND tracking_code='$tracking_code'";
    $log_check_result = mysqli_query($conn, $log_check_query);

    if (!$log_check_result || mysqli_num_rows($log_check_result) == 0) {
        return null;
    }

    $found = false;
    $query2 = "SELECT * FROM transaction_tbls";
    $result2 = mysqli_query($conn, $query2);

    while ($row2 = mysqli_fetch_assoc($result2)) {
        $tbl = "transaction_" . $row2['table_name'];
        $tbl_inv = "transaction_invoice_" . $row2['table_name'];
        $tbl = rtrim($tbl, ",");
        $tbl_inv = rtrim($tbl_inv, ",");

        $query = "select * from $tbl where grn_no='$grn_no' and booking_status = ''";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $found = true;
            $grnr = mysqli_fetch_array($result);

            $transaction_id = $grnr['transaction_id'];
            $query3 = "select sum(no_of_pkge) as no_of_pkge from $tbl_inv where transaction_id='$transaction_id'";
            $result3 = mysqli_query($conn, $query3);
            $row3 = mysqli_fetch_array($result3);
        // ============================================================
// DELIVERY PACKAGE STATUS
// ============================================================

$total_packages = (int)($row3['no_of_pkge'] ?? 0);

$delivery_type = '';
$delivered_packages = 0;

// Get latest delivery information
$delivery_events = array();
$delivery_by_sheet = array();
$partial_events = array();

$delivery_query = "
    SELECT sheet_id, delivery_type, delivered_packages, total_packages
    FROM transaction_status_log
    WHERE grn_no = '$grn_no'
      AND to_status = '8'
    ORDER BY sheet_id ASC
";

$delivery_result = mysqli_query($conn, $delivery_query);

if ($delivery_result) {

    while ($delivery_row = mysqli_fetch_assoc($delivery_result)) {

        $event_type = strtolower(trim($delivery_row['delivery_type'] ?? ''));
        $event_delivered = (int)($delivery_row['delivered_packages'] ?? 0);
        $event_total = (int)($delivery_row['total_packages'] ?? 0);
        if ($event_total <= 0) {
            $event_total = $total_packages;
        }

        $event = array(
            'sheet_id' => (int)$delivery_row['sheet_id'],
            'delivery_type' => $event_type,
            'delivered_packages' => $event_delivered,
            'total_packages' => $event_total,
        );

        $delivery_events[] = $event;
        $delivery_by_sheet[$event['sheet_id']] = $event;

        if ($event_type === 'partial' && $event_delivered > 0 && ($event_total <= 0 || $event_delivered < $event_total)) {
            $partial_events[] = $event;
        }
    }

    if (!empty($delivery_events)) {
        $delivery_row = end($delivery_events);

        $delivery_type = $delivery_row['delivery_type'];
        $delivered_packages = $delivery_row['delivered_packages'];

        if ($total_packages <= 0) {
            $total_packages = $delivery_row['total_packages'];
        }
    }
}

$had_partial_delivery = !empty($partial_events);

// Calculate pending packages
$pending_packages =
    max(0, $total_packages - $delivered_packages);

// Partial delivery
$is_partial_delivery =
    (
        $delivery_type === 'partial' &&
        $delivered_packages > 0 &&
        $delivered_packages < $total_packages
    );

// Full delivery
$is_full_delivery =
    (
        $delivery_type === 'full' ||
        (
            $total_packages > 0 &&
            $delivered_packages >= $total_packages
        )
    );

            $consignee_phone = !empty($grnr['con_phone']) ? $grnr['con_phone'] : get_client_phone($conn, $grnr['consignee']);

            $consignee_address = '';
            if (!empty($grnr['con_address1'])) {
                $consignee_address = $grnr['con_address1'];
                if (!empty($grnr['con_address2'])) $consignee_address .= ', ' . $grnr['con_address2'];
            } else {
                $consignee_address = get_client_address($conn, $grnr['consignee']);
            }

            $consignor_phone = !empty($grnr['phone']) ? $grnr['phone'] : get_client_phone($conn, $grnr['consigner']);

            $expected_delivery = '';

$mode_id = $grnr['mode_of_transportation'];

$q = mysqli_query($conn,"
SELECT max_hrs_delivery
FROM mode_of_transportation
WHERE mode_id='$mode_id'
LIMIT 1
");

if($r = mysqli_fetch_assoc($q)){

    $hours = (int)$r['max_hrs_delivery'];

    if(!empty($grnr['grn_date'])){

        $bookingDateTime = $grnr['grn_date'];

        if(!empty($grnr['booking_time'])){
            $bookingDateTime .= ' '.$grnr['booking_time'];
        }

        $timestamp = strtotime($bookingDateTime);

        if($timestamp){

            $expected_delivery = date(
                'd-m-Y H:i:s',
                strtotime("+{$hours} hours",$timestamp)
            );

        }

    }

}

            $consignment_details = array(
                'grn_no'              => $grn_no,
                'tracking_code'       => $tracking_code,
                'consignor'           => get_client_name($conn, $grnr['consigner']),
                'consignee'           => get_client_name($conn, $grnr['consignee']),
                'origin'              => get_city_name($conn, $grnr['origin']),
                'destination'         => get_city_name($conn, $grnr['destination']),
                'mode'                => get_mode($conn, $grnr['mode_of_transportation']),
                'mode_of_consignment' => consignment_mode($conn, $grnr['mode_of_consignment']),
                'grn_date'            => $grnr['grn_date'],
            'booking_time' => !empty($grnr['booking_time'])
    ? date('H:i:s', strtotime($grnr['booking_time']))
    : '',
                'no_of_packages'      => $total_packages,
'delivered_packages'  => $delivered_packages,
'pending_packages'    => $pending_packages,
'is_partial_delivery' => $is_partial_delivery,
'is_full_delivery'    => $is_full_delivery,
'delivery_type'       => $delivery_type,
                'consignee_phone'     => $consignee_phone,
                'consignee_address'   => $consignee_address,
                'consignor_phone'     => $consignor_phone,
                'expected_delivery'   => $expected_delivery,
                'shipping_address'    => $grnr['shipping_address'],
            );

            $tracking_history = array();
            $trans_status_query = "SELECT * FROM `transaction_status` WHERE sheet_id IN (select sheet_id from transaction_status_log where grn_no='$grn_no')";
            $res_status = mysqli_query($conn, $trans_status_query);

            if ($res_status) {
                while ($result_status = mysqli_fetch_assoc($res_status)) {
                    $timestamp = strtotime($result_status['created_at']);
                    if ($timestamp) {
                        $date = date('d-m-Y', $timestamp);
                        $time = date('H:i:s', $timestamp);
                    } else {
                        $date = $result_status['created_at'];
                        $time = '';
                    }

                    $history_status_id = (int) $result_status['status'];
                    $history_status = get_trans_status($result_status['status']);
                    $history_details = $result_status['remarks'];
                    $history_sheet_id = (int)($result_status['sheet_id'] ?? 0);
                    $history_is_partial = false;
                    $history_is_full = false;

                    if ($history_status_id === 8 && isset($delivery_by_sheet[$history_sheet_id])) {
                        $history_delivery = $delivery_by_sheet[$history_sheet_id];
                        $history_delivered = (int)$history_delivery['delivered_packages'];
                        $history_total = (int)$history_delivery['total_packages'];
                        if ($history_total <= 0) {
                            $history_total = $total_packages;
                        }
                        $history_pending = max(0, $history_total - $history_delivered);

                        if (
                            $history_delivery['delivery_type'] === 'partial' &&
                            $history_delivered > 0 &&
                            ($history_total <= 0 || $history_delivered < $history_total)
                        ) {
                            $history_is_partial = true;
                            $history_status =
                                'Partial Delivery - ' .
                                $history_delivered . '/' .
                                $history_total .
                                ' Packages Delivered';
                            $history_details =
                                "Your consignment $grn_no has been partially delivered. " .
                                "$history_delivered/$history_total packages have been delivered successfully. " .
                                "$history_pending packages are still pending.";
                        } elseif (
                            $history_delivery['delivery_type'] === 'full' ||
                            ($history_total > 0 && $history_delivered >= $history_total)
                        ) {
                            $history_is_full = true;
                            $history_status =
                                'Fully Delivered - ' .
                                $history_total . '/' .
                                $history_total .
                                ' Packages';
                        }
                    }

                    $tracking_history[] = array(
                        'status'      => $history_status,
                        'status_id'   => $history_status_id,
                        'details'     => $history_details,
                        'date'        => $date,
                        'time'        => $time,
                        'origin'      => get_city_name($conn, $result_status['origin']),
                        'destination' => get_city_name($conn, $result_status['destination']),
                        'is_partial'  => $history_is_partial,
                        'is_full'     => $history_is_full,
                    );
                }
            }

            $status_log = array();
            array_push($status_log, 1);
        $timeline_steps = array();

foreach ($status_log as $stepId) {
    $timeline_steps[] = array(
        'step_id'     => (int) $stepId,
        'status_name' => get_trans_status($stepId),
    );
}
            $query_log = "select * from transaction_status_log where grn_no='$grn_no'";
            $result_log = mysqli_query($conn, $query_log);
            if ($result_log) {
                while ($rlog = mysqli_fetch_array($result_log)) {
                    if (!in_array($rlog['from_status'], $status_log)) {
                        array_push($status_log, $rlog['from_status']);
                    }
                    if (!in_array($rlog['to_status'], $status_log)) {
                        array_push($status_log, $rlog['to_status']);
                    }
                }
            }

            $timeline_steps = array();
            foreach ($status_log as $stepId) {
                $timeline_steps[] = array(
                    'step_id'     => (int) $stepId,
                    'status_name' => get_trans_status($stepId),
                );
            }
        
        // ============================================================
// CURRENT TRACKING STATUS
// ============================================================

$current_status_id = empty($status_log)
    ? 1
    : max($status_log);

$current_status_name = get_trans_status($current_status_id);


// PARTIAL DELIVERY
// Delivery is NOT completed yet.
if ($is_partial_delivery) {

    $current_status_id = 7;

    $current_status_name =
        'Partial Delivery - ' .
        $delivered_packages .
        '/' .
        $total_packages .
        ' Packages Delivered';
}


// FULL DELIVERY
// Only 100% delivery becomes completed.
elseif ($is_full_delivery) {

    $current_status_id = 8;

    $current_status_name =
        'Fully Delivered - ' .
        $total_packages .
        '/' .
        $total_packages .
        ' Packages';
}

            return array(
    'consignment_details' => $consignment_details,

    'tracking_history' => $tracking_history,

    'status_timeline' => $timeline_steps,

    'current_status_id' => $current_status_id,

    'current_status_name' => $current_status_name,

    'delivery_status' => array(
        'total_packages' => $total_packages,

        'delivered_packages' => $delivered_packages,

        'pending_packages' => $pending_packages,

        'delivery_type' => $delivery_type,

        'is_partial' => $is_partial_delivery,

        'is_full' => $is_full_delivery,

        'had_partial' => $had_partial_delivery,

        'delivery_events' => $delivery_events
    )
);
        }
    }

    return null;
}

