<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_invoice_gst_audit_functions.php');

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$filters = array(
    'from_date' => trim($_REQUEST['from_date'] ?? ''),
    'to_date' => trim($_REQUEST['to_date'] ?? ''),
    'invoice_no' => trim($_REQUEST['invoice_no'] ?? 'GTA/0059/26-27'),
);

if (isset($_REQUEST['export']) && $_REQUEST['export'] === 'csv') {
    $rows = billing_invoice_gst_audit_mismatch_rows($conn, $filters);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="invoice_gst_mismatch_audit.csv"');
    echo billing_invoice_gst_audit_to_csv($rows);
    exit;
}

$rows = billing_invoice_gst_audit_mismatch_rows($conn, $filters);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tax invoice GST audit</title>
    <style>
        body { font-family: sans-serif; margin: 24px; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 14px; }
        th { background: #f4f4f4; }
        .note { max-width: 720px; color: #444; line-height: 1.5; }
    </style>
</head>
<body>
    <h1>Issued invoices — IGST with intrastate place of supply</h1>
    <p class="note">Lists <strong>final</strong> tax invoices where IGST was charged but the issuing company state matches the Bill To place of supply (supplier vs Bill To GSTIN / handover rule). For review only; records are not changed automatically.</p>
    <form method="get">
        <label>Invoice no <input type="text" name="invoice_no" value="<?php echo htmlspecialchars($filters['invoice_no']); ?>"></label>
        <label>From <input type="text" name="from_date" placeholder="dd-mm-yyyy" value="<?php echo htmlspecialchars($filters['from_date']); ?>"></label>
        <label>To <input type="text" name="to_date" placeholder="dd-mm-yyyy" value="<?php echo htmlspecialchars($filters['to_date']); ?>"></label>
        <button type="submit">Search</button>
        <button type="submit" name="export" value="csv">Download CSV</button>
    </form>
    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Date</th>
                <th>GCN</th>
                <th>Taxable</th>
                <th>CGST</th>
                <th>SGST</th>
                <th>IGST</th>
                <th>Grand total</th>
                <th>Bill To GSTIN</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)) { ?>
            <tr><td colspan="9">No matching issued invoices in this filter.</td></tr>
        <?php } else { foreach ($rows as $r) { ?>
            <tr>
                <td><?php echo htmlspecialchars($r['invoice_no']); ?></td>
                <td><?php echo htmlspecialchars($r['invoice_date']); ?></td>
                <td><?php echo htmlspecialchars($r['grn_no']); ?></td>
                <td><?php echo number_format($r['taxable_value'], 2); ?></td>
                <td><?php echo number_format($r['cgst_amount'], 2); ?></td>
                <td><?php echo number_format($r['sgst_amount'], 2); ?></td>
                <td><?php echo number_format($r['igst_amount'], 2); ?></td>
                <td><?php echo number_format($r['grand_total'], 2); ?></td>
                <td><?php echo htmlspecialchars($r['bill_to_gstin']); ?></td>
            </tr>
        <?php }} ?>
        </tbody>
    </table>
</body>
</html>