<?php 
ini_set('max_execution_time', 27000);

$conn = mysqli_connect("localhost","root","") or die(mysqli_error());
$db = mysqli_select_db($conn,"gracious") or die(mysqli_error());


echo "<html><body><table>\n\n";
$f = fopen("excel/statelist.csv", "r");
$i=1;
while (($line = fgetcsv($f)) !== false) {

       
		$i = $i + 1;
        $j = 0;
        foreach ($line as $cell)
        {
        $j = $j+1;
        if ($j == 1) $sl= $cell;
        if ($j == 2) $name= $cell;
        if ($j == 3) $address= $cell;
        if ($j == 4) $pan= $cell;
        if ($j == 5) $gst= $cell;
        if ($j == 6) $city= $cell;
        if ($j == 7) $state= $cell;
        if ($j == 8) $pin= $cell;
        if ($j == 9) $cperson= $cell;
        if ($j == 10) $phone= $cell;
        if ($j == 11) $mobile= $cell;
        if ($j == 12) $email= $cell;
		
        }
		
		 	
	 $query="select * from city where city_name='$city'";
	$result=mysqli_query($conn,$query);
	while($row=mysqli_fetch_array($result))
	{
		
		echo "<tr>";       
        echo "<td>$sl</td>";
        echo "<td>$name</td>";         
        echo "<td>$address</td>";         
        echo "<td>$pan</td>";         
        echo "<td>$gst</td>";         
        echo "<td>".$row['city_id']."</td>";         
        echo "<td>".$row['state']."</td>";         
        echo "<td>$pin</td>";         
        echo "<td>$cperson</td>";         
        echo "<td>$phone</td>";         
        echo "<td>$mobile</td>";         
        echo "<td>$email</td>";         
        echo "</tr>\n";
		
		$query1=" insert into client(client_id,client_company_name,contact_person,address1,city,state,pan_no,gst_no,email,contact_no,pincode,status,created_at,created_by) values ('$sl','$name','$cperson','$address','".$row['city_id']."','".$row['state']."','$pan','$gst','$email','$mobile','$pin','0','26-09-2018','1')";
	$result1=mysqli_query($conn,$query1);	
	
	} 	
		
		
	
	
	
	
}
fclose($f);
echo "\n</table></body></html>";
?>
