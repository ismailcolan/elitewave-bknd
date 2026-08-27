<?php

require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/gst_invoice_pdf_footer.php';
require_once __DIR__ . '/gst_invoice_pdf_layout.php';

function tax_invoice_fmt_addr($det, $conn)
{
    $lines = array();
    if (!empty($det['address1'])) {
        $lines[] = trim($det['address1']);
    }
    if (!empty($det['address2'])) {
        $lines[] = trim($det['address2']);
    }
    $city = get_city_name($conn, $det['city']);
    $tail = trim($city . (!empty($det['pincode']) ? '-' . $det['pincode'] : ''));
    if ($tail !== '') {
        $lines[] = $tail;
    }
    return implode('<br>', $lines);
}

function tax_invoice_build_pdf_html($conn, $billing_invoice_id)
{
    $data = billing_get_invoice($conn, $billing_invoice_id);
    if (!$data || empty($data['details'])) {
        return '';
    }

    $master = $data['master'];
    $details = $data['details'];

    $company_result = mysqli_query($conn, 'SELECT * FROM company WHERE status=0 LIMIT 1');
    $company_row = mysqli_fetch_assoc($company_result);
    $company_gstin = $company_row['gst_no'] ?? '';
    $company_pan = $company_row['pan_no'] ?? '';

    $bill_to = get_client_info($conn, $master['customer_id']);
    if (!$bill_to) {
        $bill_to = array();
    }

    $bill_to_name = strtoupper(get_client_name($conn, $master['customer_id']));
    $bill_to_addr = tax_invoice_fmt_addr($bill_to, $conn);
    $bill_to_state = get_statename($conn, $bill_to['state'] ?? 0);
    $bill_to_gst = strtoupper($bill_to['gst_no'] ?? '');
    $state_code = substr($bill_to_gst, 0, 2);
    $client_email = $bill_to['email'] ?? '';
    $client_email2 = trim($bill_to['email1'] ?? '');
    $client_contact_person = $bill_to['contact_person'] ?? '';
    $client_contact_no = $bill_to['contact_no'] ?? '';
    $client_contact_no2 = trim($bill_to['contact_no1'] ?? '');
    $mobile_numbers = array_filter(array($client_contact_no, $client_contact_no2));
    $mobile_numbers_text = !empty($mobile_numbers) ? implode(' / ', $mobile_numbers) : 'Not Available';

    $unique_invoice_no = $master['invoice_no'] ?: 'DRAFT';
    $invoice_date = $master['invoice_date'];
    $sac = '996812';
    $sac_text = $sac . ' - Multimodal Transport of Goods';

    $sum_qty = 0;
    $sum_weight = 0;
    $sum_freight = 0;
    $sum_dc = 0;
    $sum_total_line = 0;
    $rows_html = '';
    $sno = 1;
    $gcn_count = 0;
    $transport_types = array(
        'vehicle_type' => '',
        'premium_train_type' => '',
        'premium_airlines_type' => '',
    );

    foreach ($details as $detail) {
        $tbl = preg_replace('/[^a-zA-Z0-9_]/', '', $detail['trans_table']);
        $tid = (int) $detail['transaction_id'];
        $tq = mysqli_query($conn, "SELECT * FROM `$tbl` WHERE transaction_id='$tid' LIMIT 1");
        if (!$tq || !($trow = mysqli_fetch_assoc($tq))) {
            continue;
        }

        $transport_types = gst_invoice_merge_transport_types(
            $transport_types,
            gst_invoice_resolve_transport_types($conn, $trow)
        );

        $inv_tbl = str_replace('transaction_', 'transaction_invoice_', $tbl);
        $item = array('qty' => $detail['packages'], 'charged_weight' => $detail['weight'], 'party_invoice_no' => '', 'frieght_rate' => $trow['frieght_rate']);
        $iq = @mysqli_query($conn, "SELECT * FROM `$inv_tbl` WHERE transaction_id='$tid' AND type_of_pkge!='Select Package Type' LIMIT 1");
        if ($iq && ($ir = mysqli_fetch_assoc($iq))) {
            $item = array_merge($item, $ir);
        }

        $qty = (int) ($item['qty'] ?: $detail['packages']);
        $weight = round((float) ($item['charged_weight'] ?: $detail['weight']), 1);
        $rate = (float) ($item['frieght_rate'] ?: $trow['frieght_rate']);
        $freight = (float) $detail['freight_amount'];
        $dc = (float) $detail['other_charges'];
        $total_line = (float) $detail['total_amount'];
        $mode_name = get_mode($conn, $trow['mode_of_transportation']);
        $consignee_det = get_client_info($conn, $trow['consignee']);
        $consignee_addr_html = tax_invoice_fmt_addr($consignee_det ?: array(), $conn);
        $consignee_gst = $consignee_det['gst_no'] ?? '';

        $sum_qty += $qty;
        $sum_weight += $weight;
        $sum_freight += $freight;
        $sum_dc += $dc;
        $sum_total_line += $total_line;
        $gcn_count++;

        $rows_html .= '
<tr>
    <td style="border:1px solid #000;padding:3px;text-align:center;vertical-align:top;">' . $sno . '</td>
    <td style="border:1px solid #000;padding:3px;text-align:center;vertical-align:top;">' . htmlspecialchars($detail['grn_no']) . '</td>
    <td style="border:1px solid #000;padding:3px;text-align:center;vertical-align:top;">' . htmlspecialchars($detail['grn_date']) . '</td>
    <td style="border:1px solid #000;padding:3px;text-align:center;vertical-align:top;">' . $qty . '</td>
    <td style="border:1px solid #000;padding:3px;text-align:center;vertical-align:top;">' . $weight . '</td>
    <td style="border:1px solid #000;padding:3px;text-align:center;vertical-align:top;">' . $rate . '</td>
    <td style="border:1px solid #000;padding:3px;text-align:center;vertical-align:top;">' . htmlspecialchars(get_client_name($conn, $trow['consigner'])) . '</td>
    <td style="border:1px solid #000;padding:3px;text-align:left;vertical-align:top;font-size:7pt;">' . htmlspecialchars(get_client_name($conn, $trow['consignee'])) . '<br>' . $consignee_addr_html . '<br>GSTIN : ' . htmlspecialchars($consignee_gst) . '</td>
    <td style="border:1px solid #000;padding:3px;text-align:center;vertical-align:top;font-size:7pt;">' . htmlspecialchars($mode_name) . '</td>
    <td style="border:1px solid #000;padding:3px;text-align:center;vertical-align:top;">' . htmlspecialchars($item['party_invoice_no'] ?? '') . '</td>
    <td style="border:1px solid #000;padding:3px;text-align:right;vertical-align:top;">' . number_format($freight, 2) . '</td>
    <td style="border:1px solid #000;padding:3px;text-align:right;vertical-align:top;">' . number_format($dc, 2) . '</td>
    <td style="border:1px solid #000;padding:3px;text-align:right;vertical-align:top;">' . number_format($total_line, 2) . '</td>
</tr>';
        $sno++;
    }

    $cgst_amt = (float) $master['cgst_amount'];
    $sgst_amt = (float) $master['sgst_amount'];
    $igst_amt = (float) $master['igst_amount'];
    $cess_amt = (float) ($master['cess_amount'] ?? 0);
    $taxable = (float) $master['taxable_value'];
    $grand_total = (float) $master['grand_total'];
    $is_same_state = ($cgst_amt > 0 || $sgst_amt > 0);

    $cgst_rate = $taxable > 0 ? round(($cgst_amt / $taxable) * 100, 2) : 0;
    $sgst_rate = $taxable > 0 ? round(($sgst_amt / $taxable) * 100, 2) : 0;
    $igst_rate = $taxable > 0 ? round(($igst_amt / $taxable) * 100, 2) : 0;

    $computed_total = $taxable + $cgst_amt + $sgst_amt + $igst_amt + $cess_amt;
    $round_off = round($grand_total - $computed_total, 2);
    if (abs($round_off) < 0.01) {
        $round_off = 0;
    }
    $total_words = $master['total_words'] ?: gst_tax_report_amount_in_words($grand_total);
    $current_date = date('Y.m.d H:i:s O');

    $html = gst_invoice_pdf_css();

    $html .= '
<table style="width:100%;border-collapse:collapse;border-right:1px solid #000;border-left:1px solid #000;border-top:1px solid #000;">
<tr>
    <td style="width:20%;text-align:center;vertical-align:middle;padding-left:240px;"></td>
    <td style="width:55%;text-align:center;vertical-align:middle;">
        <div style="font-size:14pt;font-weight:bold;line-height:18px;padding-left:700px;">TAX INVOICE</div>
    </td>
    <td style="width:25%;font-weight:bold;text-align:right;vertical-align:top;font-size:9pt;padding-left:5px;">(ORIGINAL COPY)</td>
</tr>
</table>';

    $html .= '
<table style="width:100%;border-collapse:collapse;border-right:1px solid #000;border-left:1px solid #000;">
<tr>
    <td style="width:20%;text-align:center;vertical-align:middle;"><img src="images/elite-nav.png" style="width:180px;"></td>
    <td style="width:65%;text-align:center;vertical-align:middle;">
        <div style="font-size:17pt;font-weight:bold;color:#021659;line-height:18px;">EliteWave360 Logistics</div>
        <div style="font-size:8.8pt;line-height:10px;">No.10/35, M.V.Badran Street, Anaikar Complex, Second Floor, Naval Hospital Road,<br>Periamet, Chennai - 600003 Tamil Nadu, Phone : +91 9840859711 &nbsp;&nbsp; +91 9952918211<br>E-Mail : info@elitewave360.in, athar@elitewave360.in &nbsp;&nbsp; www.elitewave360.in</div>
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

    $html .= '
<table cellpadding="4" cellspacing="0" width="100%" style="border-collapse:collapse;border:1px solid #000;">
<tr>
    <td style="width:40%;font-weight:bold;border:none;font-size:10pt;white-space:nowrap;">Invoice Number&nbsp;&nbsp;: &nbsp;' . htmlspecialchars($unique_invoice_no) . '</td>
    <td style="width:25%;font-weight:bold;border:none;font-size:10pt;text-align:center;white-space:nowrap;">SAC CODE:&nbsp;&nbsp;' . htmlspecialchars($sac) . '</td>
    <td style="width:35%;font-weight:bold;border:none;font-size:10pt;text-align:right;white-space:nowrap;">Invoice Generated Date :&nbsp;' . htmlspecialchars($invoice_date) . '</td>
</tr>
</table>';

    $html .= '
<table width="100%" style="border-collapse:collapse;font-size:8.5pt;">
<tr>
    <td width="50%" style="vertical-align:top;padding:3px 6px;border:1px solid #000;border-right:1px solid #000;line-height:1.15;">
        <table cellpadding="0" cellspacing="0" width="100%" style="border:none;border-collapse:collapse;">
            <tr>
                <td width="70" style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">Bill To</td>
                <td width="10" style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">:</td>
                <td style="border:none;vertical-align:top;font-size:9pt;font-weight:bold;">' . htmlspecialchars($bill_to_name) . '</td>
            </tr>
            <tr><td style="border:none;"></td><td style="border:none;"></td><td style="border:none;font-size:9pt;">' . strtoupper(strip_tags($bill_to_addr)) . '</td></tr>
            <tr><td style="border:none;"></td><td style="border:none;"></td><td style="border:none;font-size:9.5pt;">State : ' . htmlspecialchars($bill_to_state) . ' &nbsp;&nbsp; Code : ' . htmlspecialchars($state_code) . '</td></tr>
            <tr><td style="border:none;"></td><td style="border:none;"></td><td style="border:none;font-weight:bold;font-size:8.5pt;">GSTIN/UIN : ' . htmlspecialchars($bill_to_gst) . '</td></tr>
        </table>
    </td>
    <td width="50%" style="vertical-align:top;padding:3px 6px;border:1px solid #000;border-left:0;line-height:1.15;">
        <table cellpadding="0" cellspacing="0" width="100%" style="border:none;font-size:8.5pt;border-collapse:collapse;">
            <tr><td width="130" style="border:none;vertical-align:top;font-weight:bold;font-size:9.5pt;">Contact Person</td><td width="10" style="border:none;font-weight:bold;font-size:9.5pt;">:</td><td style="border:none;font-size:9.5pt;">' . htmlspecialchars($client_contact_person ?: 'Not Available') . '</td></tr>
            <tr><td style="border:none;font-weight:bold;font-size:9.5pt;">Mobile Numbers</td><td style="border:none;font-weight:bold;font-size:9.5pt;">:</td><td style="border:none;font-size:9.5pt;">' . htmlspecialchars($mobile_numbers_text) . '</td></tr>
            <tr><td style="border:none;font-weight:bold;font-size:9.5pt;">Email 1</td><td style="border:none;font-weight:bold;font-size:9.5pt;">:</td><td style="border:none;font-size:9.5pt;">' . htmlspecialchars($client_email ?: 'Not Available') . '</td></tr>
            <tr><td style="border:none;font-weight:bold;font-size:9.5pt;">Email 2</td><td style="border:none;font-weight:bold;font-size:9.5pt;">:</td><td style="border:none;font-size:9.5pt;">' . htmlspecialchars($client_email2 ?: 'Not Available') . '</td></tr>
        </table>
    </td>
</tr>
</table>';

    $html .= '
<table border="1" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;table-layout:fixed;font-family:Arial, Helvetica, sans-serif;font-size:7.5pt;">
<tr style="font-weight:bold;text-align:center;vertical-align:middle;height:22px;">
    <td style="border:1px solid #000;width:5%;padding:2px;">S/No</td>
    <td style="border:1px solid #000;width:8%;padding:2px;">GCN No</td>
    <td style="border:1px solid #000;width:8%;padding:2px;">Date</td>
    <td style="border:1px solid #000;width:4%;padding:2px;">Qty</td>
    <td style="border:1px solid #000;width:6%;padding:2px;">Weight</td>
    <td style="border:1px solid #000;width:5%;padding:2px;">Rate</td>
    <td style="border:1px solid #000;width:15%;padding:2px;">Consignor / Consignee</td>
    <td style="border:1px solid #000;width:20%;padding:2px;">Ship To</td>
    <td style="border:1px solid #000;width:7%;padding:2px;">Mode</td>
    <td style="border:1px solid #000;width:8%;padding:2px;">Supp.Inv.No.</td>
    <td style="border:1px solid #000;width:6%;padding:2px;">Freight</td>
    <td style="border:1px solid #000;width:4%;padding:2px;">DC</td>
    <td style="border:1px solid #000;width:6%;padding:2px;">Total</td>
</tr>' . $rows_html . '
<tr style="font-weight:bold;height:20px;page-break-inside:avoid;">
    <td style="border:1px solid #000;text-align:left;padding:2px 5px;font-weight:bold;vertical-align:middle;white-space:nowrap;">Total</td>
    <td style="border:1px solid #000;text-align:center;padding:2px;vertical-align:middle;font-weight:bold;white-space:nowrap;">' . $gcn_count . '</td>
    <td style="border:1px solid #000;padding:2px;"></td>
    <td style="border:1px solid #000;text-align:center;padding:2px;font-weight:bold;vertical-align:middle;">' . $sum_qty . '</td>
    <td style="border:1px solid #000;text-align:center;font-weight:bold;padding:2px;vertical-align:middle;">' . $sum_weight . '</td>
    <td style="border:1px solid #000;padding:2px;"></td>
    <td style="border:1px solid #000;padding:2px;"></td>
    <td style="border:1px solid #000;padding:2px;"></td>
    <td style="border:1px solid #000;padding:2px;"></td>
    <td style="border:1px solid #000;padding:2px;"></td>
    <td style="border:1px solid #000;text-align:right;padding:2px;font-weight:bold;vertical-align:middle;">' . number_format($sum_freight, 2) . '</td>
    <td style="border:1px solid #000;text-align:right;padding:2px;vertical-align:middle;font-weight:bold;">' . number_format($sum_dc, 2) . '</td>
    <td style="border:1px solid #000;text-align:right;padding:2px;font-weight:bold;vertical-align:middle;">' . number_format($sum_total_line, 2) . '</td>
</tr>
</table>';

    $html .= gst_invoice_pdf_summary_section_html(
        $unique_invoice_no,
        $transport_types,
        $is_same_state,
        $cgst_rate,
        $sgst_rate,
        $igst_rate,
        $cgst_amt,
        $sgst_amt,
        $igst_amt,
        $round_off,
        $grand_total
    );

    $html .= '
<table border="1" cellpadding="4" cellspacing="0" width="100%" style="border-collapse:collapse;margin-top:-1px;">
<tr><td style="border:1px solid #000;font-weight:bold;font-size:8.5pt;">Amount (In words) : ' . htmlspecialchars($total_words) . '</td></tr>
</table>';

    $html .= gst_invoice_pdf_footer_html($sac_text, $current_date, dirname(__DIR__));

    return $html;
}

