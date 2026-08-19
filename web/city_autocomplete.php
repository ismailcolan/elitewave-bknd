<?php 
require_once 'include/connect.php';
$name = $_GET['term'];
//$selected_dept = !empty($_REQUEST['department'])?"and department_id='".$_REQUEST['department']."' and employee_id!='".$_SESSION['employee_id']."'":"";
	$query = "SELECT * FROM city where (city_name LIKE '%".$name."%') and status=0";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	while($row = mysqli_fetch_assoc($result))
	{
		$result_value['value'] = $row['city_name'];
		$result_value['id'] = $row['city_id'];
		
		$result_value['query'] = $query;
		$search[] = $result_value;
	}
	echo json_encode($search);
?>