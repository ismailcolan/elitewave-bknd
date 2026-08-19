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

.user_appr_tbl{
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
                    <div class="heading"> <i class="fa fa-table"></i> List of User 
                    <!-- <span class="align-right"><i class="fa fa-plus"></i> 
                    <a href="client.php">Add Client</a></span>  -->
                </div>
                    <div class="widget-content padded clearfix new_dept">
                        <table class="table table-bordered table-striped user_appr_tbl" id="dataTable1">
                            <thead>
                                <th class="table-title" style="width:10%">S.No</th>
                                <th class="table-title" style="width:30%">Name</th>
                                <th class="table-title" style="width:30%">Username/Email</th>
                                <!-- <th class="table-title" style="width:30%">Email</th>-->
                                <th class="table-title" style="width:30%">Contact</th>
                                <!-- <th class="table-title" style="width:30%">City</th> -->
                                <th class="table-title" style="width:30%">Status</th>
                                <th class="table-title" style="width:10%">Action</th>
                            </thead>
                            <tbody>
                                <?php
                                // $con = mysqli_connect("localhost","root","","bookconsignment");
                                $query = mysqli_query($conn, 'select * from users order by user_id desc');
                                $i = 1;
                                while ($row = mysqli_fetch_array($query)) {
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $i; ?></td>
                                        <td><?php echo $row['user_name']; ?></td>
                                        <td><?php echo $row['email']; ?></td>
                                        <td><?php echo $row['contact_no']; ?></td>
                                        <!-- <td><?php  // echo $row['consignor_city'];
                                    ?></td> -->
                                        <td class="actions center-content ">

                                            <div class="action-buttons">
                                                <?php
                                                if ($row['status'] == '0') {
                                                    // if($status ==0){
                                                    ?>

                                                    <a class="table-actions btn-active" style="color:orange;" data-status="<?php echo $row['status'] ?>" title="Not Active" id="<?php echo $row['user_id'] ?>">In Active</a>
                                                <?php
                                                } else {
                                                    ?>
                                                    <a class="table-actions btn-active" style="color:green; hover:white;" data-status="<?php echo $row['status'] ?>" title="Active" id="<?php echo $row['user_id'] ?>">Active</a>
                                                <?php
                                                }
                                                ?>

                                            </div>
                                        </td>
                                        <td class="actions center-content ">

                                            <div class="action-buttons">
                                                <?php
                                                if ($row['status'] == '0') {
                                                    // if($status ==0){
                                                    ?>
                                                    <a class="table-actions btn-active" data-status="<?php echo $row['status'] ?>" title="Not Approval" id="<?php echo $row['user_id'] ?>"><i class="fa fa-check"></i></a>
                                                <?php
                                                } else {
                                                    ?>
                                                    <a class="table-actions btn-active" style="color:red;" data-status="<?php echo $row['status'] ?>" title="Approved" id="<?php echo $row['user_id'] ?>"><i class="fa fa-times"></i></a>
                                                <?php
                                                }
                                                ?>

                                                <?php
                                                if ($row['credential_status'] == '') {
                                                    // if($status ==0){
                                                    ?>

                                                    <a class="table-actions send_invoice" data-status="<?php echo $row['credential_status']; ?>" title="Sent Credential" id="send_invoice" data-id="<?php echo $row['user_id'] ?>"><i class="fa fa-envelope"></i></a>

                                                <?php
                                                } else {
                                                    ?>

                                                    <a class="table-actions " style="color:green;" title="Sent Credential" data-id="<?php echo $row['user_id'] ?>" readonly><i class="fa fa-envelope"></i></a>
                                                <?php
                                                }
                                                ?>

                                                <!-- <a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php  // echo $row['user_id']
                                    ?>"><i class="fa fa-trash-o"></i></a> -->

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
        <?php require_once ('include/footer.php'); ?>

    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            		// DataTable initialized by main.js

            $('#dataTable1').on('click', '.btn-active', function() {

                $(".btn-active").click(function() {
                    $(".form-data-saving").show();
                    var status1 = '';
                    var msg = '';
                    var status = $(this).attr('data-status');
                    //alert(status);
                    if (status == '0') {
                        status1 = '1';
                        msg = 'Approved';
                    } else {
                        status1 = '0';
                        msg = 'Not Approved';
                    }
                    $.post('https://elitewave360.in/php/save_details.php', {
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
                            $("#alert-message").text("User is " + msg + " Now, Please Wait Until Page Refresh.!!");
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
                //btn-active

            });

            //Send User Credentials


            $(document).on('click', '.send_invoice', function(e) {
                e.preventDefault();
                if (confirm('Are You Sure Want to Send User Credential?')) {
                    $(".form-data-saving").show();
                    var status = $(this).data('status');
                    var unique_user_id = $(this).data('id');
                    var form_name = "send_user_credentials";
                    //alert(month);
                    $.ajax({
                        url: 'https://elitewave360.in/php/save_details.php',
                        type: "POST",
                        data: {
                            unique_user_id: unique_user_id,
                            form_name: form_name,
                            status: status
                        },
                        success: function(result) {
                            $(".form-data-saving").hide();
                            console.log(result);
                            if (result != 0) {

                                $(".form-data-saving").hide();
                                $("#alert-status").text("");
                                $("#alert-message").text("Credential Sent Successfully");
                                $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-success");
                                    // $(this).data('id').hide();
                                    location.reload();
                                });
                            } else {
                                $(".form-data-saving").hide();
                                $("#alert-status").text("");
                                $("#alert-message").text("Credential Sent Failure!");
                                $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-danger");
                                    location.reload();
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