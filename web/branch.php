<?php
require_once ('include/connect.php');
require_once ('include/function.php');
?>
<!DOCTYPE html>
<html>

<head>
    <?php include ('include/title.php'); ?>
    <?php include ('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <style>
        #contact_no:invalid {
            color: red;
        }

        .dataTable th.sorting:before,
        .dataTable th.sorting_asc:after {
            top: 10px;
            right: 3px;
        }

        .dataTable th.sorting:after,
        .dataTable th.sorting_desc:after {
            top: 17px;
            right: 3px;
        }
        @media (min-width: 360px) and (max-width: 575.98px) {  
   .widget-container .widget-content {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
}
.branch_tble{
	margin: 0 auto;
	width: max-content!important;
    max-width: unset!important;

    clear: both;
    border-collapse: collapse;
    table-layout: fixed;
} 
th.table-title.sorting  {
    width: 135px!important;
}
 th.table-title.sorting_disabled {
    width: 51px!important;
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
                        <div class="heading"> <i class="fa fa-plus"></i>Branch </div>

                        <div class="widget-content padded">
                            <form class="form-horizontal" id="form_data">

                                <input type="hidden" id="form_name" name="form_name" value="add_branch">
                                <input type="hidden" id="edit_id" name="edit_id" value="">

                                <div id="response" class="alert alert-danger" style="display:none;">
                                    <div class="message" style="text-align:center"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-offset-1 col-md-5">
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Branch Code <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <input type="text" id="branch_code" name="branch_code" class="form-control" required autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Branch Name <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <input type="text" name="branch_name" id="branch_name" class="form-control" required autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Contact Person <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <input type="text" id="contact_person" name="contact_person" class="form-control" required  autocomplete="off"/>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Contact No <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <input type="text" pattern="\d{10}" maxlength=10  minlength=10 name="contact_no" id="contact_no" class="form-control" required onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Address 1 <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <input type="text" id="address1" name="address1" class="form-control" required autocomplete="off"/>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Address 2:</label>
                                            <div class="col-lg-8">
                                                <input type="text" name="address2" id="address2" class="form-control" autocomplete="off"/>
                                            </div>

                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">State <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <select name="state" id="state" class="form-control" required>
                                                    <option value="">Select State</option>
                                                    <?php
                                                    $state_query = 'select * from state where status=0 order by state_name';
                                                    $state_result = mysqli_query($conn, $state_query);
                                                    while ($state_row = mysqli_fetch_array($state_result)) {
                                                        ?>
                                                        <option value="<?php echo $state_row['state_id']; ?>"><?php echo $state_row['state_name']; ?></option>
                                                    <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">City <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <select name="city" id="city" class="form-control" required>
                                                    <option value="">Select City</option>
                                                    <?php
                                                    $city_query = 'select * from city where status=0';
                                                    $city_result = mysqli_query($conn, $city_query);
                                                    while ($city_row = mysqli_fetch_array($city_result)) {
                                                        ?>
                                                        <option value="<?php echo $city_row['city_id']; ?>"><?php echo $city_row['city_name']; ?></option>
                                                    <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Pincode <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
									        <input type="text" name="pincode" id="pincode"  minlength=6  maxlength=6  class="form-control" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off"/>
                                               
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Email <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <input type="email" name="email" id="email" class="form-control" required autocomplete="off" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div><br />
                        <div class="row">
                            <div class="col-md-12 form-action">
                                <button class="btn btn-primary" type="button" id="save">Submit</button>
                                <a class="btn btn-default-outline  btn-reset" type="button" href="dashboard.php">Cancel</a>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-offset-1 col-md-10">

                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"> <i class="fa fa-table"></i> List of Branch </div>
                        <div class="widget-content padded clearfix new_dept">
                            <table class="table table-bordered table-striped branch_tble" id="dataTable1">
                                <thead>
                                    <th class="table-title" style="width:10%">S.No</th>
                                    <th class="table-title" style="width:10%">Branch Code</th>
                                    <th class="table-title" style="width:30%">Branch Name</th>
                                    <th class="table-title" style="width:20%">Contact Person</th>
                                    <th class="table-title" style="width:10%">Action</th>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = 'select * from branch';
                                    $result = mysqli_query($conn, $query);
                                    $i = 1;
                                    while ($row = mysqli_fetch_array($result)) {
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $i; ?></td>
                                            <td><?php echo $row['branch_code']; ?></td>
                                            <td><?php echo $row['branch_name']; ?></td>
                                            <td><?php echo $row['contact_person'] ?></td>

                                            <td class="actions center-content ">
                                                <div class="action-buttons">
                                                    <a title="Edit" class="table-actions btn-edit" id="<?php echo $row['branch_id']; ?>"><i class="fa fa-pencil"></i></a>
                                                    <?php
                                                    if ($row['status'] == 0) {
                                                        ?>
                                                        <a class="table-actions btn-active" data-status="<?php echo $row['status'] ?>" title="InActive" id="<?php echo $row['branch_id'] ?>"><i class="fa fa-check"></i></a>
                                                    <?php
                                                    } else {
                                                        ?>
                                                        <a class="table-actions btn-active" style="color:red;" data-status="<?php echo $row['status'] ?>" title="Active" id="<?php echo $row['branch_id'] ?>"><i class="fa fa-times"></i></a>
                                                    <?php
                                                    }
                                                    ?>
                                                    <a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php echo $row['branch_id'] ?>"><i class="fa fa-trash-o"></i></a>

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


        <?php require_once ('include/footer.php'); ?>
    </div>


    <script type="text/javascript">
        $(document).ready(function() {

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

            $(document).on('change', '#state', function() {
                var state_id = $(this).val();
                //alert(state_id);
                $.ajax({
                    url: 'fetch_details.php',
                    type: "post",
                    data: {
                        cmd: "get_city_name",
                        state_id: state_id
                    },
                    success: function(result) {
                        console.log(result);
                        $('#city').html(result);
                    }
                });
            });
            //button Save
            $(document).on('click', '#save', function() {
                var data = $('#form_data').serialize();
                duplicate_check();
                if ($('#form_data').valid() == true && dup_chk) {
                    $(this).attr("disabled", true);
                    $.ajax({
                        url: "save_details.php",
                        type: "post",
                        data: data,
                        success: function(result) {
                            console.log(result);
                            if (result == 1) {
                                $(".form-data-saving").hide();
                                $("#alert-status").text("");
                                $("#alert-message").text("Saved Successfully please wait until page refresh");
                                $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-success");
                                    location.reload();
                                });
                            } else {
                                $(".form-data-saving").hide();
                                $("#alert-status").text("Alert !!! ");
                                $("#alert-message").text("Data Saving Failed");
                                $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-danger");
                                });
                            }
                        },
                        error: function(jqxhr) {
                            console.log(jqxhr.responseText);
                        }
                    });
                }
            });
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
            $(document).on('click', '.btn-trash', function(ev) {
                var del_id = $(this).attr("id");
                $(".btn-confirm-delete").attr("id", del_id);
            });
            $(document).on('click', '.delete-error-popup-close', function(ev) {
                $(".delete-error-popup").hide();
            });
            $(document).on('click', '.btn-confirm-delete', function(ev) {
                $(".form-data-saving").show();
                $.post('save_details.php', {
                    form_name: "del_branch",
                    tbl_id: $(this).attr("id")
                }, function(data, status) {
                    console.log(data);
                    if (data == 1) {
                        $(".form-data-saving").hide();
                        $("#alert-status").text("");
                        $("#alert-message").text("Branch Deleted successfully...");
                        $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                            $("#alert-container").hide();
                            $("#alert-container").removeClass("alert-success");
                            location.reload();
                        });
                    } else if (data == "404-del") {
                        $(".delete-error-popup").show();
                        $(".form-data-saving").hide();
                    } else {
                        $(".form-data-saving").hide();
                        $("#alert-status").text("Alert !!! ");
                        $("#alert-message").text("Branch deletion failed");
                        $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                            $("#alert-container").hide();
                            $("#alert-container").removeClass("alert-danger");
                        });
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
                    form_name: "inacv_branch",
                    tbl_id: $(this).attr("id"),
                    status: status1
                }, function(data, status) {
                    console.log(data);
                    if (data == 1) {
                        $(".form-data-saving").hide();
                        $("#alert-status").text("");
                        $("#alert-message").text("Branch Is " + msg + "...");
                        $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                            $("#alert-container").hide();
                            $("#alert-container").removeClass("alert-success");
                            location.reload();
                        });
                    } else if (data == 2) {
                        $(".form-data-saving").hide();
                        $("#alert-status").text("");
                        $("#alert-message").text("Branch Is " + msg + "...");
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
                        $("#branch_code").val(result['branch_code']);
                        $('#branch_name').val(result['branch_name']);
                        $('#contact_person').val(result['contact_person']);
                        $('#contact_no').val(result['contact_no']);
                        $('#address1').val(result['address1']);
                        $('#address2').val(result['address2']);
                        $('#city').val(result['city']);
                        $('#state').val(result['state']);
                        $('#pincode').val(result['pincode']);
                        $('#email').val(result['email']);

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

</body>

</html>