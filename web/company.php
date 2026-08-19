<?php require_once 'include/connect.php'; ?>
<!DOCTYPE html>
<html>

<head>
    <?php require_once ('include/title.php'); ?>
    <?php require_once ('include/css_js.php'); ?>
    <?php require_once ('include/function.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <style>
        div.alert#alert-container,
        div.alert#alert-container1 {
            position: fixed;
            z-index: 19999;
            top: 0px;
            width: 100%;
            left: 0px;
            text-align: center;
        }

        img#image_preview {
            height: 93px;
            width: 98px;
        }

        img#image_preview1 {
            height: 93px;
            width: 98px;
        }

        #mobile_no:invalid {
            color: red !important;
        }
    </style>
</head>

<body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
        <!-- Navigation -->
        <div class="navbar navbar-fixed-top scroll-hide">
            <?php require_once ('include/header.php'); ?>
            <?php require_once ('include/menu.php'); ?>

        </div>
        <!-- End Navigation -->
        <div class="container-fluid main-content new_dpt_bottom">
            <!-- <div class="row">
                <div class="col-lg-12">
                    <h1 class="text-2xl font-bold text-black mb-2">Company Information</h1>
                </div>
            </div> -->
            <br/>

            <div class="row">
                <div class="col-md-offset-1 col-md-10">
                    <div class="widget-container fluid-height clearfix" style="border-radius:5px">
                        <div class="heading"> <i class="fa fa-user"></i> Company Info</div>
                        <div class="widget-content padded">

                            <form class="form-horizontal" id="company_form" method="post" enctype="multipart/form-data">
                                <?php
                                $query = 'select * from company where status=0 limit 1';
                                $result = mysqli_query($conn, $query);
                                $row = mysqli_fetch_array($result);

                                $state = $row['state'];
                                $city = $row['city'];

                                if ($row['company_id'] != '') {
                                    ?>
                                    <input type="hidden" id="form_name" name="form_name" value="edit_company">
                                <?php
                                } else {
                                    ?>
                                    <input type="hidden" id="form_name" name="form_name" value="add_company">
                                <?php
                                }
                                ?>
                                <input id="attach_type" name="logo_attach_type" type="hidden" value=<?php echo $row['logo']; ?> />
                                <input id="attach_type" name="flag_attach_type" type="hidden" value=<?php echo $row['flag']; ?> />

                                <input type="hidden" id="edit_id" name="edit_id" value="<?php echo $row['company_id']; ?>" />
                                <input type="hidden" id="comapny_id" name="company_id" value="<?php echo $row['company_id'] ?>" />

                                <div id="response" class="alert alert-danger" style="display:none;">
                                    <div class="message" style="text-align:center"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-offset-1 col-md-5">
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Company Code <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <input class="form-control" type="text" name="comp_code" value="<?php echo $row['company_code']; ?>" id="comp_code" required />
                                            </div>

                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Company Name <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <input class="form-control cust" type="text" name="comp_name" value="<?php echo $row['company_name']; ?>" id="comp_name" required />
                                            </div>

                                        </div>

                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Contact Person <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <input class="form-control" type="text" name="contact_person" value="<?php echo $row['contact_person']; ?>" id="contact_person" required />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Address1 <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <input class="form-control" type="text" name="address1" id="address1" value="<?php echo $row['address1']; ?>" required />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Address2:</label>
                                            <div class="col-lg-8">
                                                <input type="text" name="address2" id="address2" value="<?php echo $row['address2']; ?>" class="form-control" />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">PAN No:</label>
                                            <div class="col-lg-8">
                                                <input class="form-control" type="text" name="pan_no" value="<?php echo $row['pan_no']; ?>" id="pan_no" />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Status Autochange Hours:</label>
                                            <div class="col-lg-8">
                                                <input class="form-control" type="text" name="autochange_hours" value="<?php echo $row['company_code']; ?>" id="autochange_hours" />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Logo:</label>
                                            <div class="file-container">
                                                <div class="col-sm-12 file-group" id="file-no1" data-file-no="1">
                                                    <div class="col-sm-5">
                                                        <input type="file" id="logo" name="logo" class="filestyle" data-id="1" data-buttonbefore="true" data-buttonname="btn-primary">
                                                    </div>

                                                    <div class="col-sm-3">
                                                        <?php
                                                        if ($row['logo'] != '') {
                                                            $logo = $row['logo'];
                                                        } else {
                                                            $logo = 'no_image.png';
                                                        }
                                                        ?>
                                                        <img src="images/<?php echo $logo; ?>" class="image_preview" id="image_preview">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">

                                        <div class="form-group">
                                            <label class="control-label col-sm-4">State <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <select name="state" id="state" class="form-control">
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
                                                <select name="city" id="city" class="form-control">
                                                    <option value="">Select City</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="control-label col-sm-4">PinCode:</label>
                                            <div class="col-lg-8">
                                            <input class="form-control" minlength=6  maxlength=6 type="text" name="pincode"  id="pincode" value="<?php echo $row['pincode']; ?>" required  inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;"  autocomplete="off"/>

                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Mobile No <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                            <input class="form-control"  pattern="\d{10}" minlength=10 maxlength=10  type="text" name="mobile_no"  id="mobile_no" value="<?php echo $row['mobile_no']; ?>" required   inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="form-group">

                                            <label class="control-label col-sm-4">E-Mail <span style="color:red;">*</span> :</label>
                                            <div class="col-lg-8">
                                                <input class="form-control" type="email" name="email" id="email" value="<?php echo $row['email']; ?>" required />
                                            </div>
                                        </div>
                                        <div class="form-group">
    <label class="control-label col-sm-4">GST IN:</label>
    <div class="col-lg-8">
        <input class="form-control" type="text" name="gst_no" id="gst_no" value="<?php echo $row['gst_no']; ?>" />
    </div>
