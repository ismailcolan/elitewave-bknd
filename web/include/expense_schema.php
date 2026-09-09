<?php
/**
 * Expense Type Master — base table schema (Extra Expense module removed).
 */
function expense_ensure_tables($conn)
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS expense_category (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function expense_drop_legacy_module_tables($conn)
{
    mysqli_query($conn, 'DROP TABLE IF EXISTS extra_expense');
    mysqli_query($conn, 'DROP TABLE IF EXISTS expense_vendor');
}

function expense_require_admin()
{
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'AD') {
        header('Location: dashboard.php');
        exit;
    }
}
