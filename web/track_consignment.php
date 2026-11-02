<?php
require_once ('include/connect.php');
require_once ('include/function.php');
require_once ('include/city_transport_endpoint_helpers.php');
require_once ('include/tracking_templates.php');
require_once ('include/tracking_functions.php');
require_once ('include/shipment_rating_helpers.php');

$grn_no = ew_normalize_track_consignment_query($_REQUEST['grn_no'] ?? $_REQUEST['gcn_no'] ?? $_REQUEST['code'] ?? '');
$tracking_code = $grn_no;

?>
<!DOCTYPE html>
<html>

<head>
    <?php include ('include/title.php'); ?>
    <?php include ('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/track-consignment.css?v=20261023nps9">
</head>

<body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
        <!-- Navigation -->
        <div class="navbar navbar-fixed-top scroll-hide">
            <?php
            require_once ('include/header.php');
            require_once ('include/menu.php');
            ?>
        </div>
        <div class="container-fluid main-content new_dpt_bottom track-page-shell">
            <div class="row">
                <div class="col-md-12">
                    <div class="ew-page-v2 ew-page-v2--track">
                        <div class="ew-page-head ew-page-head--center">
                            <div class="ew-page-head-left">
                                <h1 class="ew-page-title">Track Consignment</h1>
                            </div>
                        </div>
                        <div class="ew-card ew-track-search-card">
                            <form class="form-horizontal" id="transaction_form">
                                <div id="response" class="alert alert-danger" style="display:none;">
                                    <div class="message" style="text-align:center"></div>
                                </div>
                                <div class="track-search-row">
                                    <div class="track-search-field">
                                        <label>GCN / Tracking code <span class="req">*</span></label>
                                        <input type="text" autocomplete="off" name="grn_no" id="grn_no" value="<?php echo htmlspecialchars($grn_no, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Enter GCN or tracking code">
                                    </div>
                                    <button class="track-search-btn" type="submit" id="search"><i class="fa fa-search" aria-hidden="true"></i> Track Now</button>
                                </div>
                            </form>
                        </div>
                        <div class="track-page-wrap">
                            <div class="track-panel">
                            <div class="track-panel-body">

                                    <?php
                                    require_once 'include/connect.php';
                                    require_once 'include/function.php';

                                    $add_on = '';
                                    if ($_SESSION['role'] == 'CL') {
                                        $query22 = 'select  * from users  where user_id=' . $_SESSION['user_id'];
                                        $result22 = mysqli_query($conn, $query22) or die(mysqli_error($conn));
                                        $row22 = mysqli_fetch_assoc($result22);

                                        $add_on = ' and (consignee=' . $row22['company_name'] . ' or consigner=' . $row22['company_name'] . ')';
                                    }

                                    if ($grn_no != '') {
                                        $log_check_query = "SELECT * FROM transaction_log
                                                            WHERE grn_no='$grn_no'
                                                            OR tracking_code='$grn_no'";

                                        $log_check_result = mysqli_query($conn, $log_check_query);

                                        if (mysqli_num_rows($log_check_result) > 0) {
                                            $found_consignment = false;
                                            $tbl = $tbl_inv = '';
                                            $tracking_row = mysqli_fetch_assoc($log_check_result);
                                            $grn_no = $tracking_row['grn_no'];
                                            $query2 = 'SELECT * FROM transaction_tbls';
                                            $result2 = mysqli_query($conn, $query2) or die(mysqli_error($conn));
                                            while ($row2 = mysqli_fetch_assoc($result2)) {
                                                $tbl = 'transaction_' . $row2['table_name'];
                                                $tbl_inv = 'transaction_invoice_' . $row2['table_name'];

                                                $query = "SELECT *
                                                          FROM $tbl
                                                          WHERE grn_no='$grn_no'
                                                          AND " . ew_sql_not_cancelled_booking('booking_status') . "
                                                          $add_on";
                                                $result = mysqli_query($conn, $query);
                                                $grnr = mysqli_fetch_array($result);

                                                if (mysqli_num_rows($result) > 0) {
                                                    $found_consignment = true;
                                                    echo '<div class="track-result-title">
                                                                <h2>Tracking Status</h2>
                                                                <small>GCN No. ' . htmlspecialchars($grn_no) . '</small>
                                                            </div>';
                                                    extract($grnr);

                                                    $display_tracking_code = ew_transaction_tracking_code_for_grn($conn, $grn_no);
                                                    $tracking_no_display = ew_format_public_tracking_no($display_tracking_code);
                                                    $saved_customer_rating = $display_tracking_code !== ''
                                                        ? ew_shipment_rating_get($display_tracking_code)
                                                        : null;

                                                    $status = array();
                                                    array_push($status, 1);
                                                    $query = "select * from transaction_status_log where grn_no='$grn_no'";
                                                    $result = mysqli_query($conn, $query);
                                                    while ($row = mysqli_fetch_array($result)) {
                                                        if (!in_array($row['from_status'], $status))
                                                            array_push($status, $row['from_status']);

                                                        if (!in_array($row['to_status'], $status))
                                                            array_push($status, $row['to_status']);
                                                    }
                                                    $max = max($status);
                                                    $remarks = get_cong_remarks($conn, $max, $grn_no);
                                                
                                                $query3 = "select sum(no_of_pkge) as no_of_pkge from  $tbl_inv where transaction_id='$transaction_id'";
                                                    $result3 = mysqli_query($conn, $query3);
                                                    $row3 = mysqli_fetch_array($result3);
                                                // ============================================================
// DELIVERY PACKAGE STATUS
// ============================================================

$total_packages = (int)($row3['no_of_pkge'] ?? 0);

$delivery_type = '';
$delivered_packages = 0;

$delivery_events = array();
$delivery_by_sheet = array();
$partial_events = array();
$safe_grn = mysqli_real_escape_string($conn, $grn_no);

$delivery_q = mysqli_query(
    $conn,
    "SELECT sheet_id, delivery_type, delivered_packages, total_packages
     FROM transaction_status_log
     WHERE grn_no='$safe_grn'
       AND to_status='8'
     ORDER BY sheet_id ASC"
);

if ($delivery_q && mysqli_num_rows($delivery_q) > 0) {

    while ($delivery_r = mysqli_fetch_assoc($delivery_q)) {

        $event_type = strtolower(trim($delivery_r['delivery_type'] ?? ''));
        $event_delivered = (int)($delivery_r['delivered_packages'] ?? 0);
        $event_total = (int)($delivery_r['total_packages'] ?? 0);
        if ($event_total <= 0) {
            $event_total = $total_packages;
        }

        $event = array(
            'sheet_id' => (int)$delivery_r['sheet_id'],
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

    $delivery_r = end($delivery_events);

    $delivery_type = $delivery_r['delivery_type'];
    $delivered_packages = $delivery_r['delivered_packages'];

    if ($total_packages <= 0) {
        $total_packages = $delivery_r['total_packages'];
    }
}

$had_partial_delivery = !empty($partial_events);

// Safety
if ($delivered_packages < 0) {
    $delivered_packages = 0;
}

if ($delivered_packages > $total_packages && $total_packages > 0) {
    $delivered_packages = $total_packages;
}

$pending_packages = max(0, $total_packages - $delivered_packages);

$is_partial_delivery =
    ($delivery_type === 'partial' &&
     $delivered_packages > 0 &&
     $delivered_packages < $total_packages);

$is_full_delivery =
    ($delivery_type === 'full' ||
     ($total_packages > 0 && $delivered_packages >= $total_packages));

// IMPORTANT:
// A partial delivery must NOT make tracking status 8 completed.
$tracking_max = $max;

if ($is_partial_delivery) {
    $tracking_max = 7;
} elseif ($is_full_delivery) {
    $tracking_max = 8;
}
                                                
                                               
                                                   $mode = get_mode($conn, $mode_of_transportation);

$mode_icon = '<i class="fa fa-truck"></i>';

if (stripos($mode, 'air') !== false) {

    $mode_icon = '<i class="fa fa-plane"></i>';

}
elseif (stripos($mode, 'train') !== false) {

    $mode_icon = '<i class="fa fa-train"></i>';

}
elseif (stripos($mode, 'express') !== false) {

    $mode_icon = '<i class="fa fa-shipping-fast"></i>';

}
elseif (
    stripos($mode, 'road') !== false ||
    stripos($mode, 'truck') !== false ||
    stripos($mode, 'load') !== false
) {

    $mode_icon = '<i class="fa fa-truck"></i>';

}

$consignee_name = get_client_name($conn, $consignee);
$consignor_name = get_client_name($conn, $consigner);
$consignee_info = get_client($conn, $consignee);
$consignor_info = get_client($conn, $consigner);
$origin_name = get_city_name($conn, $origin);
$destination_name = get_city_name($conn, $destination);

$customer_phone = !empty($con_phone) ? $con_phone : ($consignee_info['contact_no'] ?? '');
$seller_phone = !empty($phone) ? $phone : ($consignor_info['contact_no'] ?? '');

$delivery_address = '';
if (!empty($shipping_address)) {
    $delivery_address = $shipping_address;
} elseif (!empty($con_address1)) {
    $delivery_address = $con_address1 . (!empty($con_address2) ? ', ' . $con_address2 : '');
} elseif (!empty($consignee_info['address1'])) {
    $delivery_address = $consignee_info['address1'] . (!empty($consignee_info['address2']) ? ', ' . $consignee_info['address2'] : '');
}

$mask_phone = function ($phone) {
    $digits = preg_replace('/\D+/', '', (string) $phone);
    if (strlen($digits) < 6) {
        return $phone;
    }
    return substr($digits, 0, 2) . str_repeat('*', max(4, strlen($digits) - 4)) . substr($digits, -2);
};

$trans_status = "SELECT * FROM `transaction_status` WHERE sheet_id IN(select sheet_id from transaction_status_log where grn_no='$grn_no') ORDER BY created_at ASC, sheet_id ASC";
$res = mysqli_query($conn, $trans_status);
$scan_rows = ($res) ? mysqli_fetch_all($res, MYSQLI_ASSOC) : array();
// Status 1 (booked) is rendered once from GCN booking date/time; sheet rows duplicate it.
$scan_rows = array_values(array_filter($scan_rows, function ($row) {
    return (int) ($row['status'] ?? 0) !== 1;
}));
$scan_row_count = count($scan_rows);

$tempRow = $grnr;
$tempRow['active_status'] = 1;
$bookedMessage = get_tracking_message($conn, $tempRow);
$booked_date = date('d-m-Y', strtotime($grn_date));
$booked_time = !empty($booking_time) ? date('H:i:s', strtotime($booking_time)) : '';

$booked_ts = 0;
if (!empty($grn_date)) {
    $booked_dt_str = trim($grn_date . ' ' . (!empty($booking_time) ? $booking_time : '00:00:00'));
    $booked_ts = strtotime($booked_dt_str) ?: (int) strtotime($grn_date);
}

$history_timeline = array(
    array(
        'ts' => $booked_ts,
        'status_id' => 1,
        'title' => get_trans_status(1),
        'date' => $booked_date,
        'time' => $booked_time,
        'remarks' => $bookedMessage,
        'is_partial' => false,
        'sheet_id' => 0,
    ),
);

foreach ($scan_rows as $result_status) {
    $re = $result_status['status'];
    $date_data = $result_status['created_at'];
    $tempRow = $grnr;
    $tempRow['active_status'] = $re;

    $remarks = get_tracking_message($conn, $tempRow);

    $scan_sheet_id = (int)($result_status['sheet_id'] ?? 0);
    $scan_delivery = isset($delivery_by_sheet[$scan_sheet_id])
        ? $delivery_by_sheet[$scan_sheet_id]
        : null;

    $scan_delivery_type = $scan_delivery
        ? $scan_delivery['delivery_type']
        : '';
    $scan_delivered = $scan_delivery
        ? (int)$scan_delivery['delivered_packages']
        : $delivered_packages;
    $scan_total = $scan_delivery
        ? (int)$scan_delivery['total_packages']
        : $total_packages;
    if ($scan_total <= 0) {
        $scan_total = $total_packages;
    }
    $scan_pending = max(0, $scan_total - $scan_delivered);

    $scan_is_partial = (
        (int)$re === 8 &&
        $scan_delivery_type === 'partial' &&
        $scan_delivered > 0 &&
        ($scan_total <= 0 || $scan_delivered < $scan_total)
    );
    $scan_is_full = (
        (int)$re === 8 &&
        (
            $scan_delivery_type === 'full' ||
            ($scan_total > 0 && $scan_delivered >= $scan_total)
        )
    );

    if ($scan_is_partial) {
        $remarks =
            "Your consignment <strong style=\"color:#000;\">$grn_no</strong> " .
            "has been partially delivered. " .
            "<strong style=\"color:#000;\">" .
            $scan_delivered . '/' . $scan_total .
            " packages</strong> have been delivered successfully. " .
            "<strong style=\"color:#DD111E;\">" .
            $scan_pending .
            " packages are still pending.</strong>";
    } elseif ($scan_is_full && $had_partial_delivery) {
        $remarks =
            "Your consignment <strong style=\"color:#000;\">$grn_no</strong> " .
            "has been fully delivered. " .
            "<strong style=\"color:#000;\">" .
            $scan_total . '/' . $scan_total .
            " packages</strong> have been delivered successfully.";
    }

    $timestamp = strtotime($date_data);
    $date = $timestamp ? date('d-m-Y', $timestamp) : $date_data;
    $time = $timestamp ? date('H:i:s', $timestamp) : '';

    if ($scan_is_partial) {
        $hist_title = 'Partial Delivery – ' . $scan_delivered . '/' . $scan_total . ' Packages Delivered';
    } elseif ($scan_is_full) {
        $hist_title = 'Fully Delivered – ' . $scan_total . '/' . $scan_total . ' Packages';
    } else {
        $hist_title = get_trans_status($re);
    }

    $history_timeline[] = array(
        'ts' => $timestamp ?: 0,
        'status_id' => (int) $re,
        'sheet_id' => (int) ($result_status['sheet_id'] ?? 0),
        'title' => $hist_title,
        'date' => $date,
        'time' => $time,
        'remarks' => $remarks,
        'is_partial' => $scan_is_partial,
    );
}

$hold_carrier = function_exists('ew_mode_offload_kind')
    ? ew_mode_offload_kind($conn, $grnr['mode_of_transportation'] ?? 0)
    : '';
$history_timeline = array_merge($history_timeline, ew_tracking_hold_timeline($conn, $grn_no, $hold_carrier));

ew_tracking_timeline_sort($history_timeline);

$total_scans = count($history_timeline);

$last_updated = trim($booked_date . ' ' . $booked_time);
$delivered_on = '';
if (!empty($history_timeline)) {
    $newest_ts = (int) ($history_timeline[0]['ts'] ?? 0);
    if ($newest_ts > 0) {
        $last_updated = date('d-m-Y, H:i:s', $newest_ts);
    }
    foreach ($history_timeline as $hist_item) {
        if ((int) ($hist_item['status_id'] ?? 0) === 8 && !empty($hist_item['ts'])) {
            $delivered_on = date('d M Y (H:i:s)', $hist_item['ts']);
            break;
        }
    }
}

$expected_delivery = '';
$hours_q = mysqli_query(
    $conn,
    "SELECT max_hrs_delivery FROM mode_of_transportation WHERE mode_id='" . mysqli_real_escape_string($conn, (string)$mode_of_transportation) . "' LIMIT 1"
);
if ($hours_q && ($hours_r = mysqli_fetch_assoc($hours_q))) {
    $hours = (int)$hours_r['max_hrs_delivery'];
    if ($hours > 0 && !empty($grn_date)) {
        $bookingDateTime = $grn_date . (!empty($booking_time) ? ' ' . $booking_time : '');
        $bts = strtotime($bookingDateTime);
        if ($bts) {
            $expected_delivery = date('d M Y (H:i:s)', strtotime("+{$hours} hours", $bts));
        }
    }
}

if ($is_full_delivery) {
    $hero_status = 'Delivered';
    $hero_class = 'delivered';
} elseif ($is_partial_delivery) {
    $hero_status = 'Partially Delivered';
    $hero_class = 'partial';
} else {
    $hero_status = get_trans_status($tracking_max);
    $hero_class = '';
}
?>
<div class="track-layout">
    <aside class="track-sidebar">
        <div class="track-side-block">
            <span class="track-side-label">Consignee Name</span>
            <div class="track-side-value"><?php echo htmlspecialchars($consignee_name); ?></div>
        </div>
        <div class="track-side-block">
            <span class="track-side-label">Consignee Contact</span>
            <div class="track-side-value"><?php echo htmlspecialchars($customer_phone ?: '-'); ?></div>
        </div>
        <div class="track-side-block">
            <span class="track-side-label">Delivery Address</span>
            <div class="track-side-value"><?php echo htmlspecialchars($delivery_address ?: '-'); ?></div>
        </div>
        <div class="track-side-divider"></div>
        <div class="track-side-block">
            <span class="track-side-label">Consignor Name</span>
            <div class="track-side-value"><?php echo htmlspecialchars($consignor_name); ?></div>
        </div>
        <div class="track-side-block">
            <span class="track-side-label">Consignor Phone</span>
            <div class="track-side-value phone-mask">
                <span class="masked-phone"><?php echo htmlspecialchars($seller_phone ? $mask_phone($seller_phone) : '-'); ?></span>
                <?php if ($seller_phone) { ?>
                    <button type="button" class="reveal-phone" data-phone="<?php echo htmlspecialchars($seller_phone); ?>">Tap to Show Number</button>
                <?php } ?>
            </div>
        </div>
        <div class="track-side-divider"></div>
        <div class="track-side-grid">
            <div class="track-side-block">
                <span class="track-side-label">GRN No.</span>
                <div class="track-side-value"><?php echo htmlspecialchars($grn_no); ?></div>
            </div>
            <div class="track-side-block">
                <span class="track-side-label">Booking Date</span>
                <div class="track-side-value"><?php echo htmlspecialchars($grn_date); ?></div>
            </div>
            <div class="track-side-block">
                <span class="track-side-label">Origin</span>
                <div class="track-side-value"><?php echo htmlspecialchars($origin_name); ?></div>
            </div>
            <div class="track-side-block">
                <span class="track-side-label">Destination</span>
                <div class="track-side-value"><?php echo htmlspecialchars($destination_name); ?></div>
            </div>
            <div class="track-side-block">
                <span class="track-side-label">Packages</span>
                <div class="track-side-value"><?php echo (int)$total_packages; ?></div>
            </div>
            <div class="track-side-block">
                <span class="track-side-label">Mode</span>
                <div class="track-side-value"><?php echo $mode_icon . ' ' . htmlspecialchars($mode); ?></div>
            </div>
        </div>
        <div class="track-side-block track-side-status">
            <span class="track-side-label">Expected Delivery</span>
            <?php if ($is_full_delivery && $expected_delivery) { ?>
                <div class="expected-old"><?php echo htmlspecialchars($expected_delivery); ?></div>
            <?php } elseif ($expected_delivery) { ?>
                <div class="track-side-value"><?php echo htmlspecialchars($expected_delivery); ?></div>
            <?php } else { ?>
                <div class="track-side-value">-</div>
            <?php } ?>
            <?php if ($delivered_on) { ?>
                <div class="delivered-on">Delivered on <?php echo htmlspecialchars($delivered_on); ?></div>
            <?php } ?>
        </div>
        <div class="track-side-help">
            <span class="track-side-label">For delivery queries</span>
            <a href="mailto:info@elitewave360.in"><i class="fa fa-envelope"></i> info@elitewave360.in</a>
            <a href="mailto:athar@elitewave360.in"><i class="fa fa-envelope"></i> athar@elitewave360.in</a>
            <a href="tel:+919840859711"><i class="fa fa-phone"></i> +91 98408 59711</a>
            <a href="tel:+919382307611"><i class="fa fa-phone"></i> +91 93823 07611</a>
            <a href="tel:+919625935011"><i class="fa fa-phone"></i> +91 96259 35011</a>
        </div>
    </aside>
    <section class="track-main">
        <div class="track-main-top">
            <div>
                <div class="track-no">Tracking No. <b>#<?php echo $tracking_no_display !== '' ? htmlspecialchars($tracking_no_display) : '—'; ?></b></div>
                <span class="track-hero-kicker">your order is</span>
                <h2 class="track-hero-title <?php echo $hero_class; ?>"><?php echo htmlspecialchars($hero_status); ?></h2>
                <div class="track-hero-sub">
                    as on <?php echo htmlspecialchars($last_updated); ?><br>
                    Last updated: <?php echo htmlspecialchars($last_updated); ?>
                </div>
            </div>
            <img class="track-brand-logo" src="images/elite-nav.png" alt="EliteWave 360 Logistics">
        </div>
        <?php if (($is_full_delivery || $is_partial_delivery) && $display_tracking_code !== '') { ?>
        <div class="track-feedback track-feedback--readonly"
             data-tracking="<?php echo htmlspecialchars($display_tracking_code); ?>"
             data-saved-rating="<?php echo $saved_customer_rating !== null ? (int) $saved_customer_rating : ''; ?>">
            <h6 class="track-feedback-heading">Customer delivery experience rating</h6>
            <div class="nps-scale" role="group" aria-label="Customer delivery rating from 1 to 10">
                <span class="nps-floating-arrow" aria-hidden="true"></span>
                <div class="nps-scale-row">
                <?php for ($nps = 1; $nps <= 10; $nps++) { ?>
                <div class="nps-cell">
                    <button type="button" class="nps-btn nps-btn--<?php echo $nps; ?>" data-rating="<?php echo $nps; ?>" disabled tabindex="-1" aria-label="Customer rated <?php echo $nps; ?> out of 10"><?php echo $nps; ?></button>
                    <span class="nps-selected-tip">Customer rated <?php echo $nps; ?> out of 10</span>
                </div>
                <?php } ?>
                </div>
            </div>
            <div class="track-rating-saved-row">
                <div class="track-rating-saved-inline">
                    <span class="track-rating-saved-text"></span>
                </div>
            </div>
        </div>
        <?php } ?>
        <?php if ($is_partial_delivery) { ?>
            <div class="pkg-banner warn">
                <i class="fa fa-exclamation-circle"></i>
                <?php echo (int)$delivered_packages; ?>/<?php echo (int)$total_packages; ?> Packages Delivered
                <span class="pending"><?php echo (int)$pending_packages; ?> packages pending</span>
            </div>
        <?php } elseif ($is_full_delivery) { ?>
            <div class="pkg-banner ok">
                <i class="fa fa-check-circle"></i>
                <?php echo (int)$total_packages; ?> Packages Delivered
            </div>
        <?php } else { ?>
            <div class="pkg-banner info">
                <i class="fa fa-inbox"></i>
                <?php echo (int)$total_packages; ?> Packages in transit
            </div>
        <?php } ?>


        <div class="hist-card">
            <div class="hist-head">
                <h3>Tracking History</h3>
                <span class="count-pill"><?php echo $total_scans; ?> update<?php echo $total_scans > 1 ? 's' : ''; ?></span>
            </div>
            <div class="hist-list">
                <?php foreach ($history_timeline as $hist_index => $hist_item) {
                    $is_latest = ($hist_index === 0);
                    ?>
                <div class="hist-item<?php echo $is_latest ? ' latest' : ''; ?><?php echo !empty($hist_item['is_partial']) ? ' partial' : ''; ?>">
                    <span class="hist-dot"><i class="fa fa-check"></i></span>
                    <div class="hist-time"><?php echo htmlspecialchars($hist_item['date']); ?><?php echo !empty($hist_item['time']) ? ' at ' . htmlspecialchars($hist_item['time']) : ''; ?></div>
                    <div class="hist-title"><?php echo htmlspecialchars($hist_item['title']); ?></div>
                    <p class="hist-desc"><?php echo $hist_item['remarks']; ?></p>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
</div>

                                        <?php
                                                }
                                            }

                                            if (!$found_consignment) {
                                                echo '<p class="track-plain-msg">Incorrect GCN No or Booking Cancelled. Please check and try again!</p>';
                                            }
                                        } else {
                                            echo '<p class="track-plain-msg">Invalid GCN or tracking code. Please check and try again!</p>';
                                        }
                                    }
                                    ?>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <?php require_once ('include/footer.php'); ?>
    </div>

    <script type="text/javascript">
        function normalizeTrackInput(val) {
            val = $.trim(String(val || ''));
            if (/^[\d\s]+$/.test(val)) {
                val = val.replace(/\s+/g, '');
            }
            return val;
        }

        $(document).ready(function() {
            $('#transaction_form').on('submit', function() {
                var $in = $('#grn_no');
                $in.val(normalizeTrackInput($in.val()));
            });

            $(window).load(function() {
                $(".loading-page").hide();
            });

            $(document).on('click', '.reveal-phone', function() {
                var phone = $(this).data('phone');
                var label = $(this).siblings('.masked-phone');
                if ($(this).hasClass('shown')) {
                    return;
                }
                label.text(phone);
                $(this).addClass('shown').text('Hide Number').off('click').on('click', function() {
                    if ($(this).hasClass('shown')) {
                        var digits = String(phone).replace(/\D+/g, '');
                        var masked = digits.length >= 6
                            ? digits.slice(0, 2) + '****' + digits.slice(-2)
                            : phone;
                        label.text(masked);
                        $(this).removeClass('shown').text('Tap to Show Number');
                    } else {
                        label.text(phone);
                        $(this).addClass('shown').text('Hide Number');
                    }
                });
            });

            function positionNpsFloatingArrow($scale, $cell) {
                var $arrow = $scale.find('.nps-floating-arrow');
                if (!$cell || !$cell.length) {
                    return;
                }
                var $row = $scale.find('.nps-scale-row');
                var rowLeft = $row.offset().left;
                var cellLeft = $cell.offset().left;
                var center = cellLeft - rowLeft + ($cell.outerWidth() / 2);
                $arrow.css({ left: center + 'px', opacity: 1 });
            }

            function hideNpsFloatingArrow($scale) {
                $scale.find('.nps-floating-arrow').css({ opacity: 0 });
            }

            var trackRatingSaveInFlight = false;

            function isReadonlyTrackFeedback($wrap) {
                return $wrap.hasClass('track-feedback--readonly');
            }

            function updateTrackFeedbackSummary($wrap, rating, options) {
                options = options || {};
                rating = parseInt(rating, 10) || 0;
                var readonly = isReadonlyTrackFeedback($wrap);
                var $scale = $wrap.find('.nps-scale');
                var locked = $scale.hasClass('is-locked');
                var editing = !readonly && ($wrap.hasClass('is-editing') || !!options.editing);
                var $heading = $wrap.find('.track-feedback-heading');
                var $row = $wrap.find('.track-rating-saved-row');
                var $saved = $wrap.find('.track-rating-saved-text');

                if (readonly) {
                    if (rating > 0 && locked) {
                        $heading.text('Customer delivery experience rating');
                        $row.addClass('is-visible');
                        $saved.text('Customer rated this delivery ' + rating + '/10 (submitted on public tracking page)');
                    } else {
                        $heading.text('Customer delivery experience rating');
                        $row.addClass('is-visible');
                        $saved.text('No customer rating submitted yet.');
                    }
                    return;
                }

                if (rating > 0 && (locked || editing)) {
                    $heading.text(editing ? 'Update your delivery experience rating' : 'Your delivery experience rating');
                    $row.addClass('is-visible').toggleClass('is-editing', editing);
                    $saved.text('You rated this delivery ' + rating + '/10');
                } else {
                    $heading.text('How was your delivery experience?');
                    $row.removeClass('is-visible is-editing');
                    $saved.text('');
                }
            }

            function applyTrackFeedbackSelection($wrap, rating) {
                rating = parseInt(rating, 10);
                if (!rating || rating < 1 || rating > 10) {
                    return;
                }
                var $scale = $wrap.find('.nps-scale');
                $wrap.find('.nps-btn').removeClass('active');
                $wrap.find('.nps-cell').removeClass('is-active is-hover-preview');
                var $btn = $wrap.find('.nps-btn[data-rating="' + rating + '"]');
                var $cell = $btn.closest('.nps-cell');
                $btn.addClass('active');
                $cell.addClass('is-active');
                $wrap.addClass('has-selection').removeClass('is-editing');
                $scale.addClass('is-locked').removeClass('is-hovering');
                positionNpsFloatingArrow($scale, $cell);
                updateTrackFeedbackSummary($wrap, rating, {});
            }

            function unlockTrackFeedbackForEdit($wrap) {
                var rating = parseInt($wrap.find('.nps-btn.active').data('rating'), 10) || 0;
                if (!rating) {
                    return;
                }
                var $scale = $wrap.find('.nps-scale');
                $scale.removeClass('is-locked');
                $wrap.addClass('is-editing');
                hideNpsFloatingArrow($scale);
                updateTrackFeedbackSummary($wrap, rating, { editing: true });
            }

            function clearTrackFeedbackSelection($wrap) {
                var $scale = $wrap.find('.nps-scale');
                $wrap.removeClass('has-selection is-editing');
                $scale.removeClass('is-locked is-hovering');
                $wrap.find('.nps-btn').removeClass('active');
                $wrap.find('.nps-cell').removeClass('is-active is-hover-preview');
                hideNpsFloatingArrow($scale);
                updateTrackFeedbackSummary($wrap, 0, {});
            }

            function initTrackFeedbackRating($wrap) {
                if (isReadonlyTrackFeedback($wrap)) {
                    var saved = parseInt($wrap.data('saved-rating'), 10);
                    if (saved >= 1 && saved <= 10) {
                        applyTrackFeedbackSelection($wrap, saved);
                    } else {
                        updateTrackFeedbackSummary($wrap, 0, {});
                    }
                    return;
                }
                var tracking = $wrap.data('tracking');
                if (!tracking) {
                    return;
                }
                $.getJSON('../api/public/rating.php', { tracking_code: tracking }, function(r) {
                    if (r && r.status === 'success' && r.rating) {
                        applyTrackFeedbackSelection($wrap, r.rating);
                    }
                });
            }

            $('.track-feedback').each(function() {
                initTrackFeedbackRating($(this));
            });

            function npsCellAtPointer($scale, pageX) {
                var $hit = null;
                $scale.find('.nps-cell').each(function() {
                    var $cell = $(this);
                    var off = $cell.offset();
                    var left = off.left;
                    var right = left + $cell.outerWidth();
                    if (pageX >= left && pageX <= right) {
                        $hit = $cell;
                        return false;
                    }
                });
                return $hit;
            }

            $(document).on('mousemove', '.track-feedback .nps-scale', function(e) {
                var $scale = $(this);
                if ($(this).closest('.track-feedback--readonly').length) {
                    return;
                }
                if ($scale.hasClass('is-locked')) {
                    return;
                }
                var $cell = npsCellAtPointer($scale, e.pageX);
                if (!$cell) {
                    return;
                }
                $scale.addClass('is-hovering');
                $scale.find('.nps-cell').removeClass('is-hover-preview');
                $cell.addClass('is-hover-preview');
                positionNpsFloatingArrow($scale, $cell);
            });

            $(document).on('mouseleave', '.track-feedback .nps-scale', function() {
                var $scale = $(this);
                if ($scale.hasClass('is-locked')) {
                    return;
                }
                $scale.removeClass('is-hovering');
                $scale.find('.nps-cell').removeClass('is-hover-preview');
                hideNpsFloatingArrow($scale);
            });

            $(document).on('click', '.track-rating-edit-btn', function() {
                var wrap = $(this).closest('.track-feedback');
                if (isReadonlyTrackFeedback(wrap)) {
                    return;
                }
                if (trackRatingSaveInFlight || !wrap.find('.nps-scale').hasClass('is-locked')) {
                    return;
                }
                unlockTrackFeedbackForEdit(wrap);
            });

            $(document).on('click', '.nps-btn', function() {
                var $btn = $(this);
                var wrap = $btn.closest('.track-feedback');
                if (isReadonlyTrackFeedback(wrap)) {
                    return;
                }
                var $scale = wrap.find('.nps-scale');
                if ($scale.hasClass('is-locked') || trackRatingSaveInFlight) {
                    return;
                }
                var rating = parseInt($btn.data('rating'), 10);
                var tracking = wrap.data('tracking');
                if (!tracking || rating < 1 || rating > 10) {
                    return;
                }
                var previousCommitted = parseInt(wrap.find('.nps-btn.active').data('rating'), 10) || 0;
                var wasEditing = wrap.hasClass('is-editing');
                if (wasEditing && rating === previousCommitted) {
                    wrap.removeClass('is-editing');
                    applyTrackFeedbackSelection(wrap, rating);
                    return;
                }
                trackRatingSaveInFlight = true;
                applyTrackFeedbackSelection(wrap, rating);
                $.ajax({
                    url: '../api/public/rating.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ tracking_code: tracking, rating: rating })
                }).done(function(r) {
                    if (!r || r.status !== 'success') {
                        if (wasEditing && previousCommitted) {
                            applyTrackFeedbackSelection(wrap, previousCommitted);
                        } else {
                            clearTrackFeedbackSelection(wrap);
                        }
                        wrap.find('.track-rating-saved-text').text('Could not save your rating. Please try again.');
                    }
                }).fail(function() {
                    if (wasEditing && previousCommitted) {
                        applyTrackFeedbackSelection(wrap, previousCommitted);
                    } else {
                        clearTrackFeedbackSelection(wrap);
                    }
                    wrap.find('.track-rating-saved-text').text('Could not save your rating. Please try again.');
                    if (typeof ewFormToast === 'function') {
                        ewFormToast('Could not save your rating. Please try again.', 'error', 4000);
                    }
                }).always(function() {
                    trackRatingSaveInFlight = false;
                });
            });

            $(window).on('resize', function() {
                $('.track-feedback .nps-scale.is-locked').each(function() {
                    var $scale = $(this);
                    var $cell = $scale.find('.nps-cell.is-active');
                    if ($cell.length) {
                        positionNpsFloatingArrow($scale, $cell);
                    }
                });
            });
        });

        function isNumber(evt, element) {
            var charCode = (evt.which) ? evt.which : event.keyCode

            if ((charCode != 45 || $(element).val().indexOf('-') != -1) &&
                (charCode != 46 || $(element).val().indexOf('.') != -1) &&
                (charCode < 48 || charCode > 57))
                return false;
            return true;
        }
    </script>
    <div class="alert" id="alert-container" style="display:none;">
        <button type="button" class="close" data-dismiss="alert">x</button>
        <strong id="alert-status"></strong>
        <span id="alert-message"></span>
    </div>

    <div class="modal fade popup_close" id="myModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                    <h4 class="modal-title" style="color:#fff">
                        Alert!
                    </h4>
                </div>

                <div class="modal-body">
                    <h5 text-align="center">
                        Do you want to Delete This Record ?
                    </h5>
                    <div class="modal-footer">
                        <button class="btn btn-primary btn-confirm-delete" data-dismiss="modal" type="button" id="">Yes</button>
                        <button class="btn btn-default-outline" data-dismiss="modal" type="button" id="">No</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="delete-error-popup">
        <div class="popup_overlay" id="popup_overlay"></div>
        <div class="popup" id="popup">
            <div class="popup_message">
                <h5 class="popup-title">Alert ! </h5>
                This Data Cannot Delete.Used by another record. so you can't Delete !!! <br /> &nbsp; <br />
                <button class="btn btn-sm btn-danger delete-error-popup-close" id="">Close</button> <br /> &nbsp; <br />
            </div>
        </div>
    </div>

    <div class="modal fade popup_close" id="eway_popup" style="display:none">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                    <h4 class="modal-title" style="color:#fff">
                        Add Attachments
                    </h4>
                </div>

                <div class="modal-body" id="attachment_body">

                </div>
            </div>
        </div>
    </div>

</body>

</html>
