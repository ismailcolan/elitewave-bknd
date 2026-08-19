<?php
error_reporting(0);
require('function_razorpay.php');
include_once '../../web/appMail.php';
//$conn = mysqli_connect('localhost','root','','bookconsignment');
class Payments
{


    function onlinePayment($conn, $transaction_id, $name, $email, $phone, $grn_date, $grn_no, $invoice_no, $client_id, $amount, $m_amount, $razorOrderId, $razorpayPaymentId, $paymentStatus, $created_at, $updated_at)
    {
        // echo "You are In ss";

        // print_r($m_amount);
  
        $comma_search = ',';
    
        if( strpos($m_amount, $comma_search) !== false ) {
            //echo "Empty Multiple Amount";
          

            
            $amount_array = explode(',',$m_amount); // ['483' , '1199']
            $amount_array1 =[]; // ['0' , '900']
            $grn_no_arr = explode(',',$grn_no);   // ['moham0001', 'moham0002']
            $invoice_no_arr = explode(',',$invoice_no); //['HR-GST00001','HR-GST00002']
            $transactionId_arr = explode(',',$transaction_id); //['HR-GST00001','HR-GST00002']
            $amount_paid  = $amount / 100 ;

            // print_r($amount_array);
            // echo "<br>";
            // echo $amount;
            // echo "<br>";
            // echo $amount_paid;
           
            
            //700
            //450
            for($i = 0; $i < count($amount_array); $i++){
            
                if($amount_paid == $amount_array[$i]){
                    //echo "begin";
                    $balance_amount = (float)$amount_paid - (float)$amount_array[$i] ;
                    $amounts = "0";
                    $amount_array1[$i] = $amounts;
                    $amount_paid = "0";
                    break;
                }else{
                    
                    if($amount_array[$i] < $amount_paid ){
                        $balance_amount = (float)$amount_paid - (float)$amount_array[$i] ;
                        $amounts = "0";
                        $amount_array1[$i] = $amounts;
                        $amount_paid = $balance_amount;
                    }else{
                        
                        $balance_amount =  (float)$amount_array[$i] - (float)$amount_paid ;
                        $amount_array1[$i] = $balance_amount;
                        $amount_paid = "0";
                    }
                
                    
                }
        
               
            }
    
            foreach($amount_array1 as $key => $val){
                if($val == "0"){
                    $paid_amt = $amount_array[$key];
                }else{
                    $paid_amt = (float)$amount_array[$key] - (float)$val;  
                }
            $query = "insert into razorpay_payment(`company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`,`paid`,`balance`,`razorpayOrderId`, `razorpayPaymentId`, `paymentStatus`, `created_at`, `updated_at`) values('$name','$email','$phone','$grn_no_arr[$key]','$invoice_no_arr[$key]','$client_id','$amount_array[$key]','$paid_amt','$val','$razorOrderId','$razorpayPaymentId','$paymentStatus','$created_at','$updated_at')";
            $sql = mysqli_query($conn,$query);

            // if ($sql) {

                $tables = get_trans_table_name($conn, $grn_date);
                if($val == '0'){
                    $bill_status = "1"; //Paid
                }else if($amount_array[$key] > $val){
                    $bill_status = "2";
               }
                else{
                    $bill_status = "0"; //Pending
               }

                echo $query_inv = "update $tables[0] set `paid_amount` = '$paid_amt', `balance` = '$val',`paid_status` = '$bill_status' where transaction_id ='$transactionId_arr[$key]'";
                $res = mysqli_query($conn, $query_inv);

               // return 'Data Inserted';
            //}
          
            }
            
           // exit() ;


       }else{
                $billamount = $amount / 100;
                $paid = $amount / 100;
                $balance = (float)$billamount - (float)$paid;

                $query = "insert into razorpay_payment(`company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`,`paid`,`balance`,`razorpayOrderId`, `razorpayPaymentId`, `paymentStatus`, `created_at`, `updated_at`) values('$name','$email','$phone','$grn_no','$invoice_no','$client_id','$billamount','$paid','$balance','$razorOrderId','$razorpayPaymentId','$paymentStatus','$created_at','$updated_at')";
                $sql = mysqli_query($conn, $query);
                if ($sql) {

                    $tables = get_trans_table_name($conn, $grn_date);
                    if ($billamount == $balance) {
                        $bill_status = "0"; //Pending
                    } elseif ($billamount == $paid) {
                        $bill_status = "1"; //Paid
                    } else {
                        $bill_status = "2"; //Partially Paid
                    }

                    $query_inv = "update $tables[0] set `paid_amount` = '$paid', `balance` = '$balance',`paid_status` = '$bill_status' where transaction_id ='$transaction_id'";
                    $res = mysqli_query($conn, $query_inv);

                    return 'Data Inserted';
                }
       }

        
    }

