<?php
require_once ('include/connect.php');
require_once ('include/function.php');
?>
<?php
$logged_id = $_SESSION['user_id'];

// if ($_SESSION['role'] == 'AD') {
$query = 'select * from client';
// } else {
// 	$query = "select * from client where created_by='" . $_SESSION['user_id'] . "'";
// }
$result = mysqli_query($conn, $query);

while ($row1 = mysqli_fetch_array($result)) {
    $data[] = $row1;
}

?>
<!DOCTYPE html>
<html>

<head>
    <?php include ('include/title.php'); ?>
    <?php include ('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <style>
        .table td.actions .action-buttons {
            width: 147px;
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

        .paging_full_numbers {
            width: 50%;
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

            .clist_tb {
                margin: 0 auto;
                width: max-content !important;
                max-width: unset !important;

                clear: both;
                border-collapse: collapse;
                table-layout: fixed;
            }

            th.table-title.sorting_disabled {
                width: 52px !important;
            }
.dataTables_filter {
    width: 53%;
    float: right;
    text-align: right;
    color: #5f5f5f;
}
 

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
                <div class="col-md-offset-1 col-md-10">
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"> <i class="fa fa-table"></i> List of Client <span class="align-right"><i class="fa fa-plus"></i> <a href="client.php">Add Client</a></span> </div>
                        <div class="widget-content padded clearfix new_dept">


                            <table class="table table-bordered table-striped clist_tb" id="dataTable1">
                                <thead>
                                    <th class="table-title" style="width:10%">S.No</th>
                                    <th class="table-title" style="width:10%">Company Name</th>
                                    <th class="table-title" style="width:30%">Contact Person</th>
                                    <th class="table-title" style="width:15%">Action</th>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;

                                    foreach ($data as $row) {
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $i; ?></td>
                                            <td><?php echo $row['client_company_name']; ?></td>
                                            <td><?php echo $row['contact_person']; ?></td>

                                            <td class="actions center-content ">
                                                <?php
                                                if ($row['approve_status'] == 1) {
                                                    ?>
                                                    <div class="action-buttons">
                                                        <button class="btn btn-danger">Approval Pending</button>

                                                    </div>
                                                <?php
                                                } else {
                                                    ?>

                                                    <div class="action-buttons">
                                                        <a title="Edit" href="client.php?key=<?php echo md5($row['client_id']); ?>" class="table-actions btn-edit" id="<?php echo $row['client_id']; ?>"><i class="fa fa-pencil"></i></a>


                                                        <?php
                                                        if ($row['status'] == 0) {
                                                            ?>
                                                            <a class="table-actions btn-active" data-status="<?php echo $row['status'] ?>" title="InActive" id="<?php echo $row['client_id'] ?>"><i class="fa fa-check"></i></a>
                                                        <?php
                                                        } else {
                                                            ?>
                                                            <a class="table-actions btn-active" style="color:red;" data-status="<?php echo $row['status'] ?>" title="Active" id="<?php echo $row['client_id'] ?>"><i class="fa fa-times"></i></a>

                                                        <?php
                                                        }
                                                        ?>

                                                        <!--- <a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php echo $row['client_id'] ?>"><i class="fa fa-trash-o"></i></a> -->
                                                        
                                                        <?php if ($row['invoice_frequency'] == '' || $row['invoice_frequency'] == '0') { ?>
                                                            <a title="Set Frequency" class="table-actions btn-edit cancel_booking" href="#cancel_grn_popup" id="<?php echo $row['client_id']; ?>" data-toggle="modal" data-frequency=<?php echo $row['invoice_frequency']; ?>><i class="fa fa-clock-o"></i></a>
                                                        <?php
                                                    } else {
                                                        ?>
                                                            <a title="Update Frequency" class="table-actions btn-edit cancel_booking" style="color:#2299ff;" href="#cancel_grn_popup" id="<?php echo $row['client_id']; ?>" data-toggle="modal" data-frequency=<?php echo $row['invoice_frequency']; ?>><i class="fa fa-clock-o"></i></a>

                                                        <?php } ?>
                                                        <?php
                                                        if ($row['invoice_status'] == '') {
                                                            ?>
                                                            <a title="Restricted" class="table-actions btn_active1 " data-status="<?php echo $row['invoice_status']; ?>" id="<?php echo $row['client_id'] ?>"><i class="fa fa-ban"></i></a>
                                                        <?php
                                                        } else {
                                                            ?>
                                                            <a title="UnRestrict" class="table-actions btn_active1 " style="color:red;" data-status="<?php echo $row['invoice_status']; ?>" id="<?php echo $row['client_id'] ?>"><i class="fa fa-ban"></i></a>

                                                        <?php } ?>
                                                    </div>
                                                <?php
                                                }
                                                ?>

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

                //Frequency Start

                $(document).on('click', '.cancel_booking', function() {
                    //alert("test");
                    var user_id = '<?php echo $logged_id; ?>';
                    var client_id = $(this).attr('id');
                    var last_freq = $(this).data('frequency');
                    if (last_freq == null) {
                        $("#last_val").val('');

                    } else if (last_freq == '0') {
                        $("#last_val").val(last_freq);
                        $('.check').each(function() {
                            var currentElement = $(this);
                            var value = currentElement.val();
                            if (value == last_freq) {
                                $(this).attr("checked", "checked");
                            } else {
                                $(this).attr("checked", false);
                            }
                        });
                    } else {
                        $("#last_val").val(last_freq);
                        $('.check').each(function() {
                            var currentElement = $(this);
                            var value = currentElement.val();
                            if (value == last_freq) {
                                $(this).attr("checked", "checked");
                            } else {
                                $(this).attr("checked", false);
                            }
                        });
                    }
                    $("#logged_id").val(user_id);
                    $("#client_id").val(client_id);
                    $("#cancel_grn").show();

                });




                //close pop

                $(document).on('click', '.close_booking_cancel', function() {
                    $("#cancel_grn_popup").modal('hide');
                    $("#set_frequency_form").trigger('reset');

                });

                //end close pop

                //Checkbox Value
                $('.check').on('change', function() {
                    $('input[name="' + this.name + '"]').not(this).prop('checked', false);
                    var check = $(this).val();
                    $('.checked_value').val(check);
                });


                //Save Frequency

                $(document).on('click', '#save_cancel_booking', function() {

                    var checkbox_val = $(".checked_value").val();
                    if (checkbox_val == '') {
                        var message = 'Please Select Frequency';
                        var show = $('#error_msg').show();
                        var display_msg = $('#error_msg').html(message);

                    } else {
                        if (!confirm("Client recieve invoices as per frequency")) {
                            return false;
                        }
                        var show = $('#error_msg').hide();
                        $("#cancel_grn_popup").modal('hide');
                        $(".form-data-saving").show();
                        var form = $("#set_frequency_form");
                        $.ajax({
                            url: "save_details.php",
                            type: "post",
                            data: form.serialize(),
                            success: function(response) {
                                console.log(response);
                                if (response == 1) {
                                    $(".form-data-saving").hide();
                                    $("#alert-status").text("");
                                    $("#alert-message").text("Frequency Updated Successfully, please wait until page refresh");
                                    $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                        $("#alert-container").hide();
                                        $("#alert-container").removeClass("alert-success");
                                        location.reload();
                                    });

                                } else {
                                    $(".form-data-saving").hide();
                                    $("#cancel_grn_popup").modal('hide');
                                    $("#alert-status").text("");
                                    $("#alert-message").text("Frequency Update Failed! Try Again");
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

                //Duplication
                var dup_chk = true;

                function duplicate_check() {
                    var department_name = $("#department_name").val();
                    var edit_id = $("#edit_id").val();
                    $.ajax({
                        cache: false,
                        url: 'check_existing.php', // url where to submit the request
                        type: "GET", //type of action POST || GET
                        dataType: 'json', // data type
                        async: false,
                        data: {
                            cmd: "chk_department",
                            department_name: department_name,
                            edit_id: edit_id
                        }, // post data || get data
                        success: function(result) {
                            $(".form-data-saving").hide();
                            dup_chk = true;
                            console.log(result);
                            if (result[0] == 1) {
                                $(".dup-check").html(result[1]).css("color", "#f00");
                                dup_chk = false;
                            } else {
                                $(".dup-check").html(result[1]).css("color", "green");
                            }
                        },
                        error: function(jqxhr) {
                            console.log(jqxhr.responseText);
                        }
                    });
                }


                $(document).on('click', '.close-popup', function() {
                    $(".form-data-saving").hide();
                    $("#alert-status").text("");
                    $("#alert-message").text("Saved Successfully please wait until page refresh");
                    $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                        $("#alert-container").hide();
                        $("#alert-container").removeClass("alert-success");
                        location.reload();
                    });
                });
                //Button Delete
                // $(document).on('click', '.btn-trash', function(ev) { // status removed because it is checking client code for only active clients it leads duplicate client code problem
                //     var del_id = $(this).attr("id");
                //     $(".btn-confirm-delete").attr("id", del_id);
                // });
                $(document).on('click', '.delete-error-popup-close', function(ev) {
                    $(".delete-error-popup").hide();
                });
                // $(document).on('click', '.btn-confirm-delete', function(ev) {
                //     $(".form-data-saving").show();
                //     $.post('save_details.php', {
                //         form_name: "del_client",
                //         tbl_id: $(this).attr("id")
                //     }, function(data, status) {
                //         console.log(data);
                //         if (data == 1) {
                //             $(".form-data-saving").hide();
                //             $("#alert-status").text("");
                //             $("#alert-message").text("Client Deleted successfully...");
                //             $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                //                 $("#alert-container").hide();
                //                 $("#alert-container").removeClass("alert-success");
                //                 location.reload();
                //             });
                //         } else if (data == "404-del") {
                //             $(".delete-error-popup").show();
                //             $(".form-data-saving").hide();
                //         } else {
                //             $(".form-data-saving").hide();
                //             $("#alert-status").text("Alert !!! ");
                //             $("#alert-message").text("Client deletion failed");
                //             $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                //                 $("#alert-container").hide();
                //                 $("#alert-container").removeClass("alert-danger");
                //             });
                //         }
                //     });
                // });
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


                //Restrict Consignor For Invoice
                $(document).on('click', '.btn_active1', function(ev) {
                    //alert("test");
                    if (confirm('Are you sure ?')) {
                        $(".form-data-saving").show();
                        var status1 = '';
                        var msg = '';
                        var status = $(this).attr('data-status');

                        if (status == '') {

                            status1 = '1';
                            msg = 'Restricted';
                            //alert("empty");
                        } else {
                            status1 = '';
                            msg = 'Not Restricted';
                            //alert("not empty");

                        }

                        $.post('save_details.php', {
                                form_name: "restrict_inv_client",
                                tbl_id: $(this).attr("id"),
                                status: status1
                            },
                            function(data, status) {
                                console.log(data);
                                if (data == 1) {


                                    $(".form-data-saving").hide();
                                    $("#alert-status").text("");
                                    $("#alert-message").text("Client Invoice Is " + msg + "...");
                                    $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                        $("#alert-container").hide();
                                        $("#alert-container").removeClass("alert-success");


                                        //location.reload();
                                    });
                                } else {
                                    $(".form-data-saving").hide();
                                    $("#alert-status").text("");
                                    $("#alert-message").text("Client Invoice Is " + msg + "...");
                                    $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                        $("#alert-container").hide();
                                        $("#alert-container").removeClass("alert-danger");
                                        location.reload();
                                    });
                                }
                                // 	else if(data == "404-del"){
                                // 		$(".delete-error-popup").show();
                                // 		$(".form-data-saving").hide();
                                // 	}

                            });
                    } else {
                        ewToast('Restrict Icon Not Clicked', 'info');
                    }
                });

                //End

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


                //Button Reset
                $(document).on('click', '.btn-reset', function(ev) {
                    $('#form_name').val('add_branch');
                    $('#edit_id').val('');
                    $('#department_name').val('');
                    $('#department_code').val('');
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

        <!--- status removed because it is checking client code for only active clients it leads duplicate client code problem -->
        <!--- <div class="modal fade popup_close" id="myModal">
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
        </div> -->

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


        <!--- Insert Invoice Frequency -->
        <div class="modal fade " id="cancel_grn_popup" style="display:none">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                        <h4 class="modal-title" style="color:#fff">
                            Invoice Frequency
                        </h4>
                    </div>

                    <div class="modal-body" style="display: none" id="cancel_grn">
                        <form id="set_frequency_form" enctype="multipart/form-data">
                            <input type="hidden" name="" id="last_val">
                            <input type="hidden" name="form_name" value="set_invoice_frequency">
                            <input type="hidden" name="logged_id" id="logged_id" value="">
                            <input type="hidden" name="client_id" id="client_id" value="">

                            <label class="control-label">Frequency:</label>
                            <br />
                            <div class="checkbox-inline">
                                <input type="checkbox" name="time_interval" class="check" value="0"> Default

                            </div>
                            <div class="checkbox-inline">
                                <input type="checkbox" name="time_interval" class="check" value="2">2 Days

                            </div>
                            <div class="checkbox-inline">
                                <input type="checkbox" name="time_interval" class="check" value="15">15 Days

                            </div>
                            <div class="checkbox-inline">
                                <input type="checkbox" name="time_interval" class="check" value="30">30 Days

                            </div>
                            <div class="checkbox-inline">
                                <input type="checkbox" name="time_interval" class="check" value="45">45 Days

                            </div>
                            <div class="checkbox-inline">
                                <input type="checkbox" name="time_interval" class="check" value="60">60 Days

                            </div>

                            <input type="hidden" name="checked_value" class="checked_value" value="">
                            <small name="error_msg" id="error_msg" style="display:none; color:red;"></small>
                            <br>
                            <div class="modal-footer" style="text-align: center;">
                                <button class="btn btn-danger btn-cancel close_booking_cancel" type="button">Cancel</button>
                                <button class="btn btn-primary btn-submit" type="button" id="save_cancel_booking">Submit</button>
                            </div>
                    </div>

                </div>
                </form>

            </div>
        </div>
        <!--- End-->


</body>

</html>