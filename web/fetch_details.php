<?php
require_once ('include/connect.php');
require_once ('include/function.php');
require_once ('include/set_connection.php');
date_default_timezone_set('Asia/Kolkata');
$c_date = date('d-m-Y');
$date = new DateTime();
$c_time = $date->format('H:i:s A');
$c_date_string = strtotime($c_date);

$cmd = $_REQUEST['cmd'];
$created_at = $updated_at = date('d-m-Y');
$updated_by = $created_by = $_SESSION['admin_id'];
date_default_timezone_set('Asia/Kolkata');
$c_date = date('d-m-Y');
$date = new DateTime();
$c_time = $date->format('H:i:s A');
$c_date_string = strtotime($c_date);
$company_id = $_SESSION['company_id'];
$month = date('m');
$year = date('Y');

if ($cmd == 'get_customer_mapping_details') {
	$out_put = '';
	$id = $_REQUEST['id'];
	$query = "select * from customer_mapping where client='" . $id . "'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);

	$mapping_query = "select * from customer_mapping_lists where mapping_id='" . $row['mapping_id'] . "'";
	$mapping_result = mysqli_query($conn, $mapping_query);
	$i = 1;
	if (mysqli_num_rows($mapping_result) > 0) {
		$client_company_name = array();
		$map_client_id = array();
		$map_list_id = array();
		while ($mapping_row = mysqli_fetch_array($mapping_result)) {
			$client = get_client($conn, $mapping_row['client_id']);
			array_push($client_company_name, $client['client_company_name']);
			array_push($map_client_id, $mapping_row['client_id']);
			array_push($map_list_id, $mapping_row['list_id']);
		}
		// sort($client_company_name);
		for ($j = 0; $j < count($client_company_name); $j++) {
			$k = $k + 1;

			$out_put .= '<tr>
			<td  class="text-center">' . $k . '</td>
			<td>' . $client_company_name[$j] . '</td>
			<td class="text-center">
			<input type="hidden" name="mapp_client_id[]" value="' . $map_client_id[$j] . '" /><a title="Delete" href="#" class="table-actions btn-trash"  id="' . $map_list_id[$j] . '"><i class="fa fa-trash-o"></i></a></td>
		</tr>';
		}
		echo $out_put;
	} else
		echo '0';
}

if ($cmd == "chck_users_email") {

    $email = trim($_REQUEST['email']);

    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        die("1");
    } else {
        die("0");
    }
}

// if ($cmd == 'get_branch_details') {
// 	$tbl_id = $_REQUEST['tbl_id'];

// 	$query = "select * from branch where branch_id='$tbl_id'";
// 	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
// 	$row = mysqli_fetch_array($result);
// 	echo json_encode($row);
// }

// if($cmd=="get_branch_details")
// {

//     $branch_id=$_POST['branch_id'];

//     $query=mysqli_query($conn,"
//         SELECT *
//         FROM client_branch
//         WHERE client_branch_id='$branch_id'
//     ");

//     echo json_encode(mysqli_fetch_assoc($query));

