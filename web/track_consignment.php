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
    <style>
        *,
        *:after,
        *:before {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --ew-primary: #123b63;
            --ew-primary-dark: #0b2946;
            --ew-primary-soft: #eaf2f8;
            --ew-accent: #e52432;
            --ew-accent-soft: #fff1f2;
            --ew-green: #1dbf73;
            --ew-green-soft: #e8f8f0;
            --ew-red: #c0392b;
            --ew-gray-900: #183247;
            --ew-gray-600: #607789;
            --ew-gray-400: #8ca0af;
            --ew-gray-200: #dbe5eb;
            --ew-gray-100: #f4f7f9;
            --ew-radius: 10px;
        }

        /* ============================================================
           Page shell — fills the available width instead of being
           capped and centered.
           ============================================================ */
        body.bg-1 {
            background: #f4f7f9;
        }
        .track-page-wrap {
            width: 100%;
            max-width: 100%;
            margin: 32px 0 60px;
            padding: 0 20px;
        }
        .track-panel {
            width: 100%;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #edf0f3;
            box-shadow: 0 10px 34px rgba(31, 45, 61, .08);
            overflow: hidden;
        }
        .track-panel-head {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            padding: 42px 32px 12px;
            background: #fff;
            color: var(--ew-gray-900);
            text-align: center;
        }
        .track-panel-head i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--ew-accent-soft);
            color: var(--ew-accent);
            font-size: 31px;
        }
        .track-panel-head h4 {
            margin: 0;
            font-weight: 700;
            font-size: 1.7rem;
            letter-spacing: -.3px;
            color: #252a30;
        }
        .track-panel-head span {
            display: block;
            max-width: 480px;
            font-size: .92rem;
            color: #69737d;
            line-height: 1.5;
            font-weight: 400;
            margin-top: -6px;
        }
        .track-panel-body {
            padding: 20px 48px 42px;
        }

/* ==========================================================
   Pending Active Step
========================================================== */

.status-progress .step.pending-active .node{

    position:relative;
    z-index:10;

    

    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    background:#DD111E !important;
    border:3px solid #DD111E !important;

    color:#fff;
    font-weight:700;

    animation:pendingPulse 1.8s ease-out infinite;
}

/* Ripple Ring */

.status-progress .step.pending-active .node::after{

    content:"";

    position:absolute;

    left:50%;
    top:50%;

    width:100%;
    height:100%;

    border-radius:50%;

    border:3px solid rgba(221,17,30,.55);

    transform:translate(-50%,-50%);

    animation:rippleRed 1.8s ease-out infinite;
}


/* Pending Line */

.status-progress .step.pending-active .connector{

    background:#DD111E !important;
    overflow:hidden;
}


/* Moving Shine */

.status-progress .step.pending-active .connector::after{

    content:"";

    position:absolute;

    top:0;
    left:-40%;

    width:40%;
    height:100%;

    background:linear-gradient(
        90deg,
        transparent,
        rgba(255,255,255,.9),
        transparent
    );

    animation:lineMove 1.4s linear infinite;
}


/* ==========================================================
   Animations
========================================================== */

@keyframes pendingPulse{

    0%{
        transform:scale(1);
    }

    50%{
        transform:scale(1.08);
    }

    100%{
        transform:scale(1);
    }

}

@keyframes rippleRed{

    0%{

        width:100%;
        height:100%;

        opacity:.8;

    }

    100%{

        width:180%;
        height:180%;

        opacity:0;

    }

}

