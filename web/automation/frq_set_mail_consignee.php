<?php
error_reporting(0);
require_once("/home/staging/public_html/web/include/connect.php");
include("/home/staging/public_html/web/include/function.php");
require_once("/home/staging/public_html/web/appMail.php");
date_default_timezone_set('Asia/Kolkata');
$current_date = date('d-m-Y');
// $current_date = "01-01-2024";
$table_date = date('d-m-Y');
// $table_date = "01-01-2024";
$explode_date = explode("-", $current_date);
$year = $explode_date[0];
$month = $explode_date[1];
$day = $explode_date[2];
$created_date = date('Y-m-d');
// $company_id = $_SESSION['company_id'];

$table = get_trans_table_name_only($conn, $table_date);
$m_year = substr($table[0], 12, 17);


if(!empty($_GET['frq_day'])) {
    $frq_day = $_GET['frq_day'];
    $frq_end = date('d-m-Y', strtotime("+$frq_day days",strtotime("$current_date")));
    
	$table_dt_quarter = date('d-m-Y', strtotime("-$frq_day days",strtotime("$current_date")));
	$table_prev_quarter = get_trans_table_name_only($conn, $table_dt_quarter);
	$m_year_quarter = substr($table_prev_quarter[0], 12, 17);
}

$find_client = "SELECT * FROM client WHERE invoice_frequency != '0' AND  invoice_frequency = '$frq_day' AND frequency_date = '$current_date'";
$check_frq = mysqli_query($conn, $find_client);
if (mysqli_num_rows($check_frq) > 0) {
    while ($row = mysqli_fetch_assoc($check_frq)) {
        $client_id = $row['client_id'];
        $frq_date = $row['frequency_date'];
        $inv_frq = $row['invoice_frequency'];
        // $frq_end = "13-10-2023";
       
        $query = 'SELECT * FROM '.$table[0].' WHERE STR_TO_DATE(grn_date,"%d-%m-%Y") < STR_TO_DATE("'.$current_date.'","%d-%m-%Y") AND  consignee = '.$client_id.' AND `mode_of_consignment` = 1 AND `status` = 8 AND `frq_sent_status` = "" AND (ISNULL(paid_status) OR `paid_status` = 0 OR `paid_status` = 2) AND `book_manual` = 1';
                    
        $transactions = mysqli_query($conn, $query);
        $rest = mysqli_num_rows($transactions);
        print_r($rest);
        // exit();
        
        if ($rest > 0) {
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

                $find_invoice_file = array($consignee, $m_year . "_" . $row2['transaction_id'] . "invoice");
                if (in_array($consignee, $find_invoice_file)) {
                    $path = "../digital_invoice/";
                    $invoice_files[] = $path . $m_year . "_" . $row2['transaction_id'] . "invoice.pdf";
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
            print_r($invoice_files);
            echo "</pre>";
            // exit();
            $count_files = count($invoice_files);
            if ($count_files > 0) {
                $freq_type = $frq_day."D";
                $check_date_exist = mysqli_query($conn, "select * from `automation_mail_report` where consignee = '$consignee' and frequency_type = '$freq_type' and client_type ='2' AND `job_status` != 'SUCCESS'");
                $result_check = mysqli_num_rows($check_date_exist);

                if ($result_check == 0) {
                    $queue_job = "INSERT INTO `automation_mail_report`(`frequency_type`, `client_type`,`consigner`, `consignee`, `grn_no`, `grn_date`, `origin`, `destination`,`transaction_id`, `invoice_amount`, `invoice_no`, `invoice_files`, `job_status`, `frq_sent_date`, `created_at`, `updated_at`) VALUES('$freq_type','2','$consigners','$consignee','$grn_numbers','$grn_dates','$origins','$destinations','$transct_id','$amounts','$invoice_no','$invoice_filess','Queued', '$current_date','$created_date','$created_date')";
                    $ress  = mysqli_query($conn, $queue_job);
                    if ($ress) {
                        $update_trans_table = "UPDATE $table[0] SET `frq_sent_status`='Queued' WHERE consignee = '$client_id' and `transaction_id` IN($transct_id)";
                        $res_upd_trans_tbl = mysqli_query($conn, $update_trans_table);

                        echo "Queued Job Successfully";
                    }
                }else{
                    $queue_job_update = "UPDATE `automation_mail_report` SET `frequency_type`='$freq_type',`consigner`='$consigners',`grn_no`='$grn_numbers',`grn_date`='$grn_dates',`origin`='$origins',`destination`='$destinations',`transaction_id`='$transct_id',`invoice_amount`='$amounts',`invoice_no`='$invoice_no',`invoice_files`='$invoice_filess', `frq_sent_date`='$current_date',`updated_at`='$created_date' WHERE `consignee` = '$consignee'AND frequency_type = '$freq_type' AND client_type ='2' AND `job_status` != 'SUCCESS'";
                    $ress  = mysqli_query($conn, $queue_job_update);
                    // echo "Data Already Exist";
                }
            } else {

                echo "Files not Found<br>";
            }
        }else{
            echo "No Data Found<br>";
        }
        unset($invoice_files);
	// Below code is to fetch the data from previous quarter if quarter is changed means the table is also changed for that below code helps to get prev quarter table data
        $query1 = 'SELECT * FROM '.$table_prev_quarter[0].' WHERE STR_TO_DATE(grn_date,"%d-%m-%Y") < STR_TO_DATE("'.$current_date.'","%d-%m-%Y") AND  consignee = '.$client_id.' AND `mode_of_consignment` = 1 AND `status` = 8 AND `frq_sent_status` = "" AND (ISNULL(paid_status) OR `paid_status` = 0 OR `paid_status` = 2) AND `book_manual` = 1';
                    
        $transactions1 = mysqli_query($conn, $query1);
        $rest1 = mysqli_num_rows($transactions1);
        print_r($rest1);
        // exit();
        
        if ($rest1 > 0) {
            $get_transaction_id1 = [];
            $grn_no1 = [];
            $grn_date1 = [];
            $consigner1 = [];
            $origin1 = [];
            $destination1 = [];
            $unique_invoice_no1 = [];
            $total1 = [];
            $balance1 = [];
            while ($row2 = mysqli_fetch_assoc($transactions1)) {
                $get_transaction_id1[] = $row2['transaction_id'];
                $grn_id1 = $row2['grn_id'];
                $grn_no1[] = $row2['grn_no'];
                $grn_date1[] = $row2['grn_date'];
                $consigner1[] = $row2['consigner'];
                $consignee1 = $row2['consignee'];
                $origin1[] = $row2['origin'];
                $destination1[] = $row2['destination'];
                $mode_of_consignment1[] = $row2['mode_of_consignment'];
                $unique_invoice_no1[] = $row2['invoice_no'];
                $total1[] = $row2['total'];
                $balance1[] = $row2['balance'];

                $find_invoice_file1 = array($consignee1, $m_year_quarter . "_" . $row2['transaction_id'] . "invoice");
                if (in_array($consignee1, $find_invoice_file1)) {
                    $path1 = "../digital_invoice/";
                    $invoice_files1[] = $path1 . $m_year_quarter . "_" . $row2['transaction_id'] . "invoice.pdf";
                } else {
                }
            }


            // Array Values into String 
            $grn_numbers1 = implode(',', $grn_no1);
            $grn_dates1 = implode(',', $grn_date1);
            $consigners1 = implode(',', $consigner1);
            $origins1 = implode(',', $origin1);
            $destinations1 = implode(',', $destination1);
            $transct_id1 = implode(',', $get_transaction_id1);
            $invoice_no1 = implode(',', $unique_invoice_no1);
            $amounts1 = implode(',', $balance1);
            $invoice_filess1 = implode(',', $invoice_files1);
            echo "<pre>";
            print_r($invoice_files1);
            echo "</pre>";
            // exit();
            $count_files1 = count($invoice_files1);
            if ($count_files1 > 0) {
                $freq_type = $frq_day."D";
                $check_date_exist1 = mysqli_query($conn, "select * from `automation_mail_report` where consignee = '$consignee1' and frequency_type = '$freq_type' and client_type ='2' AND `job_status` != 'SUCCESS'");
                $result_check1 = mysqli_num_rows($check_date_exist1);

                if ($result_check1 == 0) {
                    $queue_job1 = "INSERT INTO `automation_mail_report`(`frequency_type`, `client_type`,`consigner`, `consignee`, `grn_no`, `grn_date`, `origin`, `destination`,`transaction_id`, `invoice_amount`, `invoice_no`, `invoice_files`, `job_status`, `frq_sent_date`, `created_at`, `updated_at`) VALUES('$freq_type','2','$consigners1','$consignee1','$grn_numbers1','$grn_dates1','$origins1','$destinations1','$transct_id1','$amounts1','$invoice_no1','$invoice_filess1','Queued', '$current_date','$created_date','$created_date')";
                    $ress1  = mysqli_query($conn, $queue_job1);
                    if ($ress1) {
                        $update_trans_table1 = "UPDATE $table_prev_quarter[0] SET `frq_sent_status`='Queued' WHERE consignee = '$client_id' and `transaction_id` IN($transct_id1)";
                        $res_upd_trans_tbl1 = mysqli_query($conn, $update_trans_table1);

                        echo "Queued Job Successfully";
                    }
                }else{
                    $queue_job_update1 = "UPDATE `automation_mail_report` SET `frequency_type`='$freq_type',`consigner`='$consigners1',`grn_no`='$grn_numbers1',`grn_date`='$grn_dates1',`origin`='$origins1',`destination`='$destinations1',`transaction_id`='$transct_id1',`invoice_amount`='$amounts1',`invoice_no`='$invoice_no1',`invoice_files`='$invoice_filess1', `frq_sent_date`='$current_date',`updated_at`='$created_date' WHERE `consignee` = '$consignee1'AND frequency_type = '$freq_type' AND client_type ='2' AND `job_status` != 'SUCCESS'";
                    $ress1  = mysqli_query($conn, $queue_job_update1);
                    // echo "Data Already Exist";
                }
            } else {

                echo "Files not Found<br>";
            }
        }else{
            echo "No Data Found<br>";
        }
        unset($invoice_files1);
    }
} else {
    echo "No Clients Found<br>";
}
