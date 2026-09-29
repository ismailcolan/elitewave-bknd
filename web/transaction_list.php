<?php
require_once ('include/connect.php');
require_once ('include/function.php');
require_once ('include/billing_functions.php');
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
        .edit_disabled {
            pointer-events: none;
            cursor: default;
            color: grey;
        }

        .no-attach {
            color: grey;
            cursor: none !important;
        }

        .disable_action {
            color: #7a888f;
            cursor: not-allowed;
        }

 @media (min-width: 320px) and (max-width:575.98px) {
    .txn-datatable-area {
        overflow-x: auto;
    }
}

.table-actions-click{
    border: solid 1px;
    background: #0A1E3D;
    color: #FFF;
    border-radius: 5px;
}
 #csv_import {
  margin: auto;
  padding: 5px;
  border: 1px dashed #bbb;
  background-color: #fff;
  transition: border-color .25s ease-in-out;
  width:100%;
  &::file-selector-button{
  padding: 0.5em 0.5em;
  border-width: 0;
  border-radius: 2em;
  background-color: hsl(210 70% 30%);
  color: hsl(210 40% 90%);
  transition: all .25s ease-in-out;
  cursor: pointer;
  margin-right: 1em;
  }
  &:hover {
  border-color: #888;
    
  &::file-selector-button{
    
    background-color: hsl(210 70% 40%);
    }
  }
}

/* Month filter field */
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
    box-shadow: none !important;
    -webkit-appearance: none;
    appearance: none;
    cursor: pointer;
}
.txn-period-filter .report-date-group {
    display: flex;
    align-items: center;
}
.txn-period-filter .report-year-select {
    height: 32px;
    min-width: 110px;
    border-radius: 6px;
}
    max-width: 280px;
    flex: 0 0 auto;
}
.month-field-col .control-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 6px;
}

/* ===== Import Consignment modal ===== */
.import-trigger-btn.ew-btn-v2 {
    margin-top: 0;
}
#import_modal .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.import-sample-link {
    margin-bottom: 16px;
}
.import-sample-link a {
    font-weight: 500;
    color: #2f6fed;
}
.upload-dropzone {
    border: 2px dashed #c7cdd6;
    border-radius: 10px;
    padding: 36px 20px;
    text-align: center;
    background: #fafbfc;
    transition: all .15s ease;
    cursor: pointer;
}

