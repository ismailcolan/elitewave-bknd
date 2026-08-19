<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<form action="pod_search.php" method="post">

<input type="text" name="search" id="search" />
<input type="submit" value="Search" name="search_btn"/>
</form>
</body>
</html>


<?php
$conn1 = mysqli_connect("localhost","root","","colan_portfolio");

if(isset($_POST['search_btn'])){
    
    $people = $_POST['search'];

//$people = "5c2c7721885b9233207.jpg";


$image_data = array();

$images = "select project_screenshots from project_management where id='1'";
$res = mysqli_query($conn1,$images);
$row = mysqli_fetch_assoc($res);
$data1= ($row['project_screenshots']);

$image_expl = explode("@@",$data1);

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
     $da;
  }
  echo "</br>";
  echo '<img src="http://localhost/graciousexpress/user/images/'.$da.'" class="a" width="300px" height="200px" />';
  echo "</br>";
  echo '<a href="http://localhost/graciousexpress/user/images/'.$da.'" download>Download</a>';
}
?>
