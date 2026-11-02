<?php

/**
 * Customer NPS ratings (same store as Laravel public site: elitewave360.shipment_ratings).
 */
define('EW_SHIPMENT_RATINGS_DB', 'elitewave360');
define('EW_SHIPMENT_RATINGS_TABLE', 'shipment_ratings');

function ew_shipment_rating_normalize_code($tracking_code)
{
    return preg_replace('/\D+/', '', (string) $tracking_code);
}

function ew_shipment_rating_db()
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }
    if (!defined('EW_DB_HOST')) {
        require_once __DIR__ . '/database_config.php';
    }
    $conn = @mysqli_connect(EW_DB_HOST, EW_DB_USER, EW_DB_PASS, EW_SHIPMENT_RATINGS_DB);
    if (!$conn) {
        return null;
    }
    mysqli_set_charset($conn, 'utf8mb4');
    ew_shipment_rating_ensure_table($conn);
    return $conn;
}

function ew_shipment_rating_ensure_table($conn)
{
    $table = EW_SHIPMENT_RATINGS_TABLE;
    $sql = "CREATE TABLE IF NOT EXISTS `$table` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `tracking_code` VARCHAR(12) NOT NULL,
        `rating` TINYINT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP NULL DEFAULT NULL,
        `updated_at` TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `shipment_ratings_tracking_code_unique` (`tracking_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $sql);
}

function ew_shipment_rating_get($tracking_code)
{
    $code = ew_shipment_rating_normalize_code($tracking_code);
    if (strlen($code) !== 12) {
        return null;
    }
    $conn = ew_shipment_rating_db();
    if (!$conn) {
        return null;
    }
    $esc = mysqli_real_escape_string($conn, $code);
    $table = EW_SHIPMENT_RATINGS_TABLE;
    $q = mysqli_query($conn, "SELECT rating FROM `$table` WHERE tracking_code='$esc' LIMIT 1");
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        $rating = (int) $row['rating'];
        return ($rating >= 1 && $rating <= 10) ? $rating : null;
    }
    return null;
}

function ew_shipment_rating_save($tracking_code, $rating)
{
    $code = ew_shipment_rating_normalize_code($tracking_code);
    $rating = (int) $rating;
    if (strlen($code) !== 12 || $rating < 1 || $rating > 10) {
        return false;
    }
    $conn = ew_shipment_rating_db();
    if (!$conn) {
        return false;
    }
    $escCode = mysqli_real_escape_string($conn, $code);
    $table = EW_SHIPMENT_RATINGS_TABLE;
    $now = date('Y-m-d H:i:s');
    $check = mysqli_query($conn, "SELECT id FROM `$table` WHERE tracking_code='$escCode' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        return (bool) mysqli_query(
            $conn,
            "UPDATE `$table` SET rating='$rating', updated_at='$now' WHERE tracking_code='$escCode'"
        );
    }
    return (bool) mysqli_query(
        $conn,
        "INSERT INTO `$table` (tracking_code, rating, created_at, updated_at) VALUES ('$escCode', '$rating', '$now', '$now')"
    );
}
