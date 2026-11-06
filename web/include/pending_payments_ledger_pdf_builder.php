<?php

require_once __DIR__ . '/elitewave_pdf_letterhead.php';

function pending_payments_ledger_build_pdf_html($conn, $filters, $ledger)
{
    $client = $ledger['client'] ?: array('name' => '—', 'gst_no' => '', 'contact_no' => '', 'address' => '');
    $summary = $ledger['summary'];
    $lines = $ledger['lines'];

    $period = $filters['from_date'] . ' to ' . $filters['to_date'];
    $generated = date('d-m-Y H:i');

    $html = elitewave_pdf_letterhead_open($conn, 'Client Ledger Statement', 'Account statement · Receivables', true, true);

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
