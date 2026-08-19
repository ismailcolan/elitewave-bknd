<?php
require_once('include/connect.php');
require_once('include/function.php');

require_once __DIR__ . '/vendor/autoload.php';

$month          = $_GET['month'];
$year           = $_GET['year'];
$transaction_id = $_GET['id'];

$query  = "SELECT * FROM transaction_" . $month . "_" . $year . " WHERE transaction_id='" . $transaction_id . "'";
$result = mysqli_query($conn, $query);
$row    = mysqli_fetch_assoc($result);
extract($row);

// ─── Copy label ───────────────────────────────────────────────────────────────
if (isset($_GET['copy'])) {
    switch ($_GET['copy']) {
        case 'consignor': $copy = 'CONSIGNER COPY';  break;
        case 'consignee': $copy = 'CONSIGNEE COPY'; break;
        case 'pod':       $copy = 'POD COPY';        break;
        case 'accounts':  $copy = 'ACCOUNTS COPY';  break;
        default:          $copy = 'ORIGINAL COPY';
    }
} else {
    $copy = 'CONSIGNER COPY';
}

// ─── Cancelled-watermark class ────────────────────────────────────────────────
if($booking_status==1){

$mpdf->SetWatermarkImage(
    'images/pdf/cancel2.png',
    0.35,
    '',
    [60,110]
);

$mpdf->showWatermarkImage=true;

}

// ─── Mpdf setup ──────────────────────────────────────────────────────────────
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
  'default_font' => 'freesans',
    'margin_left' => 5,
    'margin_right' => 5,
    'margin_top' => 5,
    'margin_bottom' => 5
]);

$mpdf->SetTitle($copy);
$mpdf->SetAuthor('EliteWave360 Logistics');
// ─── Company data ─────────────────────────────────────────────────────────────
$company_result = mysqli_query($conn, "SELECT * FROM company WHERE status=0");
$company_row    = mysqli_fetch_array($company_result);
$company_pan = $company_row['pan_no'];

// ─── Client data ──────────────────────────────────────────────────────────────
$consignor_det = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM client WHERE client_id='" . $consigner . "'"));
$consignee_det = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM client WHERE client_id='" . $consignee . "'"));

// ─── Invoice rows ─────────────────────────────────────────────────────────────
$package_names = [];
$inv_result2 = mysqli_query($conn,
    "SELECT * FROM transaction_invoice_" . $month . "_" . $year .
    " WHERE transaction_id='" . $transaction_id . "'"
);
while ($r = mysqli_fetch_assoc($inv_result2)) {
    $pkg = get_package_name($conn, $r['type_of_pkge']);
    if (!empty($pkg)) $package_names[] = $pkg;
}
$package_names = array_unique($package_names);
$all_packages  = implode(', ', $package_names);

$total_pkgs    = 0;
$total_gross   = 0;
$total_charged = 0;
$first_inv     = [];

$inv_result3 = mysqli_query($conn,
    "SELECT * FROM transaction_invoice_" . $month . "_" . $year .
    " WHERE transaction_id='" . $transaction_id . "'"
);
while ($r = mysqli_fetch_assoc($inv_result3)) {
    if (empty($first_inv)) $first_inv = $r;
    $total_pkgs    += (int)$r['no_of_pkge'];
    $total_gross   += (float)$r['gross_weight'];
    $total_charged += (float)$r['charged_weight'];
}

//QR generation
function getQrPackageCode($id)
{
    switch ($id) {
        case '1': return 'CBX';
        case '2': return 'PBG';
        case '3': return 'ROL';
        case '5': return 'SHT';
        case '6': return 'BDL';
        case '7': return 'CVR';
        case '8': return 'PBL';
        case '9': return 'CAN';
        case '10': return 'BOX';
        case '11': return 'BAG';
        case '12': return 'MLD';
        case '13': return 'PKT';
        case '14': return 'CES';
        case '15': return 'CAT';
        case '16': return 'GRL';
        case '17': return 'P.B';
        case '18': return 'PRL';
        default: return '';
    }
}
$package_code = getQrPackageCode($first_inv['type_of_pkge']);
$pattern = __DIR__ . "/qrcode/" . strtoupper($grn_no) . $package_code . "-*.png";

$files = glob($pattern);

$qrImage = '';