function tax_invoice_save_pdf($conn, $billing_invoice_id)
{
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    $html = tax_invoice_build_pdf_html($conn, $billing_invoice_id);
    if ($html === '') {
        return '';
    }

    $data = billing_get_invoice($conn, $billing_invoice_id);
    $invoice_no = $data['master']['invoice_no'] ?: ('DRAFT-' . $billing_invoice_id);

    $mpdf = new \Mpdf\Mpdf(array(
        'mode' => 'utf-8',
        'format' => 'A4',
        'default_font' => 'freesans',
        'margin_left' => 5,
        'margin_right' => 5,
        'margin_top' => 5,
        'margin_bottom' => 5,
    ));
    $mpdf->SetTitle('Tax Invoice - ' . $invoice_no);
    $mpdf->SetAuthor('EliteWave360 Logistics');
    $mpdf->WriteHTML($html);

    $dir = dirname(__DIR__) . '/digital_invoice';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $safe_no = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $invoice_no);
    $path = $dir . '/billing_' . (int) $billing_invoice_id . '_' . $safe_no . '.pdf';
    $mpdf->Output($path, \Mpdf\Output\Destination::FILE);

    $rel = 'digital_invoice/' . basename($path);
    $esc = mysqli_real_escape_string($conn, $rel);
    mysqli_query($conn, "UPDATE billing_invoice_master SET pdf_path='$esc' WHERE billing_invoice_id='" . (int) $billing_invoice_id . "'");

    return $rel;
}
