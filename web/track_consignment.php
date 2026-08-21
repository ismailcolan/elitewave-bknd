<?php
require_once ('include/connect.php');
require_once ('include/function.php');

$grn_no = trim($_REQUEST['grn_no'] ?? '');
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
    <link rel="stylesheet" href="css/track-consignment.css">
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
                    <div class="track-page-wrap">
                        <div class="track-panel">
                            <div class="track-panel-head">
                                <div>
                                    <h4>Track Consignment</h4>
                                </div>
                            </div>
                            <div class="track-panel-body">
                                <form class="form-horizontal" id="transaction_form">
                                    <div id="response" class="alert alert-danger" style="display:none;">
                                        <div class="message" style="text-align:center"></div>
                                    </div>

                                    <div class="track-search-row">
                                        <div class="track-search-field">
                                            <label>GCN / PNR Number <span class="req">*</span></label>
                                            <input type="text" autocomplete="off" name="grn_no" id="grn_no" value="<?php echo htmlspecialchars($grn_no, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Enter GCN or PNR number">
                                        </div>
                                        <button class="track-search-btn" type="submit" id="search"><i class="fa fa-search" aria-hidden="true"></i> Track Now</button>
                                    </div>

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
                                                          AND booking_status=''
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
$scan_row_count = count($scan_rows);

$tempRow = $grnr;
$tempRow['active_status'] = 1;
$bookedMessage = get_tracking_message($conn, $tempRow);
$booked_date = date('d-m-Y', strtotime($grn_date));
$booked_time = !empty($booking_time) ? date('H:i:s', strtotime($booking_time)) : '';
$is_booked_latest = ($scan_row_count === 0);
$total_scans = $scan_row_count + 1;

$last_updated = trim($booked_date . ' ' . $booked_time);
$delivered_on = '';
if ($scan_row_count > 0) {
    $last_scan = $scan_rows[$scan_row_count - 1];
    $last_ts = strtotime($last_scan['created_at']);
    if ($last_ts) {
        $last_updated = date('d-m-Y, H:i:s', $last_ts);
    }
    foreach (array_reverse($scan_rows) as $sr) {
        if ((int)$sr['status'] === 8) {
            $dts = strtotime($sr['created_at']);
            if ($dts) {
                $delivered_on = date('d M Y (H:i:s)', $dts);
            }
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
                <div class="track-no">Tracking No. <b>#<?php echo htmlspecialchars($grn_no); ?></b></div>
                <span class="track-hero-kicker">your order is</span>
                <h2 class="track-hero-title <?php echo $hero_class; ?>"><?php echo htmlspecialchars($hero_status); ?></h2>
                <div class="track-hero-sub">
                    as on <?php echo htmlspecialchars($last_updated); ?><br>
                    Last updated: <?php echo htmlspecialchars($last_updated); ?>
                </div>
            </div>
            <img class="track-brand-logo" src="images/elite-nav.png" alt="EliteWave 360 Logistics">
        </div>
        <?php if ($is_full_delivery || $is_partial_delivery) { ?>
        <div class="track-feedback" data-tracking="<?php echo htmlspecialchars($grn_no); ?>">
            <p>How was your delivery experience?</p>
            <div class="faces">
                <button type="button" class="face-btn" data-rating="1" title="Very bad">&#128544;</button>
                <button type="button" class="face-btn" data-rating="2" title="Bad">&#128542;</button>
                <button type="button" class="face-btn" data-rating="3" title="Okay">&#128533;</button>
                <button type="button" class="face-btn" data-rating="4" title="Good">&#128578;</button>
                <button type="button" class="face-btn" data-rating="5" title="Great">&#128513;</button>
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
                                                        <div class="hist-item<?php echo $is_booked_latest ? ' latest' : ''; ?>">
                                                            <span class="hist-dot"><i class="fa fa-check"></i></span>
                                                            <div class="hist-time"><?php echo $booked_date; ?><?php echo $booked_time ? ' at ' . $booked_time : ''; ?></div>
                                                            <div class="hist-title"><?php echo get_trans_status(1); ?></div>
                                                            <p class="hist-desc"><?php echo $bookedMessage; ?></p>
                                                        </div>
                                                        <?php
                                                        foreach ($scan_rows as $scan_index => $result_status) {
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

                                                        // ------------------------------------------------------------
// Delivery message override for this scan row
// ------------------------------------------------------------

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

                                                            $date = date('d-m-Y', $timestamp);
                                                            $time = date('H:i:s', $timestamp);

                                                            $is_latest = ($scan_index === $scan_row_count - 1);
                                                            if ($scan_is_partial) {
                                                                $hist_title = 'Partial Delivery – ' . $scan_delivered . '/' . $scan_total . ' Packages Delivered';
                                                            } elseif ($scan_is_full) {
                                                                $hist_title = 'Fully Delivered – ' . $scan_total . '/' . $scan_total . ' Packages';
                                                            } else {
                                                                $hist_title = get_trans_status($re);
                                                            }
                                                            ?>
                                                            <div class="hist-item<?php echo $is_latest ? ' latest' : ''; ?><?php echo $scan_is_partial ? ' partial' : ''; ?>">
                                                                <span class="hist-dot"><i class="fa fa-check"></i></span>
                                                                <div class="hist-time"><?php echo $date; ?><?php echo $time ? ' at ' . $time : ''; ?></div>
                                                                <div class="hist-title"><?php echo htmlspecialchars($hist_title); ?></div>
                                                                <p class="hist-desc"><?php echo $remarks; ?></p>
                                                            </div>
                                                        <?php
                                                        }
                                                        ?>
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
                                            echo '<p class="track-plain-msg">Invalid GCN or PNR number. Please check and try again!</p>';
                                        }
                                    }
                                    ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php require_once ('include/footer.php'); ?>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {
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

            $(document).on('click', '.face-btn', function() {
                var wrap = $(this).closest('.track-feedback');
                var rating = $(this).data('rating');
                var tracking = wrap.data('tracking');
                wrap.find('.face-btn').removeClass('active');
                $(this).addClass('active');
                $.ajax({
                    url: '../api/public/rating.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ tracking_code: tracking, rating: rating })
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
