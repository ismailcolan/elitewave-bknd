<?php

// Include Composer autoloader
require 'PhpSpreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Include the database configuration file
include('../config.ini.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();

// Create a new worksheet
$sheet = $spreadsheet->getActiveSheet();

// Set the header
$sheet->setCellValue('A1', 'GRN.No*');
$sheet->setCellValue('B1', 'GRN.Date*');
$sheet->setCellValue('C1', 'Mode of Transportation*');
$sheet->setCellValue('D1', 'Mode of Consignment*');
$sheet->setCellValue('E1', 'Consignor*');
$sheet->setCellValue('F1', 'Consignee*');
$sheet->setCellValue('G1', 'No of Pkgs');
$sheet->setCellValue('H1', 'Type of Pkgs');
$sheet->setCellValue('I1', 'Party Invoice No');
$sheet->setCellValue('J1', 'Said to Contents');
$sheet->setCellValue('K1', 'Qty');
$sheet->setCellValue('L1', 'Charged wt.(Kgs)');
$sheet->setCellValue('M1', 'E-Way Number');
$sheet->setCellValue('N1', 'E-Way Expiry Date');
$sheet->setCellValue('O1', 'Freight');
$sheet->setCellValue('P1', 'Rate');
$sheet->setCellValue('Q1', 'Loading / Unloading Charges');
$sheet->setCellValue('R1', 'Crane / Fork Lift Charges');
$sheet->setCellValue('S1', 'C.O.D');
$sheet->setCellValue('T1', 'F.O.V');
$sheet->setCellValue('U1', 'Doc.Charges');
$sheet->setCellValue('V1', 'Cartage');
$sheet->setCellValue('W1', 'Labour Handling');
$sheet->setCellValue('X1', 'Any Other charges');
$sheet->setCellValue('Y1', 'Truck/ Vehicle No');
$sheet->setCellValue('Z1', 'Select Status');

// Fetch mode_type values from the database
$sql1 = "SELECT DISTINCT mode_type FROM mode_of_transportation WHERE status = 0";
$result1 = $conn->query($sql1);

// Fetch consignment_mode values from the database
$sql2 = "SELECT * FROM consignment_mode WHERE status=0";
$result2 = $conn->query($sql2);

// Fetch limited client_company_name values from the database
$sql3 = " SELECT * FROM client";
$result3 = $conn->query($sql3);

// Fetch package_code values from the database
$sql4 = "SELECT * FROM package WHERE status=0";
$result4 = $conn->query($sql4);

// Initialize an array to store mode_type values
$modeTypes = [];

if ($result1->num_rows > 0) {
    // Fetch mode_type values and store them in the array
    while ($row = $result1->fetch_assoc()) {
        $modeTypes[] = $row['mode_type'];
    }
}

// Initialize an array to store consignment_mode values
$paymentMode = [];

if ($result2->num_rows > 0) {
    // Fetch mode_type values and store them in the array
    while ($row = $result2->fetch_assoc()) {
        $paymentMode[] = $row['consignment_mode'];
    }
}

// Initialize an array to store client_company_name values
$client = [];

if ($result3->num_rows > 0) {
    while ($row = $result3->fetch_assoc()) {
        $client[] = $row['client_company_name'];
        
    }
}

// Initialize an array to store package_code values
$pkgType = [];

if ($result4->num_rows > 0) {
    while ($row = $result4->fetch_assoc()) {
        $pkgType[] = $row['package_code'];
        
    }
}

// Define the status values as an array
$statusValues = [
    "Consignment Booked",
    "Consignment Picked Up",
    "In Transit - 1 (Consignment at Origin State)",
    "In Transit - 2 (Towards Destination State)",
    "In Transit - 3 (Towards Destination)",
    "At Destination",
    "Out for Delivery",
    "Consignment Delivered Successfully"
];

// Convert the array into a comma-separated string
$statusString = implode(",", $statusValues);

// Create a data validation for the dropdown list in cell C2 (Mode of Transportation*)
$validationC2 = $sheet->getCell('C2')->getDataValidation();
$validationC2->setType('list');
$validationC2->setFormula1('"' . implode(',', $modeTypes) . '"');
$validationC2->setShowDropDown(true);

// Create a data validation for the dropdown list in cell D2 (Mode of Consignment*)
$validationD2 = $sheet->getCell('D2')->getDataValidation();
$validationD2->setType('list');
$validationD2->setFormula1('"' . implode(',', $paymentMode) . '"');
$validationD2->setShowDropDown(true);

// Loop through $client array and write each value to "AA" column starting from row 2
$row = 2; // Start from row 2 because row 1 contains headers
foreach ($client as $value) {
    $sheet->setCellValue('AA' . $row, $value);
    $row++;
}

// Create a data validation for the dropdown list in cell E2 (Consignor*)
$validationE2 = $sheet->getCell('E2')->getDataValidation();
$validationE2->setType('list');
$validationE2->setFormula1('AA2:AA' . ($row - 1)); // Range from AA2 to the last row where data is populated
$validationE2->setShowDropDown(true);

// Create a data validation for the dropdown list in cell F2 (Consignee*)
$validationF2 = $sheet->getCell('F2')->getDataValidation();
$validationF2->setType('list');
$validationF2->setFormula1('AA2:AA' . ($row - 1)); // Range from AA2 to the last row where data is populated
$validationF2->setShowDropDown(true);

// Hide the values in the "AA" column by setting font color to match cell background color
$lastRow = $row - 1;
$sheet->getStyle('AA2:AA' . $lastRow)->getFont()->getColor()->setARGB('FFFFFFFF'); // Set font color to white

// Create a data validation for the dropdown list in cell H2 (Type of Packages)
$validationH2 = $sheet->getCell('H2')->getDataValidation();
$validationH2->setType('list');
$validationH2->setFormula1('"' . implode(',', $pkgType) . '"');
$validationH2->setShowDropDown(true);


// Create a data validation for the dropdown list in cell Z2
$validationZ2 = $sheet->getCell('Z2')->getDataValidation();
$validationZ2->setType('list');
$validationZ2->setFormula1('"' . $statusString . '"');
$validationZ2->setShowDropDown(true);

// Set the cell value (optional)
$sheet->setCellValue('C2', $modeTypes[0]);
$sheet->setCellValue('D2', $paymentMode[0]);
$sheet->setCellValue('H2', $pkgType[0]);
$sheet->setCellValue('Z2', $statusValues[0]);

// Save the spreadsheet to a temporary buffer
$writer = new Xlsx($spreadsheet);
ob_start();
$writer->save('php://output');
$fileContents = ob_get_clean();

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="sample.xlsx"');
// header('Content-Type: text/csv; charset=utf-8');
// header('Content-Disposition: attachment; filename="sample.csv"');
header('Cache-Control: max-age=0');

// Output the file contents
echo $fileContents;

// Terminate the script
exit;