if (!empty($files)) {
    $qrImage = $files[0];
}

// Volumetric weight
$d1 = (float)$dimension1;
$d2 = (float)$dimension2;
$d3 = (float)$dimension3;
$volumetric_weight_calc = ($d1 && $d2 && $d3) ? round(($d1 * $d2 * $d3) / 4000, 2) : '';

// Dimension display
$dim_display = ($d1 || $d2 || $d3)
    ? trim($dimension1) . ' X ' . trim($dimension2) . ' X ' . trim($dimension3)
      . ($dimension4 ? ' X ' . $dimension4 : '') . '-'
    : '0 X 0 X 0 -';

// ─── Mode checkboxes ──────────────────────────────────────────────────────────
$mode_name = strtoupper(trim(get_mode($conn, $mode_of_transportation)));

$mode_result = mysqli_query(
    $conn,
    "SELECT sac_code FROM mode_of_transportation
     WHERE mode_id='" . $mode_of_transportation . "'"
);

$mode_row = mysqli_fetch_assoc($mode_result);

$sac_code = !empty($mode_row['sac_code']) ? $mode_row['sac_code'] : '-';

switch ($mode_name) {

    case 'PREMIUM AIR CARGO':
        $chk_air = 'YES';
        break;

    case 'PREMIUM TRAIN CARGO':
        $chk_rail = 'YES';
        break;

    case 'EXPRESS DELIVERY':
        $chk_exp = 'YES';
        break;

    case 'ROAD FREIGHT':
        $chk_road = 'YES';
        break;

    case 'FULL TRUCK LOAD':
        $chk_ftl = 'YES';
        break;

    case 'PART LOAD':
        $chk_ptl = 'YES';
        break;
}

// ─── Address helper ───────────────────────────────────────────────────────────
function fmt_addr($det, $conn)
{
    $parts = [];
    if (!empty($det['address1']) && strtoupper(trim($det['address1'])) != 'NULL')
        $parts[] = trim($det['address1']);
    if (!empty($det['address2']) && strtoupper(trim($det['address2'])) != 'NULL')
        $parts[] = trim($det['address2']);
    $city = get_city_name($conn, $det['city']);
    if (!empty($city) && strtoupper(trim($city)) != 'NULL')
        $parts[] = trim($city);
    if (!empty($det['pincode']) && strtoupper(trim($det['pincode'])) != 'NULL')
        $parts[] = trim($det['pincode']);
    return implode(', ', $parts);
}

$consignor_addr   = fmt_addr($consignor_det, $conn);
$consignee_addr   = fmt_addr($consignee_det, $conn);

// Shipping Address display
if (trim($shipping_address) == '') {

    $shipping_name_display = get_client_name($conn, $consignee);

    $shipping_addr_display =
        $consignee_det['address1'];

    if (!empty($consignee_det['address2'])) {
        $shipping_addr_display .= '<br>' . $consignee_det['address2'];
    }

    $shipping_addr_display .= '<br>'
        . get_city_name($conn, $consignee_det['city']) . ', '
        . get_state_name($conn, $consignee_det['state']) . ' '
        . $consignee_det['pincode'];

    $shipping_gst_display   = $consignee_det['gst_no'];
    $shipping_phone_display = $consignee_det['contact_no'];

} else {

    $shipping_name_display  = $shipping_address_name;
    $shipping_addr_display  = nl2br($shipping_address);
    $shipping_gst_display   = $shipping_gst_no;
    $shipping_phone_display = $shipping_phone;
}

//Time and dare
$booking_datetime = date('d-m-Y', strtotime($grn_date));

if (!empty($booking_time)) {
    $booking_datetime .= ' ('.date('H:i:s', strtotime($booking_time)).')';
}

//for tracking
$tracking_code_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT tracking_code FROM transaction_log WHERE grn_no='" . $grn_no . "' LIMIT 1"));
$tracking_code = $tracking_code_row ? $tracking_code_row['tracking_code'] : '';
$tracking_display = trim(chunk_split($tracking_code, 4, ' '));

$mode_of_pay = '';

$consignment_query = mysqli_query(
    $conn,
    "SELECT mode_of_pay
     FROM consignment_mode
     WHERE consignment_id = '".$mode_of_consignment."'"
);

