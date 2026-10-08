<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once __DIR__ . '/vendor/autoload.php';

$month          = $_GET['month'];
$year           = $_GET['year'];
$transaction_id = $_GET['id'];
$unique_invoice_no = $_GET['invoice_no'] ?? '';

$query  = "SELECT * FROM transaction_" . $month . "_" . $year . " WHERE transaction_id='" . $transaction_id . "'";
$result = mysqli_query($conn, $query);
$row    = mysqli_fetch_assoc($result);
extract($row);

require_once __DIR__ . '/include/gcn_gst_invoice_helpers.php';
require_once __DIR__ . '/include/gst_invoice_pdf_layout.php';
$transport_types = gst_invoice_resolve_transport_types($conn, $row);
$vehicle_type = $transport_types['vehicle_type'];
$premium_train_type = $transport_types['premium_train_type'];
$premium_airlines_type = $transport_types['premium_airlines_type'];

$invoice_date = $grn_date;
$gcn_invoice_proforma = isset($_GET['proforma']) && (string) $_GET['proforma'] === '1';

$gcn_invoice_doc = isset($_GET['invoice_doc']) ? strtolower(trim((string) $_GET['invoice_doc'])) : 'gst';
if ($gcn_invoice_doc !== 'other') {
    $gcn_invoice_doc = 'gst';
}

if ($gcn_invoice_proforma) {
    $unique_invoice_no = 'PROFORMA';
} else {
    $unique_invoice_no = ($unique_invoice_no != '') ? $unique_invoice_no : $invoice_no;
}

$gcn_invoice_is_other = ($gcn_invoice_doc === 'other')
    || (!$gcn_invoice_proforma && gcn_gst_invoice_doc_kind_from_no($unique_invoice_no) === 'other');

// ─── GST vs GTA detection (kept from your original logic) ─────────────────────
if ($gcn_invoice_proforma || strlen((string) $unique_invoice_no) < 5) {
    $check_gst_or_gta = in_array((string) $mode_of_transportation, array('1', '2', '3'), true) ? 'GST' : 'GTA';
} else {
    $check_gst_or_gta = substr($unique_invoice_no, 2, 3);
}

// ─── Mpdf setup (same pattern as transaction_pdf.php) ──────────────────────────
$mpdf = new \Mpdf\Mpdf([
    'mode'         => 'utf-8',
    'format'       => 'A4',
    'default_font' => 'freesans',
    'margin_left'  => 5,
    'margin_right' => 5,
    'margin_top'   => 5,
    'margin_bottom'=> 5
]);

if ($booking_status == 1) {
    $mpdf->SetWatermarkImage('images/pdf/cancel2.png', 0.35, '', [60, 110]);
    $mpdf->showWatermarkImage = true;
} elseif ($gcn_invoice_proforma) {
    $mpdf->SetWatermarkText('PROFORMA');
    $mpdf->showWatermarkText = true;
    $mpdf->watermarkTextAlpha = 0.12;
}

$pdf_doc_title = $gcn_invoice_is_other ? 'Invoice' : 'Tax Invoice';
if ($gcn_invoice_proforma) {
    $pdf_doc_title = $gcn_invoice_is_other ? 'Proforma Invoice' : 'Proforma Tax Invoice';
}
$mpdf->SetTitle($pdf_doc_title . ' - ' . $unique_invoice_no);
$mpdf->SetAuthor('EliteWave360 Logistics');

// ─── Company data (same source as transaction_pdf.php) ────────────────────────
$company_result = mysqli_query($conn, "SELECT * FROM company WHERE status=0");
$company_row    = mysqli_fetch_array($company_result);
$company_gstin  = $company_row['gst_no'];
$company_pan    = $company_row['pan_no'];

// ─── SAC code: prefer mode table, fall back to GST/GTA substring rule ─────────
$mode_result = mysqli_query($conn, "SELECT sac_code FROM mode_of_transportation WHERE mode_id='" . $mode_of_transportation . "'");
$mode_row    = mysqli_fetch_assoc($mode_result);

if (!empty($mode_row['sac_code'])) {
    $sac = $mode_row['sac_code'];
    $sac_text = $sac . ' - Multimodal Transport of Goods';
} elseif ($check_gst_or_gta == 'GST') {
    $sac = '996541';
    $sac_text = '996541 - Multimodal Transport of Goods';
} else {
    $sac = '9965';
    $sac_text = '9965 - Good Transport Agency Service';
}

$mode_name = get_mode($conn, $mode_of_transportation);

// ─── Bill-to (consignee) client record ─────────────────────────────────────────
$consignee_det = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM client WHERE client_id='" . $consignee . "'"));
$consignor_det = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM client WHERE client_id='" . $consigner . "'"));

function fmt_addr_lines($det, $conn) {
    return ew_format_party_address_invoice_html($det, $conn);
}

$state          = get_statename($conn, $consignee_det['state']);
$pincode        = $consignee_det['pincode'];
$consignee_gst  = $consignee_det['gst_no'];
$state_code     = substr($consignee_gst, 0, 2); // GSTIN's first 2 digits ARE the state code
$consignee_addr_html = fmt_addr_lines($consignee_det, $conn);

$client_email          = $consignee_det['email'];
$client_email2         = trim($consignee_det['email1'] ?? '');
$client_contact_person = $consignee_det['contact_person'];
$client_contact_no     = $consignee_det['contact_no'];
$client_contact_no2    = trim($consignee_det['contact_no1'] ?? '');

$mobile_numbers = array_filter([
    $client_contact_no,
    $client_contact_no2
], function ($value) {
    return $value !== '';
});

$mobile_numbers_text = !empty($mobile_numbers)
    ? implode(' / ', $mobile_numbers)
    : 'Not Available';

// ─── State-code compare for CGST/SGST vs IGST ──────────────────────────────────
$consignee_gst_prefix = substr($consignee_gst, 0, 2);
$company_gst_prefix   = substr($company_gstin, 0, 2);
$is_same_state = ($consignee_gst_prefix == $company_gst_prefix);

$gst_rate_full = ($mode_of_transportation == '1' || $mode_of_transportation == '2' || $mode_of_transportation == '3') ? 12 : 18;

