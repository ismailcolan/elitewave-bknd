<?php

function gst_invoice_pdf_css()
{
    return '
<style>
body{ font-family:freesans; font-size:7.5pt; }
table{ border-collapse:collapse; }
td{ font-family:freesans; font-size:7.5pt; vertical-align:middle; }
b,strong{ font-weight:bold; }
.pdf-amt{ white-space:nowrap; text-align:right; }
</style>';
}

/** Money for mPDF tables — normal commas; nobr prevents wrap at the comma. */
function gst_invoice_pdf_format_money($amount)
{
    $formatted = number_format((float) $amount, 2, '.', ',');

    return '<nobr>' . htmlspecialchars($formatted, ENT_QUOTES, 'UTF-8') . '</nobr>';
}

/** Browser download filename for single-GCN GST vs Other (HROTH) PDFs. */
function gst_invoice_pdf_download_filename($is_other_invoice, $invoice_no, $is_proforma = false)
{
    if ($is_proforma) {
        return $is_other_invoice ? 'Proforma-Invoice.pdf' : 'Proforma-Tax-Invoice.pdf';
    }
    $safe = str_replace('/', '-', trim((string) $invoice_no));
    $safe = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $safe);
    if ($is_other_invoice) {
        return 'Invoice-' . $safe . '.pdf';
    }

    return 'Tax-Invoice-' . $safe . '.pdf';
}

function gst_invoice_train_type_label($train_type, $other_train_name = '')
{
    $train_type = trim((string) $train_type);
    $other_train_name = trim((string) $other_train_name);
    if ($train_type === '1') {
        return 'Rajdhani Express';
    }
    if ($train_type === '2') {
        return $other_train_name !== '' ? $other_train_name : 'Others';
    }
    if ($other_train_name !== '') {
        return $other_train_name;
    }
    return $train_type;
}

/**
 * Map booking transport mode to invoice Vehicle / Train / Airlines type fields.
 */
function gst_invoice_resolve_transport_types($conn, $trow)
{
    $mode_id = (int) ($trow['mode_of_transportation'] ?? 0);
    $vehicle = trim((string) ($trow['vehicle_type'] ?? ''));
    $ftl = trim((string) ($trow['ftl_type'] ?? ''));
    $truck = trim((string) ($trow['truck'] ?? ''));
    $train = gst_invoice_train_type_label($trow['train_type'] ?? '', $trow['other_train_name'] ?? '');
    $airlines = '';

    $road_modes = array(3, 4, 7, 8);
    if (in_array($mode_id, $road_modes, true)) {
        if ($vehicle === '') {
            $vehicle = $ftl !== '' ? $ftl : $truck;
        }
    } elseif ($mode_id === 2) {
        if ($train === '') {
            $train = $ftl !== '' ? $ftl : $vehicle;
        }
    } elseif ($mode_id === 1) {
        $airlines = $vehicle !== '' ? $vehicle : (function_exists('get_mode') ? get_mode($conn, $mode_id) : '');
    } else {
        if ($vehicle === '') {
            $vehicle = $ftl !== '' ? $ftl : $truck;
        }
    }

    return array(
        'vehicle_type' => $vehicle,
        'premium_train_type' => $train,
        'premium_airlines_type' => $airlines,
    );
}

function gst_invoice_merge_transport_types($current, $next)
{
    foreach (array('vehicle_type', 'premium_train_type', 'premium_airlines_type') as $key) {
        if (($current[$key] ?? '') === '' && ($next[$key] ?? '') !== '') {
            $current[$key] = $next[$key];
        }
    }
    return $current;
}

function gst_invoice_pdf_label_value_row($label, $value)
{
    return '
            <tr>
                <td width="170" style="border:0;padding:0;font-weight:bold;line-height:18px;font-size:9pt;white-space:nowrap;">' . htmlspecialchars($label) . '</td>
                <td width="15" style="border:0;padding:0;font-weight:bold;line-height:18px;font-size:9pt;">:</td>
                <td style="border:0;padding:0;line-height:18px;font-size:9pt;">' . htmlspecialchars($value) . '</td>
            </tr>';
}

function gst_invoice_pdf_transport_block_html($invoice_no, $transport_types)
{
    $transport_types = array_merge(array(
        'vehicle_type' => '',
        'premium_train_type' => '',
        'premium_airlines_type' => '',
    ), $transport_types ?: array());

    $html = gst_invoice_pdf_label_value_row('Invoice Number', $invoice_no);
    $html .= gst_invoice_pdf_label_value_row('Vehicle Type', $transport_types['vehicle_type']);
    $html .= gst_invoice_pdf_label_value_row('Premium Train Type', $transport_types['premium_train_type']);
    $html .= gst_invoice_pdf_label_value_row('Premium Airlines Type', $transport_types['premium_airlines_type']);

    return $html;
}

