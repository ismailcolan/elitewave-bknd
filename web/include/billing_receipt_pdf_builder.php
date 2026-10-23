<?php

require_once __DIR__ . '/elitewave_pdf_letterhead.php';

function billing_receipt_pdf_esc($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function billing_receipt_pdf_th($text, $align = 'left')
{
    $navy = EW_LH_NAVY;
    return '<th align="' . $align . '" style="padding:6px 8px;background:' . $navy . ';color:#fff;font-size:9pt;font-weight:bold;border:1px solid ' . $navy . ';">'
        . billing_receipt_pdf_esc($text) . '</th>';
}

function billing_receipt_pdf_td($text, $align = 'left', $bold = false)
{
    $fw = $bold ? 'font-weight:bold;' : '';
    return '<td align="' . $align . '" style="padding:6px 8px;border:1px solid #cbd5e1;font-size:9pt;color:#000;' . $fw . '">'
        . billing_receipt_pdf_esc($text) . '</td>';
}

function billing_receipt_pdf_meta_cell($label, $value)
{
    $navy = EW_LH_NAVY;
    return '<td style="padding:8px 10px;border:1px solid #e2e8f0;background:#fff;vertical-align:top;width:50%;">'
        . '<div style="font-size:7.5pt;font-weight:bold;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin:0 0 4px;">'
        . billing_receipt_pdf_esc($label) . '</div>'
        . '<div style="font-size:9.5pt;font-weight:bold;color:' . $navy . ';">'
        . billing_receipt_pdf_esc($value !== '' && $value !== null ? $value : '—') . '</div>'
        . '</td>';
}

function billing_receipt_build_pdf_html($conn, array $view)
{
    $title = 'Payment Receipt';
    $html = elitewave_pdf_letterhead_open($conn, $title, 'Receipt Copy', true, true);

    $meta = array(
        array('Receipt no', $view['receipt_no'] ?? '—'),
        array('Receipt date', $view['receipt_date'] ?? '—'),
        array('Receipt type', $view['receipt_type'] ?? '—'),
        array('Customer / payer', $view['party_label'] ?? '—'),
        array('Payment mode', $view['payment_mode'] ?? '—'),
        array('Reference', $view['payment_ref'] ?? '—'),
    );
    if (!empty($view['payment_ref_date'])) {
        $meta[] = array('Reference date', $view['payment_ref_date']);
    }
    if (($view['bank_account_label'] ?? '—') !== '—') {
        $meta[] = array('Bank account', $view['bank_account_label']);
    }
    if (trim((string) ($view['remarks'] ?? '')) !== '') {
        $meta[] = array('Remarks', $view['remarks']);
    }

    $html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 12px;">';
    $half = (int) ceil(count($meta) / 2);
    for ($i = 0; $i < $half; $i++) {
        $left = $meta[$i] ?? array('', '');
        $right = $meta[$i + $half] ?? array('', '');
        $html .= '<tr>';
        $html .= billing_receipt_pdf_meta_cell($left[0] ?? '', $left[1] ?? '');
        $html .= billing_receipt_pdf_meta_cell($right[0] ?? '', $right[1] ?? '');
        $html .= '</tr>';
    }
    $html .= '</table>';

    $kpi_mid = ($view['receipt_type_key'] ?? '') === 'against_invoice'
        ? ($view['invoice_open_total'] ?? '0.00')
        : ($view['total_amount'] ?? '0.00');
    $kpi_mid_label = ($view['receipt_type_key'] ?? '') === 'against_invoice' ? 'Invoice total' : 'Receipt amount';

    $html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 14px;">';
    $html .= '<tr>';
    $html .= '<td style="width:33%;padding:10px;border:1px solid #cbd5e1;background:#f8fafc;text-align:center;">'
        . '<div style="font-size:8pt;color:#64748b;font-weight:bold;">AMOUNT RECEIVED</div>'
        . '<div style="font-size:11pt;font-weight:bold;color:#021659;">' . billing_receipt_pdf_esc($view['total_amount'] ?? '0.00') . '</div></td>';
    $html .= '<td style="width:33%;padding:10px;border:1px solid #cbd5e1;background:#f8fafc;text-align:center;">'
        . '<div style="font-size:8pt;color:#64748b;font-weight:bold;">TDS DEDUCTION</div>'
        . '<div style="font-size:11pt;font-weight:bold;color:#021659;">' . billing_receipt_pdf_esc($view['tds_total'] ?? '0.00') . '</div></td>';
    $html .= '<td style="width:34%;padding:10px;border:1px solid #cbd5e1;background:#ecfdf5;text-align:center;">'
        . '<div style="font-size:8pt;color:#64748b;font-weight:bold;">' . billing_receipt_pdf_esc(strtoupper($kpi_mid_label)) . '</div>'
        . '<div style="font-size:11pt;font-weight:bold;color:#15803d;">' . billing_receipt_pdf_esc($kpi_mid) . '</div></td>';
    $html .= '</tr></table>';

    if (!empty($view['lines'])) {
        $html .= '<p class="ew-doc-h2" style="margin-top:0;">Invoice allocation</p>';
        $html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 14px;">';
        $html .= '<tr>'
            . billing_receipt_pdf_th('Inv. no')
            . billing_receipt_pdf_th('Date')
            . billing_receipt_pdf_th('Inv. receipt', 'right')
            . billing_receipt_pdf_th('TDS', 'right')
            . billing_receipt_pdf_th('Received', 'right')
            . billing_receipt_pdf_th('Balance', 'right')
            . '</tr>';
        foreach ($view['lines'] as $ln) {
            $html .= '<tr>';
            $html .= billing_receipt_pdf_td($ln['invoice_no'] ?? '');
            $html .= billing_receipt_pdf_td($ln['invoice_date'] ?? '');
            $html .= billing_receipt_pdf_td($ln['balance_open'] ?? '', 'right');
            $html .= billing_receipt_pdf_td($ln['tds_amount'] ?? '', 'right');
            $html .= billing_receipt_pdf_td($ln['receive_now'] ?? '', 'right');
            $html .= billing_receipt_pdf_td($ln['balance_remain'] ?? '', 'right');
            $html .= '</tr>';
        }
        $html .= '</table>';
    }

    $html .= elitewave_pdf_letterhead_close();
    return $html;
}