// ─── current date for stamp ──────────────────────────────────
$current_date = date('Y.m.d H:i:s O');

// ─── Line items ─────────────────────────────────────────────────────────────
$query_items = "SELECT * FROM transaction_invoice_" . $month . "_" . $year . "
                 WHERE transaction_id = '" . $transaction_id . "'
                   AND type_of_pkge != 'Select Package Type'";
$result_items = mysqli_query($conn, $query_items);
$invoice_line_items = [];
while ($item = mysqli_fetch_assoc($result_items)) {
    $invoice_line_items[] = $item;
}
$item_count = count($invoice_line_items);

$frieght_amount_hdr = (float) ($frieght_amount ?? 0);
$calc_freight_sum = 0.0;
$weight_sum = 0.0;
foreach ($invoice_line_items as $item) {
    $calc_freight_sum += (float) $item['charged_weight'] * (float) $item['frieght_rate'];
    $weight_sum += (float) $item['charged_weight'];
}
$allocated_freight = [];
$freight_allocated_sum = 0.0;
foreach ($invoice_line_items as $idx => $item) {
    $freight_calc = (float) $item['charged_weight'] * (float) $item['frieght_rate'];
    if ($frieght_amount_hdr > 0) {
        if ($item_count === 1) {
            $line_freight = $frieght_amount_hdr;
        } elseif ($calc_freight_sum > 0.009) {
            $line_freight = $frieght_amount_hdr * ($freight_calc / $calc_freight_sum);
        } elseif ($weight_sum > 0.009) {
            $line_freight = $frieght_amount_hdr * ((float) $item['charged_weight'] / $weight_sum);
        } else {
            $line_freight = $frieght_amount_hdr / max(1, $item_count);
        }
    } else {
        $line_freight = $freight_calc;
    }
    $allocated_freight[$idx] = round($line_freight, 2);
    $freight_allocated_sum += $allocated_freight[$idx];
}
if ($item_count > 0 && $frieght_amount_hdr > 0 && abs($freight_allocated_sum - $frieght_amount_hdr) > 0.009) {
    $last_idx = $item_count - 1;
    $allocated_freight[$last_idx] += round($frieght_amount_hdr - $freight_allocated_sum, 2);
}

$sno = 1;
$gcn_count = 0;

$sum_qty = 0;
$sum_weight = 0;
$sum_freight = 0;
$sum_dc = 0;
$sum_total_line = 0;
$rows_html = '';
$pdf_amt_cell = 'border:1px solid #000;padding:2px 4px;text-align:right;vertical-align:top;white-space:nowrap;font-size:7pt;';
$pdf_gcn_cell = 'border:1px solid #000;padding:7px 4px;text-align:center;vertical-align:top;white-space:nowrap;font-size:7pt;';
$pdf_qty_cell = 'border:1px solid #000;padding:7px 4px;text-align:center;vertical-align:top;white-space:nowrap;font-size:7pt;';
$pdf_line_cell = 'border:1px solid #000;padding:7px 4px;vertical-align:top;';

foreach ($invoice_line_items as $idx => $item) {
$gcn_count++;
    $qty     = $item['qty'];
    $weight  = round($item['charged_weight'], 1);
    $rate    = $item['frieght_rate'];
    $freight = $allocated_freight[$idx];
    $dc      = $item['dc_amount'] ?? $doc_amount ?? 0; // per-line DC, falls back to header doc_amount
    $total_line = $freight + $dc;
$single_row_style = '';

if ($item_count == 1) {
    // ROLLBACK-INV: was padding-bottom:140px;
    $single_row_style = 'padding-bottom:52px;min-height:72px;';
}

    $sum_qty        += $qty;
    $sum_weight     += $weight;
    $sum_freight    += $freight;
    $sum_dc         += $dc;
    $sum_total_line += $total_line;

   $rows_html .= '
<tr>

    <td style="
        ' . $pdf_line_cell . '
        text-align:center;
        ' . $single_row_style . '
    ">
        ' . $sno . '
    </td>

    <td style="
        ' . $pdf_gcn_cell . '
        ' . $single_row_style . '
    ">
        ' . htmlspecialchars($grn_no) . '
    </td>

    <td style="
        ' . $pdf_line_cell . '
        text-align:center;
        ' . $single_row_style . '
    ">
        ' . htmlspecialchars($grn_date) . '
    </td>

    <td style="
        ' . $pdf_qty_cell . '
        ' . $single_row_style . '
    ">
        <nobr>' . htmlspecialchars((string) $qty) . '</nobr>
    </td>

    <td style="
        ' . $pdf_line_cell . '
        text-align:center;
        ' . $single_row_style . '
    ">
        ' . $weight . '
    </td>

    <td style="
        ' . $pdf_line_cell . '
        text-align:center;
        ' . $single_row_style . '
    ">
        ' . $rate . '
    </td>

    <td style="
        ' . $pdf_line_cell . '
        text-align:center;
        font-size:6.5pt;
        line-height:1.22;
        ' . $single_row_style . '
    ">
        ' . htmlspecialchars(get_client_name($conn, $consigner)) . '
    </td>

    <td style="
        ' . $pdf_line_cell . '
        text-align:left;
        font-size:6.5pt;
        line-height:1.2;
        ' . $single_row_style . '
    ">
        ' . htmlspecialchars(get_client_name($conn, $consignee)) . '<br>
        ' . $consignee_addr_html . '<br>
        GSTIN : ' . htmlspecialchars($consignee_gst) . '
    </td>

    <td style="
        ' . $pdf_line_cell . '
        text-align:center;
        font-size:7pt;
        ' . $single_row_style . '
    ">
        ' . htmlspecialchars($mode_name) . '
    </td>

    <td style="
        ' . $pdf_line_cell . '
        text-align:center;
        ' . $single_row_style . '
    ">
        ' . htmlspecialchars($item['party_invoice_no']) . '
    </td>

</tr>';

    $sno++;
}