/* ===== E-Way Attachments modal ===== */
#eway_popup .ew-v2-modal {
    max-width: 720px;
}
.eway-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
    max-height: 340px;
    overflow-y: auto;
    margin-bottom: 16px;
}
.eway-card {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
    background: #f8fafc;
}
.eway-card-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}
.eway-field {
    min-width: 0;
}
.eway-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 4px;
}
.eway-value {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
    word-break: break-word;
}
.eway-preview-wrap {
    border-top: 1px solid #e2e8f0;
    padding-top: 12px;
}
.eway-thumb {
    display: block;
    max-width: 100%;
    max-height: 160px;
    margin-top: 8px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #fff;
    object-fit: contain;
}
.eway-file-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
    padding: 8px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #fff;
    color: #0A1E3D;
    font-weight: 600;
}
.eway-file-link:hover {
    text-decoration: none;
    background: #eef2ff;
}
.eway-list-actions {
    text-align: center;
    padding-top: 4px;
}
.eway-add-form .form-group {
    margin-bottom: 14px;
}
.eway-add-form label {
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.eway-add-form .form-control {
    border-radius: 8px;
}
.eway-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
@media (max-width: 640px) {
    .eway-card-grid,
    .eway-form-grid {
        grid-template-columns: 1fr;
    }
}
.upload-dropzone.upload-dragover {
    border-color: #2f6fed;
    background: #eef4ff;
}
.upload-dropzone .upload-icon {
    font-size: 34px;
    color: #2f6fed;
    margin-bottom: 10px;
    display: block;
}
.upload-dropzone .upload-text {
    color: #6b7280;
    font-size: 13px;
    margin-bottom: 14px;
}
.upload-dropzone .btn-browse {
    border-radius: 20px;
    padding: 6px 20px;
}
.selected-file-name {
            margin-top: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #0A1E3D;
        }

/* ===== Transaction list UI ===== */
.txn-page-wrap {
    max-width: 100%;
}
.txn-page-wrap .ew-card-toolbar.txn-toolbar {
    overflow: visible;
    position: relative;
    z-index: 10;
}
.txn-toolbar .control-label {
    font-size: 12px;
    font-weight: 600;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 6px;
}
.txn-page-wrap .ew-card.txn-table-card {
    overflow: visible;
}
.txn-datatable-area {
    padding: 12px 16px 16px;
    width: 100%;
}
.txn-datatable-area .dataTables_wrapper {
    width: 100%;
}
.txn-datatable-area .dataTables_length,
.txn-datatable-area .dataTables_filter {
    padding: 12px 0 8px;
    margin: 0;
}
.txn-datatable-area .dataTables_length select {
    border: 1px solid #D8DDE5;
    border-radius: 6px;
    padding: 4px 8px;
    margin: 0 6px;
}
.txn-datatable-area .dataTables_filter input {
    border: 1px solid #D8DDE5;
    border-radius: 6px;
    padding: 6px 10px;
    margin-left: 8px;
    min-width: 180px;
}
.txn-datatable-area .dataTables_info,
.txn-datatable-area .dataTables_paginate {
    padding: 10px 0 4px;
    font-size: 12px;
}
.txn-datatable-area .dataTables_scrollHead,
.txn-datatable-area .dataTables_scrollBody {
    border-bottom: 1px solid #E2E8F0;
}
.txn-datatable-area .dataTables_scrollHeadInner,
.txn-datatable-area .dataTables_scrollHeadInner table {
    width: 100% !important;
}
.trans_list_table {
    width: 100% !important;
    margin: 0 !important;
    border-collapse: collapse !important;
    table-layout: fixed !important;
}
.trans_list_table col.col-sno { width: 42px; }
.trans_list_table col.col-gcn { width: 88px; }
.trans_list_table col.col-pnr { width: 108px; }
.trans_list_table col.col-date { width: 88px; }
.trans_list_table col.col-pkgs { width: 48px; }
.trans_list_table col.col-consignor { width: 130px; }
.trans_list_table col.col-consignee { width: 130px; }
.trans_list_table col.col-dest { width: 90px; }
.trans_list_table col.col-status { width: 100px; }
.trans_list_table col.col-pod { width: 44px; }
.trans_list_table col.col-actions { width: 240px; }
.trans_list_table thead th {
    padding: 11px 8px !important;
    white-space: nowrap;
    vertical-align: middle !important;
    box-sizing: border-box !important;
    overflow: hidden;
    text-overflow: ellipsis;
}
.trans_list_table thead th.sorting,
.trans_list_table thead th.sorting_asc,
.trans_list_table thead th.sorting_desc {
    padding-right: 22px !important;
    background-image: none !important;
}
.trans_list_table tbody td {
    font-size: 13px;
    padding: 9px 8px !important;
    vertical-align: middle !important;
    border-color: #EEF2F7 !important;
    color: #1E293B;
    box-sizing: border-box !important;
    overflow: hidden;
    word-wrap: break-word;
}
.trans_list_table tbody td.col-actions {
    overflow: visible !important;
    white-space: nowrap;
    position: relative;
    z-index: 1;
}
.trans_list_table tbody tr:hover td.col-actions {
    z-index: 4;
}
.trans_list_table tbody td.col-consignor,
.trans_list_table tbody td.col-consignee {
    white-space: normal;
    line-height: 1.4;
    font-size: 13px;
}
.trans_list_table tbody tr:hover {
    position: relative;
    z-index: 2;
}
.trans_list_table tbody tr:hover td {
    background: #F8FAFC !important;
}
.trans_list_table tbody tr:nth-child(even) td {
    background: #FBFCFE;
}
.txn-gcn-no {
    font-weight: 700;
    color: #0A1E3D;
    font-size: 13px;
}
.txn-pnr {
    font-size: 12px;
    color: #64748B;
}
.txn-dest {
    font-weight: 500;
}
.txn-dest-empty {
    color: #CBD5E1;
}
.txn-status-badge {
    display: inline-block;
    min-width: 72px;
    max-width: 100%;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.3;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.txn-status-booked { background: #DBEAFE; color: #1D4ED8; }
.txn-status-transit { background: #FEF3C7; color: #B45309; }
.txn-status-delivered { background: #DCFCE7; color: #15803D; }
.txn-status-cancelled { background: #FEE2E2; color: #B91C1C; }
.txn-status-default { background: #F1F5F9; color: #475569; }
.txn-party-name {
    font-weight: 500;
}
.txn-party-icon {
    font-size: 11px;
    margin-left: 2px;
}
.txn-icon-restricted { color: #DC2626; }
.txn-icon-frequency { color: #2563EB; }
.txn-icon-charges { color: #16A34A; }
.txn-pod-cell {
    text-align: center;
}
.txn-pod-cell .fa-check-circle { color: #16A34A; font-size: 16px; }
.txn-pod-cell .fa-times-circle-o { color: #CBD5E1; font-size: 16px; }
.txn-action-group {
    display: flex;
    flex-wrap: nowrap;
    gap: 3px;
    justify-content: flex-start;
    align-items: center;
}
.txn-action-group .table-actions,
.txn-action-group .dropdown.table-actions {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    flex: 0 0 28px;
    border-radius: 6px;
    background: #E2E8F0;
    color: #0A1E3D !important;
    border: 1px solid #CBD5E1;
    margin: 0 !important;
    transition: background .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease;
    cursor: pointer;
    position: relative;
    z-index: 1;
    font-size: 13px;
    text-decoration: none !important;
}
.txn-action-group .table-actions i {
    color: inherit !important;
    pointer-events: none;
}
.txn-action-group .table-actions:hover:not(.disable_action):not(.no-attach) {
    background: #0A1E3D !important;
    color: #ffffff !important;
    border-color: #0A1E3D !important;
    z-index: 20;
    box-shadow: 0 2px 8px rgba(10, 30, 61, .25);
}
.txn-action-group .btn-invoice,
.txn-action-group .btn-view-pod {
    z-index: 2;
}
.txn-action-group .btn-invoice:hover:not(.no-attach),
.txn-action-group .btn-view-pod:hover {
    z-index: 25;
}
.txn-action-group .table-actions.disable_action,
.txn-action-group .table-actions.no-attach {
    opacity: 0.45;
    cursor: not-allowed;
    background: #F1F5F9;
    color: #94A3B8 !important;
    border-color: #E2E8F0;
}
.txn-action-group .dropdown.table-actions {
    z-index: 3;
}
.txn-action-group .dropdown.table-actions .dropdown-menu {
    min-width: 200px;
    border-radius: 8px;
    box-shadow: 0 12px 32px rgba(10, 30, 61, .18);
    border: 1px solid #E2E8F0;
    padding: 6px 0;
    display: none;
    background: #fff;
}
.txn-print-dd:hover > .dropdown-menu {
    display: none;
}
.txn-print-dd.open {
    background: #0A1E3D !important;
    color: #ffffff !important;
    border-color: #0A1E3D !important;
    z-index: 20;
    box-shadow: 0 2px 8px rgba(10, 30, 61, .25);
}
.txn-action-group .dropdown.table-actions .dropdown-menu li a,
.dropdown-menu.txn-print-floating li a {
    padding: 8px 14px;
    font-size: 12px;
    color: #1E293B;
    display: block;
}
.dropdown-menu.txn-print-floating {
    min-width: 200px;
    border-radius: 8px;
    box-shadow: 0 12px 32px rgba(10, 30, 61, .18);
    border: 1px solid #E2E8F0;
    padding: 6px 0;
    background: #fff;
    list-style: none;
    margin: 0;
}
.dropdown-menu.txn-print-floating li {
    list-style: none;
}
.txn-table-footer {
    padding: 10px 16px 14px;
    border-top: 1px solid #EEF2F7;
    background: #FAFBFC;
}
@media (max-width: 767px) {
    .txn-page-wrap { padding: 0 12px 24px; }
    .txn-toolbar { flex-direction: column; align-items: stretch; }
    .txn-toolbar .text-right { text-align: left !important; }
}

.txn-page-wrap .txn-action-group .table-actions {
    color: #0A1E3D !important;
}
.txn-page-wrap .txn-action-group .table-actions:hover {
    border: 1px solid #0A1E3D !important;
    border-radius: 6px !important;
}
.txn-page-wrap .dataTables_length,
.txn-page-wrap .dataTables_filter,
.txn-page-wrap .dataTables_info,
.txn-page-wrap .dataTables_paginate {
    font-size: 13px !important;
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
                                <h1 class="ew-page-title">Transactions</h1>
                            </div>
                            <div class="ew-toolbar-right">
                                <a href="transactions.php" class="ew-btn-v2 ew-btn-v2-primary">Add Transaction <i class="fa fa-plus"></i></a>
                            </div>
                        </div>
                        <div class="ew-card ew-erp-list txn-table-card">
                        <div class="ew-card-toolbar txn-toolbar">
                            <div class="ew-list-toolbar__left">
                                <form class="form-horizontal" id="transaction_form" style="margin:0;">
                                    <input type="hidden" id="form_name" name="form_name" value="transaction_form">
                                    <input type="hidden" id="edit_id" name="edit_id" value="">
                                    <input type="hidden" id="cmd" name="cmd" value="get_transaction_month_details">
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
                                <button type="button" class="ew-btn-v2 ew-btn-v2-outline import-trigger-btn" data-toggle="modal" data-target="#import_modal">
                                    <i class="fa fa-upload"></i> Import
                                </button>
                            </div>
                        </div>
                        <div class="ew-table-wrap txn-datatable-area">
                            <table class="table table-bordered table-striped trans_list_table" id="txn_list_table">
                                <colgroup>
                                    <col class="col-sno">
                                    <col class="col-gcn">
                                    <col class="col-pnr">
                                    <col class="col-date">
                                    <col class="col-pkgs">
                                    <col class="col-consignor">
                                    <col class="col-consignee">
                                    <col class="col-dest">
                                    <col class="col-status">
                                    <col class="col-pod">
                                    <col class="col-actions">
                                </colgroup>
                                <thead>
                                    <tr>
                                    <th>S.No</th>
                                    <th>GCN No</th>
                                    <th>PNR</th>
                                    <th>GCN Date</th>
                                    <th>Pkgs</th>
                                    <th>Consignor</th>
                                    <th>Consignee</th>
                                    <th>Destination</th>
                                    <th>Status</th>
                                    <th>POD</th>
                                    <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="get_month_details">
                                    <?php
                                    $date = date('d-m-Y');
                                    // $date = "01-06-2022";
                                    // print($date);

                                    $my = date('m-Y');
                                    // $my = "06-2022";
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
                                        $query = '
SELECT t.*, l.tracking_code
FROM transaction_' . $m1 . '_' . $dt[2] . " t
LEFT JOIN transaction_log l
ON t.transaction_id = l.transaction_id
WHERE t.grn_date LIKE '%$my'
AND t.invoice_no != ''
ORDER BY t.grn_date DESC, t.grn_no DESC
";
                                    } else {
                                        $query = '
SELECT t.*, l.tracking_code
FROM transaction_' . $m1 . '_' . $dt[2] . " t
LEFT JOIN transaction_log l
ON t.transaction_id = l.transaction_id
WHERE
(
    t.consigner='" . $_SESSION['company_id'] . "'
    OR t.consignee='" . $_SESSION['company_id'] . "'
)
AND t.grn_date LIKE '%$my'
AND t.invoice_no != ''
ORDER BY t.grn_date DESC, t.grn_no DESC
";
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
                                        $trans_table_name = 'transaction_' . $m1 . '_' . $dt[2];
                                        $gcn_billed = booking_is_gcn_billed($conn, $trans_table_name, $row['transaction_id']);
                                        $gcn_sno = (int) transaction_gcn_serial_no($row);
                                        ?>
                                        <tr>
                                            <td class="text-center" data-order="<?php echo $gcn_sno; ?>"><?php echo $gcn_sno; ?></td>
                                            <td><span class="txn-gcn-no"><?php echo htmlspecialchars($row['grn_no']); ?></span></td>
                                            <td><span class="txn-pnr"><?php echo htmlspecialchars($row['tracking_code'] ?? ''); ?></span></td>
                                            <td><?php echo htmlspecialchars($row['grn_date']); ?></td>
                                            <td class="text-center"><?php echo (int) $pkg_r['pkge']; ?></td>
                                            <td class="col-consignor"><?php echo transaction_list_client_cell($conn, $row['consigner']); ?></td>
                                            <td class="col-consignee"><?php echo transaction_list_client_cell($conn, $row['consignee']); ?></td>
                                            <td><?php
                                                $dest = get_city_name($conn, $row['destination']);
                                                echo $dest !== '' ? '<span class="txn-dest">' . htmlspecialchars($dest) . '</span>' : '<span class="txn-dest-empty">—</span>';
                                            ?></td>
                                            <td><?php echo transaction_list_status_badge($booking, $status); ?></td>

                                            <!--- POD Verification -->
                                            <td class="txn-pod-cell">
                                                <?php
                                                $imagesd1 = array();
                                                $filtered_array = array();
                                                $grn_no = $row['grn_no'];
                                                if ($grn_no != '') {
                                                    $screens = $grn_no;
                                                    $ext = '.jpg';
                                                    $search = $screens . $ext;
                                                    $image_data = array();
                                                    $images = "select screens from pod_files where screens LIKE '%$screens%' ";
                                                    $res = mysqli_query($conn, $images);
                                                    while ($pod_row = mysqli_fetch_assoc($res)) {
                                                        $imagesd1[] = explode('@@', $pod_row['screens']);
                                                    }
                                                    foreach ($imagesd1 as $key => $value1) {
                                                        foreach ($value1 as $key2 => $value2) {
                                                            $filtered_array[] = $value2;
                                                        }
                                                    }
                                                    $filter_img = preg_grep('/^' . $screens . '.*/', $filtered_array);
                                                    $array_unique = array_unique($filter_img);
                                                    $count = count($array_unique);
                                                    // $count = 1;
                                                }
                                                if ($count == 1) {
                                                    ?>
                                                    <a title="POD Uploaded" class="table-actions btn-edit" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-check-circle"></i></a>
                                                <?php
                                                } else if ($count == 2) {
                                                    ?>
                                                    <a style="color:green;" title="POD Uploaded" class="table-actions btn-edit" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-check-circle"></i></a>

                                                <?php
                                                } else {
                                                    ?>
                                                    <a title="POD Not Uploaded" class="table-actions btn-edit" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-times-circle-o"></i></a>

                                                <?php
                                                }
                                                ?>
                                            </td>
                                            <!--- End POD Verification -->

                                            <td class="actions center-content col-actions">
                                                <div class="action-buttons txn-action-group">
                                                    <?php if ($booking == '1') { ?>
                                                        <a title="Info" href="#cancel_grn_popup" class="table-actions show_info_popup"  data-toggle="modal" data-remarks="<?php echo $remarks; ?>" data-createdby="<?php echo $cancelled_by; ?>" data-createdat="<?php echo $updated_at; ?>" id="<?php echo $row['transaction_id']; ?>" ><i class="fa fa-exclamation-circle"></i></a>
                                                        <a title="Edit" href="#" class="table-actions btn-edit edit_disabled disable_action" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-pencil"></i></a>
                                                        <?php echo transaction_list_track_action_html($row); ?>
                                                        <a class="table-actions disable_action" href="javascript:void(0);" data-status="<?php echo $row['status'] ?>" title="View" id="<?php echo $row['transaction_id'] ?>"><i class="fa fa-print"></i></a>
                                                        <a class="table-actions disable_action " href="javascript:void(0);" data-status="<?php echo $row['status'] ?>" title="Invoice" id="<?php echo $row['transaction_id'] ?>"><i class="fa fa-file"></i></a>
                                                        <a class="table-actions send_invoices disable_action " href="javascript:void(0);" title="Send Invoice" id="send_invoices" data-month="<?php echo $m1; ?>" data-year="<?php echo $y; ?>" data-id="<?php echo $row['transaction_id'] ?>"><i class="fa fa-envelope"></i></a>
                                                        <a title="Cancel" class="table-actions btn-edit disable_action" href="javascript:void(0);" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-ban"></i></a>
                                                        <a title="E-way Attachments" href="javascript:void(0);" class="table-actions btn-eway disable_action" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-paperclip"></i></a>
                                                        <?php
                                                        $invoice_query = mysqli_query($conn, 'select * from transaction_images_' . $m1 . '_' . $dt[2] . " where transaction_id='" . $row['transaction_id'] . "' ");
                                                        $invoice_count = mysqli_num_rows($invoice_query);
                                                        if ($invoice_count > 0) {
                                                            ?>
                                                        <!-- <a title="Invoice Attachments" href="javascript:void(0);" class="table-actions btn-invoices disable_action"  id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-picture-o"></i></a> -->
                                                        <?php
                                                        } else {
                                                        ?>
                                                        <!-- <a title="No Attachments" href="javascript:void(0);" class="table-actions btn-eway disable_action" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-picture-o"></i></a> -->
                                                        <?php
                                                        }
                                                        ?>
                                                    <?php
                                        } else {
                                                    ?>
                                                        <?php
                                                        // Full edit for submitted bookings (status 1–7). Pay-at-Booking mode (3) stays locked.
                                                        // After delivery (status 8): payment/billing edit only until invoiced.
                                                        if ($consignment_mode == '3') {
                                                            ?>
                                                        <a title="Edit" href="javascript:void(0)" class="table-actions btn-edits disable_action" id="<?php echo $row['transaction_id']; ?>" readonly><i class="fa fa-pencil"></i></a>
                                                            <!-- <a title="Pay at Booking" href="#" class="table-actions btn-edit edit_disabled" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-pencil"></i></a> -->

                                                        <?php
                                                        } elseif ($gcn_billed) {
                                                            ?>
                                                        <a title="Invoiced — edit locked" href="javascript:void(0)" class="table-actions btn-edits disable_action" id="<?php echo $row['transaction_id']; ?>" readonly><i class="fa fa-pencil"></i></a>
                                                        <?php
                                                        } else {
                                                            $edit_title = ((int) $status === 8) ? 'Edit Payment / Billing' : 'Edit';
                                                            if ($row['book_manual'] == 2) {
                                                                ?>
                                                            <a title="<?php echo $edit_title; ?>" href="transactions_manual.php?key=<?php echo md5($row['transaction_id']); ?>&m=<?php echo $m1; ?>&y=<?php echo $dt[2] ?>" class="table-actions btn-edit" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-pencil"></i></a>
                                                        <?php
                                                            } else {
                                                        ?>
                                                            <a title="<?php echo $edit_title; ?>" href="transactions.php?key=<?php echo md5($row['transaction_id']); ?>&m=<?php echo $m1; ?>&y=<?php echo $dt[2] ?>" class="table-actions btn-edit" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-pencil"></i></a>

                                                        <?php
                                                            }
                                                        }
                                                        ?>
                                                        <?php echo transaction_list_track_action_html($row); ?>
                                                        <!-- <a class="table-actions " target="BLANK" href="transaction_pdf.php?month=<?php echo $m1; ?>&year=<?php echo $y; ?>&id=<?php echo $row['transaction_id']; ?>" data-status="<?php echo $row['status'] ?>" title="View" id="<?php echo $row['transaction_id'] ?>"><i class="fa fa-print"></i></a> -->
                                                        
                                                        <span class="table-actions dropdown txn-print-dd" title="Print GR"><i class="fa fa-print"></i>
                                                            <ul class="dropdown-menu">
                                                                <li><a href="transaction_pdf.php?month=<?php echo $m1; ?>&year=<?php echo $y; ?>&id=<?php echo $row['transaction_id']; ?>&copy=consignor" data-status="<?php echo $row['status'] ?>" title="View" id="<?php echo $row['transaction_id'] ?>" target="_blank">Consignor GR</a></li>
                                                                <li><a href="transaction_pdf.php?month=<?php echo $m1; ?>&year=<?php echo $y; ?>&id=<?php echo $row['transaction_id']; ?>&copy=consignee" data-status="<?php echo $row['status'] ?>" title="View" id="<?php echo $row['transaction_id'] ?>" target="_blank">Consignee GR</a></li>
                                                                <li><a href="transaction_pdf.php?month=<?php echo $m1; ?>&year=<?php echo $y; ?>&id=<?php echo $row['transaction_id']; ?>&copy=pod" data-status="<?php echo $row['status'] ?>" title="View" id="<?php echo $row['transaction_id'] ?>" target="_blank">P.O.D GR</a></li>
                                                                <li><a href="transaction_pdf.php?month=<?php echo $m1; ?>&year=<?php echo $y; ?>&id=<?php echo $row['transaction_id']; ?>&copy=accounts" data-status="<?php echo $row['status'] ?>" title="View" id="<?php echo $row['transaction_id'] ?>" target="_blank">Accounts GR</a></li>
                                                            </ul>
                                                        </span>

                                                        <a class="table-actions " target="BLANK" href="gst_invoice_page.php?month=<?php echo $m1; ?>&year=<?php echo $y; ?>&id=<?php echo $row['transaction_id']; ?>" data-status="<?php echo $row['status'] ?>" title="Invoice" id="<?php echo $row['transaction_id'] ?>"><i class="fa fa-file"></i></a>
                                                        <?php
                                                        if ($consignment_mode == '1' || $consignment_mode == '4') {
                                                            $restricted = check_invoice_restricted($conn, $row['consignee']);
                                                            $pay_at_book = 0;
                                                        } else {
                                                            $restricted = check_invoice_restricted($conn, $row['consigner']);
                                                            $pay_at_book = 0;
                                                        }
                                                        if ($consignment_mode == '3') {
                                                            $pay_at_book = 1;
                                                        }
                                                        if ($status == 8 && $restricted == 1 && $pay_at_book != 1) {
                                                            ?>
                                                            <a class="table-actions send_invoice " href="#" title="Send Invoice" id="send_invoice" data-month="<?php echo $m1; ?>" data-year="<?php echo $y; ?>" data-id="<?php echo $row['transaction_id'] ?>"><i class="fa fa-envelope"></i></a>
                                                        <?php } else { ?>
                                                            <a class="table-actions disable_action" href="javascript:void(0)" ><i class="fa fa-envelope"></i></a>
                                                        <?php
                                                        }
                                                        if ($status < 6) {  // disable if consignment status is above in transit 3
                                                            ?>
                                                        	<a title="Cancel" class="table-actions btn-edit cancel_booking" href="#cancel_grn_popup" id="<?php echo $row['transaction_id']; ?>"  data-toggle="modal" data-grnid="<?php echo $row['grn_no']; ?>" data-tabid="<?php echo $trans_name; ?>" ><i class="fa fa-ban"></i></a>
                                                    	<?php } else { ?>
                                                            <a class="table-actions disable_action" href="javascript:void(0)"><i class="fa fa-ban"></i></a>
                                                        <?php } ?>
                                                        <a title="E-way Attachments" href="javascript:void(0);" class="table-actions btn-eway" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-paperclip"></i></a>
                                                        <?php
                                                        $invoice_query = mysqli_query($conn, 'select * from transaction_images_' . $m1 . '_' . $dt[2] . " where transaction_id='" . $row['transaction_id'] . "' ");
                                                        $invoice_count = mysqli_num_rows($invoice_query);
                                                        if ($invoice_count > 0) {
                                                            ?>
                                                            <a title="Invoice Attachments" href="#invoice_popup" class="table-actions btn-invoice" data-toggle="modal" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-picture-o"></i></a>
                                                        <?php
                                                        } else {
                                                            ?>
                                                            <a title="No Attachments" href="#" class="table-actions btn-invoice no-attach" data-toggle="modal" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-picture-o"></i></a>
                                                        <?php
                                                        }
                                                        ?>
 
                                                    <?php } ?>
                                                    <?php if ($count >= 1) { ?>
                                                        <a title="View POD" href="#pod_popup" class="table-actions btn-view-pod" data-toggle="modal" data-grn="<?php echo $row['grn_no']; ?>" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-camera"></i></a>
                                                    <?php } else { ?>
                                                        <a title="No POD Uploaded" href="javascript:void(0);" class="table-actions no-attach"><i class="fa fa-camera"></i></a>
                                                    <?php } ?>
                                                </div>

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
        window.txnListTable = null;

        function destroyTxnListDataTable() {
            var $table = $('#txn_list_table');
            if (!$table.length || !$.fn.dataTable) {
                window.txnListTable = null;
                return;
            }
            if ($.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($table[0])) {
                try {
                    $table.dataTable().fnDestroy();
                } catch (e) {}
            }
            window.txnListTable = null;
        }

        function initTxnListDataTable() {
            if (!$.fn.dataTable) {
                return;
            }
            var $table = $('#txn_list_table');
            if (!$table.length) {
                return;
            }
            destroyTxnListDataTable();
            if ($.fn.dataTableExt) {
                $.fn.dataTableExt.sErrMode = 'throw';
            }
            window.txnListTable = $table.dataTable({
                sDom: '<"txn-dt-top"lf>rt<"txn-dt-bottom"ip>',
                sPaginationType: 'full_numbers',
                iDisplayLength: 10,
                aLengthMenu: [[10, 25, 50, 100, -1], ['10', '25', '50', '100', 'All']],
                aaSorting: [[0, 'desc']],
                bAutoWidth: false,
                bDestroy: true,
                oSearch: { sSearch: '', bSmart: false, bRegex: false, bCaseInsensitive: true },
                aoColumnDefs: [
                    { bSortable: false, aTargets: [9, 10] },
                    { sType: 'numeric', aTargets: [0] },
                    { sClass: 'text-center', aTargets: [0, 4, 9] },
                    { sClass: 'col-consignor', aTargets: [5] },
                    { sClass: 'col-consignee', aTargets: [6] },
                    { sClass: 'col-actions', aTargets: [10] }
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

        function fetchMonthTransactions() {
            destroyTxnListDataTable();
            $.ajax({
                url: 'fetch_details.php',
                type: "GET",
                data: $('#transaction_form').serialize(),
                success: function(result) {
                    $('#get_month_details').html(result);
                    initTxnListDataTable();
                },
                error: function() {
                    $('#get_month_details').html('');
                    initTxnListDataTable();
                    ewToast('Could not load bookings.', 'error');
                }
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

        $(document).ready(function() {
            $(".loading-page").hide();
            try {
                initTxnListDataTable();
            } catch (e) {
                console.error('DataTable init failed:', e);
            }

            var monthFetchTimer = null;
            var monthPickerReady = false;
            setTimeout(function() { monthPickerReady = true; }, 500);
            setTxnPeriodType($('#report_type').val() || 'ALL');
            fetchMonthTransactions();
            $(document).on('change', '#report_type', function() {
                setTxnPeriodType($(this).val());
                if (monthPickerReady) {
                    fetchMonthTransactions();
                }
            });
            $('#month, #date').on('changeDate', function() {
                if (!monthPickerReady) {
                    return;
                }
                clearTimeout(monthFetchTimer);
                monthFetchTimer = setTimeout(fetchMonthTransactions, 150);
            });
            $('#year').on('change', function() {
                if (!monthPickerReady) {
                    return;
                }
                fetchMonthTransactions();
            });

            $(document).on('click', '.send_invoice', function(e) {

                e.preventDefault();
                if (confirm('Are You Sure Want to Send Invoice?')) {
                    $(".form-data-saving").show();
                    var month = $(this).data('month');
                    var year = $(this).data('year');
                    var transaction_id = $(this).data('id');

                    //alert(month);
                    $.ajax({
                        url: 'send_invoice.php',
                        type: "POST",
                        data: {
                            month: month,
                            year: year,
                            transaction_id: transaction_id
                        },
                        success: function(result) {
                            $(".form-data-saving").hide();
                            console.log(result);
                            if (result == 1) {
                                $(".form-data-saving").hide();
                                $("#alert-status").text("");
                                $("#alert-message").text("Invoice Sent Successfully");
                                $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-success");
                                    //location.reload();
                                });

                            } else if (result == 2) {

                                $(".form-data-saving").hide();
                                $("#alert-status").text("");
                                $("#alert-message").text("Invoice Restricted to Consignor");
                                $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                $("#alert-container").hide();
                                $("#alert-container").removeClass("alert-danger");
                                    //location.reload();
                                });
                            } else {
                                $(".form-data-saving").hide();
                                $("#alert-status").text("");
                                $("#alert-message").text("Invoice Sent Failure!");
                                $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                $("#alert-container").hide();
                                $("#alert-container").removeClass("alert-danger");
                                    //location.reload();
                                });
                            }
                            //$('#get_month_details').html(result);
                        },
                        error: function(jqxhr) {
                            ewToast(jqxhr.responseText, 'error');
                        }
                    });
                } else {
                    console.log("Please Click Email Icon!");
                }
                //alert(transaction_id);
            });


            // 	$(function() {
            //     $("body").delegate(".date-picker", "focusin", function(){
            //         $(this).datepicker();
            //     });
            // });

            /*
	$('.date-picker').on("click", function() {
				$(this).datepicker({
					changeMonth: true,
					changeYear: true,
					changeDate:false,
					format: 'mm-yyyy',
					}).datepicker('show');
			});

	$('.issuedate,.expiredate').on("click", function() {
				$(this).datepicker({
					changeMonth: true,
					changeYear: true,
					changeDate:true,
					format: 'dd-mm-yyyy',
					}).datepicker('show');
			});
*/


            //Cancel Grn Popup Script 

            //open popup
            $(document).on('click', '.cancel_booking', function() {
                var user_id = '<?php echo $logged_id; ?>';
                var id = $(this).attr('id');
                var grn_no = $(this).data('grnid');
                var table_name = $(this).data('tabid');;
                $("#transaction_id").val(id);
                $("#table_names").val(table_name);
                $("#grn_no").val(grn_no);
                $("#logged_id").val(user_id);
                $("#cancel_grn").show();
				$("#show_cancel_grn").hide();

            });


            $(document).on('click', '.show_info_popup', function() {

                var remarkss = $(this).data('remarks');
                var created_by = $(this).data('createdby');
                var created_at = $(this).data('createdat');
                $('#show_remarks').val(remarkss);
                $('#show_client_id').html(created_by);
                $('#show_created_at').html(created_at);
                $("#show_cancel_grn").show();
                $("#cancel_grn").hide();

            });



            //close pop

            $(document).on('click', '#close_booking_cancel', function() {
                $("#cancel_grn_popup").modal('hide');
            });

            //end close pop

            //Save Cancel Grn Booking

            $(document).on('click', '#save_cancel_booking', function() {
                //alert("You are in");

                var remarks = $('#remarks').val();
                if (remarks == '') {
                    var message = 'Please Enter Remarks';
                    var show = $('#error_msg').show();
                    var display_msg = $('#error_msg').html(message);

                } else {
                    if (!confirm("Once Booking Cancelled can not be Revised!")) {
                        return false;
                    }
                    var show = $('#error_msg').hide();
                    $("#cancel_grn_popup").modal('hide');
                    $(".form-data-saving").show();
                    var form = $("#cancel_booking_form");
                    $.ajax({
                        url: "save_details.php",
                        type: "post",
                        data: form.serialize(),
                        success: function(response) {
                            console.log(response);
                            if (response == 1) {
                                $(".form-data-saving").hide();
                                $("#alert-status").text("");
                                $("#alert-message").text("Booking Cancelled Successfully, please wait until page refresh");
                                $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-success");
                                    //location.reload();
                                });

                            } else if (response == 2) {
                                $(".form-data-saving").hide();
                                $("#cancel_grn_popup").modal('hide');
                                $("#alert-status").text("");
                                $("#alert-message").text("Booking Already Cancelled");
                                $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-danger");
                                    // location.reload();
                                });
                            } else {
                                $(".form-data-saving").hide();

                                $("#cancel_grn_popup").modal('hide');

                                $("#alert-status").text("");
                                $("#alert-message").text("Booking Cancel Failed! Try Again");
                                $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-danger");
                                    // location.reload();
                                });

                            }


                        }
                    })
                }




            });


            //End



            //Cancel Grn Popup Script 

            function resetEwayUploadUi() {
                $('#eway_upload_file').val('');
                $('#eway_upload_wrap').removeClass('has-file');
                $('#eway_upload_wrap .ew-upload-image__filename').text('').attr('title', '');
            }

            function resetEwayModal() {
                if ($('#eway_form').length && $('#eway_form')[0]) {
                    $('#eway_form')[0].reset();
                }
                resetEwayUploadUi();
                $('#eway_issue_date, #eway_expire_date').each(function() {
                    var $inp = $(this);
                    $inp.val('');
                    if ($inp.data('datepicker')) {
                        $inp.datepicker('update', '');
                    }
                });
                $('#old_attach_div').html('').show();
                $('#attachment_body').hide();
                $('#eway_modal_foot').hide();
            }

            function closeEwayModal() {
                if (typeof ewV2CloseModal === 'function') {
                    ewV2CloseModal('eway_popup');
                } else {
                    $('#eway_popup').removeClass('open');
                }
                resetEwayModal();
            }

            function showEwayForm(show) {
                if (show) {
                    $('#attachment_body').show();
                    $('#eway_modal_foot').show();
                    if (typeof initEwDatepickers === 'function') {
                        initEwDatepickers('#attachment_body');
                    }
                } else {
                    $('#attachment_body').hide();
                    $('#eway_modal_foot').hide();
                }
            }

            function ewayFileLabel(files) {
                if (!files || !files.length) {
                    return '';
                }
                if (files.length === 1) {
                    return files[0].name;
                }
                return files.length + ' files selected';
            }

            function ewaySetInputFiles($input, files) {
                if (typeof DataTransfer !== 'undefined' && files && files.length) {
                    var dt = new DataTransfer();
                    for (var i = 0; i < files.length; i++) {
                        dt.items.add(files[i]);
                    }
                    $input[0].files = dt.files;
                }
            }

            function initEwayUploadMulti() {
                var $wrap = $('#eway_upload_wrap');
                if (!$wrap.length || $wrap.data('ew-upload-init')) {
                    return;
                }
                $wrap.data('ew-upload-init', 1);
                var $input = $wrap.find('.ew-upload-image__input');
                var $zone = $wrap.find('.ew-upload-image__zone');
                var $filename = $wrap.find('.ew-upload-image__filename');

                function applyFiles(fileList) {
                    if (!fileList || !fileList.length) {
                        $filename.text('').attr('title', '');
                        $wrap.removeClass('has-file');
                        return;
                    }
                    var label = ewayFileLabel(fileList);
                    $filename.text(label).attr('title', label);
                    $wrap.addClass('has-file');
                }

                $zone.on('click', function(e) {
                    if ($(e.target).is('input')) {
                        return;
                    }
                    $input.trigger('click');
                });

                $zone.on('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        $input.trigger('click');
                    }
                });

                $input.on('change', function() {
                    applyFiles(this.files);
                });

                $zone.on('dragover dragenter', function(e) {
                    e.preventDefault();
                    $zone.addClass('is-dragover');
                });

                $zone.on('dragleave drop', function(e) {
                    e.preventDefault();
                    $zone.removeClass('is-dragover');
                });

                $zone.on('drop', function(e) {
                    var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
                    if (!files || !files.length) {
                        return;
                    }
                    ewaySetInputFiles($input, files);
                    applyFiles($input[0].files);
                });
            }

            initEwayUploadMulti();

            $(document).on('click', '#new_eway', function() {
                $('#old_attach_div').hide();
                showEwayForm(true);
            });

            $(document).on('click', '#eway_cancel', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if ($('#old_attach_div').html().trim() !== '') {
                    $('#old_attach_div').show();
                    showEwayForm(false);
                } else {
                    closeEwayModal();
                }
            });

            $(document).on('click', '#eway_popup .ew-v2-modal-close[data-ew-v2-close]', function() {
                resetEwayModal();
            });


            $(document).on('click', '.close-popup', function() {
                $(".form-data-saving").hide();
                $("#alert-status").text("");
                $("#alert-message").text("Saved Successfully, please wait until page refresh");
                $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                    $("#alert-container").hide();
                    $("#alert-container").removeClass("alert-success");
                    location.reload();
                });
            });
            //Button Delete

            $(document).on('click', '.btn-eway', function(ev) {
                ev.preventDefault();
                if ($(this).hasClass('disable_action')) {
                    return;
                }
                var id = $(this).attr('id');
                var table_name = '<?php echo $trans_image_name; ?>';
                resetEwayModal();
                $("#attachment_id").val(id);
                $("#table_name").val(table_name);
                if (typeof ewV2OpenModal === 'function') {
                    ewV2OpenModal('eway_popup');
                } else {
                    $('#eway_popup').addClass('open');
                }
                $.ajax({
                    url: 'fetch_details.php',
                    type: "GET",
                    data: {
                        cmd: "get_existing_attchment",
                        transaction_id: id,
                        table_name: table_name
                    },
                    success: function(result) {
                        if (result != 0 && String(result).trim() !== '0') {
                            $('#old_attach_div').html(result).show();
                            showEwayForm(false);
                        } else {
                            $('#old_attach_div').html('').hide();
                            showEwayForm(true);
                        }
                    },
                    error: function() {
                        $('#old_attach_div').html('<p class="text-danger">Could not load attachments.</p>').show();
                        showEwayForm(false);
                    }
                });

            });
            //invoice attachment

            $(document).on('click', '.btn-invoice', function(ev) {
                var id = $(this).attr('id');
                var table_name = '<?php echo $trans_image_name; ?>';

                $.ajax({
                    url: 'fetch_details.php',
                    type: "GET",
                    data: {
                        cmd: "get_existing_invoice_attchment",
                        transaction_id: id,
                        table_name: table_name
                    },
                    success: function(result) {
                        console.log(result);
                        if (result != 0)
                            $('#invoice_attach_div').html(result);

                    }
                });
            });



            //Active Inactive
            $(document).on('click', '.btn-active', function(ev) {
                $(".form-data-saving").show();
                var status1 = '';
                var msg = '';
                var status = $(this).attr('data-status');
                if (status == '1') {
                    status1 = '0';
                    msg = "Activated";
                } else {
                    status1 = '1';
                    msg = "In-Activated";
                }
                $.post('save_details.php', {
                    form_name: "inacv_client",
                    tbl_id: $(this).attr("id"),
                    status: status1
                }, function(data, status) {
                    console.log(data);
                    if (data == 1) {
                        $(".form-data-saving").hide();
                        $("#alert-status").text("");
                        $("#alert-message").text("Department Is " + msg + "...");
                        $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                            $("#alert-container").hide();
                            $("#alert-container").removeClass("alert-success");
                            location.reload();
                        });
                    } else if (data == 2) {
                        $(".form-data-saving").hide();
                        $("#alert-status").text("");
                        $("#alert-message").text("Department Is " + msg + "...");
                        $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                            $("#alert-container").hide();
                            $("#alert-container").removeClass("alert-danger");
                            location.reload();
                        });
                    } else if (data == "404-del") {
                        $(".delete-error-popup").show();
                        $(".form-data-saving").hide();
                    }

                });
            });


            //	Button Edit
            $(document).on('click', '.btn-edit', function(ev) {
                $(".form-data-saving").show();
                var tbl_id = $(this).attr("id");
                $.ajax({
                    cache: false,
                    url: 'fetch_details.php', // url where to submit the request
                    type: "GET", // type of action POST || GET
                    dataType: 'json', // data type
                    data: {
                        cmd: "get_branch_details",
                        tbl_id: tbl_id
                    }, // post data || get data
                    success: function(result) {
                        console.log(result);
                        $(".form-data-saving").hide();
                        $("#form_name").val("edit_branch");
                        $("#edit_id").val(result['branch_id']);
                        $("#department_code").val(result['department_code']);
                        $('#department_name').val(result['department_name']);

                    },
                    error: function(jqxhr) {
                        ewToast(jqxhr.responseText, 'error');
                    }
                });
            });



            $(document).on('click', '#save_eway', function(ev) {
                var formData = new FormData(document.getElementById("eway_form"));
                if ($('#eway_form').valid() == true) {
                    var $btn = $(this);
                    $btn.prop("disabled", true);
                    $.ajax({
                        url: "save_details.php",
                        type: "post",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(result) {
                            $btn.prop("disabled", false);
                            if (result == 1) {
                                if (typeof ewFormToast === 'function') {
                                    ewFormToast('E-Way attachment saved successfully.', 'success', 4000);
                                }
                                closeEwayModal();
                                setTimeout(function() { location.reload(); }, 800);
                            } else if (typeof ewFormToast === 'function') {
                                ewFormToast('Could not save attachment. Please check all fields and try again.', 'error', 5000);
                            }
                        },
                        error: function() {
                            $btn.prop("disabled", false);
                            if (typeof ewFormToast === 'function') {
                                ewFormToast('Network error while saving attachment.', 'error', 5000);
                            }
                        }
                    });
                }

            });


        });
        $(window).load(function() {
            $(".loading-page").hide();
        });
        setTimeout(function() {
            $(".loading-page").hide();
        }, 3000);
        
        function placeTxnPrintMenu($dd) {
            var $menu = $dd.children('.dropdown-menu');
            if (!$menu.length) {
                return;
            }
            if (!$menu.data('txn-print-parent')) {
                $menu.data('txn-print-parent', $dd);
            }
            if (!$menu.parent().is('body')) {
                $menu.appendTo(document.body);
            }
            $menu.css({
                display: 'block',
                visibility: 'hidden',
                position: 'fixed',
                top: '0px',
                left: '0px',
                bottom: 'auto',
                right: 'auto'
            });
            var btn = $dd[0].getBoundingClientRect();
            var mh = $menu.outerHeight() || 150;
            var mw = $menu.outerWidth() || 168;
            var spaceBelow = window.innerHeight - btn.bottom;
            var openUp = spaceBelow < mh + 10;
            var top = openUp ? (btn.top - mh - 6) : (btn.bottom + 6);
            if (top < 8) {
                top = 8;
            }
            var left = btn.right - mw;
            if (left < 8) {
                left = 8;
            }
            if (left + mw > window.innerWidth - 8) {
                left = window.innerWidth - mw - 8;
            }
            $menu.addClass('txn-print-floating').css({
                display: 'block',
                visibility: 'visible',
                position: 'fixed',
                zIndex: 10050,
                top: top + 'px',
                left: left + 'px',
                bottom: 'auto',
                right: 'auto'
            });
        }

        function hideTxnPrintMenus() {
            $('.txn-print-dd').removeClass('open');
            $('.txn-print-dd .dropdown-menu, body > .dropdown-menu.txn-print-floating').each(function() {
                var $menu = $(this);
                var $parent = $menu.data('txn-print-parent');
                $menu.hide().css({
                    position: '',
                    top: '',
                    left: '',
                    bottom: '',
                    right: '',
                    visibility: '',
                    zIndex: ''
                }).removeClass('txn-print-floating');
                if ($parent && $parent.length) {
                    $menu.appendTo($parent);
                }
            });
        }

        $(document).on('click', '.txn-print-dd', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $dd = $(this);
            var wasOpen = $dd.hasClass('open');
            hideTxnPrintMenus();
            if (!wasOpen) {
                $dd.addClass('open');
                placeTxnPrintMenu($dd);
            }
        });
        $(document).on('click', '.txn-print-dd .dropdown-menu', function(e) {
            e.stopPropagation();
        });
        $(document).on('click', function() {
            hideTxnPrintMenus();
        });
        $(window).on('scroll resize', hideTxnPrintMenus);

		
		// import excel file to database
		$(document).ready(function() {
            $("#submit_btn").click(function() { // Change event listener to submit button click
                const file = $("#csv_import").prop('files')[0]; // Get the selected file

                if (!file) {
                    ewToast('Please select a file', 'warning');
                    return;
                }

                const formData = new FormData(); // Create form data object
                formData.append('csv_import', file); // Append file to form data

                // Show the loader
                $(".loading-page").show();

                $.ajax({
                    url: "import.php",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        // Hide the loader
                        $(".loading-page").hide();

                        if (response.includes("Data inserted successfully")) {
                            ewToast("Data inserted successfully!", 'success');
                            $("#import_modal").modal('hide');
                            fetchMonthTransactions();
                        } else if (response.includes("Data already exists")) {
                            // alert("Data already exists!");
                        // Parse the JSON-encoded response to get the existing data
            var existingData = JSON.parse(response);
            // Alert the existing data
            ewToast("Data already exists: " + existingData.join(", "), 'warning');
                        } else {
                            ewToast("An error occurred: " + response, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Hide the loader
                        $(".loading-page").hide();
                        console.error(xhr.responseText);
                        ewToast("Error: " + xhr.responseText, 'error');
                    }
                });
            });

            $(document).on('click', '.btn-view-pod', function(ev) {
    var grn_no = $(this).data('grn');
    $('#pod_attach_div').html('<p><i class="fa fa-spinner fa-spin"></i> Loading...</p>');
    $.ajax({
        url: 'fetch_details.php',
        type: "GET",
        data: {
            cmd: "get_pod_attachment",
            grn_no: grn_no
        },
        success: function(result) {
            $('#pod_attach_div').html(result);
        },
        error: function() {
            $('#pod_attach_div').html('<p>Failed to load POD image.</p>');
        }
    });
});
        });

        /* ===================================================
           Import Consignment modal: drag & drop upload zone
           =================================================== */
        (function() {
            var $dropzone = $("#upload_dropzone");
            var $fileInput = $("#csv_import");
            var $fileNameLabel = $("#selected_file_name");

            function showFileName(file) {
                if (file) {
                    $fileNameLabel.html('<i class="fa fa-file-text-o"></i>&nbsp; ' + file.name);
                } else {
                    $fileNameLabel.html('');
                }
            }

            $("#browse_btn").on("click", function(e) {
                e.stopPropagation();
                $fileInput.trigger("click");
            });

            $dropzone.on("click", function() {
                $fileInput.trigger("click");
            });

            $fileInput.on("change", function() {
                showFileName(this.files[0]);
            });

            $dropzone.on("dragover", function(e) {
                e.preventDefault();
                e.stopPropagation();
                $dropzone.addClass("upload-dragover");
            });

            $dropzone.on("dragleave", function(e) {
                e.preventDefault();
                e.stopPropagation();
                $dropzone.removeClass("upload-dragover");
            });

            $dropzone.on("drop", function(e) {
                e.preventDefault();
                e.stopPropagation();
                $dropzone.removeClass("upload-dragover");
                var files = e.originalEvent.dataTransfer.files;
                if (files && files.length) {
                    $fileInput[0].files = files;
                    showFileName(files[0]);
                }
            });

            $("#import_modal").on("hidden.bs.modal", function() {
                $fileInput.val("");
                showFileName(null);
            });
        })();
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
            <!--<span class="popup_close" id="popup_close">X</span>-->
        </div>
    </div>

    <div class="ew-v2-modal-backdrop" id="eway_popup">
        <div class="ew-v2-modal ew-v2-modal--wide">
            <div class="ew-v2-modal-head">
                <h3>E-Way Attachments</h3>
                <button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
            </div>
            <div class="ew-v2-modal-body">
                <div id="old_attach_div"></div>
                <div id="attachment_body" style="display:none">
                    <form id="eway_form" class="eway-add-form" enctype="multipart/form-data">
                        <input type="hidden" name="form_name" value="add_eway_bill">
                        <input type="hidden" name="attachment_id" id="attachment_id" value="">
                        <input type="hidden" name="table_name" id="table_name" value="">

                        <div class="form-group">
                            <label class="control-label">E-Way Attachment</label>
                            <div class="ew-upload-image ew-upload-image--multi" id="eway_upload_wrap">
                                <div class="ew-upload-image__zone" tabindex="0" role="button" aria-label="Upload E-Way files">
                                    <input type="file" name="attachment[]" id="eway_upload_file" class="ew-upload-image__input" required multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,image/*,application/pdf">
                                    <div class="ew-upload-image__body">
                                        <i class="fa fa-cloud-upload" aria-hidden="true"></i>
                                        <span class="ew-upload-image__hint">Drag &amp; drop or browse</span>
                                        <span class="ew-upload-image__meta">Image or PDF · multiple files allowed</span>
                                        <span class="ew-upload-image__filename"></span>
                                        <span class="ew-btn-v2 ew-btn-v2-outline ew-upload-image__browse">Browse</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">E-Way Bill No</label>
                            <input type="text" class="form-control" name="eway_bill_no" required placeholder="Enter e-way bill number">
                        </div>
                        <div class="eway-form-grid">
                            <div class="form-group">
                                <label class="control-label">Date of Issue</label>
                                <?php echo ew_date_input(array('id' => 'eway_issue_date', 'name' => 'issue_date', 'placeholder' => 'dd-mm-yyyy')); ?>
                            </div>
                            <div class="form-group">
                                <label class="control-label">Date of Expiry</label>
                                <?php echo ew_date_input(array('id' => 'eway_expire_date', 'name' => 'expire_date', 'placeholder' => 'dd-mm-yyyy')); ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="ew-v2-modal-foot" id="eway_modal_foot" style="display:none">
                <button type="button" class="btn btn-default-outline" id="eway_cancel">Cancel</button>
                <button type="button" class="btn btn-primary" id="save_eway">Submit</button>
            </div>
        </div>
    </div>


    <div class="modal fade " id="cancel_grn_popup" style="display:none">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                    <h4 class="modal-title" style="color:#fff">
                        Cancel Booking
                    </h4>
                </div>
                <div class="modal-body" id="old_cancel_grn">

                </div>

                <!--- Cancel Booking / GRN Model -->
                <div class="modal-body" style="display: none" id="cancel_grn">
                    <form id="cancel_booking_form" enctype="multipart/form-data">
                        
                        <input type="hidden" name="form_name" value="cancel_booking_consignment">
                        <input type="hidden" name="logged_id" id="logged_id" value="">
                        <input type="hidden" name="transaction_id" id="transaction_id" value="">
                        <input type="hidden" name="table_names" id="table_names" value="">
                        <input type="hidden" name="grn_no" id="grn_no" value="">

                        <label class="control-label">Remarks:</label>
                        <textarea class="form-control" name="remarks" id="remarks" rows="4" required="required"></textarea>
                        <small name="error_msg" id="error_msg" style="display:none; color:red;"></small>
                        <br>
                        <div class="modal-footer" style="text-align: center;">
                            <button class="btn btn-danger btn-cancel" type="button" id="close_booking_cancel">Cancel</button>
                            <button class="btn btn-primary btn-submit" type="button" id="save_cancel_booking">Submit</button>
                        </div>
                </div>


                <!--- Cancel Booking / GRN Model -->

                <!--Show Remarks Popup -->
                <div class="modal-body" style="display: none" id="show_cancel_grn">
					<form id="cancel_booking_form" enctype="multipart/form-data">

						<label class="control-label">Remarks:</label>
						<textarea class="form-control" name="show_remarks" id="show_remarks" rows="4" required="required" readonly></textarea>
						<!-- <small name="show_client_id" id="show_client_id" >Cancelled By : <span id="admin_id"></span></small></br>
						<small name="show_created_at" id="show_created_at" >Cancelled at : <span id="created_at"></span></small> -->
						<small>Cancelled by : </small><small name="show_client_id" id="show_client_id" ><span id="span_d"></span></small></br>
						<small>Cancelled at : </small><small name="show_created_at" id="show_created_at" ></small>
						
						<br>
						<div class="modal-footer" style="text-align: center;">
							<button class="btn btn-info btn-cancel" type="button" id="close_booking_cancel">Close</button>
						</div>
				</div>
                <!---End-->


            </div>
            </form>

        </div>
    </div>


    <!-- Import Consignment Modal -->
    <div class="modal fade" id="import_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Import Consignment</h4>
                    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="import-sample-link">
                        <a href="download_csv.php"><i class="fa fa-download"></i>&nbsp; Download Sample Excel File</a>
                    </div>

                    <form method="POST" name="excel_import_form" id="excel_import_form" enctype="multipart/form-data">
                        <div class="upload-dropzone" id="upload_dropzone">
                            <i class="fa fa-cloud-upload upload-icon"></i>
                            <div class="upload-text">Drag &amp; drop your CSV/Excel file here</div>
                            <input type="file" name="csv_import" id="csv_import" accept=".csv,.xlsx,.xls" style="display:none;">
                            <button type="button" class="btn btn-primary btn-browse" id="browse_btn">Browse Files</button>
                            <div class="selected-file-name" id="selected_file_name"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="text-align:center;">
                    <button class="btn btn-default" type="button" data-dismiss="modal">Cancel</button>
                    <button class="btn btn-success" type="button" id="submit_btn">Import</button>
                </div>
            </div>
        </div>
    </div>

    <!--invoice attachment-->
    <div class="modal fade " id="invoice_popup" style="display:none">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                    <h4 class="modal-title" style="color:#fff">
                        Invoice Attachments
                    </h4>
                </div>
                <div class="modal-body" id="invoice_attach_div">

                </div>


            </div>

        </div>
    </div>
    <!--POD Attachment Modal-->
    <div class="modal fade" id="pod_popup" style="display:none">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                <h4 class="modal-title" style="color:#fff">Proof of Delivery</h4>
            </div>
            <div class="modal-body" id="pod_attach_div" style="text-align:center;">
            </div>
        </div>
    </div>
</div>

</body>

</html>