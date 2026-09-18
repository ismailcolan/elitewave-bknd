<?php
require_once('include/connect.php');
require_once('include/function.php');

$month = $_GET['month'];
$year = $_GET['year'];
$transaction_id = $_GET['id'];
$unique_invoice_no = $_GET['invoice_no'];
$query = "select * from  transaction_".$month."_".$year." where transaction_id='".$transaction_id."'";
		$result = mysqli_query($conn,$query);
		$row=mysqli_fetch_assoc($result);
		extract($row);
   
$invoice_date = $grn_date;
if($unique_invoice_no != ''){
  
  $unique_invoice_no = $_GET['invoice_no'];
}else{
  
  $unique_invoice_no = $invoice_no;

}


$check_gst_or_gta  = substr($unique_invoice_no, 2, 3);

if($check_gst_or_gta == 'GST'){
      $sac = "996812";
			$sac_text = '996812 - COURIER SERVICES';
}else{
      $sac = "9965";
			$sac_text = '9965 - Good Transport Agency Service';
}

$gracious_gst = '06AUIPM7033M4Z7';

$address3 = 'Port Blair,Victorious Victor 
             Victor Antiu,
             Portuan Street Building B, Lot 7, Port Blair, AN,Lot 7, Port Blair, AN Port Blair,Victorious Victor';

//$year = '2022';
require_once ('tcpdf/tcpdf.php');
if($booking_status == 1){
class MYPDF extends TCPDF {
 
  public function Header() {

       // Define the path to the image that you want to use as watermark.
       
        $img_file = 'images/pdf/cancel2.png';

       // Render the image
       $this->Image($img_file, 280, 200, 250, 90, '', '', '', false, 300, '', false, false, 0);
   }

}
}else{
  class MYPDF extends TCPDF {
 
    public function Header() {
  
         // Define the path to the image that you want to use as watermark.  
     }
  
  }
}
$fontname = TCPDF_FONTS::addTTFfont('./fonts/CopyofMistral2.ttf', 'TrueTypeUnicode', '', 96);
$obj_pdf = new MYPDF('L', 'pt', ['format' => [$width, $height], 'Rotate' =>360]);

$obj_pdf->SetCreator(PDF_CREATOR);  
$obj_pdf->SetAuthor("Elite Wave 360");  
$obj_pdf->SetTitle("Elite Wave 360 Invoice");  
$obj_pdf->SetSubject('Invoice');
$obj_pdf->SetKeywords('Elite Wave 360');

$obj_pdf->SetHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);  
$obj_pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));  
$obj_pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));  
//$obj_pdf->SetDefaultMonospacedFont('helvetica');  
$obj_pdf->SetFooterMargin(PDF_MARGIN_FOOTER);  
$obj_pdf->SetMargins(PDF_MARGIN_LEFT, '2', PDF_MARGIN_RIGHT);  
// $obj_pdf->setPrintHeader(false);  
$obj_pdf->setPrintFooter(false);  
$obj_pdf->SetAutoPageBreak(TRUE, 10);  
$obj_pdf->SetFont('helvetica', '', 12);  
$obj_pdf->AddPage();  
$content = '';  
   // $conn = mysqli_connect("localhost","root","","graciousexpress");

		  $address1 = get_client_name($conn,$consignee);
      $consignor = get_client_name($conn,$consigner);

      // echo $grn_no;
      // echo $grn_date;
      // $get_consinee_query =mysqli_query($conn,"select * from client where client_id='".$consignee."'");
      // $get_consignee_det = mysqli_fetch_assoc($get_consinee_query);

      $address2 = $row['con_address1'];
      $word_wrap = wordwrap($address2, 55,"<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n");
      
      $state = get_statename($conn,$row['con_state']);
      $pincode = $row['con_pincode'];
      $consignee_gst = $row['con_gst_no'];
      $consignment_weight = $row['consignment_weight'];
      $mode_of_transport = get_mode($conn,$mode_of_transportation);
      $mode_of_consignment = $row['mode_of_consignment'];
      if($mode_of_consignment == '2' || $mode_of_consignment == '3'){
        $consignor_consignee_gst = $row['gst_no'];
      }else{
        $consignor_consignee_gst = $row['con_gst_no'];

      } 
      if($mode_of_transportation == '1' || $mode_of_transportation == '2' || $mode_of_transportation == '3'){
        $gst_label = '18%';
      }else{
        $gst_label = '12%';
      } 

      //$gracious_gst = '07AUIPM7033M1Z8';

     
      $consignee_gst_get_two_ltrs = substr($consignor_consignee_gst, 0, 2);
      $gracious_gst_get_two_ltrs = substr($gracious_gst, 0, 2);



      $mode_of_station = get_city_name($conn,$origin);

      $total_value = $total;
      
      $address = $address1.' '.$address2.' '.$consignee_gst;
      $address_and_gst =$address1.'<br>'.$address2.' <br>  '.$consignee_gst;



      //Get Client Information
      $get_client_info = get_client_info($conn,$consignee);
      $client_email = $get_client_info['email'];
      $client_contact_person = $get_client_info['contact_person'];
      $client_contact_no = $get_client_info['contact_no'];

      $content .= '  

      
      <style>
