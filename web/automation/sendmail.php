<?php
error_reporting(0); 
ini_set('display_errors', 1);
include("../../config.ini.php");
include("/home/staging/public_html/web/include/function.php");
include ('/home/staging/public_html/web/automation/appMail.php');
// $conn = mysqli_connect("localhost","staging","vySzrpsqDRupDHS","staging");
//Send Mail
$grn_no = "ABCD0001";
$grn_date = "11-02-2022";

$consignor = '4262';
$consignee = '4220';

$origin = '98';
$destination = '78';
// $path = "transaction_pdf/".$month."_".$year."_".$transaction_id."transaction.pdf";
// $download_path =  $directory.$invoice_file_name.'.pdf';
$attachment1 = '1_2022_70invoice.pdf';
$attachment2 = '1_2022_69transaction.pdf';
$attachment3 = '/home/staging/public_html/web/digital_invoice/3_2022_2invoice.pdf';

// $multi_attach = array($attachment3,$attachment2,$attachment1);
$multi_attach = array();

// print_r($multi_attach);

// exit();
// foreach($multi_attach as $single_attach){
//     echo $single_attach;
// }
// exit();



 // Please Find Your Invoice Attached (in PDF Format) to this email.
$msg='<p style="line-height: 24px; margin-bottom:15px;">
							  
				
Thank You for Your Order On <a href = "http://localhost/graciousexpress" >Elite Wave 360</a> on '.$grn_date.'! <br>
Following Your Successful Consignment Delivery, 				
<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
<tr>
<td >GRN No	</td><td >'.$grn_no.'</td>
</tr><tr>	
<td >GRN Date:	</td><td >	'.$grn_date.'	</td>	
</tr>
<tr><td >Booked By	</td><td >'.get_client_name($conn,$consignor).' , '.get_city_name($conn,$origin).'</td>	</tr>	
<tr><td >Booked to	</td><td >	'.get_client_name($conn,$consignee).' , '.get_city_name($conn,$destination).'</td>	</tr>	
<tr>		
<td >Status	</td><td >Consignment Out For Delivery</td>		
    </td>
                    </tr>
                </table>	
<br>
<br>';
$to_name = array();
$to_email = array();

if(!empty(get_client_email($conn,$consignor)) && !empty(get_client_email($conn,$consignee))){
    //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
    array_push($to_email,get_client_email($conn,$consignor),get_client_email($conn,$consignee));
    array_push($to_name,get_client_name($conn,$consignor),get_client_name($conn,$consignee));
    
    // $mail = sendAttachments($to_name,$to_email, 'Consignment Invoice Notification',$multi_attach,$image ,$msg,$name); 
    //$mail = sendAttachments($to_name,$to_email, 'Consignment Booking Notification',$path,$image ,$msg,$name); 
        
//echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst'); 
        
}
            

//End
?>