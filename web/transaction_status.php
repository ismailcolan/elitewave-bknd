<?php
require_once ('include/connect.php');
require_once ('include/function.php');
require_once ('include/transaction_list_query.php');
$logged_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>

<head>
    <?php include ('include/title.php'); ?>
    <?php include ('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">

    <style>
        .txn-page-wrap { max-width: 100%; }
        .txn-page-wrap .ew-card-toolbar.txn-toolbar {
            overflow: visible;
            position: relative;
            z-index: 10;
        }
        .month-field-col {
            max-width: 280px;
            flex: 0 0 auto;
        }
        .txn-period-filter {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 10px;
        }
        .txn-period-filter .txn-period-select-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .txn-period-filter .txn-period-select {
            height: 32px !important;
            min-height: 32px !important;
            width: 168px;
            padding: 0 28px 0 12px !important;
            font-size: 13px !important;
            font-weight: 600;
            color: #0A1E3D !important;
            border: 1px solid #D8DDE5 !important;
            border-radius: 6px !important;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%236B7A8D' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E") no-repeat right 10px center !important;
            background-size: 10px 6px !important;
            -webkit-appearance: none;
            appearance: none;
            cursor: pointer;
        }
        .txn-toolbar .control-label,
        .month-field-col .control-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 6px;
        }
        .txn-datatable-area {
            padding: 12px 16px 16px;
            width: 100%;
            overflow-x: auto;
        }
        .trans_list_table {
            width: 100% !important;
            min-width: 980px;
            margin: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
        }
        .trans_list_table col.col-sno { width: 42px; }
        .trans_list_table col.col-gcn { width: 88px; }
        .trans_list_table col.col-date { width: 88px; }
        .trans_list_table col.col-pkgs { width: 48px; }
        .trans_list_table col.col-consignor { width: 120px; }
        .trans_list_table col.col-consignee { width: 120px; }
        .trans_list_table col.col-dest { width: 90px; }
        .trans_list_table col.col-status { width: 110px; }
        .trans_list_table col.col-steps { width: 280px; }
        .trans_list_table thead th {
            padding: 11px 8px !important;
            white-space: nowrap;
            vertical-align: middle !important;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .trans_list_table thead th:last-child { text-align: center !important; }
        .trans_list_table tbody td {
            font-size: 13px;
            padding: 9px 8px !important;
            vertical-align: middle !important;
            border-color: #EEF2F7 !important;
            color: #1E293B;
            overflow: hidden;
            word-wrap: break-word;
        }
        .trans_list_table tbody td.col-steps {
            overflow: visible !important;
            white-space: nowrap;
            padding: 8px 6px !important;
            text-align: center !important;
        }
        .trans_list_table tbody tr:hover td { background: #F8FAFC !important; }
        .trans_list_table tbody tr:nth-child(even) td { background: #FBFCFE; }
        .txn-gcn-no { font-weight: 700; color: #0A1E3D; font-size: 13px; }
        .txn-datatable-area .dataTables_wrapper { width: 100%; }
        .txn-datatable-area .dataTables_length,
        .txn-datatable-area .dataTables_filter { padding: 12px 0 8px; margin: 0; }
        .txn-datatable-area .dataTables_length select {
            border: 1px solid #D8DDE5; border-radius: 6px; padding: 4px 8px; margin: 0 6px;
        }
        .txn-datatable-area .dataTables_filter input {
            border: 1px solid #D8DDE5; border-radius: 6px; padding: 6px 10px; margin-left: 8px; min-width: 180px;
        }
        .txn-datatable-area .dataTables_info,
        .txn-datatable-area .dataTables_paginate { padding: 10px 0 4px; font-size: 12px; }
        .col-steps .border,
        #get_month_details button.border {
            width: 30px;
            height: 30px;
            min-width: 25px;
            padding: 0;
            margin: 0;
            border: 1px solid silver;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            vertical-align: middle;
        }
        .col-steps .border i.fa-check,
        #get_month_details button.border i.fa-check {
            font-size: 10px;
        }
        .col-steps .actions > div,
        #get_month_details .actions > div,
        #get_month_details .col-steps > div {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: center;
            gap: 2px;
        }
        .booked { background-color: #8dafbf; color: #fff; }
        .picked-up { background-color: #77aec9; color: #fff; }
        .transit-1 { background-color: #6293ab; color: #fff; }
        .transit-2 { background-color: #4c798f; color: #fff; }
        .transit-3 { background-color: #3d6578; color: #fff; }
        .destination { background-color: #1a607e; color: #fff; }
        .out-delivery { background-color: #0e4c68; color: #fff; }
        .delivered { background-color: #003e58; color: #fff; }
        .partial-delivery-button {
            background-color: #DC2626 !important;
            color: #fff !important;
            border-color: #DC2626 !important;
        }
        .mobile-update-status {
            display: none;
            width: 100%;
            margin-top: 8px;
            border-radius: 8px;
            font-weight: 600;
        }
        .mobile-status-group { display: none; }
        .table-actions-click {
            border: solid 1px;
            background: #0A1E3D;
            color: #FFF;
            border-radius: 5px;
        }
        .month-field-col .date-input-inside { width: 168px; max-width: 168px; }
        .month-field-col { margin-bottom: 0; }
        #status_popup_modal.ew-v2-modal-backdrop,
        #show_remarks_modal.ew-v2-modal-backdrop {
            z-index: 10050;
        }
        #status_popup_modal .ew-v2-modal,
        #show_remarks_modal .ew-v2-modal {
            max-width: 560px;
            width: 92%;
        }
        #status_popup_modal .ew-v2-modal-body {
            padding: 20px 24px 8px;
        }
        .ts-modal-remarks-panel {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 18px;
            min-height: 88px;
            max-height: 168px;
            overflow-y: auto;
            font-size: 14px;
            line-height: 1.55;
            color: #334155;
        }
        .ts-modal-field { margin-bottom: 16px; }
        .ts-modal-field > label,
        #status_popup_modal .form-group > label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 6px;
        }
        #status_popup_modal .form-control {
            border-radius: 8px;
            border-color: #CBD5E1;
            min-height: 38px;
        }
        #status_popup_modal .date-input-inside .form-control { min-height: 38px; }
        #delivery_options {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }
        #delivery_options > .form-group:last-child { margin-bottom: 0; }
        .ts-delivery-types {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 20px;
            margin-top: 4px;
        }
        .ts-delivery-opt {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            text-transform: none;
            letter-spacing: 0;
            margin: 0;
            cursor: pointer;
        }
        .ts-modal-datetime-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 575.98px) {
            .ts-modal-datetime-row { grid-template-columns: 1fr; }
        }
        .datepicker,
        .ui-timepicker-wrapper {
            z-index: 10140 !important;
        }
        @media (max-width: 767px) {
            .txn-page-wrap { padding: 0 12px 24px; }
        }
        @media (max-width: 575.98px) {
            .txn-datatable-area { overflow-x: visible; }
            .trans_list_table { min-width: 0; table-layout: auto !important; }
            .trans_list_table thead { display: none; }
            .trans_list_table, .trans_list_table tbody, .trans_list_table tr, .trans_list_table td {
                display: block; width: 100%;
            }
            .trans_list_table tr {
                border: 1px solid #E2E8F0;
                border-radius: 10px;
                margin-bottom: 14px;
                background: #fff;
                padding: 10px;
            }
            .trans_list_table td {
                border: none !important;
                border-bottom: 1px solid #EEF2F7 !important;
                text-align: right;
                padding: 10px 10px 10px 45% !important;
                position: relative;
                white-space: normal;
            }
            .trans_list_table td:before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                top: 10px;
                width: 40%;
                font-weight: 700;
                text-align: left;
                color: #0A1E3D;
                font-size: 11px;
                text-transform: uppercase;
            }
            .trans_list_table td.col-steps,
            td[data-label="Change Status"] {
                text-align: left;
                padding-left: 10px !important;
                overflow: visible !important;
            }
            td[data-label="Change Status"]:before {
                position: static;
                display: block;
                margin-bottom: 8px;
            }
            .col-steps > div { flex-wrap: wrap !important; }
            .col-steps .border { display: none; }
            .mobile-update-status { display: inline-block; }
            .mobile-status-group { display: block; }
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
                    <div class="ew-page-v2 ew-page-v2--wide-table txn-page-wrap">
                        <div class="ew-page-head">
                            <div class="ew-page-head-left">
                                <h1 class="ew-page-title">Change Status</h1>
                            </div>
                        </div>
                        <div class="ew-card ew-erp-list txn-table-card">
                        <div class="ew-card-toolbar txn-toolbar">
                            <div class="ew-list-toolbar__left">
                                <form class="form-horizontal" id="transaction_form" style="margin:0;">
                                    <input type="hidden" id="form_name" name="form_name" value="transaction_form">
                                    <input type="hidden" id="edit_id" name="edit_id" value="">
                                    <input type="hidden" id="cmd" name="cmd" value="get_transact_status_month_detail">
                                    <div id="response" class="alert alert-danger" style="display:none;">
                                        <div class="message" style="text-align:center"></div>
                                    </div>
                                    <div class="ew-month-filter txn-period-filter">
                                        <div class="txn-period-select-wrap">
                                            <select id="report_type" name="report_type" class="form-control report_type txn-period-select">
                                                <option value="ALL" selected>All records</option>
                                                <option value="DAILY">Daily</option>
                                                <option value="MONTHLY">Monthly</option>
                                                <option value="YEARLY">Yearly</option>
                                            </select>
                                        </div>
                                        <div class="report-date-group">
                                            <div id="picker_daily" style="display:none;">
                                                <?php echo ew_date_input(array('id' => 'date', 'name' => 'date', 'value' => date('d-m-Y'), 'readonly' => true, 'class' => 'ew-toolbar-month')); ?>
                                            </div>
                                            <div id="picker_month" style="display:none;">
                                                <?php echo ew_month_input(array('id' => 'month', 'name' => 'month', 'required' => false, 'placeholder' => 'Select Month', 'class' => 'ew-toolbar-month')); ?>
                                            </div>
                                            <div id="picker_year" style="display:none;">
                                                <select id="year" name="year" class="form-control report-year-select">
                                                    <?php
                                                    $current_year = (int) date('Y');
                                                    for ($yr = $current_year + 1; $yr >= $current_year - 15; $yr--) {
                                                        $selected = ($yr === $current_year) ? ' selected' : '';
                                                        echo '<option value="' . $yr . '"' . $selected . '>' . $yr . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="ew-toolbar-right">
                                <div class="ew-list-toolbar__tools"></div>
                            </div>
                        </div>
                        <div class="ew-table-wrap txn-datatable-area">
                            <table class="table table-bordered table-striped trans_list_table" id="ts_status_table">
                                <colgroup>
                                    <col class="col-sno">
                                    <col class="col-gcn">
                                    <col class="col-date">
                                    <col class="col-pkgs">
                                    <col class="col-consignor">
                                    <col class="col-consignee">
                                    <col class="col-dest">
                                    <col class="col-status">
                                    <col class="col-steps">
                                </colgroup>
                                <thead>
                                    <tr>
                                    <th>S.No</th>
                                    <th>GCN No</th>
                                    <th>GCN Date</th>
                                    <th>Pkgs</th>
                                    <th>Consignor</th>
                                    <th>Consignee</th>
                                    <th>Destination</th>
                                    <th>Status</th>
                                    <th>Change Status</th>
                                    </tr>
                                </thead>
                                <tbody id="get_month_details">
                                        <?php
                                        $date = date('d-m-Y');
                                        $my = date('m-Y');
                                        $dt = (explode('-', $date));

                                        if ($dt[1] <= 3) {
                                            $m = 4;
                                            $m1 = 1;
                                            $y = $dt[2];
                                            $trans_name = 'transaction_' . $m1 . '_' . $dt[2];
                                            $trans_image_name = 'transaction_images_' . $m1 . '_' . $dt[2];
                                            $trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $dt[2];
                                        } else if (($dt[1] >= 4) && ($dt[1] <= 6)) {
                                            $m = 1;
                                            $m1 = 2;
                                            $y = $dt[2];
                                            $trans_name = 'transaction_' . $m1 . '_' . $dt[2];
                                            $trans_image_name = 'transaction_images_' . $m1 . '_' . $dt[2];
                                            $trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $dt[2];
                                        } else if (($dt[1] >= 7) && ($dt[1] <= 9)) {
                                            $m = 2;
                                            $m1 = 3;
                                            $y = $dt[2];
                                            $trans_name = 'transaction_' . $m1 . '_' . $dt[2];
                                            $trans_image_name = 'transaction_images_' . $m1 . '_' . $dt[2];
                                            $trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $dt[2];
                                        } else {
                                            $m = 3;
                                            $m1 = 4;
                                            $y = $dt[2];
                                            $trans_name = 'transaction_' . $m1 . '_' . $dt[2];
                                            $trans_image_name = 'transaction_images_' . $m1 . '_' . $dt[2];
                                            $trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $dt[2];
                                        }
                                        if ($_SESSION['role'] == 'AD') {
                                            $query = 'select * from transaction_' . $m1 . '_' . $dt[2] . " where grn_date like '%$my' and invoice_no !='' order by grn_date desc,grn_no desc ";
                                        } else {
                                            $query = 'select * from transaction_' . $m1 . '_' . $dt[2] . " where consigner='" . $_SESSION['company_id'] . "' or consignee='" . $_SESSION['company_id'] . "' and grn_date like '%$my' and invoice_no !='' order by grn_date desc,grn_no desc";
                                        }
                                        $result = mysqli_query($conn, $query);
                                        $i = 1;
                                        while ($row = mysqli_fetch_array($result)) {
                                            $booking = $row['booking_status'];
                                            $remarks = $row['remarks'];
                                            $consignment_mode = $row['mode_of_consignment'];
                                            $status = $row['status'];
                                            $cancelled_by = get_user($conn, $row['cancelled_by']);
                                            $updated_at = $row['updated_at'];
                                            $pkg_q = mysqli_query($conn, 'select sum(no_of_pkge) as pkge from transaction_invoice_' . $m1 . '_' . $dt[2] . " where transaction_id='" . $row['transaction_id'] . "' ");
                                            $pkg_r = mysqli_fetch_array($pkg_q);
                                            $total_packages = (int)$pkg_r['pkge'];

$delivery_q = mysqli_query(
    $conn,
    "SELECT delivery_type, delivered_packages, total_packages
     FROM transaction_status_log
     WHERE grn_no='" . $row['grn_no'] . "'
       AND to_status='8'
     ORDER BY sheet_id DESC
     LIMIT 1"
);

$delivery_r = mysqli_fetch_assoc($delivery_q);

$delivery_type = !empty($delivery_r['delivery_type'])
    ? $delivery_r['delivery_type']
    : '';

$delivered_packages = !empty($delivery_r['delivered_packages'])
    ? (int)$delivery_r['delivered_packages']
    : 0;

if ($delivery_type == 'partial') {
    $display_status = 'Partially Delivered (' .
        $delivered_packages . '/' .
        $total_packages .
        ')';
} elseif ($delivery_type == 'full') {
    $display_status = 'Delivered Successfully';
} else {
    $display_status = get_trans_status($row['status']);
}
                                            ?>
                                            <tr>
                                                <td class="text-center" data-label="S.No" data-order="<?php echo (int) transaction_gcn_serial_no($row); ?>"><?php echo (int) transaction_gcn_serial_no($row); ?></td>
                                                <td data-label="GCN No"><span class="txn-gcn-no"><?php echo htmlspecialchars($row['grn_no']); ?></span></td>
                                                <td data-label="GCN Date"><?php echo htmlspecialchars($row['grn_date']); ?></td>
                                                <td class="text-center" data-label="Pkgs"><?php echo (int) $pkg_r['pkge']; ?></td>
                                                <td data-label="Consignor"><?php echo htmlspecialchars(get_client_name($conn, $row['consigner'])); ?></td>
                                                <td data-label="Consignee"><?php echo htmlspecialchars(get_client_name($conn, $row['consignee'])); ?></td>
                                                <td data-label="Destination"><?php echo htmlspecialchars(get_city_name($conn, $row['destination'])); ?></td>
                                                <td data-label="Status"><?php echo transaction_status_badge($booking, $status, array(
                                                    'delivery_type' => $delivery_type,
                                                    'delivered_packages' => $delivered_packages,
                                                    'total_packages' => $total_packages,
                                                )); ?></td>

                                                <td class="col-steps actions center-content" data-label="Change Status">
                                                    <div>
                                                        <button class="border booked" disabled title="Consignment Booked"><i class="fa fa-check"></i></button>
                                                        <button class="border picked-up <?php if ($row['status'] >= 2) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 2) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="2" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 2) { echo 'data-remarks="' . get_cong_remarks($conn, 2, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="Consignment Picked Up"><?php if ($row['status'] >= 2) { echo "<i class='fa fa-check'></i>"; } else { echo '2'; } ?></button>
                                                        <button class="border transit-1 <?php if ($row['status'] >= 3) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 3) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="3" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 3) { echo 'data-remarks="' . get_cong_remarks($conn, 3, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="In Transit-1"><?php if ($row['status'] >= 3) { echo "<i class='fa fa-check'></i>"; } else { echo '3'; } ?></button>
                                                        <button class="border transit-2 <?php if ($row['status'] >= 4) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 4) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="4" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 4) { echo 'data-remarks="' . get_cong_remarks($conn, 4, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="In Transit-2"><?php if ($row['status'] >= 4) { echo "<i class='fa fa-check'></i>"; } else { echo '4'; } ?></button>
                                                        <button class="border transit-3 <?php if ($row['status'] >= 5) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 5) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="5" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 5) { echo 'data-remarks="' . get_cong_remarks($conn, 15, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="In Transit-3"><?php if ($row['status'] >= 5) { echo "<i class='fa fa-check'></i>"; } else { echo '5'; } ?></button>
                                                        <button class="border destination <?php if ($row['status'] >= 6) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 6) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="6" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 6) { echo 'data-remarks="' . get_cong_remarks($conn, 6, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="At Destination"><?php if ($row['status'] >= 6) { echo "<i class='fa fa-check'></i>"; } else { echo '6'; } ?></button>
                                                        <button class="border out-delivery <?php if ($row['status'] >= 7) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 7) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="7" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 7) { echo 'data-remarks="' . get_cong_remarks($conn, 7, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="Out For Delivery"><?php if ($row['status'] >= 7) { echo "<i class='fa fa-check'></i>"; } else { echo '7'; } ?></button>
                                                        <?php if ($row['status'] >= 8 && $delivery_type == 'full') { ?>
                                                        <button class="border delivered" data-status="8" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" data-consignment="<?php echo $row['transaction_id']; ?>" title="Delivered Successfully" disabled><i class="fa fa-check"></i></button>
                                                        <?php } else {
                                                            $delivered_classes = 'border delivered';
                                                            if ($delivery_type == 'partial') {
                                                                $delivered_classes .= ' partial-delivery-button';
                                                            } elseif ($row['status'] >= 8) {
                                                                $delivered_classes .= ' show_info_popup';
                                                            }
                                                            $delivered_attrs = ($row['status'] < 8 || $delivery_type == 'partial') ? 'id="status_popup"' : 'readonly';
                                                        ?>
                                                        <button class="<?php echo $delivered_classes; ?>" <?php echo $delivered_attrs; ?> data-status="8" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" data-consignment="<?php echo $row['transaction_id']; ?>" data-total-packages="<?php echo $total_packages; ?>" data-delivered-packages="<?php echo $delivered_packages; ?>" data-delivery-type="<?php echo $delivery_type; ?>" title="Change Delivery Status"><?php if ($row['status'] >= 8) { echo "<i class='fa fa-check'></i>"; } else { echo '8'; } ?></button>
                                                        <?php } ?>
                                                    </div>
                                                    
                                                    <button
        type="button"
        class="btn btn-primary mobile-update-status"
        data-status="<?php echo $row['status']; ?>"
        data-tabid="<?php echo $trans_name; ?>"
        data-grnid="<?php echo $row['grn_id']; ?>"
        data-grnno="<?php echo $row['grn_no']; ?>"
        data-consignment="<?php echo $row['transaction_id']; ?>">
        Update Status
    </button>
                                                </td>
                                            </tr>
                                        <?php
                                            $i++;
                                        }
                                        ?>

                                    </tbody>
                                </table>
                        </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>


        <?php require_once ('include/footer.php'); ?>
    </div>

    <script type="text/javascript">
        window.tsStatusTable = null;

        function hidePageLoaders() {
            $(".loading-page").hide();
            $(".form-data-saving").hide();
        }

        function destroyTsStatusTable() {
            var $table = $('#ts_status_table');
            if (!$table.length || !$.fn.dataTable) {
                window.tsStatusTable = null;
                return;
            }
            if ($.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($table[0])) {
                try {
                    $table.dataTable().fnDestroy();
                } catch (e) {}
            }
            window.tsStatusTable = null;
        }

        function initTsStatusTable() {
            if (!$.fn.dataTable) {
                return;
            }
            var $table = $('#ts_status_table');
            if (!$table.length) {
                return;
            }
            destroyTsStatusTable();
            if ($.fn.dataTableExt) {
                $.fn.dataTableExt.sErrMode = 'throw';
            }
            window.tsStatusTable = $table.dataTable({
                sDom: '<"txn-dt-top"lf>rt<"txn-dt-bottom"ip>',
                sPaginationType: 'full_numbers',
                iDisplayLength: 10,
                aLengthMenu: [[10, 25, 50, 100, -1], ['10', '25', '50', '100', 'All']],
                aaSorting: [[0, 'desc']],
                bAutoWidth: false,
                bDestroy: true,
                oSearch: { sSearch: '', bSmart: false, bRegex: false, bCaseInsensitive: true },
                aoColumnDefs: [
                    { bSortable: false, aTargets: [8] },
                    { sType: 'numeric', aTargets: [0] },
                    { sClass: 'text-center', aTargets: [0, 3] },
                    { sClass: 'col-steps', aTargets: [8] }
                ],
                oLanguage: {
                    sEmptyTable: 'No bookings found.',
                    sZeroRecords: 'No matching consignments found.'
                },
                fnDrawCallback: function () {
                    if (window.applyEwListLayout) {
                        window.applyEwListLayout();
                    }
                }
            });
            if (window.applyEwListLayout) {
                window.applyEwListLayout();
            }
        }

        function refreshStatusTableAfterSave(message) {
            hidePageLoaders();
            $('#save_status_change').attr('disabled', false);
            if (message && typeof ewToast === 'function') {
                ewToast(message, 'success');
            }
            fetchStatusMonthDetails(true);
        }

        function fetchStatusMonthDetails(silent) {
            var data = $('#transaction_form').serialize();
            if (!silent) {
                hidePageLoaders();
            }
            destroyTsStatusTable();
            $.ajax({
                url: 'fetch_details.php',
                type: 'GET',
                data: data,
                success: function(result) {
                    $('#get_month_details').html(result);
                    initTsStatusTable();
                },
                error: function() {
                    $('#get_month_details').html('');
                    initTsStatusTable();
                    if (typeof ewToast === 'function') {
                        ewToast('Could not load consignments.', 'error');
                    }
                },
                complete: hidePageLoaders
            });
        }

        function setTxnPeriodType(type) {
            $('#picker_daily, #picker_month, #picker_year').hide();
            if (type === 'DAILY') {
                $('#picker_daily').show();
            } else if (type === 'MONTHLY') {
                $('#picker_month').show();
            } else if (type === 'YEARLY') {
                $('#picker_year').show();
            }
        }

        function unlockStatusDatePicker() {
            var $statusDate = $('#status_date');
            if (!$statusDate.length) {
                return;
            }
            if (!$statusDate.data('datepicker') && typeof initEwDatepickers === 'function') {
                initEwDatepickers('#status_popup_modal');
            }
            var dp = $statusDate.data('datepicker');
            if (dp) {
                dp.o.endDate = Infinity;
                dp.o.startDate = -Infinity;
                dp.updateNavArrows();
            }
        }

        function ewTodayDisplayDate() {
            var now = new Date();
            var dd = String(now.getDate()).padStart(2, '0');
            var mm = String(now.getMonth() + 1).padStart(2, '0');
            return dd + '-' + mm + '-' + now.getFullYear();
        }

        function ewTodayDisplayTime() {
            var now = new Date();
            return String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        }

        function ewServerDateFromDisplay(val) {
            if (!val) {
                return val;
            }
            var parts = val.split('-');
            if (parts.length === 3 && parts[0].length === 2 && parts[2].length === 4) {
                return parts[2] + '-' + parts[1] + '-' + parts[0];
            }
            return val;
        }

        function initStatusTimePicker() {
            var $time = $('#status_time');
            if (!$time.length || $time.data('ew-time-init')) {
                return;
            }
            if (typeof $time.timepicker !== 'function') {
                return;
            }
            $time.timepicker({
                timeFormat: 'H:i',
                interval: 15,
                minTime: '00:00',
                maxTime: '23:59',
                dropdown: true,
                scrollbar: true,
                appendTo: 'body'
            });
            $time.data('ew-time-init', true);
        }

        function setStatusModalDateTime() {
            $('#status_date').val(ewTodayDisplayDate());
            $('#status_time').val(ewTodayDisplayTime());
            if ($('#status_date').data('datepicker')) {
                $('#status_date').datepicker('update', ewTodayDisplayDate());
            }
        }

        function openStatusChangeModal() {
            if (typeof ewV2OpenModal === 'function') {
                ewV2OpenModal('status_popup_modal');
            } else {
                $('#status_popup_modal').addClass('open');
            }
            setTimeout(function() {
                unlockStatusDatePicker();
                initStatusTimePicker();
            }, 40);
        }

        function closeStatusChangeModal() {
            if (typeof ewV2CloseModal === 'function') {
                ewV2CloseModal('status_popup_modal');
            } else {
                $('#status_popup_modal').removeClass('open');
            }
        }

        $(document).ready(function() {
            hidePageLoaders();
            initTsStatusTable();
            initStatusTimePicker();

            var monthPickerReady = false;
            setTimeout(function() { monthPickerReady = true; }, 500);
            setTxnPeriodType($('#report_type').val() || 'ALL');
            fetchStatusMonthDetails();
            $(document).on('change', '#report_type', function() {
                setTxnPeriodType($(this).val());
                if (monthPickerReady) {
                    fetchStatusMonthDetails();
                }
            });
            $('#month, #date').on('changeDate', function() {
                if (!monthPickerReady) {
                    return;
                }
                fetchStatusMonthDetails();
            });
            $('#year').on('change', function() {
                if (!monthPickerReady) {
                    return;
                }
                fetchStatusMonthDetails();
            });

            $(document).on('show', '#status_date', function() {
                unlockStatusDatePicker();
            });


            $(document).on('click', '.close-popup', function() {
                $(".form-data-saving").hide();
                refreshStatusTableAfterSave('Status updated successfully.');
            });

            $(document).on('click', '#save_status_change', function(e) {
                e.preventDefault();
                if ($('#status_change_consignment').valid() != true) {
                    return;
                }
                var $saveBtn = $(this);
                function proceedStatusSave() {
                        $saveBtn.attr('disabled', true);
                        $(".loading-page").show();
                        closeStatusChangeModal();
                        if ($(window).width() <= 575) {
    $("#status").val($("#mobile_status_select").val());
}

// Delivery validation
if (parseInt($("#status").val()) === 8) {

    const deliveryType =
        $("input[name='delivery_type']:checked").val();

        if (!deliveryType) {
        alert("Please select Partially Delivered or Fully Delivered.");
        $saveBtn.attr('disabled', false);
        hidePageLoaders();
        openStatusChangeModal();
        return false;
    }

    if (deliveryType === 'partial') {

        const deliveredPackages =
            parseInt($("#delivered_packages").val());

        const totalPackages =
            parseInt($("#total_packages").val());

        if (
            !deliveredPackages ||
            deliveredPackages <= 0 ||
            deliveredPackages >= totalPackages
        ) {
            alert(
                "Please select the number of packages delivered."
            );

            $saveBtn.attr('disabled', false);
            hidePageLoaders();
            openStatusChangeModal();

            return false;
        }
    }
}
                        var data = $('#status_change_consignment').serializeArray();
                        var statusDateVal = ewServerDateFromDisplay($('#status_date').val());
                        var hasStatusDate = false;
                        $.each(data, function(i, field) {
                            if (field.name === 'status_date') {
                                field.value = statusDateVal;
                                hasStatusDate = true;
                            }
                        });
                        if (!hasStatusDate) {
                            data.push({ name: 'status_date', value: statusDateVal });
                        }

                        $.ajax({
                            url: 'save_details.php',
                            type: "POST",
                            data: $.param(data),
                            success: function(result) {
                                if (result != 0) {
                                    refreshStatusTableAfterSave('Status updated successfully.');
                                } else {
                                    hidePageLoaders();
                                    $('#save_status_change').attr('disabled', false);
                                    if (typeof ewToast === 'function') {
                                        ewToast('Status update failed.', 'error');
                                    }
                                }
                            },
                            error: function() {
                                if (typeof ewToast === 'function') {
                                    ewToast('Status update failed. Please try again.', 'error');
                                }
                            },
                            complete: function() {
                                hidePageLoaders();
                                $('#save_status_change').attr('disabled', false);
                            }
                        });
                }
                if (typeof ewConfirm === 'function') {
                    ewConfirm({
                        title: 'Please confirm',
                        message: 'Are you sure you want to submit?',
                        confirmText: 'OK',
                        onConfirm: proceedStatusSave
                    });
                } else if (confirm('Are you sure! Do you want to submit?')) {
                    proceedStatusSave();
                }
			});
            // show popup on status button click

        });
        hidePageLoaders();
        $(window).load(hidePageLoaders);
        setTimeout(hidePageLoaders, 2000);

       $(document).on('click','#status_popup', function(){

    unlockStatusDatePicker();

    openStatusChangeModal();

    setStatusModalDateTime();

    const tabid = $(this).data('tabid');
    const grn_id = $(this).data('grnid');
    const grn_no = $(this).data('grnno');
    const transaction_id = $(this).data('consignment');
    const status = $(this).data('status');

    $("#transaction_id").val(transaction_id);
    $("#table_names").val(tabid);
    $("#grn_id").val(grn_id);
    $("#grn_no").val(grn_no);
    $("#status").val(status);

    // =====================================
// Delivery status handling for Status 8
// =====================================

if (parseInt(status) === 8) {

    const totalPackages =
        parseInt($(this).data('total-packages')) || 0;

    const existingDelivered =
        parseInt($(this).data('delivered-packages')) || 0;

    const existingDeliveryType =
        $(this).data('delivery-type') || '';

    $("#delivery_options").show();

    $("#total_packages_display").val(totalPackages);
    $("#total_packages").val(totalPackages);
    $("#existing_delivered_packages").val(existingDelivered);

 // Reset
    $("#delivery_partial").prop('checked', false);
    $("#delivery_full").prop('checked', false);

    $("#partial_package_group").hide();

    $("#delivered_packages").html(
        '<option value="">Select packages</option>'
    );

    if (totalPackages <= 1) {

    // Only one package - partial delivery is not applicable
    $("#delivery_partial").closest("label").hide();
    $("#delivery_full").closest("label").show();

    // Automatically select full delivery
    $("#delivery_full").prop("checked", true);

    // Hide partial package selection
    $("#partial_package_group").hide();
    $("#delivered_packages").prop("required", false);

} else {

    // Multiple packages - show both options
    $("#delivery_partial").closest("label").show();
    $("#delivery_full").closest("label").show();

}

   

    $(document).on(
    'change',
    'input[name="delivery_type"]',
    function () {

        const deliveryType = $(this).val();

        if (deliveryType === 'partial') {

            $("#partial_package_group").slideDown();

            $("#delivered_packages").prop('required', true);

        } else {

            $("#partial_package_group").slideUp();

            $("#delivered_packages").prop('required', false);

            $("#delivered_packages").val('');
        }
    }
);

    // If already partially delivered
    if (existingDeliveryType === 'partial' && existingDelivered > 0) {

        $("#delivery_partial").prop('checked', true);

        for (
            let i = existingDelivered + 1;
            i < totalPackages;
            i++
        ) {
            $("#delivered_packages").append(
                '<option value="' + i + '">' +
                i + ' / ' + totalPackages +
                '</option>'
            );
        }

        $("#partial_package_group").show();

    } else {

        // First partial delivery
        for (let i = 1; i < totalPackages; i++) {

            $("#delivered_packages").append(
                '<option value="' + i + '">' +
                i + ' / ' + totalPackages +
                '</option>'
            );
        }
    }

}
else {

    $("#delivery_options").hide();

    $("#delivery_partial").prop('checked', false);
    $("#delivery_full").prop('checked', false);

    $("#delivered_packages").val('');
}

    //==========================
    // Get Tracking Message
    //==========================

    var table = tabid.split("_");

    $.ajax({

       url: "fetch_details.php?cmd=get_tracking_message",

        type:"POST",

        data:{
            transaction_id:transaction_id,
            month:table[1],
            year:table[2],
            status:status
        },

       success:function(msg){

    $("#status_remarks").html(msg);

    $("#remarks_text").val(
        $("<div>").html(msg).text()
    );

}

    });

});

        $(document).on('click','.show_info_popup', function(e){
            var remarks = $(this).attr('data-remarks');
            if (!remarks || remarks === '') {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            if (typeof ewV2OpenModal === 'function') {
                ewV2OpenModal('show_remarks_modal');
            } else {
                $('#show_remarks_modal').addClass('open');
            }
            $("#view_remarks").html(remarks);
        });
        const statusList = {
    2: "Consignment Picked Up",
    3: "In Transit - 1",
    4: "In Transit - 2",
    5: "In Transit - 3",
    6: "At Destination",
    7: "Out For Delivery",
    8: "Delivered Successfully"
};
$(document).on('click','.mobile-update-status',function(){

    setStatusModalDateTime();

    openStatusChangeModal();

    let currentStatus=parseInt($(this).data('status'));

    $("#transaction_id").val($(this).data('consignment'));
    $("#table_names").val($(this).data('tabid'));
    $("#grn_id").val($(this).data('grnid'));
    $("#grn_no").val($(this).data('grnno'));
var table = $(this).data('tabid').split("_");

$.ajax({

    url: "fetch_details.php?cmd=get_tracking_message",

    type:"POST",

    data:{
        transaction_id:$("#transaction_id").val(),
        month:table[1],
        year:table[2],
        status:$("#mobile_status_select").val()
    },

   success:function(msg){

    $("#status_remarks").html(msg);

    $("#remarks_text").val(
        $("<div>").html(msg).text()
    );

}

});

    let html='';

    for(let i=currentStatus+1;i<=8;i++){

        html+='<option value="'+i+'">'+statusList[i]+'</option>';

    }

    $("#mobile_status_select").html(html);

});
$(document).on("change","#mobile_status_select",function(){

    var table = $("#table_names").val().split("_");

    $.ajax({

       url: "fetch_details.php?cmd=get_tracking_message",

        type:"POST",

        data:{
            transaction_id:$("#transaction_id").val(),
            month:table[1],
            year:table[2],
            status:$(this).val()
        },

       success:function(msg){

    $("#status_remarks").html(msg);

    $("#remarks_text").val(
        $("<div>").html(msg).text()
    );

    $("#status").val($("#mobile_status_select").val());

}

    });

});
    </script>
    <div class="alert" id="alert-container" style="display:none;">
        <button type="button" class="close" data-dismiss="alert">x</button>
        <strong id="alert-status"></strong>
        <span id="alert-message"></span>
    </div>

    <div class="ew-v2-modal-backdrop" id="status_popup_modal">
        <div class="ew-v2-modal" role="dialog" aria-labelledby="statusPopupTitle" aria-modal="true">
            <div class="ew-v2-modal-head">
                <h3 id="statusPopupTitle">Change consignment status</h3>
                <button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
            </div>
            <form id="status_change_consignment" enctype="multipart/form-data">
                <div class="ew-v2-modal-body" id="cancel_grn">
                        <input type="hidden" name="form_name" value="status_change_consignment">
                        <input type="hidden" name="logged_id" id="logged_id" value="<?php echo $logged_id; ?>">
                        <input type="hidden" name="transaction_id" id="transaction_id" value="">
                        <input type="hidden" name="table_names" id="table_names" value="">
                        <input type="hidden" name="grn_id" id="grn_id" value="">
                        <input type="hidden" name="grn_no" id="grn_no" value="">
                        <input type="hidden" name="status" id="status" value="">
                        <input type="hidden" name="total_packages" id="total_packages" value="">
                        <input type="hidden" name="existing_delivered_packages" id="existing_delivered_packages" value="">

                        <div class="form-group mobile-status-group">
                            <label>Status</label>
                            <select class="form-control" id="mobile_status_select"></select>
                        </div>

                        <div id="status_remarks" class="ts-modal-remarks-panel"></div>

                        <div id="delivery_options" style="display:none;">
                            <div class="form-group">
                                <label>Delivery Type <span class="req-star">*</span></label>
                                <div class="ts-delivery-types">
                                    <label class="ts-delivery-opt">
                                        <input type="radio" name="delivery_type" value="partial" id="delivery_partial">
                                        Partially Delivered
                                    </label>
                                    <label class="ts-delivery-opt">
                                        <input type="radio" name="delivery_type" value="full" id="delivery_full">
                                        Fully Delivered
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Total Packages</label>
                                <input type="text" class="form-control" id="total_packages_display" readonly>
                            </div>
                            <div class="form-group" id="partial_package_group" style="display:none;">
                                <label>Packages Delivered <span class="req-star">*</span></label>
                                <select class="form-control" name="delivered_packages" id="delivered_packages">
                                    <option value="">Select packages</option>
                                </select>
                            </div>
                        </div>

                        <div class="ts-modal-datetime-row">
                            <div class="ts-modal-field">
                                <label>Status Date <span class="req-star">*</span></label>
                                <?php echo ew_date_input(array(
                                    'id' => 'status_date',
                                    'name' => 'status_date',
                                    'value' => date('d-m-Y'),
                                    'required' => true,
                                    'attrs' => 'data-allow-future="1"',
                                )); ?>
                            </div>
                            <div class="ts-modal-field">
                                <label>Status Time <span class="req-star">*</span></label>
                                <input type="text" class="form-control" id="status_time" name="status_time" value="<?php echo date('H:i'); ?>" autocomplete="off">
                            </div>
                        </div>

                        <input type="hidden" id="remarks_text" name="remarks">
                </div>
                <div class="ew-v2-modal-foot">
                    <button class="btn btn-default-outline" type="button" id="status_modal_cancel" data-ew-v2-close>Cancel</button>
                    <button class="btn btn-primary" type="button" id="save_status_change">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <div class="ew-v2-modal-backdrop" id="show_remarks_modal">
        <div class="ew-v2-modal" role="dialog" aria-labelledby="remarksPopupTitle" aria-modal="true">
            <div class="ew-v2-modal-head">
                <h3 id="remarksPopupTitle">Remark</h3>
                <button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
            </div>
            <div class="ew-v2-modal-body">
                <label>Remarks (auto generated)</label>
                <div id="view_remarks" class="ts-modal-remarks-panel"></div>
                <input type="hidden" id="view_remarks_text" name="remarks">
            </div>
            <div class="ew-v2-modal-foot">
                <button class="btn btn-primary" type="button" data-ew-v2-close>OK</button>
            </div>
        </div>
    </div>


</body>

</html>