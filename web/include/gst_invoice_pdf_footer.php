<?php

function gst_invoice_pdf_footer_html($sac_text, $current_date, $base_dir)
{
    $qr_image_path = rtrim($base_dir, '/') . '/images/original-payment-qr.jpg';
    $has_qr_image = file_exists($qr_image_path);

    $html = '';

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
</table>';

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
    <div style="font-size:8pt;font-weight:bold;line-height:10px;margin-bottom:5px;font-size:9.2pt;">
        Please pay by Cheque/DD/RTGS/NEFT only in favour of
        EliteWave360 Logistics
    </div>
<br>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;font-family:freesans;font-size:9.2pt;line-height:13px;">
        <tr>
            <td width="70" style="border:0;padding:0;font-weight:bold;white-space:nowrap;font-size:9.2pt;">Bank Name</td>
            <td width="8" style="border:0;padding:0;font-weight:bold;">:</td>
            <td style="border:0;padding:0;white-space:nowrap;font-size:9.2pt;">Axis Bank</td>
        </tr>
        <tr>
            <td style="border:0;padding:0;font-weight:bold;white-space:nowrap;font-size:9.2pt;">Account No.</td>
            <td style="border:0;padding:0;font-weight:bold;font-size:9.2pt;">:</td>
            <td style="border:0;padding:0;white-space:nowrap;font-size:9.2pt;">926020021424035</td>
        </tr>
        <tr>
            <td style="border:0;padding:0;font-weight:bold;font-size:9.2pt;white-space:nowrap;">Branch</td>
            <td style="border:0;padding:0;font-weight:bold;">:</td>
            <td style="border:0;padding:0;white-space:nowrap;font-size:9.2pt;">Vepery Chennai - 600007 Tamil Nadu</td>
        </tr>
        <tr>
            <td style="border:0;padding:0;font-weight:bold;font-size:9.2pt;white-space:nowrap;">IFSC Code</td>
            <td style="border:0;padding:0;font-weight:bold;">:</td>
            <td style="border:0;padding:0;font-size:9.2pt;white-space:nowrap;">UTIB0001885</td>
        </tr>
    </table>
</td>
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
    >' .
        ($has_qr_image ? '
        <div style="width:100%;text-align:center;">
            <img src="' . $qr_image_path . '" style="width:30mm;height:30mm;">
            <div style="font-size:7.5pt;font-weight:bold;margin-top:1px;text-align:center;">
               Scan & Pay Your Freight Charges
            </div>
        </div>' : '') . '
    </td>
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
        <div style="width:100%;text-align:center;font-size:11pt;font-weight:bold;color:#021659;line-height:15px;">
            For EliteWave360 Logistics
        </div>
        <div style="height:3px;"></div>
        <div style="width:100%;text-align:center;font-size:15pt;font-weight:bold;color:#111;line-height:18px;">
            AADIL AHMED
        </div>
        <div style="width:100%;text-align:center;font-size:6.5pt;color:#000;line-height:9px;">
            <b>Digitally signed by AADIL AHMED</b><br>
            Date: ' . htmlspecialchars($current_date) . '
        </div>
        <div style="width:100%;text-align:center;font-size:9pt;font-weight:bold;color:#021659;margin-top:2px;line-height:12px;">
            Authorised Signatory
        </div>
    </td>
</tr>
</table>';

    $html .= '
<table border="1" width="100%" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">
<tr><td style="border:1px solid #000;text-align:center;font-size:8.5pt;font-weight:bold;">
    Note : We Do Not Accept Freight In Cash. Please Pay By Cheque / Online Only In Favour Of M/s. EliteWave360 Logistics
</td></tr>
</table>';

    $html .= '
<table border="1" width="100%" cellspacing="0" style="border-collapse:collapse; border:1px solid #000;">
<tr>
    <td style="text-align:center;font-size:9pt;font-weight:bold;color:#021659;width:50%;border:none;">
        This is a Computer generated Freight Invoice, Digitally Signed
    </td>
    <td style="text-align:center;font-size:9pt;font-weight:bold;color:#021659;width:50%;border:none;">
        Visit : www.elitewave360.in
    </td>
</tr>
</table>';

    return $html;
}