// ─── Charges / grand total ─────────────────────────────────────────────────────
$loading_unloading_amount = $loading_unloading_amount ?? 0;
$crane_fork_lift_amount   = $crane_fork_lift_amount ?? 0;
$fov_amount               = $fov_amount ?? 0;
$doc_amount               = $doc_amount ?? 0;
$other_charge_amount      = $other_charge_amount ?? 0;
$cod_amount               = $cod_amount ?? 0;
$cartage_amount           = $cartage_amount ?? 0;
$labour_handling_amount   = $labour_handling_amount ?? 0;
$octroi_amount            = $octroi_amount ?? 0;
$rajdhani_charges         = $rajdhani_charges ?? 0;
$gst_amount               = (float) ($gst_amount ?? 0);
$mamul_charge             = (float) ($mamul_charge ?? 0);
$vehicle_halting_charge   = (float) ($vehicle_halting_charge ?? 0);
$vehicle_loading_unloading = (float) ($vehicle_loading_unloading ?? 0);
$taxable_value_stored     = (float) ($taxable_value ?? 0);
$total_stored             = (float) ($total ?? 0);

if ($frieght_amount_hdr > 0 && abs($frieght_amount_hdr - $sum_freight) > 0.009) {
    $sum_total_line += ($frieght_amount_hdr - $sum_freight);
    $sum_freight = $frieght_amount_hdr;
}

$grand_total_before_round = $sum_total_line + $loading_unloading_amount + $crane_fork_lift_amount
    + $fov_amount + $other_charge_amount + $labour_handling_amount
    + $cod_amount + $cartage_amount + $octroi_amount + $rajdhani_charges
    + $mamul_charge + $vehicle_halting_charge + $vehicle_loading_unloading + $gst_amount;

if ($taxable_value_stored > 0 && $total_stored > 0) {
    $grand_total = $total_stored;
    $round_off = round($grand_total - ($taxable_value_stored + $gst_amount), 2);
} else {
    $round_off   = (float) $total - $grand_total_before_round;
    $grand_total = $round_off + $grand_total_before_round;
}

$gst_pdf_tax_mode = gst_invoice_pdf_resolve_tax_mode($row, $is_same_state);
$gst_pdf_display = gst_invoice_pdf_prepare_gst_display($row, $gst_pdf_tax_mode, $gst_rate_full);
$cgst_rate = $gst_pdf_display['cgst_rate'];
$sgst_rate = $gst_pdf_display['sgst_rate'];
$igst_rate = $gst_pdf_display['igst_rate'];
$cgst_amt = $gst_pdf_display['cgst_amt'];
$sgst_amt = $gst_pdf_display['sgst_amt'];
$igst_amt = $gst_pdf_display['igst_amt'];

if ($gcn_invoice_is_other) {
    $gst_pdf_tax_mode = 'none';
    $cgst_amt = 0;
    $sgst_amt = 0;
    $igst_amt = 0;
    // Other invoice: payable before GST (same as payment screen "Taxable Value"), never booking total with tax.
    $other_pre_gst_total = $taxable_value_stored;
    if ($other_pre_gst_total <= 0 && $total_stored > 0) {
        $other_pre_gst_total = max(0, round($total_stored - $gst_amount, 2));
    }
    if ($other_pre_gst_total <= 0) {
        $other_pre_gst_total = $sum_total_line + $loading_unloading_amount + $crane_fork_lift_amount
            + $fov_amount + $other_charge_amount + $labour_handling_amount
            + $cod_amount + $cartage_amount + $octroi_amount + $rajdhani_charges
            + $mamul_charge + $vehicle_halting_charge + $vehicle_loading_unloading;
    }
    $grand_total = $other_pre_gst_total;
    $round_off = 0;
}

// Line-table "Total" column = sum of Freight + DC shown above (same on GST and Other PDFs).
$table_footer_total = $sum_total_line;
$show_round_off_row = !$gcn_invoice_is_other || abs($round_off) > 0.009;

require_once __DIR__ . '/include/gst_tax_report_functions.php';
$invoice_amount_in_words = trim((string) ($total_words ?? ''));
if ($gcn_invoice_is_other) {
    $invoice_amount_in_words = gst_tax_report_amount_in_words((float) $grand_total);
} elseif ($invoice_amount_in_words === '') {
    $invoice_amount_in_words = gst_tax_report_amount_in_words((float) $grand_total);
}

// ══════════════════════════════════════════════════════════════════════════════
// HTML BUILD  (same freesans / border-table technique as transaction_pdf.php)
// ══════════════════════════════════════════════════════════════════════════════
$css = '
<style>
body{ font-family: freesans; font-size:7.5pt; }
table{ border-collapse:collapse; }
td{ font-family:freesans; font-size:7.5pt; vertical-align:middle; }
b,strong{ font-weight:bold; }
</style>';

$html = $css;

// ─── SECTION 1: HEADER ─────────────────────────────────────────────────────────
$html .= '

<table style="width:100%;border-collapse:collapse;border-right:1px solid #000;border-left:1px solid #000;border-top:1px solid #000;">


<tr>

<td style="width:20%;text-align:center;vertical-align:middle;padding-left:240px;">



</td>

<td style="width:55%;text-align:center;vertical-align:middle;">

<div style="
font-size:14pt;
font-weight:bold;
line-height:18px;
padding-left:700px;
">

' . ($gcn_invoice_proforma
    ? ($gcn_invoice_is_other ? 'PROFORMA INVOICE' : 'PROFORMA TAX INVOICE')
    : ($gcn_invoice_is_other ? 'INVOICE' : 'TAX INVOICE')) . '

</div>



</td>

<td style="
width:25%;
font-weight:bold;
text-align:right;
vertical-align:top;
font-size:9pt;
padding-left:5px;


">

(ORIGINAL COPY)

</td>

</tr>



</table>

';



$html .= '

<table style="width:100%;border-collapse:collapse;border-right:1px solid #000;border-left:1px solid #000;">


<tr>

<td style="width:20%;text-align:center;vertical-align:middle;">

<img src="images/elite-nav.png" style="width:180px;">

</td>

<td style="width:65%;text-align:center;vertical-align:middle;">

