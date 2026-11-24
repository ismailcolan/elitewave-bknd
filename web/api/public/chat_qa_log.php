<?php

error_reporting(0);
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	http_response_code(405);
	echo json_encode(array('ok' => false, 'message' => 'POST required.'));
	exit;
}

require_once __DIR__ . '/../../include/connect.php';
require_once __DIR__ . '/../../include/chatbot_qa_helpers.php';

$expected = getenv('CHAT_INTERNAL_TOKEN');
if ($expected === false || $expected === '') {
	$expected = 'ew360-chat-internal-7f3a9c2e';
}
$given = $_SERVER['HTTP_X_CHAT_TOKEN'] ?? '';
if (!is_string($given) || !hash_equals($expected, $given)) {
	http_response_code(403);
	echo json_encode(array('ok' => false, 'message' => 'Unauthorized.'));
	exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
	http_response_code(422);
	echo json_encode(array('ok' => false, 'message' => 'Invalid request.'));
	exit;
}

$result = chatbot_qa_save($conn, $input);
if (empty($result['ok'])) {
	http_response_code(422);
	echo json_encode(array(
		'ok' => false,
		'message' => $result['message'] ?? 'Save failed.',
	));
	exit;
}

echo json_encode(array(
	'ok' => true,
	'log_id' => (int) ($result['log_id'] ?? 0),
	'message' => $result['message'] ?? 'Saved.',
));
exit;