td.dd {
    word-break: break-all;
}
tbody {
    border: hidden;
}
.ssh{
  border-left-style:none;
}


</style>
     <table width="812" border="1" cellspacing="0" cellpadding="0" font-family="sans-serif;" align="center" >
         <tr>
          <td>
               <table width="812" border="0" cellspacing="0" cellpadding="0" style="border-top:none;">
             <tr>
                  <td style=" font-family:sans-serif; font-size:10px; text-align:center;font-weight: 200px; line-height:20px;" >TAX INVOICE</td><br/><br>
             </tr>
        

              
               <tr>
                  <td style="font-family: '.$fontname.';font-size:20px;text-align:center;font-style:normal;font-weight:200px;line-height: 12px;">Elite Wave 360</td>
               </tr>
               <tr>
                <td style="font-family:sans-serif; font-size:8px; text-align:center;font-style:normal;font-weight:200px;">Basement and Ground Floor, Plot No.68, Pace City-1, Sector-37, Gurgaon-122001 (Haryana)</td>
              </tr>
            
              <tr>
                <td style="font-family:sans-serif; font-size:8px; text-align:center;font-style:normal;font-weight:200px;">Email : accounts@graciousexpress.com</td>
              </tr>
                </table>
                </td>
                </tr>

                <tr>
                <td>
                 <table width="812"  border="0" cellspacing="0" cellpadding="0" style="border-top:none;">
                   <tr>
                   <td style="width:70px;line-height:14px;font-family:sans-serif;font-size: 8px;font-weight: bold;">&nbsp;GSTIN/UIN</td>
                   <td style="width:10px ;line-height:14px;">:</td>
                   <td style="width:100px;line-height:14px;font-family:sans-serif;font-size: 8px;font-weight: bold;">'.$gracious_gst.'</td>
                   <td style="width:490px;line-height:14px;font-family:sans-serif;font-size: 8px;text-align: right;font-weight: bold;position: relative; left: -13px;">INVOICE.NO</td>
                   <td style="width:25px;line-height:14px;text-align: right;">&nbsp;&nbsp;:</td>
                   <td style="width:110px;line-height:14px;font-family:sans-serif;font-size: 8px;font-weight: bold;">'.$unique_invoice_no.'</td>
                    </tr>
                    <tr>
                     <td style="width:70px;line-height:14px;font-size: 8px;font-weight: bold;font-family:sans-serif;">&nbsp;SAC CODE</td>
                     <td style="line-height:14px;">:</td>
                     <td style="text-align:left;line-height:14px;font-size: 8px;font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$sac.'</td>
                     <td style="width:500px;line-height:14px;font-family:sans-serif;font-size: 8px;text-align: right;font-weight: bold;">INVOICE DATE</td>
                     <td style="width:15px;line-height:14px;text-align: right;">:</td>
                     <td style="width:77px;line-height:14px;font-family:sans-serif;font-size: 8px;font-weight: bold;">'.$invoice_date.'</td>
                      </tr>
                  </table>
                </td>
              </tr>
  
              <tr>
               <td>
               <table width="812" border="0" cellspacing="0" cellpadding="0" style="border-top:none;">
                    <tr>
                      <td style="text-align:left;line-height:12px;font-family:sans-serif;font-size: 10px;font-weight:200;"><b>Bill To :</b> '.$address1.'</td>
                     <td style="width:610px;"><small>Email:'.$client_email.'</small></td>
                      </tr>
                      <tr>
                          <td style="text-align:left;line-height:11px;font-family:sans-serif;font-size: 10px;font-weight:200;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$word_wrap.'</td>
                          <td style="width:601px;"><small>Contact Person: '.$client_contact_person.'</small></td>
                          
                          </tr>
          
               
                 <tr>
                   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<td style="text-align:left;width:110px;line-height:10px;font-family:sans-serif;font-size: 10px;font-weight:200;">State : '.$state.'</td>
                   <td style="width:100px; line-height:10px;font-family:sans-serif;font-size: 10px;font-weight:200;">Code : '.$pincode.'</td>
                   
                      <td style="width:180px;line-height:15px;font-family:sans-serif;font-size: 10px;font-weight:200;">GSTIN/UIN : '.$consignee_gst.'</td>
                      <td style="width:544px;" ><small>Contact No: '.$client_contact_no.'</small></td>
                   </tr>
                </table>
               </td>
            </tr>
