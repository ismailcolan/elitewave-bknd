<?php
require_once ('include/connect.php');
require_once ('include/function.php');
require_once ('include/billing_functions.php');
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
.month-field-col {
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
.import-trigger-btn {
    background: #0A1E3D;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 9px 18px;
    font-weight: 600;
    margin-top: 20px;
}
.import-trigger-btn:hover {
    background: #08375c;
    color: #fff;
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
    padding: 0 24px 32px;
    max-width: 100%;
}
.txn-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    background: linear-gradient(185deg, var(--ew-navy, #0A1E3D) 0%, var(--ew-navy-deep, #061428) 100%);
    color: #fff;
    padding: 14px 24px;
    border-radius: 10px 10px 0 0;
    min-height: 64px;
}
.txn-page-header h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #ffffff !important;
}
.txn-page-header h1 i {
    margin-right: 8px;
    opacity: 1;
    color: #ffffff !important;
}
.txn-header-meta {
    font-size: 14px;
    color: rgba(255,255,255,.88) !important;
    margin-top: 4px;
}
.txn-btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    color: #0A1E3D !important;
    border: none;
    border-radius: 8px;
    padding: 10px 18px;
    font-weight: 600;
    font-size: 13px;
    text-decoration: none !important;
    box-shadow: 0 2px 8px rgba(0,0,0,.15);
    transition: transform .15s ease, box-shadow .15s ease;
}
.txn-btn-add:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,.2);
    color: #0A1E3D !important;
}
.txn-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    padding: 14px 20px;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
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
.txn-table-card {
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 0 0 10px 10px;
    overflow: visible;
    box-shadow: 0 4px 18px rgba(15, 23, 42, .06);
}
.txn-datatable-area {
    padding: 0 16px 16px;
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
    background: var(--ew-primary-light, #EEF2F7) !important;
    color: var(--ew-navy, #0A1E3D) !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: .04em;
    padding: 11px 8px !important;
    border: none !important;
    border-bottom: 2px solid var(--ew-border, #D8DDE5) !important;
    border-right: 1px solid var(--ew-border-light, #E2E8F0) !important;
    white-space: nowrap;
    vertical-align: middle !important;
    box-sizing: border-box !important;
    overflow: hidden;
    text-overflow: ellipsis;
}
.trans_list_table thead th:last-child {
    border-right: none !important;
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
.txn-action-group .dropdown.table-actions.open,
.txn-action-group .dropdown.table-actions:hover {
    z-index: 30;
}
.txn-action-group .dropdown.table-actions .dropdown-menu {
    min-width: 160px;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,.15);
    border: 1px solid #E2E8F0;
    padding: 6px 0;
    z-index: 1000;
}
.txn-action-group .dropdown.table-actions .dropdown-menu li a {
    padding: 8px 14px;
    font-size: 12px;
}
.txn-table-footer {
    padding: 10px 16px 14px;
    border-top: 1px solid #EEF2F7;
    background: #FAFBFC;
}
.txn-dt-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    padding-top: 4px;
}
.txn-dt-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
@media (max-width: 767px) {
    .txn-page-wrap { padding: 0 12px 24px; }
    .txn-toolbar { flex-direction: column; align-items: stretch; }
    .txn-toolbar .text-right { text-align: left !important; }
}

.txn-page-wrap .txn-page-header,
.txn-page-wrap .txn-page-header h1,
.txn-page-wrap .txn-page-header h1 i,
.txn-page-wrap .txn-header-meta {
    color: #ffffff !important;
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
                    <div class="txn-page-wrap">
                    <div class="widget-container fluid-height clearfix" style="padding:0;border:none;background:transparent;box-shadow:none;">
                        <div class="txn-page-header">
                            <div>
                                <h1><i class="fa fa-truck"></i> Transactions</h1>
                                <div class="txn-header-meta">Manage consignments, documents, and delivery status</div>
                            </div>
                            <a href="transactions.php" class="txn-btn-add"><i class="fa fa-plus"></i> Add Transaction</a>
                        </div>
                        <div class="txn-table-card">
                        <div class="txn-toolbar">
                            <form class="form-horizontal" id="transaction_form" style="margin:0;">
                                    <input type="hidden" id="form_name" name="form_name" value="transaction_form">
                                    <input type="hidden" id="edit_id" name="edit_id" value="">
                                    <input type="hidden" id="cmd" name="cmd" value="get_transaction_month_details">
                                    <div id="response" class="alert alert-danger" style="display:none;">
                                        <div class="message" style="text-align:center"></div>
                                    </div>
                                    <div class="month-field-col">
                                        <label class="control-label">Month</label>
                                        <?php echo ew_month_input(array('id' => 'month', 'name' => 'month', 'required' => true)); ?>
                                    </div>
                            </form>
                            <div class="text-right">
                                <button type="button" class="btn import-trigger-btn" data-toggle="modal" data-target="#import_modal" style="margin-top:0;">
                                    <i class="fa fa-upload"></i>&nbsp; Import Consignment
                                </button>
                            </div>
                        </div>
                        <div class="txn-datatable-area">
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
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $i; ?></td>
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
                                                        <!-- <a class="table-actions " target="BLANK" href="transaction_pdf.php?month=<?php echo $m1; ?>&year=<?php echo $y; ?>&id=<?php echo $row['transaction_id']; ?>" data-status="<?php echo $row['status'] ?>" title="View" id="<?php echo $row['transaction_id'] ?>"><i class="fa fa-print"></i></a> -->
                                                        
                                                        <span class="table-actions dropdown" id="print_grn"><i class="fa fa-print"></i>
                                                            <ul class="dropdown-menu" style="display: none;">
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
                                                        <a title="E-way Attachments" href="#eway_popup" class="table-actions btn-eway" data-toggle="modal" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-paperclip"></i></a>
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
                iDisplayLength: 25,
                aLengthMenu: [[10, 25, 50, 100, -1], ['10', '25', '50', '100', 'All']],
                aaSorting: [[3, 'desc']],
                bAutoWidth: false,
                bDestroy: true,
                aoColumnDefs: [
                    { bSortable: false, aTargets: [9, 10] },
                    { sClass: 'text-center', aTargets: [0, 4, 9] },
                    { sClass: 'col-consignor', aTargets: [5] },
                    { sClass: 'col-consignee', aTargets: [6] },
                    { sClass: 'col-actions', aTargets: [10] }
                ],
                oLanguage: {
                    sEmptyTable: 'No bookings found for this month.',
                    sZeroRecords: 'No matching consignments found.'
                }
            });
        }

        function fetchMonthTransactions() {
            var data = $('#transaction_form').serialize();
            if ($('#transaction_form').valid() == true) {
                destroyTxnListDataTable();
                $.ajax({
                    url: 'fetch_details.php',
                    type: "GET",
                    data: data,
                    success: function(result) {
                        $('#get_month_details').html(result);
                        initTxnListDataTable();
                    },
                    error: function() {
                        $('#get_month_details').html('');
                        initTxnListDataTable();
                        ewToast('Could not load bookings for this month.', 'error');
                    }
                });
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
            $('#month').on('changeDate', function() {
                if (!monthPickerReady) {
                    return;
                }
                clearTimeout(monthFetchTimer);
                monthFetchTimer = setTimeout(fetchMonthTransactions, 150);
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

            $(document).on('click', '#new_eway', function() {
                $("#old_attach_div").hide(100);
                $("#attachment_body").show();
            });


            $(document).on('click', '#eway_cancel', function() {
                $("#old_attach_div").show();
                $("#attachment_body").hide(100);
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
                var id = $(this).attr('id');
                var table_name = '<?php echo $trans_image_name; ?>';
                $("#attachment_id").val(id);
                $("#table_name").val(table_name);
                console.log($("#table_name").val(table_name));
                $.ajax({
                    url: 'fetch_details.php',
                    type: "GET",
                    data: {
                        cmd: "get_existing_attchment",
                        transaction_id: id,
                        table_name: table_name
                    },
                    success: function(result) {
                        console.log(result);
                        if (result != 0)
                            $('#old_attach_div').html(result);
                        else
                            $("#attachment_body").show();
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
                    $(this).prop("disabled", true);
                    $.ajax({
                        url: "save_details.php",
                        type: "post",
                        //dataType:"json",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(result) {
                            console.log(result);
                            if (result == 1) {
                                $(".form-data-saving").hide();

                                $("#attachment_body").html("Attachments Uploaded Successfully ");
                                location.reload();

                            } else {
                                $(".form-data-saving").hide();

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
        
        // On click print icon show gr copy download options
        $(document).on('click','#print_grn',function(){
            const element = $(this).children(".dropdown-menu");
            const display = element.css("display");
            if (display === "none") {
                $(this).children('.dropdown-menu').css({"display":"block"});
                $(this).addClass('table-actions-click');
            }
            //  else {
            //     $(this).removeClass('table-actions-click');
            //     $(this).children('.dropdown-menu').css({"display":"none"});
            // }
        });
      
        //  On click outside of print icon options will hide
        const print_btn = $("#print_grn");
        $(document.body).on("click", function(event) {
            const closestDiv = $(event.target).closest("#print_grn");
            if (closestDiv.length === 0) {
                const element = $('.table-actions-click').children(".dropdown-menu");
                const display = element.css("display");
                if (display === "block") {
                    $('.table-actions-click').children('.dropdown-menu').css({"display":"none"});
                    $('.table-actions-click').removeClass('table-actions-click')
                }
            }
        });

		
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

    <div class="modal fade " id="eway_popup" style="display:none">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                    <h4 class="modal-title" style="color:#fff">
                        Add E-Way Attachments
                    </h4>
                </div>
                <div class="modal-body" id="old_attach_div">

                </div>

                <div class="modal-body" style="margin-left: 100px;display: none" id="attachment_body">
                    <form id="eway_form" enctype="multipart/form-data">
                        <input type="hidden" name="form_name" value="add_eway_bill">
                        <input type="hidden" name="attachment_id" id="attachment_id" value="">
                        <input type="hidden" name="table_name" id="table_name" value="">


                        <label class="control-label">E-way Attachment:</label>
                        <input type="file" name="attachment[]" required multiple=""><br>
                        <label class="control-label">E-way Bill No:</label>
                        <input type="text" class="form-control" style="width: 184px;" name="eway_bill_no" required><br>



                        <label class="control-label">Date of Issue:</label>
                        <input type="date" style="width: 180px;" class="form-control " placeholder="Date of Issue" name="issue_date" /><br>
                        <label class="control-label">Date of Expiry:</label>
                        <input type="date" style="width: 180px;" class="form-control expiredate" placeholder="Date of Expiry" name="expire_date" />
                        <br>
                        <div class="modal-footer" style="text-align: center;">
                            <button class="btn btn-danger btn-cancel" type="button" id="eway_cancel">Cancel</button>
                            <button class="btn btn-primary btn-submit" type="button" id="save_eway">Submit</button>
                        </div>
                </div>
            </div>
            </form>

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