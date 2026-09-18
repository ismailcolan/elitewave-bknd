<?php
require_once('include/connect.php');
require_once('include/function.php');
require 'tcpdf/tcpdf.php';

 $month = $_GET['month'];
$year = $_GET['year'];
$transaction_id = $_GET['id'];

/*$month = "2";
$year = "2018";
$transaction_id = '1';*/
$query = "select * from  transaction_".$month."_".$year." where transaction_id='".$transaction_id."'";
		$result = mysqli_query($conn,$query);
		$row=mysqli_fetch_assoc($result);
		extract($row);

        if($booking_status == '1'){
            class MYPDF extends TCPDF {
                public function Header() {
                    $img_file = 'images/pdf/cancel2.png';
             
                    // Render the image
                    $this->Image($img_file, 60, 110, 90, 30, '', '', '', false, 300, '', false, false, 0);
               }
           
           }
        }else{
            class MYPDF extends TCPDF {
                public function Header() {
                    
               }
           
           }
        }

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);


$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Elite Wave 360');
$pdf->SetTitle('Report');
$pdf->SetSubject('Transaction');
$pdf->SetKeywords('Elite Wave 360');
//$Mistral = $pdf->addTTFfont('/fonts/MISTRAL.woff', ‘TrueTypeUnicode’, “, 32);

$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
// remove default footer
$pdf->setPrintFooter(false);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

 
$pdf->SetFont('times', '',11);
 
 
$pdf->AddPage();

//$pdf->Image('assets/pdf/header.png', '', '', 190, 16, '', '', '', false, 300, '', false, false, 1, false, false, false);

	$out_put='';
	
	 	$query = "select * from  transaction_".$month."_".$year." where transaction_id='".$transaction_id."'";
		$result = mysqli_query($conn,$query);
		$row=mysqli_fetch_assoc($result);
		extract($row);
		
		$i = 1;
	
$company_query = "select * from company where status=0";
$company_result = mysqli_query($conn,$company_query); 
$company_row = mysqli_fetch_array($company_result);

		$out_put .='<style> .border td {
        border: 1px solid #091c4d;
		}</style>
<div style="color: #091c4d;border-color:#091c4d;"  ><table cellpadding="5" class="border"  border="1"  class="border"   ><tr>
		<td colspan="5" style="text-align:center;">
		<img src="images/pdf/gracious_new.png" style="height:80px" width="620px"/>
		</td></tr>
		</table>';
		
		$out_put .='<table border="1"  class="border"  >
				<tr>
					<td style="text-align:center;width:20%;" > <div style="font-size:2pt">&nbsp;</div> <img src="images/pdf/loveearth-or-leave-earth.png" style="height:100px;"/></td>
					
					<td style="text-align:center;width:25%">
						<table  cellpadding="3">
							<tr><td style="background-color:#091c4d;color:#fff;;font-weight:bold;">Origin</td></tr>
							<tr><td style="font-weight:bold;">'.get_city_name($conn,$origin).'</td></tr>
							<tr><td style="background-color:#091c4d;color:#fff;;font-weight:bold;">Destination</td></tr>
							<tr><td style="font-weight:bold;">'.get_city_name($conn,$destination).'</td></tr>
						</table>
					</td>
					<td style="text-align:left;width:55%">
						<table border="1"  class="border"   cellpadding="5" >
							<tr>
								<td style="width:40%">GR.No.</td>
								<td style="width:60%"> '.$grn_no.'</td>
							</tr>
							<tr>
								<td  style="width:40%" >Booking Date</td>
								<td  style="width:60%">'.$grn_date.'</td>
							</tr>
							<tr>
								
								<td>GST No.</td>
								<td> '.$company_row['gst_no'].'</td>
							</tr>
							<tr>
								
								<td>PAN No.</td>
								<td>'.$company_row['pan_no'].'</td>
							</tr>
							<tr><td>Mode Of Transport</td>
							<td>'.get_mode($conn,$mode_of_transportation).'</td>
							</tr>
						</table>
					</td>
				</tr>
</table>'; 

$get_consinor_query =mysqli_query($conn,"select * from client where client_id='".$consigner."'");
$get_consigner_det = mysqli_fetch_assoc($get_consinor_query);

$get_consinee_query =mysqli_query($conn,"select * from client where client_id='".$consignee."'");
$get_consignee_det = mysqli_fetch_assoc($get_consinee_query);


