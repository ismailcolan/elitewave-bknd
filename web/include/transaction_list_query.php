<?php

/** S.No = company GCN sequence (EW360/0207 → 207), not table row index. */
function transaction_gcn_serial_no($row)
{
	if (isset($row['grn_id']) && (int) $row['grn_id'] > 0) {
		return (int) $row['grn_id'];
	}
	$grn = trim((string) ($row['grn_no'] ?? ''));
	if ($grn !== '' && preg_match('/\/(\d+)\s*$/', $grn, $m)) {
		return (int) $m[1];
	}
	return 0;
}

function transaction_list_track_action_html($row)
{
	$grn = trim((string) ($row['grn_no'] ?? ''));
	if ($grn === '') {
		$grn = trim((string) ($row['tracking_code'] ?? ''));
	}
	if ($grn === '') {
		return '<a title="Track unavailable" href="javascript:void(0);" class="table-actions disable_action"><i class="fa fa-map-marker"></i></a>';
	}
	$href = 'track_consignment.php?grn_no=' . rawurlencode($grn);
	return '<a title="Track Consignment" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" class="table-actions btn-track-consignment"><i class="fa fa-map-marker"></i></a>';
}

function transaction_list_booking_tables($conn, $year = 0)
{
	$tables = array();
	$q = mysqli_query($conn, "SHOW TABLES LIKE 'transaction_%'");
	while ($q && ($r = mysqli_fetch_row($q))) {
		if (!preg_match('/^transaction_([1-4])_(\d{4})$/', $r[0], $m)) {
			continue;
		}
		$tblYear = (int) $m[2];
		if ($tblYear < 2000) {
			continue;
		}
		if ($year > 0 && $tblYear !== (int) $year) {
			continue;
		}
		$tables[] = array(
			'name' => $r[0],
			'qtr' => (int) $m[1],
			'year' => $tblYear,
		);
	}
	return $tables;
}

function transaction_list_quarter_for_month($mm)
{
	$mm = (int) $mm;
	if ($mm <= 3) {
		return 1;
	}
	if ($mm <= 6) {
		return 2;
	}
	if ($mm <= 9) {
		return 3;
	}
	return 4;
}

function transaction_list_fetch_rows($conn, $params = array())
{
	$report_type = strtoupper(trim((string) ($params['report_type'] ?? '')));
	$month = trim((string) ($params['month'] ?? ''));
	$date = trim((string) ($params['date'] ?? ''));
	$year = (int) ($params['year'] ?? 0);

	if ($report_type === '' && $month !== '') {
		$report_type = 'MONTHLY';
	}
	if ($report_type === '') {
		$report_type = 'ALL';
	}

	$date_sql = '';
	$year_filter = 0;
	$qtr_filter = 0;
	if ($report_type === 'DAILY' && $date !== '') {
		$date_esc = mysqli_real_escape_string($conn, $date);
		$date_sql = " AND t.grn_date='$date_esc'";
		if (preg_match('/^\d{2}-(\d{2})-(\d{4})$/', $date, $m)) {
			$qtr_filter = transaction_list_quarter_for_month($m[1]);
			$year_filter = (int) $m[2];
		}
	} elseif ($report_type === 'MONTHLY' && $month !== '') {
		$month_esc = mysqli_real_escape_string($conn, $month);
		$date_sql = " AND t.grn_date LIKE '%$month_esc'";
		if (preg_match('/^(\d{2})-(\d{4})$/', $month, $m)) {
			$qtr_filter = transaction_list_quarter_for_month($m[1]);
			$year_filter = (int) $m[2];
		}
	} elseif ($report_type === 'YEARLY') {
		if ($year <= 0) {
			$year = (int) date('Y');
		}
		$year_filter = $year;
		$year_esc = mysqli_real_escape_string($conn, (string) $year);
		$date_sql = " AND t.grn_date LIKE '%-$year_esc'";
	}

	$tables = transaction_list_booking_tables($conn, $year_filter);
	if ($qtr_filter > 0) {
		$filtered = array();
		foreach ($tables as $tbl) {
			if ((int) $tbl['qtr'] === $qtr_filter) {
				$filtered[] = $tbl;
			}
		}
		$tables = $filtered;
	}

	$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
	$company_id = isset($_SESSION['company_id']) ? mysqli_real_escape_string($conn, (string) $_SESSION['company_id']) : '';
	$client_sql = '';
	if ($role !== 'AD' && $company_id !== '') {
		$client_sql = " AND (t.consigner='$company_id' OR t.consignee='$company_id')";
	}

	$all_rows = array();
	foreach ($tables as $tbl) {
		$name = preg_replace('/[^a-zA-Z0-9_]/', '', $tbl['name']);
		if ($name === '') {
			continue;
		}
		$qtr = (int) $tbl['qtr'];
		$yr = (int) $tbl['year'];
		$sql = "SELECT t.*, l.tracking_code
			FROM `$name` t
			LEFT JOIN transaction_log l ON t.transaction_id = l.transaction_id AND t.grn_no = l.grn_no
			WHERE TRIM(COALESCE(t.grn_no, '')) != '' $date_sql $client_sql";
		$result = mysqli_query($conn, $sql);
		if (!$result) {
			continue;
		}
		while ($row = mysqli_fetch_array($result)) {
			$row['list_qtr'] = $qtr;
			$row['list_year'] = $yr;
			$row['list_table'] = $name;
			$all_rows[] = $row;
		}
	}

	usort($all_rows, function ($a, $b) {
		$ta = strtotime(str_replace('/', '-', $a['grn_date'] ?? ''));
		$tb = strtotime(str_replace('/', '-', $b['grn_date'] ?? ''));
		if ($ta === $tb) {
			return strcmp((string) ($b['grn_no'] ?? ''), (string) ($a['grn_no'] ?? ''));
		}
		return ($tb ?: 0) - ($ta ?: 0);
	});

	return $all_rows;
}

