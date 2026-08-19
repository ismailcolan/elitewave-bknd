<?php
/**
 * db_describe.php
 *
 * Simple utility to output the MySQL CREATE TABLE statements for every table
 * in the current Gracious Express database. Useful for generating Laravel
 * migration files.
 *
 * Usage: place this file in the project root (or any reachable location),
 * then access it via the browser or CLI: php db_describe.php
 */

// Include the existing DB connection (adjust path if needed)
require_once __DIR__ . '/web/include/connect.php'; // $conn should be defined here

if (!isset($conn) || !$conn) {
    die('Database connection not available. Check connect.php');
}

// Get the current database name
$database = '';
$res = mysqli_query($conn, "SELECT DATABASE() as db");
if ($row = mysqli_fetch_assoc($res)) {
    $database = $row['db'];
}
if (empty($database)) {
    die('Unable to determine current database name');
}

// Fetch all tables for this database
$tablesResult = mysqli_query($conn, "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$database'");
if (!$tablesResult) {
    die('Failed to retrieve table list: ' . mysqli_error($conn));
}

while ($tbl = mysqli_fetch_assoc($tablesResult)) {
    $table = $tbl['TABLE_NAME'];
    // Get CREATE statement
    $createRes = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
    if ($createRow = mysqli_fetch_assoc($createRes)) {
        $createSql = $createRow['Create Table'];
        echo "-- --------------------------------------------------------\n";
        echo "-- Table structure for `$table`\n";
        echo "-- --------------------------------------------------------\n\n";
        echo $createSql . ";\n\n";
    } else {
        echo "-- Unable to get CREATE statement for $table\n";
    }
}
?>
