<?php
include("../config.ini.php");
if (session_id() == '') {
    session_start();
}
//Check Page is Second Time Loaded
// $RequestSignature = ($_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING']);
// if ($_SESSION['LastRequest'] == $RequestSignature){
//      unset($_SESSION['msg']); //remove session message
//      unset($_SESSION['paymentId']); //remove session payment
// }else{
//   $_SESSION['LastRequest'] = $RequestSignature;
// }
//End Check Page is Second Time Loaded
// $message = (isset($_SESSION['msg'])) ? $_SESSION['msg'] : "";
// $paymentId = (isset($_SESSION['paymentId'])) ? $_SESSION['paymentId'] : "";

include_once('include/user-function.php');
//include_once('include/function.php');
// $conn = mysqli_connect("localhost", "staging", "vySzrpsqDRupDHS", "staging");

if ($_GET['key'] != '') {
    $client_id = $_GET['key'];
    $query = "SELECT * FROM `razorpay_payment` where md5(client_id) = '$client_id' order by created_at desc";
    $sql = mysqli_query($conn, $query);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
    <title>Gracious Express - Transactions</title>
    <?php include("include/title.php"); ?>
    <?php include("include/css_js_forgetpassword.php"); ?>
    <link href="favicon.png" type="image/x-icon" rel="shortcut icon">
    <link href="assets/css/master.css" rel="stylesheet">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">
    <link href="stylesheets/datatables.css" media="all" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha512-aOG0c6nPNzGk+5zjwyJaoRUgCdOrfSDhmMID2u4+OIslr0GjpLKo7Xm0Ao3xmpM4T8AmIouRkqwj1nrdVsLKEQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- book consignment css and js starts here -->
    <link rel="stylesheet" href="assets/css/book-consignment.css">
    <link rel="stylesheet" href="f5/fontawesome.min.css">
    <!-- book consignment css and js finished here -->
    <!-- <script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script> -->
    <script src="assets/js/jquery.validate.min.js"></script>
    <script src="assets/js/modernizr.custom.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap.min.js"></script>

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

        .borderless td,
        .borderless th {
            border: none;
        }

        .disable_action {
            color: #7a888f;
            cursor: not-allowed;
        }

        .dataTable th.sorting {
            color: #04243D;
            cursor: pointer;
            position: relative;
        }

        .dataTables_filter {
            width: unset !important;
            float: right;
            text-align: right;
            color: #5f5f5f;
        }

        button.dt-button.buttons-excel.buttons-html5 {
            background: #337ab7;
            ;
            color: white;
        }

        button.dt-button.buttons-excel.buttons-html5:hover {
            background: #00adff;
        }

        button.dt-button.buttons-pdf.buttons-html5 {
            background: #337ab7;
            color: white;
        }

        button.dt-button.buttons-pdf.buttons-html5:hover {
            background: #00adff;
        }

        @media (min-width: 360px) and (max-width: 576.98px) {
            div.dt-buttons {
                float: left !important;
                text-align: center;
                margin-top: 7px;
                margin-left: 12px;
            }

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
            div#employee_data_filter {
                margin-top: 8px;
                margin-right: 6px;
            }
            button#search {
                margin-top:0px!important;
              }
              table.dataTable thead>tr>th.sorting, table.dataTable thead>tr>th.sorting_asc{
               padding-right: 6px;
              }
              .table > thead > tr > th {

               font-size: 12px;
               
               }

        }
    </style>

</head>

