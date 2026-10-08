<?php

function quotation_pdf_terms_navy()
{
	if (!defined('EW_QUOTE_PDF_NAVY')) {
		define('EW_QUOTE_PDF_NAVY', '#021659');
	}
	return EW_QUOTE_PDF_NAVY;
}

function quotation_pdf_terms_accent()
{
	if (!defined('EW_LH_ACCENT')) {
		define('EW_LH_ACCENT', '#dc2626');
	}
	return EW_LH_ACCENT;
}

function quotation_pdf_term_heading_html($title, $is_first = false)
{
	$navy = quotation_pdf_terms_navy();
	$mt = $is_first ? '4px' : '10px';

	return '<div style="font-size:9pt;font-weight:bold;color:#000;margin:' . $mt . ' 0 6px;padding:0 0 5px;border-bottom:1px solid ' . $navy . ';">'
		. $title
		. '</div>';
}

function quotation_pdf_term_body_html($text, $margin_bottom = '8px')
{
	return '<p style="margin:0 0 ' . $margin_bottom . ';font-size:9pt;line-height:1.38;text-align:justify;color:#000;">'
		. $text
		. '</p>';
}

function quotation_pdf_terms_section($inner_html, $padding = '12px 14px', $font_size = '11pt', $line_height = '17px')
{
	$navy = quotation_pdf_terms_navy();

	return '
<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:0 0 6px;border:2px solid ' . $navy . ';">
<tr>
	<td style="border:1px solid ' . $navy . ';font-size:' . $font_size . ';line-height:' . $line_height . ';text-align:justify;color:#000;padding:' . $padding . ';vertical-align:top;background:#fff;">'
		. $inner_html
		. '</td>
</tr>
</table>';
}

/** Signatory block below the terms box, right-aligned (mPDF-safe table layout). */
function quotation_pdf_terms_signatory_html()
{
	$navy = quotation_pdf_terms_navy();
	$cell = 'font-size:9.5pt;line-height:1.5;color:#000;text-align:right;vertical-align:top;padding:0;border:0;';

	$sig = 'font-size:9.5pt;line-height:1.5;color:#000;text-align:right;vertical-align:top;padding:0;border:0;';

	return '
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:10px 0 8px;border:0;">
<tr>
	<td style="width:58%;border:0;padding:0;">&nbsp;</td>
	<td style="width:42%;' . $cell . '">
		<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
		<tr>
			<td style="' . $sig . '">Thanks &amp; Regards,</td>
		</tr>
		<tr>
			<td style="' . $sig . 'height:22mm;min-height:22mm;line-height:22mm;font-size:1pt;">&#160;</td>
		</tr>
		<tr>
			<td style="' . $sig . 'font-weight:bold;color:' . $navy . ';">For EliteWave360 Logistics</td>
		</tr>
		<tr>
			<td style="' . $sig . 'font-weight:bold;padding-top:3px;">Authorised Signatory</td>
		</tr>
		<tr>
			<td style="' . $sig . 'padding-top:3px;">Athar - +91 98408 59711</td>
		</tr>
		</table>
	</td>
</tr>
</table>';
}

function quotation_pdf_terms_brand_bar_html()
{
	if (function_exists('elitewave_pdf_brand_bar_html')) {
		return elitewave_pdf_brand_bar_html('6px', false);
	}
	$navy = quotation_pdf_terms_navy();
	$accent = quotation_pdf_terms_accent();

	return '
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 6px;height:3px;border-collapse:collapse;">
<tr>
	<td style="width:70%;background:' . $navy . ';padding:0;font-size:1px;line-height:3px;border:0;">&nbsp;</td>
	<td style="width:30%;background:' . $accent . ';padding:0;font-size:1px;line-height:3px;border:0;">&nbsp;</td>
</tr>
</table>';
}

function quotation_pdf_terms_brand_footer_html()
{
	$navy = quotation_pdf_terms_navy();
	$accent = quotation_pdf_terms_accent();

	return '
<div style="text-align:center;margin:6px 0 4px;font-family:freesans;font-style:italic;font-size:10pt;line-height:1.4;">
	<span style="color:' . $navy . ';font-weight:bold;font-style:italic;">EliteWave360 Logistics</span>
	<span style="color:#000;font-style:italic;"> · </span>
	<span style="color:' . $accent . ';font-weight:bold;font-style:italic;">Expectations Delivered</span>
</div>';
}

/** @deprecated Closing is embedded in quotation_pdf_terms_body_html(); kept for callers. */
function quotation_pdf_page2_closing_html()
{
	return '';
}

