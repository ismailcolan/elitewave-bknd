<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_schema.php');
require_once('include/expense_functions.php');

expense_ensure_tables($conn);
expense_require_admin();
?>
<!DOCTYPE html>
<html>

<head>
    <?php include('include/title.php'); ?>
    <?php include('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="./stylesheets/buttons.dataTables.min.css">
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
                            <i class="fa fa-table"></i> Extra Expense List
                            <span class="align-right"><i class="fa fa-plus"></i> <a href="expense.php">Add Expense</a></span>
                        </div>
                        <div class="widget-content padded clearfix new_dept">
                            <table class="table table-bordered table-striped" id="expense_table">
                                <thead>
                                    <tr>
                                        <th class="table-title">S.No</th>
                                        <th class="table-title">Expense No</th>
                                        <th class="table-title">Date</th>
                                        <th class="table-title">GCN No</th>
                                        <th class="table-title">Category</th>
                                        <th class="table-title">Paid To</th>
                                        <th class="table-title">Amount (₹)</th>
                                        <th class="table-title">Payment</th>
                                        <th class="table-title">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT e.*, c.category_name, v.vendor_name
                                        FROM extra_expense e
                                        LEFT JOIN expense_category c ON c.category_id = e.category_id
                                        LEFT JOIN expense_vendor v ON v.vendor_id = e.vendor_id
                                        WHERE e.status = 0
                                        ORDER BY e.expense_id DESC";
                                    $result = mysqli_query($conn, $sql);
                                    $i = 1;
                                    if ($result) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                            <tr>
                                                <td class="text-center"><?php echo $i; ?></td>
                                                <td><?php echo htmlspecialchars($row['expense_no']); ?></td>
                                                <td><?php echo htmlspecialchars($row['expense_date']); ?></td>
                                                <td>
                                                    <a href="expense_report.php?grn_no=<?php echo urlencode($row['grn_no']); ?>"><?php echo htmlspecialchars($row['grn_no']); ?></a>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['vendor_name']); ?></td>
                                                <td class="text-right"><?php echo expense_format_rupee($row['amount']); ?></td>
                                                <td><?php echo htmlspecialchars($row['payment_mode']); ?></td>
                                                <td class="actions center-content">
                                                    <div class="action-buttons">
                                                        <a title="Edit" href="expense.php?key=<?php echo md5($row['expense_id']); ?>" class="table-actions"><i class="fa fa-pencil"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php
                                            $i++;
                                        }
                                    }
                                    if ($i === 1) {
                                        echo '<tr><td colspan="9" class="text-center">No extra expenses recorded yet.</td></tr>';
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

        <script src="./javascripts/jquery.dataTables1.13.7.min.js"></script>
        <script src="./javascripts/dataTables1.13.7.buttons.min.js"></script>
        <script src="./javascripts/jszip.min.js"></script>
        <script src="./javascripts/buttons.html5.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function() {
                if ($('#expense_table tbody tr:first td').length === 9) {
                    $('#expense_table').DataTable({
                        dom: 'Bfrtip',
                        order: [[0, 'asc']],
                        buttons: [{
                            extend: 'excel',
                            text: 'Export'
                        }]
                    });
                }
            });
            $(window).load(function() {
                $('.loading-page').hide();
            });
        </script>
</body>

</html>
