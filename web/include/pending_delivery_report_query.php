<?php
/**
 * Shared query helpers for Pending Delivery Report (screen + Excel export).
 * Pending = not cancelled AND (status < 8 OR latest delivery is partial).
 */

if (!function_exists('pdr_text')) {
	function pdr_text($val)
	{
		if ($val === null || $val === false) {
			return '';
		}
		$val = (string) $val;
		if (function_exists('mb_convert_encoding')) {
			$val = mb_convert_encoding($val, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
		}
		return $val;
	}
}

if (!function_exists('pdr_status_label')) {
	function pdr_status_label($status, $delivery_type = '', $delivered_packages = 0, $total_packages = 0)
	{
		if ((string) $delivery_type === 'partial') {
			$del = (int) $delivered_packages;
			$tot = (int) $total_packages;
			if ($tot > 0) {
				return 'Partially Delivered (' . $del . ' of ' . $tot . ' packages)';
			}
			return 'Partially Delivered';
		}
		$label = get_trans_status($status);
		return $label ? $label : 'Unknown';
	}
}

if (!function_exists('pdr_build_where')) {
	function pdr_build_where($from_mysql, $to_mysql, $customers, $statuses, $modes, $origins, $destinations, $payment_modes)
	{
		$where = "STR_TO_DATE(t.grn_date, '%d-%m-%Y') >= '$from_mysql'
			AND STR_TO_DATE(t.grn_date, '%d-%m-%Y') <= '$to_mysql'
			AND " . ew_sql_not_cancelled_booking('t.booking_status');

		// Base pending rule: not fully delivered
		$pending_core = "(
			t.status IS NULL OR t.status = '' OR CAST(t.status AS UNSIGNED) < 8
			OR (
				CAST(t.status AS UNSIGNED) = 8
				AND COALESCE((
					SELECT lsl.delivery_type
					FROM transaction_status_log lsl
					WHERE lsl.grn_no = t.grn_no AND lsl.to_status = '8'
					ORDER BY lsl.sheet_id DESC
					LIMIT 1
				), '') = 'partial'
			)
		)";
		$where .= " AND $pending_core";

		if ($customers != '') {
			$cust_list = implode(',', array_map('intval', explode(',', $customers)));
			if ($cust_list != '') {
				$where .= " AND t.consigner IN ($cust_list)";
			}
		}
		if ($modes != '') {
			$mode_list = implode(',', array_map('intval', explode(',', $modes)));
			if ($mode_list != '') {
				$where .= " AND t.mode_of_transportation IN ($mode_list)";
			}
		}
		if ($origins != '') {
			$origin_list = implode(',', array_map('intval', explode(',', $origins)));
			if ($origin_list != '') {
				$where .= " AND t.origin IN ($origin_list)";
			}
		}
		if ($destinations != '') {
			$dest_list = implode(',', array_map('intval', explode(',', $destinations)));
			if ($dest_list != '') {
				$where .= " AND t.destination IN ($dest_list)";
			}
		}
		if ($payment_modes != '') {
			$pm_list = implode(',', array_map('intval', explode(',', $payment_modes)));
			if ($pm_list != '') {
				$where .= " AND t.mode_of_consignment IN ($pm_list)";
			}
		}

		if ($statuses != '') {
			$status_parts = array_filter(array_map('trim', explode(',', $statuses)), 'strlen');
			$status_ints = array();
			$want_partial = false;
			foreach ($status_parts as $part) {
				if ($part === 'partial') {
					$want_partial = true;
				} elseif (ctype_digit($part) && (int) $part >= 1 && (int) $part <= 7) {
					$status_ints[] = (int) $part;
				}
			}
			$status_ints = array_values(array_unique($status_ints));
			$or = array();
			if (!empty($status_ints)) {
				$or[] = 'CAST(t.status AS UNSIGNED) IN (' . implode(',', $status_ints) . ')';
			}
			if ($want_partial) {
				$or[] = "(
					CAST(t.status AS UNSIGNED) = 8
					AND COALESCE((
						SELECT lsl.delivery_type
						FROM transaction_status_log lsl
						WHERE lsl.grn_no = t.grn_no AND lsl.to_status = '8'
						ORDER BY lsl.sheet_id DESC
						LIMIT 1
					), '') = 'partial'
				)";
			}
			if (!empty($or)) {
				$where .= ' AND (' . implode(' OR ', $or) . ')';
			}
		}

		return $where;
	}
}