@keyframes lineMove{

    from{

        left:-40%;

    }

    to{

        left:100%;

    }

}
        /* ============================================================
           Search form
           ============================================================ */
        .track-search-row {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            width: 100%;
            max-width: 620px;
            margin: 0 auto;
            background: #fff;
            border-radius: 9px;
            padding: 5px;
        }
        .track-search-field {
            flex: 1 1 auto;
            min-width: 0;
            padding: 0 10px;
        }
        .track-search-field label {
            display: block;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            font-weight: 700;
            color: #73808c;
            margin-bottom: 5px;
        }
        .track-search-field label .req {
            color: var(--ew-red);
        }
        .track-search-field label .opt {
            color: var(--ew-gray-400);
            font-weight: 500;
            text-transform: none;
            letter-spacing: 0;
        }
        .track-search-field input.form-control {
            border: 0;
            border-radius: 6px;
            height: 42px;
            padding: 0 10px;
            font-size: .92rem;
            color: #303942;
            box-shadow: none;
            background: #fafbfc;
        }
        .track-search-field input.form-control:focus {
            border-color: var(--ew-accent);
            box-shadow: 0 0 0 3px rgba(229, 36, 50, .12);
        }
        .track-search-btn {
            border-radius: 7px;
            background: var(--ew-accent);
            border: none;
            color: #fff;
            font-weight: 700;
            padding: 10px 10px;
            font-size: .9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all .15s ease;
        }
        .track-search-btn:hover {
            background: #c91d2a;
            color: #fff;
        }

        #response.alert {
            border-radius: 10px;
            border: 1px solid #f3c2c2;
            margin-top: 18px;
        }
        .track-plain-msg {
            text-align: center;
            color: #6c7a87;
            font-weight: 600;
            font-size: .85rem;
            background: #f7f9fb;
            border: 1px solid #eef1f4;
            border-radius: 8px;
            padding: 16px 18px;
                max-width: 600px;
                 margin: 20px auto;
        }

        /* ============================================================
           Result heading
           ============================================================ */
        .track-result-title {
            text-align: left;
            margin: 30px 0 18px;
            padding-left: 2px;
        }
        .track-result-title h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #222;
        }
        .track-result-title small {
            display: block;
            color: #DD111E;
            font-size:12px;
            font-weight:700;
            margin-top: 3px;
        }

/* ============================================================
   PARTIAL DELIVERY STEP
   ============================================================ */

.status-progress .step.partial-delivery .node {
    background: #DD111E !important;
    border-color: #DD111E !important;
    color: #fff !important;
}

.status-progress .step.partial-delivery .step-label {
    color: #DD111E !important;
    font-weight: 700;
}

.status-progress .step .step-label small {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    font-weight: 700;
}

