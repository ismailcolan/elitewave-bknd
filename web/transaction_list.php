<?php
require_once ('include/connect.php');
require_once ('include/function.php');
$logged_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>

<head>
    <?php include ('include/title.php'); ?>
    <?php include ('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha512-aOG0c6nPNzGk+5zjwyJaoRUgCdOrfSDhmMID2u4+OIslr0GjpLKo7Xm0Ao3xmpM4T8AmIouRkqwj1nrdVsLKEQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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

        .ui-monthpicker-trigger {
            display: none;
        }

        .disable_action {
            color: #7a888f;
            cursor: not-allowed;
        }
  .fa_calend{
            display: table-cell !important;
            text-align: center !important;
            width: 1% !important;
            cursor: pointer;
            border-left: 1.5px solid var(--ew-border);
            background: #F5F6F8;
            color: var(--ew-text-muted);
            transition: background .2s ease;
            vertical-align: middle !important;
            padding: 0 10px;
  }
  .fa_calend:hover {
            background: #E8EDF4;
            color: var(--ew-navy);
  }
   .fa_calend i {
            font-size: 15px;
            margin: 0;
            padding: 0;
            vertical-align: middle;
   }
    
 @media (min-width: 320px) and (max-width:575.98px) {
    .trans_list {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
}
.trans_list_table {
    margin: 0 auto;
    width: max-content!important;
    max-width: unset!important;
    clear: both;
    border-collapse: collapse;
    table-layout: fixed;
}}

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

/* ===== Custom calendar-style month picker ===== */
.month-field-col {
    max-width: 260px;
}
.month-picker-wrap {
    position: relative;
    width: 180px;
    border:0 !important;
}
.month-picker-wrap .form-control {
    background: #fff;
    cursor: pointer;
    padding-right: 50px !important;
}
.month-picker-wrap .fa_calend {
    cursor: pointer;
    color: #2f6fed;
}
.month-picker-panel {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    z-index: 1050;
    width: 300px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.18);
    border: 1px solid #e7e9ee;
    overflow: hidden;
    font-family: inherit;
}
.mp-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #0A1E3D;
}
.mp-year-label {
    color: #fff;
    font-weight: 600;
    font-size: 16px;
    letter-spacing: .5px;
}
.mp-nav-btn {
    background: transparent;
    border: none;
    color: #cfe1ff;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    transition: background .15s ease;
}
.mp-nav-btn:hover {
    background: rgba(255,255,255,0.15);
    color: #fff;
}
.mp-months-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    padding: 16px;
}
.mp-month-cell {
    padding: 10px 4px;
    text-align: center;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #333;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all .15s ease;
}
.mp-month-cell:hover {
    background: #eef4ff;
    border-color: #cfe1ff;
}
.mp-month-cell.mp-selected {
    background: #2f6fed;
    color: #fff;
}
.mp-month-cell.mp-today:not(.mp-selected) {
    border-color: #2f6fed;
    color: #2f6fed;
}
.mp-footer {
    display: flex;
    justify-content: flex-end;
    padding: 10px 16px;
    border-top: 1px solid #eef0f3;
}
.mp-footer .btn {
    border-radius: 6px;
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
                <!-- <div class="col-md-offset-1 col-md-10"> -->
                    <div class="" style="padding:0 40px;"> 
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"> <i class="fa fa-table"></i>Transaction <span class="align-right"><i class="fa fa-plus"></i> <a href="transactions.php">Add Transaction</a></span> </div>
                        <div class="widget-content padded">
                            
                            <div class="row">
                                <form class="form-horizontal" id="transaction_form">
                                    <input type="hidden" id="form_name" name="form_name" value="transaction_form">
                                    <input type="hidden" id="edit_id" name="edit_id" value="">
                                    <input type="hidden" id="cmd" name="cmd" value="get_transaction_month_details">
                                    <div id="response" class="alert alert-danger" style="display:none;">
                                        <div class="message" style="text-align:center"></div>
                                    </div>

                                    <div class="col-md-6 col-6">
                                        <div class="month-field-col">
                                            <div class="form-group">
                                                <label class="control-label">Month:</label>
                                                <div class="input-group month-picker-wrap" id="month_picker_wrap">
                                                    <input class="form-control" type="text" id="month" name="month" value="<?php echo date('m-Y'); ?>" readonly required>
                                                    <span class="input-group-addon fa_calend" id="month_picker_trigger"><i class="fa fa-calendar"></i></span>

                                                    <div class="month-picker-panel" id="month_picker_panel" style="display:none;">
                                                        <div class="mp-header">
                                                            <button type="button" class="mp-nav-btn" id="mp_prev_year"><i class="fa fa-chevron-left"></i></button>
                                                            <span class="mp-year-label" id="mp_year_label"></span>
                                                            <button type="button" class="mp-nav-btn" id="mp_next_year"><i class="fa fa-chevron-right"></i></button>
                                                        </div>
                                                        <div class="mp-months-grid" id="mp_months_grid"></div>
                                                        <div class="mp-footer">
                                                            <button type="button" class="btn btn-sm btn-default" id="mp_clear">Clear</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div class="col-md-6 col-6 text-right">
                                    <button type="button" class="btn import-trigger-btn" data-toggle="modal" data-target="#import_modal">
                                        <i class="fa fa-upload"></i>&nbsp; Import Consignment
                                    </button>
                                </div>
                            </div><br />
<div class="trans_list">
                            <table class="table table-bordered table-striped trans_list_table">
                                <thead>
                                    <th class="table-title" style="width:6%; padding: 9px;">S.No</th>
                                    <th class="table-title" style="width:10%">GCN NO</th>
                                    <th class="table-title" style="width:8%">PNR</th>
                                    <th class="table-title" style="width:10%">GCN Date</th>
                                    <th class="table-title" style="width:5%">No of Pkgs</th>
                                    <th class="table-title" style="width:10%">Consignor </th>
                                    <th class="table-title" style="width:13%">Consignee </th>
                                    <th class="table-title" style="width:8%">Destination</th>
                                    <th class="table-title" style="width:10%">Status</th>
                                    <th class="table-title" style="width:3%">POD</th>
                                    <th class="table-title" style="width:25%">Action</th>
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
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $i; ?></td>
                                            <td><?php echo $row['grn_no']; ?></td>
                                            <td><?php echo $row['tracking_code']; ?></td>
                                            <td><?php echo $row['grn_date']; ?></td>
                                            <td><?php echo $pkg_r['pkge']; ?></td>
                                            <td><?php echo get_client_name($conn, $row['consigner']);
                                        if (check_invoice_restricted($conn, $row['consigner']) == 1) {
                                            echo " <i class='fa fa-ban text-danger' title='This client is restricted'></i>";
                                        }
                                        if (checkPartyWiseFrequency($conn, $row['consigner']) == 0) {
                                            echo " <i class='fa fa-clock-o text-primary' title='This client is in frequency'></i>";
                                        }
                                        if (checkClientCharges($conn, $row['consigner']) > 0) {
                                            echo " <i class='fa fa-inr text-success' title='This client applies client charges'></i>";
                                        } ?></td>
                                            
                                            <td><?php echo get_client_name($conn, $row['consignee']);
                                        if (check_invoice_restricted($conn, $row['consignee']) == 1) {
                                            echo " <i class='fa fa-ban text-danger' title='This client is restricted'></i>";
                                        }
                                        if (checkPartyWiseFrequency($conn, $row['consignee']) == 0) {
                                            echo " <i class='fa fa-clock-o text-primary' title='This client is in frequency'></i> ";
                                        }
                                        if (checkClientCharges($conn, $row['consignee']) > 0) {
                                            echo " <i class='fa fa-inr text-success' title='This client applies client charges'></i>";
                                        } ?></td>
                                            <td><?php echo get_city_name($conn, $row['destination']); ?></td>
                                            <?php if ($booking == '1') { ?>
                                                <td style="color:red;">Consignment Cancelled</td>
                                            <?php
                                        } else {
                                            ?>
                                                <td><?php echo get_trans_status($row['status']); ?></td>

                                            <?php } ?>

                                            <!--- POD Verification -->
                                            <td>
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

                                            <td class="actions center-content ">


                                                <div class="action-buttons" style="width: 100%;">


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
                                                        if ($consignment_mode == '3' || $status > 6) {
                                                            ?>
                                                        <a title="Edit" href="javascript:void(0)" class="table-actions btn-edits disable_action" id="<?php echo $row['transaction_id']; ?>" readonly><i class="fa fa-pencil"></i></a>
                                                            <!-- <a title="Pay at Booking" href="#" class="table-actions btn-edit edit_disabled" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-pencil"></i></a> -->

                                                        <?php
                                                        } else {
                                                            if ($row['book_manual'] == 2) {
                                                                ?>
                                                            <a title="Edit" href="transactions_manual.php?key=<?php echo md5($row['transaction_id']); ?>&m=<?php echo $m1; ?>&y=<?php echo $dt[2] ?>" class="table-actions btn-edit" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-pencil"></i></a>
                                                        <?php
                                                            } else {
                                                        ?>
                                                            <a title="Edit" href="transactions.php?key=<?php echo md5($row['transaction_id']); ?>&m=<?php echo $m1; ?>&y=<?php echo $dt[2] ?>" class="table-actions btn-edit" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-pencil"></i></a>

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
        function fetchMonthTransactions() {
            var data = $('#transaction_form').serialize();
            if ($('#transaction_form').valid() == true) {
                $.ajax({
                    url: 'fetch_details.php',
                    type: "GET",
                    data: data,
                    success: function(result) {
                        console.log(result);
                        $('#get_month_details').html(result);
                    }
                });
            }
        }

        $(document).ready(function() {

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
                $(".loading-page").addClass('show');

                $.ajax({
                    url: "import.php",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        // Hide the loader
                        $(".loading-page").removeClass('show');

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
                        $(".loading-page").removeClass('show');
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
           Custom calendar-style Month Picker
           =================================================== */
        (function() {
            var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            var $input = $("#month");
            var $panel = $("#month_picker_panel");
            var $grid = $("#mp_months_grid");
            var $yearLabel = $("#mp_year_label");

            var current = $input.val().split('-'); // [mm, yyyy]
            var selectedMonth = current[0] ? parseInt(current[0], 10) : (new Date().getMonth() + 1);
            var selectedYear = current[1] ? parseInt(current[1], 10) : new Date().getFullYear();
            var viewYear = selectedYear;

            var todayMonth = new Date().getMonth() + 1;
            var todayYear = new Date().getFullYear();

            function pad(n) {
                return n < 10 ? "0" + n : "" + n;
            }

            function renderGrid() {
                $yearLabel.text(viewYear);
                $grid.empty();
                for (var m = 1; m <= 12; m++) {
                    var classes = "mp-month-cell";
                    if (m === selectedMonth && viewYear === selectedYear) classes += " mp-selected";
                    if (m === todayMonth && viewYear === todayYear) classes += " mp-today";
                    $grid.append(
                        $("<div>").addClass(classes).attr("data-month", m).text(monthNames[m - 1])
                    );
                }
            }

            function openPanel() {
                viewYear = selectedYear;
                renderGrid();
                $panel.stop(true, true).slideDown(150);
            }

            function closePanel() {
                $panel.stop(true, true).slideUp(120);
            }

            $("#month_picker_trigger, #month").on("click", function(e) {
                e.stopPropagation();
                if ($panel.is(":visible")) {
                    closePanel();
                } else {
                    openPanel();
                }
            });

            $("#mp_prev_year").on("click", function(e) {
                e.stopPropagation();
                viewYear--;
                renderGrid();
            });

            $("#mp_next_year").on("click", function(e) {
                e.stopPropagation();
                viewYear++;
                renderGrid();
            });

            $grid.on("click", ".mp-month-cell", function(e) {
                e.stopPropagation();
                selectedMonth = parseInt($(this).attr("data-month"), 10);
                selectedYear = viewYear;
                $input.val(pad(selectedMonth) + "-" + selectedYear);
                closePanel();
                fetchMonthTransactions();
            });

            $("#mp_clear").on("click", function(e) {
                e.stopPropagation();
                $input.val("");
                closePanel();
            });

            $panel.on("click", function(e) {
                e.stopPropagation();
            });

            $(document).on("click", function() {
                closePanel();
            });
        })();

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