<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_schema.php');
require_once('include/expense_functions.php');

expense_ensure_tables($conn);
expense_require_admin();

$from_date = trim($_REQUEST['from_date'] ?? '');
$to_date = trim($_REQUEST['to_date'] ?? '');
$filter_grn = trim($_REQUEST['grn_no'] ?? '');
$filter_category = (int) ($_REQUEST['category_id'] ?? 0);
$c_date = date('d-m-Y');
$month_start = date('01-m-Y');

if ($from_date === '') {
    $from_date = $month_start;
}
if ($to_date === '') {
    $to_date = $c_date;
}

$where = array("e.status = 0");
if ($filter_grn !== '') {
    $where[] = "e.grn_no = '" . mysqli_real_escape_string($conn, $filter_grn) . "'";
}
if ($filter_category > 0) {
    $where[] = 'e.category_id = ' . $filter_category;
}
$where_sql = implode(' AND ', $where);

$rows = array();
$q = mysqli_query($conn, "SELECT e.*, c.category_name, v.vendor_name
    FROM extra_expense e
    LEFT JOIN expense_category c ON c.category_id = e.category_id
    LEFT JOIN expense_vendor v ON v.vendor_id = e.vendor_id
    WHERE $where_sql
    ORDER BY e.grn_no, e.expense_date DESC, e.expense_id DESC");
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        if ($from_date !== '' && $to_date !== '') {
            $ed = DateTime::createFromFormat('d-m-Y', $r['expense_date']);
            $fd = DateTime::createFromFormat('d-m-Y', $from_date);
            $td = DateTime::createFromFormat('d-m-Y', $to_date);
            if ($ed && $fd && $td) {
                if ($ed < $fd || $ed > $td) {
                    continue;
                }
            }
        }
        $rows[] = $r;
    }
}

$gcn_summary = array();
$category_summary = array();
$grand_total = 0;

foreach ($rows as $r) {
    $amt = (float) $r['amount'];
    $grand_total += $amt;
    $grn_key = $r['grn_no'];
    if (!isset($gcn_summary[$grn_key])) {
        $lookup = expense_lookup_grn($conn, $grn_key);
        $gcn_summary[$grn_key] = array(
            'grn_no' => $grn_key,
            'grn_date' => $lookup['status'] == 1 ? ($lookup['data']['grn_date'] ?? '') : '',
            'route' => $lookup['status'] == 1 ? (($lookup['data']['origin'] ?? '') . ' → ' . ($lookup['data']['destination'] ?? '')) : '',
            'estimated_freight' => $lookup['status'] == 1 ? (float) ($lookup['data']['estimated_freight'] ?? 0) : 0,
            'extra_total' => 0,
            'entry_count' => 0,
        );
    }
    $gcn_summary[$grn_key]['extra_total'] += $amt;
    $gcn_summary[$grn_key]['entry_count']++;

    $cat_name = $r['category_name'] ?: 'Unknown';
    if (!isset($category_summary[$cat_name])) {
        $category_summary[$cat_name] = 0;
    }
    $category_summary[$cat_name] += $amt;
}
?>
<!DOCTYPE html>
<html>