.delivery-summary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
}
        /* ============================================================
           Modern stepper (replaces old .status-progress)
           Spans the full panel width. Label uses a namespaced class
           (.step-label) to avoid collision with the theme's global
           ".title" utility class.
           ============================================================ */
        .status-progress {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            width: 100%;
            max-width: 100%;
            margin: 0 auto 6px;
            padding: 14px 4px 4px;
        }
        .status-progress .step {
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            text-align: center;
            min-width: 64px;
        }
        .status-progress .step .connector {
            position: absolute;
            top: 18px;
            left: -50%;
            width: 100%;
            height: 4px;
            background: #e6ebef;
            z-index: 0;
        }
        .status-progress .step:first-child .connector {
            display: none;
        }
        .status-progress .step.done .connector {
            background: var(--ew-green);
        }
        .status-progress .step.active .connector {
            background: var(--ew-green);
        }
        .status-progress .step .node {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #e6ebef;
            color: #b6c0c9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .78rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        .status-progress .step.done .node {
            background: var(--ew-green);
            border-color: var(--ew-green);
            color: #fff;
        }
        .status-progress .step.active .node {
            background: #fff;
            border-color: var(--ew-accent);
            color: var(--ew-accent);
            box-shadow: 0 0 0 4px rgba(229, 36, 50, .12);
        }
        .status-progress .step .step-label {
            display: block;
            margin-top: 12px;
            font-size: 12px;
            font-weight: 600;
            color: #9aa6b0;
            line-height: 1.3;
            padding: 0 2px;
            background: none;
            white-space: normal;
            word-break: break-word;
        }
        .status-progress .step.done .step-label {
            color: #444;
        }
        .status-progress .step.active .step-label {
            color: var(--ew-accent);
            font-weight: 700;
        }
        @media (max-width: 900px) {
            .status-progress {
                overflow-x: auto;
                justify-content: flex-start;
            }
            .status-progress .step {
                min-width: 100px;
            }
        }

        /* ============================================================
           Consignment summary strip (replaces the old details grid)
           A light, horizontal info bar that reads naturally instead
           of a heavy bordered table.
           ============================================================ */
        .track-summary {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 18px;
            padding: 14px 18px;
            background: linear-gradient(135deg, var(--ew-navy) 0%, var(--ew-navy-light) 50%, #1B4B7A 100%);
            border: 1px solid #eef1f4;
            border-radius: 8px;
            margin-bottom: 22px;
        }
        .track-summary .sum-route {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            color: #fff;
        font-size: 18px;
        }
        .track-summary .sum-route .arrow {
            color: var(--ew-accent);
            font-size: .78rem;
        }
        .track-summary .sum-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size:14px;
            color: #fff;
            font-weight: 600;
        }
        .track-summary .sum-chip i {
            color: #fff;
            font-size: 15px;
        }
        .track-summary .sum-chip b {
            color: #fff;
            font-weight: 700;
        font-size: 15px;
        }
        .track-summary .sum-divider {
            width: 1px;
            height: 18px;
            background: #e1e7ec;
        }
        @media (max-width: 700px) {
            .track-summary {
                gap: 8px 14px;
                padding: 14px 16px;
            }
            .track-summary .sum-divider {
                display: none;
            }
        }

        /* ============================================================
           Scan feed — a responsive grid of compact scan cards.
           Each scan is a self-contained card with a colored status
           pill, timestamp, and description. The latest scan is
           highlighted with a blue accent so it stands out without
           repeating the vertical-line look of the stepper.
           ============================================================ */
        .scan-feed-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 22px 0 12px;
        }
        .scan-feed-head h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #222;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .scan-feed-head .count-pill {
            font-size: .68rem;
            font-weight: 700;
            color: #6c7a87;
            background: #f0f3f5;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .scan-feed {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 12px;
        }
        .scan-card {
            position: relative;
            background: #fff;
            border: 1px solid #79dc64;
            border-radius: 8px;
            padding: 14px 16px 16px;
            transition: box-shadow .15s ease, border-color .15s ease;
        }
        .scan-card:hover {
            box-shadow: 0 3px 12px rgba(31, 45, 61, .07);
            border-color: #15965a;
        }
        .scan-card .sc-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }
        .scan-card .sc-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 700;
            color: #15965a;
            background: #eafaf3;
            padding: 3px 9px;
            border-radius: 5px;
            line-height: 1.4;
        }
        .scan-card .sc-badge i {
            font-size: .66rem;
        }
        .scan-card .sc-date {
            font-size: 10px;
            color: #15965a;
            font-weight: 600;
            white-space: nowrap;
            text-align: right;
            line-height: 1.5;
        }
        .scan-card .sc-desc {
            font-size:12px;
            color: #000;
            line-height: 1.5;
            margin: 0;
        }
        .scan-card.latest {
            border-color: var(--ew-accent);
            box-shadow: 0 3px 14px rgba(229, 36, 50, .12);
        }
        .scan-card.latest .sc-badge {
            color: #fff;
            background: var(--ew-accent);
        }
        .scan-card.latest .sc-date {
            color: var(--ew-accent);
        }
        .scan-card.latest::before {
            content: 'LATEST';
            position: absolute;
            top: -7px;
            right: 14px;
            font-size: .56rem;
            font-weight: 800;
            letter-spacing: .5px;
            color: #fff;
            background: var(--ew-accent);
            padding: 2px 7px;
            border-radius: 4px;
        }
        @media (max-width: 700px) {
            .track-panel-body {
                padding: 18px 14px 28px;
            }
            .track-panel-head {
                padding: 28px 16px 8px;
            }
            .track-panel-head h4 {
                font-size: 1.4rem;
            }
            .track-panel-head i {
                width: 52px;
                height: 52px;
                font-size: 26px;
            }
            .status-progress {
                padding-left: 0;
                padding-right: 0;
            }
            .scan-feed {
                grid-template-columns: 1fr;
            }
            .track-search-row {
                padding: 4px;
            }
        }
    </style>
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
        <div class="container-fluid main-content new_dpt_bottom">
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
                                            $count = 1;
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
                                                    $count++;
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
                                                    $count = 1;
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

$delivery_q = mysqli_query(
    $conn,
    "SELECT delivery_type, delivered_packages, total_packages
     FROM transaction_status_log
     WHERE grn_no='" . mysqli_real_escape_string($conn, $grn_no) . "'
       AND to_status='8'
     ORDER BY sheet_id DESC
     LIMIT 1"
);

if ($delivery_q && mysqli_num_rows($delivery_q) > 0) {

    $delivery_r = mysqli_fetch_assoc($delivery_q);

    $delivery_type = strtolower(trim($delivery_r['delivery_type'] ?? ''));

    $delivered_packages = (int)($delivery_r['delivered_packages'] ?? 0);

    // Always use actual invoice package count
    if ($total_packages <= 0) {
        $total_packages = (int)($delivery_r['total_packages'] ?? 0);
    }
}

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
                                                   
