<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/gst_tax_functions.php');

ensure_gst_tax_master_table($conn);

$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = $edit_id > 0;
$row = array(
    'tax_code' => '',
    'tax_name' => '',
    'gst_rate' => '',
    'cgst_rate' => '',
    'sgst_rate' => '',
    'igst_rate' => '',
    'cess_rate' => '0',
    'status' => '1',
);

if ($is_edit) {
    $q = mysqli_query($conn, "SELECT * FROM gst_tax_master WHERE gst_tax_id='$edit_id' AND is_deleted=0 LIMIT 1");
    $loaded = mysqli_fetch_assoc($q);
    if (!$loaded) {
        header('Location: gst_tax_master.php');
        exit;
    }
    $row = $loaded;
}
?>
<!DOCTYPE html>
<html>

<head>
    <?php include('include/title.php'); ?>
    <?php include('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <style>
        .gst-auto-hint {
            font-size: 11px;
            color: #777;
            margin-top: 4px;
        }
    </style>
</head>

<body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
        <div class="navbar navbar-fixed-top scroll-hide">
            <?php
            require_once('include/header.php');
            require_once('include/menu.php');
            ?>
        </div>

        <div class="container-fluid main-content new_dpt_bottom">
            <div class="row">
                <div class="col-md-offset-1 col-md-10">
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading">
                            <i class="fa fa-plus"></i> <?php echo $is_edit ? 'Edit GST Tax' : 'Add GST Tax'; ?>
                            <span class="align-right"><i class="fa fa-table"></i> <a href="gst_tax_master.php">View List</a></span>
                        </div>
                        <div class="widget-content padded">
                            <form class="form-horizontal ew-validated-form" id="gst_tax_form" data-ew-validate="1">
                                <input type="hidden" id="form_name" name="form_name" value="<?php echo $is_edit ? 'edit_gst_tax_master' : 'add_gst_tax_master'; ?>">
                                <input type="hidden" id="edit_id" name="edit_id" value="<?php echo $is_edit ? $edit_id : ''; ?>">

                                <div id="response" class="alert alert-danger" style="display:none;">
                                    <div class="message" style="text-align:center"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Tax Code <span class="req-star">*</span> :</label>
                                            <input type="text" name="tax_code" id="tax_code" class="form-control" required maxlength="30"
                                                value="<?php echo htmlspecialchars($row['tax_code']); ?>"
                                                autocomplete="off" style="text-transform:uppercase;" placeholder="e.g. GST18" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Tax Name <span class="req-star">*</span> :</label>
                                            <input type="text" name="tax_name" id="tax_name" class="form-control" required maxlength="120"
                                                value="<?php echo htmlspecialchars($row['tax_name']); ?>"
                                                autocomplete="off" placeholder="e.g. GST 18%" />
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">GST Rate (%) <span class="req-star">*</span> :</label>
                                            <input type="number" name="gst_rate" id="gst_rate" class="form-control" required min="0" step="0.01"
                                                value="<?php echo htmlspecialchars($row['gst_rate']); ?>" />
                                            <div class="gst-auto-hint">CGST, SGST/UTGST and IGST auto-calculate from GST rate.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">CGST Rate (%) :</label>
                                            <input type="number" name="cgst_rate" id="cgst_rate" class="form-control gst-component" min="0" step="0.01"
                                                value="<?php echo htmlspecialchars($row['cgst_rate']); ?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">SGST / UTGST Rate (%) :</label>
                                            <input type="number" name="sgst_rate" id="sgst_rate" class="form-control gst-component" min="0" step="0.01"
                                                value="<?php echo htmlspecialchars($row['sgst_rate']); ?>" />
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">IGST Rate (%) :</label>
                                            <input type="number" name="igst_rate" id="igst_rate" class="form-control gst-component" min="0" step="0.01"
                                                value="<?php echo htmlspecialchars($row['igst_rate']); ?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">Cess Rate (%) :</label>
                                            <input type="number" name="cess_rate" id="cess_rate" class="form-control" min="0" step="0.01"
                                                value="<?php echo htmlspecialchars($row['cess_rate']); ?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">Status :</label>
                                            <select name="status" id="status" class="form-control">
                                                <option value="1" <?php echo (int) $row['status'] === 1 ? 'selected' : ''; ?>>Active</option>
                                                <option value="0" <?php echo (int) $row['status'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <br />
                                <div class="row">
                                    <div class="col-md-12 form-action">
                                        <button class="btn btn-primary" type="button" id="save">Submit</button>
                                        <a class="btn btn-default-outline" href="gst_tax_master.php" type="button">Cancel</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php require_once('include/footer.php'); ?>
        </div>

        <script type="text/javascript">
            $(document).ready(function() {
                var autoCalcEnabled = <?php echo $is_edit ? 'false' : 'true'; ?>;

                function round2(n) {
                    return Math.round(parseFloat(n || 0) * 100) / 100;
                }

                function autoCalculateComponents() {
                    if (!autoCalcEnabled) {
                        return;
                    }
                    var gst = round2($('#gst_rate').val());
                    if (isNaN(gst) || gst < 0) {
                        return;
                    }
                    var half = round2(gst / 2);
                    $('#cgst_rate').val(half);
                    $('#sgst_rate').val(half);
                    $('#igst_rate').val(gst);
                }

                $('#gst_rate').on('input change', autoCalculateComponents);

                $('.gst-component').on('input', function() {
                    autoCalcEnabled = false;
                });

                function showAlert(success, message) {
                    ewFormToast(message, success ? 'success' : 'error', 5000);
                }

                $('#gst_tax_form').validate({
                    ignore: [],
                    invalidHandler: function(event, validator) {
                        ewFormToast('Please fill all mandatory fields.', 'error', 5000);
                        if (validator.errorList.length) {
                            var $first = $(validator.errorList[0].element);
                            $('html, body').animate({ scrollTop: Math.max(0, $first.offset().top - 120) }, 300);
                            $first.focus();
                        }
                    }
                });

                function validateForm() {
                    var gst = round2($('#gst_rate').val());
                    var cgst = round2($('#cgst_rate').val());
                    var sgst = round2($('#sgst_rate').val());
                    var igst = round2($('#igst_rate').val());
                    var cess = round2($('#cess_rate').val());

                    if (!$('#tax_code').val().trim() || !$('#tax_name').val().trim()) {
                        showAlert(false, 'Tax Code and Tax Name are required.');
                        return false;
                    }
                    if (gst < 0 || cgst < 0 || sgst < 0 || igst < 0 || cess < 0) {
                        showAlert(false, 'Tax rates cannot be negative.');
                        return false;
                    }
                    if (Math.abs((cgst + sgst) - gst) > 0.01) {
                        showAlert(false, 'CGST + SGST/UTGST must equal GST Rate.');
                        return false;
                    }
                    if (Math.abs(igst - gst) > 0.01) {
                        showAlert(false, 'IGST must equal GST Rate.');
                        return false;
                    }
                    return true;
                }

                $(document).on('click', '#save', function() {
                    if (!validateForm()) {
                        return;
                    }
                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    $.ajax({
                        url: 'save_details.php',
                        type: 'POST',
                        dataType: 'json',
                        data: $('#gst_tax_form').serialize(),
                        success: function(result) {
                            $btn.prop('disabled', false);
                            if (result && result.status == 1) {
                                showAlert(true, result.message || 'Saved successfully. Please wait...');
                                setTimeout(function() {
                                    window.location.href = 'gst_tax_master.php';
                                }, 1200);
                            } else {
                                showAlert(false, (result && result.message) ? result.message : 'Data saving failed.');
                            }
                        },
                        error: function(jqxhr) {
                            $btn.prop('disabled', false);
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
    </div>
</body>

</html>
