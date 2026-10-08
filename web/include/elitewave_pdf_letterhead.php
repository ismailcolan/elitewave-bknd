<?php

/**
 * Modern EliteWave360 PDF letterhead (mPDF HTML). Theme: navy #021659, accent #dc2626.
 */
define('EW_LH_NAVY', '#021659');
define('EW_LH_ACCENT', '#dc2626');
define('EW_LH_TEXT', '#000000');
define('EW_LH_LABEL', '#021659');
define('EW_LH_SECONDARY', '#000000');
define('EW_LH_LINE', '#021659');

function elitewave_letterhead_company_ids($conn)
{
	$gstin = '';
	$pan = '';
	$q = @mysqli_query($conn, 'SELECT gst_no, pan_no FROM company WHERE status=0 LIMIT 1');
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$gstin = trim((string) ($row['gst_no'] ?? ''));
		$pan = trim((string) ($row['pan_no'] ?? ''));
	}
	return array('gstin' => $gstin, 'pan' => $pan);
}

/**
 * Single split bar (letterhead style): navy + red.
 * @param bool $red_on_left false = blue ~70% left + red ~30% right; true = red left + blue right.
 */
function elitewave_pdf_brand_bar_html($margin_bottom = '8px', $red_on_left = false)
{
	$navy = EW_LH_NAVY;
	$red = EW_LH_ACCENT;
	$mb = htmlspecialchars(trim((string) $margin_bottom), ENT_QUOTES, 'UTF-8');
	if ($red_on_left) {
		$left_w = '30%';
		$left_bg = $red;
		$right_w = '70%';
		$right_bg = $navy;
	} else {
		$left_w = '70%';
		$left_bg = $navy;
		$right_w = '30%';
		$right_bg = $red;
	}

	return '
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 ' . $mb . ';height:3px;border-collapse:collapse;">
<tr>
	<td style="width:' . $left_w . ';background:' . $left_bg . ';padding:0;font-size:1px;line-height:3px;border:0;">&nbsp;</td>
	<td style="width:' . $right_w . ';background:' . $right_bg . ';padding:0;font-size:1px;line-height:3px;border:0;">&nbsp;</td>
</tr>
</table>';
}

/** @param bool $red_on_left See elitewave_pdf_brand_bar_html(). */
function elitewave_pdf_accent_double_rule_html($margin_bottom = '8px', $red_on_left = false)
{
	return elitewave_pdf_brand_bar_html($margin_bottom, $red_on_left);
}

function elitewave_pdf_letterhead_css()
{
	return '
<style>
body{ font-family:freesans; font-size:10.5pt; color:' . EW_LH_TEXT . '; line-height:1.4; }
.ew-doc-secondary{ color:' . EW_LH_SECONDARY . '; font-size:9.5pt; line-height:1.4; font-weight:bold; }
.ew-doc-title{ font-size:12pt; font-weight:bold; color:' . EW_LH_NAVY . '; margin:0; }
.ew-doc-subtitle{ font-size:10pt; color:' . EW_LH_NAVY . '; font-weight:bold; margin:0; line-height:1.25; }
.ew-doc-h2{ font-size:11pt; font-weight:bold; color:' . EW_LH_NAVY . '; margin:14px 0 8px; padding-bottom:4px; border-bottom:2px solid ' . EW_LH_NAVY . '; }
.ew-doc-label{ color:' . EW_LH_LABEL . '; font-size:10pt; font-weight:bold; margin-bottom:2px; }
.ew-doc-value{ color:' . EW_LH_TEXT . '; font-size:10.5pt; font-weight:bold; margin-top:0; }
</style>';
}

/**
 * @param string $document_title Main title (e.g. Rate Quotation)
 * @param string $document_sub   Optional subtitle
 */