<tr>
<td>
<table border="1">
<tr>
          <td  class="ss ssh" style="width:20px;text-align: center;font-size: 8px;padding: 3px;">Sno</td>
          <td  class="" style="width:60px;text-align: center;font-size: 8px;padding: 3px;">Date</td>
          <td  class="" style="width:60px;text-align: center;font-size: 8px;padding: 3px;">GR No</td>
          <td  class="" style="width:50px;text-align: center;font-size: 8px;padding: 3px;">Qty</td>
          <td  class="" style="width:50px;text-align: center;font-size: 8px;padding: 3px;">Weight</td>
          <td  class="" style="width:30px;text-align:center;font-size: 8px;padding: 3px;">Rate</td>
          <td  class="" style="width:132px;text-align:center;font-size: 8px;padding: 3px;">Consignor / Consignee</td>
          <td  class="" style="width:205px;text-align: center;font-size: 8px;padding: 3px;">Ship To</td>
          <td  class=""style="width:50px;text-align: center;font-size: 8px;padding: 3px;">Station</td>
          <td  class="" style="width:50px;text-align: center;font-size: 8px;padding: 3px;">T/A</td>
          <td  class="" style="width:43px;text-align: center;font-size: 8px;padding: 3px;">Inv.No.</td>
          <td   class=""style="width:62px;text-align: center;font-size: 8px;padding: 3px;">Freight</td>
</tr>';
//  $grn_date="";
//  $grn_no="";
//  $qty="";
//  $weight="";
//  $rate="";
//  //$consignor="";
//  $shipping_address ="";
//  $station ="";
//  $ta ="";
//  $inv_no ="";
//  $fright ="";
//  $dc ="";
// $total ="";


//  $query_inv = "select * from  transaction_invoice_".$month."_".$year." where transaction_id='".$transaction_id."'";
//  $result_inv = mysqli_query($conn,$query_inv);
    //$conn = mysqli_connect("localhost","root","","graciousexpress");
    $query = "select * from  transaction_invoice_".$month."_".$year." where transaction_id='".$transaction_id."'";
    $result = mysqli_query($conn,$query);
  // $row=mysqli_fetch_assoc($result);
  // extract($row); 

  $x=1;
  $sum_qty = 0;
  $sum_weight = 0;
  $sum_total = 0;
  $count = mysqli_num_rows($result);
  if($count > 2){
    $height = '30px';
  }else{
    $height = '60px';
  }
  $count1 = count($type_of_pkge);


  while($row=mysqli_fetch_assoc($result)){
  extract($row);
  $count1 = count($type_of_pkge);
  


  //var_dump($row);
 //$qty =  $grn_date="";
 
if($type_of_pkge != 'Select Package Type'){


  
  // if($count1 > 2){
  //   $height = '0px';
  // }else{
  //   $height = '60px';
  // }

   $qty = $qty;
 
 if($consignment_weight > $charged_weight ){
  $fr = $consignment_weight/$count;
  $qty = $qty;
 }else{
 $fr =  $charged_weight ;
 $qty ;
 }

// $fr =1;
// $qty;

//  $rate;
// $origin;
// $destination;

 $city ="";

 $inv_no =$row['party_invoice_no'];

 $dc ="";
//  $total ="";

 $content .=' <tr style="text-align:center">
 <td class="dd" style="width:20px;text-align: center;font-size: 8px;padding: 3px;">'.$x.'</td>
 <td class="dd" style="width:60px;text-align: center;font-size: 8px;padding: 3px;">'.$grn_date.'</td>
 <td class="dd" style="width:60px;text-align: center;font-size: 8px;padding: 3px;">'.$grn_no.'</td>
 <td class="dd"style="width:50px;text-align: center;font-size: 8px;padding: 3px;">'.$qty.'</td>
 <td class="dd"style="width:50px;text-align: center;font-size: 8px;padding: 3px;">'. round($fr,1).'</td>
 <td class="dd"style="width:30px;text-align: center;font-size: 8px;padding: 3px;">'.$frieght_rate.'</td>
 <td class="dd"style="width:132px;text-align: center;font-size: 8px;padding: 3px;">'.$consignor.'</td>
 <td class="dd" style="width:205px;text-align: center;font-size: 8px;padding: 3px;">'.$address_and_gst.'</td>
 <td class="dd"style="width:50px;text-align: center;font-size: 8px;padding: 3px;">'. $mode_of_station .'</td>
 <td class="dd" style="width:50px;text-align: center;font-size: 8px;padding: 3px;">'.$mode_of_transport.'</td>
 <td class="dd" style="width:43px;text-align: center;font-size: 8px;padding: 3px;">'.$inv_no.'</td>
 <td class="dd" style="width:62px;text-align: center;font-size: 8px;padding: 3px;">'.$fr * $frieght_rate.'.00</td>
 </tr>
 

 ';
 
  $x++;
  
  $sum_qty += $qty;

  $sum_weight += $fr;

  $sum_total += $fr * $frieght_rate;
 }
 
}









 $loading_unloading_amount;
 $crane_fork_lift_amount;
 $fov_amount;
 $doc_amount;
 $other_charge_amount;
 $gst_amount;

 $cod_amount;
 $cartage_amount;
 $labour_handling_amount;
 $octroi_amount;
