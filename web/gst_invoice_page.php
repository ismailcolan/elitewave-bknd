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

require_once __DIR__ . '/include/gst_invoice_pdf_layout.php';
$transport_types = gst_invoice_resolve_transport_types($conn, $row);
$vehicle_type = $transport_types['vehicle_type'];
$premium_train_type = $transport_types['premium_train_type'];
$premium_airlines_type = $transport_types['premium_airlines_type'];

$invoice_date = $grn_date;
$unique_invoice_no = ($unique_invoice_no != '') ? $unique_invoice_no : $invoice_no;

// ─── GST vs GTA detection (kept from your original logic) ─────────────────────
$check_gst_or_gta = substr($unique_invoice_no, 2, 3);

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
}

$mpdf->SetTitle('Tax Invoice - ' . $unique_invoice_no);
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
    $lines = [];
    if (!empty($det['address1'])) $lines[] = trim($det['address1']);
    if (!empty($det['address2'])) $lines[] = trim($det['address2']);
    $city = get_city_name($conn, $det['city']);
    $tail = trim($city . ($det['pincode'] ? '-' . $det['pincode'] : ''));
    if ($tail) $lines[] = $tail;
    return implode('<br>', $lines);
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
$item_count = mysqli_num_rows($result_items);

$sno = 1;
$gcn_count = 0;

$sum_qty = 0;
$sum_weight = 0;
$sum_freight = 0;
$sum_dc = 0;
$sum_total_line = 0;
$rows_html = '';