function transaction_list_html($conn, $params = array())
{
	$all_rows = transaction_list_fetch_rows($conn, $params);
	$out = '';
	$i = 1;
	foreach ($all_rows as $row) {
		try {
			$out .= transaction_list_render_row($conn, $row, $i);
			$i++;
		} catch (Throwable $e) {
			continue;
		}
	}
	return $out;
}

function transaction_list_mode_cell($conn, $row)
{
	$mode_id = (int) ($row['mode_of_transportation'] ?? 0);
	if ($mode_id <= 0) {
		return '<span class="txn-mode-empty">—</span>';
	}
	$mode_name = function_exists('get_mode') ? trim((string) get_mode($conn, $mode_id)) : '';
	if ($mode_name === '') {
		return '<span class="txn-mode-empty">—</span>';
	}

	return '<span class="txn-mode">' . htmlspecialchars($mode_name, ENT_QUOTES, 'UTF-8') . '</span>';
}

function transaction_list_render_row($conn, $row, $i)
{
	$m1 = (int) ($row['list_qtr'] ?? 0);
	$y = (int) ($row['list_year'] ?? 0);
	if ($m1 <= 0 || $y <= 0) {
		if (!empty($row['list_table']) && preg_match('/^transaction_([1-4])_(\d{4})$/', $row['list_table'], $m)) {
			$m1 = (int) $m[1];
			$y = (int) $m[2];
		}
	}
	$trans_name = 'transaction_' . $m1 . '_' . $y;
	$booking = $row['booking_status'];
	$consignment_mode = $row['mode_of_consignment'];
	$status = $row['status'];
	$remarks = $row['remarks'];
	$cancelled_by = get_user($conn, $row['cancelled_by']);
	$updated_at = $row['updated_at'];

	$pkg_q = mysqli_query($conn, 'SELECT SUM(no_of_pkge) AS pkge FROM transaction_invoice_' . $m1 . '_' . $y . " WHERE transaction_id='" . (int) $row['transaction_id'] . "'");
	$pkg_r = $pkg_q ? mysqli_fetch_array($pkg_q) : array('pkge' => 0);

	$dest_name = get_city_name($conn, $row['destination']);
	$dest_cell = $dest_name !== '' ? '<span class="txn-dest">' . htmlspecialchars($dest_name) . '</span>' : '<span class="txn-dest-empty">—</span>';

	$sno = transaction_gcn_serial_no($row);
	$out_put = '<tr>
			<td class="text-center" data-order="' . $sno . '">' . $sno . '</td>
			<td><span class="txn-gcn-no">' . htmlspecialchars($row['grn_no']) . '</span></td>
			<td><span class="txn-pnr">' . htmlspecialchars($row['tracking_code'] ?? '') . '</span></td>
			<td>' . htmlspecialchars($row['grn_date']) . '</td>
			<td class="text-center">' . (int) ($pkg_r['pkge'] ?? 0) . '</td>
			<td class="col-consignor">' . transaction_list_client_cell($conn, $row['consigner'], $row['consignor_branch_id'] ?? 0) . '</td>
			<td class="col-consignee">' . transaction_list_client_cell($conn, $row['consignee'], $row['consignee_branch_id'] ?? 0) . '</td>
			<td>' . $dest_cell . '</td>
			<td>' . transaction_list_mode_cell($conn, $row) . '</td>
			<td>' . transaction_list_status_badge($booking, $status, ew_transaction_badge_opts_for_row($conn, $row, (int) ($pkg_r['pkge'] ?? 0))) . '</td>
			<td class="actions center-content col-actions">
				<div class="action-buttons txn-action-group">';
	if ($row['book_manual'] == 2) {
		$edit_btn = '<a title="Edit" href="transactions_manual.php?key=' . md5($row['transaction_id']) . '&m=' . $m1 . '&y=' . $y . '" class="table-actions btn-edit" id="' . $row['transaction_id'] . '"><i class="fa fa-pencil"></i></a>';
	} else {
		$edit_btn = '<a title="Edit" href="transactions.php?key=' . md5($row['transaction_id']) . '&m=' . $m1 . '&y=' . $y . '" class="table-actions btn-edit" id="' . $row['transaction_id'] . '"><i class="fa fa-pencil"></i></a>';
	}
	if ((int) $status === 8) {
		if ($row['book_manual'] == 2) {
			$edit_btn = '<a title="Edit Payment / Billing" href="transactions_manual.php?key=' . md5($row['transaction_id']) . '&m=' . $m1 . '&y=' . $y . '" class="table-actions btn-edit" id="' . $row['transaction_id'] . '"><i class="fa fa-pencil"></i></a>';
		} else {
			$edit_btn = '<a title="Edit Payment / Billing" href="transactions.php?key=' . md5($row['transaction_id']) . '&m=' . $m1 . '&y=' . $y . '" class="table-actions btn-edit" id="' . $row['transaction_id'] . '"><i class="fa fa-pencil"></i></a>';
		}
	}
	if (booking_is_gcn_billed($conn, $trans_name, $row['transaction_id'])) {
		$edit_btn = '<a title="Invoiced — edit locked" href="javascript:void(0)" class="table-actions btn-edits disable_action" id="' . $row['transaction_id'] . '" readonly><i class="fa fa-pencil"></i></a>';
	}
	$track_btn = transaction_list_track_action_html($row);
	if ($booking == '1') {
		$out_put .= "
			\t    <a title=\"Info\" href=\"#cancel_grn_popup\" class=\"table-actions show_info_popup\"  data-toggle=\"modal\" data-remarks=\"" . htmlspecialchars((string) $remarks, ENT_QUOTES, 'UTF-8') . '" data-createdby="' . htmlspecialchars((string) $cancelled_by, ENT_QUOTES, 'UTF-8') . '" data-createdat="' . htmlspecialchars((string) $updated_at, ENT_QUOTES, 'UTF-8') . '" id="' . $row['transaction_id'] . '" ><i class="fa fa-exclamation-circle"></i></a>
                    <a title="Edit" href="javascript:void(0)" class="table-actions btn-edits disable_action" id="' . $row['transaction_id'] . '" readonly><i class="fa fa-pencil"></i></a>
                    ' . $track_btn . '
                    <a class="table-actions disable_action"  href="javascript:void(0)" ><i class="fa fa-print"></i></a>
                    <a class="table-actions disable_action" href="javascript:void(0)" data-status="' . $row['status'] . '" title="Invoice" id="' . $row['transaction_id'] . '" readonly><i class="fa fa-file"></i></a>
                    <a title="Cancel" href="javascript:void(0) disable_action" class="table-actions cancel_booking disable_action" id="' . $row['transaction_id'] . '" ><i  class="fa fa-ban"></i></a>
                    <a title="E-way Attachments" href="javascript:void(0) disable_action" class="table-actions btn-eways disable_action" id="' . $row['transaction_id'] . '"><i class="fa fa-paperclip"></i></a>';
	} else {
		if ($consignment_mode == '3') {
			$out_put .= '<a title="Edit" href="javascript:void(0)" class="table-actions btn-edits disable_action" id="' . $row['transaction_id'] . '" readonly><i class="fa fa-pencil"></i></a>';
		} else {
			$out_put .= $edit_btn;
		}
		$out_put .= $track_btn;
		$out_put .= '<a class="table-actions" target="_blank" href="transaction_pdf.php?month=' . $m1 . '&year=' . $y . '&id=' . $row['transaction_id'] . '&copy=original" data-status="' . $row['status'] . '" title="GCN Copy" id="' . $row['transaction_id'] . '"><i class="fa fa-print"></i></a>';
		$out_put .= '<a class="table-actions " target="BLANK" href="gst_invoice_page.php?month=' . $m1 . '&year=' . $y . '&id=' . $row['transaction_id'] . '" data-status="' . $row['status'] . '" title="Invoice" id="' . $row['transaction_id'] . '"><i class="fa fa-file"></i></a>';
	}
	if ($consignment_mode == '1' || $consignment_mode == '4') {
		$restricted = check_invoice_restricted($conn, $row['consignee']);
		$pay_at_book = 0;
	} else {
		$restricted = check_invoice_restricted($conn, $row['consigner']);
		$pay_at_book = 0;
	}
	if ($consignment_mode == '3') {
		$pay_at_book = 1;
	}
	if ($status < 6) {
		$out_put .= '<a title="Cancel" href="#cancel_grn_popup" class="table-actions cancel_booking" id="' . $row['transaction_id'] . '" data-toggle="modal" data-grnid="' . htmlspecialchars($row['grn_no'], ENT_QUOTES, 'UTF-8') . '" data-tabid="' . $trans_name . '"  ><i class="fa fa-ban "></i></a>';
	} else {
		$out_put .= '<a class="table-actions disable_action" href="javascript:void(0)"><i class="fa fa-ban"></i></a>';
	}
	$out_put .= '<a title="E-way Attachments" href="javascript:void(0);" class="table-actions btn-eway" id="' . $row['transaction_id'] . '"><i class="fa fa-paperclip"></i></a>';
	$out_put .= '</div></td></tr>';
	return $out_put;
}