<div style="
font-size:17pt;
font-weight:bold;
color:#021659;
line-height:18px;
">

EliteWave360 Logistics

</div>

<div style="
font-size:8.8pt;
line-height:10px;
">

No.10/35, M.V.Badran Street, Anaikar Complex, Second Floor,
Naval Hospital Road,<br>

Periamet, Chennai - 600003 Tamil Nadu,
Phone : +91 9840859711 &nbsp;&nbsp; +91 9952918211<br>

E-Mail : info@elitewave360.in, athar@elitewave360.in &nbsp;&nbsp;
www.elitewave360.in

</div>

</td>

<td style="
width:15%;
font-weight:bold;
text-align:right;
vertical-align:top;
font-size:8pt;


">


</td>

</tr>



</table>

';

$html .= '
<table style="width:100%;border-collapse:collapse;border-right:1px solid #000;border-left:1px solid #000;">
<tr>

<td style="
width:50%;
font-size:9.2pt;
font-weight:bold;
text-align:left;
">

 GSTIN/UIN : '. $company_gstin .'

</td>

<td></td>

<td style="
width:50%;
font-size:9.2pt;
font-weight:bold;
text-align:right;
">

PAN : '. $company_pan .'

</td>

</tr>
</table>

';

// ─── SECTION 2: INVOICE NO / SAC / DATE ─────────────────────────
$html .= '
<table cellpadding="4" cellspacing="0" width="100%"
       style="border-collapse:collapse; border:1px solid #000;">
    <tr>

        <td style="
            width:40%;
            font-weight:bold;
            border:none;
            font-size:10pt;
            white-space:nowrap;
        ">
            Invoice Number&nbsp;&nbsp;: &nbsp;' . htmlspecialchars($unique_invoice_no) . '
        </td>

        <td style="
            width:25%;
            font-weight:bold;
            border:none;
            font-size:10pt;
            text-align:center;
            white-space:nowrap;
        ">
            SAC CODE:&nbsp;&nbsp;' . htmlspecialchars($sac) . '
        </td>

        <td style="
            width:35%;
            font-weight:bold;
            border:none;
            text-align:right;
            font-size:10pt;
            white-space:nowrap;
        ">
            Invoice Generated Date :&nbsp;' . htmlspecialchars($invoice_date) . '
        </td>

    </tr>
</table>';

// ─── SECTION 3: BILL TO + CONTACT DETAILS ─────────────────────────────

$html .= '
<table cellpadding="0" cellspacing="0" width="100%"
       style="
           border-collapse: collapse;
           border: 1px solid #000;
           font-size: 8.5pt;
           font-family: Arial, Helvetica, sans-serif;
       ">

<tr>

    <!-- LEFT : BILL TO -->
    <td width="50%"
        style="
            width:50%;
            vertical-align:top;
            padding:3px 6px;
            border-top:0;
            border-bottom:0;
            border-left:1px solid #000;
            border-right:0;
            line-height:1.15;
        ">

        <table cellpadding="0" cellspacing="0" width="100%"
               style="
                   border:none;
                   
                   border-collapse:collapse;
               ">

            <tr>
                <td width="70"
                    style="
                        border:none;
                        vertical-align:top;
                        font-weight:bold;
                        font-size:9.5pt;
                    ">
                    Bill To
                </td>

                <td width="10"
                    style="
                        border:none;
                        vertical-align:top;
                        font-weight:bold;
                         font-size:9.5pt;
                    ">
                    :
                </td>

                <td style="
                    border:none;
                    vertical-align:top;
                     font-size:9pt;
                     font-weight:bold;
                ">
                    ' . strtoupper(htmlspecialchars(get_client_name($conn, $consignee))) . '
                </td>
            </tr>

            <tr>
                <td style="border:none;"></td>
                <td style="border:none;"></td>
                <td style="border:none; font-size:9pt;">
                    ' . strtoupper(strip_tags($consignee_addr_html)) . '
                </td>
            </tr>

            <tr>
                <td style="border:none;"></td>
                <td style="border:none;"></td>
                <td style="border:none; font-size:9.5pt;">
                    State : ' . htmlspecialchars($state) . '
                    &nbsp;&nbsp; Code : ' . htmlspecialchars($state_code) . '
                </td>
            </tr>

            <tr>
                <td style="border:none;"></td>
                <td style="border:none;"></td>
                <td style="
                    border:none;
                    font-weight:bold;
                     font-size:8.5pt;
                ">
                    GSTIN/UIN : ' . strtoupper(htmlspecialchars($consignee_gst)) . '
                </td>
            </tr>

        </table>

    </td>


    <!-- RIGHT : CONTACT DETAILS -->
    <td width="50%"
        style="
            width:50%;
            vertical-align:top;
            padding:3px 6px;
            border-top:0;
            border-bottom:0;
            border-left:0;
            border-right:1px solid #000;
            line-height:1.15;
        ">

        <table cellpadding="0" cellspacing="0" width="100%"
               style="
                   border:none;
                   font-size:8.5pt;
                   border-collapse:collapse;
               ">

            <tr>
                <td width="130"
                    style="
                        border:none;
                        vertical-align:top;
                        font-weight:bold;
                        font-size:9.5pt;
                    ">
                   Contact Person
                </td>

                <td width="10"
                    style="
                        border:none;
                        vertical-align:top;
                        font-weight:bold;
                        font-size:9.5pt;
                    ">
                    :
                </td>

                <td style="
                    border:none;
                    vertical-align:top;
                    font-size:9.5pt;
                ">
                    ' . htmlspecialchars($client_contact_person) . '
                </td>
            </tr>

            <tr>
                <td style="
        border:none;
        vertical-align:top;
        font-weight:bold;
        font-size:9.5pt;
    ">
        Mobile Numbers
    </td>

    <td style="
        border:none;
        vertical-align:top;
        font-weight:bold;
        font-size:9.5pt;
    ">
        :
    </td>

    <td style="
        border:none;
        vertical-align:top;
        font-size:9.5pt;
    ">
        ' . htmlspecialchars($mobile_numbers_text) . '
    </td>
</tr>


