<?php
error_reporting(0);
require_once("/home/staging/public_html/web/include/connect.php");
//require_once("save_admin.php");
include("/home/staging/public_html/web/include/function.php");
require_once("/home/staging/public_html/web/appMail.php");
require_once ('/home/staging/public_html/Twillio/vendor/autoload.php');
require_once('/home/staging/public_html/Twillio/constant.php');

date_default_timezone_set('Asia/Kolkata');
$current_date = date('Y-m-d');
// $current_date = '2022-09-30';
// echo $current_date;
// exit();
$table_date = date('d-m-Y');
// $table_date = '30-09-2022';
$explode_date = explode("-", $current_date);
//print_r($explode_date);
$year = $explode_date[0];
$month = $explode_date[1];
$day = $explode_date[2];

$company_id = $_SESSION['company_id'];

use Twilio\Rest\Client;

// //First Week
// $first_week_first_day= $weeks[0][0];
// $first_week_last_day = $weeks[0][1];

// //Second Week
// $second_week_first_day = $weeks[1][0];
// $second_week_last_day = $weeks[1][1];

// //Third Week
// $third_week_first_day = $weeks[2][0];
// $third_week_last_day = $weeks[2][1];

// //Fourth Week
// $fourth_week_first_day = $weeks[3][0];
// $fourth_week_last_day = $weeks[3][1];

// //Last Week
// $last_week_first_day = $weeks[4][0];
// $last_week_last_day = $weeks[4][1];


$weeks = monthToWeeks($year, $month);
$table = get_trans_table_name_only($conn, $table_date);
$m_year = substr($table[0], 12, 17);

//(01-09-2022,02-09-2022,03-09-2022,04-09-2022,05-09-2022,06-09-2022,07-09-2022)

echo $find_client = "select * from client where invoice_frequency = '2'"; // 1000 Entry
$check_frq = mysqli_query($conn, $find_client);
if (mysqli_num_rows($check_frq) > 0) {
    while ($row = mysqli_fetch_assoc($check_frq)) {
        $client_id = $row['client_id'];
        for ($i = 0; $i < sizeof($weeks); $i++) {

            for ($j = 0; $j < sizeof($weeks[$i]); $j++) {
                // if ($weeks[$i][$j + 1] != '') {

                    if ($day == '01') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {

                            echo $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else if ($day == '03') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            // echo "<pre>";
                            // print_r("first_date : ".$weeks[$i][$j]." and last date :" . $weeks[$i][$j + 1]);
                            // echo "</pre>";
                            // echo "<br>";
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            echo $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                            //exit();
                        // }
                    } else if ($day == '05') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {

                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            $multiply_days = $days * 2;

                            $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else if ($day == '07') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            $multiply_days = $days * 3;

                            $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else if ($day == '09') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            $multiply_days = $days * 4;

                            $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else if ($day == '11') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            $multiply_days = $days * 4;

                            $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else if ($day == '13') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            $multiply_days = $days * 4;

                            $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else if ($day == '15') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            $multiply_days = $days * 4;

                            $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else if ($day == '17') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            $multiply_days = $days * 4;

                            $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else if ($day == '19') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            $multiply_days = $days * 4;

                            $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else if ($day == '21') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            $multiply_days = $days * 4;

                            $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else if ($day == '23') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            $multiply_days = $days * 4;

                            $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else if ($day == '25') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            $multiply_days = $days * 4;

                            $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else if ($day == '27') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            $multiply_days = $days * 4;

                            $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else if ($day == '29') {

                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            $multiply_days = $days * 4;

                            $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                        // }
                    } else {
                        // if ($current_date >= $weeks[$i][$j] && $current_date <= $weeks[$i][$j + 1]) {
                            $date1 = $weeks[$i - 1][$j];
                            $date2 = $weeks[$i][$j];

                            $diff = abs(strtotime($date2) - strtotime($date1));

                            $years = floor($diff / (365 * 60 * 60 * 24));
                            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
                            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                            // if ($day == '30') {
                            //     $add_day = 2;
                            // } else {
                            //     $add_day = 3;
                            // }
                            $multiply_days = $days * 4;

                            echo  $query = 'SELECT * FROM ' . $table[0] . ' WHERE consignee = "' . $client_id . '" and mode_of_consignment = 1 and status = 8 and frq_sent_status = "" and ISNULL(paid_status) or paid_status = 0 or paid_status = 2 AND `book_manual` = 1 and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $weeks[$i][$j] . '","%Y-%m-%d") - ' . $multiply_days . ' and str_to_date("' . $weeks[$i][$j + 1] . '","%Y-%m-%d")';
                            //exit();
                        // }
                    }
                // }
            }
        }

        $transactions = mysqli_query($conn, $query);
        $rest = mysqli_num_rows($transactions);
        print_r($rest);
        // exit();

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

                $find_invoice_file = array($consignee, $m_year . "_" . $row2['transaction_id'] . "invoice");
                // echo "<pre>";
                // print_r($find_invoice_file);
                // echo "</pre>";
                // echo "_______________________";
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
            print_r($consigners);
            echo "</pre>";
            // exit();
            $count_files = count($invoice_files);
            if ($count_files > 0) {

                $check_date_exist = mysqli_query($conn, "select * from `automation_mail_report` where consignee = '$consignee' and frequency_type = '2D' and client_type ='2'");
                $result_check = mysqli_num_rows($check_date_exist);

                if ($result_check == 0) {
                    $queue_job = "INSERT INTO `automation_mail_report`(`frequency_type`, `client_type`,`consigner`, `consignee`, `grn_no`, `grn_date`, `origin`, `destination`,`transaction_id`, `invoice_amount`, `invoice_no`, `invoice_files`, `job_status`, `created_at`, `updated_at`) VALUES('2D','2','$consigners','$consignee','$grn_numbers','$grn_dates','$origins','$destinations','$transct_id','$amounts','$invoice_no','$invoice_filess','Queued','$current_date','$current_date')";
                    $ress  = mysqli_query($conn, $queue_job);
                    if ($ress) {
                        $update_trans_table = "UPDATE $table[0] SET `frq_sent_status`='Queued' WHERE consignee = '$client_id' and `transaction_id` IN($transct_id)";
                        $res_upd_trans_tbl = mysqli_query($conn, $update_trans_table);

                        echo "Queued Job Successfully";
                    }
                }else{
                    echo "Data Already Exist";
                }
            } else {

                echo "Files not Found";
            }
        }else{
            echo "No Data Found";
        }
    }
} else {
    echo "No Clients Found";
}
