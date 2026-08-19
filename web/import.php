<?php
require 'PhpSpreadsheet/vendor/autoload.php';

include ('../config.ini.php');
include ('include/function.php');
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

use PhpOffice\PhpSpreadsheet\IOFactory; // Add this line to include IOFactory

session_start();
ini_set('max_execution_time', 1200);

if (isset($_FILES)) {
    // Get file information
    if (isset($_FILES['csv_import']) && is_uploaded_file($_FILES['csv_import']['tmp_name'])) {
        $sessionId = session_create_id(); // Generate a unique session ID
        $_SESSION['import_session_id'] = $sessionId;

        $fileName = $_FILES['csv_import']['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $fileTmpName = $_FILES['csv_import']['tmp_name'];

        // Check for valid XLSX extension
        if ($fileExtension === 'xlsx') {
            // $created_at = $updated_at = date('d-m-Y');
            $xlsx_file = $fileTmpName;

            // Load the XLSX file
            $spreadsheet = IOFactory::load($xlsx_file); // Use IOFactory::load here

            // Get the active sheet
            $sheet = $spreadsheet->getActiveSheet();

            // Initialize an empty array to store data
            $data = [];

            // Initialize an empty array to store header column names and their indexes
            $header = [];

            $existingGrnNo = [];
            $insertedStatus = [];

            // Iterate through each row in the worksheet
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                // Initialize an empty array for each row
                $rowData = [];

                // Iterate through each cell in the row, up to column 'Z'
                foreach ($row->getCellIterator('A', 'Z') as $cellIndex => $cell) {
                    // Get the value of the cell
                    $cellValue = $cell->getValue();

                    // If it's the first row, store the column name and its index
                    if ($rowIndex === 1) {
                        $header[$cellIndex] = $cellValue;
                    } else {
                        // If it's not the first row, add the cell value to the row data array
                        // Use the header array to map column indexes to their respective names
                        $rowData[$header[$cellIndex]] = $cellValue;
                    }
                }

                // If it's not the first row, add the row data to the main data array
                if ($rowIndex !== 1) {
                    $data[] = $rowData;
                }
            }


            //************************************************* For Loop Start Here ****************** */

            // Construct and execute the SQL query
            foreach ($data as $row_id => $row) {
                $grNo = $row['GRN.No*']; // grn_no
            
            	if ($_SERVER['REMOTE_ADDR'] == '115.242.135.162') {
    				// Date manipulation for specific IP
    				$excelDateValue = $row['GRN.Date*'];
    				$timestamp = ($excelDateValue - 25569) * 86400;
    				$formattedDate = date('d-m-Y', $timestamp);
				} else {
    				// Default behavior if IP doesn't match
    				$formattedDate = $row['GRN.Date*'];
				}
                

                //************* Need to check Star ************ */
                $tables = get_trans_table_name($conn, $formattedDate);
                $get_m_y = explode('_', $tables[0]);
                $month = $get_m_y[1];
                $year = $get_m_y[2];

                $table0 = $tables[0];

                // Assuming $grNo is the unique identifier in your CSV data
                $checkQuery = "SELECT grn_no FROM $table0 WHERE grn_no = '" . $grNo . "'";
                $checkResult = mysqli_query($conn, $checkQuery);
                if (mysqli_num_rows($checkResult) > 0) {
                    $existingGrnNo[$row_id] = $grNo;
                    continue;
                } else {
                    // mode_of_transportation
                    if ($row['Mode of Transportation*'] === 'By AIR') {
                        $modeTransport = 1;
                    } elseif ($row['Mode of Transportation*'] === 'BY TRAIN') {
                        $modeTransport = 2;
                    } elseif ($row['Mode of Transportation*'] === 'BY EXPRESS') {
                        $modeTransport = 3;
                    } elseif ($row['Mode of Transportation*'] === 'Surface') {
                        $modeTransport = 4;
                    } elseif ($row['Mode of Transportation*'] === 'LOCAL DELIVERY') {
                        $modeTransport = 5;
                    } elseif ($row['Mode of Transportation*'] === 'BY SURFACE FTL') {
                        $modeTransport = 7;
                    } elseif ($row['Mode of Transportation*'] === 'BY SURFACE PTL') {
                        $modeTransport = 8;
                    } else {
                        $modeTransport = '';
                    }

                    // mode_of_consignment
                    if ($row['Mode of Consignment*'] === 'TO PAY') {
                        $pymtMode = 1;
                    } elseif ($row['Mode of Consignment*'] === 'TBB') {
                        $pymtMode = 2;
                    } elseif ($row['Mode of Consignment*'] === 'PAY AT BOOKING') {
                        $pymtMode = 3;
                    } elseif ($row['Mode of Consignment*'] === 'COD (CASH ON DELIVERY)') {
                        $pymtMode = 4;
                    } else {
                        $pymtMode = '';
                    }

                    $consignor = $row['Consignor*']; // consigner
                    $Consignee = $row['Consignee*']; // consignee
                    $no_of_Pkgs = $row['No of Pkgs'];

                    if ($row['Type of Pkgs'] === 'Poly Bag') {
                        $pkgsType = 2;
                    } elseif ($row['Type of Pkgs'] === 'Roll') {
                        $pkgsType = 3;
                    } elseif ($row['Type of Pkgs'] === 'SHEET ') {
                        $pkgsType = 5;
                    } elseif ($row['Type of Pkgs'] === 'Bundle') {
                        $pkgsType = 6;
                    } elseif ($row['Type of Pkgs'] === 'Cover') {
                        $pkgsType = 7;
                    } elseif ($row['Type of Pkgs'] === 'Poly Bundle') {
                        $pkgsType = 8;
                    } elseif ($row['Type of Pkgs'] === 'Can') {
                        $pkgsType = 9;
                    } elseif ($row['Type of Pkgs'] === 'Box') {
                        $pkgsType = 10;
                    } elseif ($row['Type of Pkgs'] === 'Mould') {
                        $pkgsType = 12;
                    } elseif ($row['Type of Pkgs'] === 'Packets') {
                        $pkgsType = 13;
                    } elseif ($row['Type of Pkgs'] === 'CES') {
                        $pkgsType = 14;
                    } elseif ($row['Type of Pkgs'] === 'CAT') {
                        $pkgsType = 15;
                    } elseif ($row['Type of Pkgs'] === 'GROSS ROLL') {
                        $pkgsType = 16;
                    } elseif ($row['Type of Pkgs'] === 'P.BAG') {
                        $pkgsType = 17;
                    } elseif ($row['Type of Pkgs'] === 'POLY ROLL') {
                        $pkgsType = 18;
                    } elseif ($row['Type of Pkgs'] === 'Bag') {
                        $pkgsType = 24;
                    } elseif ($row['Type of Pkgs'] === 'TRAY ') {
                        $pkgsType = 27;
                    } else {
                        $pkgsType = '';
                    }

                    $party_inv_no = $row['Party Invoice No'];
                    $said_to_cont = $row['Said to Contents'];
                    $quantity = $row['Qty'];
                    $chgWt = $row['Charged wt.(Kgs)'];
                    $eway_no = $row['E-Way Number'];

                	if($_SERVER['REMOTE_ADDR'] == '115.242.135.162'){
                    	// eway_expirydate
                    	$excel_ewxd = $row['E-Way Expiry Date'];
                    	$timestamp = ($excel_ewxd - 25569) * 86400;
                    	$eway_exp_date = date('d-m-Y', $timestamp);
                    }else{
                    	$eway_exp_date = $row['E-Way Expiry Date'];
                    }

                    // $eway_exp_date = $row['E-Way Expiry Date'];

                    $freight = $row['Freight'];
                    $rate = $row['Rate'];
                    $lu_chrgs = $row['Loading / Unloading Charges'];
                    $cfl_chrgs = $row['Crane / Fork Lift Charges'];
                    $cod = $row['C.O.D'];
                    $fov = $row['F.O.V'];
                    $dChrgs = $row['Doc.Charges'];
                    $cartage = $row['Cartage'];
                    $labHandling = $row['Labour Handling'];
                    $aoChrgs = $row['Any Other charges'];
                    $tv_no = $row['Truck/ Vehicle No'];

                    if ($row['Select Status'] === 'Consignment Booked') {
                        $select_status = 1;
                    } elseif ($row['Select Status'] === 'Consignment Picked Up') {
                        $select_status = 2;
                    } elseif ($row['Select Status'] === 'In Transit - 1 (Consignment at Origin State)') {
                        $select_status = 3;
                    } elseif ($row['Select Status'] === 'In Transit - 2 (Towards Destination State)') {
                        $select_status = 4;
                    } elseif ($row['Select Status'] === 'In Transit - 3 (Towards Destination)') {
                        $select_status = 5;
                    } elseif ($row['Select Status'] === 'At Destination') {
                        $select_status = 6;
                    } elseif ($row['Select Status'] === 'Out for Delivery') {
                        $select_status = 7;
                    } elseif ($row['Select Status'] === 'Consignment Delivered Successfully') {
                        $select_status = 8;
                    } else {
                        $select_status = '';
                    }
                    //********************************************* Step - 1 ******************************* */
					
                	// Getting consignor details
                    $cons_name = $consignor;
                    $consignorquery = "SELECT * FROM client WHERE `client_company_name` LIKE '$cons_name'";
                    $consignorresult = mysqli_query($conn, $consignorquery);

                    // if($consignorresult && mysqli_num_rows($consignorresult) > 0){

                    $consignorrow = mysqli_fetch_array($consignorresult);

                    $consignor_id = $consignorrow['client_id'];
                    $address1 = $consignorrow['address1'];
                    $address2 = $consignorrow['address2'];
                    $city = $consignorrow['city'];
                    $pincode = $consignorrow['pincode'];
                    $state = $consignorrow['state'];
                    $phone = $consignorrow['contact_no'];
                    $gst_no = $consignorrow['gst_no'];

                    if (empty($consignor_id)) {
                        continue;
                    }

                    // Getting consignee details
                    $consigneequery = "SELECT * FROM client WHERE client_company_name LIKE '$Consignee'";
                    $consigneeresult = mysqli_query($conn, $consigneequery);

                    // if($consigneeresult && mysqli_num_rows($consigneeresult) > 0){

                    $consigneerow = mysqli_fetch_array($consigneeresult);

                    $consignee_id = $consigneerow['client_id'];
                    $con_address1 = $consigneerow['address1'];
                    $con_address2 = $consigneerow['address2'];
                    $con_city = $consigneerow['city'];
                    $con_state = $consigneerow['state'];
                    $con_pincode = $consigneerow['pincode'];
                    $con_phone = $consigneerow['contact_no'];
                    $con_gst = $consigneerow['gst_no'];

                    if (empty($consignee_id)) {
                        continue;
                    }

                    $created_at = $updated_at = date('d-m-Y');

                    // $tables = get_trans_table_name($conn, $formattedDate);
                    // $get_m_y = explode('_', $tables[0]);
                    // $month = $get_m_y[1];
                    // $year = $get_m_y[2];

                    // $table0 = $tables[0];

                    // // Assuming $grNo is the unique identifier in your CSV data
                    // $checkQuery = "SELECT grn_no FROM $table0 WHERE grn_no = '" . $grNo . "'";
                    // $checkResult = mysqli_query($conn, $checkQuery);
                    // print_r($checkResult);

                    $grn_id = '';
                    $ftl_type = '';
                    $origin = '';
                    $destination = $con_city;
                    $goods_dedared_value = '';
                    $octroi = '';
                    $dimension1 = '';
                    $dimension2 = '';
                    $dimension3 = '';
                    $consignment_weight = '';
                    $loading_unloading_rate = '';
                    $crane_fork_lift_rate = '';
                    $cod_rate = '';
                    $fov_rate = '';
                    $doc_charges = '';
                    $cartage_rate = '';
                    $labour_handling_rate = '';
                    $octroi_rate = '';
                    $octroi_amount = '';
                    $other_charge_rate = '';
                    $gst_rate = '';
                    $gst_amount = '';
                
                	// for total
					$chrgMulFreight = $chgWt * $freight;
                	$sumOfTotal = $lu_chrgs + $cfl_chrgs + $cod + $fov + $dChrgs + $cartage + $labHandling + $aaoC;
                    // $total = $chrgMulFreight + $sumOfTotal;
                
                	// Function to convert a number into words
					function numberToWords($number) {
    					// Array of words for numbers
   						$words = array(
        					'Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        					'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
    					);
    
                    	$tens = array(
        					'', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'
    					);

    					// Negative number handling
    					if ($number < 0) {
        					return "Negative " . numberToWords(abs($number));
    					}

    					// Number is less than 20
    					if ($number < 20) {
        					return $words[$number];
    					}

    					// Convert the number into words for thousands and below
    					$result = '';
    					if ($number >= 1000) {
        					$result .= numberToWords(floor($number / 1000)) . ' Thousand ';
        					$number %= 1000;
    					}
    					
                    	if ($number >= 100) {
        					$result .= $words[floor($number / 100)] . ' Hundred ';
        					$number %= 100;
    					}
    
                    	if ($number >= 20) {
        					$result .= $tens[floor($number / 10)];
        					$number %= 10;
    					}
    
                    	if ($number > 0) {
        					$result .= $words[$number];
    					}

    					return ucfirst(trim($result)) . ' only';
					}

					// Calculate the total
                	$total = $chrgMulFreight + $sumOfTotal;
             		

					// Convert total to words
					$totalInWords = numberToWords($total);

                    
					$paid_amount = '';
                    $balance = '';
                    $paid_status = '';
                    // $total_words = '';
                    $note1 = '';
                    $note2 = '';
                    $consigner_signature = '';
                    $client_id = '';
                    $updated_by = '';
                    $active_status = '';
                    $booking_status = '';
                    $remarks = '';
                    $cancelled_by = '';
                    $frq_sent_status = '';
                    $book_manual = 1;
                    $created_by = 1;

                    // $atLeastOneExists = (mysqli_num_rows($checkResult) > 0);

                    // if ($atLeastOneExists) {
                    //     $existingData = [];
                    //     while ($row = mysqli_fetch_assoc($checkResult)) {
                    //         $existingData[] = $row['grn_no'];
                    //     }

                    //     // Encode the $existingData array as JSON
                    //     $existingDataJSON = json_encode($existingData);

                    //     // Check if existing data is present
                    //     // if (!empty($existingData)) {
                    //     // Output the message with existing records
                    //     echo '"Already existing records ' . $existingDataJSON . '"';
                    // } else {
                        $query = "INSERT INTO $table0 (`grn_no`, `grn_id`, `con_phone`, `grn_date`, `mode_of_transportation`, `ftl_type`, `origin`, `destination`, `mode_of_consignment`, `consigner`, `address1`, `address2`, `city`, `pincode`, `state`, `phone`, `gst_no`, `consignee`, `con_address1`, `con_address2`, `con_city`, `con_state`, `con_pincode`, `con_gst_no`, `goods_dedared_value`, `octroi`, `dimension1`, `dimension2`, `dimension3`, `consignment_weight`, `frieght_rate`, `frieght_amount`, `loading_unloading_rate`,`loading_unloading_amount`, `crane_fork_lift_rate`, `crane_fork_lift_amount`, `cod_rate`, `cod_amount`, `fov_rate`, `fov_amount`, `doc_charges`, `doc_amount`, `cartage_rate`, `cartage_amount`, `labour_handling_rate`, `labour_handling_amount`, `octroi_rate`, `octroi_amount`, `other_charge_rate`, `other_charge_amount`, `gst_rate`, `gst_amount`, `total`, `paid_amount`, `balance`, `paid_status`, `total_words`, `note1`, `note2`, `truck`, `consigner_signature`, `client_id`, `created_at`, `created_by`, `updated_at`, `updated_by`, `status`, `active_status`, `invoice_no`, `eway_number`, `booking_status`, `remarks`, `cancelled_by`, `frq_sent_status`, `book_manual`, `eway_expirydate`) VALUES('$grNo', '$grn_id', '$con_phone', '$formattedDate', '$modeTransport', '$ftl_type', '$origin', '$destination', '$pymtMode', '$consignor_id', '$address1', '$address2', '$city', '$pincode', '$state', '$phone', '$gst_no', '$consignee_id', '$con_address1', '$con_address2', '$con_city', '$con_state', '$con_pincode', '$con_gst', '$goods_dedared_value', '$octroi', '$dimension1', '$dimension2', '$dimension3', '$consignment_weight', '$freight', '$rate', '$loading_unloading_rate', '$lu_chrgs', '$crane_fork_lift_rate', '$cfl_chrgs', '$cod_rate', '$cod', '$fov_rate', '$fov', '$doc_charges', '$dChrgs', '$cartage_rate', '$cartage', '$labour_handling_rate', '$labHandling', '$octroi_rate', '$octroi_amount', '$other_charge_rate', '$aoChrgs', '$gst_rate', '$gst_amount', '$total', '$paid_amount', '$balance', '$paid_status', '$totalInWords', '$note1', '$note2', '$tv_no', '$consigner_signature', '$consignor_id', '$created_at', '$created_by', '$updated_at', '$updated_by', '$select_status', '$active_status', '$party_inv_no', '$eway_no', '$booking_status', '$remarks', '$cancelled_by', '$frq_sent_status', '$book_manual', '$eway_exp_date')";

                        $result = mysqli_query($conn, $query);

                        $transaction_id = mysqli_insert_id($conn);

                        if (!$result) {
                            // error_log("Error: " . mysqli_error($conn));
                            echo 'Error';
                        } else {
                            // status select while booking start
                        	$sheetq = "SELECT max(sheet_id) AS id FROM transaction_status";
                        	$sheetres = mysqli_query($conn, $sheetq) or die(mysqli_error($conn));
                        	$sheetr = mysqli_fetch_array($sheetres);
                        	$sheet_id = $sheetr['id'] + 1;
                        	$sheet_no = "SN/" . sprintf("%04d", $sheet_id);
                        	$c_date = date('d-m-Y H:i:s A');
                        	$status = $select_status;
                        	// test_transaction_import_status_log
                        	$insq1 = "INSERT INTO `transaction_status`(`sheet_id`,`sheet_no`, `status`, `created_at`, `created_by`) VALUES ('$sheet_id','$sheet_no','$status','$c_date','1')";
                        	$insr1 = mysqli_query($conn, $insq1);

                        	// test_transaction_import_status_log
                        	$insq = "INSERT INTO `transaction_status_log`(`sheet_id`, `grn_no`, `from_status`, `to_status`,`client_id`,`updated_at`, `updated_by`) VALUES ('$sheet_id','$grNo','1','$status','$consignor_id','$created_at','1')";
                        	$insr = mysqli_query($conn, $insq);

                        	// test_transaction_import_table
                        	$status_upd_query = "UPDATE `$table0` SET `status`='$status' WHERE grn_no='$grNo' AND client_id='$consignor_id'";
                        	$results = mysqli_query($conn, $status_upd_query);

                        	// status select while booking end                            
                        	$gross_wt = ""; // gross.Wt
                        	$no_of_pkgs1 = $no_of_Pkgs;
                        	$type_of_pkgs1 = $pkgsType;
                        	$party_invoices1 = $party_inv_no;
                        	$contents1 = $said_to_cont;
                        	$qtys1 = $quantity;
                        	$grosss1 = $gross_wt;
                        	$chargeds1 = $chgWt;
                        	$table2 = $tables[2];
                        	
                        	// test_transaction_import_invoice
                        	$f_query = "INSERT INTO `$table2` (transaction_id,no_of_pkge,type_of_pkge,party_invoice_no,said_contents,qty,gross_weight,charged_weight,created_at,created_by,`status`) VALUES('" . $transaction_id . "','" . $no_of_pkgs1 . "','" . $type_of_pkgs1 . "','" . $party_invoices1 . "','" . $contents1 . "','" . $qtys1 . "','" . $grosss1 . "','" . $chargeds1 . "','" . $created_at . "','" . 1 . "','0')";
                        	$f_result = mysqli_query($conn, $f_query);

                        // $package[] = $pkgs; //Get the package number
                        $pkg_name[] = $pkgsType;
                        $invoice_id = mysqli_insert_id($conn);
                        $insertedStatus[$row_id] = $invoice_id;

                        if ($transaction_id) {
                            $inv = '';

                            $inv = rtrim($inv, ",");
                            $url = "https://graciousexpress.colanapps.in/web/transaction_pdf.php?month=" . $month . "&year=" . $year . "&id=" . $transaction_id . "&copy=consignor";
                            $path = "transaction_pdf/" . $month . "_" . $year . "_" . $transaction_id . "transaction.pdf";
                            $ch = curl_init($url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_REFERER, $url);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            $data = curl_exec($ch);
                            curl_close($ch);
                            $result_url = file_put_contents($path, $data);

                            //*Invoice Section Start
                            //Sequence Generation
                            if ($modeTransport == '1' || $modeTransport == '2' || $modeTransport == '3') {
                                $type = 'GST';
                            } else {
                                $type = 'GTA';
                            }

                            $grn_date_expl = explode("-", $formattedDate);
                            $cur_year = $grn_date_expl[2];
                            $current_year = $cur_year;
                            $previous_year = $cur_year - 1;
                            $p_y = substr($previous_year, 2);
                            $c_y = substr($current_year, 2);
                            $year_insert = $p_y . "-" . $c_y;
                            $invoice_table = invoice_table_function($conn, $formattedDate);

                            $select = mysqli_query($conn, "SELECT * FROM " . $invoice_table);
                            $get_count = mysqli_num_rows($select);
                            if ($get_count == 0) {
                                // trans_invoice_tbl2023
                                $insert_data = "INSERT INTO " . $invoice_table . "(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('0','HRGST','$year_insert','GST','$created_at','1'),('0','HRGTA','$year_insert','GTA','$created_at','1')";
                                $res = mysqli_query($conn, $insert_data);
                                if ($res) {
                                    $inv_query = "SELECT * FROM trans_invoice_tbl" . $year . " WHERE inv_type='$type'";
                                    $inv_query_result = mysqli_query($conn, $inv_query);
                                    $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                                    $inv_seq = $inv_query_row['invoice_no'] + 1;
                                    $inv_text = $inv_query_row['gst_text'];
                                    $inv_year = $inv_query_row['gst_year'];
                                    $sequence = sprintf('%05d', $inv_seq);
                                    $unique_invoice_no = $inv_text . "/" . $sequence . "/" . $inv_year;
                                }
                            } else {
                                $inv_query = "SELECT * FROM trans_invoice_tbl" . $year . " WHERE inv_type='$type'";
                                $inv_query_result = mysqli_query($conn, $inv_query);
                                $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                                $inv_seq = $inv_query_row['invoice_no'] + 1;
                                $inv_text = $inv_query_row['gst_text'];
                                $inv_year = $inv_query_row['gst_year'];
                                $sequence = sprintf('%05d', $inv_seq);
                                $unique_invoice_no = $inv_text . "/" . $sequence . "/" . $inv_year;
                            }

                            //Sequence Generation
                            $directory = 'digital_invoice/';
                            $invoice_url = "https://graciousexpress.colanapps.in/web/gst_invoice_page.php?month=" . $month . "&year=" . $year . "&id=" . $transaction_id . "&invoice_no=" . $unique_invoice_no . "";
                            $invoice_file_name = $month . "_" . $year . "_" . $transaction_id . "invoice";
                            $download_path = $directory . $invoice_file_name . '.pdf';
                            $file_inv_download = curl_init($invoice_url);
                            curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($file_inv_download, CURLOPT_REFERER, true);
                            curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
                            $store_inv = curl_exec($file_inv_download);
                            curl_close($file_inv_download);
                            $save_inv_file = file_put_contents($download_path, $store_inv);
                            if ($save_inv_file) {
                                $update = mysqli_query($conn, "UPDATE `trans_invoice_tbl`" . $year . " SET invoice_no = '$inv_seq', updated_by = '1', updated_at = '$updated_at' WHERE inv_type = '$type'");
                                $tables_0 = $tables[0];
                                $query_inv = "UPDATE `$tables_0` SET `invoice_no` = '$unique_invoice_no' WHERE transaction_id ='$transaction_id'";
                                $res = mysqli_query($conn, $query_inv);
                            }

                            //*Invoice Section End
                            $image = array();
                            $tables_1 = $tables[1];
                            // echo $tables_1;
                            $img_query = mysqli_query($conn, "SELECT * FROM `$tables_1` WHERE transaction_id ='" . $transaction_id . "'");
                            if (mysqli_num_rows($img_query) > 0) {
                                while ($img_result = mysqli_fetch_array($img_query)) {
                                    array_push($image, "invoice_image/" . $img_result['attachment']);
                                }
                            }
                        }
                           
                        }

                        
                    // }
                }
                //************* Need to check end ************ */



                // $formattedDate = $row['GRN.Date*']; 
               


            }
            if(count($insertedStatus) > 0){
                $set_message = 'New Records Inserted Successfully';
            }else{
                $set_message = 'No records inserted';
            }
            echo json_encode(["message" => $set_message,'data'=>$existingGrnNo]);  
            // print_r($existingGrnNo);


            //************************************************* For Loop End Here ****************** */

        } else {
            echo "Only XLSX files are allowed!";
        }
    } else {
        echo "Please upload a file!";
    }
} else {
    echo "Invalid request!";
}

