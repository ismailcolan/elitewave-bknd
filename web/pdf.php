<?php
require_once('include/connect.php');
require_once('include/function.php');
require 'tcpdf/tcpdf.php';

// $month = $_GET['month'];
// $year = $_GET['year'];
$transaction_id = $_GET['id'];

/*$month = "2";
$year = "2018";
$transaction_id = '1';*/


class MYPDF extends TCPDF {
	 public function Header() {
       
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
        $conn = mysqli_connect('localhost','root','','bookconsignment'); 
	 	$query = "select * from consignment where id='".$transaction_id."'";
		$result = mysqli_query($conn,$query);
		$row=mysqli_fetch_assoc($result);
		extract($row);
        $grn_no = $row['booking_id'];
        $grn_date = $row['booking_date'];
        $consignor = $row['consignor_name'];
        $consignor_address = $row['consignor_address'];
        $consignor_contact = $row['consignor_contact'];

        $consignee = $row['consignee_name'];
        $consignee_address = $row['consignee_address'];
        $consignee_contact = $row['consignee_contact'];

        $consignor_city = $row['consignor_city'];
        $consignee_city = $row['consignee_city'];
        $mode_of_transportation = $row['shipping_mode'];
        $mode_of_pay = $row['pay_mode'];
        $gst = 'DUMMYGST1234';
        $pan = 'DUMMYPAN1234';
        $pincode = '636363';
        $state = 'Tamilnadu';
        $no_of_packages = $row['no_of_package'];
        $Gross_charged = $row['Gross_charged'];
        $W_charged = $row['W_charged'];
        $kgs = $row['kgs'];
        $goods_dedared_value = $row['goods_dedared_value'];
        $length = $row['length'];
        $width = $row['width'];
        $height = $row['height'];
        $amount_in_words = $row['total_words'];
        $frieght_rate = $row['frieght_rate'];
        $frieght_amount = $row['frieght_amount'];
        $cod_rate = $row['cod_rate'];
        $cod_amount = $row['cod_amount'];
        $fov_rate = $row['fov_rate'];
        $fov_amount = $row['fov_amount'];
        $doc_rate = $row['doc_charges'];
        $doc_amount = $row['doc_amount'];
        $cartage_rate = $row['cartage_rate'];
        $cartage_amount = $row['cartage_amount'];
        $labour_rate = $row['labour_handling_rate'];
        $labour_amount = $row['labour_handling_amount'];
        $other_rate = $row['other_charge_rate'];
        $other_amount = $row['other_charge_amount'];
        $gst_rate = $row['gst_rate'];
        $gst_amount = $row['gst_amount'];
        $total = $row['total'];
        $vehicle_no = $row['truck'];
        $signature = $row['consigner_signature'];
        $file_receipt = $row['file_receipt'];
      

		
		$i = 1;
	
//$company_query = "select * from company where status=0";
//$company_result = mysqli_query($conn,$company_query); 
//$company_row = mysqli_fetch_array($company_result);

		$out_put .='<style> .border td {
        border: 1px solid #091c4d;
		}</style>
<div style="color: #091c4d;border-color:#091c4d;"  ><table cellpadding="5" class="border"  border="1"  class="border"   ><tr>
		<td colspan="5" style="text-align:center;">
		<img src="images/pdf/header.png" style="height:80px" width="620px"/>
		</td></tr>
		</table>';
		
		$out_put .='<table border="1"  class="border"  >
				<tr>
					<td style="text-align:center;width:20%;" > <div style="font-size:2pt">&nbsp;</div> <img src="images/pdf/loveearth-or-leave-earth.png" style="height:100px;"/></td>
					
					<td style="text-align:center;width:25%">
						<table  cellpadding="3">
							<tr><td style="background-color:#091c4d;color:#fff;;font-weight:bold;">Origin</td></tr>
							<tr><td style="font-weight:bold;">'.$consignor_city.'</td></tr>
							<tr><td style="background-color:#091c4d;color:#fff;;font-weight:bold;">Destination</td></tr>
							<tr><td style="font-weight:bold;">'.$consignee_city.'</td></tr>
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
								<td> '.$gst.'</td>
							</tr>
							<tr>
								