?>
                                                <!-- ---- Consignment summary strip ---- -->
                                                    <div class="track-summary">
                                                        <span class="sum-route">
                                                            <?php echo get_client_name($conn, $consigner); ?>
                                                            <span class="arrow"><i class="fa fa-long-arrow-right"></i></span>
                                                            <?php echo get_client_name($conn, $consignee); ?>
                                                        </span>
                                                        <span class="sum-divider"></span>
                                                        <span class="sum-chip"><i class="fa fa-calendar"></i> <?php echo $grn_date; ?></span>
                                                        <span class="sum-divider"></span>
                                                        <span class="sum-chip"><?php echo $mode_icon; ?> <?php echo $mode; ?></span>
                                                        <span class="sum-divider"></span>
                                                       <span class="sum-chip">
    <i class="fa fa-inbox"></i>

    <?php if ($is_partial_delivery) { ?>

        <b>
            <?php echo $delivered_packages; ?>/<?php echo $total_packages; ?>
        </b>
        Packages Delivered

    <?php } elseif ($is_full_delivery) { ?>

        <b>
            <?php echo $total_packages; ?>/<?php echo $total_packages; ?>
        </b>
        Packages Delivered

    <?php } else { ?>

        <b>
            <?php echo $total_packages; ?>
        </b>
        Packages

    <?php } ?>
</span>
    <?php if ($is_partial_delivery) { ?>

    <span class="sum-divider"></span>

    <span class="sum-chip" style="color:#ffd6d6;">
        <i class="fa fa-clock-o"></i>

        <b>
            <?php echo $pending_packages; ?>
        </b>

        Packages Pending
    </span>

<?php } ?>
                                                        <span class="sum-divider"></span>
                                                        <span class="sum-chip"><i class="fa fa-hashtag"></i> <?php echo $grn_no; ?></span>
                                                    </div>

<?php
// ============================================================
// MODERN TRACKING STEPPER
// ============================================================

echo '<div class="status-progress">';

for ($i = 1; $i < 9; $i++) {

    $step_class = 'step';
    $icon = $i;
    $label = get_trans_status($i);

    /*
     * --------------------------------------------------------
     * NORMAL STEPS 1 - 7
     * --------------------------------------------------------
     */

    if ($i < $tracking_max) {

        $step_class .= ' done';
        $icon = '&#10003;';

    } elseif ($i == $tracking_max) {

        /*
         * If fully delivered:
         * Status 8 is completed.
         */
        if ($i == 8 && $is_full_delivery) {

            $step_class .= ' done';
            $icon = '&#10003;';

        /*
         * If partial delivery:
         * Status 7 is already completed.
         */
        } elseif ($i == 7 && $is_partial_delivery) {

            $step_class .= ' done';
            $icon = '&#10003;';

        /*
         * Normal current status
         */
        } else {

            $step_class .= ' active';
            $icon = $i;
        }

    /*
     * --------------------------------------------------------
     * PARTIAL DELIVERY
     * --------------------------------------------------------
     */

    } elseif ($i == 8 && $is_partial_delivery) {

        // Delivery step remains active/in-progress
       $step_class .= ' pending-active partial-delivery';

        $icon = '8';

        $label =
            'Partial Delivery<br>' .
            '<small>' .
            $delivered_packages . '/' . $total_packages .
            ' Packages Delivered</small>';

    /*
     * --------------------------------------------------------
     * FULL DELIVERY
     * --------------------------------------------------------
     */

    } elseif ($i == 8 && $is_full_delivery) {

        $step_class .= ' done';

        $icon = '&#10003;';

        $label =
            'Fully Delivered<br>' .
            '<small>' .
            $total_packages . '/' . $total_packages .
            ' Packages</small>';

    /*
     * --------------------------------------------------------
     * FUTURE STEPS
     * --------------------------------------------------------
     */

    } else {

        $icon = $i;
    }

    echo '
        <div class="' . $step_class . '">

            <span class="connector"></span>

            <span class="node">' . $icon . '</span>

            <span class="step-label">' . $label . '</span>

        </div>
    ';
}

