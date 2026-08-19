<?php
require_once("include/connect.php");
require_once("include/function.php");
require_once("appMail.php");

$transaction_id = $_POST['transaction_id'];
$month = $_POST['month'];
$year = $_POST['year'];

$dir = 'digital_invoice/';

$file_name = $month . "_" . $year . "_" . $transaction_id . "invoice.pdf";

$pdf_file = $dir . $file_name;

$table_name = "transaction_" . $month . "_" . $year;
$query = "select * from  $table_name where transaction_id='" . $transaction_id . "'";
$result = mysqli_query($conn, $query);

$get_result_query = mysqli_fetch_assoc($result);
$get_status = $get_result_query['status'];
$get_transaction_id = $get_result_query['transaction_id'];
$consignor_name = $get_result_query['consigner'];
$consignee_name = $get_result_query['consignee'];
$origin_name = $get_result_query['origin'];
$destination_name = $get_result_query['destination'];
$grnn_no = $get_result_query['grn_no'];
$grnn_date = $get_result_query['grn_date'];
$mode_of_consignment = $get_result_query['mode_of_consignment'];
$balance = $get_result_query['balance'];
$unique_invoice_no = $get_result_query['invoice_no'];

// Payment link code start

$get_client_details =  get_client($conn, $consignee_name);
$company_name = $get_client_details['client_company_name'];
$email = $get_client_details['email'];
$phone = $get_client_details['contact_no'];
$amount_array = array($balance);
$get_transaction_id_arr = array($get_transaction_id);
$grnn_date_array = array($grnn_date);
$grnn_no_array = array($grnn_no);
$unique_invoice_no_array = array($unique_invoice_no);

if ($mode_of_consignment == 1) {
    $data = array(
        'transaction_id' => $get_transaction_id_arr,
        'company_name' => $company_name,
        'grn_date' => $grnn_date_array,
        'email' => $email,
        'phone' => $phone,
        'amount' => $amount_array,
        'grn_no' => $grnn_no_array,
        'invoice_no' => $unique_invoice_no_array,
        'client_id' => $consignee_name
    );
} else if ($mode_of_consignment == 2) {
    $data = array(
        'transaction_id' => $get_transaction_id_arr,
        'company_name' => $company_name,
        'grn_date' => $grnn_date_array,
        'email' => $email,
        'phone' => $phone,
        'amount' => $amount_array,
        'grn_no' => $grnn_no_array,
        'invoice_no' => $unique_invoice_no_array,
        'client_id' => $consignor_name
    );
}

$data_serialize = serialize($data);
$link_wit_data = http_build_query(array('aParam' => $data_serialize));
$dir = 'digital_invoice/';
$pdf_file_name = $dir . $table_name . "_" . $get_transaction_id . "invoice.pdf";

if ($mode_of_consignment != '4') { // payment link should not send for COD
    $pay_link = '<br><br>
    Payment Link : <a href = "http://localhost/graciousexpress/verify_paylink.php?data=' . urlencode($link_wit_data) . '" >Payment Link</a>';
    $mail_subject = 'Consignment Invoice Notification With Payment Link';
} else {
    $pay_link = '';
    $mail_subject = 'Consignment Invoice Notification';
}

// Payment link code end 

//* Check Client Restricted For Send Invoice

$client_chk = mysqli_query($conn, "select * from client where client_id ='$consignor_name' ");
$fetch_chck = mysqli_fetch_assoc($client_chk);
$status_chk = $fetch_chck['invoice_status']; //1
if ($status_chk != '2') {

    //Send Mail
    $msg = '<p style="line-height: 24px; margin-bottom:15px;">
Thank You for Your Order On <a href = "http://localhost/graciousexpress" >Elite Wave 360</a> on ' . $grnn_date . '! <br>
Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
<tr>
<td >GRN No	</td><td >' . $grnn_no . '</td>
</tr><tr>	
<td >GRN Date:	</td><td >	' . $grnn_date . '	</td>	
</tr>
<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . '</td>	</tr>	
<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . '</td>	</tr>	
<tr>		
<td >Status	</td><td >Consignment Delivered Successfully</td>		
</td></tr>
</table>	
<br>
<br>' . $pay_link;
    $to_name = array();
    $to_email = array();

    if ($mode_of_consignment == '1' || $mode_of_consignment == '2' || $mode_of_consignment == '4') { // TOPAY, TBB and COD
        if ($mode_of_consignment == '1' || $mode_of_consignment == '4') {
            // Mail should send only for consignee if mode of consignment is in TOPAY and COD
            if (!empty(get_client_email($conn, $consignee_name))) {
                array_push($to_email, get_client_email($conn, $consignee_name));
                array_push($to_name, get_client_name($conn, $consignee_name));
                // $mail = sendAttachments($to_name,$to_email, $mail_subject,$pdf_file,$image ,$msg,$name); 
                echo "1";
            } else {
                echo "0";
            }
        } else {
            // Mail should send only for consignee if mode of consignment is in TBB
            if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                array_push($to_email, get_client_email($conn, $consignor_name));
                array_push($to_name, get_client_name($conn, $consignor_name));
                // $mail = sendAttachments($to_name,$to_email, $mail_subject,$pdf_file,$image ,$msg,$name); 
                echo "1";
            } else {
                echo "0";
            }
        }
    }
} else {
    echo "2";
}

//End