//  $loading_charges= 1;
//  $crane_charges = 3;
//  $dc_charges = 10;
//  $fov_charges = 1;
//  $other_chrgs = 10;

  $grand_total = $sum_total + $loading_unloading_amount + $crane_fork_lift_amount +  $doc_amount + $fov_amount + $other_charge_amount + $labour_handling_amount + $cod_amount + $cartage_amount + $octroi_amount + $gst_amount ;

  $round_off = $total - $grand_total;

  $totl = $round_off + $grand_total;

 
 //print_r($round);
 
 $content 
 .='

 <tr>
 <td class="dd" style="width:20px;height:'.$height.'; text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd" style="width:60px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd" style="width:60px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd"style="width:50px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd"style="width:50px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd"style="width:30px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd"style="width:132px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd" style="width:205px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd"style="width:50px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd" style="width:50px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd" style="width:43px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd" style="width:62px;text-align: center;font-size: 8px;padding: 3px;"></td>
 </tr>';

 $content .='<tr>
 <td class="dd" style="width:20px; text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd" style="width:60px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd" style="width:60px;text-align: center;font-size: 8px;padding: 3px;">Gross Total</td>
 <td class="dd"style="width:50px;text-align: center;font-size: 8px;padding: 3px;">'.$sum_qty.'</td>
 <td class="dd"style="width:50px;text-align: center;font-size: 8px;padding: 3px;">'.$sum_weight.'</td>
 <td class="dd"style="width:30px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd"style="width:132px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd" style="width:205px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd"style="width:50px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd" style="width:50px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd" style="width:43px;text-align: center;font-size: 8px;padding: 3px;"></td>
 <td class="dd" style="width:62px;text-align: center;font-size: 8px;padding: 3px;">'.$sum_total.'.00</td>
 </tr>
