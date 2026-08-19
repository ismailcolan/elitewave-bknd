<?php
include("../config.ini.php");
if (session_id() == '') {
    session_start();
}

//Check Page is Second Time Loaded
$RequestSignature = ($_SERVER['REQUEST_URI'] . $_SERVER['QUERY_STRING']);
if ($_SESSION['LastRequest'] == $RequestSignature) {
    unset($_SESSION['msg']); //remove session message
    unset($_SESSION['paymentId']); //remove session payment
} else {
    $_SESSION['LastRequest'] = $RequestSignature;
}
//End Check Page is Second Time Loaded
$message = (isset($_SESSION['msg'])) ? $_SESSION['msg'] : "";
$paymentId = (isset($_SESSION['paymentId'])) ? $_SESSION['paymentId'] : "";

include_once('include/user-function.php');

//include_once('include/function.php');
// $conn = mysqli_connect("localhost", "staging", "vySzrpsqDRupDHS", "staging");
$select_user_email = mysqli_query($conn, "select *from users where user_id ='" . $_SESSION['user_id'] . "'");
$get_user_data = mysqli_fetch_assoc($select_user_email);
$user_email = $get_user_data['email'];
// var_dump($user_email);


$select_client_id =  mysqli_query($conn, "select *from client where email ='$user_email'");
if(mysqli_num_rows($select_client_id) > 0){
    $get_client_data = mysqli_fetch_assoc($select_client_id);
    $client_id = $get_client_data['client_id'];
}else{
    $client_id = '-1';
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
    <title>Gracious Express - Booking List</title>
    <?php include("include/title.php"); ?>
    <?php include("include/css_js_forgetpassword.php"); ?>
    <link href="../assets/img/GE_Small_Logo.png" type="image/x-icon" rel="shortcut icon">
    <link href="assets/css/master.css" rel="stylesheet">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css" />
    <link href="stylesheets/datatables.css" media="all" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha512-aOG0c6nPNzGk+5zjwyJaoRUgCdOrfSDhmMID2u4+OIslr0GjpLKo7Xm0Ao3xmpM4T8AmIouRkqwj1nrdVsLKEQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- book consignment css and js starts here -->
    <link rel="stylesheet" href="assets/css/book-consignment.css">
    <link rel="stylesheet" href="f5/fontawesome.min.css">
    <!-- book consignment css and js finished here -->
    <!-- <script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script> -->
    <script src="assets/js/jquery.validate.min.js"></script>
    <script src="assets/js/modernizr.custom.js"></script>
    <style>
        .common_popup .modal-dialog {
            margin: 0px;
            transform: translateX(-50%) !important;
        }

        .ui-monthpicker-trigger {
            display: none;
        }

        .right-inner-addon {
            position: relative;
        }

        .right-inner-addon input {
            padding-right: 30px;
        }

        .right-inner-addon i {
            position: absolute;
            right: 0px;
            padding: 10px 12px;
            pointer-events: none;
        }

        .disable_action {
            color: #7a888f;
            cursor: not-allowed;
        }

        @media (min-width: 360px) and (max-width: 576.98px) {

            .dataTables_length,
            .dataTables_filter,
            .dataTables_info,
            .paginate_button.first,
            .paginate_button.last {
                display: block;
            }

            div.dataTables_wrapper div.dataTables_length select {
                width: 58px;
                display: inline-block;
            }

            div.dataTables_wrapper div.dataTables_filter input {
                margin-left: 0.5em;
                display: inline-block;
                width: 85px;
            }

            #employee_data_wrapper>.row>.col-sm-6 {
                margin-top: 0px;
            }

            .dataTables_length {
                margin: 23px 5px 10px;
            }

            .common_popup .modal-dialog {
                margin: 0px;
                transform: translateX(0%) !important;
            }

        }
  @media (min-width: 320px) and (max-width:575.98px) { 
        .booking_list_PBF ,input#month{
            margin-bottom: 0;
        }
        .common_popup .modal-dialog {
    margin: 0px;
    transform: translateX(0%) !important;
}
   }
    </style>
