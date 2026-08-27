<?php
/**
 * Extra expense module — ensure Phase 1 master tables exist.
 */
function expense_ensure_tables($conn)
{
    $queries = array(
        "CREATE TABLE IF NOT EXISTS expense_category (
            category_id INT(11) NOT NULL AUTO_INCREMENT,
            category_code VARCHAR(20) NOT NULL,
            category_name VARCHAR(150) NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 0,
            created_at VARCHAR(20) DEFAULT NULL,
            created_by INT(11) DEFAULT NULL,
            updated_at VARCHAR(20) DEFAULT NULL,
            updated_by INT(11) DEFAULT NULL,
            PRIMARY KEY (category_id),
            UNIQUE KEY uk_expense_category_code (category_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS expense_vendor (
            vendor_id INT(11) NOT NULL AUTO_INCREMENT,
            vendor_name VARCHAR(200) NOT NULL,
            vendor_type VARCHAR(50) NOT NULL DEFAULT 'OTHER',
            mobile VARCHAR(15) DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL,
            status TINYINT(1) NOT NULL DEFAULT 0,
            created_at VARCHAR(20) DEFAULT NULL,
            created_by INT(11) DEFAULT NULL,
            updated_at VARCHAR(20) DEFAULT NULL,
            updated_by INT(11) DEFAULT NULL,
            PRIMARY KEY (vendor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS extra_expense (
            expense_id INT(11) NOT NULL AUTO_INCREMENT,
            expense_no VARCHAR(50) NOT NULL,
            expense_date VARCHAR(20) NOT NULL,
            grn_no VARCHAR(50) NOT NULL,
            transaction_id INT(11) NOT NULL DEFAULT 0,
            trans_table VARCHAR(30) NOT NULL DEFAULT '',
            category_id INT(11) NOT NULL DEFAULT 0,
            vendor_id INT(11) NOT NULL DEFAULT 0,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            payment_mode VARCHAR(30) DEFAULT NULL,
            paid_by VARCHAR(150) DEFAULT NULL,
            description TEXT,
            status TINYINT(1) NOT NULL DEFAULT 0,
            created_at VARCHAR(20) DEFAULT NULL,
            created_by INT(11) DEFAULT NULL,
            updated_at VARCHAR(20) DEFAULT NULL,
            updated_by INT(11) DEFAULT NULL,
            PRIMARY KEY (expense_id),
            KEY idx_extra_expense_grn (grn_no),
            KEY idx_extra_expense_date (expense_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    );

    foreach ($queries as $sql) {
        mysqli_query($conn, $sql);
    }

    $seed_check = mysqli_query($conn, 'SELECT COUNT(*) AS cnt FROM expense_category');
    if ($seed_check) {
        $seed_row = mysqli_fetch_assoc($seed_check);
        if ((int) ($seed_row['cnt'] ?? 0) === 0) {
            $today = date('d-m-Y');
            $defaults = array(
                array('HALT', 'Halting / Detention'),
                array('LOAD', 'Extra Loading / Unloading'),
                array('DRVR', 'Driver Allowance / Batta'),
                array('TOLL', 'Toll / Penalty (Extra)'),
                array('REPR', 'Repair (On Trip)'),
                array('MISC', 'Miscellaneous Extra'),
            );
            foreach ($defaults as $row) {
                $code = mysqli_real_escape_string($conn, $row[0]);
                $name = mysqli_real_escape_string($conn, $row[1]);
                mysqli_query($conn, "INSERT INTO expense_category (category_code, category_name, status, created_at) VALUES ('$code', '$name', 0, '$today')");
            }
        }
    }
}

function expense_require_admin()
{
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'AD') {
        header('Location: dashboard.php');
        exit;
    }
}