if(mysqli_num_rows($consignment_query) > 0){
    $consignment_row = mysqli_fetch_assoc($consignment_query);
    $mode_of_pay = $consignment_row['mode_of_pay'];
}

// ─── Helper: truncate text to a max character count for fixed cells ───────────
// For very long strings, we truncate with ellipsis so fixed-height cells don't overflow.
function truncate_text($text, $max = 120) {
    if (strlen($text) > $max) {
        return substr($text, 0, $max - 3) . '...';
    }
    return $text;
}

// ─── Prepare address strings (truncated for fixed cells) ─────────────────────
// FIX: We limit address lines to prevent overflow of fixed-height cells.
// Each address cell is 28mm tall. At 9pt ~ 3.2mm per line, we can fit ~8 lines.
// We format as individual lines for readability and clip at ~100 chars per address.
function split_addr_lines($addr, $max_chars = 95) {
    // Insert line breaks at commas if the full string is very long
    if (strlen($addr) <= $max_chars) return $addr;
    // Break at commas, max ~50 chars per chunk
    $parts = explode(', ', $addr);
    $lines = [];
    $current = '';
    foreach ($parts as $p) {
        if (strlen($current) + strlen($p) + 2 > 65) {
            if ($current) $lines[] = $current;
            $current = $p;
        } else {
            $current = $current ? $current . ', ' . $p : $p;
        }
    }
    if ($current) $lines[] = $current;
    return implode('<br>', $lines);
}

$consignor_addr_html = split_addr_lines($consignor_addr);
$consignee_addr_html = split_addr_lines($consignee_addr);

// ══════════════════════════════════════════════════════════════════════════════
// A4 PAGE LAYOUT STRATEGY
// ──────────────────────────────────────────────────────────────────────────────
// A4 = 297mm tall. Margins top+bottom = 10mm. Usable = 287mm.
//
// Section heights (mm):
//   Header              : ~28mm
//   Origin/Dest/Mode    : ~38mm
//   Consignor/Bill To   : ~28mm (FIXED MIN)
//   Shipping Address    : ~14mm (FIXED MIN)
//   Package grid (13r)  : ~65mm
//   Notes/T&C           : ~90mm (static, fills remaining space)
//   Total               : ~263mm  (leaves ~24mm breathing room for longer content)
//
// KEY TECHNIQUE: Use explicit `height` in TD styles.
// TCPDF respects `height` as a MINIMUM cell height when content is short,
// but allows expansion when content is long. This is the correct approach
// since TCPDF does NOT support CSS min-height.
// ══════════════════════════════════════════════════════════════════════════════

$css = '
<style>

body{
    font-family: freesans;
    font-size:7.5pt;
}

table{
    border-collapse:collapse;
}

td{
    font-family:freesans;
    font-size:7.5pt;
    vertical-align:middle;
}

b,strong{
    font-weight:bold;
}

</style>';

$html = $css;

// ══════════════════════════════════════════════════════════════════════════════
// SECTION 1 – HEADER
// FIX: Fixed height on header row ensures logo area is always consistent.
// ══════════════════════════════════════════════════════════════════════════════
$html .= '
<div style="
width:100%;
text-align:center;
font-size:10pt;
font-weight:bold;
margin:2px 0 0 60px;
">
Goods Consignment Note
</div>
';

$html .= '

<table style="width:100%;border-collapse:collapse;">


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

E-Mail : info@elitewave360.in &nbsp;&nbsp;
www.elitewave360.in

</div>

</td>

<td style="
width:15%;
text-align:right;
vertical-align:top;
font-size:8pt;
font-weight:bold;
">

('.$copy.')

</td>

</tr>



</table>

';

$html .= '
<table style="width:100%;border-collapse:collapse;">
<tr>

<td style="
width:50%;
font-size:9.2pt;
font-weight:bold;
text-align:left;
">

(Transporter ID) GSTIN : '.$company_row['gst_no'].'

</td>

<td></td>

<td style="
width:50%;
font-size:9.2pt;
font-weight:bold;
text-align:right;
">

PAN : '.$company_row['pan_no'].'

</td>

</tr>
</table>

