<?php
require_once("/home/staging/public_html/web/include/connect.php");
include("/home/staging/public_html/web/include/function.php");
require_once ('/home/staging/public_html/Twillio/vendor/autoload.php');
require_once('/home/staging/public_html/Twillio/constant.php');
include ('/home/staging/public_html/web/automation/appMail.php');

// require_once("../include/connect.php");
// include("../include/function.php");
// require_once("../appMail.php");
// require_once '../../Twillio/vendor/autoload.php';
// require_once('../../Twillio/constant.php');
//date_default_timezone_set('Asia/Kolkata');
$current_date = date('Y-m-d');
//echo "hi";
//echo $current_date;
// exit();
//$current_date = '2022-08-21';
$table_date = date('d-m-Y');
$explode_date = explode("-", $current_date);

//print_r($explode_date);
$table = get_trans_table_name_only($conn, $table_date);
$year = $explode_date[0];
$month = $explode_date[1];

// $first = '2022-09-01';
// $last = '2022-09-07';

use Twilio\Rest\Client;

//Get Frequency 

$get_frq_type = isset($_GET['frq_type']) ? $_GET['frq_type'] : ""; //  [Default, 1, 7, 15, 30, 45, 60]

$get_first_two_letter_of_frq = substr($get_frq_type,0,2);
//echo $get_first_two_letter_of_frq;
// echo "Frequncy: ".$get_frq_type;


switch ($get_frq_type) {
    case '1D':

        echo $query =  "SELECT * FROM `automation_mail_report` where frequency_type = '$get_frq_type' and job_status = 'Queued' and client_type = '2' ";
        break;

    case '2D':

        echo $query =  "SELECT * FROM `automation_mail_report` where frequency_type = '$get_frq_type' and job_status = 'Queued' and client_type = '2' ";
        break;

    case '7D':

        echo $query =  "SELECT * FROM `automation_mail_report` where frequency_type = '$get_frq_type' and job_status = 'Queued' and client_type = '2' ";

        break;
    case '15D':
        $query =  "SELECT * FROM `automation_mail_report` where frequency_type = '$get_frq_type' and job_status = 'Queued' and client_type = '2' ";

        break;
    case '30D':
        echo "<br>";

        echo $query =  "SELECT * FROM `automation_mail_report` where frequency_type = '$get_frq_type' and job_status = 'Queued' and client_type = '2' ";
        echo "<br>";

        break;
    case '45D':
        echo "<br>";

        echo $query =  "SELECT * FROM `automation_mail_report` where frequency_type = '$get_frq_type' and job_status = 'Queued' and client_type = '2' ";
        echo "<br>";

        break;
    case '60D':
        echo "<br>";

        echo $query =  "SELECT * FROM `automation_mail_report` where frequency_type = '$get_frq_type'and job_status = 'Queued' and client_type = '2' ";
        echo "<br>";

        break;
    default:
        echo "Default Frequency";
}

$check_pending_jobs = mysqli_query($conn,$query);

//ceck_pending_jobs = mysqli_query($conn, "SELECT * FROM `automation_mail_report` where frequency_type = '7D' and job_status = 'Queued' ");

