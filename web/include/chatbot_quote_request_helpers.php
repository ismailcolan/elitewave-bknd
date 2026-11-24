<?php

require_once __DIR__ . '/quotation_functions.php';

function chatbot_quote_request_ensure_table($conn)
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS chatbot_quote_request (
        request_id INT(11) NOT NULL AUTO_INCREMENT,
        request_no VARCHAR(40) NOT NULL,
        client_token VARCHAR(64) NOT NULL,
        email VARCHAR(120) NOT NULL,
        contact_mobile VARCHAR(20) NOT NULL,
        gst_no VARCHAR(20) DEFAULT NULL,
        company_name VARCHAR(255) NOT NULL,
        origin_city_id INT(11) NOT NULL DEFAULT 0,
        origin_text VARCHAR(255) NOT NULL DEFAULT '',
        destination_city_id INT(11) NOT NULL DEFAULT 0,
        destination_text VARCHAR(255) NOT NULL DEFAULT '',
        transhipment_type VARCHAR(40) NOT NULL DEFAULT 'single_location',
        locations_json LONGTEXT,
        mode_id INT(11) NOT NULL DEFAULT 0,
        mode_label VARCHAR(120) NOT NULL DEFAULT '',
        weight DECIMAL(14,3) NOT NULL DEFAULT 0,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        notify_seen TINYINT(1) NOT NULL DEFAULT 0,
        quotation_id INT(11) NOT NULL DEFAULT 0,
        created_at VARCHAR(20) NOT NULL,
        updated_at VARCHAR(20) DEFAULT NULL,
        PRIMARY KEY (request_id),
        UNIQUE KEY uk_chatbot_qr_no (request_no),
        UNIQUE KEY uk_chatbot_qr_token (client_token),
        KEY idx_chatbot_qr_status (status),
        KEY idx_chatbot_qr_notify (notify_seen),
        KEY idx_chatbot_qr_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    chatbot_quote_request_ensure_notify_seen_column($conn);
}

function chatbot_quote_request_ensure_notify_seen_column($conn)
{
    $q = mysqli_query($conn, "SHOW COLUMNS FROM chatbot_quote_request LIKE 'notify_seen'");
    if ($q && mysqli_num_rows($q) > 0) {
        return;
    }
    mysqli_query($conn, "ALTER TABLE chatbot_quote_request ADD COLUMN notify_seen TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
    mysqli_query($conn, "ALTER TABLE chatbot_quote_request ADD KEY idx_chatbot_qr_notify (notify_seen)");
}

function chatbot_quote_request_unseen_count($conn)
{
    chatbot_quote_request_ensure_table($conn);
    $q = mysqli_query($conn, 'SELECT COUNT(*) AS c FROM chatbot_quote_request WHERE notify_seen = 0');
    if (!$q) {
        return 0;
    }
    $row = mysqli_fetch_assoc($q);
    return (int) ($row['c'] ?? 0);
}

function chatbot_quote_request_mark_notifications_seen($conn)
{
    chatbot_quote_request_ensure_table($conn);
    mysqli_query($conn, 'UPDATE chatbot_quote_request SET notify_seen = 1 WHERE notify_seen = 0');
}

function chatbot_quote_request_mark_notification_seen($conn, $request_id)
{
    chatbot_quote_request_ensure_table($conn);
    $id = (int) $request_id;
    if ($id <= 0) {
        return;
    }
    mysqli_query($conn, "UPDATE chatbot_quote_request SET notify_seen = 1 WHERE request_id = '$id'");
}

if (!function_exists('ew_notification_time_ago')) {
    function ew_notification_time_ago($ts)
    {
        if (!$ts) {
            return '';
        }
        $diff = time() - (int) $ts;
        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'm ago';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . 'h ago';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . 'd ago';
        }
        return date('d M Y', (int) $ts);
    }
}

