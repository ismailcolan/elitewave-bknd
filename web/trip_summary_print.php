<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/trip_summary_helpers.php');

trip_summary_require_access();
trip_summary_ensure_schema($conn);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$saved = trip_summary_load($conn, $id);
if (!$saved) {
	header('Location: trip_summary_list.php');
	exit;
}
$h = $saved['header'];
$lines = $saved['lines'];
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<title>Trip Summary — <?php echo htmlspecialchars($h['sheet_no']); ?></title>
	<style>
		body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #111; margin: 24px; }
		.print-toolbar { margin-bottom: 16px; }
		.print-toolbar button { padding: 8px 16px; cursor: pointer; }
		h1 { font-size: 18px; margin: 0 0 4px; }
		.sub { color: #555; margin-bottom: 16px; }
		.meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
		.meta td { padding: 6px 10px; border: 1px solid #ddd; }
		.meta td.lbl { width: 18%; background: #f5f5f5; font-weight: 700; }
		table.items { width: 100%; border-collapse: collapse; }
		table.items th, table.items td { border: 1px solid #ddd; padding: 7px 8px; font-size: 12px; }
		table.items th { background: #0A1E3D; color: #fff; text-transform: uppercase; font-size: 11px; }
		table.items td.num { text-align: center; }
		.totals { margin-top: 14px; display: flex; gap: 24px; justify-content: flex-end; font-size: 14px; }
		@media print { .print-toolbar { display: none; } body { margin: 12px; } }
	</style>
</head>

<body>
	<div class="print-toolbar">
		<button type="button" onclick="window.print();">Print</button>
		<button type="button" onclick="window.close();">Close</button>
	</div>

	<h1>Trip Summary Sheet</h1>
	<div class="sub">Sheet <?php echo htmlspecialchars($h['sheet_no']); ?> &nbsp;|&nbsp; Date: <?php echo htmlspecialchars($h['sheet_date']); ?> &nbsp;|&nbsp; Status: <?php echo htmlspecialchars($h['status']); ?></div>

	<table class="meta">
		<tr>
			<td class="lbl">Origin</td>
			<td><?php echo htmlspecialchars($h['origin_name']); ?></td>
			<td class="lbl">Destination</td>
			<td><?php echo htmlspecialchars($h['destination_name']); ?></td>
		</tr>
		<tr>
			<td class="lbl">Mode</td>
			<td><?php echo htmlspecialchars($h['mode_label']); ?></td>
			<td class="lbl">Source / Transport</td>
			<td><?php echo htmlspecialchars($h['source_display'] ?: '—'); ?></td>
		</tr>
	</table>

	<table class="items">
		<thead>
			<tr>
				<th>Sl</th>
				<th>GCN No</th>
				<th>GCN Date</th>
				<th>Consignee</th>
				<th>Consignor</th>
				<th>Pkgs</th>
				<th>Loaded</th>
				<th>Remarks</th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($lines)): ?>
				<tr><td colspan="8" class="num">No GCN lines.</td></tr>
			<?php else: ?>
				<?php foreach ($lines as $i => $line): ?>
					<tr>
						<td class="num"><?php echo $i + 1; ?></td>
						<td><strong><?php echo htmlspecialchars($line['grn_no']); ?></strong></td>
						<td><?php echo htmlspecialchars($line['grn_date']); ?></td>
						<td><?php echo htmlspecialchars($line['consignee_name']); ?></td>
						<td><?php echo htmlspecialchars($line['consignor_name']); ?></td>
						<td class="num"><?php echo (int) $line['no_of_packages']; ?></td>
						<td class="num"><?php echo (int) $line['loaded_no']; ?></td>
						<td><?php echo htmlspecialchars($line['remarks'] ?: '—'); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<div class="totals">
		<div>Total GCNs: <strong><?php echo (int) $h['total_gcn']; ?></strong></div>
		<div>Total Packages: <strong><?php echo (int) $h['total_packages']; ?></strong></div>
		<div>Total Loaded: <strong><?php echo (int) $h['total_loaded']; ?></strong></div>
	</div>
</body>

</html>
