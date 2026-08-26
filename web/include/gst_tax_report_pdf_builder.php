<?php

require_once __DIR__ . '/gst_invoice_pdf_footer.php';

function gst_tax_report_build_pdf_html($conn, $filters, $rows, $summary)
{
    $company_result = mysqli_query($conn, 'SELECT * FROM company WHERE status=0 LIMIT 1');
    $company_row = mysqli_fetch_array($company_result);
    $company_gstin = $company_row['gst_no'] ?? '';
    $company_pan = $company_row['pan_no'] ?? '';

    $filter_summary = gst_tax_report_filter_summary($conn, $filters);
    $report_no = 'GST-RPT/' . str_replace('-', '', $filters['from_date']) . '-' . str_replace('-', '', $filters['to_date']);
    $generated_date = date('d-m-Y');
    $current_date = date('Y.m.d H:i:s O');
    $sac = '996541';
    $sac_text = '996541 - Multimodal Transport of Goods';
    $amount_words = gst_tax_report_amount_in_words($summary['grand_total']);

    $td = 'border:1px solid #000;padding:3px;font-size:7pt;vertical-align:top;';
    $th = 'border:1px solid #000;padding:2px;font-size:7pt;font-weight:bold;text-align:center;vertical-align:middle;';

    $css = '
<style>
body{ font-family: freesans; font-size:7.5pt; }
table{ border-collapse:collapse; }
td{ font-family:freesans; font-size:7.5pt; vertical-align:middle; }
b,strong{ font-weight:bold; }
</style>';

    $html = $css;

    // SECTION 1: HEADER (same as tax invoice)
    $html .= '
<table style="width:100%;border-collapse:collapse;border-right:1px solid #000;border-left:1px solid #000;border-top:1px solid #000;">
<tr>
<td style="width:20%;text-align:center;vertical-align:middle;padding-left:240px;"></td>
<td style="width:55%;text-align:center;vertical-align:middle;">
<div style="font-size:14pt;font-weight:bold;line-height:18px;padding-left:700px;">GST TAX REPORT</div>
</td>
<td style="width:25%;font-weight:bold;text-align:right;vertical-align:top;font-size:9pt;padding-left:5px;">(REPORT COPY)</td>
</tr>
</table>';

    $html .= '
<table style="width:100%;border-collapse:collapse;border-right:1px solid #000;border-left:1px solid #000;">
<tr>
<td style="width:20%;text-align:center;vertical-align:middle;">
<img src="images/elite-nav.png" style="width:180px;">
</td>
<td style="width:65%;text-align:center;vertical-align:middle;">
<div style="font-size:17pt;font-weight:bold;color:#021659;line-height:18px;">EliteWave360 Logistics</div>
<div style="font-size:8.8pt;line-height:10px;">
No.10/35, M.V.Badran Street, Anaikar Complex, Second Floor,
Naval Hospital Road,<br>
Periamet, Chennai - 600003 Tamil Nadu,
Phone : +91 9840859711 &nbsp;&nbsp; +91 9952918211<br>
E-Mail : info@elitewave360.in, athar@elitewave360.in &nbsp;&nbsp;
www.elitewave360.in
</div>
</td>
<td style="width:15%;font-weight:bold;text-align:right;vertical-align:top;font-size:8pt;"></td>
</tr>
</table>';

    $html .= '
<table style="width:100%;border-collapse:collapse;border-right:1px solid #000;border-left:1px solid #000;">
<tr>
<td style="width:50%;font-size:9.2pt;font-weight:bold;text-align:left;"> GSTIN/UIN : ' . htmlspecialchars($company_gstin) . '</td>
<td></td>
<td style="width:50%;font-size:9.2pt;font-weight:bold;text-align:right;">PAN : ' . htmlspecialchars($company_pan) . '</td>
</tr>
</table>';

    // SECTION 2: REPORT META (invoice-style row)
    $html .= '
<table cellpadding="4" cellspacing="0" width="100%" style="border-collapse:collapse; border:1px solid #000;">
<tr>
<td style="width:40%;font-weight:bold;border:none;font-size:10pt;white-space:nowrap;">
Report Number&nbsp;&nbsp;: &nbsp;' . htmlspecialchars($report_no) . '
</td>
<td style="width:25%;font-weight:bold;border:none;font-size:10pt;text-align:center;white-space:nowrap;">
SAC CODE:&nbsp;&nbsp;' . htmlspecialchars($sac) . '
</td>
<td style="width:35%;font-weight:bold;border:none;text-align:right;font-size:10pt;white-space:nowrap;">
Report Generated Date :&nbsp;' . htmlspecialchars($generated_date) . '
</td>
</tr>
</table>';

    // SECTION 3: REPORT FILTERS + COMPANY CONTACT (invoice bill-to layout)
    $html .= '
<table cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #000; font-size: 8.5pt; font-family: Arial, Helvetica, sans-serif;">
<tr>
<td width="50%" style="width:50%;vertical-align:top;padding:3px 6px;border-top:0;border-bottom:0;border-left:1px solid #000;border-right:0;line-height:1.15;">
<table cellpadding="0" cellspacing="0" width="100%" style="border:none;border-collapse:collapse;">
<tr>
<td width="110" style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">Report Period</td>
<td width="10" style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">:</td>
<td style="border:none;vertical-align:top;font-size:9pt;font-weight:bold;">' . htmlspecialchars(strtoupper($filter_summary['period'])) . '</td>
</tr>
<tr>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">Customer</td>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">:</td>
<td style="border:none;vertical-align:top;font-size:9pt;">' . htmlspecialchars(strtoupper($filter_summary['customer'])) . '</td>
</tr>
<tr>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">GST Type</td>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">:</td>
<td style="border:none;vertical-align:top;font-size:9pt;">' . htmlspecialchars($filter_summary['gst_type']) . '</td>
</tr>
<tr>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">Tax Code</td>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">:</td>
<td style="border:none;vertical-align:top;font-size:9pt;">' . htmlspecialchars($filter_summary['tax_code']) . '</td>
</tr>
<tr>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">Total Bookings</td>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">:</td>
<td style="border:none;vertical-align:top;font-size:9pt;font-weight:bold;">' . (int) $summary['booking_count'] . '</td>
</tr>
</table>
</td>
<td width="50%" style="width:50%;vertical-align:top;padding:3px 6px;border-top:0;border-bottom:0;border-left:0;border-right:1px solid #000;line-height:1.15;">
<table cellpadding="0" cellspacing="0" width="100%" style="border:none;font-size:8.5pt;border-collapse:collapse;">
<tr>
<td width="130" style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">Contact Person</td>
<td width="10" style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">:</td>
<td style="border:none;vertical-align:top;font-size:9.5pt;">AADIL AHMED</td>
</tr>
<tr>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">Mobile Numbers</td>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">:</td>
<td style="border:none;vertical-align:top;font-size:9.5pt;">+91 9840859711 / +91 9952918211</td>
</tr>
<tr>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">Email 1</td>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">:</td>
<td style="border:none;vertical-align:top;font-size:9.5pt;">info@elitewave360.in</td>
</tr>
<tr>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">Email 2</td>
<td style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">:</td>
<td style="border:none;vertical-align:top;font-size:9.5pt;">athar@elitewave360.in</td>
</tr>
</table>
</td>
</tr>
</table>';

    // SECTION 4: GST REPORT TABLE (invoice-style bordered table)
    $html .= '
<table cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;border:1px solid #000;table-layout:fixed;font-family:Arial, Helvetica, sans-serif;font-size:7.5pt;">
<tr style="font-weight:bold;text-align:center;vertical-align:middle;height:22px;">
<td style="' . $th . 'width:4%;">S/No</td>
<td style="' . $th . 'width:8%;">GCN No</td>
<td style="' . $th . 'width:7%;">Date</td>
<td style="' . $th . 'width:14%;">Customer</td>
<td style="' . $th . 'width:6%;">GST Type</td>
<td style="' . $th . 'width:6%;">Tax Code</td>
<td style="' . $th . 'width:8%;">Taxable Value</td>
<td style="' . $th . 'width:7%;">CGST</td>
<td style="' . $th . 'width:7%;">SGST</td>
<td style="' . $th . 'width:7%;">IGST</td>
<td style="' . $th . 'width:6%;">Cess</td>
<td style="' . $th . 'width:8%;">Total GST</td>
<td style="' . $th . 'width:8%;">Grand Total</td>
</tr>';

    if (empty($rows)) {
        $html .= '<tr><td colspan="13" style="' . $td . 'text-align:center;">No records found for the selected filters.</td></tr>';
    } else {
        foreach ($rows as $row) {
            $html .= '<tr>';
            $html .= '<td style="' . $td . 'text-align:center;">' . (int) $row['s_no'] . '</td>';
            $html .= '<td style="' . $td . 'text-align:center;">' . htmlspecialchars($row['grn_no']) . '</td>';
            $html .= '<td style="' . $td . 'text-align:center;">' . htmlspecialchars($row['grn_date']) . '</td>';
            $html .= '<td style="' . $td . 'text-align:left;font-size:7pt;">' . htmlspecialchars($row['customer']) . '</td>';
            $html .= '<td style="' . $td . 'text-align:center;">' . htmlspecialchars($row['gst_type']) . '</td>';
            $html .= '<td style="' . $td . 'text-align:center;">' . htmlspecialchars($row['tax_code']) . '</td>';
            $html .= '<td style="' . $td . 'text-align:right;">' . htmlspecialchars($row['taxable_value']) . '</td>';
            $html .= '<td style="' . $td . 'text-align:right;">' . htmlspecialchars($row['cgst_amount']) . '</td>';
            $html .= '<td style="' . $td . 'text-align:right;">' . htmlspecialchars($row['sgst_amount']) . '</td>';
            $html .= '<td style="' . $td . 'text-align:right;">' . htmlspecialchars($row['igst_amount']) . '</td>';
            $html .= '<td style="' . $td . 'text-align:right;">' . htmlspecialchars($row['cess_amount']) . '</td>';
            $html .= '<td style="' . $td . 'text-align:right;">' . htmlspecialchars($row['gst_amount']) . '</td>';
            $html .= '<td style="' . $td . 'text-align:right;font-weight:bold;">' . htmlspecialchars($row['grand_total']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '<tr style="font-weight:bold;height:20px;page-break-inside:avoid;">';
        $html .= '<td style="' . $td . 'text-align:left;font-weight:bold;" colspan="6">Total (' . (int) $summary['booking_count'] . ' bookings)</td>';
        $html .= '<td style="' . $td . 'text-align:right;font-weight:bold;">' . htmlspecialchars($summary['taxable_value']) . '</td>';
        $html .= '<td style="' . $td . 'text-align:right;font-weight:bold;">' . htmlspecialchars($summary['cgst_amount']) . '</td>';
        $html .= '<td style="' . $td . 'text-align:right;font-weight:bold;">' . htmlspecialchars($summary['sgst_amount']) . '</td>';
        $html .= '<td style="' . $td . 'text-align:right;font-weight:bold;">' . htmlspecialchars($summary['igst_amount']) . '</td>';
        $html .= '<td style="' . $td . 'text-align:right;font-weight:bold;">' . htmlspecialchars($summary['cess_amount']) . '</td>';
        $html .= '<td style="' . $td . 'text-align:right;font-weight:bold;">' . htmlspecialchars($summary['gst_amount']) . '</td>';
        $html .= '<td style="' . $td . 'text-align:right;font-weight:bold;">' . htmlspecialchars($summary['grand_total']) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</table>';

    // SECTION 5: META + GST TOTALS (invoice-style)
    $html .= '
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;font-family:freesans;font-size:9pt;border-left:1px solid #000;border-right:1px solid #000;border-bottom:1px solid #000;">
<tr>
<td width="70%" style="width:70%;padding:5px 9px;vertical-align:top;border-left:1px solid #000;border-right:1px solid #000;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;font-family:freesans;font-size:9pt;">
<tr>
<td width="170" style="border:0;padding:0;font-weight:bold;line-height:18px;font-size:9pt;white-space:nowrap;">Report Number</td>
<td width="15" style="border:0;padding:0;font-weight:bold;line-height:18px;font-size:9pt;">:</td>
<td style="border:0;padding:0;line-height:18px;font-size:9pt;">' . htmlspecialchars($report_no) . '</td>
</tr>
<tr>
<td style="border:0;padding:0;font-weight:bold;line-height:18px;font-size:9pt;white-space:nowrap;">Report Period</td>
<td style="border:0;padding:0;font-weight:bold;font-size:9pt;">:</td>
<td style="border:0;padding:0;line-height:18px;font-size:9pt;">' . htmlspecialchars($filter_summary['period']) . '</td>
</tr>
<tr>
<td style="border:0;padding:0;font-weight:bold;line-height:18px;font-size:9pt;white-space:nowrap;">Total Bookings</td>
<td style="border:0;padding:0;font-weight:bold;font-size:9pt;">:</td>
<td style="border:0;padding:0;line-height:18px;font-size:9pt;">' . (int) $summary['booking_count'] . '</td>
</tr>
</table>
</td>
<td width="30%" style="width:30%;padding:0;vertical-align:top;border-right:1px solid #000;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;font-family:freesans;font-size:9pt;">';

    $html .= '
<tr>
<td width="70%" style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">OUTPUT- CGST</td>
<td width="30%" style="width:30%;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . number_format((float) $summary['cgst_amount'], 2) . '</td>
</tr>
<tr>
<td style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">OUTPUT- SGST</td>
<td style="width:30%;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . number_format((float) $summary['sgst_amount'], 2) . '</td>
</tr>
<tr>
<td style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">OUTPUT- IGST</td>
<td style="width:30%;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . number_format((float) $summary['igst_amount'], 2) . '</td>
</tr>
<tr>
<td style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">CESS</td>
<td style="width:30%;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . number_format((float) $summary['cess_amount'], 2) . '</td>
</tr>
<tr>
<td style="width:70%;border-right:1px solid #000;border-bottom:1px solid #000;padding:1px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">TOTAL GST</td>
<td style="width:30%;border-bottom:1px solid #000;padding:1px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . number_format((float) $summary['gst_amount'], 2) . '</td>
</tr>
<tr>
<td style="width:70%;border-right:1px solid #000;padding:2px 4px;font-weight:bold;line-height:18px;white-space:nowrap;">GRAND TOTAL</td>
<td style="width:30%;padding:2px 4px;text-align:right;font-weight:bold;line-height:18px;white-space:nowrap;">' . number_format((float) $summary['grand_total'], 2) . '</td>
</tr>
</table>
</td>
</tr>
</table>';

    // SECTION 6: AMOUNT IN WORDS
    $html .= '
<table border="1" cellpadding="4" cellspacing="0" width="100%" style="border-collapse:collapse;">
<tr><td style="border:1px solid #000;font-weight:bold;font-size:8.5pt;">Amount (In words) : ' . htmlspecialchars($amount_words) . '</td></tr>
</table>';

    // SECTIONS 7-12: Same footer blocks as tax invoice
    $html .= gst_invoice_pdf_footer_html($sac_text, $current_date, dirname(__DIR__));

    return $html;
}