</table>
</td>
</tr>
  
        <tr>
        <td><table class="table-wrapperw" style="width:800px;" border="0" cellspacing="0"  cellpadding="0" >
         <tr>
         <td><table class="table-wrapper" style="width:800px;border-collapse:collapse;font-family:sans-serif; border-top: hidden; border-bottom: hidden;" width="800" border="1" cellspacing="0"  cellpadding="0">
        <tr>
             <td class="dd" style="width:402px;text-align: left;font-size: 9px;border-right: 1px solid black;"> Remarks : '.$unique_invoice_no.'</td>
             <td class="dd" style="width:205px;text-align: center;font-size: 9px;">Gross Total</td>
             <td class="dd" style="width:205px;text-align: center;font-size: 9px;">'.$sum_total.'.00</td>
        </tr>';
        
        if($doc_amount != ''){
            $content .= '<tr>
            <td class="dd" style="width:402px;text-align: left;font-size: 9px;padding:4px;border-right: 1px solid black;"> </td>
            <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">D.C</td>
            <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">'.$doc_amount.'</td>
          </tr>';
        }
        
        if($loading_unloading_amount != ''){
          $content .= '<tr>
          <td class="dd" style="width:402px;text-align: left;font-size: 9px;padding:4px;border-right: 1px solid black;"> </td>
          <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">Loading / Unloading Chrgs</td>
          <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">'.$loading_unloading_amount.'</td>
          </tr>';
      }
      if($crane_fork_lift_amount != ''){
        $content .= '<tr>
        <td class="dd" style="width:402px;text-align: left;font-size: 9px;padding:4px;border-right: 1px solid black;"> </td>
        <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">Crane / Fork Lift Chrgs</td>
        <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">'.$crane_fork_lift_amount.'</td>
        </tr>';
    }
    if($fov_amount != ''){
      $content .= '<tr>
      <td class="dd" style="width:402px;text-align: left;font-size: 9px;padding:4px;border-right: 1px solid black;"> </td>
      <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">Fov Chrgs</td>
      <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">'.$fov_amount.'</td>
      </tr>';
  }
  if($cod_amount != ''){
    $content .= '<tr>
    <td class="dd" style="width:402px;text-align: left;font-size: 9px;padding:4px;border-right: 1px solid black;"> </td>
    <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">C.O.D Chrgs</td>
    <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">'.$cod_amount.'</td>
    </tr>';
}
if($cartage_amount != ''){
  $content .= '<tr>
  <td class="dd" style="width:402px;text-align: left;font-size: 9px;padding:4px;border-right: 1px solid black;"> </td>
  <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">Cartage Chrgs</td>
  <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">'.$cartage_amount.'</td>
  </tr>';
}
if($labour_handling_amount != ''){
  $content .= '<tr>
  <td class="dd" style="width:402px;text-align: left;font-size: 9px;padding:4px;border-right: 1px solid black;"> </td>
  <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">Labour Handling </td>
  <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">'.$labour_handling_amount.'</td>
  </tr>';
}
if($octroi_amount != ''){
  $content .= '<tr>
  <td class="dd" style="width:402px;text-align: left;font-size: 9px;padding:4px;border-right: 1px solid black;"> </td>
  <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">Octroi Chrgs </td>
  <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">'.$octroi_amount.'</td>
  </tr>';
}
  if($other_charge_amount != ''){
    $content .= '<tr>
    <td class="dd" style="width:402px;text-align: left;font-size: 9px;padding:4px;border-right: 1px solid black;"> </td>
    <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">Other Chrgs</td>
    <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">'.$other_charge_amount.'</td>
    </tr>';
}
if($consignee_gst_get_two_ltrs == $gracious_gst_get_two_ltrs){
  $gst_label = $gst_label / 2 .'%';
  $content .= '
  <tr>
  <td class="dd" style="width:402px;text-align: left;font-size: 9px;padding:4px;border-right: 1px solid black;"> </td>
  <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">CGST '.$gst_label.'</td>
  <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">'.round($gst_amount/2,2).'</td>
  </tr>
  <tr>
  <td class="dd" style="width:402px;text-align: left;font-size: 9px;padding:4px;border-right: 1px solid black;"> </td>
  <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">SGST '.$gst_label.'</td>
  <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">'.round($gst_amount/2,2).'</td>
  </tr>'; 
}else{
  $content .= '
  <tr>
  <td class="dd" style="width:402px;text-align: left;font-size: 9px;padding:4px;border-right: 1px solid black;"> </td>
  <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">IGST '.$gst_label.'</td>
  <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">'.round($gst_amount,2).'.00</td>
  </tr>'; 
}
        if($round_off != 0){
          $content .='
        <tr>
        <td class="dd" style="width:402px;text-align: left;font-size: 9px;padding:4px;border-right: 1px solid black;"> </td>
        <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">Round Off</td>
        <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;">'.round($round_off,3).'</td>
        </tr>';
        }
        
       $content .=  '<tr>
        <td class="dd" style="width:402px;text-align: left;font-size: 10px;padding:4px;border-right: 1px solid black;"></td>
       <td class="dd" style="width:205px;text-align: center;font-size: 10px;padding:4px;">Grand Total </td>
       <td class="dd" style="width:205px;text-align: center;font-size: 9px;padding:4px;"><b>'.round($totl,2).'.00</b></td>

   </tr>
       </table></td>
       </tr>
      </table>
    </td>
     </tr>

     <tr>
      <td><table  style="width:100px;border-collapse:collapse; font-family:sans-serif;" width="200" border="0" cellspacing="0"  cellpadding="0">
        <tr>
        <td class="dd" style="text-align:left;width:auto;font-size: 10px;padding:4px;">Amount (In words) :'.$total_words.'</td>
        </tr>

     </table>
   </td>
    </tr>

    <tr>
    <td><table  style="width:800px;font-family:sans-serif;line-height:3.2px;" width="800" border="0" cellspacing="0"  cellpadding="0">
    <tr>
    <td  style="text-align: center;font-size:8px;padding:5px;text-align: left;font-weight: bold;line-height:10px;"> SAC CODE : '.$sac_text.'</td>
    </tr>
    <tr>
    <td  style="text-align: left;font-size: 8px;padding:5px;line-height: 10px;"> * Pan No. AUIPM7033M</td>
    </tr>
    <tr>
    <td  style="line-height: 10px;font-size: 8px;padding:5px;text-align: left;"> * GST will be charged as applicable.</td>
     </tr>
     <tr>
     <td  style="line-height: 10px;font-size: 8px;padding:5px;text-align: left;"> * Please pay by Cheque/DD/RTGS only in favour of Elite Wave 360.</td>
    </tr>
    <tr>
    <td  style="line-height: 10px;font-size: 8px;padding:5px;text-align: left;"> * Bank Details: KOTAK MAHINDRA BANK, CURRENT A/C NO. : 1811540631, IFSC CODE : KKBK0004596</td>
     </tr>
     <tr>
     <td  style="line-height: 10px;font-size: 8px;padding:5px;text-align: left;"> * Bank Details: HDFC BANK, CURRENT A/C NO. : 50200010347371, IFSC CODE: HDFC0001560</td>
      </tr>
      <tr>
     <td  style="line-height: 10px;font-size:8px;padding:5px;text-align: left;"> * All disputed shall be settled at Haryana jurisdiction only.</td>
     </tr>
     <tr>
     <td  style="line-height: 10px;font-size: 8px;padding:5px;text-align: left;"> * Please pay only Cheque/DD/NEFT/RTGS & we will not accept any cash.</td>
     </tr>
     <tr>
     <td  style="line-height: 10px;font-size: 8px;padding:5px;text-align: left;"> * Interest @ 18% per annum will be charged if the bill is not paid within 10 days.</td>
     </tr>
     <tr>
     <td  style="line-height: 10px;font-size: 8px;padding:5px;text-align: left;"> * Complaint of any nature shall be given to us in writing within 10 days from the date of booking and no complaints will be entertained thereafter.<span class="element" style="font-size: 8px;padding:5px;text-align: left;font-size:12px;text-align: right;float: right;border-top: 1px solid #4a4a4a;font-family: sans-serif;font-weight: 500;line-height: 8.3px; margin-right:30px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; For <span style="font-family: '.$fontname.';font-size:18px;">Elite Wave 360</span> </span></td>
    </tr>
    <tr>
    <td  style="line-height: 10px;font-size: 8px;padding:5px;text-align: left;"> * Volumetric dimension Railway formula in Inches (L x W x H) divided by 1728 x 6 times.<span style="width:160px;text-align: center;font-size: 8px;padding:px;text-align: left;font-size:10px;text-align: end;float: left;font-family: sans-serif;font-weight: 200;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;( Authorised Signatory )</span></td>
    </tr>
    <tr>
    <td  style="line-height: 10px;font-size: 8px;padding:5px;text-align: left;"> * Volumetric dimension Railway formula in Cms (L x W x H) divided by 28000 x 6 times.<span style="width:160px;text-align: center;font-size: 8px;padding:px;text-align: left;font-size:10px;text-align: end;float: left;font-family: sans-serif;font-weight: 200;"></span></td>
    </tr>
    <tr>
    <td  style="line-height: 10px;font-size:8px;padding:5px;text-align: left;"> * Volumetric dimension Airlines formula in Cms (L x W x H) divided by 5000.</td>
     </tr>
     <tr>
    <td  style="line-height: 10px;font-size: 8px;padding:5px;text-align: left;"> * For billing queries any Contact : For North +91 9625935007, For South- +91 9791095196 & +91 9381255529</td>
    </tr>
    <tr>
    <td  style="line-height: 10px;font-size: 8px;padding:5px;text-align: left;"> * Subject to exclusive jurisdiction of Haryana Courts only.</td>
    </tr>
    </table>
    </td>
    </tr>
  </table>';

  ob_end_clean();  
  $obj_pdf->writeHTML($content);  
  $obj_pdf->Output('sample_new.pdf', 'I');