</div>
<div class="form-group">
    <label class="control-label col-sm-4">GRN Mode:</label>
    <div class="col-lg-8">
        <label style="margin-right:15px;"><input type="checkbox" name="grn_mode" value="client" id="grn_client" <?php echo ($row['grn_mode'] == 'client') ? 'checked' : ''; ?> /> Client Wise</label>
        <label><input type="checkbox" name="grn_mode" value="company" id="grn_company" <?php echo ($row['grn_mode'] == 'company' || $row['grn_mode'] == '') ? 'checked' : ''; ?> /> Company Wise</label>
    </div>
</div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Flag:</label>
                                            <div class="file-container">
                                                <div class="col-sm-12 file-group" id="file-no1" data-file-no="1">
                                                    <div class="col-sm-5">
                                                        <input type="file" id="flag" name="flag" class="filestyle1" data-id="1" data-buttonbefore="true" data-buttonname="btn-primary">
                                                    </div>
                                                    <?php
                                                    if ($row['flag'] != '') {
                                                        $flag = $row['flag'];
                                                    } else {
                                                        $flag = 'no_image.png';
                                                    }
                                                    ?>
                                                    <div class="col-sm-3">
                                                        <img src="images/<?php echo $flag; ?>" class="image_preview" id="image_preview1">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                        </div>
                        <div class="col-md-12 form-action">
                            <button class="btn btn-primary " type="button" id="save">Submit</button>

                        </div>
                        </form>

                    </div>

                </div>

            </div>
        </div>

        <?php require_once ('include/footer.php'); ?>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {

            var state = '<?php echo $state; ?>';
            var city = '<?php echo $city; ?>';
            if (state != '') {
                $("#state").val(state).trigger('change');
                select(state);

            }

            $(document).on('change', 'input[name="grn_mode"]', function() {
                if ($(this).is(':checked')) {
                    $('input[name="grn_mode"]').not(this).prop('checked', false);
                } else {
                    $(this).prop('checked', true);
                }
            });

            $(document).on('click', '#save', function() {
                if ($('#company_form').valid() == true) {
                    var formData = new FormData(document.getElementById("company_form"));
                    $.ajax({
                        url: "save_details.php",
                        type: "post",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(result) {
                            console.log(result);
                            // alert(result);
                            if (result == 1) {
                                $(".form-data-saving").hide();
                                //alert("Data: " + data + "\nStatus: " + status);
                                $("#alert-status").text("");
                                $("#alert-message").text("Saved Successfully.! Please Wait Until Page Refresh.!!");
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
                        }
                    });
                }
            });

            function select(state_id) {
                $.ajax({
                    url: 'fetch_details.php',
                    type: "post",
                    asyn: false,
                    data: {
                        cmd: "get_city_name",
                        state_id: state_id
                    },
                    success: function(result) {
                        // console.log(result);
                    // alert(result);
                        $('#city').html(result);
                        if (city != '')
                            $("#city").val(city);
                    }
                });
            }

            $(document).on('change', '#state', function() {
                var state_id = $(this).val();
                //alert(state_id);
                select(state_id);

            });

            function readURL(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function(e) {
                        $('#image_preview').attr('src', e.target.result);
                    }

                    reader.readAsDataURL(input.files[0]);
                }
            }

            $(document).on('change', '.filestyle', function() {
                //var id = $(this).attr("data-id");
                readURL(this);
            });

            function readURL1(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function(e) {
                        $('#image_preview1').attr('src', e.target.result);
                    }

                    reader.readAsDataURL(input.files[0]);
                }
            }

            $(document).on('change', '.filestyle1', function() {
                //var id = $(this).attr("data-id");
                readURL1(this);
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
                This User Cannot Delete.Used by another record. so you can't Delete !!! <br /> &nbsp; <br />
                <button class="btn btn-sm btn-danger delete-error-popup-close" id="">Close</button> <br /> &nbsp; <br />
            </div>
            <!--<span class="popup_close" id="popup_close">X</span>-->
        </div>
    </div>

</body>

</html>