';
/// ══════════════════════════════════════════════════════════════════════════════
// SECTION 2 – ORIGIN / DESTINATION / MODE / GCN
// FIX: Each row has explicit height to keep this block at ~36mm regardless
//      of city name length. Origin/Dest cells use rowspan with forced height.
// ══════════════════════════════════════════════════════════════════════════════
$qrSection = '&nbsp;';

if (!empty($qrImage) && file_exists($qrImage)) {

    $qrSection = '
        <div style="text-align:center;padding-top:8px;width:150px;">
            <img src="'.$qrImage.'" style="">
        </div>
    ';
}

$html .= '

<table border="1" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;font-family:helvetica;">

<tr>

    <!-- QR -->
    <td width="18%" rowspan="6" align="center" valign="middle" style="height:48mm;">

       <img src="'.$qrImage.'" style="width:130px;">

    </td>

    <!-- DESTINATION HEADER -->
    <td width="28%" align="center"
        style="height:6mm;font-size:10pt;font-weight:bold;">

        ORIGIN & DESTINATION

    </td>

    <!-- MODE -->
    <td width="16%" style="padding-left:3px;font-size:8pt;font-weight:bold;">
        Full Truck Load
    </td>

    <td width="6%" align="center">
        '.$chk_ftl.'
    </td>

    <!-- GCN -->
    <td width="12%" align="right"
        style="font-size:9pt;font-weight:bold;padding-right:3px;">

        GCN No.

    </td>

    <td width="20%" align="center"
        style="font-size:12pt;font-weight:bold;">

        '.$grn_no.'

    </td>

</tr>

<tr>

    <!-- DESTINATION BODY -->
    <td rowspan="5"
        align="center"
        valign="middle"
        style="font-size:11pt;font-weight:bold;line-height:18px; padding-top:5px;padding-bottom:5px;">

        '.get_city_state_name($conn,$origin).'

        <br><br>

        <span style="font-size:24pt;">&#8597;</span>

        <br><br>

        '.get_city_state_name($conn,$destination).'

    </td>

    <td style="padding-left:3px;font-size:8pt;font-weight:bold;">
        Part Load
    </td>

    <td align="center">
        '.$chk_ptl.'
    </td>

    <td align="right"
        style="font-size:9pt;font-weight:bold;padding-right:3px;">

        PNR No.

    </td>

    <td align="center"
        style="font-size:14pt;font-weight:bold;color:#021659;">

       '.$tracking_display.'

    </td>

</tr>

<tr>

    <td style="padding-left:3px;font-size:8pt;font-weight:bold;">
        Express Delivery
    </td>

    <td align="center">
        '.$chk_exp.'
    </td>

    <td align="right"
        style="font-size:9pt;font-weight:bold;padding-right:3px;" rowspan="2">

        Date &amp; Time

    </td>
	
    <td align="center"
        style="font-size:10pt;font-weight:bold;" rowspan="2">

        '.$booking_datetime.'

    </td>

</tr>

<tr>

    <td style="padding-left:3px;font-size:8pt;font-weight:bold;">
        Road Freight
    </td>

    <td align="center">
        '.$chk_road.'
    </td>

    

</tr>

<tr>

    <td style="padding-left:3px;font-size:8pt;font-weight:bold;" >
        Premium Air Cargo
    </td>

    <td align="center">
        '.$chk_air.'
    </td>

<td align="right"
        style="font-size:9pt;font-weight:bold;padding-right:3px;" rowspan="2">
        SAC CODE
    </td>

    <td align="center"
        style="font-size:12pt;font-weight:bold;" rowspan="2">
        '.$sac_code.'
    </td>
</tr>

<tr>

    <td style="padding-left:3px;font-size:8pt;font-weight:bold;">
        Premium Train Cargo
    </td>

    <td align="center">
        '.$chk_rail.'
    </td>

</tr>

</table>

';
// ══════════════════════════════════════════════════════════════════════════════
// SECTION 3 – CONSIGNOR / BILL TO / SHIPPING ADDRESS
// FIX: height:28mm on address cells guarantees minimum height.
//      Content is limited via split_addr_lines() to prevent overflow.
//      Shipping address row has height:14mm minimum.
// ══════════════════════════════════════════════════════════════════════════════
$html .= '
<table border="1" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">

<tr>

<td width="50%" valign="top"
style="padding:4px 5px;height:24mm;font-size:8.5pt;line-height:14px;">