<body>

    <?php include "user-db-header.php" ?>
    <br /><br />
    <div class="container">
        <h3 align="center"><b>View Transactions</b></h3>

        <div class="col-md-12">

            <form id="booking_list">
                <input type="hidden" id="form_name" name="form_name" value="transaction_form">
                <input type="hidden" id="edit_id" name="edit_id" value="<?php echo $client_id; ?>">
                <input type="hidden" id="cmd" name="cmd" value="get_monthly_payment_transactions_details">
                <div class="row">
                    <div class="col-sm-offset-4 col-sm-6">

                        <div class="col-md-6">
                            <!-- <div class="form-group">
                              <label for="">Month:</label>
                                <input type="text" name="month" id="month" value="" class="form-control" />
                         </div> -->
                            <label for="">Month:</label>
                            <div class="right-inner-addon ">
                                <i class="fa fa-calendar"></i>
                                <input type="text" name="month" id="month" value="" class="form-control" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null :event.charCode >= 96 && event.charCode <= 105 && event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off" required/>
                            </div>

                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-success" id="search" style="margin-top:23px">Search <i class="fa fa-search"></i></button>
                        </div>

                    </div>
                </div>
            </form>
            <div class="table-responsive" id="search_transactions">
                <table id="employee_data" class="table table-striped table-bordered display" style="width:100%">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Payment Date</th>
                            <th>GRN No</th>
                            <!-- <th>Order ID</th> -->
                            <th>Payment ID</th>
                            <th>Invoice Amount</th>
                            <th>Paid Amount</th>
                            <th>Due Amount</th>
                            <th>Status</th>

                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sno = 1;
                        $total_invoice_amt = 0;
                        $total_paid_amt = 0;
                        $total_due_amt = 0;
                        while ($row = mysqli_fetch_assoc($sql)) {

                            $timestamp = $row['created_at'];
                            $timestamp = DateTime::createFromFormat('Y-m-d H:i:s', $timestamp);
                            $newDate = $timestamp->format('d-m-Y H:i:s');
                            $total_invoice_amt += $row['amount'];
                            $total_paid_amt += $row['paid'];
                            $total_due_amt += $row['balance'];
                        ?>

                            <tr>
                                <td><?php echo $sno; ?></td>
                                <td><?php echo $newDate; ?></td>
                                <td><?php echo $row['grn_no']; ?></td>
                                <td><?php echo $row['razorpayPaymentId']; ?></td>
                                <td>&#x20b9; <?php echo number_format($row['amount'], 2, '.', ''); ?></td>
                                <td>&#x20b9; <?php echo number_format($row['paid'], 2, '.', ''); ?></td>
                                <td>&#x20b9; <?php echo number_format($row['balance'], 2, '.', ''); ?></td>
                                <td><?php echo $row['paymentStatus']; ?></td>
                            </tr>

                        <?php
                            $sno++;
                        }
                        ?>
                    </tbody>
                    <tfoot style="color:#04243D">
                        <tr>
                            <th colspan="3"></th>
                            <th>Total</th>
                            <th>&#x20b9;<?php echo number_format($total_invoice_amt, 2, '.', ''); ?></th>
                            <th>&#x20b9;<?php echo number_format($total_paid_amt, 2, '.', ''); ?></th>
                            <th>&#x20b9;<?php echo number_format($total_due_amt, 2, '.', ''); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Modal -->

        </div>
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
        <script src='https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js'></script>
        <script src="//cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="//cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
        <script src="//cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
        <script>
            // $('#employee_data').DataTable({
            //      "columnDefs": [{
            //                "width": "11%",
            //                "targets": 5
            //           },
            //           {
            //                "width": "10%",
            //                "targets": 4
            //           }
            //      ],

            // });
            $(document).ready(function() {
                $('#employee_data').DataTable({
                    footer: true,
                    dom: 'Bfrtip',
                    buttons: [{
                            extend: 'excelHtml5',
                            title: 'Gracious Transaction Export To Excel'
                        },
                        {
                            extend: 'pdfHtml5',
                            title: 'Gracious Transaction Export To PDF'
                        }
                        // 'excel', 'pdf'
                    ]
                });
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
        </script>
        <script>
            var visit = localStorage.getItem("redirect_page_view");
            var retry = sessionStorage.getItem('retry_payment');
            localStorage.removeItem("redirect_page_view")
            sessionStorage.removeItem("retry_payment")

            console.log(visit);
            console.log(retry);

            $(document).ready(function() {


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
                                $('#search_transactions').html(result);
                                $("#employee_data").DataTable({
                                    dom: 'Bfrtip',
                                    buttons: [{
                                            extend: 'excelHtml5',
                                            title: 'Gracious Transaction Export To Excel'
                                        },
                                        {
                                            extend: 'pdfHtml5',
                                            title: 'Gracious Transaction Export To PDF'
                                        }
                                        // 'excel', 'pdf'
                                    ]
                                });


                            }


                        })



                    }



                });

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



                //      $(document).on('click','.btn-track',function(e){
                //        alert("hi");
                //        $(".form-data-saving").show();
                //        var tbl_id = $(this).attr("id");
                //        //alert(tbl_id);
                //        $.ajax({
                //            url:"http://localhost/GraciousExpress/fetch-details.php",
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