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
        button#search {
            margin-top: 20px;
            background: #f0ad4eeb;
            border-color: #f1b35c;
            font-weight: bold;
            color: #fff;
        }

#remarks{
    padding:8px 10px;
    line-height:20px;
    vertical-align:top;
    resize:vertical;
    overflow-y:auto;
    box-sizing:border-box;
}

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

        .fa_calend {
            height: 25px;
            position: absolute;
            right: 0;
            width: 8%;
            top: 0;
            display: grid;
            justify-content: center;
            align-items: center;
            padding-top: 2px;
        }

        .cals_csss {
            width: 100%;
        }
        #get_month_details button {
            width:25px; 
        }

        @media (min-width: 320px) and (max-width:575.98px) {
            .trans_list{
        overflow:visible;
    }

    .trans_list_table{
        width:100% !important;
    }

    .trans_list_table thead{
        display:none;
    }

    .trans_list_table,
    .trans_list_table tbody,
    .trans_list_table tr,
    .trans_list_table td{
        display:block;
        width:100%;
    }

    .trans_list_table tr{
        border:1px solid #ddd;
        border-radius:8px;
        margin-bottom:15px;
        background:#fff;
        padding:10px;
    }

    .trans_list_table td{
        border:none !important;
        border-bottom:1px solid #eee !important;
        text-align:right;
        padding:10px 10px 10px 45%;
        position:relative;
        white-space:normal;
    }

    .trans_list_table td:last-child{
        border-bottom:none !important;
    }

    .trans_list_table td:before{
        content:attr(data-label);
        position:absolute;
        left:10px;
        top:10px;
        width:40%;
        font-weight:bold;
        text-align:left;
        color:#0A1E3D;
    }

    /* Change Status buttons */

    #get_month_details button{
        width:auto;
        height:28px;
        margin:2px;
    }

    td[data-label="Change Status"]{
        text-align:left;
        padding-left:10px;
    }

    td[data-label="Change Status"]:before{
        position:static;
        display:block;
        margin-bottom:8px;
    }

    .tra-status-div{
            padding:0px !important;
        }

        #get_month_details .actions {
            width: auto;
            display: flex;
        justify-content: space-between;
        }
        }

        .table-actions-click {
            border: solid 1px;
            background: #0A1E3D;
            color: #FFF;
            border-radius: 5px;
        }

        .border{
            border: 1px solid silver;
        }

        .booked{
         background-color: #8dafbf;
         color: white;
        }

        .picked-up{
         background-color: #77aec9;
         color: white;       
        }

        .transit-1{
         background-color: #6293ab;
         color: white;       
        }

        .transit-2{
         background-color: #4c798f;
         color: white;       
        }

        .transit-3{
         background-color: #3d6578;
         color: white;       
        }

        .destination{
         background-color: #1a607e;
         color: white;       
        }

        .out-delivery{
         background-color: #0e4c68;
         color: white;       
        }

        .delivered{
         background-color: #003e58;
         color: white;       
        }

        .tra-status-div{
            padding:0 40px;
        }
.desktop-status-buttons{
    display:block;
}

.mobile-update-status{
    display:none;
}

@media (max-width:575.98px){

.desktop-status-buttons{
    display:none;
}

.mobile-update-status{
    display:inline-block;
    width:100%;
    margin-top:8px;
}

}
        .mobile-status-group{
    display:none;
}

@media(max-width:575.98px){

.mobile-status-group{
    display:block;
}

}

