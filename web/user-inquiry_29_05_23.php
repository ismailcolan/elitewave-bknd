<?php
include_once('include/connect.php');
include_once('include/function.php');
?>
<!doctype html>
<html lang="en">

<head>
    <?php include("include/title.php"); ?>
    <?php include("include/css_js.php"); ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <style>
        a.disable {
            pointer-events: none;
            cursor: default;
        }

        .dataTable th.sorting:after,
        .dataTable th.sorting_desc:after {
            top: 9px;
            right: 2px;
        }

        .dataTable th.sorting:before,
        .dataTable th.sorting_asc:after {
            top: 3px;
            right: 2px;
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
    width: 40%;
    float: left;
    margin: 5px 0 10px;
}
.user-inquiry_tbl{
	margin: 0 auto;
	width: max-content!important;
    max-width: unset!important;

    clear: both;
    border-collapse: collapse;
    table-layout: fixed;
}
}

    </style>
</head>

<body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
        <div class="navbar navbar-fixed-top scroll-hide">
            <?php include_once('include/header.php'); ?>
            <?php include_once('include/menu.php'); ?>
        </div>
    </div>
    <div class="container-fluid main-content new_dpt_bottom">
        <div class="row">
            <div class="col-md-offset-1 col-md-10">
                <div class="widget-container fluid-height clearfix">
                    <div class="heading"> <i class="fa fa-table"></i> List of User Inquiry 
                    <!-- <span class="align-right"><i class="fa fa-plus"></i> <a href="client.php">Add Client</a></span> -->
                 </div>
                    <div class="widget-content padded clearfix new_dept">
                        <table class="table table-bordered table-striped user-inquiry_tbl" id="dataTable1">
                            <thead>
                                <th class="table-title" style="width:1%">S.No</th>
                                <th class="table-title" style="width:5%">GRN No</th>
                                <th class="table-title" style="width:5%">Consignor Name</th>
                                <th class="table-title" style="width:10%">Address</th>
                                <th class="table-title" style="width:1%">Contact</th>
                                <th class="table-title" style="width:1%">City</th>

                                <th class="table-title" style="width:1%">Consignor</th>
                                <th class="table-title" style="width:1%">Consignee</th>
                                <th class="table-title" style="width:1%">Action</th>
                            </thead>
                            <tbody>
                                <?php
                                $query = mysqli_query($conn, "select * from user_inquiry_list order by id desc");
                                $i = 1;
                                while ($row = mysqli_fetch_array($query)) {
                                    $get_consignor_city = get_city_name($conn, $row['consignor_city']);
                                ?>
                                    <tr>
                                        <td class="text-center"><?php echo $i; ?></td>
                                        <td class="text-center"><?php echo $row['booking_id']; ?></td>
                                        <td class="text-center"><?php echo $row['consignor_name']; ?></td>
                                        <td class="text-center"><?php echo $row['consignor_address']; ?></td>
                                        <td class="text-center"><?php echo $row['consignor_contact']; ?></td>
                                        <td class="text-center"><?php echo $get_consignor_city ?></td>

                                        <td class="actions center-content ">

                                            <div class="action-buttons">
                                                <?php
                                                $client_status = mysqli_query($conn, "select *from users where user_id ='" . $row['user_id'] . "'");
                                                $row1 = mysqli_fetch_assoc($client_status);
                                                $status_user = $row1['status'];
                                                if ($status_user == 0) {
                                                ?>
                                                    <a title="Add to Client" href="add_to_client.php?key=<?php echo md5($row['user_id']); ?>" class="table-actions btn-edit" id="<?php echo $row['user_id']; ?>"><i class="fa fa-plus"> Client</i></a>
                                                <?php
                                                } else { ?>
                                                    <a title="Client" href="" class="table-actions disable" id=""><i class="fa fa-check"> Client</i></a>
                                                <?php
                                                }
                                                ?>

                                            </div>
                                        </td>
                                        <td class="actions center-content ">

                                            <div class="action-buttons">
                                                <?php
                                                $client_status = mysqli_query($conn, "select *from users where user_id ='" . $row['user_id'] . "'");
                                                $row1 = mysqli_fetch_assoc($client_status);
                                                $status_user = $row1['status'];
                                                if ($status_user == 0) {
                                                ?>
                                                    <a title="Add to Client" href="add_to_clientt.php?key=<?php echo md5($row['user_id']); ?>" class="table-actions btn-edit" id="<?php echo $row['user_id']; ?>"><i class="fa fa-plus"> Client</i></a>
                                                <?php
                                                } else { ?>
                                                    <a title="Client" href="" class="table-actions disable" id=""><i class="fa fa-check"> Client</i></a>

                                                <?php
                                                }
                                                ?>


                                            </div>
                                        </td>
                                        <td class="text-center">
                                        <?php if($row['status'] == '2'){?>
                                            <a title="Consignment Booked" href="#" class="table-actions btn-invoice disable" data-toggle="modal" id="<?php echo $row['user_id'] ?>"><i class="fa fa-check"></i></a>
                                       <?php }else{ ?>
										<!-- <a title="Invoice" href="user_invoice_generate.php?key=<?php //echo md5($row['user_id']);?>" class="table-actions btn-invoice " data-toggle="modal" id="<?php //echo $row['user_id'] ?>"><i class="fa fa-file"></i></a> -->
									<a title="Book Consignment" href="user_inquiry_to_consignment.php?key=<?php echo md5($row['user_id']);?>" class="table-actions btn-invoice " data-toggle="modal" id="<?php echo $row['user_id'] ?>"><i class="fa fa-pencil"></i></a>

									<!-- <a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php //echo $row['user_id'] ?>"><i class="fa fa-trash-o"></i></a></td> -->
                                    </tr>
                                <?php
                                    $i++;
                                }
                            }	
                                ?>

                            </tbody>
                        </table>

                    </div>

                </div>
            </div>
        </div>
        <?php require_once("include/footer.php"); ?>

    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            // e.preventDefault();

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
                $.post('<?php print_r(site_paths) ?>save_details.php', {
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
            }); //btn actvie

            $('#dataTable1').on('click', '.btn-edit', function() {
                $(document).on('click', '.btn-edit', function(e) {
                    $(".form-data-saving").show();
                    var tbl_id = $(this).attr("id");
                    //alert(tbl_id);
                    $.ajax({
                        url: "<?php print_r(site_paths) ?>fetch-details.php",
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

            $(document).on('click', '.btn-invoice', function(e) {
                e.preventDefault();
                $(".form-data-saving").show();
                var tbl_id = $(".btn-invoice").attr("id");
                    ewToast(tbl_id, 'info');
                $.ajax({
                    url: "<?php print_r(site_paths) ?>fetch-details.php",
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