<tr>
    <td style="
        border:none;
        vertical-align:top;
        font-weight:bold;
        font-size:9.5pt;
    ">
        Email 1
    </td>

    <td style="
        border:none;
        vertical-align:top;
        font-weight:bold;
        font-size:9.5pt;
    ">
        :
    </td>

    <td style="
        border:none;
        vertical-align:top;
        font-size:9.5pt;
    ">
        ' . htmlspecialchars($client_email ?: 'Not Available') . '
    </td>
</tr>


<tr>
    <td style="
        border:none;
        vertical-align:top;
        font-weight:bold;
        font-size:9.5pt;
    ">
        Email 2
    </td>

    <td style="
        border:none;
        vertical-align:top;
        font-weight:bold;
        font-size:9.5pt;
    ">
        :
    </td>

    <td style="
        border:none;
        vertical-align:top;
        font-size:9.5pt;
    ">
        ' . htmlspecialchars($client_email2 ?: 'Not Available') . '
    </td>
            </tr>

        </table>

    </td>

</tr>

</table>';

// ROLLBACK-INV: single-GCN line table ends at Supp.Inv.No.; amounts live in Charge Summary.
$html .= '
<table cellpadding="0" cellspacing="0" width="100%"
       style="
           border-collapse:collapse;
           border:1px solid #000;
           table-layout:fixed;
           font-family:Arial, Helvetica, sans-serif;
           font-size:7.5pt;
       ">

<tr style="
    font-weight:bold;
    text-align:center;
    vertical-align:middle;
    height:26px;
">

    <td style="border:1px solid #000;width:6%;padding:5px 3px;text-align:center;white-space:nowrap;">S/No</td>
    <td style="border:1px solid #000;width:10%;padding:5px 3px;text-align:center;white-space:nowrap;">GCN No</td>
    <td style="border:1px solid #000;width:9%;padding:5px 3px;text-align:center;">Date</td>
    <td style="border:1px solid #000;width:5%;padding:5px 3px;text-align:center;white-space:nowrap;">Qty</td>
    <td style="border:1px solid #000;width:6%;padding:5px 3px;text-align:center;">Weight</td>
    <td style="border:1px solid #000;width:4%;padding:5px 3px;text-align:center;">Rate</td>
    <td style="border:1px solid #000;width:16%;padding:5px 3px;text-align:center;font-size:6.5pt;">
        Consignor / Consignee
    </td>
    <td style="border:1px solid #000;width:18%;padding:5px 3px;text-align:center;font-size:6.5pt;">
        Ship To
    </td>
    <td style="border:1px solid #000;width:10%;padding:5px 3px;text-align:center;font-size:6.5pt;">Mode</td>
    <td style="border:1px solid #000;width:16%;padding:5px 3px;text-align:center;font-size:6.5pt;">
        Supp.Inv.No.
    </td>

</tr>

' . $rows_html . '

<tr style="
    font-weight:bold;
    height:26px;
    page-break-inside:avoid;
">

    <!-- S/No -->
    <td style="
        border:1px solid #000;
        text-align:left;
        padding:6px 5px;
        font-weight:bold;
        vertical-align:middle;
        white-space:nowrap;
        font-size:7pt;
    ">
        <nobr>Total</nobr>
    </td>

    <!-- GCN No - COUNT -->
    <td style="
        border:1px solid #000;
        text-align:center;
        padding:6px 4px;
        vertical-align:middle;
        font-weight:bold;
        white-space:nowrap;
    ">
        ' . $gcn_count . ' 
    </td>

    <!-- Date -->
    <td style="
        border:1px solid #000;
        padding:6px 4px;
        font-weight:bold;
        vertical-align:middle;
    ">
    </td>

    <!-- Qty -->
    <td style="
        ' . $pdf_qty_cell . '
        font-weight:bold;
        vertical-align:middle;
    ">
        <nobr>' . htmlspecialchars((string) $sum_qty) . '</nobr>
    </td>

    <!-- Weight -->
    <td style="
        border:1px solid #000;
        text-align:center;
        font-weight:bold;
        padding:6px 4px;
        vertical-align:middle;
    ">
        ' . $sum_weight . '
    </td>

    <!-- Rate -->
    <td style="
        border:1px solid #000;
        padding:6px 4px;
        font-weight:bold;
        vertical-align:middle;
    ">
    </td>

    <!-- Consignor / Consignee -->
    <td style="
        border:1px solid #000;
        padding:6px 4px;
        vertical-align:middle;
    ">
        &nbsp;
    </td>

    <!-- Ship To -->
    <td style="
        border:1px solid #000;
        padding:6px 4px;
        vertical-align:middle;
    ">
        &nbsp;
    </td>

    <!-- Mode -->
    <td style="
        border:1px solid #000;
        padding:6px 4px;
        vertical-align:middle;
    ">
        &nbsp;
    </td>

    <!-- Supp.Inv.No -->
    <td style="
        border:1px solid #000;
        padding:6px 4px;
        vertical-align:middle;
    ">
        &nbsp;
    </td>

</tr>

</table>';

$html .= gst_invoice_pdf_render_charge_summary_table($row, $taxable_value_stored, $table_footer_total, true);

// ─── SECTION 5: INVOICE META + GST / GRAND TOTAL ─────────────────────────────

$html .= '
<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    border="0"
    style="
        width:100%;
        border-collapse:collapse;
        font-family:freesans;
        font-size:9pt;
        border-left:1px solid #000;
        border-right:1px solid #000;
        border-bottom:1px solid #000;
    "
