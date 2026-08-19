<?php
// $conn = mysqli_connect("localhost","staging","vySzrpsqDRupDHS","staging");
include("./config.ini.php");
//Auto Entry

$name = rand(1111,9999)."test";
$time= date("h:i:s");
$date= date("Y-m-d");
echo $q = "INSERT INTO `cronjob`(`name`, `time`,`date`) VALUES ('$name','$time','$date')";
$insert = mysqli_query($conn,$q);
if($insert){
    echo "data added";
}


// $conn = mysqli_connect("localhost","ge_latest","u8nu3fJPVNm@Ekz","graciousexpress_latest");

// $orign = 'Origin-'.rand(1,99);
// $destination = 'Destination-'.rand(1,99);
// $surface = "10";
// $express = "20";
// $train = "30";
// $air = "40";
// $note= date("h:i:s");
// echo $query = "INSERT INTO `rate`(`origin`, `destination`, `surface`, `express`, `train`, `air`, `note`) VALUES ('$orign','$destination','$surface','$express','$train','$air','$note')";

// $res = mysqli_query($conn,$query);

// if($res){
    
//     echo "Data Insert";
// }else{
    
//     echo "Data Not Insert";
// }
?>