    function updateOnlinePayment($conn, $email, $grn_date, $transaction_id, $razorOrderId, $razorpayPaymentId, $paymentStatus, $updated_at)
    {
        $query = "UPDATE `razorpay_payment` SET `razorpayPaymentId`='$razorpayPaymentId',`paymentStatus`='$paymentStatus',`updated_at`='$updated_at' WHERE email = '$email' and razorpayOrderId = '$razorOrderId'";
        $sql = mysqli_query($conn, $query);
        if ($sql) {

            $tabless = get_trans_table_name($conn, $grn_date);

            $get_m_y = explode("_", $tabless[0]);
            $month = $get_m_y[1];
            $year = $get_m_y[2];
            $get_status_query = "select `origin`,`destination`,`mode_of_consignment`,`transaction_id`,`grn_no`,`grn_date`,`consigner`,`consignee`,`status` from $tabless[0] where transaction_id='$transaction_id'";
            $result_query = mysqli_query($conn, $get_status_query);
            $get_result_query = mysqli_fetch_assoc($result_query);
            $get_status = $get_result_query['status'];
            $get_transaction_id = $get_result_query['transaction_id'];
            $consignor_name = $get_result_query['consigner'];
            $consignee_name = $get_result_query['consignee'];
            $origin_name = $get_result_query['origin'];
            $destination_name = $get_result_query['destination'];
            $grnn_no = $get_result_query['grn_no'];
            $grnn_date = $get_result_query['grn_date'];
            $mode_of_consignment = $get_result_query['mode_of_consignment'];

            if ($mode_of_consignment == '3') {
                $check_restricted = check_invoice_restricted($conn, $consignor_name);
                if ($check_restricted == 0) {
                    //echo "Not Restricted";
                  
                    $dir = '../../web/digital_invoice/';
                
                   
                    $pdf_file_name = $dir . $month . "_" . $year . "_" . $transaction_id . "invoice.pdf";
                    //echo "pdf_file_name: " . $pdf_file_name;

                    //Send Mail
                    $msg = '<p style="line-height: 24px; margin-bottom:15px;">
                    Thank You for Your Order On <a href = "http://localhost/graciousexpress" >Gracious Express</a> on ' . $grnn_date . '! <br>
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
                    <td >Status	</td><td >Consignment Booked Successfully.</td>		
                        </td>
                                        </tr>
                                    </table>	
                    <br>
                    <br>';
                    $to_name = array();
                    $to_email = array();

                    if (!empty(get_client_email($conn, $consignor_name))) {
                        //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
                        array_push($to_email, get_client_email($conn, $consignor_name));
                        array_push($to_name, get_client_name($conn, $consignor_name));

                        // $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $pdf_file_name, $image, $msg, $name);

                    
                    }
                } else {
                    echo "Restriced";
                }
            }
           
            return 'Data Updated';
        }
    }

    function removePendingPayment($conn, $grn_no, $invoice_no)
    {
        $query = "DELETE FROM `razorpay_payment` WHERE grn_no = '$grn_no' and invoice_no = '$invoice_no' and `paymentStatus`='PENDING' and razorpayPaymentId = '' ";

        $sql = mysqli_query($conn, $query);

        if ($sql) {
            return 'Data Deleted';
        }
    }

    function SetOutStandingInfo($conn, $client_id, $amount)
    {

        $client_outstanding_query = "SELECT * FROM `client_outstanding` where client_id = '$client_id' ";
        $client_outstanding_query_result = mysqli_query($conn, $client_outstanding_query);
        $outstanding_count = mysqli_num_rows($client_outstanding_query_result);
        // print_r($count);
        if ($outstanding_count > 0) {
            $result_datas = mysqli_fetch_assoc($client_outstanding_query_result);
            $c_id = $result_datas['client_id'];
            $total_amtt = $result_datas['total'];
            $amount_paid = $result_datas['amount_paid'];
            $balance = $result_datas['balance'];

            //$upadate_total = (float)$amount + (float)$total_amtt; //Add old amount with new
            $upadate_amount_paid = (float)$amount + (float)$amount_paid; //Add old amount with new
            $update_balance = (float)$total_amtt - (float)$upadate_amount_paid; //Update Balance Amount

            $update_outstanding = mysqli_query($conn, "UPDATE `client_outstanding` SET `total`='$total_amtt',`amount_paid`='$upadate_amount_paid',`balance`='$update_balance' WHERE client_id = '$c_id'");
        } else {
            $insert_outstanding = mysqli_query($conn, "INSERT INTO `client_outstanding`(`client_id`, `total`, `amount_paid`, `balance`) VALUES ('$client_id','$amount','0','$amount')");
        }
    }
}
