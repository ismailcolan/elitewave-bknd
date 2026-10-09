<?php

require_once __DIR__ . '/vehicle_type_helpers.php';
require_once __DIR__ . '/quotation_multi_mode.php';

function quotation_consignor_multi_dest_quote_types()
{
	return array(
		'consignor_multi_destination' => 'Consignor — Multiple Destinations',
	);
}

function quotation_is_consignor_multi_dest_quote_type($quote_type)
{
	$quote_type = trim((string) $quote_type);
	return isset(quotation_consignor_multi_dest_quote_types()[$quote_type]);
}

function quotation_destination_row_total($freight, $doc = 0, $others = 0)
{
	return round((float) $freight, 2);
}

function quotation_ensure_destination_rows_table($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS rate_quotation_destination_rows (
		row_id INT(11) NOT NULL AUTO_INCREMENT,
		quotation_id INT(11) NOT NULL,
		sort_no INT(11) NOT NULL DEFAULT 0,
		destination_city_id INT(11) NOT NULL DEFAULT 0,
		mode_id INT(11) NOT NULL DEFAULT 0,
		vehicle_type_id INT(11) DEFAULT NULL,
		delivery_days VARCHAR(40) DEFAULT NULL,
		freight_charges DECIMAL(14,2) NOT NULL DEFAULT 0,
		doc_charges DECIMAL(14,2) NOT NULL DEFAULT 0,
		others DECIMAL(14,2) NOT NULL DEFAULT 0,
		row_total DECIMAL(14,2) NOT NULL DEFAULT 0,
		PRIMARY KEY (row_id),
		KEY idx_rq_dest_row (quotation_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$chk = @mysqli_query($conn, "SHOW COLUMNS FROM rate_quotation_destination_rows LIKE 'vehicle_text'");
	if (!$chk || mysqli_num_rows($chk) === 0) {
		@mysqli_query($conn, 'ALTER TABLE rate_quotation_destination_rows ADD COLUMN vehicle_text VARCHAR(200) DEFAULT NULL AFTER vehicle_type_id');
	}
}

function quotation_city_state_catalog($conn)
{
	$rows = array();
	$sql = "SELECT c.city_id, c.city_name, c.state AS state_id, s.state_name
		FROM city c
		LEFT JOIN state s ON s.state_id = c.state
		WHERE c.status=0
		ORDER BY s.state_name ASC, c.city_name ASC";
	$q = @mysqli_query($conn, $sql);
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$city = trim((string) ($r['city_name'] ?? ''));
			$state = trim((string) ($r['state_name'] ?? ''));
			$label = $state !== '' ? ($city . ' — ' . $state) : $city;
			$rows[] = array(
				'city_id' => (int) $r['city_id'],
				'state_id' => (int) ($r['state_id'] ?? 0),
				'label' => $label,
			);
		}
	}
	return $rows;
}

function quotation_city_state_label($conn, $city_id)
{
	$city_id = (int) $city_id;
	if ($city_id <= 0) {
		return '—';
	}
	if (function_exists('get_city_state_name')) {
		$lbl = trim((string) get_city_state_name($conn, $city_id));
		return $lbl !== '' ? $lbl : '—';
	}
	return quotation_city_name($conn, $city_id);
}

function quotation_get_destination_rows($conn, $quotation_id)
{
	quotation_ensure_destination_rows_table($conn);
	$quotation_id = (int) $quotation_id;
	$rows = array();
	$q = mysqli_query($conn, "SELECT * FROM rate_quotation_destination_rows WHERE quotation_id='$quotation_id' ORDER BY sort_no ASC, row_id ASC");
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$rows[] = $r;
		}
	}
	return $rows;
}

function quotation_default_destination_rows_for_form()
{
	return array(
		array(
			'destination_city_id' => 0,
			'mode_id' => 0,
			'vehicle_type_id' => 0,
			'vehicle_text' => '',
			'mode_group' => '',
			'delivery_days' => '',
			'freight_charges' => '',
			'doc_charges' => '',
			'others' => '',
			'row_total' => 0,
		),
	);
}