<span style="font-size:9pt;font-weight:bold;">Consignor Address</span><br>

<b>'.get_client_name($conn,$consigner).'</b>

'.$consignor_addr_html.'<br>

<b>GST No : '.$consignor_det['gst_no'].'</b><br>

<b>Phone No : '.$consignor_det['contact_no'].'</b>

</td>

<td width="50%" valign="top"
style="padding:4px 5px;height:24mm;font-size:8.5pt;line-height:14px;">

<span style="font-size:9pt;font-weight:bold;">Bill To</span><br>

<b>'.get_client_name($conn,$consignee).'</b><br>

'.$consignee_addr_html.'<br>

<b>GST No : '.$consignee_det['gst_no'].'</b><br>

<b>Phone No : '.$consignee_det['contact_no'].'</b>

</td>

</tr>

<tr>

<td colspan="2"
align="center"
style="padding:4px 5px;line-height:14px;">

<div style="font-size:9pt;font-weight:bold;">
Shipping Address
</div>

<div style="font-size:9pt;font-weight:bold;">
'.$shipping_name_display.'
</div>

<div style="font-size:8.5pt;">
'.str_replace('<br>', ', ', $shipping_addr_display).'
</div>

<div style="font-size:8.5pt;font-weight:bold;">
GST No : '.$shipping_gst_display.'
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Phone No : '.$shipping_phone_display.'
</div>

</td>

</tr>

</table>';

// ══════════════════════════════════════════════════════════════════════════════
// SECTION 4 – PACKAGE DETAILS GRID
// FIX: Each row has explicit height:5.5mm. TCPDF will expand if content is
//      longer, but for typical short values it stays compact and consistent.
//      The "Volumetric Dimensions" and signature rows have larger fixed heights.
// ══════════════════════════════════════════════════════════════════════════════
$html .= '
<table border="1" cellpadding="3" cellspacing="0" width="100%"
       style="border-collapse:collapse;font-size:8pt;">

<tr style="height:5.5mm;">
  <td width="20%" style="font-size:9pt;">No.Of Pkgs</td>
  <td width="25%" align="center" style="font-size:9pt;"><b>' . $total_pkgs . '</b></td>
  <td width="30%" style="font-size:9pt;">CFS / Port / Factory / Warehouse</td>
  <td width="25%" style="font-size:8.5pt;"><b>' . $cfs . '</b></td>
</tr>
<tr style="height:5.5mm;">
  <td style="font-size:9pt;">Gross Weight</td>
  <td align="center" style="font-size:9pt;"><b>' . $total_gross . ' Kgs</b></td>
  <td style="font-size:8.1pt;">Part Number / Article Name / Article Number</td>
  <td style="font-size:8pt;font-weight:bold;">' . $vehicle_purchase_contact_person . '</td>
</tr>
<tr style="height:5.5mm;">
  <td style="font-size:9pt;">Charged Weight</td>
  <td align="center" style="font-size:9pt;"><b>' . number_format($total_charged, 0) . ' Kgs</b></td>
  <td style="font-size:9pt;">Quotation Approval</td>
  <td style="font-size:8.5pt;font-weight:bold;">' . $quotation_approval . '</td>
</tr>
<tr style="height:5.5mm;">
  <td style="font-size:9pt;">Volumetric Weight</td>
  <td align="center" style="font-size:9pt;"><b>' . $volumetric_weight_calc . '</b></td>
  <td style="font-size:9pt;">Vehicle Number</td>
  <td style="font-size:9pt;">' . $truck . '</td>
</tr>
<tr style="height:5.5mm;">
  <td style="font-size:9pt;">Type of Packing</td>
  <td align="center" style="font-size:9pt;"><b>' . $all_packages . '</b></td>
  <td style="font-size:9pt;">Freight Paid By</td>
  <td style="font-size:9pt;font-weight:bold;">'.$mode_of_pay.'</td>
</tr>
<tr style="height:5.5mm;">
  <td style="font-size:9pt;">Party Invoice No</td>
  <td align="center" style="font-size:9pt;"><b>' . ($first_inv['party_invoice_no'] ?? '') . '</b></td>
  <td style="font-size:9pt;">Insurance Number</td>
  <td style="font-size:9pt;">' . $insurance_number . '</td>
