<?php
require_once("include/connect.php");
require_once("include/function.php");
$key = $_REQUEST['key'];
if ($key != '') {
    $client_query = "select * from client_branch where md5(client_branch_id)='" . $key . "'";
    $client_result = mysqli_query($conn, $client_query);
    $client_count = mysqli_num_rows($client_result);
    if ($client_count == 0) {
        header('Location:client_branch_list.php');
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <?php include("include/title.php"); ?>
    <?php include("include/css_js.php"); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <style>
        #contact_no:invalid {
            color: red;
        }
    </style>
</head>

<body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
        <!-- Navigation -->
        <div class="navbar navbar-fixed-top scroll-hide">
            <?php
            require_once("include/header.php");
            require_once("include/menu.php");

            ?>

        </div>
        <div class="container-fluid main-content new_dpt_bottom">

            <div class="row">
                <div class="col-md-offset-1 col-md-10">
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"> <i class="fa fa-plus"></i>Client Branch <span class="align-right"> <i class="fa fa-table"></i><a href="client_branch_list.php">View List</a></span></div>

                        <div class="widget-content padded">
                            <form class="form-horizontal" id="client_branch_form">
                                <?php
                                $client_branch_query = "select * from client_branch where md5(client_branch_id)='" . $key . "'";
                                $client_branch_result = mysqli_query($conn, $client_branch_query);
                                $client_branch_row = mysqli_fetch_array($client_branch_result);
                                ?>
                                <input type="hidden" id="form_name" name="form_name" value="add_client_branch">
                                <input type="hidden" id="edit_id" name="edit_id" value="<?php echo $key; ?>">

                                <div id="response" class="alert alert-danger" style="display:none;">
                                    <div class="message" style="text-align:center"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-offset-1 col-md-5">
                                        <div class="form-group">
                                            <label class="control-label">Select Client Company <span style="color:red;">*</span> :</label>
                                            <select type="text" id="company_id" name="company_id" class="form-control" required>
                                                <option value="">-- Select Company --</option>
                                                <?php
                                                $clientcom_query = "select * from client where status='0' order by client_company_name";
                                                $clientc_result = mysqli_query($conn, $clientcom_query);
                                                while ($client_com_r = mysqli_fetch_array($clientc_result)) {
                                                    echo '<option value="' . $client_com_r['client_id'] . '" >' . $client_com_r['client_company_name'] . '</option>';
                                                }


                                                ?>

                                            </select>
                                         
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Branch Name <span style="color:red;">*</span> :</label>
                                            <input type="text" name="branch_name" id="branch_name" value="<?php echo $client_branch_row['branch_name'];  ?>" class="form-control" required autocomplete="off" />
                                            <span class="dup-check"></span>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Branch Contact Person <span style="color:red;">*</span> :</label>
                                            <input type="text" class="form-control" value="<?php echo $client_branch_row['branch_contact_person'];  ?>" name="contact_person" id="contact_person" required autocomplete="off">
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Contact No <span style="color:red;">*</span> :</label>
                                            <input type="text" name="contact_no" pattern="\d{10}" minlength=10 maxlength=10 value="<?php echo $client_branch_row['contact_no'];  ?>" id="contact_no" class="form-control" required onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off" />

                                            <span class="dup-check"></span>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Address 1 <span style="color:red;">*</span> :</label>
                                            <input type="text" id="address1" value="<?php echo $client_branch_row['address1'];  ?>" name="address1" class="form-control" required autocomplete="off" />
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label class="control-label">Address 2:</label>
                                            <input type="text" name="address2" id="address2" value="<?php echo $client_branch_row['address2'];  ?>" class="form-control" autocomplete="off" />
                                            <span class="dup-check"></span>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">State <span style="color:red;">*</span> :</label>
                                            <select class="form-control" name="state" id="state" required>
                                                <option value="">Select State</option>
                                                <?php
                                                $state_query = "select * from state where status=0 order by state_name";
                                                $state_result = mysqli_query($conn, $state_query);
                                                while ($state_row = mysqli_fetch_array($state_result)) {
                                                ?>
                                                    <option value="<?php echo $state_row['state_id'] ?>" <?php if ($client_branch_row['state'] == $state_row['state_id']) echo "selected"; ?>><?php echo $state_row['state_name']; ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>

                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">City <span style="color:red;">*</span> :</label>
                                            <select class="form-control" name="city" id="city" required> 
                                                <option value="">Select City</option>
                                                <?php
                                                $city_query = "select * from city where status=0 order by city_name";
                                                $city_result = mysqli_query($conn, $city_query);
                                                while ($city_row = mysqli_fetch_array($city_result)) {
                                                ?>
                                                    <option value="<?php echo $city_row['city_id'] ?>" <?php if ($client_branch_row['city'] == $city_row['city_id']) echo "selected"; ?>><?php echo $city_row['city_name']; ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="control-label">Pincode:</label>
                                            <input type="text" name="pincode" id="pincode" minlength=6  maxlength=6 value="<?php echo $client_branch_row['pincode']; ?>" class="form-control" autocomplete="off" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" />

                                            <span class="dup-check"></span>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Email <span style="color:red;">*</span> :</label>
                                            <input type="email" name="email" id="email" value="<?php echo $client_branch_row['email']; ?>" class="form-control" required autocomplete="off" />
                                            <span class="dup-check"></span>
                                        </div>

                                    </div>
                                </div><br />
                                <div class="row">
                                    <div class="col-md-12 form-action">
                                        <button class="btn btn-primary" type="button" id="save">Submit</button>
                                        <a class="btn btn-default-outline  btn-reset" type="button" href="client_branch.php">Cancel</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>


            <?php require_once("include/footer.php"); ?>
        </div>


        <script type="text/javascript">
            $(document).ready(function() {

                var edit_id = $("#edit_id").val();
                var company_id = '<?php echo $client_branch_row['company_id'] ?>';

                if (edit_id != "")
                    $("#company_id").val(company_id);

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
                    var data = $('#client_branch_form').serialize();
                    duplicate_check();
                    if ($('#client_branch_form').valid() == true && dup_chk) {
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
                            $("#alert-message").text("Department Deleted successfully...");
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
                            $("#alert-message").text("Department deletion failed");
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
                        form_name: "inacv_client_branch",
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