function quotation_pdf_standard_operating_terms_html($compact = false)
{
	$mb = $compact ? '6px' : '8px';
	$h = 'quotation_pdf_term_heading_html';
	$b = 'quotation_pdf_term_body_html';

	$out = $h('1. Packing &amp; Cargo Safety', true);
	$out .= $b('We request the customer to ensure that all consignments are properly packed with suitable poly bags and shrink wrapping to prevent wetness, moisture, or transit damage. EliteWave360 Logistics shall not be liable for any wetness or damage arising due to improper, inadequate, or non-airworthy packing, including consignments without proper shrink wrap/poly bag protection.', $mb);
	$out .= $h('2. GST');
	$out .= $b('GST will be applicable as per the prevailing statutory rates.', $mb);
	$out .= $h('3. Documentation Charges');
	$out .= $b('Documentation (DC) charges of ₹250/- per consignment will be applicable.', $mb);
	$out .= $h('4. Transit Time');
	$out .= $b('The booking/dispatch day will not be considered for calculating the delivery transit time.', $mb);
	$out .= $h('5. Loading &amp; Unloading');
	$out .= $b('Loading and unloading are not included in our freight charges. If required, these services can be arranged at an additional cost.', $mb);
	$out .= $h('6. Labour / Local Charges');
	$out .= $b('Any loading/unloading labour charges, local mamool, union charges, or other incidental charges at the origin or destination will be borne by the customer.', $mb);
	$out .= $h('7. Halting / Detention Charges');
	$out .= $b('If the vehicle is detained at the loading or unloading point for more than 24 hours after reaching the respective location, applicable halting/detention charges will be charged separately.', $mb);
	$out .= $h('8. Insurance');
	$out .= $b('All cargo handed over for transportation must have valid insurance coverage. Any loss or damage occurring during transit shall be covered under the applicable cargo insurance, and EliteWave360 Logistics&apos; liability for such claims shall be nil.<br>
Where requested/required, EliteWave360 Logistics will arrange cargo insurance on behalf of the customer, and the applicable insurance premium/coverage charges will be included separately in the freight bill.', $mb);
	$out .= $h('9. Mandatory Documents');
	$out .= $b('All mandatory documents and forms required for local/interstate transportation must be duly attached with the supplier&apos;s invoice and handed over along with the consignment.', $mb);
	$out .= $h('10. Minimum Chargeable Weight');
	$out .= $b('Air Cargo: Minimum 20 KG · Train Cargo: Minimum 50 KG · Surface Transportation: Minimum 100 KG<br>
<b>Note:</b> The above terms are applicable unless specifically agreed otherwise in writing for a particular shipment.', '0');

	return $out;
}

function quotation_pdf_legacy_terms_paragraphs_html($compact = false)
{
	$mb = $compact ? '6px' : '8px';
	$h = 'quotation_pdf_term_heading_html';
	$b = 'quotation_pdf_term_body_html';

	$shipment = 'Each consignment must be covered with valid transit insurance. Our liability shall be NIL for any loss or damage arising due to any cause, including but not limited to natural calamities, accidents, theft, fire, or unforeseen events during transit. Kindly ensure that all consignments are properly packed using poly bags and shrink wrapping to prevent moisture exposure and damage. We shall not be held liable for any wetness or damage arising from inadequate or improper packing, including consignments not protected with shrink wrap or poly bags.';

	$msds = 'For <b>inflammable liquids, chemicals, or hazardous cargo</b>, the shipper must declare the material and provide a valid <b>MSDS (Material Safety Data Sheet)</b> before booking. Proper packing, labelling, and required documentation are mandatory; undeclared hazardous cargo will not be accepted.';

	$capacity = '(Full Truck / Part Load / Heavy ODC / ODC Equipment / Open Truck / Hippo / Heavy Trailers &amp; Hydraulic Trailer.) HYBED-SEMIBED-LOWBED HYDROLIC SPL. IN: 20, 28, 32, 40, 50, 70, 80, 100 FEET. We can pick up &amp; deliver your cargo PAN India.';

	$terms = '(1) Jurisdiction: All disputes shall be subject to the jurisdiction of courts in Tamil Nadu only. (2) Claims &amp; Complaints: Any complaint or claim must be submitted in writing within 7 days from the date of booking. (3) Volumetric Weight (Railway): (L×W×H in CMS)÷4000. (4) Volumetric Weight (Airlines): (L×W×H in CMS)÷5,000. (5) Volumetric Weight (Road): (L×W×H in CMS)÷4000.';

	$out = $h('Shipment Protection &amp; Insurance Advisory');
	$out .= $b($shipment, $mb);
	$out .= $h('MSDS (Material Safety Data Sheet)');
	$out .= $b($msds, $mb);
	$out .= $h('Carrying Capacity 9 MT To 100 MT');
	$out .= $b($capacity, $mb);
	$out .= $h('Terms and Conditions');
	$out .= $b($terms, '0');

	return $out;
}

function quotation_pdf_all_terms_inner_html()
{
	$inner = quotation_pdf_standard_operating_terms_html(true) . quotation_pdf_legacy_terms_paragraphs_html(true);

	return '<div style="column-count:2;column-gap:10px;-webkit-column-count:2;text-align:justify;font-size:9pt;line-height:1.38;">' . $inner . '</div>';
}

/** Page 2: title, terms box, right signatory, navy/red bar, centered tagline. */
function quotation_pdf_terms_body_html()
{
	$navy = quotation_pdf_terms_navy();

	$terms_title = '
	<div style="margin:0 0 0;">
		<div style="font-size:12pt;font-weight:bold;color:' . $navy . ';margin:0 0 4px;padding:0;">Terms &amp; conditions</div>'
		. (function_exists('elitewave_pdf_brand_bar_html') ? elitewave_pdf_brand_bar_html('8px', true) : '')
		. '
	</div>';

	return '
<div class="ew-quote-page2-body" style="padding:0 2px;font-family:freesans;color:#000;">'
		. $terms_title
		. quotation_pdf_terms_section(quotation_pdf_all_terms_inner_html(), '10px 12px', '9pt', '1.38')
		. quotation_pdf_terms_signatory_html()
		. quotation_pdf_terms_brand_bar_html()
		. quotation_pdf_terms_brand_footer_html()
		. '
</div>';
}

/** Static HTML preview (email/HTML export). */
function quotation_pdf_terms_page_html()
{
	return quotation_pdf_terms_body_html();
}