</tr>
<tr style="height:5.5mm;">
  <td style="font-size:9pt;">Party Invoice Date</td>
 <td align="center" style="font-size:9pt;"><b>' .
      (!empty($first_inv['party_invoice_date']) ? date('d-m-Y', strtotime($first_inv['party_invoice_date'])) : '')
      . '</b></td>
  <td style="font-size:9pt;">Vehicle Type</td>
  <td style="font-size:8pt;"><b>' . $vehicle_type . '</b></td>
</tr>
<tr style="height:5.5mm;">
  <td style="font-size:9pt;">Supplier Invoice Value</td>
  <td align="center" style="font-size:9pt;"><b>Rs.' . number_format((float)$supplier_invoice_value, 2) . '</b></td>
  <td style="font-size:9pt;">HighLoad Challan</td>
  <td style="font-size:9pt;">' . $highload_challan . '</td>
</tr>
<tr style="height:5.5mm;">
  <td style="font-size:9pt;">Eway Bill Number</td>
  <td align="center" style="font-size:9pt;"><b>' . $eway_number . '</b></td>
  <td style="font-size:9pt;">Document Charges</td>
  <td style="font-size:9pt;"><b>' . $doc_amount . '</b></td>
</tr>
<tr style="height:5.5mm;">
  <td style="font-size:9pt;">Eway Bill Expiry Date</td>
  <td align="center" style="font-size:9pt;"><b>' . $eway_expirydate . '</b></td>
  <td style="font-size:9pt;">Mamul Charges</td>
  <td style="font-size:9pt;">' . $mamul_charge . '</td>
</tr>
<tr style="height:5.5mm;">
  <td style="font-size:9pt;">LC Number</td>
  <td align="center" style="font-size:9pt;">' . $lc_number . '</td>
  <td style="font-size:9pt;">Vehicle Halting Charges</td>
  <td style="font-size:9pt;">' . $vehicle_halting_charge . '</td>
</tr>
<tr style="height:8mm;">
  <!-- FIX: Description row gets extra height since it may contain longer text -->
  <td style="font-size:9pt;vertical-align:top;padding-top:3px;">Description of Goods</td>
  <td style="font-size:9pt;vertical-align:top;padding-top:3px;font-weight:bold;">' . $description_of_goods . '</td>
  <td style="font-size:9pt;">Vehicle Loading/Unloading</td>
  <td style="font-size:9pt;">' . $vehicle_loading_unloading . '</td>
</tr>
<tr>
  <!-- FIX: height:16mm for the dimensions + branding row keeps it balanced -->
  <td colspan="2" align="center"
      style="padding:4px 3px;vertical-align:middle;height:16mm;">
    <span style="font-size:9pt;font-weight:bold;">Volumetric Dimensions - Length x Width x Height</span><br><br>
    <span style="font-size:9pt;">' . $dim_display . '</span>
  </td>
  <td colspan="2" align="center" style="vertical-align:middle;padding:4px 3px;height:16mm;">
    <span style="font-size:9pt;color:#021659;font-weight:bold;">For EliteWave360 Logistics</span><br>
    <span style="font-size:7.5pt;color:#021659;font-style:italic;line-height:10px;font-weight:bold;">
      For Booking Enquiry Contact.No : +91 9840859711 / +91 9382307611 / email : info@elitewave360.in
    </span>
  </td>
</tr>
<tr>
  <!-- FIX: height:14mm gives adequate space for the signature area -->
  <td colspan="2" align="center"
      style="height:14mm;vertical-align:middle;padding:3px;">
    <b style="font-size:8pt;">Receiver\'s Signature with Stamp &amp; Date &amp; Time</b>
  </td>
  <td colspan="2" align="center" style="vertical-align:middle;padding:3px;height:14mm;">
    <b style="font-size:9pt;">' . $copy . '</b><br>
    <span style="font-size:8.5pt;">(PROOF OF DELIVERY COPY)</span>
  </td>
</tr>
</table>';

// ══════════════════════════════════════════════════════════════════════════════
// SECTION 5 – NOTES, T&C, PAYMENT (STATIC)
// These sections are static and fill the bottom of the page.
// FIX: No changes needed here — static content is naturally consistent.
//      We just ensure the table borders are tight and text is properly sized.
// ══════════════════════════════════════════════════════════════════════════════
/* ============================================================
   SHIPMENT PROTECTION
============================================================ */

