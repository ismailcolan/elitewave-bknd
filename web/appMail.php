<?php
require 'SMPT/PHPMailerAutoload.php';
function sendAppMail($to_name, $to_mail, $subject, $msg)
{

	if ($to_mail == "") {
		$to_name = 'Elite Wave 360';
		// $to_mail='admin@graciousexpress.com';
		// $to_mail='colanpbt@colanapps.in';
		$to_mail = 'muzaffarahmed.g@colanonline.com';
	}
	// $mail_array = array('mailmeroselin3012@gmail.com','si.aadil@gmail.com');
	// array_push($mail_array,$to_email);

	$mail = new PHPMailer;
	$mail->isSMTP();     // Set mailer to use SMTP
	$mail->Host = 'smtpout.secureserver.net';  // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;
	// $mail->SMTPSecure = true;                           // Enable SMTP authentication
	$mail->Username = 'info@elitewave360.in';                 // SMTP username
	$mail->Password = 'EliteWave@360#';                           // SMTP password
	$mail->SMTPSecure = 'tls';   // Enable TLS encryption, `ssl` also accepted
	$mail->Port = 587;                                          // TCP port to connect to
	$mail->From = 'info@elitewave360.in';
	$mail->FromName = 'Elite Wave 360';
	// $mail = new PHPMailer;
	// $mail->isSMTP();     // Set mailer to use SMTP
	// $mail->Host = 'sg2plcpnl0032.prod.sin2.secureserver.net';  // Specify main and backup SMTP servers
	// $mail->SMTPAuth = true;    
	// $mail->SMTPSecure = true;                           // Enable SMTP authentication
	// $mail->Username = 'no-reply@graciousexpress.com';                 // SMTP username
	// $mail->Password = 'Admin@123';                           // SMTP password
	// $mail->SMTPSecure = 'TLS';   // Enable TLS encryption, `ssl` also accepted
	// $mail->Port = 587;                                          // TCP port to connect to
	// $mail->From = 'no-reply@graciousexpress.com';
	// $mail->FromName = 'Elite Wave 360';
	//$mail->addAddress('si.aadil@gmail.com', 'Aadil');
	//$mail->addAddress('mailmeroselin3012@gmail.com', 'Roselin');
	// $mail->addAddress($to_mail,$to_name );     // Add a recipient
	if (gettype($to_mail) == "array") {
		// for ($i = 0; $i < count($to_mail); $i++) {
		// 	$mail->addAddress($to_mail[$i], $to_name[$i]);
		// }
		$mail->addAddress('info@elitewave360.in', 'Elite Wave 360');
	} else {
		// $mail->addAddress($to_mail, $to_name);     // Add a recipient
		$mail->addCC('info@elitewave360.in', 'Elite Wave 360');
	}

	// Dear '.$to_name.',
	$mail->isHTML(true);
	$mail->Subject = $subject;
	$mail->Body    = '<body class="respond" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">    
    <table border="0" width="100%" cellpadding="0" cellspacing="0" bgcolor="ffffff">
    <tr>
    <td align="center">
    	<table border="0" align="center" width="590" cellpadding="0" cellspacing="0" class="container590">
    		<tr>
    			<td height="25" style="font-size: 25px; line-height: 25px;">&nbsp;</td> </tr>
    		<tr>
    			<td align="center">
    
    				<table border="0" align="center" width="590" cellpadding="6" cellspacing="6" style="background-color: #fff;border: 10px solid #20409a;" class="container590">
    
    					<tr>
    						<td align="center" height="70" style="height:70px;background-color: #fff;">
    							<a href="" style="display: block; border-style: none !important; border: 0 !important;"><img width="100" border="0" style="display: block; width: 75%;" src="https://elitewave360.in/web/images/elite-nav.png" alt="" /></a>
    						</td>
    					</tr>

    		<tr>
    			<td align="left">
    				<table border="0" width="590" align="center" cellpadding="0" cellspacing="0" class="container590">
    					<tr>
    						<td align="left" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
    							<p style="line-height: 24px; margin-bottom:15px;"><b>
    
    								Dear Sir / Madam,
    
    							</b></p>' . $msg . '
    							Thanks & Regards,
    						</td>
    						
    					</tr>
    					<tr>
    					<td>
    							<table border="0" width="300" align="left" cellpadding="0" cellspacing="0" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;"
    								class="container590">
    
    								<tr>
    									<td align="left" style="color: #888888; font-size: 14px; font-family: Work Sans, Calibri, sans-serif; line-height: 22px;"
    										class="text_color">
    										<div style="color: #333333; font-size: 14px; font-family: Work Sans, Calibri, sans-serif; mso-line-height-rule: exactly;">
												<p><b>Mohammed Athar<br>
												Director - Domestic & International  Marketing</b></p>
												<p>Phone: +91-9840859711 | +91 9382307611<br> E-mail : athar@elitewave360.in | info@elitewave360.in <br>Website: https://www.elitewave360.in </p>
											</div>
    									</td>
    								</tr>
    
    							</table>
    
    
    							
    						</td>
    					</tr>
    				</table>
    			</td>
    		</tr>      
    		
    				</table>
    			</td>
    		</tr>
    
    		<tr>
    			<td height="25" style="font-size: 25px; line-height: 45px;">&nbsp;</td>
    		</tr>
    	</table>
    </td>
    </tr>
    </table>
    </body>';
	// if(!$mail->send()) {
	// echo 'Mailer Error: ' . $mail->ErrorInfo;
	// return 0;
	// } else {
	// return 1;


	// }
	

	if (!$mail->send()) {
		echo "<pre>";
		echo "Mailer Error: " . $mail->ErrorInfo;
		echo "</pre>";
		return 0;
	}

	return 1;
}