>
<tr>

    <!-- =========================================================
         LEFT SIDE - 70%
         ========================================================= -->

    <td
        width="70%"
        style="
            width:70%;
            padding:5px 9px;
            vertical-align:top;
            border-left:1px solid #000;
            border-right:1px solid #000;
            border-bottom:1px solid #000;
        "
    >

        <table
            width="100%"
            cellpadding="0"
            cellspacing="0"
            border="0"
            style="
                width:100%;
                border-collapse:collapse;
                font-family:freesans;
                font-size:9pt;
            "
        >

            <!-- INVOICE NUMBER -->

            <tr>
                <td
                    width="170"
                    style="
                        border:0;
                        padding:0;
                        font-weight:bold;
                        line-height:18px;
                        font-size:9pt;
                        white-space:nowrap;
                    "
                >
                    Invoice Number
                </td>

                <td
                    width="15"
                    style="
                        border:0;
                        padding:0;
                        font-weight:bold;
                        line-height:18px;
                        font-size:9pt;
                    "
                >
                    :
                </td>

                <td
                    style="
                        border:0;
                        padding:0;
                        line-height:18px;
                        font-size:9pt;
                    "
                >
                    ' . htmlspecialchars($unique_invoice_no) . '
                </td>
            </tr>


            <!-- VEHICLE TYPE -->

            <tr>
                <td
                    style="
                        border:0;
                        padding:0;
                        font-weight:bold;
                        line-height:18px;
                        font-size:9pt;
                        white-space:nowrap;
                    "
                >
                    Vehicle Type
                </td>

                <td
                    style="
                        border:0;
                        padding:0;
                        font-weight:bold;
                        font-size:9pt;
                    "
                >
                    :
                </td>

                <td
                    style="
                        border:0;
                        padding:0;
                        line-height:18px;
                        font-size:9pt;
                    "
                >
                    ' . htmlspecialchars($vehicle_type ?? '') . '
                </td>
            </tr>


            <!-- PREMIUM TRAIN TYPE -->

            <tr>
                <td
                    style="
                        border:0;
                        padding:0;
                        font-weight:bold;
                        line-height:18px;
                        font-size:9pt;
                        white-space:nowrap;
                    "
                >
                    Premium Train Type
                </td>

                <td
                    style="
                        border:0;
                        padding:0;
                        font-weight:bold;
                        font-size:9pt;
                    "
                >
                    :
                </td>

                <td
                    style="
                        border:0;
                        padding:0;
                        line-height:18px;
                        font-size:9pt;
                    "
                >
                    ' . htmlspecialchars($premium_train_type ?? '') . '
                </td>
            </tr>


            <!-- PREMIUM AIRLINES TYPE -->

            <tr>
                <td
                    style="
                        border:0;
                        padding:0;
                        font-weight:bold;
                        line-height:18px;
                        font-size:9pt;
                        white-space:nowrap;
                    "
                >
                    Premium Airlines Type
                </td>

                <td
                    style="
                        border:0;
                        padding:0;
                        font-weight:bold;
                        font-size:9pt;
                    "
                >
                    :
                </td>

                <td
                    style="
                        border:0;
                        padding:0;
                        line-height:18px;
                        font-size:9pt;
                    "
                >
                    ' . htmlspecialchars($premium_airlines_type ?? '') . '
                </td>
            </tr>

        </table>

    </td>


    <!-- =========================================================
         RIGHT SIDE - 30%
         ========================================================= -->

    <td
        width="30%"
        style="
            width:30%;
            padding:0;
            vertical-align:' . ($gcn_invoice_is_other ? 'bottom' : 'top') . ';
            border-right:1px solid #000;
            border-bottom:1px solid #000;
        "
    >

        <table
            width="100%"
            cellpadding="0"
            cellspacing="0"
            border="0"
            style="
                width:100%;
                border-collapse:collapse;
                font-family:freesans;
                font-size:9pt;
            "
        >';


/* ================================================================
   GST ROWS
   ================================================================ */

if (!$gcn_invoice_is_other && $gst_pdf_tax_mode === 'intra') {
    $cgst_rate_txt = gst_invoice_pdf_format_rate($cgst_rate);
    $sgst_rate_txt = gst_invoice_pdf_format_rate($sgst_rate);
    if ($cgst_rate_txt !== '' || $cgst_amt > 0) {
        $html .= '
        <tr>
            <td width="70%" style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">OUTPUT- CGST @ ' . $cgst_rate_txt . '%</td>
            <td width="30%" style="width:30%;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . gst_invoice_pdf_format_money($cgst_amt) . '</td>
        </tr>';
    }
    if ($sgst_rate_txt !== '' || $sgst_amt > 0) {
        $html .= '
        <tr>
            <td width="70%" style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">OUTPUT- SGST @ ' . $sgst_rate_txt . '%</td>
            <td width="30%" style="width:30%;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . gst_invoice_pdf_format_money($sgst_amt) . '</td>
        </tr>';
    }
} elseif (!$gcn_invoice_is_other && $gst_pdf_tax_mode === 'inter') {
    $igst_rate_txt = gst_invoice_pdf_format_rate($igst_rate);
    if ($igst_rate_txt !== '' || $igst_amt > 0) {
        $html .= '
        <tr>
            <td width="70%" style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">OUTPUT- IGST @ ' . $igst_rate_txt . '%</td>
            <td width="30%" style="width:30%;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . gst_invoice_pdf_format_money($igst_amt) . '</td>
        </tr>';
    }
}


/* ================================================================
   ROUND OFF + GRAND TOTAL
   ================================================================ */

if ($show_round_off_row) {
    $html .= '

        <!-- ROUND OFF -->

        <tr>

            <td
                width="70%"
                style="
                    width:70%;
                    border-right:1px solid #000;
                    border-bottom:1px solid #000;
                    padding:1px 4px;
                    font-weight:bold;
                    line-height:18px;
                    white-space:nowrap;
                "
            >
                ROUND OFF
            </td>

            <td
                width="30%"
                style="
                    width:30%;
                    border-bottom:1px solid #000;
                    padding:1px 4px;
                    text-align:right;
                    font-weight:bold;
                    line-height:18px;
                    white-space:nowrap;
                "
            >
                ' . ($round_off < 0 ? '(-)' : '') . gst_invoice_pdf_format_money(abs($round_off)) . '
            </td>

        </tr>';
}

$grand_total_row_top_border = ($gcn_invoice_is_other && !$show_round_off_row) ? 'border-top:1px solid #000;' : '';