$html .= '

<table border="1" width="100%" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">

<tr>
<td width="84%"
style="border:1px solid #000;font-size:8pt;line-height:13px;text-align:justify;">
<b>Shipment Protection & Insurance Advisory : </b>
Each consignment must be covered with valid transit insurance. Our liability shall be NIL for any loss or damage arising due to any cause, including but not limited to natural calamities, accidents, theft, fire or unforeseen events during transit. Kindly ensure that all consignments are properly packed using poly bags and shrink wrapping to prevent moisture exposure and damage. We shall not be held liable for any wetness or damage arising from inadequate or improper packing, including consignments not protected with shrink wrap or poly bags.

</td>

</tr>

</table>';

/* ============================================================
   SHIPMENT PROTECTION
============================================================ */

$html .= '

<table border="1" width="100%" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">

<tr>
<td width="84%"
style="border:1px solid #000;font-size:8pt;line-height:13px;text-align:justify;">
<b>MSDS (Material Safety Data Sheet) : </b>
For <b>inflammable liquids, chemicals, or hazardous cargo </b>, the shipper must declare the material and provide a valid <b>MSDS (Material Safety Data Sheet)</b> before booking. Proper packing, labelling, and required documentation are mandatory; undeclared hazardous cargo will not be accepted.
</td>

</tr>

</table>';


/* ============================================================
   SERVICES
============================================================ */

$html .= '

<table border="1" width="100%" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">

<tr>

<td style="border:1px solid #000;font-size:8pt;line-height:13px;text-align:justify;">
<b>Carrying Capacity 9 MT To 100 MT : </b>
 (Full Truck / Part Load / Heavy ODC (Over Dimensional Cargo) / ODC Equipment Bulk & Lengthy Consignment by Open Truck / Hippo / Heavy Trailers & Hydraulic Trailer / Trailer Service / Hydraulic Trailer / Hybed / Semi Bed / Low Bed Hydraulic.)

We can pick up & deliver your cargo PAN India (Presence across India).

</td>

</tr>

</table>';


/* ============================================================
   TERMS
============================================================ */

$html .= '

<table border="1" width="100%" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">

<tr>

<td style="border:1px solid #000;font-size:8pt;line-height:13px;text-align:justify;">

<b>Terms & Conditions :</b>

(1) Jurisdiction : All disputes shall be subject to the jurisdiction of courts in Tamil Nadu only.

(2) Claims & Complaints : Any complaint or claim must be submitted within 7 days from booking.

(3) Railway Volumetric Weight :
(Length × Width × Height in cms) ÷ 4000.

(4) Airlines Volumetric Weight :
(Length × Width × Height in centimeters) ÷ 5000.

(5) Road Volumetric Weight :
(Length × Width × Height in centimeters) ÷ 4000.

</td>

</tr>

</table>';


/* ============================================================
   NOTE
============================================================ */

$html .= '

<table border="1" width="100%" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">

<tr>

<td align="center"
style="font-size:8.5pt;line-height:13px;">

<b>

NOTE : WE DO NOT ACCEPT FREIGHT IN CASH.

PLEASE PAY BY CHEQUE / ONLINE ONLY IN FAVOUR OF<br>

M/s. EliteWave360 Logistics

</b>

</td>

</tr>

</table>';


/* ============================================================
   BANK
============================================================ */

$html .= '

<table border="1" width="100%" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">

<tr>

<td align="center"
style="font-size:8.7pt;font-weight:bold;">

Bank : Axis Bank |
A/c No : 926020021424035 |
IFSC Code : UTIB0001885 |
Branch : Vepery, Chennai - 600007 Tamil Nadu

</td>

</tr>

</table>';


/* ============================================================
   FOOTER
============================================================ */

$html .= '

<table border="1" width="100%" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">

<tr>

<td align="center"
style="font-size:9pt;font-weight:bold;color:#021659;">

This is a Computer generated POD Copy, So signature is not required

</td>

</tr>

</table>';


// ─── Render → PDF ─────────────────────────────────────────────────────────────
$mpdf->WriteHTML($html);

$mpdf->Output(
    'EW360_'.$grn_no.'_'.$copy.'.pdf',
    'I'
);
?>