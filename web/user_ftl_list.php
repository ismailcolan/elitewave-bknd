<?php
include_once ('include/connect.php');
include_once ('include/function.php');
?>
<!doctype html>
<html lang="en">

<head>
    <?php include ('include/title.php'); ?>
    <?php include ('include/css_js.php'); ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <style>
        a.disable {
            pointer-events: none;
            cursor: default;
        }

        .dataTable th.sorting:after,
        .dataTable th.sorting_desc:after {
            top: 17px;
            right: 3px;
        }

        .dataTable th.sorting:before,
        .dataTable th.sorting_asc:after {
            top: 10px;
            right: 3px;
        }
@media (min-width: 360px) and (max-width:575.98px) { 
div#dataTable1_filter {
    display: block;
}


div#dataTable1_length {
    display: block;
}
.dataTables_filter input {
    width: 112px;
 
}
.dataTables_length {
    width: 43%;
    float: left;
    margin: 5px 0 10px;
}

.ftl_tbl{
	margin: 0 auto;
	width: max-content!important;
    max-width: unset!important;

    clear: both;
    border-collapse: collapse;
    table-layout: fixed;
}
th.table-title.sorting {
    width: 111px!important;
}
.dataTables_filter {
    width: 56%;
    float: right;
    text-align: right;
    color: #5f5f5f;
}
}
    </style>
</head>

