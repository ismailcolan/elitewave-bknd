<?php

function chatbot_qa_latest_log_id($conn, $tokenEsc)
{
	$find = mysqli_query($conn, "SELECT log_id FROM chatbot_qa_log WHERE session_token='$tokenEsc' ORDER BY log_id DESC LIMIT 1");
	if (!$find || !($row = mysqli_fetch_assoc($find))) {
		return 0;
	}
	return (int) ($row['log_id'] ?? 0);
}

function chatbot_qa_ensure_table($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS chatbot_qa_log (
		log_id INT(11) NOT NULL AUTO_INCREMENT,
		session_token VARCHAR(64) NOT NULL,
		visitor_name VARCHAR(150) NOT NULL DEFAULT '',
		visitor_mobile VARCHAR(20) NOT NULL DEFAULT '',
		customer_mode VARCHAR(20) NOT NULL DEFAULT 'new',
		client_id INT(11) NOT NULL DEFAULT 0,
		question TEXT NOT NULL,
		answer TEXT DEFAULT NULL,
		channel VARCHAR(30) NOT NULL DEFAULT 'auto',
		follow_up VARCHAR(20) DEFAULT NULL,
		delivery_status VARCHAR(30) NOT NULL DEFAULT 'logged',
		created_at VARCHAR(20) NOT NULL,
		updated_at VARCHAR(20) DEFAULT NULL,
		PRIMARY KEY (log_id),
		KEY idx_chatbot_qa_token (session_token),
		KEY idx_chatbot_qa_mobile (visitor_mobile),
		KEY idx_chatbot_qa_created (created_at)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function chatbot_qa_esc($conn, $value)
{
	return mysqli_real_escape_string($conn, (string) $value);
}

function chatbot_qa_now()
{
	return date('Y-m-d H:i:s');
}

function chatbot_qa_save($conn, array $data)
{
	chatbot_qa_ensure_table($conn);

	$token = trim((string) ($data['session_token'] ?? ''));
	if ($token === '') {
		return array('ok' => false, 'message' => 'Session token is required.');
	}

	$followUp = trim((string) ($data['follow_up'] ?? ''));
	$question = trim((string) ($data['question'] ?? ''));
	$answerOnly = array_key_exists('answer', $data) && $question === '' && $followUp === '';

	if ($followUp !== '' && $question === '') {
		$allowedFollow = array('yes', 'no');
		if (!in_array($followUp, $allowedFollow, true)) {
			return array('ok' => false, 'message' => 'Invalid follow-up value.');
		}
		$tokenEsc = chatbot_qa_esc($conn, $token);
		$logId = chatbot_qa_latest_log_id($conn, $tokenEsc);
		if ($logId <= 0) {
			return array('ok' => false, 'message' => 'Chat log not found.');
		}
		$followEsc = chatbot_qa_esc($conn, $followUp);
		$now = chatbot_qa_now();
		mysqli_query($conn, "UPDATE chatbot_qa_log SET follow_up='$followEsc', updated_at='$now' WHERE log_id='$logId'");
		return array('ok' => true, 'log_id' => $logId, 'message' => 'Follow-up saved.');
	}

	if (isset($data['delivery_status']) && $question === '' && $followUp === '') {
		$status = trim((string) $data['delivery_status']);
		$allowed = array('logged', 'emailed', 'failed');
		if (!in_array($status, $allowed, true)) {
			return array('ok' => false, 'message' => 'Invalid delivery status.');
		}
		$tokenEsc = chatbot_qa_esc($conn, $token);
		$logId = chatbot_qa_latest_log_id($conn, $tokenEsc);
		if ($logId <= 0) {
			$save = chatbot_qa_save($conn, array_merge($data, array(
				'question' => trim((string) ($data['question_fallback'] ?? '(Support enquiry)')),
				'channel' => 'support',
			)));
			if (empty($save['ok'])) {
				return $save;
			}
			$logId = (int) ($save['log_id'] ?? 0);
		}
		$statusEsc = chatbot_qa_esc($conn, $status);
		$answerEsc = '';
		if (isset($data['answer'])) {
			$answerEsc = chatbot_qa_esc($conn, (string) $data['answer']);
		}
		$now = chatbot_qa_now();
		$setAnswer = $answerEsc !== '' ? ", answer='$answerEsc'" : '';
		mysqli_query($conn, "UPDATE chatbot_qa_log SET delivery_status='$statusEsc', updated_at='$now'$setAnswer WHERE log_id='$logId'");
		return array('ok' => true, 'log_id' => $logId, 'message' => 'Status updated.');
	}

	if ($question === '' && !$answerOnly) {
		return array('ok' => false, 'message' => 'Question is required.');
	}

	$name = chatbot_qa_esc($conn, trim((string) ($data['visitor_name'] ?? '')));
	$mobile = chatbot_qa_esc($conn, preg_replace('/\D+/', '', (string) ($data['visitor_mobile'] ?? '')));
	$customerMode = trim((string) ($data['customer_mode'] ?? 'new'));
	if ($customerMode !== 'existing') {
		$customerMode = 'new';
	}
	$customerMode = chatbot_qa_esc($conn, $customerMode);
	$clientId = (int) ($data['client_id'] ?? 0);
	$channel = trim((string) ($data['channel'] ?? 'auto'));
	$allowedChannels = array('auto', 'support');
	if (!in_array($channel, $allowedChannels, true)) {
		$channel = 'auto';
	}
	$channel = chatbot_qa_esc($conn, $channel);
	$answer = isset($data['answer']) ? chatbot_qa_esc($conn, (string) $data['answer']) : '';
	$questionEsc = chatbot_qa_esc($conn, $question);
	$tokenEsc = chatbot_qa_esc($conn, $token);
	$deliveryInput = trim((string) ($data['delivery_status'] ?? ''));
	$delivery = 'logged';
	if (in_array($deliveryInput, array('logged', 'emailed', 'failed'), true)) {
		$delivery = $deliveryInput;
	}
	$delivery = chatbot_qa_esc($conn, $delivery);
	$now = chatbot_qa_now();

	$existing = mysqli_query($conn, "SELECT log_id FROM chatbot_qa_log WHERE session_token='$tokenEsc' LIMIT 1");
	if ($existing && mysqli_num_rows($existing) > 0 && $question !== '') {
		$row = mysqli_fetch_assoc($existing);
		$logId = (int) ($row['log_id'] ?? 0);
		$sql = "UPDATE chatbot_qa_log SET
			visitor_name='$name',
			visitor_mobile='$mobile',
			customer_mode='$customerMode',
			client_id='$clientId',
			question='$questionEsc',
			answer=" . ($answer === '' ? 'NULL' : "'$answer'") . ",
			channel='$channel',
			delivery_status='$delivery',
			updated_at='$now'
			WHERE log_id='$logId'";
		mysqli_query($conn, $sql);
		return array('ok' => true, 'log_id' => $logId, 'message' => 'Saved.');
	}

	$sql = "INSERT INTO chatbot_qa_log
		(session_token, visitor_name, visitor_mobile, customer_mode, client_id, question, answer, channel, delivery_status, created_at)
		VALUES (
			'$tokenEsc', '$name', '$mobile', '$customerMode', '$clientId',
			'$questionEsc', " . ($answer === '' ? 'NULL' : "'$answer'") . ",
			'$channel', '$delivery', '$now'
		)";
	if (!mysqli_query($conn, $sql)) {
		return array('ok' => false, 'message' => 'Could not save the chat log.');
	}

	return array(
		'ok' => true,
		'log_id' => (int) mysqli_insert_id($conn),
		'message' => 'Saved.',
	);
}

function chatbot_qa_list_rows($conn, $limit = 1000)
{
	chatbot_qa_ensure_table($conn);
	$limit = max(1, min(5000, (int) $limit));
	$rows = array();
	$sql = "SELECT * FROM chatbot_qa_log ORDER BY log_id DESC LIMIT $limit";
	$result = mysqli_query($conn, $sql);
	if (!$result) {
		return $rows;
	}
	while ($row = mysqli_fetch_assoc($result)) {
		$rows[] = $row;
	}
	return $rows;
}

function chatbot_qa_channel_label($code)
{
	$map = array(
		'auto' => 'Bot answered',
		'support' => 'Support team',
	);
	return $map[$code] ?? $code;
}

function chatbot_qa_delivery_label($code)
{
	$map = array(
		'logged' => 'Logged',
		'emailed' => 'Emailed to support',
		'failed' => 'Email failed',
	);
	return $map[$code] ?? $code;
}