$out_put .='<table border="1"  class="border"   cellpadding="3">
				<tr>
				<td style="height:80px"><table>
				<tr><td style="width:25%">Consignor:</td><td style="width:75%;"> '.get_client_name($conn,$consigner).' </td></tr>
				<tr><td style="height:40px;">Address</td><td style="font-size:11px;">'.$get_consigner_det['address1']." ".$get_consigner_det['address2'].",".get_city_name($conn,$get_consigner_det['city']).",<br>".$get_consigner_det['pincode'].",".get_statename($conn,$get_consigner_det['state']).'</td></tr>
				<tr><td>GST NO</td><td>'.$get_consigner_det['gst_no'].'</td></tr>
				<tr><td>Phone No:</td><td>'.$get_consigner_det['phone'].'</td></tr>				
				</table> </td>
				<td> <table>
				<tr><td style="width:25%">Consignee:</td><td style="width:75%;">'.get_client_name($conn,$consignee).'</td></tr>
				<tr><td style="height:40px;">Address</td><td style="font-size:11px;">'.$get_consignee_det['address1']." ".$get_consignee_det['address2'].",".get_city_name($conn,$get_consignee_det['city']).",<br>".$get_consignee_det['pincode'].",".get_statename($conn,$get_consignee_det['state']).'</td></tr>
				<tr><td>GST NO</td><td>'.$get_consignee_det['gst_no'].'</td></tr>
				<tr><td>Phone No:</td><td>'.$get_consignee_det['phone'].'</td></tr>				
				</table></td>
				</tr></table>';
				
				
$out_put .='<table border="1"  class="border"   cellpadding="3">
				<tr style="text-align:center;font-weight:bold;">
				<td>No Of Pkgs</td>
				<td>Type of Packing</td>
				<td>Party Invoice No</td>
				<td>Said to Contents</td>
				<td>Qty</td>
				<td>Gross Weight</td>
				<td>Charged Weight</td>
				</tr>';
		$i=1;
		$no_of_pkge1="";
		$type_of_pkge1="";
		$party_invoice_no1="";
		$said_contents1="";
		$qty1="";
		$gross_weight1="";
		$charged_weight1 ="";
		
		
		$query_inv = "select * from  transaction_invoice_".$month."_".$year." where transaction_id='".$transaction_id."'";
		$result_inv = mysqli_query($conn,$query_inv);
		while($row_inv=mysqli_fetch_assoc($result_inv))
		{
		extract($row_inv);		
				if($type_of_pkge !='Select Package Type'){
				    $type_of_pkge1 .=get_package_name($conn,$type_of_pkge)."<br/>";
				}
		$no_of_pkge1 .=$no_of_pkge."<br/>";
		
		$party_invoice_no1 .=$i.".".$party_invoice_no."<br/>";
		$said_contents1 .=$said_contents."<br/>";
		$qty1 .=$qty."<br/>";
		$gross_weight1 .=$gross_weight."<br/>";
		$charged_weight1 .=$charged_weight."<br/>";
		
				$i++;
		}	
		
		$out_put .='<tr>
		<td style="text-align:center;">'.$no_of_pkge1.'</td>
				<td  style="height:70px;text-align:center;">'.$type_of_pkge1.'</td>				
				
				<td style="text-align:left;">'.$party_invoice_no1.'</td>
				<td style="text-align:center;">'.$said_contents1.'</td>
				<td style="text-align:center;">'.$qty1.'</td>
				<td style="text-align:right;">'.$gross_weight1.'</td>
				<td style="text-align:right;">'.$charged_weight1.'</td>
				</tr>';
				
				
				$out_put .='</table>';

