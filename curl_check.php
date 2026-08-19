<?php

$month=2;
$year=2022;
$transaction_id=69;
$directory = "test/";
$curl ='http://localhost/graciousexpress/web/gst_invoice_page.php?month=2&year=2022&id=69&invoice_no=HRGST/00138/21-22';
echo $invoice_file_name = $month."_".$year."_".$transaction_id."invoice";
       echo  $download_path =  $directory.$invoice_file_name.'.pdf';
        $file_inv_download = curl_init($curl);
        curl_setopt($file_inv_download,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($file_inv_download,CURLOPT_REFERER,true);
		curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
        //curl_getinfo($file_inv_download)
        $store_inv = curl_exec($file_inv_download);
        curl_close($curl);

        $save_inv_file = file_put_contents($download_path,$store_inv);
if($save_inv_file){
echo "file Created";}else{echo "Not";}
// if (curl_exec($curl) === FALSE) {
//    die("Curl Failed: " . curl_error($curl));
// } else {
//    return curl_exec($curl);
// }
?>