function transaction_status_list_html($conn, $params = array())
{
	$all_rows = transaction_list_fetch_rows($conn, $params);
	$out = '';
	$i = 1;
	foreach ($all_rows as $row) {
		try {
			$out .= transaction_status_list_render_row($conn, $row, $i);
			$i++;
		} catch (Throwable $e) {
			continue;
		}
	}
	return $out;
}

function transaction_status_step_button($status_row, $booking, $step, $class, $title, $trans_name, $row, $extra_attrs = '')
{
	$done = ((int) $status_row >= $step) || $booking == '1';
	$label = $done ? '<i class="fa fa-check"></i>' : (string) $step;
	$classes = 'border ' . $class;
	$id_attr = '';
	if ($done) {
		$classes .= ' show_info_popup';
		$disabled = ' disabled';
	} else {
		$id_attr = ' id="status_popup"';
		$disabled = '';
	}
	return '<button class="' . $classes . '"' . $id_attr . $disabled
		. ' data-status="' . $step . '" data-tabid="' . htmlspecialchars($trans_name, ENT_QUOTES, 'UTF-8')
		. '" data-grnid="' . htmlspecialchars((string) $row['grn_id'], ENT_QUOTES, 'UTF-8')
		. '" data-grnno="' . htmlspecialchars((string) $row['grn_no'], ENT_QUOTES, 'UTF-8')
		. '" data-consignment="' . (int) $row['transaction_id'] . '"'
		. $extra_attrs
		. ' title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">' . $label . '</button>';
}