if (!function_exists('ew_notification_item_html')) {
    /**
     * Renders one row of the admin header notification dropdown.
     * $type: 'quote' (chatbot quote request) or 'pickup' (request for pickup).
     */
    function ew_notification_item_html($href, $type, $title, $ref, $meta, $ts = null)
    {
        $e = function ($v) {
            return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        };
        $types = array(
            'quote'  => array('icon' => 'fa-comments', 'label' => 'Quote Request'),
            'pickup' => array('icon' => 'fa-truck', 'label' => 'Pickup Request'),
            'inquiry' => array('icon' => 'fa-user-plus', 'label' => 'User Inquiry'),
        );
        $t = $types[$type] ?? $types['quote'];
        $when = ew_notification_time_ago($ts);

        return '<li class="ew-notif-item ew-notif-' . $e($type) . '">'
            . '<a href="' . $e($href) . '" title="' . $e($title) . '">'
            . '<span class="ew-notif-icon"><i class="fa ' . $t['icon'] . '" aria-hidden="true"></i></span>'
            . '<span class="ew-notif-body">'
            . '<span class="ew-notif-top">'
            . '<span class="ew-notif-type">' . $e($t['label']) . '</span>'
            . ($when !== '' ? '<span class="ew-notif-time">' . $e($when) . '</span>' : '')
            . '</span>'
            . '<span class="ew-notif-title">' . $e($title) . '</span>'
            . ($ref !== '' ? '<span class="ew-notif-ref">' . $e($ref) . '</span>' : '')
            . ($meta !== '' ? '<span class="ew-notif-meta">' . $e($meta) . '</span>' : '')
            . '</span>'
            . '</a></li>';
    }
}

function chatbot_quote_request_notification_feed($conn, $limit = 8)
{
    chatbot_quote_request_ensure_table($conn);
    $limit = max(1, min(20, (int) $limit));
    $items = array();
    $q = mysqli_query($conn, "SELECT request_id, request_no, company_name, email, origin_text, destination_text, created_at
        FROM chatbot_quote_request
        ORDER BY request_id DESC
        LIMIT $limit");
    if (!$q) {
        return $items;
    }
    while ($row = mysqli_fetch_assoc($q)) {
        $title = trim((string) ($row['company_name'] ?? ''));
        if ($title === '') {
            $title = trim((string) ($row['email'] ?? ''));
        }
        if ($title === '') {
            $title = 'Chatbot quote request';
        }
        $route = trim((string) ($row['origin_text'] ?? ''));
        $dest = trim((string) ($row['destination_text'] ?? ''));
        if ($route !== '' && $dest !== '') {
            $route .= ' → ' . $dest;
        } elseif ($dest !== '') {
            $route = $dest;
        }
        $ts = strtotime((string) ($row['created_at'] ?? ''));
        if ($ts === false) {
            $ts = (int) ($row['request_id'] ?? 0);
        }
        $items[] = array(
            'sort' => $ts,
            'html' => ew_notification_item_html(
                'chatbot_quote_request_view.php?id=' . (int) $row['request_id'],
                'quote',
                $title,
                (string) $row['request_no'],
                $route !== '' ? $route : 'Chatbot quote request',
                strtotime((string) ($row['created_at'] ?? ''))
            ),
        );
    }
    return $items;
}

function chatbot_quote_request_status_options()
{
    return array(
        'pending' => 'Pending',
        'under_review' => 'Under Review',
        'forwarded' => 'Forwarded',
        'rejected' => 'Rejected',
    );
}

function chatbot_quote_request_status_badge($status)
{
    $status = (string) $status;
    $labels = chatbot_quote_request_status_options();
    $label = isset($labels[$status]) ? $labels[$status] : ucfirst(str_replace('_', ' ', $status));
    $classMap = array(
        'pending' => 'awaiting_customer',
        'under_review' => 'sent',
        'forwarded' => 'list_approved',
        'rejected' => 'rejected',
    );
    $class = isset($classMap[$status]) ? $classMap[$status] : 'draft';
    return '<span class="ew-status-chip ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
}

function chatbot_quote_request_display($value)
{
    $text = trim((string) $value);
    return $text !== '' ? htmlspecialchars($text, ENT_QUOTES, 'UTF-8') : '—';
}

function chatbot_quote_request_allocate_no($conn)
{
    chatbot_quote_request_ensure_table($conn);
    $prefix = 'QREQ-' . date('Ymd') . '-';
    $like = mysqli_real_escape_string($conn, $prefix . '%');
    $q = mysqli_query($conn, "SELECT request_no FROM chatbot_quote_request WHERE request_no LIKE '$like' ORDER BY request_id DESC LIMIT 1");
    $next = 1;
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        $parts = explode('-', (string) ($row['request_no'] ?? ''));
        $tail = (int) end($parts);
        if ($tail > 0) {
            $next = $tail + 1;
        }
    }
    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

function chatbot_quote_request_valid_gst($gst)
{
    $gst = strtoupper(trim((string) $gst));
    if ($gst === '') {
        return false;
    }
    return (bool) preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gst);
}

