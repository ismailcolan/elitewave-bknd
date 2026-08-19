<?php
require_once("web/include/connect.php");
function enc_name($name='123'){
    $enc = base64_encode(base64_encode(base64_encode(base64_encode('GraciousExpress').':$'.base64_encode($name).':$'.base64_encode('GraciousExpress'))));
    return $enc;
}
//$con = mysqli_connect("localhost","root","","bookconsignment");
$cmd = $_REQUEST['cmd'];
if($cmd == "check_pass"){
    $check_key_id = $_REQUEST['chk_key_id'];
    $check_key= $_REQUEST['chk_key'];
	$check_key= enc_name($check_key);
    $query = "select *from users where password = '".$check_key."' and user_id = '$check_key_id' ";
    $result = mysqli_query($conn,$query) or die(mysqli_error($conn));
    if(mysqli_num_rows($result) > 0){
        echo 1; 
    }else{
        echo 0; 

    }
       
}
if($cmd == "check_email"){
    $check_key= $_REQUEST['chk_key'];
    $query = "select *from users where email = '".$check_key."'";
    $result = mysqli_query($conn,$query) or die(mysqli_error($conn));
    if(mysqli_num_rows($result) > 0){
        echo 1; 
    }else{
        echo 0; 

    }
       
}

?>