$html .= '

        <!-- GRAND TOTAL -->

        <tr>

            <td
                width="70%"
                style="
                    width:70%;
                    border-right:1px solid #000;
                    border-bottom:1px solid #000;
                    padding:2px 4px;
                    font-weight:bold;
                    line-height:18px;
                    white-space:nowrap;
                    ' . $grand_total_row_top_border . '
                "
            >
                GRAND TOTAL
            </td>

            <td
                width="30%"
                style="
                    width:30%;
                    border-bottom:1px solid #000;
                    padding:2px 4px;
                    text-align:right;
                    font-weight:bold;
                    line-height:18px;
                    white-space:nowrap;
                    ' . $grand_total_row_top_border . '
                "
            >
                ' . gst_invoice_pdf_format_money($grand_total) . '
            </td>

        </tr>

        </table>

    </td>

</tr>
</table>
';

// ─── SECTION 6: AMOUNT IN WORDS ─────────────────────────────────────────────────
$html .= '
<table border="0" cellpadding="4" cellspacing="0" width="100%" style="width:100%;border-collapse:collapse;margin-top:-1px;">
<tr><td style="border-left:1px solid #000;border-right:1px solid #000;border-bottom:1px solid #000;font-weight:bold;font-size:8.5pt;">Amount (In words) : ' . htmlspecialchars($invoice_amount_in_words) . '</td></tr>
</table>';

// ─── SECTION 7: SAC NOTE + PAYMENT NOTE ────────────────────────────────────────

$html .= '
<table
    cellpadding="0"
    cellspacing="0"
    border="0"
    width="100%"
    style="
        width:100%;
        border-collapse:collapse;
        border-spacing:0;
        margin-top:-1px;
        margin-left:0;
        margin-right:0;
        padding:0;
       
    "
>
    <tr>
        <td
            width="100%"
            style="
                width:100%;
                padding:3px 5px 3px 5px;
                 font-size:8.5pt;

                /* FORCE COMPLETE OUTER BORDER */
                border-left:1px solid #000;
                border-right:1px solid #000;
                border-top:1px solid #000;
                border-bottom:1px solid #000;

                font-weight:bold;
                line-height:13px;
                vertical-align:top;
            "
        >
            * SAC CODE : ' . htmlspecialchars($sac_text) . '<br>
           
        </td>
    </tr>
</table>
';

// ─── SECTION 8: SHIPMENT PROTECTION ADVISORY ────────────────────────────────────
$html .= '
<table border="1" width="100%" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">
<tr>
    <td width="84%" style="border:1px solid #000;font-size:9pt;line-height:13px;text-align:justify;">
         <b>Shipment Protection &amp; Insurance Advisory :</b> Each consignment must be covered with valid transit insurance. Our liability shall be NIL for any loss or damage
        arising due to any cause, including but not limited to natural calamities, accidents, theft, fire, or unforeseen
        events during transit. Kindly ensure that all consignments are properly packed using poly bags and shrink wrapping
        to prevent moisture exposure and damage. We shall not be held liable for any wetness or damage arising from
        inadequate or improper packing, including consignments not protected with shrink wrap or poly bags.
    </td>
</tr>
</table>';

// ─── SECTION 9: CARRYING CAPACITY ──────────────────────────────────────────────
$html .= '
<table border="1" width="100%" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">
<tr><td style="border:1px solid #000;font-size:9pt;line-height:13px;text-align:justify;">
     <b>Carrying Capacity 9 MT To 100 MT :</b> (Full Truck / Part Load / Heavy ODC (Over Dimensional Cargo) / ODC Equipment Bulk &amp; Lengthy Consignment by Open
    Truck / Hippo / and Heavy Trailers &amp; Hydraulic Trailer) Trailer Service Hydraulic Trailer Hybed- Semi bed - Low bed
    Hydraulic. We can pick up &amp; deliver your cargo PAN India (Presence across India) HYBED-SEMIBED-LOWBED HYDROLIC SPL.
    IN: 20, 28, 32, 40, 50, 70, 80, 100 FEET Heavy ODC, ODC Equipment Bulk &amp; Lenthly Consignment by Open Truck, Hippo,
    Valvo, Heavy Trailors &amp; Hydraulic Trailer
</td></tr>
</table>';

// ─── SECTION 10: TERMS AND CONDITIONS ───────────────────────────────────────────
$html .= '
<table border="1" width="100%" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">
<tr><td style="border:1px solid #000;font-size:9pt;line-height:13px;text-align:justify;">
    <b>Terms and Conditions :</b> Terms &amp; Conditions: (1) Jurisdiction: All disputes shall be subject to the
    jurisdiction of courts in Tamil Nadu only. (2) Claims &amp; Complaints: Any complaint or claim must be submitted in
    writing within 7 days from the date of booking. No claims will be entertained thereafter. (3) Volumetric Weight
    Calculation (Railway): (Length × Width × Height in CMS) ÷ 4000, (4) Volumetric Weight Calculation (Airlines):
    (Length × Width × Height in centimeters) ÷ 5,000, (5) Volumetric Weight Calculation (Road): (Length × Width ×
    Height in centimeters) ÷ 4000
</td></tr>
</table>';

// ─── SECTION 11: BANK DETAILS + PAYMENT + QR + DIGITAL SIGNATORY ─────────────

$qr_image_path = __DIR__ . '/images/original-payment-qr.jpg';
$has_qr_image  = file_exists($qr_image_path);

$html .= '
<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    border="0"
    style="
        width:100%;
        border-collapse:collapse;
        border:1px solid #000;
        font-family:freesans;
    "
>
<tr>

    <!-- =========================================================
         LEFT : BANK DETAILS + PAYMENT NOTE
         ========================================================= -->

   <td
    width="35%"
    style="
        width:35%;
        border-left:1px solid #000;
        border-right:1px solid #000;
        padding:5px 7px;
        vertical-align:top;
        font-size:9.2pt;
        line-height:13px;
    "
>

    <!-- PAYMENT INSTRUCTION FIRST -->

    <div
        style="
            font-size:8pt;
            font-weight:bold;
            line-height:10px;
            margin-bottom:5px;
             font-size:9.2pt;
        "
    >
        Please pay by Cheque/DD/RTGS/NEFT only in favour of
        EliteWave360 Logistics
    </div>