								<td>PAN No.</td>
								<td>'.$pan.'</td>
							</tr>
							<tr><td>Mode Of Transport</td>
							<td>'.$mode_of_transportation.'</td>
							</tr>
						</table>
					</td>
				</tr>
</table>'; 

// $get_consinor_query =mysqli_query($conn,"select * from client where client_id='".$consigner."'");
// $get_consigner_det = mysqli_fetch_assoc($get_consinor_query);

// $get_consinee_query =mysqli_query($conn,"select * from client where client_id='".$consignee."'");
// $get_consignee_det = mysqli_fetch_assoc($get_consinee_query);


$out_put .='<table border="1"  class="border"   cellpadding="3">
				<tr>
				<td style="height:80px"><table>
				<tr><td style="width:25%">Consignor:</td><td style="width:75%;"> '.$consignor.' </td></tr>
				<tr><td style="height:40px;">Address</td><td style="font-size:11px;">'.$consignor_address.",".$consignor_city.",<br>".$pincode.",".$state.'</td></tr>
				<tr><td>GST NO</td><td>'.$gst.'</td></tr>
				<tr><td>Phone No:</td><td>'.$consignor_contact.'</td></tr>				
				</table> </td>
				<td> <table>
				<tr><td style="width:25%">Consignee:</td><td style="width:75%;">'.$consignee.'</td></tr>
				<tr><td style="height:40px;">Address</td><td style="font-size:11px;">'.$consignee_address.",".$consignee_city.",<br>".$pincode.",".$pincode.'</td></tr>
				<tr><td>GST NO</td><td>'.$gst.'</td></tr>
				<tr><td>Phone No:</td><td>'.$consignee_contact.'</td></tr>				
				</table></td>
				</tr></table>';
				
				
$out_put .='<table border="1"  class="border"   cellpadding="3">
				<tr style="text-align:center;font-weight:bold;">
				<td>No Of Pkgs</td>
				<td>Qty</td>
				<td>Gross Weight</td>
				<td>Charged Weight</td>
				</tr>';
		$i=1;
		// $no_of_pkge1="";
		$type_of_pkge1="";
		$party_invoice_no1="";
		$said_contents1="";
		// $qty1="";
		$gross_weight1="";
		$charged_weight1 ="";
		
		
		// $query_inv = "select * from  transaction_invoice_".$month."_".$year." where transaction_id='".$transaction_id."'";
		// $result_inv = mysqli_query($conn,$query_inv);
		// while($row_inv=mysqli_fetch_assoc($result_inv))
		// {
		// extract($row_inv);		
		// 		if($type_of_pkge !='Select Package Type'){
		// 		    $type_of_pkge1 .=get_package_name($conn,$type_of_pkge)."<br/>";
		// 		}
		// $no_of_pkge1 .=$no_of_pkge."<br/>";
		
		// $party_invoice_no1 .=$i.".".$party_invoice_no."<br/>";
		// $said_contents1 .=$said_contents."<br/>";
		// $qty1 .=$qty."<br/>";
		// $gross_weight1 .=$gross_weight."<br/>";
		// $charged_weight1 .=$charged_weight."<br/>";
		
		// 		$i++;
		// }	
		
		$out_put .='<tr>
		<td style="text-align:center;">'.$no_of_packages.'</td>
				<td style="text-align:center;">'.$kgs.'</td>
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
				
				<td>Consignee</td>
				<td></td>
				</tr>
				<tr>
				<td>Consignor</td>
				<td></td>
				</tr>
				<tr>
				<td colspan="3" style="height:70px;text-align:center;font-weight:600">Dimensions <br/> `L`*`W`*`H` `IN CM`
				<br>'.$length." * ".$width." * ".$height.'</td>
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
				$cod_amount=explode(".",$cod_amount);
				$fov_amount=explode(".",$fov_amount);
				$doc_amount=explode(".",$doc_amount);
				$cartage_amount=explode(".",$cartage_amount);
				$labour_amount=explode(".",$labour_amount);
				$other_amount=explode(".",$other_amount);
				$gst_amount=explode(".",$gst_amount);
				$out_put .='<tr>
				<td>Freight</td>
				<td style="text-align:center;">'.$frieght_rate.'</td>
				<td style="width:25%;text-align:right;">'.$frieght_amount[0].'</td>
				<td style="width:15%">'.$frieght_amount[1].'</td>
				</tr>
				<tr>
				<td>C.O.D</td>
				<td style="text-align:center;">'.$cod_rate.'</td>
				<td style="text-align:right">'.$cod_amount[0].'</td>
				<td>'.$cod_amount[1].'</td>
				</tr>
				<tr>
				<td>F.O.V</td>
				<td style="text-align:center;">'.$fov_rate.'</td>
				<td style="text-align:right">'.$fov_amount[0].'</td>
				<td>'.$fov_amount[1].'</td>
				</tr>
				<tr>
				<td>Doc.Charges</td>
				<td style="text-align:center;">'.$doc_rate.'</td>
				<td style="text-align:right">'.$doc_amount[0].'</td>
				<td>'.$doc_amount[1].'</td>
				</tr>
				<tr>
				<td>Cartage</td>
				<td style="text-align:center;">'.$cartage_rate.'</td>
				<td style="text-align:right">'.$cartage_amount[0].'</td>
				<td>'.$cartage_amount[1].'</td>
				</tr>
				<tr>
				<td>Labour Handling</td>
				<td style="text-align:center;">'.$labour_rate.'</td>
				<td style="text-align:right">'.$labour_amount[0].'</td>
				<td>'.$labour_amount[1].'</td>
				</tr>
				<tr>
				<td>Any Other Charges</td>
				<td style="text-align:center;">'.$other_rate.'</td>
				<td style="text-align:right">'.$other_amount[0].'</td>
				<td>'.$other_amount[1].'</td>
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
				<td rowspan="2"  style="height:40px;width:20%;text-align:center;">Mode Of Payment <br><br><br>'.$mode_of_pay.'</td>
				<td  style="width:40%;" rowspan="2">Consignor Signature <br>
				<span style="text-align:center;"><img src="'.$signature.'"  /><br>Not Negotiable</span>
				
				</td>
				<td style="width:40%;"><span style="font-weight:bold;">Rs. In Word: </span>'.$amount_in_words.'</td>
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