/**
 * @return 'none'|'intra'|'inter'
 */
function gst_invoice_pdf_resolve_tax_mode(array $row, $legacy_same_state)
{
    $gst_amount = (float) ($row['gst_amount'] ?? 0);
    if ($gst_amount <= 0.009) {
        return 'none';
    }

    $gst_type = strtolower(trim((string) ($row['gst_type'] ?? '')));
    if (in_array($gst_type, array('exempt', 'non_gst'), true)) {
        return 'none';
    }

    $igst = (float) ($row['igst_amount'] ?? 0);
    $cgst = (float) ($row['cgst_amount'] ?? 0);
    $sgst = (float) ($row['sgst_amount'] ?? 0);

    if ($gst_type === 'inter' || $igst > 0.009) {
        return 'inter';
    }
    if ($gst_type === 'intra' || $cgst > 0.009 || $sgst > 0.009) {
        return 'intra';
    }

    $origin_state = (int) ($row['state'] ?? 0);
    $dest_state = (int) ($row['con_state'] ?? 0);
    if ($origin_state > 0 && $dest_state > 0) {
        return ($origin_state === $dest_state) ? 'intra' : 'inter';
    }

    return $legacy_same_state ? 'intra' : 'inter';
}

function gst_invoice_pdf_prepare_gst_display(array $row, $tax_mode, $gst_rate_full)
{
    $gst_amount = (float) ($row['gst_amount'] ?? 0);
    $gst_rate = (float) ($row['gst_rate'] ?? 0);
    $out = array(
        'cgst_rate' => 0.0,
        'sgst_rate' => 0.0,
        'igst_rate' => 0.0,
        'cgst_amt' => 0.0,
        'sgst_amt' => 0.0,
        'igst_amt' => 0.0,
    );

    if ($tax_mode === 'none') {
        return $out;
    }

    if ($tax_mode === 'inter') {
        $out['igst_rate'] = (float) ($row['igst_rate'] ?? 0);
        if ($out['igst_rate'] <= 0 && $gst_rate > 0) {
            $out['igst_rate'] = $gst_rate;
        }
        if ($out['igst_rate'] <= 0) {
            $out['igst_rate'] = (float) $gst_rate_full;
        }
        $out['igst_amt'] = (float) ($row['igst_amount'] ?? 0);
        if ($out['igst_amt'] <= 0) {
            $out['igst_amt'] = round($gst_amount, 2);
        }

        return $out;
    }

    $out['cgst_rate'] = (float) ($row['cgst_rate'] ?? 0);
    $out['sgst_rate'] = (float) ($row['sgst_rate'] ?? 0);
    if ($out['cgst_rate'] <= 0 && $gst_rate > 0) {
        $out['cgst_rate'] = $gst_rate / 2;
        $out['sgst_rate'] = $gst_rate / 2;
    }
    if ($out['cgst_rate'] <= 0) {
        $half = (float) $gst_rate_full / 2;
        $out['cgst_rate'] = $half;
        $out['sgst_rate'] = $half;
    }
    $out['cgst_amt'] = (float) ($row['cgst_amount'] ?? 0);
    $out['sgst_amt'] = (float) ($row['sgst_amount'] ?? 0);
    if ($out['cgst_amt'] <= 0 && $out['sgst_amt'] <= 0) {
        $out['cgst_amt'] = round($gst_amount / 2, 2);
        $out['sgst_amt'] = round($gst_amount / 2, 2);
    }

    return $out;
}

function gst_invoice_pdf_format_rate($rate)
{
    $rate = (float) $rate;
    if ($rate <= 0) {
        return '';
    }

    return rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');
}