<body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
        <div class="navbar navbar-fixed-top scroll-hide">
            <?php include_once ('include/header.php'); ?>
            <?php include_once ('include/menu.php'); ?>
        </div>
    <div class="container-fluid main-content new_dpt_bottom">
        <div class="row">
            <div class="col-md-offset-1 col-md-10">
                <div class="widget-container fluid-height clearfix">
                    <div class="heading"> <i class="fa fa-table"></i> List FTL Quotation </div>
                    <div class="widget-content padded clearfix new_dept">
                        <table class="table table-bordered ftl_tbl table-striped" id="dataTable1">
                            <thead>
                                <th class="table-title" style="width:1%">S.No</th>
                                <th class="table-title" style="width:5%">GRN No</th>
                                <th class="table-title" style="width:5%">GRN Date</th>
                                <th class="table-title" style="width:10%">Consignor Name</th>
                                <th class="table-title" style="width:10%">Consignee Name</th>
                                <th class="table-title" style="width:3%">Origin</th>
                                <th class="table-title" style="width:5%">Destination</th>
                                <th class="table-title" style="width:10%">FTL Type</th>

                                <!-- <th class="table-title" style="width:1%">Consignor</th>
								<th class="table-title" style="width:1%">Consignee</th> -->
                                <th class="table-title" style="width:1%">Action</th>
                            </thead>
                            <tbody>
                                <?php
                                $date = date('d-m-Y');
                                $my = date('m-Y');
                                $dt = (explode('-', $date));

                                //   var_dump($dt); //array(3) { [0]=> string(2) "03" [1]=> string(2) "08" [2]=> string(4) "2021" }
                                if ($dt[1] <= 3) {
                                    $m1 = 1;
                                    $y = $dt[2];
                                    $trans_name = 'transaction_' . $m1 . '_' . $y;
                                    $trans_images = 'transaction_images_' . $m1 . '_' . $y;
                                    $trans_invoice = 'transaction_invoice_' . $m1 . '_' . $y;
                                    // echo "First Quarter";
                                } else if (($dt[1] >= 4) && ($dt[1] <= 6)) {
                                    $m1 = 2;
                                    $trans_name = 'transaction_' . $m1 . '_' . $y;
                                    $trans_images = 'transaction_images_' . $m1 . '_' . $y;
                                    $trans_invoice = 'transaction_invoice_' . $m1 . '_' . $y;
                                    // echo "Second Quarter";
                                } else if (($dt[1] >= 7) && ($dt[1] <= 9)) {
                                    $m1 = 3;
                                    $y = $dt[2];
                                    $trans_name = 'transaction_' . $m1 . '_' . $y;
                                    $trans_images = 'transaction_images_' . $m1 . '_' . $y;
                                    $trans_invoice = 'transaction_invoice_' . $m1 . '_' . $y;

                                    //   var_dump($trans_invoice);
                                    // echo "Third Quarter";
                                } else {
                                    $m1 = 4;
                                    $y = $dt[2];
                                    $trans_name = 'transaction_' . $m1 . '_' . $y;
                                    $trans_images = 'transaction_images_' . $m1 . '_' . $y;
                                    $trans_invoice = 'transaction_invoice_' . $m1 . '_' . $y;
                                    // echo "Fourth Quarter";
                                }

                                // var_dump($client_id);

                                $query = 'select *from transaction_' . $m1 . '_' . $dt[2] . " where mode_of_transportation ='7' order by transaction_id desc";
                                $query_result = mysqli_query($conn, $query);
                                $i = 1;
                                while ($user_booking = mysqli_fetch_assoc($query_result)) {
                                    $query_invoice = 'select sum(no_of_pkge) as package from transaction_invoice_' . $m1 . '_' . $dt[2] . " where transaction_id ='" . $user_booking['transaction_id'] . "' ";
                                    $query_invoice_result = mysqli_query($conn, $query_invoice);
                                    $package_details = mysqli_fetch_assoc($query_invoice_result);
                                    $get_consignor_city = get_city_name($conn, $user_booking['consignor_city']);
                                    ?>

                                    <tr>
                                        <td><?php echo $i; ?></td>
                                        <td><?php echo $user_booking['grn_no']; ?></td>
                                        <td><?php echo $user_booking['grn_date']; ?></td>
                                        <td><?php echo get_client_name($conn, $user_booking['consigner']); ?></td>
                                        <td><?php echo get_client_name($conn, $user_booking['consignee']); ?></td>
                                        <td><?php echo get_city_name($conn, $user_booking['city']); ?></td>
                                        <td><?php echo get_city_name($conn, $user_booking['con_city']); ?></td>
                                        <td><?php echo $user_booking['ftl_type']; ?></td>
                                        <td>
                                            <?php if ($user_booking['invoice_no'] == '') { ?>
                                                <a title="Edit" href="ftl_pending_consignment.php?key=<?php echo md5($user_booking['transaction_id']); ?>&m=<?php echo $m1; ?>&y=<?php echo $dt[2] ?>" class="table-actions btn-edit" id="<?php echo $user_booking['transaction_id']; ?>"><i class="fa fa-pencil"></i></a>

                                            <?php } else { ?>
                                                <a title="Booked" href="#" class="table-actions" id=""><i class="fa fa-check"></i></a>
                                                <!-- <a title="Book Consignment" href="user_inquiry_to_consignment.php?key=<?php  // echo md5($user_booking['transaction_id']);
                                        ?>" class="table-actions btn-invoice " data-toggle="modal" id="<?php echo $user_booking['transaction_id'] ?>"><i class="fa fa-file"></i></a></td> -->
                                            <?php } ?>
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
        <?php require_once ('include/footer.php'); ?>

    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            		// DataTable initialized by main.js
            $(".btn-active").click(function() {
                $(".form-data-saving").show();
                var status1 = '';
                var msg = '';
                var status = $(this).attr('data-status');
                ewToast(status, 'info');
                if (status == '0') {
                    status1 = '1';
                    msg = 'Approved';
                } else {
                    status1 = '0';
                    msg = 'Not Approved';
                }
                $.post('../save_details.php', {
                    form_name: "inactivate_user",
                    tbl_id: $(this).attr("id"),
                    status: status1
                }, function(data, status) {
                    console.log(data);
                    if (data != 0) {
                        $(".form-data-saving").hide();
                        $("#alert-status").text("");
                        $("#alert-message").text("User is " + msg + ".");
                        $("#alert-container").addClass('alert-success').slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                            $("#alert-container").hide();
                            $("#alert-container").removeClass('alert-success');
                            location.reload();
                        });
                    } else if (data == 2) {
                        $(".form-data-saving").hide();
                        $("#alert-status").text("");
                        $("#alert-message").text("User is " + msg + " Now...");
                        $("#alert-container").addClass('alert-danger').slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                            $("#alert-container").hide();
                            $("#alert-container").removeClass('alert-danger');
                            location.reload();
                        });
                    }
                    // else if(data == "404-del"){        
                    //     $(".delete-error-popup").show();
                    // 	$(".form-data-saving").hide();
                    // }
                });
            });

            $(document).on('click', '.btn-edit', function(e) {
                $(".form-data-saving").show();
                var tbl_id = $(this).attr("id");
                //alert(tbl_id);
                $.ajax({
                    url: "../fetch-details.php",
                    cache: false,
                    type: "post",
                    dataType: "json",
                    data: {
                        cmd: "get_new_user_detail",
                        tbl_id: tbl_id
                    },
                    success: function(data) {
                        console.log(data);
                        $(".form-data-saving").hide();
                        //$("#form_name").val("new_client");
                        $("#edit_id").val(data['user_id']);

                    },
                        error: function(jqxhr) {
                            ewToast(jqxhr.responseText, 'error');
                        }
                    });

            });

            $(document).on('click', '.btn-invoice', function(e) {
                e.preventDefault();
                $(".form-data-saving").show();
                var tbl_id = $(".btn-invoice").attr("id");
                    ewToast(tbl_id, 'info');
                $.ajax({
                    url: "../fetch-details.php",
                    cache: false,
                    type: "post",
                    dataType: "json",
                    data: {
                        cmd: "get_new_user_detail",
                        tbl_id: tbl_id
                    },
                    success: function(data) {
                        console.log(data);
                        $(".form-data-saving").hide();
                        //$("#form_name").val("new_client");
                        $("#edit_id").val(data['user_id']);

                    },
                        error: function(jqxhr) {
                            ewToast(jqxhr.responseText, 'error');
                        }
                    });

            });
        });
        $(window).load(function() {
            $(".loading-page").hide();
        });
    </script>
    <div class="alert" id="alert-container" style="display:none;">
        <button type="button" class="close" data-dismiss="alert">x</button>
        <strong id="alert-status"></strong>
        <span id="alert-message"></span>
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
</body>

</html>