
function get_trans_table_name($date){
	//echo $date;
	$dt=(explode("-",$date));	

	if($dt[1]<=3){
		$m=4;
		$y=$dt[2]-1;
		$trans_name = "transaction_1_".$dt[2];
		$trans_image_name = "transaction_images_1_".$dt[2];
		$trans_invoice_name = "transaction_invoice_1_".$dt[2];
	}
	else if(($dt[1]>=4) && ($dt[1]<=6)){
		$m=1;
		$y=$dt[2];
		$trans_name = "transaction_2_".$dt[2];
		$trans_image_name = "transaction_images_2_".$dt[2];
		$trans_invoice_name = "transaction_invoice_2_".$dt[2];
	}
	else if(($dt[1]>=7) && ($dt[1]<=9)){
		$m=2;
		$y=$dt[2];
		$trans_name = "transaction_3_".$dt[2];
		$trans_image_name = "transaction_images_3_".$dt[2];
		$trans_invoice_name = "transaction_invoice_3_".$dt[2];
	}
	else{
		$m=3;
		$y=$dt[2];
		$trans_name = "transaction_4_".$dt[2];
		$trans_image_name = "transaction_images_4_".$dt[2];
		$trans_invoice_name = "transaction_invoice_4_".$dt[2];
	}
	
	$table_name = array($trans_name,$trans_image_name,$trans_invoice_name);
	$prev_name = array("transaction","transaction_images","transaction_invoice");
	$primary_key = array("transaction_id","attachment_id","invoice_id");
	for($i=0;$i<count($table_name);$i++){
		$val = mysqli_query($conn,'select 1 from $table_name[$i] LIMIT 1');

			if($val !== FALSE)
			{
				$check_query = "select max($primary_key[$i]) from ".$prev_name.$m.$y"";
				$check_result = mysqli_query($conn,$check_query);
				$check_row = mysqli_fetch_array($check_result);
				$value = $check_row[$primary_key[$i]]+1;
				
				$db_creation=mysqli_query($conn,"create table ".$table_name[$i]." like ".$prev_name[$i]."");
				$db_alter=mysqli_query($conn,"ALTER TABLE ".$table_name[$i]." AUTO_INCREMENT =".$value);
				$db_name_store=mysqli_query($conn,"insert into transaction_tables(tbl_name,created_at) values ('$trans_tbl','$date')");	
		
			}
			else
			{
				
			}
	}
	return $table_name;
}