//echo $mail = sendAppMail('Loganathan','tecnovatersloga@gmail.com', 'test mail', 'Hi <B>User</B>! <br/> This is test mail.');


function sendAttachments($to_name, $to_mail, $subject, $file, $image, $msg, $name)
{
	//function sendAttachments($to_name, $to_mail, $subject,$file,$image,$mail_content,$name){


	/*if(count($to_mail)=="")
    	{
    		$to_name='Elite Wave 360';
    		$to_mail='admin@graciousexpress.com';
    	}*/
	//$mail_array = array('mailmeroselin3012@gmail.com','si.aadil@gmail.com');
	//array_push($mail_array,$to_email);

	// $mail = new PHPMailer;
	// $mail->isSMTP();     // Set mailer to use SMTP
	// $mail->Host = 'sg2plcpnl0032.prod.sin2.secureserver.net';  // Specify main and backup SMTP servers
	// $mail->SMTPAuth = true;    
	// $mail->SMTPSecure = true;                           // Enable SMTP authentication
	// $mail->Username = 'no-reply@graciousexpress.com';                 // SMTP username
	// $mail->Password = 'Admin@123';                           // SMTP password
	// $mail->SMTPSecure = 'TLS';   // Enable TLS encryption, `ssl` also accepted
	// $mail->Port = 587;                                          // TCP port to connect to
	// $mail->From = 'no-reply@graciousexpress.com';
	// $mail->FromName = 'Elite Wave 360';
	// $mail->addAddress('si.aadil@gmail.com', 'Aadil');
	// $mail->addAddress('mailmeroselin3012@gmail.com', 'Roselin');

	$mail = new PHPMailer;

	// $mail->SMTPDebug = 1;
$mail->Debugoutput = 'html';

	$mail->isSMTP();     // Set mailer to use SMTP
	$mail->Host = 'smtpout.secureserver.net';  // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;
	// $mail->SMTPSecure = true;                           // Enable SMTP authentication
	$mail->Username = 'info@elitewave360.in';                 // SMTP username
	$mail->Password = 'EliteWave@360#';                           // SMTP password
	$mail->SMTPSecure = 'tls';   // Enable TLS encryption, `ssl` also accepted
	$mail->Port = 587;                                          // TCP port to connect to
	$mail->From = 'info@elitewave360.in';
	$mail->FromName = 'Elite Wave 360';
	// $mail->addAddress('si.aadil@gmail.com', 'Aadil');
	// $mail->addAddress('mailmeroselin3012@gmail.com', 'Roselin');

	//$mail->addAddress($to_mail,$to_name );     // Add a recipient
	// for ($i = 0; $i < count($to_mail); $i++) {
	// 	$mail->addAddress($to_mail[$i], $to_name[$i]);
	// }
	for ($i = 0; $i < count($to_mail); $i++) {

    if (!empty($to_mail[$i])) {
        // $mail->addAddress(
        //     trim($to_mail[$i]),
        //     isset($to_name[$i]) ? $to_name[$i] : ''
        // );
		$mail->addAddress('info@elitewave360.in', 'Elite Wave 360');
    }
}
	// foreach($to_email as $emails)
	// {
	// $mail->AddCC($emails, 'Testing');
	// }

	$mail->isHTML(true);


	if (is_array($file)) {
		foreach ($file as $fil) {
			if (file_exists($fil)) {
				$mail->addAttachment($fil);
			}
		}
	} else {
		if (!empty($file) && file_exists($file)) {
			$mail->addAttachment($file);
		}
	}
	if (count($image) > 0) {
		foreach ($image as $val) {
			$mail->addAttachment(trim($val));
		}
	}
	$mail->Subject = $subject;
	$mail->Body    = '<body class="respond" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">    
    <table border="0" width="100%" cellpadding="0" cellspacing="0" bgcolor="ffffff">
    <tr>
    <td align="center">
    	<table border="0" align="center" width="590" cellpadding="0" cellspacing="0" class="container590">
    		<tr>
    			<td height="25" style="font-size: 25px; line-height: 25px;">&nbsp;</td> </tr>
    		<tr>
    			<td align="center">
    
    				<table border="0" align="center" width="590" cellpadding="6" cellspacing="6" style="background-color: #fff;border: 10px solid #021659;" class="container590">
    
    					<tr>
    						<td align="center" height="70" style="height:70px;background-color: #fff;">
    							<a href="" style="display: block; border-style: none !important; border: 0 !important;"><img width="100" border="0" style="display: block; width: 75%;" src="https://elitewave360.in/web/images/elite-nav.png" alt="" /></a>
    						</td>
    					</tr>

    		<tr>
    			<td align="left">
    				<table border="0" width="590" align="center" cellpadding="0" cellspacing="0" class="container590">
    					<tr>
    						<td align="left" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
    							<p style="line-height: 24px; margin-bottom:15px;"><b>
    
    								Dear Sir/Madam,
    
    							</b></p>' . $msg . '
	
    
    						</td>
    						
    					</tr>
    					<tr>
    					<td>
    							<table border="0" width="300" align="left" cellpadding="0" cellspacing="0" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;"
    								class="container590">
    
    								<tr>
    									<td align="left" style="color: #888888; font-size: 14px; font-family: Work Sans, Calibri, sans-serif; line-height: 22px;"
    										class="text_color">
    										<div style="color: #333333; font-size: 14px; font-family: Work Sans, Calibri, sans-serif; mso-line-height-rule: exactly;">
												<p>Thanks & Regards,<br>
												<b>Mohammed Athar<br>
												Director - Domestic & International  Marketing</b><br>
												Phone: +91-9840859711 | +91 9382307611<br> 
												E-mail : athar@elitewave360.in | info@elitewave360.in <br>
												Website: https://www.elitewave360.in </p>
												<img style="width: 75%;" src="https://elitewave360.in/web/images/Mail_Signature.png" alt="" />
												<p><b>Corporate Office :</b> EliteWave360 Logistics, II Floor, #10/35, M.V.Badran Street, Periamet, Chennai - 600 003., Tamil Nadu, India.</p>
											</div>
    									</td>
    								</tr>
    
    							</table>
    
    
    							
    						</td>
    					</tr>
    				</table>
    			</td>
    		</tr>      
    		
    				</table>
    			</td>
    		</tr>
    
    		<tr>
    			<td height="25" style="font-size: 25px; line-height: 45px;">&nbsp;</td>
    		</tr>
    	</table>
    </td>
    </tr>
    </table>
    </body>';
	// if(!$mail->send()) {
	// echo 'Mailer Error: ' . $mail->ErrorInfo;
	// return 0;
	// } else {
	// return 1;


	// }
// 	echo "<pre>";
// echo "Recipients:\n";
// print_r($to_mail);

// echo "\nAttachment:\n";
// print_r($file);

// echo "\nImages:\n";
// print_r($image);

// echo "</pre>";
	if (!$mail->send()) {
		echo "<pre>";
		echo "Mailer Error: " . $mail->ErrorInfo;
		echo "</pre>";
		return 0;
	}

	return 1;
}

//echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst'); 
