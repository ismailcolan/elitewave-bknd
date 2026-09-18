<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/gst_tax_functions.php');

ensure_gst_tax_master_table($conn);

$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = $edit_id > 0;
if (!$is_edit && empty($_GET['create'])) {
    header('Location:gst_tax_master.php');
    exit;
}
$row = array(
    'tax_code' => '',
    'tax_name' => '',
    'gst_rate' => '',
    'cgst_rate' => '',
    'sgst_rate' => '',
    'igst_rate' => '',
    'cess_rate' => '0',
    'tds_rate' => '0',
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

        .ew-page-v2 .ew-form-grid--gst-tax {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        @media (max-width: 991px) {
            .ew-page-v2 .ew-form-grid--gst-tax {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .ew-page-v2 .ew-form-grid--gst-tax {
                grid-template-columns: 1fr;
            }
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
                <div class="col-md-12">
                    <div class="ew-page-v2">
                        <div class="ew-page-head">
                            <div class="ew-page-head-left">
                                <a href="gst_tax_master.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
                                <h1 class="ew-page-title"><?php echo $is_edit ? 'Edit GST Tax' : 'Add GST Tax'; ?></h1>
                            </div>
                            <div class="ew-toolbar-right">
                                <a href="gst_tax_master.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
                            </div>
                        </div>
                        <div class="ew-card">
                            <div class="ew-form-body">
                            <form class="ew-validated-form" id="gst_tax_form" data-ew-validate="1">
                                <input type="hidden" id="form_name" name="form_name" value="<?php echo $is_edit ? 'edit_gst_tax_master' : 'add_gst_tax_master'; ?>">
                                <input type="hidden" id="edit_id" name="edit_id" value="<?php echo $is_edit ? $edit_id : ''; ?>">

                                <div id="response" class="alert alert-danger" style="display:none;">
                                    <div class="message" style="text-align:center"></div>
                                </div>

                                <div class="ew-form-grid ew-form-grid--gst-tax">
                                    <div class="ew-section-label">Tax Details</div>
                                    <div class="ew-field">
                                        <label class="control-label">Tax Code <span style="color:red;">*</span> :</label>
                                        <input type="text" name="tax_code" id="tax_code" class="form-control" required maxlength="30"
                                            value="<?php echo htmlspecialchars($row['tax_code']); ?>"
                                            autocomplete="off" style="text-transform:uppercase;" placeholder="e.g. GST18" />
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">Tax Name <span style="color:red;">*</span> :</label>
                                        <input type="text" name="tax_name" id="tax_name" class="form-control" required maxlength="120"
                                            value="<?php echo htmlspecialchars($row['tax_name']); ?>"
                                            autocomplete="off" placeholder="e.g. GST 18%" />
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">Status :</label>
                                        <select name="status" id="status" class="form-control">
                                            <option value="1" <?php echo (int) $row['status'] === 1 ? 'selected' : ''; ?>>Active</option>
                                            <option value="0" <?php echo (int) $row['status'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="ew-section-label">Rate Breakdown</div>
                                    <div class="ew-field">
                                        <label class="control-label">GST Rate (%) <span style="color:red;">*</span> :</label>
                                        <input type="number" name="gst_rate" id="gst_rate" class="form-control" required min="0" step="0.01"
                                            value="<?php echo htmlspecialchars($row['gst_rate']); ?>" />
                                        <div class="gst-auto-hint">CGST, SGST/UTGST and IGST auto-calculate from GST rate.</div>
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">CGST Rate (%) :</label>
                                        <input type="number" name="cgst_rate" id="cgst_rate" class="form-control gst-component" min="0" step="0.01"
                                            value="<?php echo htmlspecialchars($row['cgst_rate']); ?>" />
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">SGST / UTGST Rate (%) :</label>
                                        <input type="number" name="sgst_rate" id="sgst_rate" class="form-control gst-component" min="0" step="0.01"
                                            value="<?php echo htmlspecialchars($row['sgst_rate']); ?>" />
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">IGST Rate (%) :</label>
                                        <input type="number" name="igst_rate" id="igst_rate" class="form-control gst-component" min="0" step="0.01"
                                            value="<?php echo htmlspecialchars($row['igst_rate']); ?>" />
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">Cess Rate (%) :</label>
                                        <input type="number" name="cess_rate" id="cess_rate" class="form-control" min="0" step="0.01"
                                            value="<?php echo htmlspecialchars($row['cess_rate']); ?>" />
                                    </div>
                                    <div class="ew-field">
                                        <label class="control-label">TDS Rate (%) :</label>
                                        <input type="number" name="tds_rate" id="tds_rate" class="form-control" min="0" step="0.01"
                                            value="<?php echo htmlspecialchars($row['tds_rate'] ?? '0'); ?>" />
                                    </div>
                                </div>
                            </form>
                            </div>
                            <div class="ew-form-footer">
                                <a class="ew-btn-v2 ew-btn-v2-outline" href="gst_tax_master.php">Cancel</a>
                                <button class="ew-btn-v2 ew-btn-v2-primary" type="button" id="save"><i class="fa fa-save"></i> <?php echo $is_edit ? 'Update' : 'Submit'; ?></button>
                            </div>
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
                    var tds = round2($('#tds_rate').val());

                    if (!$('#tax_code').val().trim() || !$('#tax_name').val().trim()) {
                        showAlert(false, 'Tax Code and Tax Name are required.');
                        return false;
                    }
                    if (gst < 0 || cgst < 0 || sgst < 0 || igst < 0 || cess < 0 || tds < 0) {
                        showAlert(false, 'Tax rates cannot be negative.');
                        return false;
                    }
                    if (gst > 0) {
                        if (Math.abs((cgst + sgst) - gst) > 0.01) {
                            showAlert(false, 'CGST + SGST/UTGST must equal GST Rate.');
                            return false;
                        }
                        if (Math.abs(igst - gst) > 0.01) {
                            showAlert(false, 'IGST must equal GST Rate.');
                            return false;
                        }
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
