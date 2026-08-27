<?php

function gst_invoice_pdf_css()
{
    return '
<style>
body{ font-family:freesans; font-size:7.5pt; }
table{ border-collapse:collapse; }
td{ font-family:freesans; font-size:7.5pt; vertical-align:middle; }
b,strong{ font-weight:bold; }
</style>';
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

function gst_invoice_pdf_gst_totals_html($is_same_state, $cgst_rate, $sgst_rate, $igst_rate, $cgst_amt, $sgst_amt, $igst_amt, $round_off, $grand_total)
{
    $html = '';
    if ($is_same_state) {
        $html .= '
        <tr>
            <td width="70%" style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">OUTPUT- CGST @ ' . $cgst_rate . '%</td>
            <td width="30%" style="width:30%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . number_format($cgst_amt, 2) . '</td>
        </tr>
        <tr>
            <td width="70%" style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">OUTPUT- SGST @ ' . $sgst_rate . '%</td>
            <td width="30%" style="width:30%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . number_format($sgst_amt, 2) . '</td>
        </tr>';
    } else {
        $html .= '
        <tr>
            <td width="70%" style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">OUTPUT- IGST @ ' . $igst_rate . '%</td>
            <td width="30%" style="width:30%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . number_format($igst_amt, 2) . '</td>
        </tr>';
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

function gst_invoice_pdf_summary_section_html($invoice_no, $transport_types, $is_same_state, $cgst_rate, $sgst_rate, $igst_rate, $cgst_amt, $sgst_amt, $igst_amt, $round_off, $grand_total)
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
        . gst_invoice_pdf_gst_totals_html($is_same_state, $cgst_rate, $sgst_rate, $igst_rate, $cgst_amt, $sgst_amt, $igst_amt, $round_off, $grand_total)
        . '</table>
    </td>
</tr>
</table>';
}
