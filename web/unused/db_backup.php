<?php 

  
require 'SMPT/PHPMailerAutoload.php';		
ini_set('max_execution_time', 1200);

echo $send=backup_tables("localhost", "gracious_web", "gracious_web", "gracious_web","*");


function backup_tables($host, $user, $pass, $dbname, $tables = '*') {
    $link = mysqli_connect($host,$user,$pass, $dbname);

    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit;
    }

    mysqli_query($link, "SET NAMES 'utf8'");

    if($tables == '*')
    {
        $tables = array();
        $result = mysqli_query($link, 'SHOW TABLES');
        while($row = mysqli_fetch_row($result))
        {
            $tables[] = $row[0];
        }
    }
    else
    {
        $tables = is_array($tables) ? $tables : explode(',',$tables);
    }

    $return = '';
    //cycle through
    foreach($tables as $table)
    {
        $result = mysqli_query($link, 'SELECT * FROM '.$table);
        $num_fields = mysqli_num_fields($result);
        $num_rows = mysqli_num_rows($result);

        $return.= 'DROP TABLE IF EXISTS '.$table.';';
        $row2 = mysqli_fetch_row(mysqli_query($link, 'SHOW CREATE TABLE '.$table));
        $return.= "\n\n".$row2[1].";\n\n";
        $counter = 1;

        //Over tables
        for ($i = 0; $i < $num_fields; $i++) 
        {   //Over rows
            while($row = mysqli_fetch_row($result))
            {   
                if($counter == 1){
                    $return.= 'INSERT INTO '.$table.' VALUES(';
                } else{
                    $return.= '(';
                }

                //Over fields
                for($j=0; $j<$num_fields; $j++) 
                {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n","\\n",$row[$j]);
                    if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                    if ($j<($num_fields-1)) { $return.= ','; }
                }

                if($num_rows == $counter){
                    $return.= ");\n";
                } else{
                    $return.= "),\n";
                }
                ++$counter;
            }
        }
        $return.="\n\n\n";
    }

    //save file
    $fileName = $dbname.Date('d_m_Y').'.sql';
    $handle = fopen($fileName,'w+');
    fwrite($handle,$return);
    if(fclose($handle)){
       
	   
$files = array($fileName);
$zipname = $dbname.Date('d_m_Y').'.zip';
$zip = new ZipArchive;
$zip->open($zipname, ZipArchive::CREATE);
foreach ($files as $file) {
  $zip->addFile($file);
}
$zip->close();

unlink($fileName);

	$mail = new PHPMailer;
	$mail->isSMTP();   
                               // Set mailer to use SMTP
	$mail->Host = 'sg2plcpnl0032.prod.sin2.secureserver.net';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;    
        $mail->SMTPSecure = true;                           // Enable SMTP authentication
        $mail->Username = 'no-reply@graciousexpress.com';                 // SMTP username
        $mail->Password = 'Admin@123';                           // SMTP password
        $mail->SMTPSecure = 'TLS';  
                          // Enable TLS encryption, `ssl` also accepted
      $mail->Port = 587;                                          // TCP port to connect to
      $mail->From = 'no-reply@graciousexpress.com';
      $mail->FromName = 'Elite Wave 360 APP';
$mail->addAddress('tecnovatersloga@gmail.com', 'Loganathan');
	//$mail->addAddress($to_mail,$to_name );     // Add a recipient
	$mail->isHTML(true);                                 
	$mail->addAttachment($zipname);       
	$mail->Subject = "Elite Wave 360 DATABASE BACKUP ";
	$mail->Body    =  "Hai Loganathan! <br><br> Please find the Attachment of Daily DB Backup For Elite Wave 360";
	if(!$mail->send()) {
		echo 'Mailer Error: ' . $mail->ErrorInfo;
		return 0;
	} else {
	unlink($zipname);
		return 1;
		

	}

    }
}










?>