function chatbot_quote_request_save($conn, array $data)
{
    chatbot_quote_request_ensure_table($conn);

    $token = trim((string) ($data['client_token'] ?? ''));
    if ($token === '') {
        return array('ok' => false, 'message' => 'Duplicate protection token missing.');
    }
    $tokenEsc = mysqli_real_escape_string($conn, $token);
    $exists = mysqli_query($conn, "SELECT request_id, request_no, status FROM chatbot_quote_request WHERE client_token='$tokenEsc' LIMIT 1");
    if ($exists && ($row = mysqli_fetch_assoc($exists))) {
        return array(
            'ok' => true,
            'request_id' => (int) $row['request_id'],
            'request_no' => $row['request_no'],
            'status' => $row['status'],
            'message' => 'Already submitted.',
        );
    }

    $email = trim((string) ($data['email'] ?? ''));
    $mobile = preg_replace('/\D+/', '', (string) ($data['contact_mobile'] ?? ''));
    $gst = strtoupper(trim((string) ($data['gst_no'] ?? '')));
    $company = trim((string) ($data['company_name'] ?? ''));
    $weight = (float) ($data['weight'] ?? 0);
    $modeId = (int) ($data['mode_id'] ?? 0);
    $modeLabel = trim((string) ($data['mode_label'] ?? ''));
    $originId = (int) ($data['origin_city_id'] ?? 0);
    $originText = trim((string) ($data['origin_text'] ?? ''));
    $destId = (int) ($data['destination_city_id'] ?? 0);
    $destText = trim((string) ($data['destination_text'] ?? ''));
    $type = trim((string) ($data['transhipment_type'] ?? 'single_location'));
    if ($type !== 'multiple_location') {
        $type = 'single_location';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return array('ok' => false, 'message' => 'Enter a valid email address.');
    }
    if (strlen($mobile) !== 10) {
        return array('ok' => false, 'message' => 'Enter a valid 10-digit contact number.');
    }
    if ($gst !== '' && !chatbot_quote_request_valid_gst($gst)) {
        return array('ok' => false, 'message' => 'Enter a valid GST number.');
    }
    if ($originText === '' || $destText === '') {
        return array('ok' => false, 'message' => 'Origin and destination are required.');
    }
    if ($modeId <= 0 || $modeLabel === '') {
        return array('ok' => false, 'message' => 'Select a mode of transport.');
    }
    if ($weight <= 0) {
        return array('ok' => false, 'message' => 'Weight must be greater than zero.');
    }

    $locations = isset($data['locations']) && is_array($data['locations']) ? $data['locations'] : array();
    if ($type === 'single_location') {
        $locations = array();
    }
    if ($type === 'multiple_location' && count($locations) < 1) {
        return array('ok' => false, 'message' => 'Add at least one additional location.');
    }
    if ($type === 'multiple_location') {
        foreach ($locations as $loc) {
            if ((int) ($loc['city_id'] ?? 0) <= 0) {
                return array('ok' => false, 'message' => 'Select each additional location from suggestions.');
            }
        }
    }

    $requestNo = chatbot_quote_request_allocate_no($conn);
    $now = date('Y-m-d H:i:s');
    $locationsJson = mysqli_real_escape_string($conn, json_encode($locations));
    $sql = "INSERT INTO chatbot_quote_request (
            request_no, client_token, email, contact_mobile, gst_no, company_name,
            origin_city_id, origin_text, destination_city_id, destination_text,
            transhipment_type, locations_json, mode_id, mode_label, weight,
            status, created_at
        ) VALUES (
            '" . mysqli_real_escape_string($conn, $requestNo) . "',
            '$tokenEsc',
            '" . mysqli_real_escape_string($conn, $email) . "',
            '" . mysqli_real_escape_string($conn, $mobile) . "',
            '" . mysqli_real_escape_string($conn, $gst) . "',
            '" . mysqli_real_escape_string($conn, $company) . "',
            '$originId',
            '" . mysqli_real_escape_string($conn, $originText) . "',
            '$destId',
            '" . mysqli_real_escape_string($conn, $destText) . "',
            '" . mysqli_real_escape_string($conn, $type) . "',
            '$locationsJson',
            '$modeId',
            '" . mysqli_real_escape_string($conn, $modeLabel) . "',
            '$weight',
            'pending',
            '$now'
        )";

    if (!mysqli_query($conn, $sql)) {
        return array('ok' => false, 'message' => 'Could not save quote request.');
    }

    return array(
        'ok' => true,
        'request_id' => (int) mysqli_insert_id($conn),
        'request_no' => $requestNo,
        'status' => 'pending',
        'message' => 'Quote request saved.',
    );
}

