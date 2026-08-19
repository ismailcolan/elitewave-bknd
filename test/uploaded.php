<?php
// if($_FILES['file']['name'] != ''){
//     $test = explode('.', $_FILES['file']['name']);
//     $extension = end($test);    
//     $name = rand(100,999).'.'.$extension;

//     $location = 'uploads/'.$name;
//     move_uploaded_file($_FILES['file']['tmp_name'], $location);

//     echo '<img src="'.$location.'" height="100" width="100" />';
// }

$con = mysqli_connect("localhost",'root','','bookconsignment');

if($_FILES['file']['name'] != ''){
    $test = explode('.', $_FILES['file']['name']);
    $extension = end($test);    
    $name = rand(100,999).'.'.$extension;

    $location = 'uploads/'.$name;
    move_uploaded_file($_FILES['file']['tmp_name'], $location);

    echo '<img src="'.$location.'" height="100" width="100" />';
}

$register_user = mysqli_query($con,"INSERT INTO user (name,username,password) VALUES('aa','$name','12345')");

if($register_user){
    echo "1";

}else{
    echo "0";
}