function gst_invoice_pdf_gst_totals_html($tax_mode, $cgst_rate, $sgst_rate, $igst_rate, $cgst_amt, $sgst_amt, $igst_amt, $round_off, $grand_total)
{
    $html = '';
    if ($tax_mode === 'intra') {
        $cgst_rate_txt = gst_invoice_pdf_format_rate($cgst_rate);
        $sgst_rate_txt = gst_invoice_pdf_format_rate($sgst_rate);
        if ($cgst_rate_txt !== '' || $cgst_amt > 0) {
            $html .= '
        <tr>
            <td width="70%" style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">OUTPUT- CGST @ ' . $cgst_rate_txt . '%</td>
            <td width="30%" style="width:30%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . number_format($cgst_amt, 2) . '</td>
        </tr>';
        }
        if ($sgst_rate_txt !== '' || $sgst_amt > 0) {
            $html .= '
        <tr>
            <td width="70%" style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">OUTPUT- SGST @ ' . $sgst_rate_txt . '%</td>
            <td width="30%" style="width:30%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . number_format($sgst_amt, 2) . '</td>
        </tr>';
        }
    } elseif ($tax_mode === 'inter') {
        $igst_rate_txt = gst_invoice_pdf_format_rate($igst_rate);
        if ($igst_rate_txt !== '' || $igst_amt > 0) {
            $html .= '
        <tr>
            <td width="70%" style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">OUTPUT- IGST @ ' . $igst_rate_txt . '%</td>
            <td width="30%" style="width:30%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . number_format($igst_amt, 2) . '</td>
        </tr>';
        }
    }

    $html .= '
        <tr>
            <td width="70%" style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">ROUND OFF</td>
            <td width="30%" style="width:30%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . ($round_off < 0 ? '(-)' : '') . number_format(abs($round_off), 2) . '</td>
        </tr>
        <tr>
            <td width="70%" style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:2px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">GRAND TOTAL</td>
            <td width="30%" style="width:30%;border-right:1px solid #000;border-bottom:1px solid #000;padding:2px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . number_format($grand_total, 2) . '</td>
        </tr>';

    return $html;
}

/**
 * Payment-screen charge lines for invoice PDF (non-zero amounts only).
 *
 * @return list<array{label:string,amount:float}>
 */
function gst_invoice_pdf_charge_summary_lines(array $row, $single_gcn_invoice = false)
{
    require_once __DIR__ . '/billing_functions.php';

    $lines = array();
    $push = function ($label, $amount) use (&$lines) {
        $amount = round((float) $amount, 2);
        if ($amount <= 0) {
            return;
        }
        $lines[] = array(
            'label' => $label,
            'amount' => $amount,
        );
    };

    if ($single_gcn_invoice) {
        $push('Freight Charges', billing_freight_from_row($row, null));
        $push('Doc.Charges', $row['doc_amount'] ?? 0);
    }

    $push('Mamul Charges', $row['mamul_charge'] ?? 0);
    $push('Vehicle Halting Charges', $row['vehicle_halting_charge'] ?? 0);

    $loading = (float) ($row['vehicle_loading_unloading'] ?? 0);
    if ($loading <= 0) {
        $loading = (float) ($row['loading_unloading_amount'] ?? 0);
    }
    $push('Vehicle Loading / Unloading', $loading);
    $push('Local Pickup & Deliver Charges', $row['cartage_amount'] ?? 0);
    $push('Rajdhani Charges', $row['rajdhani_charges'] ?? 0);
    $push('Other charges', $row['other_charge_amount'] ?? 0);
    $push('Crane / Fork Lift', $row['crane_fork_lift_amount'] ?? 0);
    $push('COD', $row['cod_amount'] ?? 0);
    $push('FOV', $row['fov_amount'] ?? 0);
    $push('Labour Handling', $row['labour_handling_amount'] ?? 0);
    $push('Octroi', $row['octroi_amount'] ?? 0);

    return $lines;
}

function gst_invoice_pdf_resolve_taxable_value(array $row, $stored_taxable)
{
    $stored_taxable = round((float) $stored_taxable, 2);
    if ($stored_taxable > 0) {
        return $stored_taxable;
    }

    require_once __DIR__ . '/billing_functions.php';
    require_once __DIR__ . '/gst_tax_functions.php';

    $loading = (float) ($row['vehicle_loading_unloading'] ?? 0);
    if ($loading <= 0) {
        $loading = (float) ($row['loading_unloading_amount'] ?? 0);
    }

    $charges = array(
        'frieght_amount' => billing_freight_from_row($row, null),
        'doc_amount' => $row['doc_amount'] ?? 0,
        'mamul_charge' => $row['mamul_charge'] ?? 0,
        'vehicle_halting_charge' => $row['vehicle_halting_charge'] ?? 0,
        'vehicle_loading_unloading' => $loading,
        'other_amount' => $row['other_charge_amount'] ?? 0,
        'rajdhani_charges' => $row['rajdhani_charges'] ?? 0,
        'loading_unload_chrg' => $row['loading_unloading_amount'] ?? 0,
        'crane_forklift_chrg' => $row['crane_fork_lift_amount'] ?? 0,
        'cod_amount' => $row['cod_amount'] ?? 0,
        'fov_amount' => $row['fov_amount'] ?? 0,
        'cartage_amount' => $row['cartage_amount'] ?? 0,
        'labour_amount' => $row['labour_handling_amount'] ?? 0,
    );

    return (float) gst_tax_calc_taxable_from_charges($charges);
}

