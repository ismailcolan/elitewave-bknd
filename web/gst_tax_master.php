<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/gst_tax_functions.php');

ensure_gst_tax_master_table($conn);

$tax_rows = gst_tax_fetch_list($conn, array(
    'search' => '',
    'status' => 'all',
    'gst_rate' => 'all',
    'deleted' => 'active',
));
?>
<!DOCTYPE html>
<html>

<head>
    <?php include('include/title.php'); ?>
    <?php include('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <style>
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

        .gst_tax_tab {
            width: 100% !important;
            max-width: 100% !important;
        }

        .gst_tax_tab .pct {
            text-align: right;
        }

        .gst-search-wrap {
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e5e5;
            clear: both;
            overflow: hidden;
        }

        .gst-search-wrap .form-control {
            width: 280px;
            max-width: 100%;
            display: inline-block;
            vertical-align: middle;
        }

        .gst-search-wrap .btn {
            vertical-align: middle;
            margin-left: 6px;
        }

        .gst-table-wrap {
            clear: both;
            overflow-x: auto;
            width: 100%;
        }

        #gstTaxTable_wrapper {
            clear: both;
            width: 100%;
        }

        #gstTaxTable_wrapper .dataTables_length,
        #gstTaxTable_wrapper .dataTables_filter {
            display: none !important;
        }

        #gstTaxTable_wrapper .dataTables_info {
            padding-top: 10px;
        }

        #gstTaxTable_wrapper .dataTables_paginate {
            padding-top: 8px;
            padding-bottom: 4px;
        }
        .btn-default-outline{
            background: #DD111E !important;
            color: #fff !important;
            border: none !important;
        }
        .btn-default-outline:hover{
            border: none !important;
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
                            <i class="fa fa-table"></i> GST Tax Master
                            <span class="align-right"><i class="fa fa-plus"></i> <a href="gst_tax_form.php">Add GST Tax</a></span>
                        </div>
                        <div class="widget-content padded clearfix new_dept">
                            <div class="gst-search-wrap">
                                <input type="text" class="form-control" id="gstTaxSearch"
                                    placeholder="Search Tax Code / Tax Name" autocomplete="off">
                                <button type="button" class="btn btn-default-outline" id="gstTaxSearchClear" style="display:none;">Clear</button>
                            </div>

                            <div class="gst-table-wrap">
                            <table class="table table-bordered table-striped gst_tax_tab" id="gstTaxTable">
                                <thead>
                                    <tr>
                                        <th class="table-title" style="width:5%">S.No</th>
                                        <th class="table-title" style="width:10%">Tax Code</th>
                                        <th class="table-title" style="width:14%">Tax Name</th>
                                        <th class="table-title pct" style="width:8%">GST %</th>
                                        <th class="table-title pct" style="width:8%">CGST %</th>
                                        <th class="table-title pct" style="width:10%">SGST/UTGST %</th>
                                        <th class="table-title pct" style="width:8%">IGST %</th>
                                        <th class="table-title pct" style="width:8%">Cess %</th>
                                        <th class="table-title" style="width:8%">Status</th>
                                        <th class="table-title" style="width:12%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    if (empty($tax_rows)) {
                                        echo '<tr><td colspan="10" class="text-center">No records found.</td></tr>';
                                    } else {
                                        foreach ($tax_rows as $row) {
                                            $is_deleted = (int) $row['is_deleted'] === 1;
                                            $is_active = (int) $row['status'] === 1;
                                            if ($is_deleted) {
                                                $status_label = 'Deleted';
                                            } elseif ($is_active) {
                                                $status_label = 'Active';
                                            } else {
                                                $status_label = 'Inactive';
                                            }
                                            ?>
                                            <tr>
                                                <td class="text-center"><?php echo $i; ?></td>
                                                <td><?php echo htmlspecialchars($row['tax_code']); ?></td>
                                                <td><?php echo htmlspecialchars($row['tax_name']); ?></td>
                                                <td class="pct"><?php echo gst_tax_format_rate($row['gst_rate']); ?></td>
                                                <td class="pct"><?php echo gst_tax_format_rate($row['cgst_rate']); ?></td>
                                                <td class="pct"><?php echo gst_tax_format_rate($row['sgst_rate']); ?></td>
                                                <td class="pct"><?php echo gst_tax_format_rate($row['igst_rate']); ?></td>
                                                <td class="pct"><?php echo gst_tax_format_rate($row['cess_rate']); ?></td>
                                                <td><?php echo $status_label; ?></td>
                                                <td class="actions center-content">
                                                    <div class="action-buttons">
                                                        <?php if (!$is_deleted) { ?>
                                                            <a title="Edit" class="table-actions" href="gst_tax_form.php?id=<?php echo (int) $row['gst_tax_id']; ?>"><i class="fa fa-pencil"></i></a>
                                                            <?php if ($is_active) { ?>
                                                                <a class="table-actions btn-active" style="color:red;" data-status="1" title="Deactivate" id="<?php echo (int) $row['gst_tax_id']; ?>"><i class="fa fa-times"></i></a>
                                                            <?php } else { ?>
                                                                <a class="table-actions btn-active" data-status="0" title="Activate" id="<?php echo (int) $row['gst_tax_id']; ?>"><i class="fa fa-check"></i></a>
                                                            <?php } ?>
                                                            <a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php echo (int) $row['gst_tax_id']; ?>"><i class="fa fa-trash-o"></i></a>
                                                        <?php } else { ?>
                                                            <a title="Restore" class="table-actions btn-restore" id="<?php echo (int) $row['gst_tax_id']; ?>"><i class="fa fa-undo"></i></a>
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
            </div>

            <?php require_once('include/footer.php'); ?>
        </div>

        <script type="text/javascript">
            $(document).ready(function() {
                var gstTaxTable = null;

                if ($.fn.dataTable) {
                    gstTaxTable = $('#gstTaxTable').dataTable({
                        sPaginationType: 'full_numbers',
                        bFilter: false,
                        bLengthChange: false,
                        sDom: 'rtip',
                        aoColumnDefs: [{
                            bSortable: false,
                            aTargets: [0, -1]
                        }]
                    });
                }

                var $gstSearch = $('#gstTaxSearch');
                var $gstClear = $('#gstTaxSearchClear');

                function applyGstSearch() {
                    var term = ($gstSearch.val() || '').toLowerCase().trim();
                    var visibleCount = 0;

                    $('#gstTaxTable tbody tr').each(function() {
                        var $row = $(this);
                        if ($row.find('td[colspan]').length) {
                            $row.hide();
                            return;
                        }
                        var code = $row.find('td').eq(1).text().toLowerCase();
                        var name = $row.find('td').eq(2).text().toLowerCase();
                        var match = !term || code.indexOf(term) !== -1 || name.indexOf(term) !== -1;
                        $row.toggle(match);
                        if (match) {
                            visibleCount++;
                        }
                    });

                    var $emptyRow = $('#gstTaxTable tbody tr.gst-search-empty');
                    if (visibleCount === 0) {
                        if (!$emptyRow.length) {
                            $('#gstTaxTable tbody').append(
                                '<tr class="gst-search-empty"><td colspan="10" class="text-center">No records found.</td></tr>'
                            );
                        }
                    } else {
                        $emptyRow.remove();
                    }

                    $gstClear.toggle(term.length > 0);
                }

                $gstSearch.on('keyup input', applyGstSearch);

                $gstClear.on('click', function() {
                    $gstSearch.val('').focus();
                    applyGstSearch();
                });

                function showAlert(success, message) {
                    ewFormToast(message, success ? 'success' : 'error', 5000);
                }

                $(document).on('click', '.btn-trash', function() {
                    $('.btn-confirm-delete').attr('id', $(this).attr('id'));
                });

                $(document).on('click', '.btn-confirm-delete', function() {
                    $.post('save_details.php', {
                        form_name: 'soft_delete_gst_tax_master',
                        tbl_id: $(this).attr('id')
                    }, function(data) {
                        var result = data;
                        try { result = typeof data === 'string' ? JSON.parse(data) : data; } catch (e) {}
                        if (result && result.status == 1) {
                            showAlert(true, result.message || 'Deleted successfully.');
                            setTimeout(function() { location.reload(); }, 1200);
                        } else {
                            showAlert(false, (result && result.message) ? result.message : 'Delete failed.');
                        }
                    });
                });

                $(document).on('click', '.btn-active', function() {
                    var id = $(this).attr('id');
                    var current = $(this).attr('data-status');
                    var newStatus = current == '1' ? 0 : 1;
                    var msg = newStatus == 1 ? 'Activated' : 'Deactivated';
                    $.post('save_details.php', {
                        form_name: 'toggle_gst_tax_status',
                        tbl_id: id,
                        status: newStatus
                    }, function(data) {
                        var result = data;
                        try { result = typeof data === 'string' ? JSON.parse(data) : data; } catch (e) {}
                        if (result && result.status == 1) {
                            showAlert(true, 'GST Tax ' + msg + ' successfully.');
                            setTimeout(function() { location.reload(); }, 1200);
                        } else {
                            showAlert(false, (result && result.message) ? result.message : 'Status update failed.');
                        }
                    });
                });

                $(document).on('click', '.btn-restore', function() {
                    $.post('save_details.php', {
                        form_name: 'restore_gst_tax_master',
                        tbl_id: $(this).attr('id')
                    }, function(data) {
                        var result = data;
                        try { result = typeof data === 'string' ? JSON.parse(data) : data; } catch (e) {}
                        if (result && result.status == 1) {
                            showAlert(true, result.message || 'Restored successfully.');
                            setTimeout(function() { location.reload(); }, 1200);
                        } else {
                            showAlert(false, (result && result.message) ? result.message : 'Restore failed.');
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

        <div class="modal fade popup_close" id="myModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                        <h4 class="modal-title" style="color:#fff">Alert!</h4>
                    </div>
                    <div class="modal-body">
                        <h5 text-align="center">Do you want to delete this GST tax record? (Soft delete — can be restored later)</h5>
                        <div class="modal-footer">
                            <button class="btn btn-primary btn-confirm-delete" data-dismiss="modal" type="button" id="">Yes</button>
                            <button class="btn btn-default-outline" data-dismiss="modal" type="button">No</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
