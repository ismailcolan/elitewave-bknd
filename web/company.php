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

            <div class="row">
                <div class="col-md-12">
                    <div class="ew-page-v2">
                        <div class="ew-page-head">
                            <div class="ew-page-head-left">
                                <h1 class="ew-page-title">Company Information</h1>
                            </div>
                        </div>
                        <div class="ew-card">
                            <h2 class="ew-card-section-title">Company Info</h2>
                            <div class="ew-form-body">
                            <form id="company_form" method="post" enctype="multipart/form-data">
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

                                <div class="ew-form-grid">
                                    <div class="ew-field">
                                        <label class="control-label">Company Code <span style="color:red;">*</span> :</label>
                                        <input class="form-control" type="text" name="comp_code" value="<?php echo htmlspecialchars($row['company_code']); ?>" id="comp_code" required />
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">Company Name <span style="color:red;">*</span> :</label>
                                        <input class="form-control cust" type="text" name="comp_name" value="<?php echo htmlspecialchars($row['company_name']); ?>" id="comp_name" required />
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">Contact Person <span style="color:red;">*</span> :</label>
                                        <input class="form-control" type="text" name="contact_person" value="<?php echo htmlspecialchars($row['contact_person']); ?>" id="contact_person" required />
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">Mobile No <span style="color:red;">*</span> :</label>
                                        <input class="form-control" pattern="\d{10}" minlength="10" maxlength="10" type="text" name="mobile_no" id="mobile_no" value="<?php echo htmlspecialchars($row['mobile_no']); ?>" required inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return ewNumericPaste(event,this);" autocomplete="off" />
                                    </div>
                                    <div class="ew-field span-2">
                                        <label class="control-label">Address1 <span style="color:red;">*</span> :</label>
                                        <input class="form-control" type="text" name="address1" id="address1" value="<?php echo htmlspecialchars($row['address1']); ?>" required />
                                    </div>
                                    <div class="ew-field span-2">
                                        <label class="control-label">Address2 :</label>
                                        <input type="text" name="address2" id="address2" value="<?php echo htmlspecialchars($row['address2']); ?>" class="form-control" />
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
                                        </select>
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">PinCode :</label>
                                        <input class="form-control" minlength="6" maxlength="6" type="text" name="pincode" id="pincode" value="<?php echo htmlspecialchars($row['pincode']); ?>" required inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return ewNumericPaste(event,this);" autocomplete="off"/>
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">E-Mail <span style="color:red;">*</span> :</label>
                                        <input class="form-control" type="email" name="email" id="email" value="<?php echo htmlspecialchars($row['email']); ?>" required />
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">PAN No :</label>
                                        <input class="form-control" type="text" name="pan_no" value="<?php echo htmlspecialchars($row['pan_no']); ?>" id="pan_no" />
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">GST IN :</label>
                                        <input class="form-control" type="text" name="gst_no" id="gst_no" value="<?php echo htmlspecialchars($row['gst_no']); ?>" />
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">Status Autochange Hours :</label>
                                        <input class="form-control" type="text" name="autochange_hours" value="<?php echo htmlspecialchars($row['company_code']); ?>" id="autochange_hours" />
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">GRN Mode :</label>
                                        <div style="display:flex;align-items:center;gap:16px;min-height:38px;">
                                            <label style="margin:0;font-weight:normal;"><input type="checkbox" name="grn_mode" value="client" id="grn_client" <?php echo ($row['grn_mode'] == 'client') ? 'checked' : ''; ?> /> Client Wise</label>
                                            <label style="margin:0;font-weight:normal;"><input type="checkbox" name="grn_mode" value="company" id="grn_company" <?php echo ($row['grn_mode'] == 'company' || $row['grn_mode'] == '') ? 'checked' : ''; ?> /> Company Wise</label>
                                        </div>
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">Logo :</label>
                                        <?php
                                        if ($row['logo'] != '') {
                                            $logo = $row['logo'];
                                        } else {
                                            $logo = 'no_image.png';
                                        }
                                        ?>
                                        <div class="ew-upload-image<?php echo ($row['logo'] != '') ? ' has-file' : ''; ?>">
                                            <div class="ew-upload-image__zone" tabindex="0" role="button" aria-label="Upload logo">
                                                <input type="file" id="logo" name="logo" class="ew-upload-image__input" accept="image/*">
                                                <div class="ew-upload-image__body">
                                                    <i class="fa fa-cloud-upload" aria-hidden="true"></i>
                                                    <span class="ew-upload-image__hint">Drag &amp; drop or browse</span>
                                                    <span class="ew-upload-image__meta">PNG, JPG · max 2MB</span>
                                                    <span class="ew-upload-image__filename" title="<?php echo ($row['logo'] != '') ? htmlspecialchars($row['logo']) : ''; ?>"><?php echo ($row['logo'] != '') ? htmlspecialchars($row['logo']) : ''; ?></span>
                                                    <span class="ew-btn-v2 ew-btn-v2-outline ew-upload-image__browse">Browse</span>
                                                </div>
                                            </div>
                                            <div class="ew-upload-image__preview">
                                                <img src="images/<?php echo htmlspecialchars($logo); ?>" id="image_preview" alt="Logo preview">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">Flag :</label>
                                        <?php
                                        if ($row['flag'] != '') {
                                            $flag = $row['flag'];
                                        } else {
                                            $flag = 'no_image.png';
                                        }
                                        ?>
                                        <div class="ew-upload-image<?php echo ($row['flag'] != '') ? ' has-file' : ''; ?>">
                                            <div class="ew-upload-image__zone" tabindex="0" role="button" aria-label="Upload flag">
                                                <input type="file" id="flag" name="flag" class="ew-upload-image__input" accept="image/*">
                                                <div class="ew-upload-image__body">
                                                    <i class="fa fa-cloud-upload" aria-hidden="true"></i>
                                                    <span class="ew-upload-image__hint">Drag &amp; drop or browse</span>
                                                    <span class="ew-upload-image__meta">PNG, JPG · max 2MB</span>
                                                    <span class="ew-upload-image__filename" title="<?php echo ($row['flag'] != '') ? htmlspecialchars($row['flag']) : ''; ?>"><?php echo ($row['flag'] != '') ? htmlspecialchars($row['flag']) : ''; ?></span>
                                                    <span class="ew-btn-v2 ew-btn-v2-outline ew-upload-image__browse">Browse</span>
                                                </div>
                                            </div>
                                            <div class="ew-upload-image__preview">
                                                <img src="images/<?php echo htmlspecialchars($flag); ?>" id="image_preview1" alt="Flag preview">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            </div>
                            <div class="ew-form-footer">
                                <button class="ew-btn-v2 ew-btn-v2-primary" type="button" id="save"><i class="fa fa-save"></i> Save Company</button>
                            </div>
                        </div>
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

            function initEwUploadImage($wrap) {
                var $input = $wrap.find('.ew-upload-image__input');
                var $zone = $wrap.find('.ew-upload-image__zone');
                var $preview = $wrap.find('.ew-upload-image__preview img');
                var $filename = $wrap.find('.ew-upload-image__filename');

                function applyFile(file) {
                    if (!file) {
                        return;
                    }
                    $filename.text(file.name).attr('title', file.name);
                    $wrap.addClass('has-file');
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $preview.attr('src', e.target.result);
                    };
                    reader.readAsDataURL(file);
                }

                $zone.on('click', function(e) {
                    if ($(e.target).is('input')) {
                        return;
                    }
                    $input.trigger('click');
                });

                $zone.on('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        $input.trigger('click');
                    }
                });

                $input.on('change', function() {
                    applyFile(this.files && this.files[0] ? this.files[0] : null);
                });

                $zone.on('dragover dragenter', function(e) {
                    e.preventDefault();
                    $zone.addClass('is-dragover');
                });

                $zone.on('dragleave drop', function(e) {
                    e.preventDefault();
                    $zone.removeClass('is-dragover');
                });

                $zone.on('drop', function(e) {
                    var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
                    if (!files || !files.length) {
                        return;
                    }
                    if (typeof DataTransfer !== 'undefined') {
                        var dt = new DataTransfer();
                        dt.items.add(files[0]);
                        $input[0].files = dt.files;
                    }
                    applyFile(files[0]);
                });
            }

            $('.ew-upload-image').each(function() {
                initEwUploadImage($(this));
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