/** Show summary when header charges are not fully reflected in the GCN line table total. */
function gst_invoice_pdf_charge_summary_needed(array $row, $line_table_total, $taxable_value, $single_gcn_invoice = false)
{
    $line_table_total = round((float) $line_table_total, 2);
    $taxable_value = round((float) $taxable_value, 2);

    if ($single_gcn_invoice) {
        $lines = gst_invoice_pdf_charge_summary_lines($row, true);
        if ($lines !== array()) {
            return true;
        }

        return $taxable_value > 0;
    }

    if ($taxable_value > 0 && abs($taxable_value - $line_table_total) > 0.009) {
        return true;
    }

    $extras = array(
        $row['mamul_charge'] ?? 0,
        $row['vehicle_halting_charge'] ?? 0,
        $row['vehicle_loading_unloading'] ?? 0,
        $row['loading_unloading_amount'] ?? 0,
        $row['cartage_amount'] ?? 0,
        $row['rajdhani_charges'] ?? 0,
        $row['other_charge_amount'] ?? 0,
        $row['crane_fork_lift_amount'] ?? 0,
        $row['cod_amount'] ?? 0,
        $row['fov_amount'] ?? 0,
        $row['labour_handling_amount'] ?? 0,
        $row['octroi_amount'] ?? 0,
    );
    foreach ($extras as $amount) {
        if (round((float) $amount, 2) > 0) {
            return true;
        }
    }

    return false;
}

function gst_invoice_pdf_render_charge_summary_table(array $row, $stored_taxable, $line_table_total, $single_gcn_invoice = false)
{
    if (!gst_invoice_pdf_charge_summary_needed($row, $line_table_total, $stored_taxable, $single_gcn_invoice)) {
        return '';
    }

    $lines = gst_invoice_pdf_charge_summary_lines($row, $single_gcn_invoice);
    if ($lines === array()) {
        return '';
    }

    $taxable_value = gst_invoice_pdf_resolve_taxable_value($row, $stored_taxable);
    if ($taxable_value <= 0) {
        return '';
    }
    $taxable_label = 'Taxable Value';
    $summary_font = $single_gcn_invoice ? '7.5pt' : '8pt';
    $summary_pad = $single_gcn_invoice ? '2px 5px' : '3px 6px';

    $html = '
<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    border="0"
    style="
        width:100%;
        border-collapse:collapse;
        font-family:freesans;
        font-size:' . $summary_font . ';
        margin-top:-1px;
        border-left:1px solid #000;
        border-right:1px solid #000;
        border-bottom:1px solid #000;
    "
>
<tr>
    <td colspan="2" style="border-bottom:1px solid #000;padding:' . $summary_pad . ';font-weight:bold;background-color:#f2f2f2;">
        Charge Summary
    </td>
</tr>';

    foreach ($lines as $line) {
        $html .= '
<tr>
    <td style="border-bottom:1px solid #000;padding:' . $summary_pad . ';width:75%;">' . htmlspecialchars($line['label'], ENT_QUOTES, 'UTF-8') . '</td>
    <td style="border-bottom:1px solid #000;padding:' . $summary_pad . ';width:25%;text-align:right;white-space:nowrap;">' . gst_invoice_pdf_format_money($line['amount']) . '</td>
</tr>';
    }

    $html .= '
<tr>
    <td style="padding:' . $summary_pad . ';font-weight:bold;width:75%;">' . htmlspecialchars($taxable_label, ENT_QUOTES, 'UTF-8') . '</td>
    <td style="padding:' . $summary_pad . ';font-weight:bold;width:25%;text-align:right;white-space:nowrap;">' . gst_invoice_pdf_format_money($taxable_value) . '</td>
</tr>
</table>';

    return $html;
}

function gst_invoice_pdf_summary_section_html($invoice_no, $transport_types, $tax_mode, $cgst_rate, $sgst_rate, $igst_rate, $cgst_amt, $sgst_amt, $igst_amt, $round_off, $grand_total)
{
    return '
<table width="100%" cellpadding="0" cellspacing="0" border="1" style="width:100%;border-collapse:collapse;font-family:freesans;font-size:9pt;margin-top:-1px;border:1px solid #000;">
<tr>
    <td width="70%" style="width:70%;padding:5px 9px;vertical-align:top;border-left:1px solid #000;border-right:1px solid #000;border-top:1px solid #000;border-bottom:1px solid #000;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;font-family:freesans;font-size:9pt;">'
        . gst_invoice_pdf_transport_block_html($invoice_no, $transport_types)
        . '</table>
    </td>
    <td width="30%" style="width:30%;padding:0;vertical-align:top;border:1px solid #000;border-left:1px solid #000;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;font-family:freesans;font-size:9pt;">'
        . gst_invoice_pdf_gst_totals_html($tax_mode, $cgst_rate, $sgst_rate, $igst_rate, $cgst_amt, $sgst_amt, $igst_amt, $round_off, $grand_total)
        . '</table>
    </td>
</tr>
</table>';
}
