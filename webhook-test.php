<?php
// Simple webhook test - logs to file
$log_file = __DIR__ . '/webhook-debug.log';

// Get request data
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);
if (!is_array($data)) {
    $data = $_REQUEST;
}

// Log everything
$log_msg = "[" . date('Y-m-d H:i:s') . "] ";
$log_msg .= "Method: " . $_SERVER['REQUEST_METHOD'] . " | ";
$log_msg .= "Data: " . json_encode($data) . " | ";
$log_msg .= "Headers: " . json_encode(getallheaders()) . "\n";

file_put_contents($log_file, $log_msg, FILE_APPEND);

// Echo challenge if validation request
if (isset($data['type']) && $data['type'] === 'url_validation') {
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode(['challenge' => $data['challenge']]);
} else {
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode(['status' => true, 'message' => 'Webhook received']);
}
exit;
?>
