<?php

require_once __DIR__ . '/elitewave_pdf_letterhead.php';

function pending_payments_ledger_build_pdf_html($conn, $filters, $ledger)
{
    $client = $ledger['client'] ?: array('name' => '—', 'gst_no' => '', 'contact_no' => '', 'address' => '');
    $summary = $ledger['summary'];
    $lines = $ledger['lines'];

    $period = $filters['from_date'] . ' to ' . $filters['to_date'];
    $generated = date('d-m-Y H:i');

    $html = elitewave_pdf_letterhead_open($conn, 'Client Ledger Statement', 'Without narration · Date, Particulars, Dr/Cr', true, true);

    $html .= '
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 10px;font-size:8.5pt;">
<tr>
	<td width="55%" style="border:1px solid #021659;padding:8px 10px;vertical-align:top;">
		<div style="font-size:8pt;color:#021659;font-weight:bold;margin-bottom:2px;">ACCOUNT OF</div>
		<div style="font-size:10pt;font-weight:bold;color:#000;">' . htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') . '</div>';
    if (!empty($client['address'])) {
        $html .= '<div style="font-size:8pt;margin-top:3px;line-height:1.3;">' . htmlspecialchars($client['address'], ENT_QUOTES, 'UTF-8') . '</div>';
    }
    if (!empty($client['gst_no'])) {
        $html .= '<div style="font-size:8pt;margin-top:3px;"><b>GSTIN</b> ' . htmlspecialchars($client['gst_no'], ENT_QUOTES, 'UTF-8') . '</div>';
    }
    if (!empty($client['contact_no'])) {
        $html .= '<div style="font-size:8pt;"><b>Phone</b> ' . htmlspecialchars($client['contact_no'], ENT_QUOTES, 'UTF-8') . '</div>';
    }
    $html .= '
	</td>
	<td width="45%" style="border:1px solid #021659;border-left:none;padding:8px 10px;vertical-align:top;">
		<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:8.5pt;">
		<tr>
			<td style="border:0;padding:2px 0;width:42%;font-weight:bold;color:#021659;">Statement period</td>
			<td style="border:0;padding:2px 0;">' . htmlspecialchars($period, ENT_QUOTES, 'UTF-8') . '</td>
		</tr>
		<tr>
			<td style="border:0;padding:2px 0;font-weight:bold;color:#021659;">Generated on</td>
			<td style="border:0;padding:2px 0;">' . htmlspecialchars($generated, ENT_QUOTES, 'UTF-8') . '</td>
		</tr>
		<tr>
			<td style="border:0;padding:2px 0;font-weight:bold;color:#021659;">Opening balance</td>
			<td style="border:0;padding:2px 0;font-weight:bold;">₹ ' . htmlspecialchars($summary['opening_balance'], ENT_QUOTES, 'UTF-8') . '</td>
		</tr>
		<tr>
			<td style="border:0;padding:2px 0;font-weight:bold;color:#021659;">Closing balance</td>
			<td style="border:0;padding:2px 0;font-weight:bold;">₹ ' . htmlspecialchars($summary['closing_balance'], ENT_QUOTES, 'UTF-8') . '</td>
		</tr>
		</table>
	</td>
</tr>
</table>';

    $html .= '
<p style="font-size:7.5pt;color:#334155;margin:0 0 8px;line-height:1.35;">
Debit = invoices / debit notes (amount receivable). Credit = payments / credit notes / TDS / advances (amount settled). Balance = running receivable.
</p>';

    $th = 'border:1px solid #021659;background:#021659;color:#fff;padding:5px 4px;font-size:7.5pt;font-weight:bold;';
    $td = 'border:1px solid #C5D3E0;padding:4px 4px;font-size:7.5pt;vertical-align:top;';

    $html .= '
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;">
<thead>
<tr>
	<th style="' . $th . 'width:11%;text-align:center;">Date</th>
	<th style="' . $th . 'width:40%;text-align:left;">Particulars</th>
	<th style="' . $th . 'width:13%;text-align:left;">Ref. No.</th>
	<th style="' . $th . 'width:12%;text-align:right;">Debit (₹)</th>
	<th style="' . $th . 'width:12%;text-align:right;">Credit (₹)</th>
	<th style="' . $th . 'width:12%;text-align:right;">Balance (₹)</th>