echo '</div>';
?>
                           <?php                         

                                                    $trans_status = "SELECT * FROM `transaction_status` WHERE sheet_id IN(select sheet_id from transaction_status_log where grn_no='$grn_no')";
                                                    $res = mysqli_query($conn, $trans_status);
                                                    // Pull every scan row up front so we know which one is the
                                                    // most recent — that's the only row we mark as "current".
                                                    // Everything before it already happened, so it renders green,
                                                    // matching the "done" steps in the stepper above.
                                                    $scan_rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
                                                    $scan_row_count = count($scan_rows);

                                                    $tempRow = $grnr;
                                                    $tempRow['active_status'] = 1;

                                                    $bookedMessage = get_tracking_message($conn, $tempRow);
                                                    ?>

                                                    <?php
                                                    $total_scans = $scan_row_count + 1;
                                                    $booked_date = date('d-m-Y', strtotime($grn_date));
                                                    $booked_time = !empty($booking_time) ? date('H:i:s', strtotime($booking_time)) : '';
                                                    $is_booked_latest = ($scan_row_count === 0);
                                                    ?>
                                                    <!-- ---- Scan feed ---- -->
                                                    <div class="scan-feed-head">
                                                        <h3>Scan History</h3>
                                                        <span class="count-pill"><?php echo $total_scans; ?> update<?php echo $total_scans > 1 ? 's' : ''; ?></span>
                                                    </div>
                                                    <div class="scan-feed">
                                                        <div class="scan-card<?php echo $is_booked_latest ? ' latest' : ''; ?>">
                                                            <div class="sc-top">
                                                                <span class="sc-badge"><i class="fa fa-check"></i> <?php echo get_trans_status(1); ?></span>
                                                                <span class="sc-date"><?php echo $booked_date; ?><?php echo $booked_time ? '<br>' . $booked_time : ''; ?></span>
                                                            </div>
                                                            <p class="sc-desc"><?php echo $bookedMessage; ?></p>
                                                        </div>
                                                        <?php
                                                        foreach ($scan_rows as $scan_index => $result_status) {
                                                            $re = $result_status['status'];
                                                            $date_data = $result_status['created_at'];
                                                            $tempRow = $grnr;
                                                            $tempRow['active_status'] = $re;

                                                            $remarks = get_tracking_message($conn, $tempRow);
                                                        // ------------------------------------------------------------
// Partial delivery message override
// ------------------------------------------------------------

if ($re == 8 && $is_partial_delivery) {

    $remarks =
        "Your consignment <strong style=\"color:#000;\">$grn_no</strong> " .
        "has been partially delivered. " .
        "<strong style=\"color:#000;\">" .
        $delivered_packages . '/' . $total_packages .
        " packages</strong> have been delivered successfully. " .
        "<strong style=\"color:#DD111E;\">" .
        $pending_packages .
        " packages are still pending.</strong>";
}

                                                            $timestamp = strtotime($date_data);

                                                            $date = date('d-m-Y', $timestamp);
                                                            $time = date('H:i:s', $timestamp);

                                                            $is_latest = ($scan_index === $scan_row_count - 1);
                                                            ?>
                                                            <div class="scan-card<?php echo $is_latest ? ' latest' : ''; ?>">
                                                                <div class="sc-top">
                                                                    <span class="sc-badge"><i class="fa fa-check"></i> <?php
if ($re == 8 && $is_partial_delivery) {

    echo 'Partial Delivery – ' .
         $delivered_packages . '/' .
         $total_packages .
         ' Packages Delivered';

} elseif ($re == 8 && $is_full_delivery) {

    echo 'Fully Delivered – ' .
         $total_packages . '/' .
         $total_packages .
         ' Packages';

} else {

    echo get_trans_status($re);
}
?></span>
                                                                    <span class="sc-date"><?php echo $date; ?><?php echo $time ? '<br>' . $time : ''; ?></span>
                                                                </div>
                                                                <p class="sc-desc"><?php echo $remarks; ?></p>
                                                            </div>
                                                        <?php
                                                        }
                                                        ?>
                                                    </div>

                                        <?php
                                                }
                                            }

                                            if ($count == 1)
                                                echo '<p class="track-plain-msg">Incorrect GCN No or Booking Cancelled. Please check and try again!</p>';
                                        } else {
                                            echo '<p class="track-plain-msg">Invalid GCN or PNR number. Please check and try again!</p>';
                                        }
                                    } else {
                                        echo '<p class="track-plain-msg">Please enter either GCN No or PNR Number to track your consignment.</p>';
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
