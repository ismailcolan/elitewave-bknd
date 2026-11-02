<?php

require_once __DIR__ . '/elitewave_pdf_letterhead.php';

/**
 * Build letterhead list-export PDF HTML and stream download.
 *
 * @param mysqli $conn
 * @param array  $payload  headers, rows, search, optional series_filter / type_filter
 * @param array  $options  title, subtitle, file_prefix, col_align callable, col_extra callable, colgroup_html
 */
function ew_list_table_export_pdf_send($conn, array $payload, array $options)
{
	$headers = isset($payload['headers']) && is_array($payload['headers']) ? $payload['headers'] : array();
	$rows = isset($payload['rows']) && is_array($payload['rows']) ? $payload['rows'] : array();

	if (empty($headers) || empty($rows)) {
		http_response_code(400);
		header('Content-Type: text/plain; charset=UTF-8');
		exit('No data to export.');
	}
	if (count($rows) > 500) {
		http_response_code(400);
		header('Content-Type: text/plain; charset=UTF-8');
		exit('Too many rows.');
	}

	$esc = static function ($v) {
		return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
	};

	$title = (string) ($options['title'] ?? 'List');
	$subtitle = (string) ($options['subtitle'] ?? 'List export');
	$file_prefix = preg_replace('/[^a-z0-9_\-]+/i', '_', (string) ($options['file_prefix'] ?? 'list'));
	$colgroup = (string) ($options['colgroup_html'] ?? '');
	$col_align = $options['col_align'] ?? static function () {
		return 'left';
	};
	$col_extra = $options['col_extra'] ?? static function () {
		return '';
	};

	$navy = EW_LH_NAVY;
	$cell_pad = 'padding:9px 10px;';
	$th_base = $cell_pad . 'background:' . $navy . ';color:#fff;font-size:9pt;font-weight:bold;border:1px solid ' . $navy . ';vertical-align:middle;line-height:1.35;';
	$td_base = $cell_pad . 'border:1px solid #d1d9e6;font-size:9pt;color:#000;vertical-align:middle;line-height:1.4;';

	$html = elitewave_pdf_letterhead_open($conn, $title, $subtitle, true, true);

	$html .= '<p class="ew-doc-secondary" style="margin:0 0 10px;">Generated '
		. $esc(date('d-m-Y H:i'))
		. ' · ' . count($rows) . ' row(s)';

	$series = trim((string) ($payload['series_filter'] ?? ''));
	if ($series !== '') {
		$html .= ' · Series: ' . $esc($series);
	}
	$type_filter = trim((string) ($payload['type_filter_label'] ?? ''));
	if ($type_filter !== '') {
		$html .= ' · Type: ' . $esc($type_filter);
	}
	$search = trim((string) ($payload['search'] ?? ''));
	if ($search !== '') {
		$html .= ' · Search: ' . $esc($search);
	}
	$html .= '</p>';

	$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0;border:1px solid #d1d9e6;">';
	if ($colgroup !== '') {
		$html .= $colgroup;
	}
	$html .= '<thead><tr>';
	foreach ($headers as $hi => $h) {
		$align = is_callable($col_align) ? $col_align($hi) : 'left';
		$html .= '<th align="' . $esc($align) . '" style="' . $th_base . '">' . $esc($h) . '</th>';
	}
	$html .= '</tr></thead><tbody>';
	$row_idx = 0;
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$bg = ($row_idx % 2 === 1) ? 'background:#f4f7fb;' : 'background:#fff;';
		$row_idx++;
		$html .= '<tr>';
		foreach ($row as $i => $cell) {
			$align = is_callable($col_align) ? $col_align($i) : 'left';
			$extra = is_callable($col_extra) ? $col_extra($i) : '';
			$html .= '<td align="' . $esc($align) . '" style="' . $td_base . $extra . $bg . '">' . $esc($cell) . '</td>';
		}
		$html .= '</tr>';
	}
	$html .= '</tbody></table>';
	$html .= elitewave_pdf_letterhead_close(false);

	require_once dirname(__DIR__) . '/vendor/autoload.php';

	$mpdf = new \Mpdf\Mpdf(array(
		'mode' => 'utf-8',
		'format' => 'A4-L',
		'default_font' => 'freesans',
		'margin_left' => 8,
		'margin_right' => 8,
		'margin_top' => 8,
		'margin_bottom' => 12,
	));
	$mpdf->SetTitle($title);
	$mpdf->SetAuthor('EliteWave360 Logistics');
	$mpdf->WriteHTML($html);
	$filename = $file_prefix . '_' . date('Y-m-d') . '.pdf';
	$pdfBinary = $mpdf->Output('', 'S');

	while (ob_get_level() > 0) {
		ob_end_clean();
	}

	header('Content-Type: application/pdf');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Content-Length: ' . strlen($pdfBinary));
	header('Cache-Control: no-store, no-cache, must-revalidate');
	echo $pdfBinary;
	exit;
}

function ew_list_export_pdf_read_payload()
{
	$raw = '';
	if (isset($_POST['payload']) && (string) $_POST['payload'] !== '') {
		$raw = (string) $_POST['payload'];
	} else {
		$raw = file_get_contents('php://input');
		if ($raw === false) {
			$raw = '';
		}
	}
	$payload = json_decode($raw, true);
	if (!is_array($payload)) {
		http_response_code(400);
		header('Content-Type: text/plain; charset=UTF-8');
		exit('Invalid request.');
	}
	return $payload;
}
