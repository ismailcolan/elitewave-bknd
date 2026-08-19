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
//$current_date = '2022-09-30';
$current_date = '2022-08-15'; //CronSet Every Month 15
$table_date = '01-02-2022';
$explode_date = explode("-", $current_date);
$year = $explode_date[0];
$month = $explode_date[1];
$day = $explode_date[2];

use Twilio\Rest\Client;

function get_quarter($date)
{
    $dt = (explode("-", $date));
    //print_r($dt);
    $y = $dt[0];
    //print_r($y);
    $m = $dt[1];
    $d = $dt[2];
    if ($m <= 3)
        $m1 = 1;

    else if (($m >= 4) && ($m <= 6))
        $m1 = 2;

    else if (($m >= 7) && ($m <= 9))
        $m1 = 3;

    else
        $m1 = 4;

    $get_all = [
        $m1,
        $y,
        $d
    ];
    return $get_all;
}

$table = get_trans_table_name_only($conn, $table_date);

//print_r($table[0]);
$tabless = explode("_", $table[0]);

//print_r($tabless);
$transaction = 'transaction_';


$m_year = substr($table[0], 12, 17);

$find_client = "select * from client where invoice_frequency = '45'"; // 1000 Entry
$check_frq = mysqli_query($conn, $find_client);
if (mysqli_num_rows($check_frq) > 0) {

    while ($row = mysqli_fetch_assoc($check_frq)) {
        echo $client_id = $row['client_id'];

        //Current Date of Month
        $first_day_of_month = date('Y-m-01', strtotime($current_date . '-1 Months')); // 01- 03-2022 Q1
        $last_day_of_month = date('Y-m-t', strtotime($current_date . '-1 Months')); // 31- 03-2022 
        $lastd = substr($last_day_of_month, 0, 2);
        // echo $lastd;
        if ($lastd == '31') {
            $add_intval = 13;
        } else if ($lastd == '30') {
            $add_intval = 14;
        } else {
            $add_intval = 16;
        }

        // echo "<br>";
        // echo "First_Day_Current_Month:" . $first_day_of_month . " Last Day of Month" . $last_day_of_month;
        // echo "<br>";

        //Current Date of Next Month
        $next_fifteen_days_of_month = date('Y-m-d', strtotime($first_day_of_month . '+ 1 months')); //01-04-2022 Q2

        $last_date_of_fifteen_days_of_month = date('Y-m-d', strtotime($next_fifteen_days_of_month . $add_intval . ' Days')); //15-04-2022

        // echo "<br>";
        // echo "Next_15Day_Month:" . $next_fifteen_days_of_month . " LastDay_15Day_Month" . $last_date_of_fifteen_days_of_month;
        // echo "<br>";

        //Current Date of Previous Month
        $previous_month = date('Y-m-d', strtotime($first_day_of_month . '- 1 months'));
        $previous_month_last_date = date('Y-m-t', strtotime($previous_month));

        // echo "<br>";
        // echo "Previous_Month:" . $previous_month . " LastDay_Previous_Month" . $previous_month_last_date;
        // echo "<br>";


        $lastdd = substr($previous_month_last_date, 0, 2);
        if ($lastdd == '31') {
            $prev_add_intval = 13;
        } else if ($lastdd == '30') {
            $prev_add_intval = 14;
        } else {
            $prev_add_intval = 16;
        }
        //Current Date of Previous Month 15Days
        $next_fifteen_days_of_previous_month = date('Y-m-d', strtotime($previous_month . '-1 Days')); //
        $last_date_of_fifteen_days_of_prev_month = date('Y-m-d', strtotime($next_fifteen_days_of_previous_month . "-" . $prev_add_intval . ' Days')); //

        // echo "<br>";
        // echo "Previous_15Day_Month:" . $next_fifteen_days_of_previous_month . " Previous_LastDay_15Day_Month" . $last_date_of_fifteen_days_of_prev_month;
        // echo "<br>";
        // echo "<br>";

        $transaction = 'transaction_';

        //current
        $current_month_quarter = get_quarter($first_day_of_month);
        $yy = $current_month_quarter[1];
        $mm = $current_month_quarter[0];

        //next month of current month
        $next_month_quarter = get_quarter($next_fifteen_days_of_month);
        $yy1 = $next_month_quarter[1];
        $mm1 = $next_month_quarter[0];

        //previous month of current month
        $previous_month_quarter = get_quarter($next_fifteen_days_of_previous_month);
        $yy2 = $previous_month_quarter[1];
        $mm2 = $previous_month_quarter[0];

        //last 15 days of previous month
        $last15_previous_month_quarter = get_quarter($last_date_of_fifteen_days_of_prev_month);
        $yy3 = $last15_previous_month_quarter[1];
        $mm3 = $last15_previous_month_quarter[0];

        // echo $transaction . $mm . "_" . $yy;

        // echo "<br>";
        // echo $transaction . $mm1 . "_" . $yy1;
        // echo "<br>";
        // echo $transaction . $mm2 . "_" . $yy2;
        // echo "<br>";
        // echo $transaction . $mm3 . "_" . $yy3;
        // echo "<br>";
        // echo "<br>";
        // echo "<br>";
        echo $query = 'SELECT * FROM ' . $transaction . $mm . "_" . $yy . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $first_day_of_month . '","%Y-%m-%d") and str_to_date("' . $last_day_of_month . '","%Y-%m-%d")
            UNION SELECT * FROM ' . $transaction . $mm1 . "_" . $yy1 . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $next_fifteen_days_of_month . '","%Y-%m-%d") and str_to_date("' . $last_date_of_fifteen_days_of_month . '","%Y-%m-%d")
            UNION SELECT * FROM ' . $transaction . $mm2 . "_" . $yy2 . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $previous_month . '","%Y-%m-%d") and str_to_date("' . $previous_month_last_date . '","%Y-%m-%d")
            UNION SELECT * FROM ' . $transaction . $mm3 . "_" . $yy3 . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $last_date_of_fifteen_days_of_prev_month . '","%Y-%m-%d") and str_to_date("' . $next_fifteen_days_of_previous_month . '","%Y-%m-%d")
    ';

       
        $transactions = mysqli_query($conn, $query);
        $rest = mysqli_num_rows($transactions);
        print_r($rest);
        exit();

        if (mysqli_num_rows($transactions) > 0) {

            $get_transaction_id = [];
            $grn_no = [];
            $grn_date = [];
            $consigner = [];
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
                $consigner[] = $row2['consigner'];
                $consignee = $row2['consignee'];
                $origin[] = $row2['origin'];
                $destination[] = $row2['destination'];
                $mode_of_consignment[] = $row2['mode_of_consignment'];
                $unique_invoice_no[] = $row2['invoice_no'];
                $total[] = $row2['total'];
                $balance[] = $row2['balance'];

                $dt = (explode("-", $row2['grn_date']));
                //print_r($dt);
                $y = $dt[2];
                $m = $dt[1];
                $d = $dt[0];
                if ($m <= 3)
                    $m1 = 1;

                else if (($m >= 4) && ($m <= 6))
                    $m1 = 2;

                else if (($m >= 7) && ($m <= 9))
                    $m1 = 3;

                else
                    $m1 = 4;
                // echo $m1 . "_" . $y .  "_" . $row2['transaction_id'] . "invoice";
                // echo "<br>";

                $find_invoice_file = array($consignee, $m1 . "_" . $y .  "_" . $row2['transaction_id'] . "invoice");
                // echo "<pre>";
                // print_r($find_invoice_file);
                // echo "</pre>";
                //exit();

                if (in_array($consignee, $find_invoice_file)) {

                    $path = "../digital_invoice/";
                    $invoice_files[] = $path .  $m1 . "_" . $y  . "_" . $row2['transaction_id'] . "invoice.pdf";
                } else {
                }
            }


            // Array Values into String 
            $grn_numbers = implode(',', $grn_no);
            $grn_dates = implode(',', $grn_date);
            $consigners = implode(',', $consigner);
            $origins = implode(',', $origin);
            $destinations = implode(',', $destination);
            $transct_id = implode(',', $get_transaction_id);
            $invoice_no = implode(',', $unique_invoice_no);
            $amounts = implode(',', $balance);
            $invoice_filess = implode(',', $invoice_files);
            echo "<pre>";
            print_r($get_transaction_id);
            echo "</pre>";	
            //exit();
            $count_files = count($invoice_files);
            if ($count_files > 0) {

                $check_date_exist = mysqli_query($conn, "select * from `automation_mail_report` where consignee = '$consignee' and frequency_type = '45D' and client_type ='2'");
                $result_check = mysqli_num_rows($check_date_exist);

                if ($result_check == 0) {
                    $queue_job = "INSERT INTO `automation_mail_report`(`frequency_type`, `client_type`,`consigner`, `consignee`, `grn_no`, `grn_date`, `origin`, `destination`,`transaction_id`, `invoice_amount`, `invoice_no`, `invoice_files`, `job_status`, `created_at`, `updated_at`) VALUES('45D','2','$consigners','$consignee','$grn_numbers','$grn_dates','$origins','$destinations','$transct_id','$amounts','$invoice_no','$invoice_filess','Queued','$current_date','$current_date')";
                    $ress  = mysqli_query($conn, $queue_job);
                    if ($ress) {

                        $explode_grn_dates = explode(',', $grn_dates);
                        $explode_trans_id = explode(',', $transct_id);
                        $count_grn_dates = count($explode_grn_dates);
                        if ($count_grn_dates > 0) {

                            for ($h = 0; $h < $count_grn_dates; $h++) {
                                $transaction_id = $explode_trans_id[$h];

                                $grn_dated = $explode_grn_dates[$h];

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

                                echo $update_trans_table = "UPDATE " . $transaction . $m1 . "_" . $y . " SET `frq_sent_status`='Queued' WHERE consignee = '$client_id' and `transaction_id` = '$transaction_id'";
                                $res_upd_trans_tbl = mysqli_query($conn, $update_trans_table);
                                // exit();

                            }
                            echo "Queued Job Successfully";
                        }
                    }
                } else {
                    echo "Entry Already Exist";
                }
            } else {

                echo "Files not Found";
            }
        }else{
            echo "Not Data Found";
        }
    }
} else {
    echo "No Clients Found";
}