while ($item = mysqli_fetch_assoc($result_items)) {
$gcn_count++;
    $qty     = $item['qty'];
    $weight  = round($item['charged_weight'], 1);
    $rate    = $item['frieght_rate'];
    $freight = $item['charged_weight'] * $item['frieght_rate'];
    $dc      = $item['dc_amount'] ?? $doc_amount ?? 0; // per-line DC, falls back to header doc_amount
    $total_line = $freight + $dc;
$single_row_style = '';

if ($item_count == 1) {
    $single_row_style = 'padding-bottom:140px;';
}

    $sum_qty        += $qty;
    $sum_weight     += $weight;
    $sum_freight    += $freight;
    $sum_dc         += $dc;
    $sum_total_line += $total_line;

   $rows_html .= '
<tr>

    <td style="
        border:1px solid #000;
        padding:3px;
        text-align:center;
        vertical-align:top;
        ' . $single_row_style . '
    ">
        ' . $sno . '
    </td>

    <td style="
        border:1px solid #000;
        padding:3px;
        text-align:center;
        vertical-align:top;
        ' . $single_row_style . '
    ">
        ' . htmlspecialchars($grn_no) . '
    </td>

    <td style="
        border:1px solid #000;
        padding:3px;
        text-align:center;
        vertical-align:top;
        ' . $single_row_style . '
    ">
        ' . htmlspecialchars($grn_date) . '
    </td>

    <td style="
        border:1px solid #000;
        padding:3px;
        text-align:center;
        vertical-align:top;
        ' . $single_row_style . '
    ">
        ' . $qty . '
    </td>

    <td style="
        border:1px solid #000;
        padding:3px;
        text-align:center;
        vertical-align:top;
        ' . $single_row_style . '
    ">
        ' . $weight . '
    </td>

    <td style="
        border:1px solid #000;
        padding:3px;
        text-align:center;
        vertical-align:top;
        ' . $single_row_style . '
    ">
        ' . $rate . '
    </td>

    <td style="
        border:1px solid #000;
        padding:3px;
        text-align:center;
        vertical-align:top;
        ' . $single_row_style . '
    ">
        ' . htmlspecialchars(get_client_name($conn, $consigner)) . '
    </td>

    <td style="
        border:1px solid #000;
        padding:3px;
        text-align:left;
        vertical-align:top;
        font-size:7pt;
        ' . $single_row_style . '
    ">
        ' . htmlspecialchars(get_client_name($conn, $consignee)) . '<br>
        ' . $consignee_addr_html . '<br>
        GSTIN : ' . htmlspecialchars($consignee_gst) . '
    </td>

    <td style="
        border:1px solid #000;
        padding:3px;
        text-align:center;
        vertical-align:top;
        font-size:7pt;
        ' . $single_row_style . '
    ">
        ' . htmlspecialchars($mode_name) . '
    </td>

    <td style="
        border:1px solid #000;
        padding:3px;
        text-align:center;
        vertical-align:top;
        ' . $single_row_style . '
    ">
        ' . htmlspecialchars($item['party_invoice_no']) . '
    </td>

    <td style="
        border:1px solid #000;
        padding:3px;
        text-align:right;
        vertical-align:top;
        ' . $single_row_style . '
    ">
        ' . number_format($freight, 2) . '
    </td>

    <td style="
        border:1px solid #000;
        padding:3px;
        text-align:right;
        vertical-align:top;
        ' . $single_row_style . '
    ">
        ' . number_format($dc, 2) . '
    </td>

    <td style="
        border:1px solid #000;
        padding:3px;
        text-align:right;
        vertical-align:top;
        ' . $single_row_style . '
    ">
        ' . number_format($total_line, 2) . '
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
$gst_amount               = $gst_amount ?? 0;

$grand_total_before_round = $sum_total_line + $loading_unloading_amount + $crane_fork_lift_amount
    + $fov_amount + $other_charge_amount + $labour_handling_amount
    + $cod_amount + $cartage_amount + $octroi_amount + $rajdhani_charges + $gst_amount;

$round_off   = $total - $grand_total_before_round;
$grand_total = $round_off + $grand_total_before_round;

if ($is_same_state) {
    $cgst_rate = $gst_rate_full / 2;
    $sgst_rate = $gst_rate_full / 2;
    $cgst_amt  = round($gst_amount / 2, 2);
    $sgst_amt  = round($gst_amount / 2, 2);
} else {
    $igst_rate = $gst_rate_full;
    $igst_amt  = round($gst_amount, 2);
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

TAX INVOICE

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
    height:22px;
">

    <td style="border:1px solid #000;width:5%;padding:2px;text-align:center;">S/No</td>
    <td style="border:1px solid #000;width:8%;padding:2px;text-align:center;">GCN No</td>
    <td style="border:1px solid #000;width:8%;padding:2px;text-align:center;">Date</td>
    <td style="border:1px solid #000;width:4%;padding:2px;text-align:center;">Qty</td>
    <td style="border:1px solid #000;width:6%;padding:2px;text-align:center;">Weight</td>
    <td style="border:1px solid #000;width:5%;padding:2px;text-align:center;">Rate</td>
    <td style="border:1px solid #000;width:15%;padding:2px;text-align:center;">
        Consignor / Consignee
    </td>
    <td style="border:1px solid #000;width:20%;padding:2px;text-align:center;">
        Ship To
    </td>
    <td style="border:1px solid #000;width:7%;padding:2px;text-align:center;">Mode</td>
    <td style="border:1px solid #000;width:8%;padding:2px;text-align:center;">
        Supp.Inv.No.
    </td>
    <td style="border:1px solid #000;width:6%;padding:2px;text-align:center;">
        Freight
    </td>
    <td style="border:1px solid #000;width:4%;padding:2px;text-align:center;">DC</td>
    <td style="border:1px solid #000;width:6%;padding:2px;text-align:center;">Total</td>

</tr>

' . $rows_html . '

<tr style="
    font-weight:bold;
    height:20px;
    page-break-inside:avoid;
">

    <!-- S/No -->
    <td style="
        border:1px solid #000;
        text-align:left;
        padding:2px 5px;
        font-weight:bold;
        vertical-align:middle;
        white-space:nowrap;
    ">
        Total
    </td>

    <!-- GCN No - COUNT -->
    <td style="
        border:1px solid #000;
        text-align:center;
        padding:2px;
        vertical-align:middle;
        font-weight:bold;
        white-space:nowrap;
    ">
        ' . $gcn_count . ' 
    </td>

    <!-- Date -->
    <td style="
        border:1px solid #000;
        padding:2px;
        font-weight:bold;
        vertical-align:middle;
    ">
    </td>

    <!-- Qty -->
    <td style="
        border:1px solid #000;
        text-align:center;
        padding:2px;
        font-weight:bold;
        vertical-align:middle;
    ">
        ' . $sum_qty . '
    </td>

    <!-- Weight -->
    <td style="
        border:1px solid #000;
        text-align:center;
        font-weight:bold;
        padding:2px;
        vertical-align:middle;
    ">
        ' . $sum_weight . '
    </td>

    <!-- Rate -->
    <td style="
        border:1px solid #000;
        padding:2px;
        font-weight:bold;
        vertical-align:middle;
    ">
    </td>

    <!-- Consignor / Consignee -->
    <td style="
        border:1px solid #000;
        padding:2px;
        vertical-align:middle;
    ">
    </td>

    <!-- Ship To -->
    <td style="
        border:1px solid #000;
        padding:2px;
        vertical-align:middle;
    ">
    </td>

    <!-- Mode -->
    <td style="
        border:1px solid #000;
        padding:2px;
        vertical-align:middle;
    ">
    </td>

    <!-- Supp.Inv.No -->
    <td style="
        border:1px solid #000;
        padding:2px;
        vertical-align:middle;
    ">
    </td>

    <!-- Freight -->
    <td style="
        border:1px solid #000;
        text-align:right;
        padding:2px;
        font-weight:bold;
        vertical-align:middle;
    ">
        ' . number_format($sum_freight, 2) . '
    </td>

    <!-- DC -->
    <td style="
        border:1px solid #000;
        text-align:right;
        padding:2px;
        vertical-align:middle;
        font-weight:bold;
    ">
        ' . number_format($sum_dc, 2) . '
    </td>

    <!-- Total -->
    <td style="
        border:1px solid #000;
        text-align:right;
        padding:2px;
        font-weight:bold;
        vertical-align:middle;
    ">
        ' . number_format($sum_total_line, 2) . '
    </td>

</tr>

</table>';

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
            vertical-align:top;
            border-right:1px solid #000;
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

if ($is_same_state) {

    $html .= '

        <!-- CGST -->

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
                OUTPUT- CGST @ ' . $cgst_rate . '%
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
                ' . number_format($cgst_amt, 2) . '
            </td>

        </tr>


        <!-- SGST -->

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
                OUTPUT- SGST @ ' . $sgst_rate . '%
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
                ' . number_format($sgst_amt, 2) . '
            </td>

        </tr>';

} else {

    $html .= '

        <!-- IGST -->

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
                OUTPUT- IGST @ ' . $igst_rate . '%
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
                ' . number_format($igst_amt, 2) . '
            </td>

        </tr>';

}


/* ================================================================
   ROUND OFF + GRAND TOTAL
   ================================================================ */

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
                ' . ($round_off < 0 ? '(-)' : '') . number_format(abs($round_off), 2) . '
            </td>

        </tr>


        <!-- GRAND TOTAL -->

        <tr>

            <td
                width="70%"
                style="
                    width:70%;
                    border-right:1px solid #000;
                    padding:2px 4px;
                    font-weight:bold;
                    line-height:18px;
                    white-space:nowrap;
                "
            >
                GRAND TOTAL
            </td>

            <td
                width="30%"
                style="
                    width:30%;
                    padding:2px 4px;
                    text-align:right;
                    font-weight:bold;
                    line-height:18px;
                    white-space:nowrap;
                "
            >
                ' . number_format($grand_total, 2) . '
            </td>

        </tr>

        </table>

    </td>

</tr>
</table>
';

// ─── SECTION 6: AMOUNT IN WORDS ─────────────────────────────────────────────────
$html .= '
<table border="1" cellpadding="4" cellspacing="0" width="100%" style="border-collapse:collapse;">
<tr><td style="border:1px solid #000;font-weight:bold;font-size:8.5pt;">Amount (In words) : ' . htmlspecialchars($total_words ?? '') . '</td></tr>
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
$mpdf->Output('GST-' . str_replace('/', '-', $unique_invoice_no) . '.pdf', 'I');