<?php
require_once 'vendor/autoload.php';

//Create Login

$credential = (new Swift_SmtpTransport('smtp.gmail.com',587,'tls'))
->setUsername('mohammedtouheed77@gmail.com')
->setPassword("Mixture@321")
;


//login

$mailer = new Swift_Mailer($credential);


// Use AntiFlood to re-connect after 100 emails
$mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin(1));

// And specify a time in seconds to pause for (30 secs)
$mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin(1, 30));


//Create HTML Template

$html = "<html>
<head>
<title>Alerting System</title>
<style type=\"text/css\">
<!--
table {
font-family: Verdana, Arial, Helvetica, sans-serif;
border: thin solid;
}
th {
font-family: Verdana, Arial, Helvetica, sans-serif;
color: #f0f0f0;
background-color: #ff0000;
font-size: 12px;
}
td {
font-family: Verdana, Arial, Helvetica, sans-serif;
color: #000000;
font-size: 10px;
border: thin solid;
}
body {
font-family: Verdana, Arial, Helvetica, sans-serif;
font-size: 10px;
}
-->
</style>
<head>
<body>
<CENTER>
<TABLE width=\"50%\">
<TBODY>
<tr>
<th >test</th>
</tr>
</TBODY>
</TABLE>
<br>
Alerting and Reporting System
</CENTER></BODY></HTML>
";

//Create Mail

$message = (new Swift_Message("Test Subject"))
->setFrom(['mohammedtouheed77@gmail.com' => "Touheed"])
//->setTo(['mohammedtouheed75@gmail.com' => "Personal"])
->setBody($html , 'text/html')
->attach(Swift_Attachment::fromPath('1_2021_1transaction.pdf'))
;

set_time_limit(0);
$pause = 1;
$each = 1;
//$conn = mysqli_connect("localhost","root","","bookconsignment");
//$data = mysqli_query($conn,"select name,username from user where id = 3");

//$result -> $conn -> query($data);
// $data ->fetch_all(MYSQLI_ASSOC);
$count = mysqli_num_rows($data);

while($row = mysqli_fetch_assoc($data)){

    $data1 = $row['username'];
   
}

$mail = explode("@@",$data1);

//var_dump($count);


//for($i=0; $i<$count; $i+=$each){
//     $data2 = $data;
    
//     
 //}
 foreach($mail as $d){
     //var_dump($d);
             $message->AddTo($d,'Client_Name');
     }sleep($pause);
    
// //Send Mail

$send = $mailer->Send($message);
?>