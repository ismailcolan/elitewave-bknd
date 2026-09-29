<?php

function quotation_pdf_terms_section($inner_html)
{
	if (!defined('EW_QUOTE_PDF_NAVY')) {
		define('EW_QUOTE_PDF_NAVY', '#021659');
	}
	$navy = EW_QUOTE_PDF_NAVY;

	return '
<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:0 0 6px;border:2px solid ' . $navy . ';">
<tr>
	<td style="border:1px solid ' . $navy . ';font-size:11pt;line-height:17px;text-align:justify;color:#000;padding:12px 14px;vertical-align:top;background:#fff;">'
		. $inner_html
		. '</td>
</tr>
</table>';
}

function quotation_pdf_page2_closing_html()
{
	if (!defined('EW_QUOTE_PDF_NAVY')) {
		define('EW_QUOTE_PDF_NAVY', '#021659');
	}
	if (!defined('EW_QUOTE_PDF_LINE')) {
		define('EW_QUOTE_PDF_LINE', '#021659');
	}
	$navy = EW_QUOTE_PDF_NAVY;
	$line = EW_QUOTE_PDF_LINE;

	return '
<div style="margin-top:10px;font-family:freesans;color:#000;">
	<p style="margin:0 0 10px;font-size:10.5pt;font-weight:bold;">Thanks &amp; Regards,</p>
	<div style="padding-top:8px;border-top:2px solid ' . $navy . ';">
		<table width="100%" cellpadding="0" cellspacing="0">
		<tr>
			<td style="width:62%;vertical-align:bottom;border:0;padding:0;">
				<div style="font-size:9.5pt;font-weight:bold;color:#000;">EliteWave360 Logistics · Expectations Delivered</div>
			</td>
			<td style="width:38%;text-align:right;vertical-align:bottom;border:0;padding:0;">
				<div style="font-size:9.5pt;font-weight:bold;color:' . $navy . ';">For EliteWave360 Logistics</div>
				<div style="font-size:9.5pt;font-weight:bold;color:#000;margin-top:2px;">Authorised Signatory</div>
			</td>
		</tr>
		</table>
	</div>
</div>';
}

/** Page 2: terms (navy borders) then Thanks &amp; Regards. */
function quotation_pdf_terms_page_html()
{
	if (!defined('EW_QUOTE_PDF_NAVY')) {
		define('EW_QUOTE_PDF_NAVY', '#021659');
	}
	$navy = EW_QUOTE_PDF_NAVY;

	$shipment = '
		<b>Shipment Protection &amp; Insurance Advisory :</b> Each consignment must be covered with valid transit insurance. Our liability shall be NIL for any loss or damage
		arising due to any cause, including but not limited to natural calamities, accidents, theft, fire, or unforeseen
		events during transit. Kindly ensure that all consignments are properly packed using poly bags and shrink wrapping
		to prevent moisture exposure and damage. We shall not be held liable for any wetness or damage arising from
		inadequate or improper packing, including consignments not protected with shrink wrap or poly bags.';

	$msds = '
		<b>MSDS (Material Safety Data Sheet) :</b> For <b>inflammable liquids, chemicals, or hazardous cargo</b>, the shipper must declare the material and provide a valid
		<b>MSDS (Material Safety Data Sheet)</b> before booking. Proper packing, labelling, and required documentation are mandatory; undeclared hazardous cargo will not be accepted.';

	$capacity = '
		<b>Carrying Capacity 9 MT To 100 MT :</b> (Full Truck / Part Load / Heavy ODC (Over Dimensional Cargo) / ODC Equipment Bulk &amp; Lengthy Consignment by Open
		Truck / Hippo / Heavy Trailers &amp; Hydraulic Trailer / Trailer Service / Hydraulic Trailer / Hybed / Semi Bed / Low Bed Hydraulic.)
		HYBED-SEMIBED-LOWBED HYDROLIC SPL. IN: 20, 28, 32, 40, 50, 70, 80, 100 FEET Heavy ODC, ODC Equipment Bulk &amp; Lengthy Consignment by Open Truck, Hippo,
		Valvo, Heavy Trailors &amp; Hydraulic Trailer. We can pick up &amp; deliver your cargo PAN India (Presence across India).';

	$terms = '
		<b>Terms and Conditions :</b> (1) Jurisdiction: All disputes shall be subject to the jurisdiction of courts in Tamil Nadu only.
		(2) Claims &amp; Complaints: Any complaint or claim must be submitted in writing within 7 days from the date of booking.
		No claims will be entertained thereafter. (3) Volumetric Weight Calculation (Railway): (Length × Width × Height in CMS) ÷ 4000,
		(4) Volumetric Weight Calculation (Airlines): (Length × Width × Height in centimeters) ÷ 5,000,
		(5) Volumetric Weight Calculation (Road): (Length × Width × Height in centimeters) ÷ 4000.';

	return '
<div style="padding:0 2px;font-family:freesans;color:#000;">
<div style="font-size:12pt;font-weight:bold;color:' . $navy . ';margin:0 0 8px;padding-bottom:4px;border-bottom:2px solid ' . $navy . ';">Terms &amp; conditions</div>'
		. quotation_pdf_terms_section($shipment)
		. quotation_pdf_terms_section($msds)
		. quotation_pdf_terms_section($capacity)
		. quotation_pdf_terms_section($terms)
		. quotation_pdf_page2_closing_html()
		. '
</div>';
}