// }
if ($cmd == 'get_transaction_month_details') {
	$out_put = '';
	$month = $_REQUEST['month'];
	$dt = explode('-', $month);

	if ($dt[0] <= 3) {
		$m = 4;
		$m1 = 1;
		$y = $dt[1];
		$trans_name = 'transaction_' . $m1 . '_' . $dt[1];
		$trans_image_name = 'transaction_images_' . $m1 . '_' . $dt[1];
		$trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $dt[1];
	} else if (($dt[0] >= 4) && ($dt[0] <= 6)) {
		$m = 1;
		$m1 = 2;
		$y = $dt[1];
		$trans_name = 'transaction_' . $m1 . '_' . $dt[1];
		$trans_image_name = 'transaction_images_' . $m1 . '_' . $dt[1];
		$trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $dt[1];
	} else if (($dt[0] >= 7) && ($dt[0] <= 9)) {
		$m = 2;
		$m1 = 3;
		$y = $dt[1];
		$trans_name = 'transaction_' . $m1 . '_' . $dt[1];
		$trans_image_name = 'transaction_images_' . $m1 . '_' . $dt[1];
		$trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $dt[1];
	} else {
		$m = 3;
		$m1 = 4;
		$y = $dt[1];
		$trans_name = 'transaction_' . $m1 . '_' . $dt[1];
		$trans_image_name = 'transaction_images_' . $m1 . '_' . $dt[1];
		$trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $dt[1];
	}
	// OLD
	if ($_SESSION['role'] == 'AD') {
		$query = 'select * from transaction_' . $m1 . '_' . $dt[1] . " where grn_date like '%$month' and invoice_no !='' order by grn_date desc,grn_no desc";
	} else {
		$query = 'select * from transaction_' . $m1 . '_' . $dt[1] . " where consigner='" . $_SESSION['company_id'] . "' or consignee='" . $_SESSION['company_id'] . "' and grn_date like '%$month' and invoice_no !='' order by grn_date desc,grn_no desc";
	}

	// NEW — add LEFT JOIN so tracking_code is available in $row
	if ($_SESSION['role'] == 'AD') {
		$query = 'SELECT t.*, l.tracking_code FROM transaction_' . $m1 . '_' . $dt[1] . " t
              LEFT JOIN transaction_log l ON t.transaction_id = l.transaction_id
              WHERE t.grn_date LIKE '%" . $month . "' AND t.invoice_no != ''
              ORDER BY t.grn_date DESC, t.grn_no DESC";
	} else {
		$query = 'SELECT t.*, l.tracking_code FROM transaction_' . $m1 . '_' . $dt[1] . " t
              LEFT JOIN transaction_log l ON t.transaction_id = l.transaction_id
              WHERE (t.consigner='" . $_SESSION['company_id'] . "' OR t.consignee='" . $_SESSION['company_id'] . "')
              AND t.grn_date LIKE '%" . $month . "' AND t.invoice_no != ''
              ORDER BY t.grn_date DESC, t.grn_no DESC";
	}
	$result = mysqli_query($conn, $query);
	$i = 1;
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$booking = $row['booking_status'];
			$consignment_mode = $row['mode_of_consignment'];
			$status = $row['status'];
			$remarks = $row['remarks'];
			$cancelled_by = get_user($conn, $row['cancelled_by']);
			$updated_at = $row['updated_at'];

			$pkg_q = mysqli_query($conn, 'select sum(no_of_pkge) as pkge from transaction_invoice_' . $m1 . '_' . $dt[1] . " where transaction_id='" . $row['transaction_id'] . "'");
			$pkg_r = mysqli_fetch_array($pkg_q);

			// to add charges, restricted and frequency symbol for consigner
			if (check_invoice_restricted($conn, $row['consigner']) == 1) {
				$restricted_sign = " <i class='fa fa-ban text-danger' title='This client is restricted'></i>";
			}
			if (checkPartyWiseFrequency($conn, $row['consigner']) == 0) {
				$frequency_sign = " <i class='fa fa-clock-o text-primary' title='This client is in frequency'></i>";
			}
			if (checkClientCharges($conn, $row['consigner']) > 0) {
				$charges_sign = " <i class='fa fa-inr text-success' title='This client applies client charges'></i>";
			}

			// to add charges, restricted and frequency symbol for consignee
			if (check_invoice_restricted($conn, $row['consignee']) == 1) {
				$restricted_symbol = " <i class='fa fa-ban text-danger' title='This client is restricted'></i>";
			}
			if (checkPartyWiseFrequency($conn, $row['consignee']) == 0) {
				$frequency_symbol = " <i class='fa fa-clock-o text-primary' title='This client is in frequency'></i> ";
			}
			if (checkClientCharges($conn, $row['consignee']) > 0) {
				$charges_symbol = " <i class='fa fa-inr text-success' title='This client applies client charges'></i>";
			}

			$out_put .= '<tr>
			<td class="text-center">' . $i . '</td>
			<td>' . $row['grn_no'] . '</td>
			<td>' . ($row['tracking_code'] ?? '') . '</td>
			<td>' . $row['grn_date'] . '</td>
			<td>' . $pkg_r['pkge'] . '</td>
			<td>' . get_client_name($conn, $row['consigner']) . ' ' . $restricted_sign . ' ' . $frequency_sign . ' ' . $charges_sign . '</td>
			<td>' . get_client_name($conn, $row['consignee']) . ' ' . $restricted_symbol . ' ' . $frequency_symbol . ' ' . $charges_symbol . '</td>
			<td>' . get_city_name($conn, $row['destination']) . '</td>';
			if ($booking == '1') {
				$out_put .= '<td style="color:red;">Consignment Cancelled</td>';
			} else {
				$out_put .=
					'<td>' . get_trans_status($row['status']) . '</td>';
			}
			$out_put .= '<td>';
			$grn_no = $row['grn_no'];
			if ($grn_no != '') {
				$screens = $grn_no;
				$ext = '.jpg';
				$search = $screens . $ext;
				$image_data = array();
				$images = "select screens from pod_files where screens LIKE '%$screens%' ";
				$res = mysqli_query($conn, $images);
				while ($pod_row = mysqli_fetch_assoc($res)) {
					$imagesd1[] = explode('@@', $pod_row['screens']);
				}
				foreach ($imagesd1 as $key => $value1) {
					foreach ($value1 as $key2 => $value2) {
						$filtered_array[] = $value2;
					}
				}
				$filter_img = preg_grep('/^' . $screens . '.*/', $filtered_array);
				$array_unique = array_unique($filter_img);
				$count = count($array_unique);
				// $count = 1;
			}
			if ($count == 1)
				$out_put .= '<a title="POD Uploaded"  class="table-actions btn-edit" id=' . $row['transaction_id'] . '><i class="fa fa-check-circle"></i></a>';
			elseif ($count == 2)
				$out_put .= '<a style="color:green;" title="POD Uploaded"  class="table-actions btn-edit" id=' . $row['transaction_id'] . '><i class="fa fa-check-circle"></i></a>';
			else
				$out_put .= '<a title="POD Not Uploaded"  class="table-actions btn-edit" id=' . $row['transaction_id'] . '><i class="fa fa-times-circle-o"></i></a>';

			$out_put .= '</td>
			
			<td class="actions center-content ">
			
			
				<div class="action-buttons" style="width: 100%;">
				
                <!--- <a title="Cancel"  class="table-actions btn-edit " id="' . $row['transaction_id'] . '"><i class="fa fa-ban"></i></a> -->';
			if ($row['book_manual'] == 2) {
				$edit_btn = '<a title="Edit" href="transactions_manual.php?key=' . md5($row['transaction_id']) . '&m=' . $m1 . '&y=' . $dt[1] . '" class="table-actions btn-edit" id="' . $row['transaction_id'] . '"><i class="fa fa-pencil"></i></a>';
			} else {
				$edit_btn = '<a title="Edit" href="transactions.php?key=' . md5($row['transaction_id']) . '&m=' . $m1 . '&y=' . $dt[1] . '" class="table-actions btn-edit" id="' . $row['transaction_id'] . '"><i class="fa fa-pencil"></i></a>';
			}
			if ($booking == '1')
				$out_put .= "
			\t    <a title=\"Info\" href=\"#cancel_grn_popup\" class=\"table-actions show_info_popup\"  data-toggle=\"modal\" data-remarks=\"" . $remarks . '" data-createdby="' . $cancelled_by . '" data-createdat="' . $updated_at . '" id="' . $row['transaction_id'] . '" ><i class="fa fa-exclamation-circle"></i></a>
                    <a title="Edit" href="javascript:void(0)" class="table-actions btn-edits disable_action" id="' . $row['transaction_id'] . '" readonly><i class="fa fa-pencil"></i></a>
                    <a class="table-actions disable_action"  href="javascript:void(0)" ><i class="fa fa-print"></i></a>
                    <a class="table-actions disable_action" href="javascript:void(0)" data-status="' . $row['status'] . '" title="Invoice" id="' . $row['transaction_id'] . '" readonly><i class="fa fa-file"></i></a>
                    <a class="table-actions send_invoices disable_action" href="javascript:void(0)" title="Send Invoice" id="send_invoice_d" data-month="' . $m1 . '" data-year="' . $dt[1] . '" data-id="' . $row['transaction_id'] . '" > <i class="fa fa-envelope"></i></a>
                    <a title="Cancel" href="javascript:void(0) disable_action" class="table-actions cancel_booking disable_action" id="' . $row['transaction_id'] . '" ><i  class="fa fa-ban"></i></a>
                    <a title="E-way Attachments" href="javascript:void(0) disable_action" class="table-actions btn-eways disable_action" id="' . $row['transaction_id'] . '"><i class="fa fa-paperclip"></i></a>';
			else if ($consignment_mode == '3' || $status > 6)
				$out_put .= '<a title="Edit" href="javascript:void(0)" class="table-actions btn-edits disable_action" id="' . $row['transaction_id'] . '" readonly><i class="fa fa-pencil"></i></a>';
			else
				// $out_put .= '<a title="Edit" href="transactions.php?key=' . md5($row['transaction_id']) . '&m=' . $m1 . '&y=' . $dt[1] . '" class="table-actions btn-edit" id="' . $row['transaction_id'] . '"><i class="fa fa-pencil"></i></a>';
				$out_put .= $edit_btn . '<span class="table-actions dropdown"><i class="fa fa-print"></i>
						<ul class="dropdown-menu">
							<li><a href="transaction_pdf.php?month=' . $m1 . '&year=' . $y . '&id=' . $row['transaction_id'] . '&copy=consignor" data-status="' . $row['status'] . '" title="View" id="' . $row['transaction_id'] . '" target="_blank">Consignor GR</a></li>
							<li><a href="transaction_pdf.php?month=' . $m1 . '&year=' . $y . '&id=' . $row['transaction_id'] . '&copy=consignee" data-status="' . $row['status'] . '" title="View" id="' . $row['transaction_id'] . '" target="_blank">Consignee GR</a></li>
							<li><a href="transaction_pdf.php?month=' . $m1 . '&year=' . $y . '&id=' . $row['transaction_id'] . '&copy=pod" data-status="' . $row['status'] . '" title="View" id="' . $row['transaction_id'] . '" target="_blank">P.O.D GR</a></li>
							<li><a href="transaction_pdf.php?month=' . $m1 . '&year=' . $y . '&id=' . $row['transaction_id'] . '&copy=accounts" data-status="' . $row['status'] . '" title="View" id="' . $row['transaction_id'] . '" target="_blank">Accounts GR</a></li>
						</ul>
					</span>
                    <a class="table-actions " target="BLANK" href="gst_invoice_page.php?month=' . $m1 . '&year=' . $dt[1] . '&id=' . $row['transaction_id'] . '" data-status="' . $row['status'] . '" title="Invoice" id="' . $row['transaction_id'] . '"><i class="fa fa-file"></i></a>';
			if ($consignment_mode == '1' || $consignment_mode == '4') {
				$restricted = check_invoice_restricted($conn, $row['consignee']);
				$pay_at_book = 0;
			} else {
				$restricted = check_invoice_restricted($conn, $row['consigner']);
				$pay_at_book = 0;
			}
			if ($consignment_mode == '3') {
				$pay_at_book = 1;
			}
			if ($status == 8 && $restricted == 1 && $pay_at_book != 1) {
				$out_put .= '<a class="table-actions send_invoice" href="#" title="Send Invoice" id="send_invoice" data-month="' . $m1 . '" data-year="' . $dt[1] . '" data-id="' . $row['transaction_id'] . '" > <i class="fa fa-envelope"></i></a>';
			} else {
				$out_put .= '<a class="table-actions disable_action" href="javascript:void(0)" ><i class="fa fa-envelope"></i></a>';
			}
			if ($status < 6) {
				$out_put .= '<a title="Cancel" href="#cancel_grn_popup" class="table-actions cancel_booking" id="' . $row['transaction_id'] . '" data-toggle="modal" data-grnid="' . $row['grn_no'] . '" data-tabid="' . $trans_name . '"  ><i class="fa fa-ban "></i></a>';
			} else {
				$out_put .= '<a class="table-actions disable_action" href="javascript:void(0)"><i class="fa fa-ban"></i></a>';
			}
			$out_put .= '<a title="E-way Attachments" href="#eway_popup" class="table-actions btn-eway" data-toggle="modal" id="' . $row['transaction_id'] . '"><i class="fa fa-paperclip"></i></a>
					
				</div>
				
			</td>
		</tr>';

			$i++;
		}
		echo $out_put;
	} else
		echo "<tr><td colspan='9' style='padding:10px;text-align:center;font-size:17px;'> No Booking in this Month</td></tr>";
}
if ($cmd == 'get_vehicle_details') {
	$tbl_id = $_REQUEST['tbl_id'];

	$query = "select * from vehicle where vehicle_id='$tbl_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}

if ($cmd == 'get_hub_details') {
	$tbl_id = $_REQUEST['tbl_id'];

	$query = "select * from hub where hub_id='$tbl_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}
if ($cmd == 'get_role_details') {
	$tbl_id = $_REQUEST['tbl_id'];

	$query = "select * from role where role_id='$tbl_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}

if ($cmd == 'get_train_details') {
	$tbl_id = $_REQUEST['tbl_id'];

	$query = "select * from train where train_id='$tbl_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	$row['city_name1'] = get_city_name($conn, $row['loading_point1']);
	$row['city_name2'] = get_city_name($conn, $row['loading_point2']);
	$row['city_name3'] = get_city_name($conn, $row['loading_point3']);
	$row['city_name4'] = get_city_name($conn, $row['loading_point4']);
	echo json_encode($row);
}
if ($cmd == 'get_flight_details') {
	$tbl_id = $_REQUEST['tbl_id'];

	$query = "select * from flight where flight_id='$tbl_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	$row['city_name1'] = get_city_name($conn, $row['loading_point1']);
	$row['city_name2'] = get_city_name($conn, $row['loading_point2']);
	$row['city_name3'] = get_city_name($conn, $row['loading_point3']);
	$row['city_name4'] = get_city_name($conn, $row['loading_point4']);
	echo json_encode($row);
}
if ($cmd == 'get_amount_words') {
	$number = $_REQUEST['val'];
	// $number =  $_POST['rupees'];
	$no = (int) floor($number);
	$point = (int) round(($number - $no) * 100);
	$hundred = null;
	$digits_1 = strlen($no);
	$i = 0;
	$str = array();
	$words = array(
		'0' => '',
		'1' => 'one',
		'2' => 'two',
		'3' => 'three',
		'4' => 'four',
		'5' => 'five',
		'6' => 'six',
		'7' => 'seven',
		'8' => 'eight',
		'9' => 'nine',
		'10' => 'ten',
		'11' => 'eleven',
		'12' => 'twelve',
		'13' => 'thirteen',
		'14' => 'fourteen',
		'15' => 'fifteen',
		'16' => 'sixteen',
		'17' => 'seventeen',
		'18' => 'eighteen',
		'19' => 'nineteen',
		'20' => 'twenty',
		'30' => 'thirty',
		'40' => 'forty',
		'50' => 'fifty',
		'60' => 'sixty',
		'70' => 'seventy',
		'80' => 'eighty',
		'90' => 'ninety'
	);
	$digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
	while ($i < $digits_1) {
		$divider = ($i == 2) ? 10 : 100;
		$number = floor($no % $divider);
		$no = floor($no / $divider);
		$i += ($divider == 10) ? 1 : 2;

		if ($number) {
			$plural = (($counter = count($str)) && $number > 9) ? 's' : null;
			$hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
			$str[] = ($number < 21)
				? $words[$number]
					. ' ' . $digits[$counter] . $plural . ' ' . $hundred
				: $words[floor($number / 10) * 10]
					. ' ' . $words[$number % 10] . ' '
					. $digits[$counter] . $plural . ' ' . $hundred;
		} else
			$str[] = null;
	}
	$str = array_reverse($str);
	$result = implode('', $str);

	$points = ($point)
		? '' . $words[floor($point / 10) * 10] . ' '
			. $words[$point = $point % 10]
		: '';

	if ($points != '') {
		echo ucfirst($result) . 'Rupees  ' . $points . ' Paise Only';
	} else {
		echo ucfirst($result) . 'Rupees Only';
	}
}
if ($cmd == 'get_delivery_details') {
	$tbl_id = $_REQUEST['tbl_id'];

	$query = "select * from delivery_status where delivery_status_id='$tbl_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}
if ($cmd == 'get_city_details') {
	$tbl_id = $_REQUEST['tbl_id'];

	$query = "select * from city where city_id='$tbl_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}
if ($cmd == 'get_state_details') {
	$tbl_id = $_REQUEST['tbl_id'];

	$query = "select * from state where state_id='$tbl_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}
if ($cmd == 'get_city_name') {
	$state_id = $_REQUEST['state_id'];

	$out_put .= '<option value="">Select City</option>';
	$query = "select * from city where status=0 and state='" . $state_id . "' order by city_name";
	$result = mysqli_query($conn, $query);
	while ($row = mysqli_fetch_array($result)) {
		$out_put .= '<option value=' . $row['city_id'] . '>' . $row['city_name'] . '</option>';
	}
	echo $out_put;
}
if ($cmd == 'get_mode_details') {
	$tbl_id = $_REQUEST['tbl_id'];
	$query = "select * from mode_of_transportation where mode_id='$tbl_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}

if ($cmd == 'get_client_user_details') {
	$tbl_id = $_REQUEST['tbl_id'];
	/* $query = "select  * from client_branch where client_branch_id IN (select branch_name from users where user_id='$tbl_id')"; */
	$query = "select * from client where client_id =(select company_name from users where user_id='$tbl_id')";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_assoc($result);
	$row['state_name'] = get_statename($conn, $row['state']);
	$row['city_name'] = get_city_name($conn, $row['city']);
	echo json_encode($row);
}

if ($cmd == 'get_client_details') {
	$tbl_id = $_REQUEST['tbl_id'];
	$query = "select * from client where client_id='$tbl_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_assoc($result);
	echo json_encode($row);
}

if ($cmd == 'get_grn_number') {
	$grn_type = isset($_REQUEST['grn_type']) ? $_REQUEST['grn_type'] : 'party';
	$company_id = isset($_REQUEST['company_id']) ? $_REQUEST['company_id'] : '';
	$client_id = isset($_REQUEST['client_id']) ? $_REQUEST['client_id'] : '';

	if ($grn_type == 'company') {
		// Fetch company code
		$company_query = mysqli_query($conn, 'SELECT company_code FROM company WHERE status=0 LIMIT 1');
		$company_row = mysqli_fetch_assoc($company_query);
		$company_code = isset($company_row['company_code']) ? $company_row['company_code'] : '';

		// Preview only — does NOT consume the sequence number
		$next_grn_id = peek_next_grn_id($conn, 'COMPANY');
		$grn_no = $company_code . sprintf('%04d', $next_grn_id);
	} else {
		// Fetch client billing code
		$query_code = mysqli_query($conn, "SELECT billing_code FROM client WHERE client_id='" . $client_id . "'");
		$r_code = mysqli_fetch_assoc($query_code);
		$billing_code = isset($r_code['billing_code']) ? $r_code['billing_code'] : '';

		// Preview only — does NOT consume the sequence number
		$next_grn_id = peek_next_grn_id($conn, $client_id);
		$grn_no = $billing_code . sprintf('%05d', $next_grn_id);
	}

	echo json_encode([
		'grn_no' => strtoupper($grn_no),
		'grn_id' => $next_grn_id
	]);
	exit;
}

if ($cmd == 'get_client_details_consignment') {
	$tbl_id = $_REQUEST['tbl_id'];
	$consignor = $_REQUEST['consignor'];
	if (!empty($consignor)) {
		$query_code = mysqli_query($conn, "select * from client where client_id='" . $tbl_id . "'");
		$r_code = mysqli_fetch_array($query_code);
		$billing_code = $r_code['billing_code'];
		$id = peek_next_grn_id($conn, $tbl_id);
		$grn_no = $billing_code . sprintf('%05d', $id);
		$grn_id = $id;
	} else {
		$grn_no = '';
		$grn_id = '';
	}
	$query = "select * from client where client_id='$tbl_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_assoc($result);
	$row['state'] = $row['state'] > 0 ? get_statename($conn, $row['state']) : '';
	$row['city_name'] = $row['city'] > 0 ? get_city_name($conn, $row['city']) : '';
	$row['grn_no'] = strtoupper($grn_no);
	$row['grn_id'] = $grn_id;
	echo json_encode($row);
}

if ($cmd == 'get_client') {
	$tbl_id = $_REQUEST['id'];
	$out_put = '<option value=""> -- Select Consignee</option>';

	$query = "select * from client where client_id!='$tbl_id' and client_id NOT IN (select client_id from customer_mapping_lists where mapping_id=(select mapping_id from customer_mapping where client='$tbl_id')) order by client_company_name";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	while ($row = mysqli_fetch_array($result)) {
		$out_put .= '<option value=' . $row['client_id'] . '>' . $row['client_company_name'] . '</option>';
	}
	echo $out_put;
}
if ($cmd == 'get_consignment_details') {
	$tbl_id = $_REQUEST['tbl_id'];
	$query = "select * from consignment_mode where consignment_id='$tbl_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}
if ($cmd == 'get_package_details') {
	$tbl_id = $_REQUEST['tbl_id'];
	$query = "select * from package where package_id='$tbl_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}
if ($cmd == 'get_consignee') {
	$tbl_id = $_REQUEST['id'];
	$out_put = '<option value="">--Select Consignee--</option>';

	$city_query = "select * from customer_mapping_lists where mapping_id IN (select mapping_id from customer_mapping where client='" . $tbl_id . "') and status='0' order by client_company_name asc";
	$city_result = mysqli_query($conn, $city_query);
	while ($row = mysqli_fetch_array($city_result)) {
		$client = get_client($conn, $row['client_id']);

		$out_put .= '<option value=' . $row['client_id'] . '>' . $client['client_company_name'] . '</option>';
	}
	echo $out_put;
}

if ($cmd == 'get_consignee1') {
	$tbl_id = $_REQUEST['id'];
	$destination = $_REQUEST['destination'];
	$q = '';
	if ($destination != '')
		$q = " and city='$destination'";
	$out_put = '<option value="">--Select Consignee--</option>';

	$city_query = "select * from client where client_id IN (select client_id from customer_mapping_lists where mapping_id IN (select mapping_id from customer_mapping where client='" . $tbl_id . "') and status='0') $q order by client_company_name asc";
	$city_result = mysqli_query($conn, $city_query);
	while ($row = mysqli_fetch_array($city_result)) {
		$out_put .= '<option value=' . $row['client_id'] . '>' . $row['client_company_name'] . '</option>';
	}
	echo $out_put;
}

if ($cmd == 'get_consignor') {
	$tbl_id = $_REQUEST['id'];
	$out_put = '<option value="">--Select Consignor--</option>';

	$city_query = "select * from customer_mapping where mapping_id IN (select mapping_id from customer_mapping_lists where client_id='" . $tbl_id . "') and status='0'";
	$city_result = mysqli_query($conn, $city_query);
	while ($row = mysqli_fetch_array($city_result)) {
		$client = get_client($conn, $row['client']);

		$out_put .= '<option value=' . $row['client'] . '>' . $client['client_company_name'] . '</option>';
	}
	echo $out_put;
}

if ($cmd == 'get_destination') {
	$tbl_id = $_REQUEST['id'];
	$out_put = '<option value="">--Select Destination--</option>';
	$city_query = "select * from city where status=0 and city_id!='$tbl_id' order by city_name";
	$city_result = mysqli_query($conn, $city_query);
	while ($city_row = mysqli_fetch_array($city_result)) {
		$out_put .= '<option value=' . $city_row['city_id'] . '>' . $city_row['city_name'] . '</option>';
	}
	echo $out_put;
}

if ($cmd == 'get_destination_consignor') {
	$res_val = array();
	$tbl_id = $_REQUEST['id'];
	$out_put = '<option value="">--Select Destination--</option>';
	// $city_query = "select * from city where status=0 and city_id!='$tbl_id' order by city_name asc";
	$city_query = "SELECT * FROM city WHERE status=0 ORDER BY city_name ASC";
	$city_result = mysqli_query($conn, $city_query);
	while ($city_row = mysqli_fetch_array($city_result)) {
		$out_put .= '<option value=' . $city_row['city_id'] . '>' . $city_row['city_name'] . '</option>';
	}
	$res_val['destination'] = $out_put;

	$out_put = '<option value="">--Select Consignor--</option>';
	$city_query = "select * from client where status=0 and city='$tbl_id' order by client_company_name asc";
	$city_result = mysqli_query($conn, $city_query);
	while ($city_row = mysqli_fetch_array($city_result)) {
		$out_put .= '<option value=' . $city_row['client_id'] . '>' . $city_row['client_company_name'] . '</option>';
	}
	$res_val['consignor'] = $out_put;

	$out_put = '<option value="">--Select Vehicle--</option>';
	$hub_id = '';
	$q1 = mysqli_query($conn, "select hub_id from hub where FIND_IN_SET('$tbl_id',covered_cities)");
	while ($r1 = mysqli_fetch_array($q1)) {
		$hub_id .= $r1['hub_id'] . ',';
	}
	$hub_id = rtrim($hub_id, ',');
	$city_query = "select * from vehicle where status=0 and FIND_IN_SET('$hub_id',branch_id) order by vehicle_reg_no";
	$city_result = mysqli_query($conn, $city_query);
	while ($city_row = mysqli_fetch_array($city_result)) {
		$out_put .= '<option value=' . $city_row['vehicle_id'] . '>' . $city_row['vehicle_reg_no'] . '</option>';
	}
	$res_val['vehicle'] = $out_put;

	echo json_encode($res_val);
}

if ($cmd == 'get_pickup_report_details') {
	$out_put = '';
	extract($_REQUEST);
	$company_id = get_company($conn, $_SESSION['user_id']);
	if ($_SESSION['role'] == 'CL') {
		if ($report_type == 'MONTHLY') {
			$add_q = "and grn_date LIKE '%$month'";
			$dt = explode('-', $month);
			$y = $dt[1];
			$m = $dt[0];
		} else if ($report_type == 'YEARLY') {
			$add_q = "and grn_date LIKE '%$year'";
			$y = $year;
		} else {
			$add_q = "and grn_date='$date'";
			$dt = explode('-', $date);
			$y = $dt[2];
			$m = $dt[1];
		}
	} else {
		if ($report_type == 'MONTHLY') {
			$add_q = "grn_date LIKE '%$month'";
			$dt = explode('-', $month);
			$y = $dt[1];
			$m = $dt[0];
		} else if ($report_type == 'YEARLY') {
			$add_q = "grn_date LIKE '%$year'";
			$y = $year;
		} else {
			$add_q = "grn_date='$date'";
			$dt = explode('-', $date);
			$y = $dt[2];
			$m = $dt[1];
		}
	}

	if ($report_type != 'YEARLY') {
		if ($m < 4)
			$m1 = 1;
		else if (($m > 3) && ($m < 7))
			$m1 = 2;
		else if (($m > 6) && ($m < 10))
			$m1 = 3;
		else
			$m1 = 4;
	}

	if ($client_wise_report != '') {
		$add_q .= "and consigner='$client_wise_report'";
	}
	if ($consignee_wise_report != '') {
		$add_q .= "and consignee='$consignee_wise_report'";
	}
	if ($mode_of_trasport != '')
		$add_q .= " and mode_of_transportation='$mode_of_trasport'";
	if ($origin != '')
		$add_q .= " and origin='$origin'";
	if ($destination != '')
		$add_q .= " and destination='$destination'";
	if ($status != '')
		$add_q .= " and status='$status'";

	$i = 1;
	if ($_SESSION['role'] == 'CL') {
		if ($report_type != 'YEARLY') {
			$query = 'select * from transaction_' . $m1 . '_' . $y . " where consigner='" . $company_id . "' $add_q";
		} else {
			$query = 'SELECT * FROM transaction_1_' . $y . " where consigner='" . $company_id . "' $add_q UNION ALL SELECT * FROM transaction_2_" . $y . " where consigner='" . $company_id . "' $add_q UNION ALL SELECT * FROM transaction_3_" . $y . " where consigner='" . $company_id . "' $add_q UNION ALL SELECT * FROM transaction_4_" . $y . " where consigner='" . $company_id . "' $add_q";
		}
	} else {
		if ($report_type != 'YEARLY') {
			$query = 'select * from transaction_' . $m1 . '_' . $y . " where  $add_q";
		} else {
			$query = 'SELECT * FROM transaction_1_' . $y . " WHERE  $add_q UNION ALL SELECT * FROM transaction_2_" . $y . " WHERE  $add_q UNION ALL SELECT * FROM transaction_3_" . $y . " WHERE  $add_q UNION ALL SELECT * FROM transaction_4_" . $y . " WHERE  $add_q";
		}
	}
	$result = mysqli_query($conn, $query);

	$out_put .= '<style>tr { height: 30px; }</style>
	<table class="table table-bordered table-striped" id="report_table" style="width:100%">
	<thead>
	<th class="table-title" width="60px">S.No</th>
		<th class="table-title">GRN NO</th>
		<th class="table-title" width="100px">GRN Date</th>
		<th class="table-title" width="100px">Invoice No.</th>
		<th class="table-title"  width="80px">Weight</th>
		<th class="table-title" width="100px">No.of.Pkgs</th>
		<th class="table-title"  width="80px">Mode</th>
		<th class="table-title">Origin</th>
		<th class="table-title" >Consignor </th>
		<th class="table-title" >Consignee </th>
		<th class="table-title" width="120px">Destination</th>
		<th class="table-title" width="80px">Status</th>      
	</thead>
	<tbody id="get_month_details">';

	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$booking = $row['booking_status'];
			$grn_date = $row['grn_date'];
			$dat = explode('-', $grn_date);
			$y2 = $dat[2];
			if ($dat[1] < 4)
				$m2 = 1;
			else if (($dat[1] > 3) && ($dat[1] < 7))
				$m2 = 2;
			else if (($dat[1] > 6) && ($dat[1] < 10))
				$m2 = 3;
			else
				$m2 = 4;

			$query1 = 'select sum(no_of_pkge) as no_of_pkge,party_invoice_no,gross_weight from transaction_invoice_' . $m2 . '_' . $y2 . " where transaction_id='" . $row['transaction_id'] . "'";
			$result1 = mysqli_query($conn, $query1);
			$row1 = mysqli_fetch_array($result1);

			$out_put .= '<tr>
		<td class="text-center">' . $i . '</td>
		<td>' . $row['grn_no'] . '</td>
		<td>' . $row['grn_date'] . '</td>
		<td>' . $row1['party_invoice_no'] . '</td>
		<td>' . $row1['gross_weight'] . '</td>
		<td>' . $row1['no_of_pkge'] . '</td>
		<td>' . get_mode($conn, $row['mode_of_transportation']) . '</td>
		<td>' . get_city_name($conn, $row['origin']) . '</td>
		<td>' . get_client_name($conn, $row['consigner']) . '</td>
		<td>' . get_client_name($conn, $row['consignee']) . '</td>
		<td>' . get_city_name($conn, $row['destination']) . '</td>';
			if ($booking == '1') {
				$out_put .= '<td style="color:red;">Consignment Cancelled</td>';
			} else {
				$out_put .= '<td>' . get_trans_status($row['status']) . '</td>';
			}

			$out_put .= '</tr>';

			$i++;
		}
		$out_put .= '</tbody>
		</table>';
		echo $out_put;
	} else {
		echo '<tr>
		<td class="text-center" colspan="10"> No Records Found For this Search</td></tr>';
	}
}

if ($cmd == 'get_payment_report_details') {
	$report_type = $_REQUEST['report_type'];
	$client_wise_report = $_REQUEST['client_wise_report'];
	$month = $_REQUEST['month'];
	$date = $_REQUEST['date'];
	if ($report_type == 'MONTHLY') {
		$dates = $month;
		$timestamp = $dates;
		$timestamp = DateTime::createFromFormat('m-Y', $timestamp);
		$newDate = $timestamp->format('Y-m');
		$add_qw = "and created_at like '%$newDate%'";
	} else if ($report_type == 'DAILY') {
		$dates = $date;

		$timestamp = $dates;
		$timestamp = DateTime::createFromFormat('d-m-Y', $timestamp);
		$newDate = $timestamp->format('Y-m-d');
		$add_qw = "and created_at like '%$newDate%'";
	} else {
		$add_qw = '';
	}

	if ($client_wise_report != '') {
		$add_q .= "client_id='$client_wise_report'";
	}

	$query = "select * from razorpay_payment where client_id ='$client_wise_report' " . $add_qw . ' order by created_at desc';
	$result = mysqli_query($conn, $query);
	$i = 1;
	$out_put .= '<table id="dataTable1" class="table table-striped table-bordered display" style="width:100%">
           <thead>
                <tr>
                     <th class="table-title" >S.No</th>
                     <th class="table-title">Payment Date</th>
                     <th class="table-title">GRN No</th>
                     <!-- <th class="table-title">Order ID</th> -->
                     <th class="table-title">Payment ID</th>
                     <th class="table-title">Invoice Amount</th>
                     <th class="table-title">Paid Amount</th>
                     <th class="table-title">Due Amount</th>
                     <th class="table-title">Status</th>

                </tr>
           </thead>
           <tbody>';
	if (mysqli_num_rows($result) > 0) {
		$total_invoice_amt = 0;
		$total_paid_amt = 0;
		$total_due_amt = 0;
		while ($row = mysqli_fetch_array($result)) {
			$timestamp = $row['created_at'];
			$timestamp = DateTime::createFromFormat('Y-m-d H:i:s', $timestamp);
			$newDate = $timestamp->format('d-m-Y H:i:s');
			$total_invoice_amt += $row['amount'];
			$total_paid_amt += $row['paid'];
			$total_due_amt += $row['balance'];
			$out_put .= '<tr>
           <td class="text-center">' . $i . '</td>
           <td>' . $newDate . '</td>
           <td>' . $row['grn_no'] . '</td>
           <td>' . $row['razorpayPaymentId'] . '</td>
           <td>&#x20b9;' . number_format($row['amount'], 2, '.', '') . '</td>
           <td>&#x20b9;' . number_format($row['paid'], 2, '.', '') . '</td>
           <td>&#x20b9;' . number_format($row['balance'], 2, '.', '') . '</td>
           <td>' . $row['paymentStatus'] . '</td>';

			$output .= '
       </tr>
       ';

			$i++;
		}
		$out_put .= '</tbody>
       <tfoot style="color:#0A1E3D">
                             <tr>
                                  <th colspan="3"></th>
                                  <th >Total</th>
                                  <th>&#x20b9;' . number_format($total_invoice_amt, 2, '.', '') . '</th>
                                  <th>&#x20b9;' . number_format($total_paid_amt, 2, '.', '') . '</th>
                                  <th>&#x20b9;' . number_format($total_due_amt, 2, '.', '') . '</th>
                                  <th></th>
                             </tr>
                        </tfoot>

       </table>';
	} else {
		$out_put1 .= '<table id="employee_data" class="table table-striped table-bordered display" style="width:100%">
       <thead>
                <tr>
                     <th>S.No</th>
                     <th>Payment Date</th>
                     <th>GRN No</th>
                     <!-- <th>Order ID</th> -->
                     <th>Payment ID</th>
                     <th>Invoice Amount</th>
                     <th>Paid Amount</th>
                     <th>Due Amount</th>
                     <th>Status</th>

                </tr>
           </thead>
           <tbody>
       <tr><td colspan="9" style="padding:10px;text-align:center;font-size:17px;"> No Transactions in this Month</td></tr>
       </tbody>
       </table>';
	}

	echo $out_put;
}

// if ($cmd == "get_grn_for_status") {
// 	$out_put = '';
// 	$i = 1;
// 	$count = 1;
// 	$res_val = array();
// 	$grn_no = $_REQUEST['grn_no'];
// 	$status = $_REQUEST['status'];
// 	$slno = $_REQUEST['slno'];
// 	$query2 = "SELECT * FROM transaction_tbls";
// 	$result2 = mysqli_query($conn, $query2) or die(mysqli_error($conn));
// 	while ($row2 = mysqli_fetch_assoc($result2)) {

// 		$query = "select * from transaction_" . $row2['table_name'] . " where grn_no='$grn_no' and status < '$status' and booking_status = '' ";

// 		$result = mysqli_query($conn, $query);
// 		if (mysqli_num_rows($result) > 0) {
// 			$count++;
// 			while ($row = mysqli_fetch_array($result)) {
// 				$query1 = "select sum(no_of_pkge) as no_of_pkge from transaction_invoice_" . $row2['table_name'] . " where transaction_id='" . $row['transaction_id'] . "'";
// 				$result1 = mysqli_query($conn, $query1);
// 				$row1 = mysqli_fetch_array($result1);

// 				$out_put .= '<tr>
// 		<td class="text-center">' . $slno . '</td>
// 		<td><input type="hidden" name="grn_id[]" class="grn_id" id="grn_id_' . $i . '" value="' . $row['grn_id'] . '" />' . $row['grn_no'] . '</td>
// 		<td><input type="hidden" name="client_id[]" class="client_id" id="client_id_' . $i . '" value="' . $row['client_id'] . '" />' . $row['grn_date'] . '</td>
// 		<td>' . $row1['no_of_pkge'] . '</td>
// 		<td>' . get_mode($conn, $row['mode_of_transportation']) . '</td>
// 		<td>' . get_client_name($conn, $row['consigner']) . '-' . get_city_name($conn, $row['origin']) . '</td>
// 		<td>' . get_client_name($conn, $row['consignee']) . '-' . get_city_name($conn, $row['destination']) . '</td>
// 		<td>' . get_trans_status($row['status']) . '</td>
// 		<td style="text-align:  center;"><button class="btn btn-danger delete" type="button" data-id="' . $row['grn_id'] . '">Remove</button></td>
// 		</tr>';

// 				$i++;
// 			}
// 		}
// 	}
// 	$res_val['data'] = $out_put;
// 	$res_val['status'] = 0;

// 	if ($count == 1) {
// 		$res_val['data'] = "GRN No Not Match / Booking Cancelled";
// 		$res_val['status'] = 1;
// 	}

// 	echo json_encode($res_val);
// }

if ($cmd == 'get_grn_for_status') {
	$out_put = '';
	$i = 1;
	$found = false;  // ← was: $count = 1 (bug: started at 1, confused 0-matches with 1-match)
	$res_val = array();
	$grn_no = $_REQUEST['grn_no'];
	$status = $_REQUEST['status'];
	$slno = $_REQUEST['slno'];

	$query2 = 'SELECT * FROM transaction_tbls';
	$result2 = mysqli_query($conn, $query2) or die(mysqli_error($conn));

	while ($row2 = mysqli_fetch_assoc($result2)) {
		$query = 'SELECT * FROM transaction_' . $row2['table_name']
			. " WHERE grn_no='$grn_no' AND status < '$status' AND booking_status = ''";
		$result = mysqli_query($conn, $query);

		if (mysqli_num_rows($result) > 0) {
			$found = true;  // ← clear boolean flag
			while ($row = mysqli_fetch_array($result)) {
				$query1 = 'SELECT SUM(no_of_pkge) AS no_of_pkge FROM transaction_invoice_'
					. $row2['table_name'] . " WHERE transaction_id='" . $row['transaction_id'] . "'";
				$result1 = mysqli_query($conn, $query1);
				$row1 = mysqli_fetch_array($result1);

				$out_put .= '<tr>
                    <td class="text-center">' . $slno . '</td>
                    <td><input type="hidden" name="grn_id[]" class="grn_id" value="' . $row['grn_id'] . '" />' . $row['grn_no'] . '</td>
                    <td><input type="hidden" name="client_id[]" class="client_id" value="' . $row['client_id'] . '" />' . $row['grn_date'] . '</td>
                    <td>' . $row1['no_of_pkge'] . '</td>
                    <td>' . get_mode($conn, $row['mode_of_transportation']) . '</td>
                    <td>' . get_client_name($conn, $row['consigner']) . '-' . get_city_name($conn, $row['origin']) . '</td>
                    <td>' . get_client_name($conn, $row['consignee']) . '-' . get_city_name($conn, $row['destination']) . '</td>
                    <td>' . get_trans_status($row['status']) . '</td>
                    <td style="text-align:center;">
                        <button class="btn btn-danger delete" type="button" data-id="' . $row['grn_id'] . '">Remove</button>
                    </td>
                </tr>';
				$i++;
			}
		}
	}

	$res_val['data'] = $out_put;
	$res_val['status'] = 0;

	if (!$found) {  // ← was: if ($count == 1) — broken when count was never reset
		$res_val['data'] = 'GRN No Not Match / Booking Cancelled';
		$res_val['status'] = 1;
	}

	echo json_encode($res_val);
}

if ($cmd == 'get_all_bookings_for_status') {
	$res_val = array();
	$filter_status = isset($_REQUEST['filter_status']) ? trim($_REQUEST['filter_status']) : '';

	if ($filter_status === '') {
		$res_val['status'] = 1;
		$res_val['message'] = 'Please select a status to search.';
		echo json_encode($res_val);
		exit;
	}

	$filter_status = (int) $filter_status;
	$rows_out = array();

	$query2 = 'SELECT * FROM transaction_tbls';
	$result2 = mysqli_query($conn, $query2);

	if (!$result2) {
		$res_val['status'] = 1;
		$res_val['message'] = 'Failed to load. Please try again.';
		echo json_encode($res_val);
		exit;
	}

	while ($row2 = mysqli_fetch_assoc($result2)) {
		$table_name = $row2['table_name'];
		$trans_table = 'transaction_' . $table_name;
		$invoice_table = 'transaction_invoice_' . $table_name;

		$query = "SELECT * FROM $trans_table
			\t   WHERE status = '$filter_status'
			\t   AND booking_status = ''
			\t   ORDER BY grn_date DESC, grn_no DESC";

		$result = mysqli_query($conn, $query);

		// If a monthly table doesn't exist yet, skip it silently rather than failing the whole request
		if (!$result) {
			continue;
		}

		while ($row = mysqli_fetch_array($result)) {
			$pkg_query = "SELECT SUM(no_of_pkge) AS pkgs, SUM(gross_weight) AS weight, GROUP_CONCAT(DISTINCT party_invoice_no SEPARATOR ', ') AS invoice_no
					\t   FROM $invoice_table WHERE transaction_id='" . $row['transaction_id'] . "'";
			$pkg_result = mysqli_query($conn, $pkg_query);
			$pkg_row = $pkg_result ? mysqli_fetch_assoc($pkg_result) : array();

			$rows_out[] = array(
				'transaction_id' => $row['transaction_id'],
				'grn_id' => $row['grn_id'],
				'grn_no' => $row['grn_no'],
				'grn_date' => $row['grn_date'],
				// 'invoice_no'     => isset($pkg_row['invoice_no']) ? $pkg_row['invoice_no'] : '',
				'weight' => isset($pkg_row['weight']) ? $pkg_row['weight'] : '',
				'pkgs' => isset($pkg_row['pkgs']) ? $pkg_row['pkgs'] : '',
				'mode' => get_mode($conn, $row['mode_of_transportation']),
				'origin' => get_city_name($conn, $row['origin']),
				'consignor' => get_client_name($conn, $row['consigner']),
				'consignee' => get_client_name($conn, $row['consignee']),
				'destination' => get_city_name($conn, $row['destination']),
				'current_status' => get_trans_status($row['status']),
				'client_id' => $row['client_id'],
				'tab_name' => $table_name,
			);
		}
	}

	$res_val['status'] = 0;
	$res_val['data'] = $rows_out;
	echo json_encode($res_val);
	exit;
}

// Arrival Report
if ($cmd == 'get_arrival_report_details') {
	$out_put = '';
	extract($_REQUEST);
	$company_id = get_company($conn, $_SESSION['user_id']);
	if ($report_type == 'MONTHLY') {
		$add_q = "and grn_date LIKE '%$month'";
		$dt = explode('-', $month);
		$y = $dt[1];
		$m = $dt[0];
	} else {
		$add_q = "and grn_date='$date'";
		$dt = explode('-', $date);
		$y = $dt[2];
		$m = $dt[1];
	}
	if ($m < 4)
		$m1 = 1;
	else if (($m > 3) && ($m < 7))
		$m1 = 2;
	else if (($m > 6) && ($m < 10))
		$m1 = 3;
	else
		$m1 = 4;

	if ($mode_of_trasport != '')
		$add_q .= " and mode_of_transportation='$mode_of_trasport'";
	if ($origin != '')
		$add_q .= " and origin='$origin'";
	if ($destination != '')
		$add_q .= " and destination='$destination'";
	if ($status != '')
		$add_q .= " and status='$status'";

	$i = 1;
	echo $query = 'select * from transaction_' . $m1 . '_' . $y . " where consignee='" . $company_id . "' $add_q";
	$result = mysqli_query($conn, $query);
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$query1 = 'select sum(no_of_pkge) as no_of_pkge,party_invoice_no,gross_weight from transaction_invoice_' . $m1 . '_' . $y . " where transaction_id='" . $row['transaction_id'] . "'";
			$result1 = mysqli_query($conn, $query1);
			$row1 = mysqli_fetch_array($result1);

			$out_put .= '<tr>
		<td class="text-center">' . $i . '</td>
		<td>' . $row['grn_no'] . '</td>
		<td>' . $row['grn_date'] . '</td>
		<td>' . $row1['party_invoice_no'] . '</td>
		<td>' . $row1['gross_weight'] . '</td>
		<td>' . $row1['no_of_pkge'] . '</td>
		<td>' . get_mode($conn, $row['mode_of_transportation']) . '</td>
		<td>' . get_city_name($conn, $row['origin']) . '</td>
		<td>' . get_client_name($conn, $row['consigner']) . '</td>
		<td>' . get_client_name($conn, $row['consignee']) . '</td>
		<td>' . get_city_name($conn, $row['destination']) . '</td>
		<td>' . get_trans_status($row['status']) . '</td>
		</tr>';

			$i++;
		}
		echo $out_put;
		echo $query;
	} else
		echo '<tr>
		<td class="text-center" colspan="10"> No Records Found For this Search</td></tr>';
}

if ($cmd == 'get_gracious_branch') {
	$out_put = '<option value="">--Select Branch--</option>';
	$city_query = 'select * from branch where status=0 order by branch_name';
	$city_result = mysqli_query($conn, $city_query);
	while ($city_row = mysqli_fetch_array($city_result)) {
		$out_put .= '<option value=' . $city_row['branch_id'] . '>' . $city_row['branch_name'] . '</option>';
	}
	echo $out_put;
}

if ($cmd == 'get_client_branch') {
	$id = $_REQUEST['id'];
	$out_put = '<option value="">--Select Branch--</option>';
	$city_query = "select * from client_branch where status=0 and company_id='$id' order by branch_name";
	$city_result = mysqli_query($conn, $city_query);
	while ($city_row = mysqli_fetch_array($city_result)) {
		$out_put .= '<option value=' . $city_row['client_branch_id'] . '>' . $city_row['branch_name'] . '</option>';
	}
	echo $out_put;
}

if ($cmd == 'get_existing_attchment') {
	$table_name = $_REQUEST['table_name'];
	$transaction_id = $_REQUEST['transaction_id'];
	$out_put = '<div style="border: 1px solid;"><br>';

	$query = "select * from $table_name where transaction_id='$transaction_id'";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	while ($row = mysqli_fetch_array($result)) {
		$out_put .= '<div class="col-md-offset-1 col-md-5">
<label class="control-label">Eway Bill No ' . $row['eway_bill_no'] . ":</label>
		\t  \t\t<label class=\"control-label\">Date Of Issue " . date('d-m-Y', strtotime($row['issue_date'])) . ":</label>
		\t  \t\t<label class=\"control-label\">Date Of Expire " . date('d-m-Y', strtotime($row['expire_date'])) . ":</label>
		\t  \t</div>
		\t  \t<div class=\"col-md-offset-1 col-md-5\">
		\t  \t\t<label class=\"control-label\">E-way Attachments:</label><br>
		\t  \t\t<label class=\"control-label\"> " . $row['attachment'] . ' <a href="eway/' . $row['attachment'] . '" target="BLANK" ><img src="images/Pdf1.png" id="eway_image_src" data-val="' . $row['attachment'] . "\" width=\"20px\" /> </a> </label>  </br>
</br></br>
		\t  \t</div>";
	}

	$out_put .= '<div class="modal-footer" style="text-align: center;">
				<button class="btn btn-primary btn-new"  type="button" id="new_eway">Add New</button></div>

</div>';

	if (mysqli_num_rows($result) > 0)
		echo $out_put;
	else
		echo 0;
}

if ($cmd == 'get_existing_invoice_attchment') {
	$table_name = $_REQUEST['table_name'];
	$transaction_id = $_REQUEST['transaction_id'];
	$out_put = '<div style="border: 1px solid;"><br>';

	$query = "select * from $table_name where transaction_id='$transaction_id' and status=0";
	$result = mysqli_query($conn, $query) or die(mysqli_error($conn));
	while ($row = mysqli_fetch_array($result)) {
		$out_put .= "

		\t  \t<div class=\"col-md-offset-1 col-md-5\">
		\t  \t\t<label class=\"control-label\">Invoice Attachments:</label><br>
		\t  \t\t<label class=\"control-label\"> " . $row['attachment'] . ' <a href="invoice_image/' . $row['attachment'] . '" target="BLANK" ><img src="images/Pdf1.png" id="eway_image_src" data-val="' . $row['attachment'] . "\" width=\"20px\" /> </a> </label>  </br>
</br></br>
		\t  \t</div>";
	}

	$out_put .= '<div class="modal-footer" style="text-align: center;">
				</div>

</div>';

	if (mysqli_num_rows($result) > 0)
		echo $out_put;
	else
		echo 0;
}

if ($_GET['cmd'] == 'get_pod_attachment') {
	$grn_no = mysqli_real_escape_string($conn, $_GET['grn_no']);
	$q = mysqli_query($conn, "SELECT screens FROM pod_files WHERE screens LIKE '%$grn_no%'");

	$files = array();
	while ($row = mysqli_fetch_assoc($q)) {
		foreach (explode('@@', $row['screens']) as $f) {
			// only keep files that actually belong to this GRN (prefix match)
			if (stripos($f, $grn_no) === 0) {
				$files[] = $f;
			}
		}
	}
	$files = array_unique($files);

	if (count($files) > 0) {
		foreach ($files as $file) {
			echo '<img src="../pod_uploads/' . htmlspecialchars($file) . '" 
                       style="max-width:100%;margin-bottom:15px;border:1px solid #ddd;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.08);" />';
		}
	} else {
		echo '<p style="color:#6b7280;">No POD uploaded for this GRN.</p>';
	}
	exit;
}

if ($cmd == 'get_notification') {
	// echo "hello";
	if (isset($_POST['view'])) {
		if ($_POST['view'] != '') {
			$update_inquiry_sts = mysqli_query($conn, 'UPDATE user_inquiry_list set status=1 where status=0');
		}
		$get_inq_data = mysqli_query($conn, 'select *from user_inquiry_list order by id DESC LIMIT 5');
		$output = '';
		if (mysqli_num_rows($get_inq_data) > 0) {
			while ($row_notify = mysqli_fetch_assoc($get_inq_data)) {
				$output .= '<li>
				<a href="user-inquiry.php">
				<strong>' . $row_notify['consignor_name'] . '</strong><br/>
				<small><em>' . $row_notify['booking_id'] . '</em></small><br/>
				<small><em>User Inquiry Page</em></small>
				</a>
				</li>
				';
			}
		} else {
			$output .= "<li><a href=\"#\" class=\"text-bold text-italic\">No Notification
		\t Found</a></li>";
		}
		$status = mysqli_query($conn, 'SELECT *FROM `user_inquiry_list` where status = 0');
		$count = mysqli_num_rows($status);

		$data = array(
			'notification' => $output,
			'unseen_notification' => $count
		);
		echo json_encode($data);
	}
}
if ($cmd == 'get_notification_rfp') {
	// echo "hello";
	if (isset($_POST['view'])) {
		// echo "view";

		if ($_POST['view'] != '') {
			// echo "view1";
			$update_rfp_sts = mysqli_query($conn, 'UPDATE user_pickup set `status`=1 where `status`=3');
		}
		$get_rfp_data = mysqli_query($conn, 'select *from user_pickup order by pickup_id DESC LIMIT 5');
		$output = '';
		if (mysqli_num_rows($get_rfp_data) > 0) {
			while ($row_notify1 = mysqli_fetch_assoc($get_rfp_data)) {
				$output .= '<li>
				<a href="user-requestpickup-list.php">
				<strong>' . $row_notify1['consignor_name'] . '</strong><br/>
				<small><em>' . $row_notify1['pickup_ref_id'] . '</em></small><br/>
				<small><em>RFP Page</em></small>
				</a>
				</li>
				';
			}
		} else {
			$output .= "<li><a href=\"#\" class=\"text-bold text-italic\">No Notification
		\t Found</a></li>";
		}
		$status = mysqli_query($conn, 'SELECT *FROM `user_pickup` where status = 3');
		$count = mysqli_num_rows($status);

		$data = array(
			'notification' => $output,
			'unseen_notification' => $count
		);
		echo json_encode($data);
	}
}

if ($cmd == 'check_consginor_invoice_no') {
	// print_r("CHECK");
	$all_party_invoice = str_replace(["'", '"'], '', $_REQUEST['all_party_invoice']);
	$conr_id = $_REQUEST['conr_id'];

	$all_Tables = $getDatas->query('SELECT * FROM transaction_tbls', 2);

	$transaction_ = [];
	$transaction_invoice_ = [];
	$send_value = [];
	$send_value_check = [];

	for ($i = 0; $i < count($all_party_invoice); $i++) {
		$inv_value = trim($all_party_invoice[$i], "'");

		if ($inv_value != '' && $inv_value != null) {
			if (count($all_Tables) > 0) {
				for ($t = 0; $t < count($all_Tables); $t++) {
					$transaction_[$t] = 'transaction_' . $all_Tables[$t]['table_name'];
					$transaction_invoice_[$t] = 'transaction_invoice_' . $all_Tables[$t]['table_name'];
					$find_all = $getDatas->query("SELECT count(transaction_id) as inv_count FROM `$transaction_[$t]` WHERE consigner = '$conr_id' and EXISTS (
				\t SELECT * FROM `$transaction_invoice_[$t]` WHERE transaction_id = $transaction_[$t].transaction_id and party_invoice_no = '$inv_value'
				\t   )", 2);
					if ($find_all[0]['inv_count'] == 0) {
						$send_value_check[$i][$t] = 'NO';
					} else {
						$send_value_check[$i][$t] = 'YES';
					}
				}
			}

			if (in_array('YES', $send_value_check[$i])) {
				$send_value[$i] = 'YES';
			} else {
				$send_value[$i] = 'NO';
			}
		} else {
			$send_value[$i] = 'EMPTY';
		}
	}
	echo json_encode($send_value);
}
if ($cmd == 'client_charges_auto_fetch') {
	$consinee_dec_id = $_REQUEST['consinee_dec_id'];
	$consignor_get_id = $_REQUEST['consignor_get_id'];
	$consignee__get_id = $_REQUEST['consignee__get_id'];

	$all_details = $getDatas->query("SELECT * FROM consignor_payment where consigner_id = '$consignor_get_id' and destination = '$consinee_dec_id' ", 2);
	if (count($all_details) > 0) {
		echo json_encode($all_details[0]);
	} else {
		echo json_encode('No_Destination');
	}
}
if ($cmd == 'get_company_user_mail') {
	$user_id = $_REQUEST['comp_name_val'];
	// echo  $user_id;
	$query = "SELECT email FROM `client` where client_id = $user_id";
	$result = mysqli_query($conn, $query);
	$user_email = mysqli_fetch_assoc($result);
	echo $user_email['email'];
}

if ($cmd == 'get_transact_status_month_detail') {
	$out_put = '';
	$month = $_REQUEST['month'];
	$dt = explode('-', $month);

	if ($dt[0] <= 3) {
		$m = 4;
		$m1 = 1;
		$y = $dt[1] - 1;
		$trans_name = 'transaction_' . $m1 . '_' . $dt[1];
		$trans_image_name = 'transaction_images_' . $m1 . '_' . $dt[1];
		$trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $dt[1];
	} else if (($dt[0] >= 4) && ($dt[0] <= 6)) {
		$m = 1;
		$m1 = 2;
		$y = $dt[1];
		$trans_name = 'transaction_' . $m1 . '_' . $dt[1];
		$trans_image_name = 'transaction_images_' . $m1 . '_' . $dt[1];
		$trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $dt[1];
	} else if (($dt[0] >= 7) && ($dt[0] <= 9)) {
		$m = 2;
		$m1 = 3;
		$y = $dt[1];
		$trans_name = 'transaction_' . $m1 . '_' . $dt[1];
		$trans_image_name = 'transaction_images_' . $m1 . '_' . $dt[1];
		$trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $dt[1];
	} else {
		$m = 3;
		$m1 = 4;
		$y = $dt[1];
		$trans_name = 'transaction_' . $m1 . '_' . $dt[1];
		$trans_image_name = 'transaction_images_' . $m1 . '_' . $dt[1];
		$trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $dt[1];
	}
	if ($_SESSION['role'] == 'AD') {
		$query = 'select * from transaction_' . $m1 . '_' . $dt[1] . " where grn_date like '%$month' and invoice_no !='' order by grn_date desc,grn_no desc";
	} else {
		$query = 'select * from transaction_' . $m1 . '_' . $dt[1] . " where consigner='" . $_SESSION['company_id'] . "' or consignee='" . $_SESSION['company_id'] . "' and grn_date like '%$month' and invoice_no !='' order by grn_date desc,grn_no desc";
	}
	$result = mysqli_query($conn, $query);
	$i = 1;
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$booking = $row['booking_status'];
			$consignment_mode = $row['mode_of_consignment'];
			$status = $row['status'];
			$remarks = $row['remarks'];
			$cancelled_by = get_user($conn, $row['cancelled_by']);
			$updated_at = $row['updated_at'];

			$pkg_q = mysqli_query($conn, 'select sum(no_of_pkge) as pkge from transaction_invoice_' . $m1 . '_' . $dt[1] . " where transaction_id='" . $row['transaction_id'] . "'");
			$pkg_r = mysqli_fetch_array($pkg_q);

			$out_put .= '<tr>
			<td class="text-center">' . $i . '</td>
			<td>' . $row['grn_no'] . '</td>
			<td>' . $row['grn_date'] . '</td>
			<td>' . $pkg_r['pkge'] . '</td>
			<td>' . get_client_name($conn, $row['consigner']) . ' ' . $restricted_sign . ' ' . $frequency_sign . ' ' . $charges_sign . '</td>
			<td>' . get_client_name($conn, $row['consignee']) . ' ' . $restricted_symbol . ' ' . $frequency_symbol . ' ' . $charges_symbol . '</td>
			<td>' . get_city_name($conn, $row['destination']) . '</td>';
			if ($booking == '1') {
				$out_put .= '<td style="color:red;">Consignment Cancelled</td>';
			} else {
				$out_put .= '<td>' . get_trans_status($row['status']) . '</td>';
			}

			$status_row = $row['status'];
			$grn_no_row = $row['grn_no'];
			$grn_id_row = $row['grn_id'];
			$transaction_id_row = $row['transaction_id'];

			if ($status_row >= 2 || $booking == '1') {
				$disabled2 = 'disabled';
			} else {
				$disabled2 = "id='status_popup'";
			}
			if ($status_row >= 3 || $booking == '1') {
				$disabled3 = 'disabled';
			} else {
				$disabled3 = "id='status_popup'";
			}
			if ($status_row >= 4 || $booking == '1') {
				$disabled4 = 'disabled';
			} else {
				$disabled4 = "id='status_popup'";
			}
			if ($status_row >= 5 || $booking == '1') {
				$disabled5 = 'disabled';
			} else {
				$disabled5 = "id='status_popup'";
			}
			if ($status_row >= 6 || $booking == '1') {
				$disabled6 = 'disabled';
			} else {
				$disabled6 = "id='status_popup'";
			}
			if ($status_row >= 7 || $booking == '1') {
				$disabled7 = 'disabled';
			} else {
				$disabled7 = "id='status_popup'";
			}
			if ($status_row >= 8 || $booking == '1') {
				$disabled8 = 'disabled';
			} else {
				$disabled8 = "id='status_popup'";
			}

			if ($status_row >= 2) {
				$button_text2 = '<i class="fa fa-check"></i>';
			} else {
				$button_text2 = 2;
			}
			if ($status_row >= 3) {
				$button_text3 = '<i class="fa fa-check"></i>';
			} else {
				$button_text3 = 3;
			}
			if ($status_row >= 4) {
				$button_text4 = '<i class="fa fa-check"></i>';
			} else {
				$button_text4 = 4;
			}
			if ($status_row >= 5) {
				$button_text5 = '<i class="fa fa-check"></i>';
			} else {
				$button_text5 = 5;
			}
			if ($status_row >= 6) {
				$button_text6 = '<i class="fa fa-check"></i>';
			} else {
				$button_text6 = 6;
			}
			if ($status_row >= 7) {
				$button_text7 = '<i class="fa fa-check"></i>';
			} else {
				$button_text7 = 7;
			}
			if ($status_row >= 8) {
				$button_text8 = '<i class="fa fa-check"></i>';
			} else {
				$button_text8 = 8;
			}

			$color_button = ($booking == 1) ? 'style="color:red !important;"' : '';

			$out_put .= '<td class="actions center-content ">
				<div>
					<button ' . $color_button . ' class="border booked" disabled title="Consignment Booked"><i class="fa fa-check"></i></button>&nbsp;
					<button ' . $color_button . ' class="border picked-up" ' . $disabled2 . ' data-status="2" data-tabid="' . $trans_name . '" data-grnid="' . $grn_id_row . '" data-grnno="' . $grn_no_row . '" data-consignment="' . $transaction_id_row . '" title="Consignment Picked Up">' . $button_text2 . '</button>&nbsp;
					<button ' . $color_button . ' class="border transit-1"  ' . $disabled3 . ' data-status="3" data-tabid="' . $trans_name . '" data-grnid="' . $grn_id_row . '" data-grnno="' . $grn_no_row . '" data-consignment="' . $transaction_id_row . '" title="In Transit-1">' . $button_text3 . '</button>&nbsp;
					<button ' . $color_button . ' class="border transit-2"  ' . $disabled4 . ' data-status="4" data-tabid="' . $trans_name . '" data-grnid="' . $grn_id_row . '" data-grnno="' . $grn_no_row . '" data-consignment="' . $transaction_id_row . '" title="In Transit-2">' . $button_text4 . '</button>&nbsp;
					<button ' . $color_button . ' class="border transit-3"  ' . $disabled5 . ' data-status="5" data-tabid="' . $trans_name . '" data-grnid="' . $grn_id_row . '" data-grnno="' . $grn_no_row . '" data-consignment="' . $transaction_id_row . '" title="In Transit-3">' . $button_text5 . '</button>&nbsp;
					<button ' . $color_button . ' class="border destination"  ' . $disabled6 . ' data-status="6" data-tabid="' . $trans_name . '" data-grnid="' . $grn_id_row . '" data-grnno="' . $grn_no_row . '" data-consignment="' . $transaction_id_row . '" title="At Destination">' . $button_text6 . '</button>&nbsp;
					<button ' . $color_button . ' class="border out-delivery"  ' . $disabled7 . ' data-status="7" data-tabid="' . $trans_name . '" data-grnid="' . $grn_id_row . '" data-grnno="' . $grn_no_row . '" data-consignment="' . $transaction_id_row . '" title="Out For Delivery">' . $button_text7 . '</button>&nbsp;
					<button ' . $color_button . ' class="border delivered"  ' . $disabled8 . ' data-status="8" data-tabid="' . $trans_name . '" data-grnid="' . $grn_id_row . '" data-grnno="' . $grn_no_row . '" data-consignment="' . $transaction_id_row . '" title="Delivered Successfully">' . $button_text8 . '</button>
				</div>
			</td>
		</tr>';

			$i++;
		}
		echo $out_put;
	} else
		echo "<tr><td colspan='9' style='padding:10px;text-align:center;font-size:17px;'> No Booking in this Month</td></tr>";
}

// check manual grn duplicate
if ($cmd == 'check_grn_manual') {
	$grn_id_manual = $_POST['grn_id_manual'];

	$query = 'SELECT * FROM transaction_tbls';
	$results = mysqli_query($conn, $query);
	$response = 0;
	while ($rows = mysqli_fetch_assoc($results)) {
		$query = 'SELECT grn_no FROM transaction_' . $rows['table_name'] . " WHERE grn_no LIKE '$grn_id_manual' AND book_manual = 2";
		$result = mysqli_query($conn, $query);
		$manual_count = mysqli_num_rows($result);
		if ($manual_count > 0) {
			$response = 1;
			break;
		} else {
			$response = 0;
		}
	}
	if ($response == 1) {
		echo 1;
	} else {
		echo 0;
	}
}
// check manual grn duplicate end


//delivery statuses
if ($cmd == 'get_tracking_message') {

    $transaction_id = $_POST['transaction_id'];
    $month          = $_POST['month'];
    $year           = $_POST['year'];
    $status         = $_POST['status'];

    $table = "transaction_" . $month . "_" . $year;

    $query = mysqli_query(
        $conn,
        "SELECT * FROM $table WHERE transaction_id='$transaction_id'"
    );

    if (mysqli_num_rows($query) > 0) {

        $row = mysqli_fetch_assoc($query);

        // Override the status with the newly selected status
        $row['active_status'] = $status;

        echo get_tracking_message($conn, $row);

    } else {

        echo "No Record Found";

    }

    exit;
}

if($cmd=="get_client_branches")
{
    $company_id = $_REQUEST['company_id'];

    $query = mysqli_query($conn,"
        SELECT *
        FROM client_branch
        WHERE company_id='$company_id'
        AND status='0'
        ORDER BY branch_name
    ");

    $rows=array();

    while($row=mysqli_fetch_assoc($query))
    {
        $rows[]=$row;
    }

    echo json_encode($rows);
    exit;
}

// if($cmd=="get_branch_details")
// {

//     $branch_id=$_GET['branch_id'];

//     $query=mysqli_query($conn,"
//         SELECT
//             cb.*,
//             c.city_name,
//             s.state_name
//         FROM client_branch cb
//         LEFT JOIN city c
//             ON c.city_id=cb.city
//         LEFT JOIN state s
//             ON s.state_id=cb.state
//         WHERE cb.client_branch_id='$branch_id'
//     ");

//     $row=mysqli_fetch_assoc($query);

//     echo json_encode($row);

// }

// if ($cmd == "get_branch_details") {

//     $branch_id = isset($_REQUEST['branch_id']) ? $_REQUEST['branch_id'] : 0;

//     $query = mysqli_query($conn,"
//         SELECT
//             cb.*,
//             c.city_name,
//             s.state_name
//         FROM client_branch cb
//         LEFT JOIN city c
//             ON c.city_id = cb.city
//         LEFT JOIN state s
//             ON s.state_id = cb.state
//         WHERE cb.client_branch_id = '$branch_id'
//     ");

//     $row = mysqli_fetch_assoc($query);

//     echo json_encode($row);
//     exit;
// }

if ($cmd == "get_branch_details") {

    $branch_id = isset($_REQUEST['branch_id']) ? $_REQUEST['branch_id'] : 0;

    $query = mysqli_query($conn,"
        SELECT
            cb.*,
            c.city_name,
            s.state_name
        FROM client_branch cb
        LEFT JOIN city c
            ON c.city_id = cb.city
        LEFT JOIN state s
            ON s.state_id = cb.state
        WHERE cb.client_branch_id = '$branch_id'
    ");

    $row = mysqli_fetch_assoc($query);

    echo json_encode($row);
    exit;
}

if($cmd=="get_client_branch_details"){

    $branch_id=$_GET['branch_id'];

    $query=mysqli_query($conn,"
        SELECT cb.*,
               c.city_name,
               s.state_name
        FROM client_branch cb
        LEFT JOIN city c
            ON cb.city=c.city_id
        LEFT JOIN state s
            ON cb.state=s.state_id
        WHERE cb.client_branch_id='$branch_id'
    ");

    $row=mysqli_fetch_assoc($query);

    echo json_encode($row);

    exit;
}

// Cargo Booking Report - AJAX Handler
if ($cmd == 'get_cargo_booking_report_details') {
    @ini_set('memory_limit', '512M');
    @set_time_limit(180);
    error_reporting(0);
    ini_set('display_errors', 0);
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    $from_date = isset($_REQUEST['from_date']) ? $_REQUEST['from_date'] : '';
    $to_date = isset($_REQUEST['to_date']) ? $_REQUEST['to_date'] : '';
    $customers = isset($_REQUEST['customers']) ? $_REQUEST['customers'] : '';
    $modes = isset($_REQUEST['modes']) ? $_REQUEST['modes'] : '';
    $origins = isset($_REQUEST['origins']) ? $_REQUEST['origins'] : '';
    $destinations = isset($_REQUEST['destinations']) ? $_REQUEST['destinations'] : '';
    $payment_modes = isset($_REQUEST['payment_modes']) ? $_REQUEST['payment_modes'] : '';

    if ($from_date == '' || $to_date == '') {
        echo json_encode(['status' => 1, 'message' => 'From Date and To Date are required.', 'data' => []]);
        exit;
    }

    $from_dt = DateTime::createFromFormat('d-m-Y', $from_date);
    $to_dt = DateTime::createFromFormat('d-m-Y', $to_date);
    if (!$from_dt || !$to_dt) {
        echo json_encode(['status' => 1, 'message' => 'Invalid date format.', 'data' => []]);
        exit;
    }

    $from_mysql = $from_dt->format('Y-m-d');
    $to_mysql = $to_dt->format('Y-m-d');

    $all_tables_q = mysqli_query($conn, 'SELECT * FROM transaction_tbls');
    $table_list = [];
    while ($tbl_row = mysqli_fetch_assoc($all_tables_q)) {
        $table_list[] = $tbl_row['table_name'];
    }

    $union_queries = [];
    foreach ($table_list as $tbl_name) {
        $t_trans = 'transaction_' . $tbl_name;
        $t_inv = 'transaction_invoice_' . $tbl_name;

        $chk = @mysqli_query($conn, "SELECT 1 FROM $t_trans LIMIT 1");
        if (!$chk) continue;

        $colChk = @mysqli_query($conn, "SHOW COLUMNS FROM $t_trans LIKE 'eway_number'");
        if (!$colChk || mysqli_num_rows($colChk) == 0) continue;

        $where = "STR_TO_DATE(t.grn_date, '%d-%m-%Y') >= '$from_mysql' AND STR_TO_DATE(t.grn_date, '%d-%m-%Y') <= '$to_mysql'";
        $where .= " AND (t.booking_status IS NULL OR t.booking_status = '' OR t.booking_status != '1')";

        if ($customers != '') {
            $cust_ids = array_map('intval', explode(',', $customers));
            $cust_list = implode(',', $cust_ids);
            $where .= " AND t.consigner IN ($cust_list)";
        }
        if ($modes != '') {
            $mode_ids = array_map('intval', explode(',', $modes));
            $mode_list = implode(',', $mode_ids);
            $where .= " AND t.mode_of_transportation IN ($mode_list)";
        }
        if ($origins != '') {
            $origin_ids = array_map('intval', explode(',', $origins));
            $origin_list = implode(',', $origin_ids);
            $where .= " AND t.origin IN ($origin_list)";
        }
        if ($destinations != '') {
            $dest_ids = array_map('intval', explode(',', $destinations));
            $dest_list = implode(',', $dest_ids);
            $where .= " AND t.destination IN ($dest_list)";
        }
        if ($payment_modes != '') {
            $pm_ids = array_map('intval', explode(',', $payment_modes));
            $pm_list = implode(',', $pm_ids);
            $where .= " AND t.mode_of_consignment IN ($pm_list)";
        }

        $q = "SELECT
            t.transaction_id,
            t.grn_date,
            t.grn_no,
            t.transaction_id AS trip_id,
            agg.no_of_pkge,
            agg.gross_weight,
            agg.charged_weight,
            t.frieght_rate AS rate,
            t.consigner,
            t.origin,
            t.consignee,
            t.destination,
            t.mode_of_transportation,
            agg.type_of_pkge,
            agg.party_invoice_no,
            agg.party_inv_date,
            t.supplier_invoice_value,
            t.mode_of_consignment,
            t.eway_number AS eway_bill_no,
            t.eway_expirydate AS eway_bill_expiry,
            t.lc_number,
            t.description_of_goods,
            t.cfs,
            t.quotation_approval,
            t.truck AS vehicle_number,
            t.freight_paid_by,
            t.insurance_number,
            t.vehicle_type,
            t.frieght_amount AS freight,
            t.doc_amount AS dc_amt,
            t.fov_amount AS fov,
            t.labour_handling_amount AS hamali_amt,
            t.total AS total_amt,
            t.gst_amount,
            t.total AS grand_total
        FROM $t_trans t
        LEFT JOIN (
            SELECT transaction_id,
                SUM(no_of_pkge) AS no_of_pkge,
                SUM(gross_weight) AS gross_weight,
                SUM(charged_weight) AS charged_weight,
            GROUP_CONCAT(DISTINCT CASE WHEN type_of_pkge != '' THEN type_of_pkge END SEPARATOR ', ') AS type_of_pkge,
            GROUP_CONCAT(DISTINCT CASE WHEN party_invoice_no != '' THEN party_invoice_no END SEPARATOR ', ') AS party_invoice_no,
            GROUP_CONCAT(DISTINCT CASE WHEN party_invoice_date != '' THEN party_invoice_date END SEPARATOR ', ') AS party_inv_date
            FROM $t_inv
            GROUP BY transaction_id
        ) agg ON agg.transaction_id = t.transaction_id
        WHERE $where";

        $union_queries[] = $q;
    }

    if (empty($union_queries)) {
        echo json_encode(array('status' => 0, 'data' => array()));
        exit;
    }

    $final_query = implode(' UNION ALL ', $union_queries) . ' ORDER BY grn_date DESC, grn_no DESC';

    $result = @mysqli_query($conn, $final_query);
    if (!$result) {
        echo json_encode(array('status' => 1, 'message' => 'Unable to load report data.', 'data' => array()));
        exit;
    }

    $data = array();
    $i = 1;
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $packing_names = array();
            if ($row['type_of_pkge'] !== null && $row['type_of_pkge'] !== '') {
                foreach (array_map('trim', explode(',', $row['type_of_pkge'])) as $pkg_part) {
                    if ($pkg_part === '') continue;
                    if (ctype_digit($pkg_part)) {
                        $pkg_name = @get_package_name($conn, $pkg_part);
                        $packing_names[] = $pkg_name ? $pkg_name : $pkg_part;
                    } else {
                        $packing_names[] = $pkg_part;
                    }
                }
            }
            $data[] = array(
                's_no' => $i,
                'gr_date' => $row['grn_date'],
                'gr_no' => $row['grn_no'],
                'trip_id' => $row['trip_id'],
                'pkgs' => $row['no_of_pkge'],
                'gross_wt' => $row['gross_weight'],
                'chg_wt' => $row['charged_weight'],
                'rate' => $row['rate'],
                'party_name' => @get_client_name($conn, $row['consigner']),
                'from_city' => @get_city_name($conn, $row['origin']),
                'consignee' => @get_client_name($conn, $row['consignee']),
                'to_city' => @get_city_name($conn, $row['destination']),
                'mode' => @get_mode($conn, $row['mode_of_transportation']),
                'type_of_packing' => implode(', ', array_unique($packing_names)),
                'party_invoice_no' => $row['party_invoice_no'],
                'party_inv_date' => $row['party_inv_date'],
                'supplier_inv_value' => $row['supplier_invoice_value'],
                'pymt_mode' => @consignment_mode($conn, $row['mode_of_consignment']),
                'eway_bill_no' => $row['eway_bill_no'],
                'eway_bill_expiry' => $row['eway_bill_expiry'],
                'lc_number' => $row['lc_number'],
                'desc_of_goods' => $row['description_of_goods'],
                'cfs' => $row['cfs'],
                'quotation_approval' => $row['quotation_approval'],
                'vehicle_number' => $row['vehicle_number'],
                'freight_paid_by' => $row['freight_paid_by'],
                'insurance_number' => $row['insurance_number'],
                'vehicle_type' => $row['vehicle_type'],
                'freight' => $row['freight'],
                'dc_amt' => $row['dc_amt'],
                'fov' => $row['fov'],
                'hamali_amt' => $row['hamali_amt'],
                'total_amt' => $row['total_amt'],
                'gst_amt' => $row['gst_amount'],
                'total' => $row['grand_total']
            );
            $i++;
        }
    }

    $json_flags = JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $json_flags = $json_flags | JSON_INVALID_UTF8_SUBSTITUTE;
    }
    echo json_encode(array('status' => 0, 'data' => $data), $json_flags);
    exit;
}

// Cargo Booking Report - Load filter options
if ($cmd == 'get_cargo_booking_filters') {
    $result = ['customers' => [], 'modes' => [], 'origins' => [], 'destinations' => [], 'payment_modes' => []];

    $cust_q = mysqli_query($conn, 'SELECT client_id, client_company_name FROM client ORDER BY client_company_name ASC');
    while ($r = mysqli_fetch_assoc($cust_q)) {
        $result['customers'][] = ['id' => $r['client_id'], 'name' => $r['client_company_name']];
    }

    $mode_q = mysqli_query($conn, 'SELECT mode_id, mode_type FROM mode_of_transportation WHERE status=0 ORDER BY mode_type ASC');
    while ($r = mysqli_fetch_assoc($mode_q)) {
        $result['modes'][] = ['id' => $r['mode_id'], 'name' => $r['mode_type']];
    }

    $city_q = mysqli_query($conn, 'SELECT city_id, city_name FROM city WHERE status=0 ORDER BY city_name ASC');
    while ($r = mysqli_fetch_assoc($city_q)) {
        $result['origins'][] = ['id' => $r['city_id'], 'name' => $r['city_name']];
    }
    $result['destinations'] = $result['origins'];

    $pm_q = mysqli_query($conn, 'SELECT consignment_id, consignment_mode FROM consignment_mode WHERE status=0 ORDER BY consignment_mode ASC');
    while ($r = mysqli_fetch_assoc($pm_q)) {
        $result['payment_modes'][] = ['id' => $r['consignment_id'], 'name' => $r['consignment_mode']];
    }

    echo json_encode($result);
    exit;
}
