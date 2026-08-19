<?php
error_reporting(0);
require_once("/home/staging/public_html/web/include/connect.php");
include("/home/staging/public_html/web/include/function.php");
require_once("/home/staging/public_html/web/appMail.php");
require_once ('/home/staging/public_html/Twillio/vendor/autoload.php');
require_once('/home/staging/public_html/Twillio/constant.php');

// require_once("../include/connect.php");
// include("../include/function.php");
// require_once("../appMail.php");
// require_once '../../Twillio/vendor/autoload.php';
// require_once('../../Twillio/constant.php');

date_default_timezone_set('Asia/Kolkata');
//$current_date = date('Y-m-d');
$current_date = '2022-09-16';

$table_date = '30-09-2022';
$explode_date = explode("-", $current_date);
$year = $explode_date[0];
$month = $explode_date[1];
$day = $explode_date[2];

use Twilio\Rest\Client;
 
// Month Divide into 15Days.

$first_day_of_month = date('Y-m-01',strtotime($current_date));
$midle_day_of_month = date('Y-m-15',strtotime($current_date));
$midle_next_day_of_month = date('Y-m-16',strtotime($current_date));
$lastDay_of_month = date('Y-m-t',strtotime($current_date));

// echo $first_day_of_month;
// echo "<br>";
// echo $midle_day_of_month;
// echo "<br>";
// echo $midle_next_day_of_month;
// echo "<br>";
// echo $lastDay_of_month;
// echo "<br>";
// echo "<br>";

// End.

$table = get_trans_table_name_only($conn, $table_date);
$m_year = substr($table[0], 12, 17);

$find_client = "select * from client where invoice_frequency = '15'"; // 1000 Entry
$check_frq = mysqli_query($conn, $find_client);
if (mysqli_num_rows($check_frq) > 0) {
    while ($row = mysqli_fetch_assoc($check_frq)) {
        $client_id = $row['client_id'];

        //echo $client_id;


        if ($current_date >= $first_day_of_month && $current_date <= $midle_day_of_month) {

            echo $query = 'SELECT * FROM ' . $table[0] . ' WHERE consigner = "' . $client_id . '" and mode_of_consignment = 2 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2  AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $first_day_of_month . '","%Y-%m-%d") and str_to_date("' . $midle_day_of_month . '","%Y-%m-%d")';
            echo "<br>";
            echo "<br>";
        } else {

            echo $query = 'SELECT * FROM ' . $table[0] . ' WHERE consigner = "' . $client_id . '" and mode_of_consignment = 2 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2  AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $midle_next_day_of_month . '","%Y-%m-%d") - 15 and str_to_date("' . $lastDay_of_month . '","%Y-%m-%d")';
            echo "<br>";
            echo "<br>";
            //and mode_of_consignment = 2 
        }

        $transactions = mysqli_query($conn, $query);
        $rest = mysqli_num_rows($transactions);
        //print_r($rest);
        //exit();

        if (mysqli_num_rows($transactions) > 0) {

            $get_transaction_id = [];
            $grn_no = [];
            $grn_date = [];
            $consignee = [];
            $origin = [];
            $destination = [];
            $unique_invoice_no = [];
            $total = [];
            $balance = [];
            while ($row2 = mysqli_fetch_assoc($transactions)) {
                $get_transaction_id[] = $row2['transaction_id'];
                $grn_id = $row2['grn_id'];
                $grn_no[] = $row2['grn_no'];
                $grn_date[] = $row2['grn_date'];
                $consigner = $row2['consigner'];
                $consignee[] = $row2['consignee'];
                $origin[] = $row2['origin'];
                $destination[] = $row2['destination'];
                $mode_of_consignment[] = $row2['mode_of_consignment'];
                $unique_invoice_no[] = $row2['invoice_no'];
                $total[] = $row2['total'];
                $balance[] = $row2['balance'];

                $find_invoice_file = array($consigner, $m_year . "_" . $row2['transaction_id'] . "invoice");

                if (in_array($consigner, $find_invoice_file)) {

                    $path = "../digital_invoice/";
                    $invoice_files[] = $path . $m_year . "_" . $row2['transaction_id'] . "invoice.pdf";
                } else {
                }
            }


            // Array Values into String 
            $grn_numbers = implode(',', $grn_no);
            $grn_dates = implode(',', $grn_date);
            $consignees = implode(',', $consignee);
            $origins = implode(',', $origin);
            $destinations = implode(',', $destination);
            $transct_id = implode(',', $get_transaction_id);
            $invoice_no = implode(',', $unique_invoice_no);
            $amounts = implode(',', $balance);
            $invoice_filess = implode(',', $invoice_files);
            // echo "<pre>";
            // print_r($invoice_files);
            // echo "</pre>";	
            //exit();
            $count_files = count($invoice_files);
            if ($count_files > 0) {

                $check_date_exist = mysqli_query($conn, "select * from `automation_mail_report` where consigner = '$consigner' and 	frequency_type = '15D' and client_type ='1'");
                $result_check = mysqli_num_rows($check_date_exist);

                if ($result_check == 0) {
                    $queue_job = "INSERT INTO `automation_mail_report`(`frequency_type`,`client_type`, `consigner`, `consignee`, `grn_no`, `grn_date`, `origin`, `destination`,`transaction_id`, `invoice_amount`, `invoice_no`, `invoice_files`, `job_status`, `created_at`, `updated_at`) VALUES('15D','1','$consigner','$consignees','$grn_numbers','$grn_dates','$origins','$destinations','$transct_id','$amounts','$invoice_no','$invoice_filess','Queued','$current_date','$current_date')";
                    $ress  = mysqli_query($conn, $queue_job);
                    if ($ress) {
                        // echo "Inserted";
                        // exit();
                        $update_trans_table = "UPDATE $table[0] SET `frq_sent_status`='Queued' WHERE consigner = '$client_id' and `transaction_id` IN($transct_id)";
                        $res_upd_trans_tbl = mysqli_query($conn, $update_trans_table);

                        echo "Queued Job Successfully";
                    }
                } else {
                    echo "Entry Already Exist";
                }
            } else {

                echo "Files not Found";
            }
        }
    }
} else {
    echo "No Clients Found";
}
