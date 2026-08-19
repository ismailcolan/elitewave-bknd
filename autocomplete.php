<?php
require_once("web/include/connect.php");
require_once("web/include/function.php");

//$con = mysqli_connect("localhost",'root','','bookconsignment');
$term = $_GET['term'];
$user_id = $_GET['user_id'];
$autocomplete = $_REQUEST['autocomplete'];
if($autocomplete == "get_username"){
    //SELECT *from customer_mapping_lists where mapping_id IN(select mapping_id from customer_mapping where client='4205')
    $users = mysqli_query($conn,"select *from users where user_id = '$user_id'");
    $user_email = mysqli_fetch_assoc($users);
    $email = $user_email['email'];

    $query_client = mysqli_query($conn,"select *from client where email = '$email'");
    $query_client_id = mysqli_fetch_assoc($query_client);
    $client_id =$query_client_id['client_id'];
                                 
    $query = "select *from customer_mapping_lists where mapping_id IN(select mapping_id from customer_mapping where client='$client_id')";
    $result = mysqli_query($conn,$query);
    while($row = mysqli_fetch_array($result)){
        $result_value['value'] = get_client_name($conn,$row['client_id']);
        $result_value['id'] = $row['client_id'];
        $search[] = $result_value; 
    }
     echo json_encode($search);
}
if($autocomplete == "get_consigner"){
    //SELECT *from customer_mapping_lists where mapping_id IN(select mapping_id from customer_mapping where client='4205')
    $users = mysqli_query($conn,"select *from users where user_id = '$user_id'");
    $user_email = mysqli_fetch_assoc($users);
    $email = $user_email['email'];

    $query_client = mysqli_query($conn,"select *from client where email = '$email'");
    while($query_client_id = mysqli_fetch_array($query_client)){
    $client_id =$query_client_id['client_id'];
    $client_name =$query_client_id['client_company_name'];
    $result_value['value'] = $query_client_id['client_company_name'];
    $result_value['id'] = $query_client_id['client_id'];
    $search[] = $result_value; 
    }
     echo json_encode($search);
}

if($autocomplete == "get_origin_pincode"){
    $query = "select *from rate where origin LIKE '%$term%' group by origin";
  
      $res = mysqli_query($conn,$query);
      while($row = mysqli_fetch_assoc($res)){
  
          $id =$row['id'];
          $result_value['value'] = $row['origin'];
          $result_value['id'] = $row['id'];
  
          $search[] = $result_value;
  
         
  
      }
      echo json_encode($search);
  }
  
  if($autocomplete == "get_destination_pincode"){
      $query = "select *from rate where destination LIKE '%$term%' group by destination";
   
       $res = mysqli_query($conn,$query);
       while($row = mysqli_fetch_array($res)){
   
           $id =$row['id'];
           $result_value['value'] = $row['destination'];
           $result_value['id'] = $row['id'];
   
           $search[] = $result_value;
   
       }
       echo json_encode($search);
   }
  
   if($autocomplete == "origin_expected_delivery"){
      $query = "select *from expectded_delivery where origin LIKE '%$term%' group by origin";
    
        $res = mysqli_query($conn,$query);
        while($row = mysqli_fetch_assoc($res)){
    
            $id =$row['id'];
            $result_value['value'] = $row['origin'];
            $result_value['id'] = $row['id'];
    
            $search[] = $result_value;
        }
        echo json_encode($search);
    }
  
    if($autocomplete == "destination_expected_delivery"){
      $query = "select *from expectded_delivery where destination LIKE '%$term%' group by destination";
    
        $res = mysqli_query($conn,$query);
        while($row = mysqli_fetch_assoc($res)){
    
            $id =$row['id'];
            $result_value['value'] = $row['destination'];
            $result_value['id'] = $row['id'];
    
            $search[] = $result_value;
    
        }
        echo json_encode($search);
    }

?>