<br>

    <!-- BANK DETAILS -->

    <table
        width="100%"
        cellpadding="0"
        cellspacing="0"
        border="0"
        style="
            width:100%;
            border-collapse:collapse;
            font-family:freesans;
            font-size:9.2pt;
            line-height:13px;
        "
    >

        <tr>
            <td
                width="70"
                style="
                    border:0;
                    padding:0;
                    font-weight:bold;
                    white-space:nowrap;
                     font-size:9.2pt;
                "
            >
                Bank Name
            </td>
<br><br>
            <td
                width="8"
                style="
                    border:0;
                    padding:0;
                    font-weight:bold;
                "
            >
                :
            </td>
<br><br>
            <td
                style="
                    border:0;
                    padding:0;
                    white-space:nowrap;
                     font-size:9.2pt;
                "
            >
                Axis Bank
            </td>
 <br><br>          
        </tr>
 
        <tr>
            <td
                style="
                    border:0;
                    padding:0;
                    font-weight:bold;
                    white-space:nowrap;
                     font-size:9.2pt;
                "
            >
                Account No.
            </td>
<br><br>
            <td
                style="
                    border:0;
                    padding:0;
                    font-weight:bold;
                     font-size:9.2pt;
                "
            >
                :
            </td>
<br><br>
            <td
                style="
                    border:0;
                    padding:0;
                    white-space:nowrap;
                     font-size:9.2pt;
                "
            >
                926020021424035
            </td>
<br><br>
        </tr>

        <tr>
            <td
                style="
                    border:0;
                    padding:0;
                    font-weight:bold;
                     font-size:9.2pt;
                    white-space:nowrap;
                "
            >
                Branch
            </td>
<br><br>
            <td
                style="
                    border:0;
                    padding:0;
                    font-weight:bold;
                "
            >
                :
            </td>
<br><br>
            <td
                style="
                    border:0;
                    padding:0;
                    white-space:nowrap;
                     font-size:9.2pt;
                "
            >
                Vepery Chennai - 600007 Tamil Nadu
            </td>
            <br><br>
        </tr>

        <tr>
            <td
                style="
                    border:0;
                    padding:0;
                    font-weight:bold;
                     font-size:9.2pt;
                    white-space:nowrap;
                "
            >
                IFSC Code
            </td>

            <td
                style="
                    border:0;
                    padding:0;
                    font-weight:bold;
                "
            >
                :
            </td>

            <td
                style="
                    border:0;
                    padding:0;
                     font-size:9.2pt;
                    white-space:nowrap;
                "
            >
                UTIB0001885
            </td>
        </tr>

    </table>

</td>


    <!-- =========================================================
         MIDDLE : QR CODE
         ========================================================= -->

    <td
        width="30%"
        style="
            width:30%;
            border-left:0;
            border-right:1px solid #000;
            border-top:0;
            border-bottom:0;
            padding:4px;
            vertical-align:middle;
            text-align:center;
        "
    >
        ' .
        ($has_qr_image ? '

        <div
            style="
                width:100%;
                text-align:center;
            "
        >
            <img
                src="' . $qr_image_path . '"
                style="
                    width:30mm;
                    height:30mm;
                "
            >

            <div
                style="
                    font-size:7.5pt;
                    font-weight:bold;
                    margin-top:1px;
                    text-align:center;
                "
            >
               Scan & Pay Your Freight Charges
            </div>
        </div>

        ' : '') . '
    </td>


    <!-- =========================================================
         RIGHT : DIGITAL SIGNATORY
         ========================================================= -->

    <td
        width="35%"
        style="
            width:35%;
            border-left:0;
            border-right:1px solid #000;
            border-top:0;
            border-bottom:0;
            padding:4px 5px;
            vertical-align:middle;
            text-align:center;
        "
    >

        <div
            style="
                width:100%;
                text-align:center;
                font-size:11pt;
                font-weight:bold;
                color:#021659;
                line-height:15px;
            "
        >
            For EliteWave360 Logistics
        </div>

        <div style="height:3px;"></div>

        <div
            style="
                width:100%;
                text-align:center;
                font-size:15pt;
                font-weight:bold;
                color:#111;
                line-height:18px;
            "
        >
            AADIL AHMED
        </div>

        <div
            style="
                width:100%;
                text-align:center;
                font-size:6.5pt;
                color:#000;
                line-height:9px;
            "
        >
            <b>Digitally signed by AADIL AHMED</b><br>
            Date: ' . $current_date . '
        </div>

        <div
            style="
                width:100%;
                text-align:center;
                font-size:9pt;
                font-weight:bold;
                color:#021659;
                margin-top:2px;
                line-height:12px;
            "
        >
            Authorised Signatory
        </div>

    </td>

</tr>
</table>
';
// ─── SECTION 12: PAYMENT NOTE + FOOTER ─────────────────────────────────────────
$html .= '
<table border="1" width="100%" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">
<tr><td style="border:1px solid #000;text-align:center;font-size:8.5pt;font-weight:bold;">
    Note : We Do Not Accept Freight In Cash. Please Pay By Cheque / Online Only In Favour Of M/s. EliteWave360 Logistics
</td></tr>
</table>';


$html .= '
<table border="1" width="100%" cellspacing="0"
       style="border-collapse:collapse; border:1px solid #000;">
<tr>

    <td style="
        text-align:center;
        font-size:9pt;
        font-weight:bold;
        color:#021659;
        width:50%;
        border:none;
    ">
        This is a Computer generated Freight Invoice, Digitally Signed
    </td>

    <td style="
        text-align:center;
        font-size:9pt;
        font-weight:bold;
        color:#021659;
        width:50%;
        border:none;
    ">
        Visit : www.elitewave360.in
    </td>

</tr>
</table>';

// ─── Render → PDF ───────────────────────────────────────────────────────────────
$mpdf->WriteHTML($html);
$pdf_name = gst_invoice_pdf_download_filename($gcn_invoice_is_other, $unique_invoice_no, $gcn_invoice_proforma);
$force_download = isset($_GET['download']) && (string) $_GET['download'] === '1';
$mpdf->Output($pdf_name, $force_download ? 'D' : 'I');