if (!function_exists('pdr_fetch_rows')) {
	function pdr_fetch_rows($conn, $from_mysql, $to_mysql, $customers, $statuses, $modes, $origins, $destinations, $payment_modes)
	{
		$all_tables_q = mysqli_query($conn, 'SELECT * FROM transaction_tbls');
		$table_list = array();
		if ($all_tables_q) {
			while ($tbl_row = mysqli_fetch_assoc($all_tables_q)) {
				$table_list[] = $tbl_row['table_name'];
			}
		}

		$union_queries = array();
		foreach ($table_list as $tbl_name) {
			$t_trans = 'transaction_' . $tbl_name;
			$t_inv = 'transaction_invoice_' . $tbl_name;

			$chk = @mysqli_query($conn, "SELECT 1 FROM `$t_trans` LIMIT 1");
			if (!$chk) {
				continue;
			}

			$has_eway = false;
			$colChk = @mysqli_query($conn, "SHOW COLUMNS FROM `$t_trans` LIKE 'eway_number'");
			if ($colChk && mysqli_num_rows($colChk) > 0) {
				$has_eway = true;
			}
			$eway_no_expr = $has_eway ? 't.eway_number' : "''";
			$eway_exp_expr = $has_eway ? 't.eway_expirydate' : "''";

			$inv_chk = @mysqli_query($conn, "SELECT 1 FROM `$t_inv` LIMIT 1");
			$inv_join = '';
			$pkgs_expr = 'NULL';
			if ($inv_chk) {
				$pkgs_expr = 'agg.no_of_pkge';
				$inv_join = "LEFT JOIN (
					SELECT
						transaction_id,
						SUM(no_of_pkge) AS no_of_pkge
					FROM `$t_inv`
					GROUP BY transaction_id
				) agg ON agg.transaction_id = t.transaction_id";
			}

			$where = pdr_build_where($from_mysql, $to_mysql, $customers, $statuses, $modes, $origins, $destinations, $payment_modes);

			$union_queries[] = "SELECT
					t.transaction_id,
					t.grn_date,
					t.grn_no,
					t.status,
					t.consigner,
					t.origin,
					t.consignee,
					t.destination,
					t.mode_of_transportation,
					t.mode_of_consignment,
					t.truck AS vehicle_number,
					$eway_no_expr AS eway_bill_no,
					$eway_exp_expr AS eway_bill_expiry,
					t.description_of_goods,
					DATEDIFF(CURDATE(), STR_TO_DATE(t.grn_date, '%d-%m-%Y')) AS days_pending,
					$pkgs_expr AS no_of_pkge,
					COALESCE((
						SELECT lsl.delivery_type
						FROM transaction_status_log lsl
						WHERE lsl.grn_no = t.grn_no AND lsl.to_status = '8'
						ORDER BY lsl.sheet_id DESC
						LIMIT 1
					), '') AS delivery_type,
					COALESCE((
						SELECT lsl.delivered_packages
						FROM transaction_status_log lsl
						WHERE lsl.grn_no = t.grn_no AND lsl.to_status = '8'
						ORDER BY lsl.sheet_id DESC
						LIMIT 1
					), 0) AS delivered_packages,
					COALESCE((
						SELECT lsl.total_packages
						FROM transaction_status_log lsl
						WHERE lsl.grn_no = t.grn_no AND lsl.to_status = '8'
						ORDER BY lsl.sheet_id DESC
						LIMIT 1
					), 0) AS log_total_packages
				FROM `$t_trans` t
				$inv_join
				WHERE $where";
		}

		if (empty($union_queries)) {
			return array();
		}

		$final_query = implode(' UNION ALL ', $union_queries) . ' ORDER BY days_pending DESC, grn_date DESC, grn_no DESC';
		$result = @mysqli_query($conn, $final_query);
		if (!$result) {
			return false;
		}

		$rows = array();
		while ($row = mysqli_fetch_assoc($result)) {
			$rows[] = $row;
		}
		return $rows;
	}
}

if (!function_exists('pdr_map_row')) {
	function pdr_map_row($conn, $row, $s_no)
	{
		$pkgs = isset($row['no_of_pkge']) ? (int) $row['no_of_pkge'] : 0;
		$delivered = isset($row['delivered_packages']) ? (int) $row['delivered_packages'] : 0;
		$log_total = isset($row['log_total_packages']) ? (int) $row['log_total_packages'] : 0;
		$total_for_label = $log_total > 0 ? $log_total : $pkgs;
		$delivery_type = isset($row['delivery_type']) ? (string) $row['delivery_type'] : '';

		return array(
			's_no' => $s_no,
			'gr_date' => pdr_text($row['grn_date']),
			'gr_no' => pdr_text($row['grn_no']),
			'status' => (int) $row['status'],
			'status_label' => pdr_text(pdr_status_label($row['status'], $delivery_type, $delivered, $total_for_label)),
			'days_pending' => isset($row['days_pending']) ? (int) $row['days_pending'] : 0,
			'pkgs' => $pkgs > 0 ? (string) $pkgs : '',
			'delivered_pkgs' => ($delivery_type === 'partial' && $delivered > 0) ? (string) $delivered : '',
			'party_name' => pdr_text(@get_client_name($conn, $row['consigner'])),
			'from_city' => pdr_text(@get_city_name($conn, $row['origin'])),
			'consignee' => pdr_text(@get_client_name($conn, $row['consignee'])),
			'to_city' => pdr_text(@get_city_name($conn, $row['destination'])),
			'mode' => pdr_text(@get_mode($conn, $row['mode_of_transportation'])),
			'pymt_mode' => pdr_text(@consignment_mode($conn, $row['mode_of_consignment'])),
			'vehicle_number' => pdr_text($row['vehicle_number']),
			'eway_bill_no' => pdr_text($row['eway_bill_no']),
			'eway_bill_expiry' => pdr_text($row['eway_bill_expiry']),
			'desc_of_goods' => pdr_text($row['description_of_goods']),
		);
	}
}