<head>
    <?php include('include/title.php'); ?>
    <?php include('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="./stylesheets/buttons.dataTables.min.css">
    <style>
        .expense-summary-box {
            margin-bottom: 20px;
            padding: 14px 16px;
            border: 1px solid #D8DDE5;
            border-radius: 8px;
            background: #F8FAFC;
        }

        .expense-summary-box h4 {
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: 700;
        }

        .expense-summary-total {
            font-size: 18px;
            font-weight: 700;
            color: #1A2744;
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
                            <i class="fa fa-bar-chart"></i> GCN Extra Expense Report
                            <span class="align-right"><i class="fa fa-plus"></i> <a href="expense.php">Add Expense</a></span>
                        </div>
                        <div class="widget-content padded">
                            <form class="form-horizontal" method="get" id="report_form">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">From Date :</label>
                                            <?php echo ew_date_input(array(
                                                'id' => 'from_date',
                                                'name' => 'from_date',
                                                'value' => $from_date,
                                                'readonly' => true,
                                            )); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">To Date :</label>
                                            <?php echo ew_date_input(array(
                                                'id' => 'to_date',
                                                'name' => 'to_date',
                                                'value' => $to_date,
                                                'readonly' => true,
                                            )); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">GCN No :</label>
                                            <input type="text" name="grn_no" class="form-control" value="<?php echo htmlspecialchars($filter_grn); ?>" placeholder="All GCNs" autocomplete="off" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Category :</label>
                                            <select name="category_id" class="form-control">
                                                <option value="">All Categories</option>
                                                <?php
                                                $cat_q = mysqli_query($conn, 'SELECT category_id, category_name FROM expense_category WHERE status=0 ORDER BY category_name');
                                                while ($cat_q && ($cat_row = mysqli_fetch_assoc($cat_q))) {
                                                    $sel = ($filter_category === (int) $cat_row['category_id']) ? ' selected' : '';
                                                    echo '<option value="' . (int) $cat_row['category_id'] . '"' . $sel . '>' . htmlspecialchars($cat_row['category_name']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-action" style="margin-bottom:20px;">
                                    <button type="submit" class="btn btn-primary">Search</button>
                                    <a href="expense_report.php" class="btn btn-default-outline">Reset</a>
                                </div>
                            </form>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="expense-summary-box">
                                        <h4>Total Extra Expense</h4>
                                        <div class="expense-summary-total">₹ <?php echo expense_format_rupee($grand_total); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="expense-summary-box">
                                        <h4>By Category</h4>
                                        <?php if (empty($category_summary)) { ?>
                                            <div>No data for selected filters.</div>
                                        <?php } else {
                                            foreach ($category_summary as $cat => $total) {
                                                echo '<div>' . htmlspecialchars($cat) . ': <strong>₹ ' . expense_format_rupee($total) . '</strong></div>';
                                            }
                                        } ?>
                                    </div>
                                </div>
                            </div>

                            <h4 style="margin:20px 0 10px;font-weight:700;">GCN-wise Summary</h4>
                            <table class="table table-bordered table-striped" id="gcn_summary_table">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>GCN No</th>
                                        <th>GRN Date</th>
                                        <th>Route</th>
                                        <th>Est. Freight (₹)</th>
                                        <th>Extra Paid (₹)</th>
                                        <th>Combined (₹)</th>
                                        <th>Entries</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sn = 1;
                                    foreach ($gcn_summary as $g) {
                                        $combined = $g['estimated_freight'] + $g['extra_total'];
                                    ?>
                                        <tr>
                                            <td class="text-center"><?php echo $sn; ?></td>
                                            <td><a href="expense_report.php?grn_no=<?php echo urlencode($g['grn_no']); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"><?php echo htmlspecialchars($g['grn_no']); ?></a></td>
                                            <td><?php echo htmlspecialchars($g['grn_date']); ?></td>
                                            <td><?php echo htmlspecialchars($g['route']); ?></td>
                                            <td class="text-right"><?php echo expense_format_rupee($g['estimated_freight']); ?></td>
                                            <td class="text-right"><?php echo expense_format_rupee($g['extra_total']); ?></td>
                                            <td class="text-right"><strong><?php echo expense_format_rupee($combined); ?></strong></td>
                                            <td class="text-center"><?php echo (int) $g['entry_count']; ?></td>
                                        </tr>
                                    <?php
                                        $sn++;
                                    }
                                    if ($sn === 1) {
                                        echo '<tr><td colspan="8" class="text-center">No records for selected filters.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>

                            <h4 style="margin:24px 0 10px;font-weight:700;">Expense Details</h4>
                            <table class="table table-bordered table-striped" id="expense_detail_table">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Expense No</th>
                                        <th>Date</th>
                                        <th>GCN No</th>
                                        <th>Category</th>
                                        <th>Paid To</th>
                                        <th>Amount (₹)</th>
                                        <th>Payment</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $dn = 1;
                                    foreach ($rows as $r) {
                                    ?>
                                        <tr>
                                            <td class="text-center"><?php echo $dn; ?></td>
                                            <td><?php echo htmlspecialchars($r['expense_no']); ?></td>
                                            <td><?php echo htmlspecialchars($r['expense_date']); ?></td>
                                            <td><?php echo htmlspecialchars($r['grn_no']); ?></td>
                                            <td><?php echo htmlspecialchars($r['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($r['vendor_name']); ?></td>
                                            <td class="text-right"><?php echo expense_format_rupee($r['amount']); ?></td>
                                            <td><?php echo htmlspecialchars($r['payment_mode']); ?></td>
                                            <td><?php echo htmlspecialchars($r['description']); ?></td>
                                        </tr>
                                    <?php
                                        $dn++;
                                    }
                                    if ($dn === 1) {
                                        echo '<tr><td colspan="9" class="text-center">No records for selected filters.</td></tr>';
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
                ['#gcn_summary_table', '#expense_detail_table'].forEach(function(sel) {
                    var cols = $(sel + ' tbody tr:first td').length;
                    var headerCols = $(sel + ' thead th').length;
                    if (cols === headerCols && cols > 1) {
                        $(sel).DataTable({
                            dom: 'Bfrtip',
                            paging: true,
                            buttons: [{
                                extend: 'excel',
                                text: 'Export'
                            }]
                        });
                    }
                });
            });
            $(window).load(function() {
                $('.loading-page').hide();
            });
        </script>
</body>

</html>
