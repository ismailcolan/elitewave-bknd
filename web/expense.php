<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_schema.php');
require_once('include/expense_functions.php');

expense_ensure_tables($conn);
expense_require_admin();

$key = $_REQUEST['key'] ?? '';
$prefill_grn = trim($_REQUEST['grn'] ?? '');
$row = array(
    'expense_id' => 0,
    'expense_no' => '',
    'expense_date' => date('d-m-Y'),
    'grn_no' => $prefill_grn,
    'transaction_id' => 0,
    'trans_table' => '',
    'category_id' => '',
    'vendor_id' => '',
    'amount' => '',
    'payment_mode' => 'CASH',
    'paid_by' => '',
    'description' => '',
);
$is_edit = false;
$gcn_data = null;

if ($key != '') {
    $key_esc = mysqli_real_escape_string($conn, $key);
    $q = mysqli_query($conn, "SELECT * FROM extra_expense WHERE md5(expense_id)='$key_esc' AND status=0 LIMIT 1");
    if (!$q || mysqli_num_rows($q) === 0) {
        header('Location: expense_list.php');
        exit;
    }
    $row = mysqli_fetch_assoc($q);
    $is_edit = true;
    $lookup = expense_lookup_grn($conn, $row['grn_no']);
    if ($lookup['status'] == 1) {
        $gcn_data = $lookup['data'];
        if ($is_edit) {
            $gcn_data['extra_paid_total'] = round(expense_sum_by_grn($conn, $row['grn_no'], (int) $row['expense_id']));
        }
    }
} elseif ($prefill_grn !== '') {
    $lookup = expense_lookup_grn($conn, $prefill_grn);
    if ($lookup['status'] == 1) {
        $gcn_data = $lookup['data'];
        $row['grn_no'] = $gcn_data['grn_no'];
        $row['transaction_id'] = $gcn_data['transaction_id'];
        $row['trans_table'] = $gcn_data['trans_table'];
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <?php include('include/title.php'); ?>
    <?php include('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <style>
        #gcn_info_panel {
            display: none;
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid #D8DDE5;
            border-radius: 8px;
            background: #F8FAFC;
        }

        #gcn_info_panel.visible {
            display: block;
        }

        #gcn_info_panel .gcn-info-row {
            margin-bottom: 6px;
            font-size: 13px;
        }

        #gcn_info_panel .gcn-info-label {
            font-weight: 600;
            color: #334155;
        }

        #gcn_search_msg {
            margin-top: 6px;
            font-size: 12px;
        }

        .grn-search-wrap {
            display: flex;
            gap: 8px;
        }

        .grn-search-wrap .form-control {
            flex: 1;
        }

        .expense-steps {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding: 12px 14px;
            border: 1px solid #D8DDE5;
            border-radius: 8px;
            background: #F8FAFC;
        }

        .expense-steps .step {
            flex: 1;
            min-width: 180px;
            font-size: 13px;
            color: #475569;
        }

        .expense-steps .step-num {
            display: inline-block;
            width: 22px;
            height: 22px;
            margin-right: 6px;
            border-radius: 50%;
            background: #0A1E3D;
            color: #fff;
            text-align: center;
            line-height: 22px;
            font-size: 12px;
            font-weight: 700;
        }

        .expense-steps .step strong {
            color: #0A1E3D;
        }

        .select-with-add {
            display: flex;
            gap: 8px;
            align-items: stretch;
        }

        .select-with-add select {
            flex: 1;
        }

        .select-with-add .btn-add-inline {
            white-space: nowrap;
            padding-left: 12px;
            padding-right: 12px;
        }

        .expense-manage-note {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px dashed #CBD5E1;
            font-size: 13px;
            color: #64748B;
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
                            <i class="fa fa-<?php echo $is_edit ? 'pencil' : 'plus'; ?>"></i>
                            <?php echo $is_edit ? 'Edit Extra Expense' : 'Add Extra Expense'; ?>
                            <span class="align-right">
                                <i class="fa fa-list"></i><a href="expense_list.php">View List</a>
                            </span>
                        </div>
                        <div class="widget-content padded">
                            <?php if (!$is_edit) { ?>
                                <div class="expense-steps">
                                    <div class="step"><span class="step-num">1</span><strong>Search GCN</strong> — one expense is linked to one GCN (even when a truck has many GCNs).</div>
                                    <div class="step"><span class="step-num">2</span><strong>Pick category &amp; paid to</strong> — use <strong>+ New</strong> if the name is not in the list yet.</div>
                                    <div class="step"><span class="step-num">3</span><strong>Enter amount</strong> and save. Repeat for each GCN on the trip.</div>
                                </div>
                            <?php } ?>
                            <form class="form-horizontal" id="extra_expense_form">
                                <input type="hidden" id="form_name" name="form_name" value="<?php echo $is_edit ? 'edit_extra_expense' : 'add_extra_expense'; ?>">
                                <input type="hidden" id="edit_id" name="edit_id" value="<?php echo $is_edit ? (int) $row['expense_id'] : ''; ?>">
                                <input type="hidden" id="transaction_id" name="transaction_id" value="<?php echo (int) $row['transaction_id']; ?>">
                                <input type="hidden" id="trans_table" name="trans_table" value="<?php echo htmlspecialchars($row['trans_table']); ?>">
                                <input type="hidden" id="gcn_valid" name="gcn_valid" value="<?php echo $gcn_data ? '1' : '0'; ?>">

                                <div class="row">
                                    <div class="col-md-offset-1 col-md-5">
                                        <?php if ($is_edit) { ?>
                                            <div class="form-group">
                                                <label class="control-label">Expense No :</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['expense_no']); ?>" readonly />
                                            </div>
                                        <?php } ?>
                                        <div class="form-group">
                                            <label class="control-label">GCN No <span style="color:red;">*</span> :</label>
                                            <div class="grn-search-wrap">
                                                <input type="text" id="grn_no" name="grn_no" class="form-control" value="<?php echo htmlspecialchars($row['grn_no']); ?>" required autocomplete="off" <?php echo $is_edit ? 'readonly' : ''; ?> />
                                                <?php if (!$is_edit) { ?>
                                                    <button type="button" class="btn btn-primary" id="btn_search_grn">Search</button>
                                                <?php } ?>
                                            </div>
                                            <div id="gcn_search_msg"></div>
                                        </div>
                                        <div id="gcn_info_panel" class="<?php echo $gcn_data ? 'visible' : ''; ?>">
                                            <div class="gcn-info-row"><span class="gcn-info-label">GRN Date:</span> <span id="info_grn_date"><?php echo htmlspecialchars($gcn_data['grn_date'] ?? ''); ?></span></div>
                                            <div class="gcn-info-row"><span class="gcn-info-label">Route:</span> <span id="info_route"><?php echo htmlspecialchars(($gcn_data['origin'] ?? '') . ' → ' . ($gcn_data['destination'] ?? '')); ?></span></div>
                                            <div class="gcn-info-row"><span class="gcn-info-label">Consignor:</span> <span id="info_consignor"><?php echo htmlspecialchars($gcn_data['consignor'] ?? ''); ?></span></div>
                                            <div class="gcn-info-row"><span class="gcn-info-label">Consignee:</span> <span id="info_consignee"><?php echo htmlspecialchars($gcn_data['consignee'] ?? ''); ?></span></div>
                                            <div class="gcn-info-row"><span class="gcn-info-label">Mode:</span> <span id="info_mode"><?php echo htmlspecialchars($gcn_data['mode'] ?? ''); ?></span></div>
                                            <div class="gcn-info-row"><span class="gcn-info-label">Estimated Freight:</span> ₹ <span id="info_freight"><?php echo expense_format_rupee($gcn_data['estimated_freight'] ?? 0); ?></span></div>
                                            <div class="gcn-info-row"><span class="gcn-info-label">Extra Already Paid on this GCN:</span> ₹ <span id="info_extra_total"><?php echo expense_format_rupee($gcn_data['extra_paid_total'] ?? 0); ?></span></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Expense Date <span style="color:red;">*</span> :</label>
                                            <div class="input-group date date-picker" data-date-format="dd-mm-yyyy" data-date-autoclose="true">
                                                <input type="text" id="expense_date" name="expense_date" class="form-control" value="<?php echo htmlspecialchars($row['expense_date']); ?>" required autocomplete="off" />
                                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Category <span style="color:red;">*</span> :</label>
                                            <div class="select-with-add">
                                                <select name="category_id" id="category_id" class="form-control" required>
                                                    <?php echo expense_category_options($conn, (int) $row['category_id']); ?>
                                                </select>
                                                <button type="button" class="btn btn-default-outline btn-add-inline" id="btn_add_category" title="Add new category">+ New</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label class="control-label">Paid To <span style="color:red;">*</span> :</label>
                                            <div class="select-with-add">
                                                <select name="vendor_id" id="vendor_id" class="form-control" required>
                                                    <?php echo expense_vendor_options($conn, (int) $row['vendor_id']); ?>
                                                </select>
                                                <button type="button" class="btn btn-default-outline btn-add-inline" id="btn_add_vendor" title="Add new paid-to party">+ New</button>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Amount (₹) <span style="color:red;">*</span> :</label>
                                            <input type="number" min="1" step="1" name="amount" id="amount" class="form-control" value="<?php echo $row['amount'] !== '' ? (int) $row['amount'] : ''; ?>" required autocomplete="off" />
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Payment Mode :</label>
                                            <select name="payment_mode" id="payment_mode" class="form-control">
                                                <?php
                                                $modes = array('CASH' => 'Cash', 'UPI' => 'UPI', 'BANK' => 'Bank', 'CHEQUE' => 'Cheque');
                                                foreach ($modes as $val => $label) {
                                                    $sel = ($row['payment_mode'] === $val) ? ' selected' : '';
                                                    echo '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Paid By (Partner) :</label>
                                            <input type="text" name="paid_by" id="paid_by" class="form-control" value="<?php echo htmlspecialchars($row['paid_by']); ?>" autocomplete="off" />
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Description :</label>
                                            <textarea name="description" id="description" class="form-control" rows="3" autocomplete="off"><?php echo htmlspecialchars($row['description']); ?></textarea>
                                        </div>
                                    </div>
                                </div><br />
                                <div class="row">
                                    <div class="col-md-12 form-action">
                                        <button class="btn btn-primary" type="button" id="save"><?php echo $is_edit ? 'Update' : 'Submit'; ?></button>
                                        <a class="btn btn-default-outline" href="expense_list.php">Cancel</a>
                                    </div>
                                </div>
                                <div class="expense-manage-note">
                                    Need to edit or deactivate many categories / paid-to parties?
                                    <a href="expense_setup.php">Open full lists &amp; settings</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once('include/footer.php'); ?>
        </div>

        <!-- Quick add category -->
        <div class="modal fade" id="modal_add_category" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Add Expense Category</h4>
                    </div>
                    <div class="modal-body">
                        <form id="quick_category_form">
                            <div class="form-group">
                                <label>Category Name <span style="color:red;">*</span></label>
                                <input type="text" class="form-control" id="quick_category_name" maxlength="100" required autocomplete="off" placeholder="e.g. Halting, Loading, Toll" />
                                <small class="text-muted">Code is created automatically.</small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default-outline" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="quick_category_save">Save &amp; Select</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick add paid to -->
        <div class="modal fade" id="modal_add_vendor" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Add Paid To Party</h4>
                    </div>
                    <div class="modal-body">
                        <form id="quick_vendor_form">
                            <div class="form-group">
                                <label>Name <span style="color:red;">*</span></label>
                                <input type="text" class="form-control" id="quick_vendor_name" maxlength="120" required autocomplete="off" placeholder="Driver / agent / halting party name" />
                            </div>
                            <div class="form-group">
                                <label>Type <span style="color:red;">*</span></label>
                                <select class="form-control" id="quick_vendor_type" required>
                                    <option value="DRIVER">Driver</option>
                                    <option value="AGENT">Agent</option>
                                    <option value="HALTING">Halting</option>
                                    <option value="OTHER">Other</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default-outline" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="quick_vendor_save">Save &amp; Select</button>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            $(document).ready(function() {
                $('.date-picker').datepicker({
                    format: 'dd-mm-yyyy',
                    autoclose: true,
                    todayHighlight: true
                });

                function formatRupee(n) {
                    n = parseInt(n, 10) || 0;
                    return n.toLocaleString('en-IN');
                }

                function showGcnInfo(data) {
                    $('#gcn_info_panel').addClass('visible');
                    $('#info_grn_date').text(data.grn_date || '');
                    $('#info_route').text((data.origin || '') + ' → ' + (data.destination || ''));
                    $('#info_consignor').text(data.consignor || '');
                    $('#info_consignee').text(data.consignee || '');
                    $('#info_mode').text(data.mode || '');
                    $('#info_freight').text(formatRupee(data.estimated_freight || 0));
                    $('#info_extra_total').text(formatRupee(data.extra_paid_total || 0));
                    $('#transaction_id').val(data.transaction_id || '');
                    $('#trans_table').val(data.trans_table || '');
                    $('#gcn_valid').val('1');
                    $('#gcn_search_msg').html('<span style="color:green;">GCN verified.</span>');
                }

                function searchGrn() {
                    var grn = $.trim($('#grn_no').val());
                    if (!grn) {
                        $('#gcn_search_msg').html('<span style="color:red;">Enter GCN number.</span>');
                        $('#gcn_valid').val('0');
                        return;
                    }
                    $('#gcn_search_msg').html('<span style="color:#666;">Searching...</span>');
                    $.ajax({
                        url: 'fetch_details.php',
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            cmd: 'get_grn_for_extra_expense',
                            grn_no: grn,
                            exclude_expense_id: $('#edit_id').val() || ''
                        },
                        success: function(res) {
                            if (res.status == 1 && res.data) {
                                showGcnInfo(res.data);
                            } else {
                                $('#gcn_info_panel').removeClass('visible');
                                $('#gcn_valid').val('0');
                                $('#transaction_id').val('');
                                $('#trans_table').val('');
                                $('#gcn_search_msg').html('<span style="color:red;">' + (res.message || 'GCN not found.') + '</span>');
                            }
                        },
                        error: function() {
                            ewToast('Unable to search GCN.', 'error');
                        }
                    });
                }

                $('#btn_search_grn').on('click', searchGrn);
                $('#grn_no').on('keypress', function(e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        searchGrn();
                    }
                });

                function selectDropdownOption($select, id, label) {
                    var found = false;
                    $select.find('option').each(function() {
                        if ($(this).val() == id) {
                            found = true;
                            return false;
                        }
                    });
                    if (!found) {
                        $select.append($('<option></option>').val(id).text(label));
                    }
                    $select.val(String(id));
                }

                $('#btn_add_category').on('click', function() {
                    $('#quick_category_name').val('');
                    $('#modal_add_category').modal('show');
                    setTimeout(function() {
                        $('#quick_category_name').focus();
                    }, 300);
                });

                $('#quick_category_save').on('click', function() {
                    var name = $.trim($('#quick_category_name').val());
                    if (!name) {
                        ewToast('Enter category name.', 'warning');
                        return;
                    }
                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    $.ajax({
                        url: 'save_details.php',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            form_name: 'quick_add_expense_category',
                            category_name: name
                        },
                        success: function(res) {
                            if (res && res.result == 1) {
                                selectDropdownOption($('#category_id'), res.id, res.name);
                                $('#modal_add_category').modal('hide');
                                ewToast('Category added.', 'success');
                            } else {
                                ewToast((res && res.message) ? res.message : 'Could not add category.', 'error');
                            }
                            $btn.prop('disabled', false);
                        },
                        error: function() {
                            ewToast('Could not add category.', 'error');
                            $btn.prop('disabled', false);
                        }
                    });
                });

                $('#btn_add_vendor').on('click', function() {
                    $('#quick_vendor_name').val('');
                    $('#quick_vendor_type').val('DRIVER');
                    $('#modal_add_vendor').modal('show');
                    setTimeout(function() {
                        $('#quick_vendor_name').focus();
                    }, 300);
                });

                $('#quick_vendor_save').on('click', function() {
                    var name = $.trim($('#quick_vendor_name').val());
                    var type = $('#quick_vendor_type').val();
                    if (!name) {
                        ewToast('Enter paid-to name.', 'warning');
                        return;
                    }
                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    $.ajax({
                        url: 'save_details.php',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            form_name: 'quick_add_expense_vendor',
                            vendor_name: name,
                            vendor_type: type
                        },
                        success: function(res) {
                            if (res && res.result == 1) {
                                selectDropdownOption($('#vendor_id'), res.id, res.name);
                                $('#modal_add_vendor').modal('hide');
                                ewToast('Paid-to party added.', 'success');
                            } else {
                                ewToast((res && res.message) ? res.message : 'Could not add paid-to party.', 'error');
                            }
                            $btn.prop('disabled', false);
                        },
                        error: function() {
                            ewToast('Could not add paid-to party.', 'error');
                            $btn.prop('disabled', false);
                        }
                    });
                });

                $('#save').on('click', function() {
                    if ($('#gcn_valid').val() !== '1') {
                        ewToast('Please search and verify GCN first.', 'warning');
                        return;
                    }
                    if (!$('#extra_expense_form').valid()) {
                        return;
                    }
                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    $.ajax({
                        url: 'save_details.php',
                        type: 'post',
                        data: $('#extra_expense_form').serialize(),
                        success: function(result) {
                            if ($.trim(result) === '1') {
                                $("#alert-message").text("Saved Successfully");
                                $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    window.location.href = 'expense_list.php';
                                });
                            } else {
                                ewToast(result || 'Save failed.', 'error');
                                $btn.prop('disabled', false);
                            }
                        },
                        error: function(jqxhr) {
                            ewToast(jqxhr.responseText, 'error');
                            $btn.prop('disabled', false);
                        }
                    });
                });
            });
            $(window).load(function() {
                $('.loading-page').hide();
            });
        </script>
        <div class="alert" id="alert-container" style="display:none;">
            <button type="button" class="close" data-dismiss="alert">x</button>
            <strong id="alert-status"></strong>
            <span id="alert-message"></span>
        </div>
</body>

</html>