if (mysqli_num_rows($check_pending_jobs) > 0) {
    while ($row = mysqli_fetch_assoc($check_pending_jobs)) { //While Select First Row
        $id = $row['id'];
        $consignee = $row['consignee']; //First 
        $grn_no = explode(',', $row['grn_no']);
        $grn_date = explode(',', $row['grn_date']);
        $consigner = explode(',', $row['consigner']);
        $origin = explode(',', $row['origin']);
        $destination = explode(',', $row['destination']);
        $invoice_files = explode(',', $row['invoice_files']);
        $get_transaction_id = explode(',', $row['transaction_id']);
        $unique_invoice_no = explode(',', $row['invoice_no']);
        $balance = explode(',', $row['invoice_amount']);
        $get_client_details =  get_client($conn, $consignee);
        $company_name = $get_client_details['client_company_name'];
        $email = $get_client_details['email'];
        $phone = $get_client_details['contact_no'];

        $data = array(
            'transaction_id' => $get_transaction_id,
            'company_name' => $company_name,
            'grn_date' => $grn_date,
            'email' => $email,
            'phone' => $phone,
            'amount' => $balance,
            'grn_no' => $grn_no,
            'invoice_no' => $unique_invoice_no,
            'client_id' => $consignee
        );

        // echo "<pre>";
        // print_r($data);
        // echo "</pre>";
    	// echo "<pre>";
    	// print_r($invoice_files);
    	// echo "</pre>";
    //exit();
    	
    
        $data_serialize = serialize($data);
        $link_wit_data = http_build_query(array('aParam' => $data_serialize));

        $count_files = count($invoice_files);
    	// echo "<pre>";
    	// print_r($invoice_files);
    	// echo "</pre>";
    	//$invoice = array("../digital_invoice/3_2022_2invoice.pdf","../digital_invoice/3_2022_1invoice.pdf");
        $invoce_file = [];
        foreach($invoice_files as $inv){
		$invoice_es = explode("/",$inv);
		$directory= $invoice_es[1];
		$filename= $invoice_es[2];

		$file =$directory."/".$filename;

		$path = '/home/staging/public_html/web/';

		//echo $invoice_es;
		$invoce_file[] = $path.$file;

		//echo $file;


		}
		//print_r($invoce_file);
    	//exit();
   
        if ($count_files > 0) {
            $out_put = '<p style="line-height: 24px; margin-bottom:15px;">		
            Your ' . $get_first_two_letter_of_frq . '-Days Order Summary From <a href = "http://localhost/graciousexpress" >Elite Wave 360</a> on ' . $row['grn_date'] . '! <br>
            Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
            <table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
            <thead>
            <tr>
            <th>S.No </th>
            <th>Consigner </th>
            <th>Grn No </th>
            <th>Grn Date </th>
            <th>Origin </th>
            <th>Destination</th>
            </tr>
            </thead>
            <tbody>
            ';
            $sno = 1;
            for ($i = 0; $i < sizeof($grn_no); $i++) { //First Client Loop 

                $out_put .= '<tr>
                <td>' . $sno . ' </td>
                <td>' . get_client_name($conn,$consigner[$i]) . '</td>
                <td>' . $grn_no[$i] . '</td>
                <td>' . $grn_date[$i] . '</td>
                <td>' . get_city_name($conn,$origin[$i]) . '</td>
                <td>' . get_city_name($conn,$destination[$i]) . '</td>
                </tr>
               ';

                $sno++;
            }

            $out_put .= '</tbody>
            </table>
            <br>
            <br>
            Payment Link : <a href = "http://localhost/graciousexpress/verify_paylink.php?data=' . urlencode($link_wit_data) . '" >Payment Link</a>';
            $msg = $out_put;
            $to_name = array();
            $to_email = array();

            
            if (!empty(get_client_email($conn, $consignee))) {
                //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
                array_push($to_email, get_client_email($conn, $consignee));
                array_push($to_name, get_client_name($conn, $consignee));

                // print_r($to_email);
                // print_r($to_name);
                // exit();
//                 $mail = sendAttachments($to_name, $to_email, $get_first_two_letter_of_frq . '-Days Consignment Report Mail With Payment Link', $invoce_file, $image, $msg, $name);

//                 if ($mail) {
                    echo "Sent";
                    //exit();
                    echo $query_update = "UPDATE `automation_mail_report` SET job_status = 'SUCCESS', updated_at = '$current_date' where id = '$id'";
                    $update_res = mysqli_query($conn, $query_update);
                    if ($update_res) {
                        if ($get_frq_type == '30D' || $get_frq_type == '45D' || $get_frq_type == '60D') {
                            $count_grn_dates = count($grn_date);
                            if ($count_grn_dates > 0) {
                                for ($h = 0; $h < $count_grn_dates; $h++) {

                                    $transaction_id = $get_transaction_id[$h];

                                    $grn_dated = $grn_date[$h];

                                    $dt = explode("-", $grn_dated);
                                    //print_r($dt);

                                    $y = $dt[2];
                                    $m = $dt[1];
                                    $dd = $dt[0];
                                    if ($m <= 3) {
                                        $m1 = 1;
                                    } else if (($m >= 4) && ($m <= 6)) {
                                        $m1 = 2;
                                    } else if (($m >= 7) && ($m <= 9)) {
                                        $m1 = 3;
                                    } else {
                                        $m1 = 4;
                                    }
                                    $transaction = 'transaction_';
                                    // echo "<br>";
                                    // echo "<br>";
                                    echo $update_trans_table = "UPDATE " . $transaction . $m1 . "_" . $y . " SET `frq_sent_status`='SENT' WHERE consignee = '$consignee' and `transaction_id` = '$transaction_id'";
                                    $res_upd_trans_tbl = mysqli_query($conn, $update_trans_table);
                                    // exit();
                                    echo "<br>";

                                    // $done_job = "DELETE FROM `automation_mail_report` where job_status = 'SUCCESS' and id = '$id'";
                                    // $delete_records = mysqli_query($conn, $done_job);

                                }
                            }
                        } else {
                           echo  $update_trans_table = "UPDATE $table[0] SET `frq_sent_status`='SENT' WHERE consignee = '$consignee' and `transaction_id` IN(" . $row['transaction_id'] . ")";
                           //exit(); 
                           $res_upd_trans_tbl = mysqli_query($conn, $update_trans_table);
                            // $done_job = "DELETE FROM `automation_mail_report` where job_status = 'SUCCESS' and id = '$id'";
                            // $delete_records = mysqli_query($conn, $done_job);
                        }

                        echo "Success";
                    }
                // }
            }
            //End

        } else {
            echo "Files not Found";
        }
    }
    //echo "Job Completed and Queue Removed";
} else {


    echo "No Records Found";
}