<?php 
// function get_user($con,$id){
// 	$query = "SELECT *FROM user where id = '$id'";
// 	$result = mysqli_query($con,$query);
// 	$row = mysqli_fetch_array($result);
// 	return $row['name'];
// }
function get_user_email($con,$id){
	$query = "SELECT *FROM user where id = '$id'";
	$result = mysqli_query($con,$query);
	$row = mysqli_fetch_array($result);
	return $row['username'];
}
function get_rfp_id($con){
	$query = "SELECT max(pickup_id) as pickup_id FROM pickup";
	$result = mysqli_query($con,$query);
	$row = mysqli_fetch_array($result);
	return $row['pickup_id'];
}
function get_userss($con,$id){
	$query = "SELECT *FROM user where id = '$id'";
	$result = mysqli_query($con,$query);
	$row = mysqli_fetch_array($result);
	return $row['name'];
}

?>