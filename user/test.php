<?php
$conn = mysqli_connect("localhost","root","","graciousexpress");
$conn1 = mysqli_connect("localhost","root","","colan_portfolio");
//Get Consignee
// $consinee = "SELECT * FROM `customer_mapping_lists` where mapping_id IN(select mapping_id from customer_mapping where client='4205')";
// $result = mysqli_query($conn,$consinee);

// while($get_consignee = mysqli_fetch_assoc($result)){
//     $client_id = $get_consignee['client_id'];
//     echo $client_id;

// }
// $output = '';
// $tbl_id = 1960;
// $sql = mysqli_query($conn,"SELECT *FROM client where `client_id` = '$tbl_id' ");
// $row = mysqli_fetch_array($sql);

// $address1 = $row['address1'];
// $address2 = $row['address2'];
// $city = $row['city'];
// $state = $row['state'];
// $pincode = $row['pincode'];
// $gst_no = $row['gst_no'];

// $output = array(
//     'address1' => $address1,
//     'address2' => $address2,
//     'city' => $city,
//     'state' => $state,
//     'pincode' => $pincode,
//     'gst_no' => $gst_no
// );
// echo json_encode($output);


// var_dump($client_id);
// exit();

$people = "5c2c7721885b9233207.jpg";


$image_data = array();

$images = "select project_screenshots from project_management where id='1'";
$res = mysqli_query($conn1,$images);
$row = mysqli_fetch_assoc($res);
$data1= ($row['project_screenshots']);

$image_expl = explode("@@",$data1);
//print_r($image_expl);
//var_dump($image_data);

// $array_image = ('abc.png,xyz.png,yyy.png');

// //echo array_search("abc.png",$array_image);

// $explode = explode(",",$array_image);

//print_r($explode);


for($i=0; $i<sizeof($image_expl); $i++){
  $image[] = array("image_name" => $image_expl[$i]);
  
  if(in_array($people,$image[$i])){
    //echo "Matched";
    $data = $image[$i];
    
  }else{
    
    continue;
  }
}
foreach($data as $da){
  echo $da;
}
echo '<img src="http://localhost/graciousexpress/user/images/'.$da.'" />';
echo '<a href="http://localhost/graciousexpress/user/images/'.$da.'" download>Download</a>';



// ini_set("SMTP","ssl://smtp.gmail.com");
// ini_set("smtp_port","587");
// $to = "mohammedtouheed75@gmail.com";
// $subject = "POD Sent";

// $message = '
// <html>
// <head>
// <title>HTML email</title>
// </head>
// <body>
// <p>This email contains HTML Tags!</p>
// <table>
// <tr>
// <th>Image</th>

// </tr>
// <tr>
// <td><img src="project_management/" /></td>
// <td>Doe</td>
// </tr>
// </table>
// </body>
// </html>
// ';

// // Always set content-type when sending HTML email
// $headers = "MIME-Version: 1.0" . "\r\n";
// $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

// // More headers
// $headers .= 'From: <mohammedtouheed77@gmail.com>' . "\r\n";
// $headers .= 'Cc: myboss@example.com' . "\r\n";

// mail($to,$subject,$message,$headers);


//print_r($image);




?>
<!-- <img src="http://localhost/graciousexpress/user/images/<?php// echo $da;?>" /> -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

<!-- <input type="text" name="price[]" id="price" value="" class="form-control price" />

<input type ="button" value="add" class="btn-add-srvice"/>
<script>

$('.btn-add-srvice').on('click', function(e){

e.preventDefault();

var template = '<input type="text" name="price[]" id="price" class="form-control price" />';

$(this).before(template);
});
$(document).on('keyup', ".price",function () {
var total = 0;

$('.price').each(function(){
  total += parseFloat($(this).val());
})  

console.log(total)
})
</script> -->