function chatbot_quote_request_get($conn, $request_id)
{
    chatbot_quote_request_ensure_table($conn);
    $id = (int) $request_id;
    if ($id <= 0) {
        return null;
    }
    $q = mysqli_query($conn, "SELECT * FROM chatbot_quote_request WHERE request_id='$id' LIMIT 1");
    if (!$q || !($row = mysqli_fetch_assoc($q))) {
        return null;
    }
    chatbot_quote_request_normalize_forward_status($conn, $row);
    $row['locations'] = json_decode((string) ($row['locations_json'] ?? '[]'), true);
    if (!is_array($row['locations'])) {
        $row['locations'] = array();
    }
    return $row;
}

/** Re-open when marked forwarded but quotation was never saved or was deleted. */
function chatbot_quote_request_normalize_forward_status($conn, array &$row)
{
    if (($row['status'] ?? '') !== 'forwarded') {
        return;
    }
    $qid = (int) ($row['quotation_id'] ?? 0);
    $needsPending = ($qid <= 0);
    if (!$needsPending) {
        if (!function_exists('quotation_get')) {
            require_once __DIR__ . '/quotation_functions.php';
        }
        $needsPending = !quotation_get($conn, $qid);
    }
    if (!$needsPending) {
        return;
    }
    $id = (int) ($row['request_id'] ?? 0);
    if ($id > 0) {
        $now = date('Y-m-d H:i:s');
        mysqli_query($conn, "UPDATE chatbot_quote_request SET status='pending', quotation_id=0, updated_at='$now' WHERE request_id='$id'");
    }
    $row['status'] = 'pending';
    $row['quotation_id'] = 0;
}

function chatbot_quote_request_can_forward($conn, $row)
{
    if (!$row || !is_array($row)) {
        return false;
    }
    chatbot_quote_request_normalize_forward_status($conn, $row);
    return ($row['status'] ?? '') !== 'forwarded';
}

function chatbot_quote_request_list($conn, array $filters = array(), $limit = 1000)
{
    chatbot_quote_request_ensure_table($conn);
    $where = '1=1';
    if (!empty($filters['request_no'])) {
        $v = mysqli_real_escape_string($conn, trim((string) $filters['request_no']));
        $where .= " AND request_no LIKE '%$v%'";
    }
    if (!empty($filters['company_name'])) {
        $v = mysqli_real_escape_string($conn, trim((string) $filters['company_name']));
        $where .= " AND company_name LIKE '%$v%'";
    }
    if (!empty($filters['status'])) {
        $v = mysqli_real_escape_string($conn, trim((string) $filters['status']));
        $where .= " AND status='$v'";
    }
    if (!empty($filters['mode_id'])) {
        $where .= ' AND mode_id=' . (int) $filters['mode_id'];
    }
    if (!empty($filters['request_date'])) {
        $v = mysqli_real_escape_string($conn, trim((string) $filters['request_date']));
        $where .= " AND created_at LIKE '$v%'";
    }
    $limit = max(1, min(5000, (int) $limit));
    $rows = array();
    $q = mysqli_query($conn, "SELECT * FROM chatbot_quote_request WHERE $where ORDER BY request_id DESC LIMIT $limit");
    if (!$q) {
        return $rows;
    }
    while ($row = mysqli_fetch_assoc($q)) {
        $rows[] = $row;
    }
    return $rows;
}

function chatbot_quote_request_mark_forwarded($conn, $request_id, $quotation_id = 0)
{
    chatbot_quote_request_ensure_table($conn);
    $id = (int) $request_id;
    $quotationId = (int) $quotation_id;
    if ($id <= 0) {
        return array('ok' => false, 'message' => 'Invalid request.');
    }
    $row = chatbot_quote_request_get($conn, $id);
    if (!$row) {
        return array('ok' => false, 'message' => 'Request not found.');
    }
    if ($quotationId <= 0) {
        return array('ok' => false, 'message' => 'Quotation id required to mark forwarded.');
    }
    if (($row['status'] ?? '') === 'forwarded' && (int) ($row['quotation_id'] ?? 0) === $quotationId) {
        return array('ok' => true, 'message' => 'Already linked.', 'quotation_id' => $quotationId);
    }
    $now = date('Y-m-d H:i:s');
    mysqli_query($conn, "UPDATE chatbot_quote_request SET status='forwarded', quotation_id='$quotationId', updated_at='$now' WHERE request_id='$id'");
    return array('ok' => true, 'message' => 'Forwarded.');
}