</head>

<body>

    <?php include "user-db-header.php" ?>
    <br /><br />
    <div class="container">
        <h3 align="center"><b>Bookings List</b></h3>

        <div class="col-md-12">

            <form id="booking_list">
                <input type="hidden" id="form_name" name="form_name" value="transaction_form">
                <input type="hidden" id="edit_id" name="edit_id" value="<?php echo $client_id; ?>">
                <input type="hidden" id="cmd" name="cmd" value="get_transaction_month_details">
                <div class="row">
                    <div class="col-sm-offset-4 col-sm-6">

                        <div class="col-md-6 booking_list_PBF">
                            <!-- <div class="form-group">
                              <label for="">Month:</label>
                                <input type="text" name="month" id="month" value="" class="form-control"/>
                         </div> -->
                            <label for="">Month:</label>
                            <div class="right-inner-addon ">
                                <i class="fa fa-calendar"></i>
                                <input type="text" name="month" id="month" value="" class="form-control"  onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null :event.charCode >= 96 && event.charCode <= 105 && event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off" required />
                            </div>

                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-success" id="search" style="margin-top:23px">Search <i class="fa fa-search"></i></button>
                        </div>

                    </div>
                </div>
            </form>
            <div class="table-responsive" id="booking_search_table">
                <table id="employee_data" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <td>S.No</td>
                            <td>Payment Status</td>
                            <td>GRN No</td>
                            <td>GRN Date</td>
                            <td>Consignee Name</td>
                            <!-- <td>Consignee Contact</td>   -->
                            <td>Destination</td>
                            <td>No Of Pkgs</td>
                            <td>Status</td>
                            <td>Action</td>

                        </tr>
                    </thead>
                    <tbody id="get_month_details">
                        <?php
                        $date = date('d-m-Y');
                        $my = date('m-Y');
                        $dt = (explode("-", $date));

                        //   var_dump($dt); //array(3) { [0]=> string(2) "03" [1]=> string(2) "08" [2]=> string(4) "2021" }
                        if ($dt[1] <= 3) {
                            $m1 = 1;
                            $y = $dt[2];
                            $trans_name = "transaction_" . $m1 . "_" . $y;
                            $trans_images = "transaction_images_" . $m1 . "_" . $y;
                            $trans_invoice = "transaction_invoice_" . $m1 . "_" . $y;
                            //echo "First Quarter";

                        } else if (($dt[1] >= 4) && ($dt[1] <= 6)) {
                            $m1 = 2;
                            $trans_name = "transaction_" . $m1 . "_" . $y;
                            $trans_images = "transaction_images_" . $m1 . "_" . $y;
                            $trans_invoice = "transaction_invoice_" . $m1 . "_" . $y;
                            //echo "Second Quarter";
                        } else if (($dt[1] >= 7) && ($dt[1] <= 9)) {
                            $m1 = 3;
                            $y = $dt[2];
                            $trans_name = "transaction_" . $m1 . "_" . $y;
                            $trans_images = "transaction_images_" . $m1 . "_" . $y;
                            $trans_invoice = "transaction_invoice_" . $m1 . "_" . $y;

                            //   var_dump($trans_invoice);
                            //echo "Third Quarter";
                        } else {
                            $m1 = 4;
                            $y = $dt[2];
                            $trans_name = "transaction_" . $m1 . "_" . $y;
                            $trans_images = "transaction_images_" . $m1 . "_" . $y;
                            $trans_invoice = "transaction_invoice_" . $m1 . "_" . $y;
                            //echo "Fourth Quarter";
                        }

                        //var_dump($client_id);

                        $query = "select *from transaction_" . $m1 . "_" . $dt[2] . " where consigner ='$client_id' order by transaction_id desc";
                        $query_result = mysqli_query($conn, $query);
                        $i = 1;
                        while ($user_booking = mysqli_fetch_assoc($query_result)) {
                            $booking = $user_booking['booking_status'];
                            $remarks = $user_booking['remarks'];
                            $cancelled_by = get_user($conn, $user_booking['cancelled_by']);
                            $updated_at = $user_booking['updated_at'];
                            $query_invoice = "select sum(no_of_pkge) as package from transaction_invoice_" . $m1 . "_" . $dt[2] . " where transaction_id ='" . $user_booking['transaction_id'] . "' ";
                            $query_invoice_result = mysqli_query($conn, $query_invoice);
                            $package_details = mysqli_fetch_assoc($query_invoice_result);
                        ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td>
                                    <?php                                   
                                    $data = array(
                                        'transaction_id' => array($user_booking['transaction_id']),
                                        'company_name' => get_client_name($conn, $client_id),
                                        'grn_date' => array($user_booking['grn_date']),
                                        'email' => get_client_emails($conn, $client_id),
                                        'phone' => get_client_phones($conn, $client_id),
                                        'amount' => array($user_booking['total']),
                                        'grn_no' => array($user_booking['grn_no']),
                                        'invoice_no' => array($user_booking['invoice_no']),
                                        'client_id' => $user_booking['consigner']
                                    );
                                    $data_serialize = serialize($data);


                                    $link_wit_data = http_build_query(array('aParam' => $data_serialize));
                                    $link_url = urlencode($link_wit_data);

                                    $output_url = "http://localhost/graciousexpress/verify_paylink1.php?data=" . $link_url;
                                    ?>
                                    <?php
                                    if ($booking == '1') { ?>
                                        <span
                                            style="text-decoration: none;background-color: #ff5129;padding: 4px 10px;color: white;border: none;display: flex;margin: auto;border-radius: 15px;justify-content: center;">Cancelled</span>
                                    <?php } else {
                                        if (($user_booking['paid_status'] != '1' && $user_booking['paid_status'] == '0' && $user_booking['mode_of_consignment'] == '3')) { ?>
                                            <a style="text-decoration: none;background-color: #2962ff;padding: 4px 10px;color: white;border: none;display: flex;margin: auto;border-radius: 15px;justify-content: center;"
                                                href="<?php echo $output_url; ?>">Pay Now</a>
                                        <?php } else {
                                            if ($user_booking['paid_status'] == '2') { ?>
                                                <span
                                                    style="text-decoration: none;background-color: #e7cb04;padding: 4px 10px;color: white;border: none;display: flex;margin: auto;border-radius: 15px;justify-content: center;">Partially
                                                    Paid</span>
                                            <?php } else if ($user_booking['paid_status'] == '1') { ?>
                                                    <span
                                                        style="text-decoration: none;background-color: #069f03;padding: 4px 10px;color: white;border: none;display: flex;margin: auto;border-radius: 15px;justify-content: center;">Paid</span>
                                            <?php } else {
                                                echo '<span
                                                style="text-decoration: none;background-color: coral;padding: 4px 10px;color: white;border: none;display: flex;margin: auto;border-radius: 15px;justify-content: center;">Other Mode</span>';
                                            }
                                        }
                                    }
                                    ?>
                                </td>
                                <td><?php echo $user_booking['grn_no']; ?></td>
                                <td><?php echo $user_booking['grn_date']; ?></td>
                                <td><?php echo get_client_name($conn, $user_booking['consignee']); ?></td>
                                <!-- <td><?php //echo $user_booking['consignee'];
                                            ?></td> -->
                                <td><?php echo get_city_name($conn, $user_booking['destination']); ?></td>
                                <td><?php echo $package_details['package']; ?></td>
                                <td>
                                    <?php if ($booking == '1') {
                                        echo "<span style='color:red;'>Consignment Cancelled</span>";
                                    } else {
                                        echo get_trans_status($user_booking['status']);
                                    }
                                    ?>
                                </td>
                                <?php if ($booking == '1') { ?>
                                    <td>

                                        <a title="Info" href="#cancel_grn_popup" class="table-actions show_info_popup" data-toggle="modal" data-remarks="<?php echo $remarks; ?>" data-createdby="<?php echo $cancelled_by; ?>" data-createdat="<?php echo $updated_at; ?>" id="<?php echo $user_booking['transaction_id']; ?>"><i class="fa fa-exclamation-circle"></i></a>&nbsp;&nbsp;&nbsp;
                                        <a href="javascript:void(0);" class="btn-views disable_action" data-id="<?php echo $user_booking['transaction_id']; ?>" data-grn="<?php echo $user_booking['grn_date']; ?>"><i class="fa fa-eye" title="View"></i></a>&nbsp;&nbsp;&nbsp;
                                        <a href="javascript:void(0);" class="btn-tracks disable_action" id="<?php echo $user_booking['transaction_id']; ?>"><img src="http://localhost/graciousexpress/user/images/track_icon_svg.svg" style="opacity:0.4;"></img></a>&nbsp;&nbsp;&nbsp;
                                        <?php if ($user_booking['status'] == '8') { ?>
                                            <a href="javascript:void(0);" class="btn-tracks disable_action" id="<?php echo $user_booking['transaction_id']; ?>"><i class="fa fa-image" title="View"></i> POD </a>&nbsp;&nbsp;&nbsp;
                                        <?php } ?>
                                    </td>
                                <?php
                                } else {
                                ?>
                                    <td>
                                        <a href="#myModal" data-toggle="modal" class="btn-view" data-id="<?php echo $user_booking['transaction_id']; ?>" data-grn="<?php echo $user_booking['grn_date']; ?>"><i class="fa fa-eye" title="View"></i></a>&nbsp;&nbsp;&nbsp;
                                        <a href="user-track-consignment.php?key=<?php echo md5($user_booking['transaction_id']); ?>&grn_date=<?php echo $user_booking['grn_date']; ?>" class="btn-track" id="<?php echo $user_booking['transaction_id']; ?>"><img src="http://localhost/graciousexpress/user/images/track_icon_svg.svg"></img></a>&nbsp;&nbsp;&nbsp;
                                        <?php if ($user_booking['status'] == '8') { ?>
                                            <a href="proof_of_delivery_page.php?key=<?php echo md5($user_booking['transaction_id']); ?>&grn_date=<?php echo $user_booking['grn_date']; ?>" class="btn-track" id="<?php echo $user_booking['transaction_id']; ?>"><i class="fa fa-image" title="View"></i> POD </a>&nbsp;&nbsp;&nbsp;
                                        <?php } ?>
                                    </td>
                                <?php } ?>

                            </tr>
                        <?php
                            $i++;
                        }
                        ?>
                    </tbody>

                </table>
            </div>

            <!-- Modal -->

        </div>
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
        <script src='https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js'></script>

        <script>
            $(document).ready(function() {
                $('#employee_data').DataTable({
                    "columnDefs": [{
                            "width": "11%",
                            "targets": 5
                        },
                        {
                            "width": "10%",
                            "targets": 4
                        }
                    ]
                });


                //Payment Successful Popup
                var popMsg = '<?php echo $message; ?>';
                var paymentIdd = '<?php echo $paymentId; ?>';
                if (popMsg != '') {
                    swal({
                        title: "Payment Successful!",
                        text: "Your Payment ID is " + paymentIdd,
                        icon: "success",
                        html: true,
                        button: "OK",
                    }).then(function() {
                        window.location = "booking_list.php";

                    });
                }

                //End Payment Successful Popup


                $(document).on("click", ".btn-view", function(e) {
                    e.preventDefault();
                    var tbl_id = $(this).data("id");
                    var grn_date = $(this).data("grn");
                    //alert(tbl_id);
                    $.ajax({
                        url: "../fetch-details.php",
                        method: "post",
                        data: {
                            cmd: "get_booking_details",
                            tbl_id: tbl_id,
                            grn_date: grn_date
                        },
                        success: function(response) {
                            console.log(response);
                            $('.table_content').html(response);
                        }
                    });
                })
                $(document).on('click', '#search', function(e) {
                    e.preventDefault();

                    var data = $('#booking_list').serialize();

                    //alert(data);
                    if ($('#booking_list').valid() == true) {

                        $.ajax({
                            url: '../fetch-details.php',
                            type: "GET",
                            data: data,
                            success: function(result) {
                                console.log(result);
                                $('#employee_data').DataTable().destroy();
                                $('#booking_search_table').html(result);
                                $("#employee_data").DataTable();
                            }
                        });
                    }

                });

                //      $(document).on('click','.btn-track',function(e){
                //        alert("hi");
                //        $(".form-data-saving").show();
                //        var tbl_id = $(this).attr("id");
                //        //alert(tbl_id);
                //        $.ajax({
                //            url:"http://localhost/graciousexpress/fetch-details.php",
                //            cache: false,
                //            type: "post",
                //            dataType:"json",
                //            data:{cmd:"get_track_no",tbl_id:tbl_id},
                //            success:function(data){
                //                console.log(data);
                //                $(".form-data-saving").hide();
                //                //$("#form_name").val("new_client");
                //                $("#grn_no").val(data['grn_no']);

                //            },
                //            error: function(jqxhr) {
                // 					alert(jqxhr.responseText);
                // 				}
                //        });

                //    });



                //Show Cancellation Popup
                $(document).on("click", ".show_info_popup", function(e) {
                    e.preventDefault();
                    var remarkss = $(this).data('remarks');
                    console.log(remarkss);
                    var created_by = $(this).data('createdby');
                    var created_at = $(this).data('createdat');
                    $('#showRemarks').val(remarkss);
                    $('#show_client_id').html(created_by);
                    $('#show_created_at').html(created_at);
                    // $("#show_cancel_grn").show();

                    //alert(tbl_id);
                })

                //End

            });
            $(window).load(function() {
                $(".loading-page").hide();
            });
        </script>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                //alert('datepicker');
                jQuery("#month").monthpicker({
                    showOn: "both",
                    dateFormat: 'mm-yy'
                    // showOn:     "both",
                    // buttonImage: "calendar.png",
                    // buttonImageOnly: true
                });
            });
        </script>
        <div class="modal fade common_popup" id="myModal" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                        <h4 class="modal-title" style="color:#fff">
                            Booking Details
                        </h4>
                    </div>

                    <div class="modal-body">
                        <h5>
                            <b>Consignee Details:</b>
                        </h5>
                        <div class="table_content"></div>
                        <div class="modal-footer">
                            <!-- <button class="btn btn-primary btn-confirm-delete" data-dismiss="modal" type="button" id="">Yes</button> -->
                            <button class="btn btn-default-outline" data-dismiss="modal" type="button" id="">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!--- Show Cancellation Popup -->
        <div class="modal fade common_popup" id="cancel_grn_popup" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                        <h4 class="modal-title" style="color:#fff">
                            Reason For Cancellation
                        </h4>
                    </div>

                    <div class="modal-body">

                        <div class="table_content">
                            <label>Remarks:</label>
                            <textarea id="showRemarks" name="showRemarks" class="form-control" rows="4" readonly></textarea>
                            <small>Cancelled by : </small><small name="show_client_id" id="show_client_id"><span id="span_d"></span></small></br>
                            <small>Cancelled at : </small><small name="show_created_at" id="show_created_at"></small>

                        </div>
                        <div class="modal-footer">
                            <!-- <button class="btn btn-primary btn-confirm-delete" data-dismiss="modal" type="button" id="">Yes</button> -->
                            <button class="btn btn-default-outline" data-dismiss="modal" type="button" id="">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- End -->
</body>

</html>