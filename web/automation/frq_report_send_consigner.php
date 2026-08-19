<?php
require_once("/home/staging/public_html/web/include/connect.php");
//require_once("save_admin.php");
include("/home/staging/public_html/web/include/function.php");
require_once("/home/staging/public_html/web/appMail.php");
// require_once ('/home/staging/public_html/Twillio/vendor/autoload.php');
// require_once('/home/staging/public_html/Twillio/constant.php');

date_default_timezone_set('Asia/Kolkata');
$current_date = date('d-m-Y');
// $current_date = "13-10-2023";
$table_date = date('d-m-Y');
$explode_date = explode("-", $current_date);
$created_date = date('Y-m-d');

//print_r($explode_date);
$table = get_trans_table_name_only($conn, $table_date);

// $first = '2022-09-01';
// $last = '2022-09-07';

if(!empty($_GET['frq_day'])) {
    $frq_type = $_GET['frq_day'];
    $inv_frq_arr = explode('D',$frq_type);
    $inv_frq = $inv_frq_arr[0];

    $table_dt_quarter = date('d-m-Y', strtotime("-$inv_frq days",strtotime("$current_date")));
	$table_prev_quarter = get_trans_table_name_only($conn, $table_dt_quarter);
	$m_year_quarter = substr($table_prev_quarter[0], 12, 17);
}

// use Twilio\Rest\Client;
$qr =  "SELECT * FROM `automation_mail_report` where frequency_type = '$frq_type' and job_status = 'Queued' AND client_type = '1' AND `frq_sent_date` = '$current_date'";
$check_pending_jobs = mysqli_query($conn,$qr);

if (mysqli_num_rows($check_pending_jobs) > 0) {
    while ($row = mysqli_fetch_assoc($check_pending_jobs)) { //While Select First Row
        $id = $row['id'];
        $consigner =  $row['consigner'];
        $consignee = explode(',', $row['consignee']); //First 
        $grn_no = explode(',', $row['grn_no']);
        $grn_date = explode(',', $row['grn_date']);
        $origin = explode(',', $row['origin']);
        $destination = explode(',', $row['destination']);
        $invoice_files = explode(',', $row['invoice_files']);
        $get_transaction_id = explode(',', $row['transaction_id']);
        $get_client_details =  get_client($conn, $consigner);
        $company_name = $get_client_details['client_company_name'];
        $unique_invoice_no = explode(',', $row['invoice_no']);
        $balance = explode(',', $row['invoice_amount']);
        $email = $get_client_details['email'];
        $phone = $get_client_details['contact_no'];
        $count_files = count($grn_no);
        $count_inv_files = count($invoice_files);
    	// echo "<pre>";    
    	// print_r($grn_no);
    	// echo "</pre>";  
    	//exit();
        $invoice_file = [];
        foreach ($invoice_files as $inv) {
            $invoice_es = explode("/", $inv);
            $directory = $invoice_es[1];
            $filename = $invoice_es[2];
            $file = $directory . "/" . $filename;
            $path = '/home/staging/public_html/web/';
            $invoice_file[] = $path . $file;
        }

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

        $data_serialize = serialize($data);
        $link_wit_data = http_build_query(array('aParam' => $data_serialize));

        if ($count_files > 0) {
            $out_put = '<p style="line-height: 24px; margin-bottom:15px;">		
            Your '.$inv_frq.' Days Order Summary From <a href = "http://localhost/graciousexpress" >Elite Wave 360</a> on ' . $row['grn_date'] . '! <br>
            Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
            <table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
            <thead>
            
            <tr>
            <th colspan=6>Consignor Report Email</th>

            </tr>
            <tr>
            <th>S.No </th>
            <th>Consignee </th>
            <th>Grn No </th>
            <th>Grn Date </th>
            <th>Origin </th>
            <th>Destination</th>
            </tr>
            </thead>
            <tbody>
            ';
            $sno = 1;
        	
            for ($i = 0; $i < $count_files; $i++) { //First Client Loop 

                $out_put .= '<tr>
                <td>' . $sno . ' </td>
                <td>' . get_client_name($conn,$consignee[$i]) . '</td>
                <td>' . $grn_no[$i] . '</td>
                <td>' . $grn_date[$i] . '</td>
                <td>' . get_city_name($conn,$origin[$i]) . '</td>
                <td>' . get_city_name($conn,$destination[$i]) . '</td>
                </tr>
               ';

                $sno++;
            }
            
            //echo $out_put;
            $out_put .= '</tbody>
            </table>
            <br>
            <br>
            Payment Link : <a href = "http://localhost/graciousexpress/verify_paylink.php?data=' . urlencode($link_wit_data) . '" >Payment Link</a>';
            $msg = $out_put;
            $to_name = array();
            $to_email = array();

            if (!empty(get_client_email($conn, $consigner))) {
                //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
                array_push($to_email, get_client_email($conn, $consigner));
                array_push($to_name, get_client_name($conn, $consigner));

                // $mail = sendAttachments($to_name, $to_email, $inv_frq.'-Days Consignor Report Mail', $invoice_file, $image, $msg, $name);
                // if ($mail) {
                    $query_update = "UPDATE `automation_mail_report` SET job_status = 'SUCCESS', updated_at = '$created_date' WHERE consigner = '$consigner'";
                    echo $consigner ."/";
                    $update_res = mysqli_query($conn, $query_update);
                    if ($update_res) {
                        echo "Job Sent";
                        $update_trans_table = "UPDATE $table[0] SET `frq_sent_status`='SENT' WHERE consigner = '$consigner' AND `transaction_id` IN(".$row['transaction_id'].")";
                        $res_upd_trans_tbl = mysqli_query($conn, $update_trans_table);

                        $update_trans_table1 = "UPDATE $table_prev_quarter[0] SET `frq_sent_status`='SENT' WHERE consigner = '$consigner' AND `transaction_id` IN(".$row['transaction_id'].")";
                        $res_upd_trans_tbl1 = mysqli_query($conn, $update_trans_table1);

                        // $done_job = "DELETE FROM `automation_mail_report` where job_status = 'SUCCESS' and id = '$id'";
                        // $delete_records = mysqli_query($conn, $done_job);

                        
                    }
                // }
                
                $frq_date_client = $get_client_details['invoice_frequency'];
                // $frq_ends = date('d-m-Y', strtotime($current_date, "+$frq_date_client days"));
                $frq_ends = $frq_ends = date('d-m-Y', strtotime("+$frq_date_client days",strtotime("$current_date")));
                $upd_client = "UPDATE `client` SET `frequency_date` = '$frq_ends' WHERE client_id = '$consigner'";
                $res_upd_client_tbl = mysqli_query($conn, $upd_client);
                
            }
            //End
           unset($invoice_file);
        } else {
            echo "Files not Found<br>";
        }


    }
    //echo "Job Completed and Queue Removed";
} else {

    echo "No Records Found<br>";
    
}