function quotation_hydrate_destination_rows_for_form($conn, $quotation_id)
{
	$stored = quotation_get_destination_rows($conn, $quotation_id);
	if ($stored === array()) {
		return quotation_default_destination_rows_for_form();
	}
	$catalog = quotation_mode_transport_catalog($conn);
	$by_mode = array();
	foreach ($catalog as $m) {
		$by_mode[(int) $m['mode_id']] = $m;
	}
	$out = array();
	foreach ($stored as $row) {
		$mid = (int) ($row['mode_id'] ?? 0);
		$meta = $by_mode[$mid] ?? array('mode_group' => '');
		$out[] = array(
			'destination_city_id' => (int) ($row['destination_city_id'] ?? 0),
			'mode_id' => $mid,
			'mode_group' => trim((string) ($meta['mode_group'] ?? '')),
			'vehicle_type_id' => (int) ($row['vehicle_type_id'] ?? 0),
			'vehicle_text' => trim((string) ($row['vehicle_text'] ?? '')),
			'delivery_days' => $row['delivery_days'] ?? '',
			'freight_charges' => rtrim(rtrim(number_format((float) ($row['freight_charges'] ?? 0), 2, '.', ''), '0'), '.'),
			'doc_charges' => rtrim(rtrim(number_format((float) ($row['doc_charges'] ?? 0), 2, '.', ''), '0'), '.'),
			'others' => rtrim(rtrim(number_format((float) ($row['others'] ?? 0), 2, '.', ''), '0'), '.'),
			'row_total' => (float) ($row['row_total'] ?? 0),
		);
	}
	return $out;
}

function quotation_parse_destination_rows_from_post($conn, $payload)
{
	$cities = $payload['md_city_id'] ?? array();
	if (!is_array($cities)) {
		$cities = array();
	}
	$modes = $payload['md_mode_id'] ?? array();
	$vehicles = $payload['md_vehicle_type_id'] ?? array();
	$train_ids = $payload['md_train_id'] ?? array();
	$flight_ids = $payload['md_flight_id'] ?? array();
	$days = $payload['md_delivery_days'] ?? array();
	$mode_catalog = quotation_mode_transport_catalog($conn);
	$mode_groups = array();
	foreach ($mode_catalog as $m) {
		$mode_groups[(int) $m['mode_id']] = trim((string) ($m['mode_group'] ?? ''));
	}
	$freight = $payload['md_freight'] ?? array();

	$rows = array();
	$n = count($cities);
	for ($i = 0; $i < $n; $i++) {
		$city_id = (int) ($cities[$i] ?? 0);
		$mode_id = (int) ($modes[$i] ?? 0);
		$f = quotation_parse_money_field($freight[$i] ?? 0);
		$row_total = quotation_destination_row_total($f);
		if ($city_id <= 0 && $mode_id <= 0 && $row_total <= 0) {
			continue;
		}
		if ($city_id <= 0) {
			return array('ok' => false, 'message' => 'Select destination (city — state) for each row with charges.');
		}
		if ($mode_id <= 0) {
			return array('ok' => false, 'message' => 'Select mode of transport for each destination row.');
		}
		$mode_group = $mode_groups[$mode_id] ?? '';
		$kind = quotation_mode_source_kind($mode_group);
		$vt_id = 0;
		$vtxt = '';
		if ($kind === 'road') {
			$vt_id = (int) ($vehicles[$i] ?? 0);
		} elseif ($kind === 'train') {
			$tid = (int) ($train_ids[$i] ?? 0);
			$vtxt = $tid > 0 ? ('train:' . $tid) : '';
		} elseif ($kind === 'flight') {
			$fid = (int) ($flight_ids[$i] ?? 0);
			$vtxt = $fid > 0 ? ('flight:' . $fid) : '';
		}
		$rows[] = array(
			'destination_city_id' => $city_id,
			'mode_id' => $mode_id,
			'vehicle_type_id' => $vt_id,
			'vehicle_text' => $vtxt,
			'delivery_days' => trim((string) ($days[$i] ?? '')),
			'freight_charges' => $f,
			'doc_charges' => 0,
			'others' => 0,
			'row_total' => $row_total,
		);
	}
	if ($rows === array()) {
		return array('ok' => false, 'message' => 'Add at least one destination row with city, mode, and charges.');
	}
	$has_charge = false;
	foreach ($rows as $row) {
		if ((float) ($row['row_total'] ?? 0) > 0) {
			$has_charge = true;
			break;
		}
	}
	if (!$has_charge) {
		return array('ok' => false, 'message' => 'Enter freight charges for at least one destination.');
	}
	return array('ok' => true, 'rows' => $rows);
}

