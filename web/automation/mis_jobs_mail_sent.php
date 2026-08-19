<?php
require_once("/home/staging/public_html/web/include/connect.php");
//require_once("save_admin.php");
include("/home/staging/public_html/web/include/function.php");
require_once("/home/staging/public_html/web/appMail.php");
require_once ('/home/staging/public_html/Twillio/vendor/autoload.php');
require_once('/home/staging/public_html/Twillio/constant.php');

date_default_timezone_set('Asia/Kolkata');
$current_date = date('Y-m-d');
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
$qr =  "SELECT * FROM `automation_mail_report` where frequency_type = '1D' and job_status = 'Queued' ";
$check_pending_jobs = mysqli_query($conn,$qr);

if (mysqli_num_rows($check_pending_jobs) > 0) {
    while ($row = mysqli_fetch_assoc($check_pending_jobs)) { //While Select First Row
        $id = $row['id'];
        $consignee = $row['consignee']; //First 
        $consigner = explode(',', $row['consigner']);
        $grn_no = explode(',', $row['grn_no']);
        $grn_date = explode(',', $row['grn_date']);
        $origin = explode(',', $row['origin']);
       
        $get_client_details =  get_client($conn, $consignee);
        $company_name = $get_client_details['client_company_name'];

        $count_files = count($grn_no);
    	// echo "<pre>";    
    	// print_r($grn_no);
    	// echo "</pre>";  
    	//exit();
        if ($count_files > 0) {
            $out_put = '<p style="line-height: 24px; margin-bottom:15px;">		
            Your 1 Day Order Summary From <a href = "http://localhost/graciousexpress" >Elite Wave 360</a> on ' . $row['grn_date'] . '! <br>
            Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
            <table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
            <thead>
            
            <tr>
            <th colspan=5>Consignee Report Email</th>

            </tr>
            <tr>
            <th>S.No </th>
            <th>Consignor </th>
            <th>Grn No </th>
            <th>Grn Date </th>
            <th>Origin </th>
            </tr>
            </thead>
            <tbody>
            ';
            $sno = 1;
        	
            for ($i = 0; $i < $count_files; $i++) { //First Client Loop 

                $out_put .= '<tr>
                <td>' . $sno . ' </td>
                <td>' . get_client_name($conn,$consigner[$i]) . '</td>
                <td>' . $grn_no[$i] . '</td>
                <td>' . $grn_date[$i] . '</td>
                <td>' . get_city_name($conn,$origin[$i]) . '</td>
                </tr>
               ';

                $sno++;
            }
            
            //echo $out_put;
            $out_put .= '</tbody>
            </table>
            <br>
            <br>';
            $msg = $out_put;
            $to_name = array();
            $to_email = array();

            // if (!empty(get_client_email($conn, $consignee))) {
            //     //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
            //     array_push($to_email, get_client_email($conn, $consignee));
            //     array_push($to_name, get_client_name($conn, $consignee));

                // $mail = sendAttachments($to_name, $to_email, '1-Days Consignee Report Mail', $invoice_files, $image, $msg, $name);

                // if ($mail) {
                    $query_update = "UPDATE `automation_mail_report` SET job_status = 'SUCCESS', updated_at = '$current_date'";
                    $update_res = mysqli_query($conn, $query_update);
                    if ($update_res) {
                        echo "Job Sent";
                        $update_trans_table = "UPDATE $table[0] SET `frq_sent_status`='SENT' WHERE consigner = '$client_id' and `transaction_id` IN(" . $row['transaction_id'] . ")";
                        $res_upd_trans_tbl = mysqli_query($conn, $update_trans_table);

                        // $done_job = "DELETE FROM `automation_mail_report` where job_status = 'SUCCESS' and id = '$id'";
                        // $delete_records = mysqli_query($conn, $done_job);

                        
                    }
                // }
            // }
            //End

        } else {
            echo "Files not Found";
        }


    }
    //echo "Job Completed and Queue Removed";
} else {

    echo "No Records Found";
    
}
