<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Razorpay backend APIs (Orders, verify payment, optional webhook).
 *
 * Keys: Admin → Payment settings (general_settings: razorpay_key_id, razorpay_secret_key),
 *       optional razorpay_webhook_secret for webhooks,
 *       or override via application/config/razorpay.php / env vars.
 */
class Razorpay extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->config('razorpay', true);
	}

	protected function read_json_body()
	{
		$raw = file_get_contents('php://input');
		$decoded = json_decode($raw, true);

		return is_array($decoded) ? $decoded : array();
	}

	/**
	 * general_settings row by key_text; velue_text is the project spelling.
	 */
	protected function setting_val($key_text)
	{
		$data = $this->db_model->select_data('*', 'general_settings', array('key_text' => (string) $key_text), 1);
		if (empty($data) || !isset($data[0]['velue_text'])) {
			return '';
		}

		return trim((string) $data[0]['velue_text']);
	}

	protected function razorpay_credentials()
	{
		$mode = strtolower((string) $this->config->item('razorpay_mode', 'razorpay'));
		if ($mode !== 'live' && $mode !== 'test') {
			$mode = 'test';
		}

		$key_id = (string) $this->config->item('razorpay_key_id', 'razorpay');
		$key_secret = (string) $this->config->item('razorpay_key_secret', 'razorpay');
		$webhook_secret = (string) $this->config->item('razorpay_webhook_secret', 'razorpay');

		$test_db_key_id = $this->setting_val('razorpay_test_key_id');
		$test_db_key_secret = $this->setting_val('razorpay_test_secret_key');
		$live_db_key_id = $this->setting_val('razorpay_live_key_id');
		$live_db_key_secret = $this->setting_val('razorpay_live_secret_key');

		if ($key_id === '' && $mode === 'test') {
			$key_id = $test_db_key_id;
		}
		if ($key_secret === '' && $mode === 'test') {
			$key_secret = $test_db_key_secret;
		}
		if ($key_id === '' && $mode === 'live') {
			$key_id = $live_db_key_id;
		}
		if ($key_secret === '' && $mode === 'live') {
			$key_secret = $live_db_key_secret;
		}

		if ($key_id === '') {
			$key_id = $this->setting_val('razorpay_key_id');
		}
		if ($key_secret === '') {
			$key_secret = $this->setting_val('razorpay_secret_key');
		}
		if ($webhook_secret === '') {
			$webhook_secret = $this->setting_val('razorpay_webhook_secret');
		}

		return array(
			'key_id' => $key_id,
			'key_secret' => $key_secret,
			'mode' => $mode,
			'webhook_secret' => $webhook_secret,
		);
	}

	protected function load_razorpay_lib()
	{
		$c = $this->razorpay_credentials();
		$this->load->library('razorpay_api', array(
			'key_id' => $c['key_id'],
			'key_secret' => $c['key_secret'],
		));

		return $c;
	}

	protected function masked_key_id($key_id)
	{
		$key_id = trim((string) $key_id);
		if ($key_id === '') {
			return '(empty)';
		}
		if (strlen($key_id) <= 10) {
			return substr($key_id, 0, 4) . '***';
		}

		return substr($key_id, 0, 10) . '***';
	}

	/**
	 * POST api/payment/razorpay/create-order
	 * Auth: student | teacher | institute (Bearer / body token).
	 * JSON: amount_in_paise (int, required) OR amount_in_rupees (float) — one is required.
	 *       currency (optional, default INR), receipt (optional), notes (optional object).
	 */
	public function create_order()
	{

		
		$data = array_merge($_REQUEST, $this->read_json_body());
		$payload = $this->require_auth_payload(array('student', 'teacher', 'institute'), is_array($data) ? $data : null);
		if ($payload === false) {
			return;
		}

		$this->load_razorpay_lib();
		if (!$this->razorpay_api->has_credentials()) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Razorpay is not configured (key id / secret).',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		$amount_paise = 0;
		if (isset($data['amount_in_paise']) && is_numeric($data['amount_in_paise'])) {
			$amount_paise = (int) $data['amount_in_paise'];
		} elseif (isset($data['amount_in_rupees']) && is_numeric($data['amount_in_rupees'])) {
			$amount_paise = (int) round((float) $data['amount_in_rupees'] * 100);
		}

		if ($amount_paise < 100) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Invalid amount: send amount_in_paise (min 100) or amount_in_rupees (min 1.00).',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		$currency = isset($data['currency']) ? (string) $data['currency'] : 'INR';
		$receipt = isset($data['receipt']) ? (string) $data['receipt'] : null;
		$notes = array();
		if (!empty($data['notes']) && is_array($data['notes'])) {
			$notes = $data['notes'];
		}
		$notes['uid'] = (string) $payload['uid'];
		$notes['ut'] = (string) $payload['ut'];

		$resp = $this->razorpay_api->create_order($amount_paise, $currency, $receipt, $notes);
		if (!$resp['ok'] || !is_array($resp['body'])) {
			$msg = 'Could not create order.';
			if (is_array($resp['body']) && isset($resp['body']['error']['description'])) {
				$msg = (string) $resp['body']['error']['description'];
			}
			if ((int) $resp['http_code'] === 401) {
				$creds = $this->razorpay_credentials();
				$msg = 'Razorpay authentication failed. Use valid ' . strtoupper($creds['mode']) . ' key id/secret pair.';
				$debug = array(
					'mode' => strtoupper($creds['mode']),
					'keyIdHint' => $this->masked_key_id(isset($creds['key_id']) ? $creds['key_id'] : ''),
				);
			}
			$out = array(
				'status' => 'false',
				'msg' => $msg,
				'httpCode' => $resp['http_code'],
			);
			if (isset($debug) && is_array($debug)) {
				$out['debug'] = $debug;
			}
			echo json_encode($out, JSON_UNESCAPED_SLASHES);
			return;
		}

		$b = $resp['body'];
		echo json_encode(array(
			'status' => 'true',
			'msg' => $this->lang->line('ltr_fetch_successfully'),
			'order' => array(
				'id' => isset($b['id']) ? $b['id'] : '',
				'amount' => isset($b['amount']) ? (int) $b['amount'] : $amount_paise,
				'currency' => isset($b['currency']) ? $b['currency'] : $currency,
				'receipt' => isset($b['receipt']) ? $b['receipt'] : '',
				'status' => isset($b['status']) ? $b['status'] : '',
			),
			'keyId' => $this->razorpay_credentials()['key_id'],
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * POST api/payment/razorpay/verify-payment
	 * Auth: student | teacher | institute.
	 * JSON: razorpay_order_id, razorpay_payment_id, razorpay_signature (required).
	 * Optional: student_id, batch_id — if caller is that student, records student_payment_history (Razorpay).
	 */
	public function verify_payment()
	{
		$data = array_merge($_REQUEST, $this->read_json_body());
		$payload = $this->require_auth_payload(array('student', 'teacher', 'institute'), is_array($data) ? $data : null);
		if ($payload === false) {
			return;
		}

		$this->load_razorpay_lib();
		if (!$this->razorpay_api->has_credentials()) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Razorpay is not configured.',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		$order_id = isset($data['razorpay_order_id']) ? trim((string) $data['razorpay_order_id']) : '';
		$payment_id = isset($data['razorpay_payment_id']) ? trim((string) $data['razorpay_payment_id']) : '';
		$signature = isset($data['razorpay_signature']) ? trim((string) $data['razorpay_signature']) : '';

		if ($order_id === '' || $payment_id === '' || $signature === '') {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'razorpay_order_id, razorpay_payment_id, and razorpay_signature are required.',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		if (!$this->razorpay_api->verify_signature($order_id, $payment_id, $signature)) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Invalid payment signature.',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		$pay = $this->razorpay_api->fetch_payment($payment_id);
		$amount_paise = 0;
		$currency = 'INR';
		$status = '';
		if ($pay['ok'] && is_array($pay['body'])) {
			$amount_paise = isset($pay['body']['amount']) ? (int) $pay['body']['amount'] : 0;
			$currency = isset($pay['body']['currency']) ? (string) $pay['body']['currency'] : 'INR';
			$status = isset($pay['body']['status']) ? (string) $pay['body']['status'] : '';
		}

		$recorded = false;
		if (!empty($data['student_id']) && !empty($data['batch_id']) && $payload['ut'] === 'student') {
			$sid = (int) $data['student_id'];
			$bid = (int) $data['batch_id'];
			if ($sid === (int) $payload['uid'] && $bid > 0 && $sid > 0) {
				$dup = $this->db_model->select_data('id', 'student_payment_history', array(
					'student_id' => $sid,
					'batch_id' => $bid,
					'transaction_id' => $payment_id,
				), 1);
				if (empty($dup)) {
					$st = $this->db_model->select_data('admin_id', 'students', array('id' => $sid), 1);
					if (!empty($st)) {
						$admin_id = (int) $st[0]['admin_id'];
						$amount_record = $amount_paise > 0 ? (int) round($amount_paise / 100) : 0;
						if ($amount_record < 1) {
							$amount_record = 1;
						}
						$new_id = $this->db_model->insert_data('student_payment_history', array(
							'student_id' => $sid,
							'batch_id' => $bid,
							'transaction_id' => $payment_id,
							'mode' => 'razorpay',
							'amount' => $amount_record,
							'admin_id' => $admin_id,
						));
						$recorded = ((int) $new_id > 0);
					}
				}
			}
		}

		echo json_encode(array(
			'status' => 'true',
			'msg' => 'Payment verified.',
			'payment' => array(
				'id' => $payment_id,
				'orderId' => $order_id,
				'amountPaise' => $amount_paise,
				'currency' => $currency,
				'gatewayStatus' => $status,
			),
			'recordedInHistory' => $recorded,
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * POST api/payment/razorpay/fetch-payment
	 * Auth: student | teacher | institute.
	 * JSON / query: payment_id
	 */
	public function fetch_payment()
	{
		$data = array_merge($_REQUEST, $this->read_json_body());
		$payload = $this->require_auth_payload(array('student', 'teacher', 'institute'), is_array($data) ? $data : null);
		if ($payload === false) {
			return;
		}

		$this->load_razorpay_lib();
		if (!$this->razorpay_api->has_credentials()) {
			echo json_encode(array('status' => 'false', 'msg' => 'Razorpay is not configured.'), JSON_UNESCAPED_SLASHES);
			return;
		}

		$payment_id = isset($data['payment_id']) ? trim((string) $data['payment_id']) : '';
		if ($payment_id === '') {
			echo json_encode(array('status' => 'false', 'msg' => 'payment_id is required.'), JSON_UNESCAPED_SLASHES);
			return;
		}

		$resp = $this->razorpay_api->fetch_payment($payment_id);
		echo json_encode(array(
			'status' => $resp['ok'] ? 'true' : 'false',
			'msg' => $resp['ok'] ? $this->lang->line('ltr_fetch_successfully') : 'Request failed',
			'httpCode' => $resp['http_code'],
			'payment' => is_array($resp['body']) ? $resp['body'] : null,
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * POST api/payment/razorpay/order-status
	 * Auth: student | teacher | institute.
	 * JSON / query: order_id
	 */
	public function order_status()
	{
		$data = array_merge($_REQUEST, $this->read_json_body());
		$payload = $this->require_auth_payload(array('student', 'teacher', 'institute'), is_array($data) ? $data : null);
		if ($payload === false) {
			return;
		}

		$this->load_razorpay_lib();
		if (!$this->razorpay_api->has_credentials()) {
			echo json_encode(array('status' => 'false', 'msg' => 'Razorpay is not configured.'), JSON_UNESCAPED_SLASHES);
			return;
		}

		$order_id = isset($data['order_id']) ? trim((string) $data['order_id']) : '';
		if ($order_id === '') {
			echo json_encode(array('status' => 'false', 'msg' => 'order_id is required.'), JSON_UNESCAPED_SLASHES);
			return;
		}

		$resp = $this->razorpay_api->fetch_order($order_id);
		echo json_encode(array(
			'status' => $resp['ok'] ? 'true' : 'false',
			'msg' => $resp['ok'] ? $this->lang->line('ltr_fetch_successfully') : 'Request failed',
			'httpCode' => $resp['http_code'],
			'order' => is_array($resp['body']) ? $resp['body'] : null,
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * POST api/payment/razorpay/webhook
	 * No Bearer auth. Configure URL in Razorpay Dashboard → Webhooks.
	 * Set razorpay_webhook_secret in general_settings or RAZORPAY_WEBHOOK_SECRET / config.
	 */
	public function webhook()
	{
		$creds = $this->razorpay_credentials();
		$this->load->library('razorpay_api', array(
			'key_id' => $creds['key_id'],
			'key_secret' => $creds['key_secret'],
		));

		$raw = file_get_contents('php://input');
		$sig = $this->input->get_request_header('X-Razorpay-Signature', true);
		if (empty($sig)) {
			$sig = isset($_SERVER['HTTP_X_RAZORPAY_SIGNATURE']) ? (string) $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] : '';
		}

		$wh = $creds['webhook_secret'];
		if ($wh === '' || $raw === '') {
			$this->output->set_status_header(400);
			echo json_encode(array('status' => 'false', 'msg' => 'Webhook secret not configured or empty body.'));
			return;
		}

		if (!$this->razorpay_api->verify_webhook_with_secret($raw, $sig, $wh)) {
			$this->output->set_status_header(400);
			echo json_encode(array('status' => 'false', 'msg' => 'Invalid webhook signature.'));
			return;
		}

		$event = json_decode($raw, true);
		$event_name = is_array($event) && isset($event['event']) ? (string) $event['event'] : '';

		$this->output->set_status_header(200);
		echo json_encode(array(
			'status' => 'true',
			'msg' => 'Webhook received',
			'event' => $event_name,
		), JSON_UNESCAPED_SLASHES);
	}
}