function quotation_save_destination_rows($conn, $quotation_id, $rows)
{
	quotation_ensure_destination_rows_table($conn);
	$quotation_id = (int) $quotation_id;
	mysqli_query($conn, "DELETE FROM rate_quotation_destination_rows WHERE quotation_id='$quotation_id'");
	$esc = function ($v) use ($conn) {
		return mysqli_real_escape_string($conn, (string) $v);
	};
	$sort = 0;
	foreach ($rows as $row) {
		$sort += 10;
		$vt = (int) ($row['vehicle_type_id'] ?? 0);
		$vt_sql = $vt > 0 ? "'$vt'" : 'NULL';
		$vtxt_sql = "'" . $esc($row['vehicle_text'] ?? '') . "'";
		mysqli_query($conn, "INSERT INTO rate_quotation_destination_rows (
			quotation_id, sort_no, destination_city_id, mode_id, vehicle_type_id, vehicle_text, delivery_days,
			freight_charges, doc_charges, others, row_total
		) VALUES (
			'$quotation_id', '$sort', '" . (int) $row['destination_city_id'] . "', '" . (int) $row['mode_id'] . "', $vt_sql, $vtxt_sql,
			'" . $esc($row['delivery_days'] ?? '') . "',
			'" . $esc(number_format((float) $row['freight_charges'], 2, '.', '')) . "',
			'" . $esc(number_format((float) $row['doc_charges'], 2, '.', '')) . "',
			'" . $esc(number_format((float) $row['others'], 2, '.', '')) . "',
			'" . $esc(number_format((float) $row['row_total'], 2, '.', '')) . "'
		)");
	}
}

function quotation_destination_rows_taxable_total($rows)
{
	$sum = 0.0;
	foreach ($rows as $row) {
		$sum += (float) ($row['row_total'] ?? 0);
	}
	return round($sum, 2);
}

function quotation_destination_rows_for_display($conn, $quotation_id)
{
	$stored = quotation_get_destination_rows($conn, $quotation_id);
	$modes = quotation_mode_transport_catalog($conn);
	$mode_names = array();
	foreach ($modes as $m) {
		$mode_names[(int) $m['mode_id']] = $m['mode_type'];
	}
	$out = array();
	foreach ($stored as $row) {
		if ((float) ($row['row_total'] ?? 0) <= 0) {
			continue;
		}
		$mid = (int) ($row['mode_id'] ?? 0);
		$row['mode_type'] = $mode_names[$mid] ?? '';
		$row['destination_label'] = quotation_city_state_label($conn, (int) ($row['destination_city_id'] ?? 0));
		$row['vehicle_display'] = quotation_mode_row_vehicle_display($conn, $row);
		$row['delivery_days_label'] = quotation_delivery_days_label($row['delivery_days'] ?? '');
		$out[] = $row;
	}
	return $out;
}

/** Short label for list screens: cities + destination count. */
function quotation_consignor_multi_dest_destinations_summary($conn, $quotation_id)
{
	$rows = quotation_get_destination_rows($conn, (int) $quotation_id);
	$n = count($rows);
	if ($n === 0) {
		return array('label' => '—', 'title' => '');
	}
	$labels = array();
	foreach ($rows as $r) {
		$lbl = quotation_city_state_label($conn, (int) ($r['destination_city_id'] ?? 0));
		if ($lbl !== '—') {
			$labels[] = $lbl;
		}
	}
	$labels = array_values(array_unique($labels));
	$title = implode(', ', $labels);
	if ($n === 1 && $title !== '') {
		return array('label' => $title, 'title' => $title);
	}
	if ($title !== '') {
		$shown = array_slice($labels, 0, 2);
		$label = implode(', ', $shown);
		if (count($labels) > 2) {
			$label .= ' …';
		}
		$label .= ' (' . $n . ' destinations)';
		return array('label' => $label, 'title' => $title);
	}
	return array('label' => $n . ' destination' . ($n === 1 ? '' : 's'), 'title' => '');
}