</tr>
</thead>
<tbody>';

    $i = 0;
    foreach ($lines as $line) {
        $i++;
        $is_open = !empty($line['is_opening']);
        $is_close = !empty($line['is_closing']);
        $bold = ($is_open || $is_close);
        $bg = $bold ? '#E8EEF6' : (($i % 2 === 0) ? '#F8FAFC' : '#FFFFFF');
        $fw = $bold ? 'font-weight:bold;' : '';
        $html .= '<tr>';
        $html .= '<td style="' . $td . 'background:' . $bg . ';' . $fw . 'text-align:center;">' . htmlspecialchars($line['display_date'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td style="' . $td . 'background:' . $bg . ';' . $fw . '">' . htmlspecialchars($line['particulars'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td style="' . $td . 'background:' . $bg . ';' . $fw . '">' . htmlspecialchars($line['ref'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td style="' . $td . 'background:' . $bg . ';' . $fw . 'text-align:right;">' . htmlspecialchars($line['debit'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td style="' . $td . 'background:' . $bg . ';' . $fw . 'text-align:right;">' . htmlspecialchars($line['credit'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td style="' . $td . 'background:' . $bg . ';' . $fw . 'text-align:right;">' . htmlspecialchars($line['balance'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '</tr>';
    }

    /* Period totals row */
    $html .= '<tr>';
    $html .= '<td colspan="3" style="' . $td . 'background:#021659;color:#fff;font-weight:bold;text-align:right;">Period totals</td>';
    $html .= '<td style="' . $td . 'background:#021659;color:#fff;font-weight:bold;text-align:right;">' . htmlspecialchars($summary['total_debit'], ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '<td style="' . $td . 'background:#021659;color:#fff;font-weight:bold;text-align:right;">' . htmlspecialchars($summary['total_credit'], ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '<td style="' . $td . 'background:#021659;color:#fff;font-weight:bold;text-align:right;">' . htmlspecialchars($summary['closing_balance'], ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '</tr>';

    $html .= '</tbody></table>';

    $html .= '
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:12px;font-size:8pt;">
<tr>
	<td width="33%" style="border:1px solid #C5D3E0;padding:8px;background:#F8FAFC;">
		<div style="color:#64748B;font-size:7.5pt;">Total Debit</div>
		<div style="font-size:11pt;font-weight:bold;color:#021659;">₹ ' . htmlspecialchars($summary['total_debit'], ENT_QUOTES, 'UTF-8') . '</div>
	</td>
	<td width="34%" style="border:1px solid #C5D3E0;border-left:none;padding:8px;background:#F8FAFC;">
		<div style="color:#64748B;font-size:7.5pt;">Total Credit</div>
		<div style="font-size:11pt;font-weight:bold;color:#021659;">₹ ' . htmlspecialchars($summary['total_credit'], ENT_QUOTES, 'UTF-8') . '</div>
	</td>
	<td width="33%" style="border:1px solid #C5D3E0;border-left:none;padding:8px;background:#E8EEF6;">
		<div style="color:#64748B;font-size:7.5pt;">Closing Balance (Receivable)</div>
		<div style="font-size:11pt;font-weight:bold;color:#B91C1C;">₹ ' . htmlspecialchars($summary['closing_balance'], ENT_QUOTES, 'UTF-8') . '</div>
	</td>
</tr>
</table>';

    $html .= '
<p style="font-size:7pt;color:#64748B;margin:10px 0 0;line-height:1.35;">
This is a computer-generated account statement. Positive balance means amount receivable from the customer.
Invoices raise the balance (debit); payments, credit notes and TDS reduce it (credit).
</p>';

    $html .= elitewave_pdf_letterhead_close(true);
    return $html;
}

function pending_payments_ledger_pdf_money($n)
{
    return number_format((float) $n, 2, '.', ',');
}

/** Expanded GCN lines for each invoice — same columns as the on-screen expand. */
function pending_payments_ledger_build_gcn_pdf_html($conn, $filters, $ledger)
{
    require_once __DIR__ . '/bill_wise_functions.php';

    $client = $ledger['client'] ?: array('name' => '—', 'gst_no' => '', 'contact_no' => '', 'address' => '');
    $bw_filters = array(
        'from_date' => $filters['from_date'],
        'to_date' => $filters['to_date'],
        'from_mysql' => $filters['from_mysql'],
        'to_mysql' => $filters['to_mysql'],
        'customers' => (string) (int) ($filters['customer_id'] ?? 0),
    );
    $report = bill_wise_fetch($conn, $bw_filters);
    $customer = null;
    $cid = (int) ($filters['customer_id'] ?? 0);
    foreach (($report['customers'] ?? array()) as $c) {
        if ((int) ($c['id'] ?? 0) === $cid) {
            $customer = $c;
            break;
        }
    }
    $invoices = $customer['invoices'] ?? array();

    $period = $filters['from_date'] . ' to ' . $filters['to_date'];
    $html = elitewave_pdf_letterhead_open($conn, 'Client Ledger — GCN detail', '', true, true);
    $html .= '
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 8px;font-size:8pt;">
<tr>
	<td style="border:1px solid #021659;padding:6px 8px;">
		<div style="font-size:7.5pt;color:#021659;font-weight:bold;">ACCOUNT OF</div>
		<div style="font-size:10pt;font-weight:bold;">' . htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') . '</div>
		<div style="margin-top:2px;">Period ' . htmlspecialchars($period, ENT_QUOTES, 'UTF-8') . '</div>
	</td>
</tr>
</table>';

    if ($invoices === array()) {
        $html .= '<p style="font-size:9pt;">No invoices in this period.</p>';
        $html .= elitewave_pdf_letterhead_close(true);
        return $html;
    }

    $th = 'border:1px solid #021659;background:#E8EEF6;color:#021659;padding:3px 2px;font-size:6.5pt;font-weight:bold;';
    $td = 'border:1px solid #C5D3E0;padding:2px 2px;font-size:6.5pt;vertical-align:top;';
    $heads = array('S.No', 'T/A', 'GCN No.', 'GCN Date', 'Package', 'Consignor', 'Station', 'Ship To', 'Inv No', 'Freight', 'D.Tempo', 'DC', 'Weight', 'Rate', 'per', 'Amount');
    $col_widths = array(
        'S.No' => '3%', 'T/A' => '6%', 'GCN No.' => '11%', 'GCN Date' => '7%', 'Package' => '5%',
        'Consignor' => '7%', 'Station' => '5%', 'Ship To' => '7%',
        'Inv No' => '7%', 'Freight' => '8%', 'D.Tempo' => '6%', 'DC' => '4%', 'Weight' => '6%',
        'Rate' => '6%', 'per' => '3%', 'Amount' => '10%',
    );
    $text_wrap_cols = array(5, 7); // Consignor, Ship To

    foreach ($invoices as $inv) {
        $gcns = $inv['gcns'] ?? array();
        $html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:8px 0 2px;font-size:8pt;">';
        $html .= '<tr><td style="border:1px solid #021659;background:#021659;color:#fff;padding:4px 6px;">';
        $html .= '<b>' . htmlspecialchars($inv['date_short'] ?: $inv['date'], ENT_QUOTES, 'UTF-8') . '</b> &nbsp; ';
        $html .= htmlspecialchars($inv['ledger_name'], ENT_QUOTES, 'UTF-8') . ' &nbsp; ';
        $html .= '<b>' . htmlspecialchars($inv['voucher_no'], ENT_QUOTES, 'UTF-8') . '</b> &nbsp; ';
        $html .= count($gcns) . ' GCN(s) &nbsp; Invoice ₹ ' . pending_payments_ledger_pdf_money($inv['total']);
        $html .= ' &nbsp; Received ₹ ' . pending_payments_ledger_pdf_money($inv['received']);
        $html .= ' &nbsp; Balance ₹ ' . pending_payments_ledger_pdf_money($inv['balance']);
        $html .= '</td></tr></table>';

        $html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 6px;table-layout:fixed;width:100%;"><colgroup>';
        foreach ($heads as $h) {
            $w = $col_widths[$h] ?? '5%';
            $html .= '<col style="width:' . $w . ';" />';
        }
        $html .= '</colgroup><thead><tr>';
        foreach ($heads as $h) {
            $align = in_array($h, array('Freight', 'D.Tempo', 'DC', 'Rate', 'Amount'), true) ? 'right' : 'left';
            $html .= '<th style="' . $th . 'text-align:' . $align . ';">' . htmlspecialchars($h, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        $sub = 0.0;
        $wsum = 0.0;
        $n = 0;
        foreach ($gcns as $g) {
            $n++;
            $sub += (float) ($g['amount'] ?? 0);
            $wsum += (float) ($g['weight'] ?? 0);
            $cells = array(
                (string) $n,
                (string) ($g['type'] ?? ''),
                (string) ($g['gr_no'] ?? ''),
                (string) ($g['gr_date_short'] ?: ($g['gr_date'] ?? '')),
                (string) ($g['qty'] ?? ''),
                (string) ($g['consignor'] ?? ''),
                (string) ($g['station'] ?? ''),
                (string) ($g['ship_to'] ?? ''),
                (string) ($g['inv_no'] ?? ''),
                pending_payments_ledger_pdf_money($g['freight'] ?? 0),
                pending_payments_ledger_pdf_money($g['detention_tempo'] ?? 0),
                pending_payments_ledger_pdf_money($g['dc'] ?? 0),
                (string) ($g['weight_label'] ?? $g['weight'] ?? ''),
                pending_payments_ledger_pdf_money($g['rate'] ?? 0),
                (string) ($g['per'] ?? ''),
                pending_payments_ledger_pdf_money($g['amount'] ?? 0),
            );
            $html .= '<tr>';
            foreach ($cells as $i => $val) {
                $align = ($i >= 9 && $i !== 12 && $i !== 14) ? 'right' : 'left';
                $wrap = in_array($i, $text_wrap_cols, true)
                    ? 'word-wrap:break-word;overflow-wrap:break-word;line-height:1.25;'
                    : '';
                $html .= '<td style="' . $td . $wrap . 'text-align:' . $align . ';">' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }
        if ($n === 0) {
            $html .= '<tr><td colspan="16" style="' . $td . '">No GCN lines on this invoice.</td></tr>';
        }
        $html .= '<tr><td colspan="15" style="' . $td . 'text-align:right;font-weight:bold;">Sub total</td>';
        $html .= '<td style="' . $td . 'text-align:right;font-weight:bold;">' . pending_payments_ledger_pdf_money($sub) . '</td></tr>';
        if ((float) ($inv['cgst'] ?? 0) > 0.009) {
            $html .= '<tr><td colspan="15" style="' . $td . 'text-align:right;">Output CGST</td><td style="' . $td . 'text-align:right;">' . pending_payments_ledger_pdf_money($inv['cgst']) . '</td></tr>';
        }
        if ((float) ($inv['sgst'] ?? 0) > 0.009) {
            $html .= '<tr><td colspan="15" style="' . $td . 'text-align:right;">Output SGST</td><td style="' . $td . 'text-align:right;">' . pending_payments_ledger_pdf_money($inv['sgst']) . '</td></tr>';
        }
        if (abs((float) ($inv['round_off'] ?? 0)) > 0.009) {
            $html .= '<tr><td colspan="15" style="' . $td . 'text-align:right;">Round off</td><td style="' . $td . 'text-align:right;">' . pending_payments_ledger_pdf_money($inv['round_off']) . '</td></tr>';
        }
        $html .= '<tr><td colspan="12" style="' . $td . 'font-weight:bold;background:#F8FAFC;">' . count($gcns) . ' GCN(s), ' . number_format($wsum, 2, '.', '') . ' Kgs</td>';
        $html .= '<td colspan="3" style="' . $td . 'text-align:right;font-weight:bold;background:#F8FAFC;">Total</td>';
        $html .= '<td style="' . $td . 'text-align:right;font-weight:bold;background:#F8FAFC;">' . pending_payments_ledger_pdf_money($inv['total']) . '</td></tr>';
        $html .= '</tbody></table>';
    }

    $html .= elitewave_pdf_letterhead_close(true);
    return $html;
}
