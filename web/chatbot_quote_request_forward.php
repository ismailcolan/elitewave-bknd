<?php
require_once('include/ew_quotation_module_flag.php');
ew_quotation_module_deny_web();
require_once('include/connect.php');
require_once('include/chatbot_quote_request_helpers.php');

$id = (int) ($_GET['id'] ?? 0);
$row = chatbot_quote_request_get($conn, $id);
if (!$row) {
	header('Location: chatbot_quote_request_list.php');
	exit;
}
$linkedQid = (int) ($row['quotation_id'] ?? 0);
if (($row['status'] ?? '') === 'forwarded' && $linkedQid > 0) {
	header('Location: quotation.php?id=' . $linkedQid);
	exit;
}
header('Location: quotation.php?create=1&chatbot_quote_request_id=' . $id);
exit;