<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_schema.php');

expense_ensure_tables($conn);
expense_require_admin();

$tab = isset($_GET['tab']) ? strtolower(trim($_GET['tab'])) : 'category';
if (!in_array($tab, array('category', 'vendor'), true)) {
    $tab = 'category';
}

$type_labels = array(
    'DRIVER' => 'Driver',
    'AGENT' => 'Agent',
    'HALTING' => 'Halting',
    'OTHER' => 'Other',
);
?>
<!DOCTYPE html>
<html>

<head>
    <?php include('include/title.php'); ?>
    <?php include('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <style>
        .expense-setup-tabs {
            margin-bottom: 16px;
            border-bottom: 1px solid #D8DDE5;
        }

        .expense-setup-tabs .tab-btn {
            display: inline-block;
            padding: 10px 18px;
            margin-right: 4px;
            margin-bottom: -1px;
            border: 1px solid transparent;
            border-radius: 6px 6px 0 0;
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            text-decoration: none !important;
        }

        .expense-setup-tabs .tab-btn.active {
            background: #fff;
            border-color: #D8DDE5;
            border-bottom-color: #fff;
            color: #0A1E3D;
        }

        .setup-panel {
            display: none;
        }

        .setup-panel.active {
            display: block;
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
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading">
                            <i class="fa fa-list-alt"></i> Categories &amp; Paid To Lists
                            <span class="align-right">
                                <i class="fa fa-arrow-left"></i><a href="expense.php">Back to Add Expense</a>
                                &nbsp;|&nbsp;
                                <i class="fa fa-table"></i><a href="expense_list.php">Expense List</a>
                            </span>
                        </div>
                        <div class="widget-content padded">
                            <p class="text-muted" style="margin-bottom:12px;font-size:13px;">
                                Use this page when you need to <strong>edit, rename, or deactivate</strong> categories and paid-to parties.
                                While entering expenses, use the <strong>+ New</strong> button on the Add Expense form for quick one-time additions.
                            </p>
                            <div class="expense-setup-tabs">
                                <a href="expense_setup.php?tab=category" class="tab-btn<?php echo $tab === 'category' ? ' active' : ''; ?>">Expense Category</a>
                                <a href="expense_setup.php?tab=vendor" class="tab-btn<?php echo $tab === 'vendor' ? ' active' : ''; ?>">Paid To (Vendor)</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category -->
            <div class="row setup-panel<?php echo $tab === 'category' ? ' active' : ''; ?>" id="panel-category">
                <div class="col-md-3 master_left">
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"><i class="fa fa-plus"></i> Expense Category</div>
                        <div class="widget-content padded">
                            <form class="form-horizontal" id="expense_category_form">
                                <input type="hidden" id="cat_form_name" name="form_name" value="add_expense_category">
                                <input type="hidden" id="cat_edit_id" name="edit_id" value="">
                                <div class="form-group">
                                    <label class="control-label">Category Code <span style="color:red;">*</span> :</label>
                                    <input type="text" id="category_code" name="category_code" class="form-control" style="text-transform:uppercase;" maxlength="20" required autocomplete="off" />
                                    <span class="dup-check"></span>
                                </div>
                                <div class="form-group">
                                    <label class="control-label">Category Name <span style="color:red;">*</span> :</label>
                                    <input type="text" name="category_name" id="category_name" class="form-control" required autocomplete="off" />
                                </div>
                                <div class="form-action">
                                    <button class="btn btn-primary" type="button" id="cat_save">Submit</button>
                                    <button class="btn btn-default-outline cat-reset" type="button">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-9 master_right">
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"><i class="fa fa-table"></i> List of Expense Category</div>
                        <div class="widget-content padded clearfix new_dept">
                            <table class="table table-bordered table-striped" id="dataTable_cat">
                                <thead>
                                    <tr>
                                        <th style="width:8%">S.No</th>
                                        <th style="width:15%">Category Code</th>
                                        <th style="width:35%">Category Name</th>
                                        <th style="width:12%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = 'SELECT * FROM expense_category ORDER BY category_name';
                                    $result = mysqli_query($conn, $query);
                                    $i = 1;
                                    if ($result) {
                                        while ($row = mysqli_fetch_array($result)) {
                                    ?>
                                            <tr>
                                                <td class="text-center"><?php echo $i; ?></td>
                                                <td><?php echo htmlspecialchars($row['category_code']); ?></td>
                                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                                <td class="actions center-content">
                                                    <div class="action-buttons">
                                                        <a title="Edit" class="table-actions cat-edit" id="<?php echo $row['category_id']; ?>"><i class="fa fa-pencil"></i></a>
                                                        <?php if ($row['status'] == 0) { ?>
                                                            <a class="table-actions cat-active" data-status="<?php echo $row['status']; ?>" title="InActive" id="<?php echo $row['category_id']; ?>"><i class="fa fa-check"></i></a>
                                                        <?php } else { ?>
                                                            <a class="table-actions cat-active" style="color:red;" data-status="<?php echo $row['status']; ?>" title="Active" id="<?php echo $row['category_id']; ?>"><i class="fa fa-times"></i></a>
                                                        <?php } ?>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php
                                            $i++;
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vendor -->
            <div class="row setup-panel<?php echo $tab === 'vendor' ? ' active' : ''; ?>" id="panel-vendor">
                <div class="col-md-3 master_left">
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"><i class="fa fa-plus"></i> Paid To (Vendor)</div>
                        <div class="widget-content padded">
                            <form class="form-horizontal" id="expense_vendor_form">
                                <input type="hidden" id="ven_form_name" name="form_name" value="add_expense_vendor">
                                <input type="hidden" id="ven_edit_id" name="edit_id" value="">
                                <div class="form-group">
                                    <label class="control-label">Paid To Name <span style="color:red;">*</span> :</label>
                                    <input type="text" id="vendor_name" name="vendor_name" class="form-control" required autocomplete="off" />
                                </div>
                                <div class="form-group">
                                    <label class="control-label">Type <span style="color:red;">*</span> :</label>
                                    <select name="vendor_type" id="vendor_type" class="form-control" required>
                                        <option value="">Select Type</option>
                                        <option value="DRIVER">Driver</option>
                                        <option value="AGENT">Agent</option>
                                        <option value="HALTING">Halting</option>
                                        <option value="OTHER">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="control-label">Mobile :</label>
                                    <input type="text" name="mobile" id="mobile" class="form-control" maxlength="10" minlength="10" pattern="\d{10}" autocomplete="off" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" />
                                </div>
                                <div class="form-group">
                                    <label class="control-label">City :</label>
                                    <input type="text" name="city" id="city" class="form-control" autocomplete="off" />
                                </div>
                                <div class="form-action">
                                    <button class="btn btn-primary" type="button" id="ven_save">Submit</button>
                                    <button class="btn btn-default-outline ven-reset" type="button">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-9 master_right">
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"><i class="fa fa-table"></i> List of Paid To (Vendor)</div>
                        <div class="widget-content padded clearfix new_dept">
                            <table class="table table-bordered table-striped" id="dataTable_ven">
                                <thead>
                                    <tr>
                                        <th style="width:6%">S.No</th>
                                        <th style="width:22%">Paid To Name</th>
                                        <th style="width:12%">Type</th>
                                        <th style="width:12%">Mobile</th>
                                        <th style="width:12%">City</th>
                                        <th style="width:10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = 'SELECT * FROM expense_vendor ORDER BY vendor_name';
                                    $result = mysqli_query($conn, $query);
                                    $i = 1;
                                    if ($result) {
                                        while ($row = mysqli_fetch_array($result)) {
                                            $type_key = strtoupper(trim($row['vendor_type']));
                                    ?>
                                            <tr>
                                                <td class="text-center"><?php echo $i; ?></td>
                                                <td><?php echo htmlspecialchars($row['vendor_name']); ?></td>
                                                <td><?php echo htmlspecialchars($type_labels[$type_key] ?? $row['vendor_type']); ?></td>
                                                <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                                <td><?php echo htmlspecialchars($row['city']); ?></td>
                                                <td class="actions center-content">
                                                    <div class="action-buttons">
                                                        <a title="Edit" class="table-actions ven-edit" id="<?php echo $row['vendor_id']; ?>"><i class="fa fa-pencil"></i></a>
                                                        <?php if ($row['status'] == 0) { ?>
                                                            <a class="table-actions ven-active" data-status="<?php echo $row['status']; ?>" title="InActive" id="<?php echo $row['vendor_id']; ?>"><i class="fa fa-check"></i></a>
                                                        <?php } else { ?>
                                                            <a class="table-actions ven-active" style="color:red;" data-status="<?php echo $row['status']; ?>" title="Active" id="<?php echo $row['vendor_id']; ?>"><i class="fa fa-times"></i></a>
                                                        <?php } ?>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php
                                            $i++;
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <?php require_once('include/footer.php'); ?>
        </div>

        <script type="text/javascript">
            $(document).ready(function() {
                var dup_chk = true;

                function duplicate_check() {
                    var category_code = $('#category_code').val();
                    var edit_id = $('#cat_edit_id').val();
                    $.ajax({
                        cache: false,
                        url: 'check_existing.php',
                        type: 'GET',
                        dataType: 'json',
                        async: false,
                        data: {
                            cmd: 'chk_expense_category_code',
                            category_code: category_code,
                            edit_id: edit_id
                        },
                        success: function(result) {
                            dup_chk = true;
                            if (result[0] == 1) {
                                $('.dup-check').html(result[1]).css('color', '#f00');
                                dup_chk = false;
                            } else {
                                $('.dup-check').html(result[1]).css('color', 'green');
                            }
                        }
                    });
                }

                $('#cat_save').on('click', function() {
                    duplicate_check();
                    if ($('#expense_category_form').valid() == true && dup_chk) {
                        var $btn = $(this);
                        $btn.prop('disabled', true);
                        $.ajax({
                            url: 'save_details.php',
                            type: 'post',
                            data: $('#expense_category_form').serialize(),
                            success: function(result) {
                                if ($.trim(result) == '1') {
                                    window.location.href = 'expense_setup.php?tab=category&saved=1';
                                } else {
                                    ewToast('Data Saving Failed', 'error');
                                    $btn.prop('disabled', false);
                                }
                            },
                            error: function(jqxhr) {
                                ewToast(jqxhr.responseText, 'error');
                                $btn.prop('disabled', false);
                            }
                        });
                    }
                });

                $(document).on('click', '.cat-active', function() {
                    var status1 = $(this).attr('data-status') == '1' ? '0' : '1';
                    $.post('save_details.php', {
                        form_name: 'inacv_expense_category',
                        tbl_id: $(this).attr('id'),
                        status: status1
                    }, function(data) {
                        if ($.trim(data) == '1') {
                            window.location.href = 'expense_setup.php?tab=category';
                        }
                    });
                });

                $(document).on('click', '.cat-edit', function() {
                    $.ajax({
                        url: 'fetch_details.php',
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            cmd: 'get_expense_category_details',
                            tbl_id: $(this).attr('id')
                        },
                        success: function(result) {
                            $('#cat_form_name').val('edit_expense_category');
                            $('#cat_edit_id').val(result.category_id);
                            $('#category_code').val(result.category_code);
                            $('#category_name').val(result.category_name);
                            $('html, body').animate({
                                scrollTop: $('#expense_category_form').offset().top - 80
                            }, 300);
                        }
                    });
                });

                $('.cat-reset').on('click', function() {
                    $('#cat_form_name').val('add_expense_category');
                    $('#cat_edit_id').val('');
                    $('#category_code').val('');
                    $('#category_name').val('');
                    $('.dup-check').html('');
                });

                $('#ven_save').on('click', function() {
                    if ($('#expense_vendor_form').valid() == true) {
                        var $btn = $(this);
                        $btn.prop('disabled', true);
                        $.ajax({
                            url: 'save_details.php',
                            type: 'post',
                            data: $('#expense_vendor_form').serialize(),
                            success: function(result) {
                                if ($.trim(result) == '1') {
                                    window.location.href = 'expense_setup.php?tab=vendor&saved=1';
                                } else {
                                    ewToast('Data Saving Failed', 'error');
                                    $btn.prop('disabled', false);
                                }
                            },
                            error: function(jqxhr) {
                                ewToast(jqxhr.responseText, 'error');
                                $btn.prop('disabled', false);
                            }
                        });
                    }
                });

                $(document).on('click', '.ven-active', function() {
                    var status1 = $(this).attr('data-status') == '1' ? '0' : '1';
                    $.post('save_details.php', {
                        form_name: 'inacv_expense_vendor',
                        tbl_id: $(this).attr('id'),
                        status: status1
                    }, function(data) {
                        if ($.trim(data) == '1') {
                            window.location.href = 'expense_setup.php?tab=vendor';
                        }
                    });
                });

                $(document).on('click', '.ven-edit', function() {
                    $.ajax({
                        url: 'fetch_details.php',
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            cmd: 'get_expense_vendor_details',
                            tbl_id: $(this).attr('id')
                        },
                        success: function(result) {
                            $('#ven_form_name').val('edit_expense_vendor');
                            $('#ven_edit_id').val(result.vendor_id);
                            $('#vendor_name').val(result.vendor_name);
                            $('#vendor_type').val(result.vendor_type);
                            $('#mobile').val(result.mobile);
                            $('#city').val(result.city);
                            $('html, body').animate({
                                scrollTop: $('#expense_vendor_form').offset().top - 80
                            }, 300);
                        }
                    });
                });

                $('.ven-reset').on('click', function() {
                    $('#ven_form_name').val('add_expense_vendor');
                    $('#ven_edit_id').val('');
                    $('#expense_vendor_form')[0].reset();
                });

                if (window.location.search.indexOf('saved=1') !== -1) {
                    ewToast('Saved Successfully', 'success');
                }
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
