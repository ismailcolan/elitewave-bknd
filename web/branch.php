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
                <div class="col-md-12">
                    <div class="ew-page-v2">
                        <div class="ew-page-head">
                            <div class="ew-page-head-left">
                                <h1 class="ew-page-title">Branch Master</h1>
                            </div>
                        </div>
                        <div class="ew-card">
                            <div class="ew-card-toolbar">
                                <h2>Branch List</h2>
                                <div class="ew-toolbar-right">
                                    <button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="openCreateBranch">
                                        Create <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="ew-table-wrap widget-content padded clearfix new_dept">
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
                dup_chk = true;
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
            $('#openCreateBranch').on('click', function() {
                $('#form_name').val('add_branch');
                $('#edit_id').val('');
                $('#form_data')[0].reset();
                $('#city').html('<option value="">Select City</option>');
                $('#branch_code').prop('readonly', true);
                $.getJSON('fetch_details.php', { cmd: 'get_branch_next_code' }, function(data) {
                    $('#branch_code').val(data && data.branch_code ? data.branch_code : '');
                });
                $('#branchModalTitle').text('Create Branch');
                ewV2OpenModal('branchModal');
            });

            //button Save
            $(document).on('click', '#save', function() {
                var $saveBtn = $(this);
                var data = $('#form_data').serialize();
                duplicate_check();
                if ($('#form_data').valid() == true && dup_chk) {
                    $saveBtn.prop('disabled', true);
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
                                $saveBtn.prop('disabled', false);
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
                            $saveBtn.prop('disabled', false);
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
                        if (!result || !result.branch_id) {
                            if (typeof ewToast === 'function') {
                                ewToast('Could not load branch details.', 'error');
                            }
                            return;
                        }
                        $("#form_name").val("edit_branch");
                        $("#edit_id").val(result.branch_id);
                        $("#branch_code").val(result.branch_code).prop('readonly', false);
                        $('#branch_name').val(result.branch_name);
                        $('#contact_person').val(result.contact_person);
                        $('#contact_no').val(result.contact_no);
                        $('#address1').val(result.address1);
                        $('#address2').val(result.address2 || '');
                        $('#pincode').val(result.pincode);
                        $('#email').val(result.email);
                        var stateId = result.state;
                        var cityId = result.city;
                        $('#state').val(stateId);
                        $.ajax({
                            url: 'fetch_details.php',
                            type: 'post',
                            data: { cmd: 'get_city_name', state_id: stateId },
                            success: function(cityHtml) {
                                $('#city').html(cityHtml);
                                $('#city').val(cityId);
                            }
                        });
                        $('#branchModalTitle').text('Edit Branch');
                        ewV2OpenModal('branchModal');
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
                $('#form_data')[0].reset();
                $('#city').html('<option value="">Select City</option>');
                ewV2CloseModal('branchModal');
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

    <div class="ew-v2-modal-backdrop" id="branchModal">
        <div class="ew-v2-modal ew-v2-modal--lg">
            <div class="ew-v2-modal-head">
                <h3 id="branchModalTitle">Create Branch</h3>
                <button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
            </div>
            <div class="ew-v2-modal-body">
                <form id="form_data">
                    <input type="hidden" id="form_name" name="form_name" value="add_branch">
                    <input type="hidden" id="edit_id" name="edit_id" value="">
                    <div id="response" class="alert alert-danger" style="display:none;">
                        <div class="message" style="text-align:center"></div>
                    </div>
                    <div class="ew-form-grid">
                        <div class="ew-field">
                            <label class="control-label">Branch Code <span style="color:red;">*</span> :</label>
                            <input type="text" id="branch_code" name="branch_code" class="form-control" required autocomplete="off" readonly />
                        </div>
                        <div class="ew-field">
                            <label class="control-label">Branch Name <span style="color:red;">*</span> :</label>
                            <input type="text" name="branch_name" id="branch_name" class="form-control" required autocomplete="off" />
                        </div>
                        <div class="ew-field">
                            <label class="control-label">Contact Person <span style="color:red;">*</span> :</label>
                            <input type="text" id="contact_person" name="contact_person" class="form-control" required autocomplete="off"/>
                        </div>
                        <div class="ew-field">
                            <label class="control-label">Contact No <span style="color:red;">*</span> :</label>
                            <input type="text" pattern="\d{10}" maxlength="10" minlength="10" name="contact_no" id="contact_no" class="form-control" required onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return ewNumericPaste(event,this);" autocomplete="off" />
                        </div>
                        <div class="ew-field span-2">
                            <label class="control-label">Address 1 <span style="color:red;">*</span> :</label>
                            <input type="text" id="address1" name="address1" class="form-control" required autocomplete="off"/>
                        </div>
                        <div class="ew-field span-2">
                            <label class="control-label">Address 2 :</label>
                            <input type="text" name="address2" id="address2" class="form-control" autocomplete="off"/>
                        </div>
                        <div class="ew-field">
                            <label class="control-label">State <span style="color:red;">*</span> :</label>
                            <select name="state" id="state" class="form-control" required>
                                <option value="">Select State</option>
                                <?php
                                $state_query = 'select * from state where status=0 order by state_name';
                                $state_result = mysqli_query($conn, $state_query);
                                while ($state_row = mysqli_fetch_array($state_result)) {
                                    ?>
                                    <option value="<?php echo $state_row['state_id']; ?>"><?php echo $state_row['state_name']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="ew-field">
                            <label class="control-label">City <span style="color:red;">*</span> :</label>
                            <select name="city" id="city" class="form-control" required>
                                <option value="">Select City</option>
                                <?php
                                $city_query = 'select * from city where status=0';
                                $city_result = mysqli_query($conn, $city_query);
                                while ($city_row = mysqli_fetch_array($city_result)) {
                                    ?>
                                    <option value="<?php echo $city_row['city_id']; ?>"><?php echo $city_row['city_name']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="ew-field">
                            <label class="control-label">Pincode <span style="color:red;">*</span> :</label>
                            <input type="text" name="pincode" id="pincode" minlength="6" maxlength="6" class="form-control" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return ewNumericPaste(event,this);" autocomplete="off"/>
                        </div>
                        <div class="ew-field">
                            <label class="control-label">Email <span style="color:red;">*</span> :</label>
                            <input type="email" name="email" id="email" class="form-control" required autocomplete="off" />
                        </div>
                    </div>
                </form>
            </div>
            <div class="ew-v2-modal-foot">
                <button type="button" class="btn btn-default-outline btn-reset" data-ew-v2-close>Cancel</button>
                <button class="btn btn-primary" type="button" id="save">Submit</button>
            </div>
        </div>
    </div>

</body>

</html>