function quotation_consignor_multi_dest_email_table_html($conn, $quotation_id)
{
	$rows = quotation_destination_rows_for_display($conn, $quotation_id);
	if ($rows === array()) {
		return '';
	}
	$th = 'padding:8px 10px;background:#021659;color:#fff;font-size:13px;font-weight:bold;border:1px solid #021659;text-align:left;';
	$td = 'padding:7px 10px;border:1px solid #cbd5e1;font-size:13px;color:#1a1a1a;vertical-align:top;';
	$html = '<table cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin:12px 0 16px;">'
		. '<tr>'
		. '<th style="' . $th . '">Destination</th>'
		. '<th style="' . $th . '">Mode</th>'
		. '<th style="' . $th . '">Source of transport</th>'
		. '<th style="' . $th . '">Days</th>'
		. '<th style="' . $th . 'text-align:right;">Freight</th>'
		. '</tr>';
	foreach ($rows as $r) {
		$fmt = function ($n) {
			return '&#8377; ' . htmlspecialchars(quotation_format_money_display($n), ENT_QUOTES, 'UTF-8') . ' /-';
		};
		$html .= '<tr>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['destination_label'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['mode_type'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['vehicle_display'] ?? '—', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['delivery_days_label'] ?? '—', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $td . 'text-align:right;">' . $fmt($r['freight_charges'] ?? 0) . '</td>'
			. '</tr>';
	}
	$html .= '</table>';
	return $html;
}

function quotation_render_destination_row_html($conn, $row, $city_catalog, $modes, $vehicle_types, $delivery_days_opts, $form_editable, $trains = array(), $flights = array())
{
	$city_id = (int) ($row['destination_city_id'] ?? 0);
	$mode_id = (int) ($row['mode_id'] ?? 0);
	$mode_group = trim((string) ($row['mode_group'] ?? ''));
	if ($mode_group === '' && $mode_id > 0) {
		foreach ($modes as $m) {
			if ((int) $m['mode_id'] === $mode_id) {
				$mode_group = trim((string) ($m['mode_group'] ?? ''));
				break;
			}
		}
	}
	if ($trains === array()) {
		$trains = quotation_active_trains($conn);
	}
	if ($flights === array()) {
		$flights = quotation_active_flights($conn);
	}
	ob_start();
	?>
	<tr class="md-row" data-mode-group="<?php echo htmlspecialchars($mode_group, ENT_QUOTES, 'UTF-8'); ?>">
		<td>
			<select name="md_city_id[]" class="form-control md-city" <?php echo $form_editable ? '' : 'disabled'; ?>>
				<option value="">City — State</option>
				<?php foreach ($city_catalog as $c) {
					$sel = ($city_id === (int) $c['city_id']) ? ' selected' : '';
					echo '<option value="' . (int) $c['city_id'] . '"' . $sel . '>' . htmlspecialchars($c['label']) . '</option>';
				} ?>
			</select>
		</td>
		<td>
			<select name="md_mode_id[]" class="form-control md-mode" <?php echo $form_editable ? '' : 'disabled'; ?>>
				<option value="">Mode</option>
				<?php foreach ($modes as $m) {
					$sel = ($mode_id === (int) $m['mode_id']) ? ' selected' : '';
					$grp = htmlspecialchars($m['mode_group'] ?? '', ENT_QUOTES, 'UTF-8');
					echo '<option value="' . (int) $m['mode_id'] . '" data-mode-group="' . $grp . '"' . $sel . '>' . htmlspecialchars($m['mode_type']) . '</option>';
				} ?>
			</select>
		</td>
		<td class="md-vehicle-cell">
			<?php echo quotation_source_selects_html('md', $row, $vehicle_types, $trains, $flights, $form_editable, $mode_group); ?>
		</td>
		<td>
			<select name="md_delivery_days[]" class="form-control md-days" <?php echo $form_editable ? '' : 'disabled'; ?>>
				<option value="">Days</option>
				<?php foreach ($delivery_days_opts as $dk => $dl) {
					$sel = (($row['delivery_days'] ?? '') === $dk) ? ' selected' : '';
					echo '<option value="' . htmlspecialchars($dk, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . htmlspecialchars($dl) . '</option>';
				} ?>
			</select>
		</td>
		<td><input type="text" name="md_freight[]" class="form-control md-amt" value="<?php echo htmlspecialchars($row['freight_charges'] ?? ''); ?>" onpaste="return ewNumericPaste(event,this);" <?php echo $form_editable ? '' : 'readonly'; ?> /></td>
		<?php if ($form_editable) { ?>
			<td class="md-row-actions text-center">
				<div class="md-row-action">
					<button type="button" class="md-row-btn is-add btn-md-add-row" title="Add destination row"><i class="fa fa-plus"></i></button>
					<button type="button" class="md-row-btn is-remove btn-md-remove-row" title="Remove row"><i class="fa fa-minus"></i></button>
				</div>
			</td>
		<?php } ?>
	</tr>
	<?php
	return ob_get_clean();
}
