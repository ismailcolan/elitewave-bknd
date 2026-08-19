<?php 
function get_city($conn,$id){
	$query = "select * from city where city_id=$id";
	$result = mysqli_query($conn,$query);
	$row =mysqli_fetch_array($result);
	return $row['city_name'];
}
function get_state($conn,$id){
	$query = "select * from state where state_id=$id";
	$result = mysqli_query($conn,$query);
	$row =mysqli_fetch_array($result);
	return $row['state_name'];
}
?>