$out_put .='<table border="1"  class="border" >
				<tr>
				<td>
				<table border="1"  class="border"  cellspacing="0" cellpadding="3">
				<tr>
				<td>Declared Value</td>
				<td   style="text-align:left;">Rs. '.number_format($goods_dedared_value,2,".","").'</td>
				</tr>
                <tr>
				
				<td>Eway Number</td>
				<td>'.$eway_number.'</td>
				</tr>
				<tr>
				
				<td>Consignee</td>
				<td></td>
				</tr>
				<tr>
				<td>Consignor</td>
				<td></td>
				</tr>
				<tr>
				<td colspan="3" style="height:70px;text-align:center;font-weight:600">Dimensions <br/> `L`*`W`*`H` `IN CM`
				<br>'.$dimension1." * ".$dimension2." * ".$dimension3.'</td>
				</tr>
				<tr>
				<td colspan="3" style="height:105x;text-align:center;">
				<table><tr><td colspan="4"><span style="background-color:#091c4d;color:#fff;font-weight:bold;"> Delivery Details </span></td></tr>
				<tr><td style="font-size:11px">Delivery Details:</td><td></td><td style="font-size:11px">Time:</td><td></td></tr>
				<tr><td colspan="4" style="height:50px"></td></tr>
				<tr><td colspan="4" style="font-size:11px">Receiver`s Signature </td></tr>
				</table>
				</td>
				</tr>
				</table></td>
				<td>
				<table border="1"  class="border"  cellpadding="5">
				<tr style="font-weight:bold;">
				<td style="width:40%;text-align:center">Particulars</td>
				<td style="width:20%;text-align:center">Rate</td>
				<td style="width:40%;text-align:center;" colspan="2"> Amount</td>
				</tr>';
				$frieght_amount=explode(".",$frieght_amount);
				$loading_unloading_amount=explode(".",$loading_unloading_amount);
				$crane_fork_lift_amount=explode(".",$crane_fork_lift_amount);
				$cod_amount=explode(".",$cod_amount);
				$fov_amount=explode(".",$fov_amount);
				$doc_amount=explode(".",$doc_amount);
				$cartage_amount=explode(".",$cartage_amount);
				$labour_handling_amount=explode(".",$labour_handling_amount);
				$other_charge_amount=explode(".",$other_charge_amount);
				$gst_amount=explode(".",$gst_amount);
				$out_put .='<tr>
				<td>Freight</td>
				<td style="text-align:center;">'.$frieght_rate.'</td>
				<td style="width:25%;text-align:right;">'.$frieght_amount[0].'</td>
				<td style="width:15%">'.$frieght_amount[1].'</td>
				</tr>
				<tr>
				<td>Loading / Unloading Chrgs:</td>
				<td style="text-align:center;">'.$loading_unloading_rate.'</td>
				<td style="text-align:right">'.$loading_unloading_amount[0].'</td>
				<td>'.$loading_unloading_amount[1].'</td>
				</tr>
				<tr>
				<td>Crane / Fork Lift Chrgs:</td>
				<td style="text-align:center;">'.$crane_fork_lift_rate.'</td>
				<td style="text-align:right">'.$crane_fork_lift_amount[0].'</td>
				<td>'.$crane_fork_lift_amount[1].'</td>
				</tr>
				<tr>
				<td>F.O.V</td>
				<td style="text-align:center;">'.$fov_rate.'</td>
				<td style="text-align:right">'.$fov_amount[0].'</td>
				<td>'.$fov_amount[1].'</td>
				</tr>
				<tr>
				<td>Doc.Charges</td>
				<td style="text-align:center;">'.$doc_charges.'</td>
				<td style="text-align:right">'.$doc_amount[0].'</td>
				<td>'.$doc_amount[1].'</td>
				</tr>
				<tr>
				<td>Labour Charges</td>
				<td style="text-align:center;">'.$labour_handling_rate.'</td>
				<td style="text-align:right">'.$labour_handling_amount[0].'</td>
				<td>'.$labour_handling_amount[1].'</td>
				</tr>
				<tr>
				<td>Any Other Charges</td>
				<td style="text-align:center;">'.$other_charge_rate.'</td>
				<td style="text-align:right">'.$other_charge_amount[0].'</td>
				<td>'.$other_charge_amount[1].'</td>
				</tr>
				<tr>
				<td>GST (as Applicable)</td>
				<td style="text-align:center;">'.$gst_rate.'</td>
				<td style="text-align:right">'.$gst_amount[0].'</td>
				<td>'.$gst_amount[1].'</td>
				</tr>
				</table>
				</td>
				</tr></table>';
$out_put .='<table border="1"  class="border"   cellpadding="3">
				<tr>
				<td style="width:70%;height:40px;font-size:11px;">Note: (1) Please pay by Cheque or DD only In Favor of M/s Elite Wave 360 <br/> (2) GST will be payable by Consignor/Consignee, Who ever pay the Freight as for GST rules</td>
				<td style="width:10%">Total</td>
				<td style="width:12.5%;text-align:right">'.$total.'</td>
				<td style="width:7.5%">00</td>
				</tr></table>';
				
	$out_put .='<table border="1"  class="border"   cellpadding="3">
				<tr>
				<td rowspan="2"  style="height:40px;width:20%;text-align:center;">Mode Of Payment <br><br><br>'.consignment_mode($conn,$mode_of_consignment).'</td>
				<td  style="width:40%;" rowspan="2">Consignor Signature <br>
				<span style="text-align:center;"><img src="'.$consigner_signature.'"  /><br>Not Negotiable</span>
				
				</td>
				<td style="width:40%;"><span style="font-weight:bold;">Rs. In Word: </span>'.$total_words.'</td>
				</tr>
				<tr>
				<td rowspan="2" style="height:70px;text-align:  center;"><table>
				<tr><td> <img src="images/pdf/signature.png" style="height:25px;"/> </td></tr>
				
				<tr><td style="text-align:right;">Signature</td></tr>
				<tr><td style="background-color:#091c4d;color:#fff;font-weight:bold;"> ACCOUNTS COPY </td></tr>
				</table></td>
				</tr>
				</table></div>';
				
 $out_put;				
				
$pdf->writeHTML($out_put, true, false, true, false, '');


$pdf->setPrintHeader(false);

$pdf->Output('example_051.pdf', 'I'); 

?>