.partial-delivery-status {
    color: #fff !important;
    background-color: #d9534f !important;
    font-weight: bold;
}
.partial-delivery-button {
    background-color: #d9534f !important;
    color: #fff !important;
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
                    <div class="tra-status-div"> 
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"> <i class="fa fa-table"></i>Change Status </div>
                        <div class="widget-content padded">
                            <form class="form-horizontal" id="transaction_form">

                                <input type="hidden" id="form_name" name="form_name" value="transaction_form">
                                <input type="hidden" id="edit_id" name="edit_id" value="">
                                <input type="hidden" id="cmd" name="cmd" value="get_transact_status_month_detail">
                                <div id="response" class="alert alert-danger" style="display:none;">
                                    <div class="message" style="text-align:center"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-offset-4 col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Month:</label>
                                            <div class="input-group cals_csss">
                                                <input class="form-control" type="text" id="month" name="month" value="<?php echo date('m-Y'); ?>" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null :event.charCode >= 96 && event.charCode <= 105 && event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" required><span class="input-group-addon fa_calend"><i class="fa fa-calendar"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class=" col-md-4">
                                        <button class="btn btn-primary" type="button" id="search" style="margin-top:  20px;">Search</button>
                                    </div>


                                </div><br />

                            </form>
                            <div class="trans_list">
                                <table class="table table-bordered table-striped trans_list_table">
                                    <thead>
                                        <th class="table-title" style="padding:9px;">S.No</th>
                                        <th class="table-title" style="width:10%">GCN NO</th>
                                        <th class="table-title" style="width:10%">GCN Date</th>
                                        <th class="table-title" style="width:5%">No of Pkgs</th>
                                        <th class="table-title" style="width:13%">Consignor </th>
                                        <th class="table-title" style="width:15%">Consignee </th>
                                        <th class="table-title" style="width:13%">Destination</th>
                                        <th class="table-title" style="width:10%">Status</th>
                                        <th class="table-title" style="width:20%">Change Status</th>
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
                                                <td class="text-center" data-label="S.No"><?php echo $i; ?></td>
                                                <td data-label="GCN No"><?php echo $row['grn_no']; ?></td>
                                                <td data-label="GCN Date"><?php echo $row['grn_date']; ?></td>
                                                <td data-label="No of Pkgs"><?php echo $pkg_r['pkge']; ?></td>
                                                <td data-label="Consignor">
                                                    <?php echo get_client_name($conn, $row['consigner']); ?>
                                                    </td>

                                                <td data-label="Consignee">
                                                    <?php echo get_client_name($conn, $row['consignee']); ?>
                                                    </td>
                                                <td data-label="Destination"><?php echo get_city_name($conn, $row['destination']); ?></td>
                                                <?php if ($booking == '1') { ?>
                                                    <td style="color:red;">Consignment Cancelled</td>
                                               
                                                <?php } else { ?>

    <td data-label="Status"
        class="<?php echo ($delivery_type == 'partial') ? 'partial-delivery-status' : ''; ?>">

        <?php echo htmlspecialchars($display_status); ?>

    </td>

<?php } ?>
                                                

                                                <td class="actions center-content "  data-label="Change Status">
                                                    <div class="desktop-status-buttons">
                                                        <button class="border booked" disabled title="Consignment Booked"><i class="fa fa-check"></i></button>&nbsp;
                                                        <button class="border picked-up <?php if ($row['status'] >= 2) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 2) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="2" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 2) { echo 'data-remarks="' . get_cong_remarks($conn, 2, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="Consignment Picked Up"><?php if ($row['status'] >= 2) { echo "<i class='fa fa-check'></i>"; } else { echo '2'; } ?></button>&nbsp;
                                                        <button class="border transit-1  <?php if ($row['status'] >= 3) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 3) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="3" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 3) { echo 'data-remarks="' . get_cong_remarks($conn, 3, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="In Transit-1"><?php if ($row['status'] >= 3) { echo "<i class='fa fa-check'></i>"; } else { echo '3'; } ?></button>&nbsp;
                                                        <button class="border transit-2  <?php if ($row['status'] >= 4) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 4) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="4" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 4) { echo 'data-remarks="' . get_cong_remarks($conn, 4, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="In Transit-2"><?php if ($row['status'] >= 4) { echo "<i class='fa fa-check'></i>"; } else { echo '4'; } ?></button>&nbsp;
                                                        <button class="border transit-3  <?php if ($row['status'] >= 5) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 5) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="5" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 5) { echo 'data-remarks="' . get_cong_remarks($conn, 15, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="In Transit-3"><?php if ($row['status'] >= 5) { echo "<i class='fa fa-check'></i>"; } else { echo '5'; } ?></button>&nbsp;
                                                        <button class="border destination  <?php if ($row['status'] >= 6) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 6) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="6" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 6) { echo 'data-remarks="' . get_cong_remarks($conn, 6, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="At Destination"><?php if ($row['status'] >= 6) { echo "<i class='fa fa-check'></i>"; } else { echo '6'; } ?></button>&nbsp;
                                                        <button class="border out-delivery  <?php if ($row['status'] >= 7) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 7) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="7" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 7) { echo 'data-remarks="' . get_cong_remarks($conn, 7, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="Out For Delivery"><?php if ($row['status'] >= 7) { echo "<i class='fa fa-check'></i>"; } else { echo '7'; } ?></button>&nbsp;
                                                        <!-- <button class="border delivered  <?php if ($row['status'] >= 8) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 8) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="8" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 8) { echo 'data-remarks="' . get_cong_remarks($conn, 8, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="Delivered Successfully"><?php if ($row['status'] >= 8) { echo "<i class='fa fa-check'></i>"; } else { echo '8'; } ?></button> -->
                                                         <?php if ($row['status'] >= 8 && $delivery_type == 'full') { ?>

    <!-- Fully Delivered - no popup -->
    <button
        class="border delivered"
        data-status="8"
        data-tabid="<?php echo $trans_name; ?>"
        data-grnid="<?php echo $row['grn_id']; ?>"
        data-grnno="<?php echo $row['grn_no']; ?>"
        data-consignment="<?php echo $row['transaction_id']; ?>"
        title="Delivered Successfully"
        disabled>

        <i class="fa fa-check"></i>

    </button>

<?php } else { ?>

    <!-- Not delivered OR partially delivered - allow popup -->
    <button
        class="border delivered <?php echo ($delivery_type == 'partial') ? 'partial-delivery-button' : ''; ?>"
        id="status_popup"
        data-status="8"
        data-tabid="<?php echo $trans_name; ?>"
        data-grnid="<?php echo $row['grn_id']; ?>"
        data-grnno="<?php echo $row['grn_no']; ?>"
        data-consignment="<?php echo $row['transaction_id']; ?>"
        data-total-packages="<?php echo $total_packages; ?>"
        data-delivered-packages="<?php echo $delivered_packages; ?>"
        data-delivery-type="<?php echo $delivery_type; ?>"
        title="Change Delivery Status">

        <?php
        if ($row['status'] >= 8) {
            echo "<i class='fa fa-check'></i>";
        } else {
            echo '8';
        }
        ?>

    </button>

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

    <script src='javascripts/jquery.ui.monthpicker.js'></script>
    <script type="text/javascript">
        jQuery(document).ready(function() {
            jQuery("#month").monthpicker({
                showOn: "both",
                dateFormat: 'mm-yy'
            });
        });

        $(document).ready(function() {

            $(document).on('click', '#search', function() {

                var data = $('#transaction_form').serialize();

                //alert(data);
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

            });

            //close pop
            $(document).on('click', '#status_modal_cancel', function() {
                $("#status_popup_modal").modal('hide');
            });

            //end close pop


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

            $(document).on('click', '#save_status_change', function(e) {
                if ($('#status_change_consignment').valid() == true) {
                    if(confirm("Are you sure! Do you want to submit?")){
                        $(this).attr('disabled', true);
                        $(".loading-page").show();
                        $("#status_popup_modal").modal('hide');
                        e.preventDefault();
                        if ($(window).width() <= 575) {
    $("#status").val($("#mobile_status_select").val());
}

// Delivery validation
if (parseInt($("#status").val()) === 8) {

    const deliveryType =
        $("input[name='delivery_type']:checked").val();

    if (!deliveryType) {
        alert("Please select Partially Delivered or Fully Delivered.");
        $(this).attr('disabled', false);
        $(".loading-page").hide();
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

            $(this).attr('disabled', false);
            $(".loading-page").hide();

            return false;
        }
    }
}
                        var data = $('#status_change_consignment').serialize();

                        //$("#table_div").show();

                        $.ajax({
                            url: 'save_details.php',
                            type: "POST",
                            data: data,
                            success: function(result) {
                                console.log(result);
                                $(".loading-page").hide();
                                $(this).attr('disabled', false);
                                if (result != 0) {
                                    $("#alert-status").text("Alert !!!");
                                    $("#alert-message").text("Status Updated Successfully.! Please Wait Until Page Refresh.!!");
                                    $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                        $("#alert-container").hide();
                                        $("#alert-container").removeClass("alert-success");
                                        location.reload();
                                    });

                                } else {
                                    $("#alert-status").text("Alert !!! ");
                                    $("#alert-message").text("Status update Failed");
                                    $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                        $("#alert-container").hide();
                                        $("#alert-container").removeClass("alert-danger");
                                    });
                                }

                            }
                        });
                    }
                }
			});
            // show popup on status button click

        });
        $(window).load(function() {
            $(".loading-page").hide();
        });

       $(document).on('click','#status_popup', function(){

       

    $("#status_popup_modal").modal('show');

    let now = new Date();

let date = now.toISOString().split('T')[0];

let time =
    String(now.getHours()).padStart(2,'0') + ':' +
    String(now.getMinutes()).padStart(2,'0');

$("#status_date").val(date);
$("#status_time").val(time);

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

    $("#remarks").html(msg);

    $("#remarks_text").val(
        $("<div>").html(msg).text()
    );

}

    });

});

        $(document).on('click','.show_info_popup', function(){
            if($(this).data('remarks') != ""){
                let remarks = $(this).data('remarks');
                $('#show_remarks_modal').modal('show');
                $("#show_remarks").val(remarks)
            }
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

let now = new Date();

let date = now.toISOString().split('T')[0];

let time =
    String(now.getHours()).padStart(2,'0') + ':' +
    String(now.getMinutes()).padStart(2,'0');

let currentDate = now.toISOString().split('T')[0];

let currentTime =
    String(now.getHours()).padStart(2,'0') + ':' +
    String(now.getMinutes()).padStart(2,'0');

$("#status_date").val(currentDate);
$("#status_time").val(currentTime);

    $("#status_popup_modal").modal('show');

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

    $("#remarks").html(msg);

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

    $("#remarks").html(msg);

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

    <div class="modal fade " id="status_popup_modal" style="display:none">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                    <h4 class="modal-title" style="color:#fff">
                        Change consignment status
                    </h4>
                </div>
                <!--- Change consignment status / GRN Model -->
                <div class="modal-body" id="cancel_grn">
                    <form id="status_change_consignment" enctype="multipart/form-data">
                        <input type="hidden" name="form_name" value="status_change_consignment">
                        <input type="hidden" name="logged_id" id="logged_id" value="<?php echo $logged_id; ?>">
                        <input type="hidden" name="transaction_id" id="transaction_id" value="">
                        <input type="hidden" name="table_names" id="table_names" value="">
                        <input type="hidden" name="grn_id" id="grn_id" value="">
                        <input type="hidden" name="grn_no" id="grn_no" value="">
                        <input type="hidden" name="status" id="status" value="">
                        <input type="hidden"
       name="total_packages"
       id="total_packages"
       value="">

<input type="hidden"
       name="existing_delivered_packages"
       id="existing_delivered_packages"
       value="">
<div class="form-group mobile-status-group">

<label>Status</label>

<select class="form-control" id="mobile_status_select">

</select>

</div>

<!-- Delivery Options -->
<div id="delivery_options" style="display:none; margin-top:15px;">

    <div class="form-group">
        <label>
            Delivery Type <span style="color:red">*</span>
        </label>

        <div>
            <label style="margin-right:20px;">
                <input
                    type="radio"
                    name="delivery_type"
                    value="partial"
                    id="delivery_partial">
                Partially Delivered
            </label>

            <label>
                <input
                    type="radio"
                    name="delivery_type"
                    value="full"
                    id="delivery_full">
                Fully Delivered
            </label>
        </div>
    </div>

    <div class="form-group">
        <label>Total Packages</label>

        <input
            type="text"
            class="form-control"
            id="total_packages_display"
            readonly>
    </div>

    <div class="form-group" id="partial_package_group" style="display:none;">

        <label>
            Packages Delivered
            <span style="color:red">*</span>
        </label>

        <select
            class="form-control"
            name="delivered_packages"
            id="delivered_packages">

            <option value="">Select packages</option>

        </select>

    </div>

</div>
<div id="remarks"
     class="form-control"
     style="height:115px !important;
            overflow-y:auto;
            white-space:normal;
            padding:10px;
            line-height:24px;
            font-size:15px !important;
            background:#fff;">
</div>
<div class="row" style="margin-top:15px;">
    <div class="col-md-6">
        <label>Status Date <span style="color:red">*</span></label>
        <input type="date"
               class="form-control"
               id="status_date"
               name="status_date"
               value="<?php echo date('Y-m-d'); ?>">
    </div>

    <div class="col-md-6">
        <label>Status Time <span style="color:red">*</span></label>
        <input type="time"
               class="form-control"
               id="status_time"
               name="status_time"
               value="<?php echo date('H:i'); ?>">
    </div>
</div>
<input type="hidden" id="remarks_text" name="remarks">
                        <div class="modal-footer" style="text-align: center;">
                            <button class="btn btn-danger btn-cancel" type="button" id="status_modal_cancel">Cancel</button>
                            <button class="btn btn-primary btn-submit" type="button" id="save_status_change">Submit</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade " id="show_remarks_modal" style="display:none">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                    <h4 class="modal-title" style="color:#fff">
                        Remark
                    </h4>
                </div>
                <!--- Change consignment status / GRN Model -->
                <div class="modal-body" id="cancel_grn">
                <div class="form-group">
    <label>REMARKS (AUTO GENERATED):</label>

    <div id="remarks"
         style="
            min-height:115px !important;
            max-height:180px;
            overflow-y:auto;
            border:1px solid #ced4da;
            border-radius:4px;
            background:#fff;
            padding:12px;
            font-size:15px !important;
            line-height:24px;
            color:#333;
            text-align:left;
        ">
    </div>

    <input type="hidden" id="remarks_text" name="remarks">
</div>
                    <div class="text-right">
                        <button class="btn btn-info btn-cancel" type="button" aria-hidden="true" class="close" data-dismiss="modal" style="margin-top: 10px; margin-right: 0;">OK</button>
                    </div>
                </div>
            </div>

        </div>
    </div>


</body>

</html>