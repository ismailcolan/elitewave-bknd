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
            .trans_list {
                width: 100%;
                overflow-x: auto;
                overflow-y: hidden;
            }

            .trans_list_table {
                margin: 0 auto;
                width: max-content !important;
                max-width: unset !important;
                clear: both;
                border-collapse: collapse;
                table-layout: fixed;
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
                                        <th class="table-title" style="width:10%">GRN NO</th>
                                        <th class="table-title" style="width:10%">GRN Date</th>
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
                                            ?>
                                            <tr>
                                                <td class="text-center"><?php echo $i; ?></td>
                                                <td><?php echo $row['grn_no']; ?></td>
                                                <td><?php echo $row['grn_date']; ?></td>
                                                <td><?php echo $pkg_r['pkge']; ?></td>
                                                <td>
                                                    <?php echo get_client_name($conn, $row['consigner']); ?>
                                                    </td>

                                                <td>
                                                    <?php echo get_client_name($conn, $row['consignee']); ?>
                                                    </td>
                                                <td><?php echo get_city_name($conn, $row['destination']); ?></td>
                                                <?php if ($booking == '1') { ?>
                                                    <td style="color:red;">Consignment Cancelled</td>
                                                <?php } else { ?>
                                                    <td><?php echo get_trans_status($row['status']); ?></td>
                                                <?php } ?>
                                                

                                                <td class="actions center-content ">
                                                    <div>
                                                        <button class="border booked" disabled title="Consignment Booked"><i class="fa fa-check"></i></button>&nbsp;
                                                        <button class="border picked-up <?php if ($row['status'] >= 2) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 2) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="2" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 2) { echo 'data-remarks="' . get_cong_remarks($conn, 2, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="Consignment Picked Up"><?php if ($row['status'] >= 2) { echo "<i class='fa fa-check'></i>"; } else { echo '2'; } ?></button>&nbsp;
                                                        <button class="border transit-1  <?php if ($row['status'] >= 3) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 3) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="3" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 3) { echo 'data-remarks="' . get_cong_remarks($conn, 3, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="In Transit-1"><?php if ($row['status'] >= 3) { echo "<i class='fa fa-check'></i>"; } else { echo '3'; } ?></button>&nbsp;
                                                        <button class="border transit-2  <?php if ($row['status'] >= 4) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 4) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="4" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 4) { echo 'data-remarks="' . get_cong_remarks($conn, 4, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="In Transit-2"><?php if ($row['status'] >= 4) { echo "<i class='fa fa-check'></i>"; } else { echo '4'; } ?></button>&nbsp;
                                                        <button class="border transit-3  <?php if ($row['status'] >= 5) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 5) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="5" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 5) { echo 'data-remarks="' . get_cong_remarks($conn, 15, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="In Transit-3"><?php if ($row['status'] >= 5) { echo "<i class='fa fa-check'></i>"; } else { echo '5'; } ?></button>&nbsp;
                                                        <button class="border destination  <?php if ($row['status'] >= 6) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 6) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="6" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 6) { echo 'data-remarks="' . get_cong_remarks($conn, 6, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="At Destination"><?php if ($row['status'] >= 6) { echo "<i class='fa fa-check'></i>"; } else { echo '6'; } ?></button>&nbsp;
                                                        <button class="border out-delivery  <?php if ($row['status'] >= 7) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 7) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="7" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 7) { echo 'data-remarks="' . get_cong_remarks($conn, 7, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="Out For Delivery"><?php if ($row['status'] >= 7) { echo "<i class='fa fa-check'></i>"; } else { echo '7'; } ?></button>&nbsp;
                                                        <button class="border delivered  <?php if ($row['status'] >= 8) { echo 'show_info_popup'; } ?>" <?php if ($row['status'] >= 8) { echo 'readonly'; } else { ?> id="status_popup" <?php } ?> data-status="8" data-tabid="<?php echo $trans_name; ?>" data-grnid="<?php echo $row['grn_id']; ?>" data-grnno="<?php echo $row['grn_no']; ?>" <?php if ($row['status'] >= 8) { echo 'data-remarks="' . get_cong_remarks($conn, 8, $row['grn_no']) . '"'; } ?> data-consignment="<?php echo $row['transaction_id']; ?>" title="Delivered Successfully"><?php if ($row['status'] >= 8) { echo "<i class='fa fa-check'></i>"; } else { echo '8'; } ?></button>
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
        });

        $(document).on('click','.show_info_popup', function(){
            if($(this).data('remarks') != ""){
                let remarks = $(this).data('remarks');
                $('#show_remarks_modal').modal('show');
                $("#show_remarks").val(remarks)
            }
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

                        <label class="control-label">Remarks:</label>
                        <textarea class="form-control" name="remarks" id="remarks" rows="4" required="required"></textarea>
                        <small name="error_msg" id="error_msg" style="display:none; color:red;"></small>
                        <br>
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
                <label class="control-label">Remarks:</label>
                    <textarea class="form-control" name="show_remarks" id="show_remarks" rows="4" required="required" disabled></textarea>
                    <div class="text-right">
                        <button class="btn btn-info btn-cancel" type="button" aria-hidden="true" class="close" data-dismiss="modal" style="margin-top: 10px; margin-right: 0;">OK</button>
                    </div>
                </div>
            </div>

        </div>
    </div>


</body>

</html>