function transaction_status_list_render_row($conn, $row, $i)
{
	$m1 = (int) ($row['list_qtr'] ?? 0);
	$y = (int) ($row['list_year'] ?? 0);
	$trans_name = 'transaction_' . $m1 . '_' . $y;
	$booking = $row['booking_status'];
	$status = $row['status'];

	$pkg_q = mysqli_query($conn, 'SELECT SUM(no_of_pkge) AS pkge FROM transaction_invoice_' . $m1 . '_' . $y . " WHERE transaction_id='" . (int) $row['transaction_id'] . "'");
	$pkg_r = $pkg_q ? mysqli_fetch_array($pkg_q) : array('pkge' => 0);
	$total_packages = (int) ($pkg_r['pkge'] ?? 0);

	$badge_opts = ew_transaction_badge_opts_for_row($conn, $row, $total_packages);
	$delivery_type = $badge_opts['delivery_type'];
	$delivered_packages = $badge_opts['delivered_packages'];

	$sno = transaction_gcn_serial_no($row);
	$out = '<tr>
		<td class="text-center" data-label="S.No" data-order="' . $sno . '">' . $sno . '</td>
		<td data-label="GCN No"><span class="txn-gcn-no">' . htmlspecialchars($row['grn_no']) . '</span></td>
		<td data-label="GCN Date">' . htmlspecialchars($row['grn_date']) . '</td>
		<td class="text-center" data-label="Pkgs">' . $total_packages . '</td>
		<td data-label="Consignor">' . htmlspecialchars(get_client_name($conn, $row['consigner'])) . '</td>
		<td data-label="Consignee">' . htmlspecialchars(get_client_name($conn, $row['consignee'])) . '</td>
		<td data-label="Destination">' . htmlspecialchars(get_city_name($conn, $row['destination'])) . '</td>
		<td data-label="Status">' . transaction_status_badge($booking, $status, $badge_opts) . '</td>
		<td class="col-steps actions center-content" data-label="Change Status"><div>';
	$out .= '<button class="border booked" disabled title="Consignment Booked"><i class="fa fa-check"></i></button>';
	$out .= transaction_status_step_button($status, $booking, 2, 'picked-up', 'Consignment Picked Up', $trans_name, $row);
	$out .= transaction_status_step_button($status, $booking, 3, 'transit-1', 'In Transit-1', $trans_name, $row);
	$out .= transaction_status_step_button($status, $booking, 4, 'transit-2', 'In Transit-2', $trans_name, $row);
	$out .= transaction_status_step_button($status, $booking, 5, 'transit-3', 'In Transit-3', $trans_name, $row);
	$out .= transaction_status_step_button($status, $booking, 6, 'destination', 'At Destination', $trans_name, $row);
	$out .= transaction_status_step_button($status, $booking, 7, 'out-delivery', 'Out For Delivery', $trans_name, $row);

	$deliver_extra = ' data-total-packages="' . $total_packages . '" data-delivered-packages="' . $delivered_packages . '" data-delivery-type="' . htmlspecialchars($delivery_type, ENT_QUOTES, 'UTF-8') . '"';
	if ($status >= 8 && $delivery_type === 'full') {
		$out .= '<button class="border delivered" disabled data-status="8" data-tabid="' . htmlspecialchars($trans_name, ENT_QUOTES, 'UTF-8') . '" data-grnid="' . htmlspecialchars((string) $row['grn_id'], ENT_QUOTES, 'UTF-8') . '" data-grnno="' . htmlspecialchars((string) $row['grn_no'], ENT_QUOTES, 'UTF-8') . '" data-consignment="' . (int) $row['transaction_id'] . '"' . $deliver_extra . ' title="Delivered Successfully"><i class="fa fa-check"></i></button>';
	} elseif ($status >= 8 && $delivery_type === 'partial') {
		$out .= '<button class="border delivered partial-delivery-button" id="status_popup" data-status="8" data-tabid="' . htmlspecialchars($trans_name, ENT_QUOTES, 'UTF-8') . '" data-grnid="' . htmlspecialchars((string) $row['grn_id'], ENT_QUOTES, 'UTF-8') . '" data-grnno="' . htmlspecialchars((string) $row['grn_no'], ENT_QUOTES, 'UTF-8') . '" data-consignment="' . (int) $row['transaction_id'] . '"' . $deliver_extra . ' title="Change Delivery Status"><i class="fa fa-check"></i></button>';
	} else {
		$out .= transaction_status_step_button($status, $booking, 8, 'delivered' . ($delivery_type === 'partial' ? ' partial-delivery-button' : ''), 'Delivered Successfully', $trans_name, $row, $deliver_extra);
	}

	$out .= '</div>
		<button type="button" class="btn btn-primary mobile-update-status" data-status="' . (int) $status . '" data-tabid="' . htmlspecialchars($trans_name, ENT_QUOTES, 'UTF-8') . '" data-grnid="' . htmlspecialchars((string) $row['grn_id'], ENT_QUOTES, 'UTF-8') . '" data-grnno="' . htmlspecialchars((string) $row['grn_no'], ENT_QUOTES, 'UTF-8') . '" data-consignment="' . (int) $row['transaction_id'] . '">Update Status</button>
		</td></tr>';
	return $out;
}
