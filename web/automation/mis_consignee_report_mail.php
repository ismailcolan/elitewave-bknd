<?php
require_once("/home/staging/public_html/web/include/connect.php");
//require_once("save_admin.php");
include("/home/staging/public_html/web/include/function.php");
require_once("/home/staging/public_html/web/appMail.php");
require_once ('/home/staging/public_html/Twillio/vendor/autoload.php');
require_once('/home/staging/public_html/Twillio/constant.php');

$current_date = date('Y-m-d');
//$current_date = '2022-12-22';

$table_date = date('d-m-Y');
//$table_date = '30-09-2022';
$explode_date = explode("-", $current_date);
//print_r($explode_date);
$year = $explode_date[0];
$month = $explode_date[1];
$day = $explode_date[2];

$company_id = $_SESSION['company_id'];

use Twilio\Rest\Client;

$table = get_trans_table_name_only($conn, $table_date);
$m_year = substr($table[0], 12, 17);

echo $query = 'SELECT * FROM ' . $table[0] . ' WHERE str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $current_date . '","%Y-%m-%d") and str_to_date("' . $current_date . '","%Y-%m-%d") AND `book_manual` = 1';

$mis_result = mysqli_query($conn, $query);
$consinee = array();
while ($row = mysqli_fetch_assoc($mis_result)) {

    if(!in_array($row['consignee'],$consinee)){
        $consinee[] = $row['consignee'];
    }     
}

// print_r($consinee);


for($i = 0; $i < sizeof($consinee); $i++){
    //echo $consinee[$i];
    echo $select_consignee_wise = 'SELECT * FROM ' . $table[0] . '  WHERE consignee ="'.$consinee[$i].'"  and str_to_date(grn_date,"%d-%m-%Y") BETWEEN str_to_date("' . $current_date . '","%Y-%m-%d") and str_to_date("' . $current_date . '","%Y-%m-%d") AND `book_manual` = 1';
   
$mis_result1 = mysqli_query($conn,$select_consignee_wise);

    $result_count = mysqli_num_rows($mis_result1);
    if($result_count > 0){

        $get_transaction_id = [];
        $grn_no = [];
        $grn_date = [];
        $consigner = [];
        $origin = [];
        // $destination = [];
        // $unique_invoice_no = [];
        // $total = [];
     
        while($row2 = mysqli_fetch_assoc($mis_result1)){

            
            $grn_no[] = $row2['grn_no'];
            $grn_date[] = $row2['grn_date'];
            $consigner[] = $row2['consigner'];
            $consignee = $row2['consignee'];
            $origin[] = $row2['origin'];
        //  $destination[] = $row2['destination'];
            //$mode_of_consignment[] = $row2['mode_of_consignment'];
        //  $unique_invoice_no[] = $row2['invoice_no'];
        //  $total[] = $row2['total'];
            
        }
    //     echo "<pre>";
    //     print_r($grn_no);
    //     echo "</pre>";
    // exit();

        $grn_numbers = implode(',', $grn_no);
        $grn_dates = implode(',', $grn_date);
        $consigners = implode(',', $consigner);
        $origins = implode(',', $origin);

        $count_grn = count($grn_no);
        // echo $count_grn;

        if($count_grn > 0){
            $w = "select * from `automation_mail_report` where consignee = '$consignee' and frequency_type = '1D' and 	job_status = 'Queued'";
            $check_date_exist = mysqli_query($conn, $w);
            $result_check = mysqli_num_rows($check_date_exist);

            if ($result_check == 0) {
               $queue_job = "INSERT INTO `automation_mail_report`(`frequency_type`, `consigner`, `consignee`, `grn_no`, `grn_date`, `origin`, `job_status`, `created_at`, `updated_at`) VALUES('1D','$consigners','$consignee','$grn_numbers','$grn_dates','$origins','Queued','$current_date','$current_date')";
                $ress  = mysqli_query($conn, $queue_job);
                if ($ress) {
                    echo "Queued Job Successfully";
                }
            }else{
                echo "Job Already Exist";
            }

        }

    }
  
}