function elitewave_pdf_letterhead_open($conn, $document_title, $document_sub = '', $open_content = true, $compact = false)
{
	$ids = elitewave_letterhead_company_ids($conn);
	$gst = htmlspecialchars($ids['gstin'], ENT_QUOTES, 'UTF-8');
	$pan = htmlspecialchars($ids['pan'], ENT_QUOTES, 'UTF-8');
	$title = htmlspecialchars($document_title, ENT_QUOTES, 'UTF-8');
	$sub = htmlspecialchars($document_sub, ENT_QUOTES, 'UTF-8');
	$logo_w = $compact ? 148 : 178;
	$addr_fs = $compact ? '8pt' : '9pt';
	$title_fs = $compact ? '11pt' : '12pt';
	$sub_mb = $compact ? '3px' : '5px';
	$hdr_mb = $compact ? '2px' : '4px';
	$rule_mb = $compact ? '4px' : '8px';

	$html = elitewave_pdf_letterhead_css();
	$html .= '
<div style="padding:0 4px 0 4px;">';
	if ($sub !== '') {
		$html .= '
<p class="ew-doc-subtitle" style="text-align:center;margin:0 0 ' . $sub_mb . ';">' . $sub . '</p>';
	}
	$html .= '
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 ' . $hdr_mb . ';">
<tr>
	<td width="36%" style="vertical-align:top;padding:0 10px 0 0;border:0;">
		<img src="images/elite-nav.png" alt="EliteWave360" style="width:' . $logo_w . 'px;height:auto;display:block;">
	</td>
	<td width="64%" style="vertical-align:middle;text-align:left;border:0;padding:2px 0 0 0;">
		<div style="text-align:left;font-size:' . $addr_fs . ';color:#000;font-weight:bold;line-height:1.25;">
			No.10/35, M.V.Badran Street, Anaikar Complex, 2nd Floor,
			Naval Hospital Road, Periamet, Chennai – 600003<br>
			<span style="color:' . EW_LH_NAVY . ';">+91 9840859711</span> · +91 9952918211 ·
			info@elitewave360.in · www.elitewave360.in
		</div>
	</td>
</tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="margin:2px 0 4px;border-collapse:collapse;">
<tr>
	<td width="33%" style="border:0;padding:0;text-align:left;vertical-align:middle;font-size:8.5pt;color:#000;font-weight:bold;white-space:nowrap;">'
		. ($gst !== '' ? 'GSTIN ' . $gst : '&nbsp;')
		. '</td>
	<td width="34%" style="border:0;padding:0 4px;text-align:center;vertical-align:middle;">
		<span class="ew-doc-title" style="font-size:' . $title_fs . ';line-height:1.1;">' . $title . '</span>
	</td>
	<td width="33%" style="border:0;padding:0;text-align:right;vertical-align:middle;font-size:8.5pt;color:#000;font-weight:bold;white-space:nowrap;">'
		. ($pan !== '' ? 'PAN ' . $pan : '&nbsp;')
		. '</td>
</tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 ' . $rule_mb . ';height:3px;border-collapse:collapse;">
<tr>
	<td style="width:70%;background:' . EW_LH_NAVY . ';padding:0;font-size:1px;line-height:3px;border:0;">&nbsp;</td>
	<td style="width:30%;background:' . EW_LH_ACCENT . ';padding:0;font-size:1px;line-height:3px;border:0;">&nbsp;</td>
</tr>
</table>';

	if ($open_content) {
		$html .= '<div style="margin-top:2px;">';
	}
	return $html;
}

function elitewave_pdf_letterhead_footer_html()
{
	return '
<div style="margin-top:28px;padding-top:12px;border-top:1px solid ' . EW_LH_LINE . ';">
	<table width="100%" cellpadding="0" cellspacing="0">
	<tr>
		<td style="border:0;padding:0;width:65%;vertical-align:bottom;">
			<div style="font-size:9pt;color:#000;font-weight:bold;">EliteWave360 Logistics · Expectations Delivered</div>
		</td>
		<td style="border:0;padding:0;width:35%;text-align:right;vertical-align:bottom;">
			<div style="font-size:9pt;font-weight:bold;color:' . EW_LH_NAVY . ';">For EliteWave360 Logistics</div>
			<div style="font-size:9pt;color:#000;margin-top:2px;font-weight:bold;">Authorised Signatory</div>
		</td>
	</tr>
	</table>
</div>';
}

/** Close main content area only (outer wrapper stays open until full close). */
function elitewave_pdf_letterhead_close_content()
{
	return '</div>';
}

/** End document wrapper with optional signatory footer block. */
function elitewave_pdf_letterhead_close($include_footer = true)
{
	$html = elitewave_pdf_letterhead_close_content();
	if ($include_footer) {
		$html .= elitewave_pdf_letterhead_footer_html();
	}
	$html .= '</div>';
	return $html;
}
