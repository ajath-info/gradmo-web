<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Razorpay backend APIs (Orders, verify payment, optional webhook).
 *
 * Keys: payment_gateway_api_credentials (same source as api/main/get_defaults_requirements),
 *       then Admin → Payment settings (general_settings),
 *       then application/config/razorpay.php / env vars.
 */
class Razorpay extends MY_Controller
{
	/** @var array|false|null Cached flip map of `student_payment_history` columns */
	protected $_student_payment_history_fields = null;

	/** @var array|false|null */
	protected $_student_batchs_table_fields = null;

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
		$db_row = $this->get_payment_gateway_api_credentials_row();
		$db_key_id = '';
		$db_key_secret = '';
		$db_webhook = '';
		$db_mode = '';

		if (!empty($db_row)) {
			$pg = strtolower(trim(isset($db_row['paymentgateway']) ? (string) $db_row['paymentgateway'] : ''));
			if ($pg === '' || $pg === 'razorpay') {
				if (isset($db_row['Key_id'])) {
					$db_key_id = trim((string) $db_row['Key_id']);
				} elseif (isset($db_row['key_id'])) {
					$db_key_id = trim((string) $db_row['key_id']);
				}
				$db_key_secret = isset($db_row['secret_key']) ? trim((string) $db_row['secret_key']) : '';
				$db_webhook = isset($db_row['webhook_secret']) ? trim((string) $db_row['webhook_secret']) : '';
				$m = isset($db_row['mode']) ? strtolower(trim((string) $db_row['mode'])) : '';
				if ($m === 'live' || $m === 'test') {
					$db_mode = $m;
				}
			}
		}

		$mode = $db_mode !== ''
			? $db_mode
			: strtolower((string) $this->config->item('razorpay_mode', 'razorpay'));
		if ($mode !== 'live' && $mode !== 'test') {
			$mode = 'test';
		}

		if ($db_key_id !== '' && $db_key_secret !== '') {
			$webhook_secret = $db_webhook;
			if ($webhook_secret === '') {
				$webhook_secret = (string) $this->config->item('razorpay_webhook_secret', 'razorpay');
			}
			if ($webhook_secret === '') {
				$webhook_secret = $this->setting_val('razorpay_webhook_secret');
			}

			return array(
				'key_id' => $db_key_id,
				'key_secret' => $db_key_secret,
				'mode' => $mode,
				'webhook_secret' => $webhook_secret,
			);
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
	 * Cached column map for `student_payment_history` (false = table missing).
	 *
	 * @return array<string, int>|false
	 */
	protected function get_student_payment_history_table_fields()
	{
		if ($this->_student_payment_history_fields !== null) {
			return $this->_student_payment_history_fields;
		}
		if (!$this->db->table_exists('student_payment_history')) {
			$this->_student_payment_history_fields = false;
			return false;
		}
		$this->_student_payment_history_fields = array_flip($this->db->list_fields('student_payment_history'));
		return $this->_student_payment_history_fields;
	}

	/**
	 * Cached column map for `student_batchs` (false = table missing).
	 *
	 * @return array<string, int>|false
	 */
	protected function get_student_batchs_table_fields()
	{
		if ($this->_student_batchs_table_fields !== null) {
			return $this->_student_batchs_table_fields;
		}
		if (!$this->db->table_exists('student_batchs')) {
			$this->_student_batchs_table_fields = false;
			return false;
		}
		$this->_student_batchs_table_fields = array_flip($this->db->list_fields('student_batchs'));
		return $this->_student_batchs_table_fields;
	}

	/**
	 * @param array<string, int>|false $fields_flip
	 * @return array<string, mixed>
	 */
	protected function filter_insert_row_for_table_fields($fields_flip, array $row)
	{
		if ($fields_flip === false || !is_array($fields_flip)) {
			return array();
		}
		$out = array();
		foreach ($row as $k => $v) {
			if (isset($fields_flip[$k])) {
				$out[$k] = $v;
			}
		}
		return $out;
	}

	/**
	 * Build one insert row for `student_payment_history` (Razorpay + optional plan columns).
	 * Unknown columns are dropped so DBs without the merged migration still work.
	 *
	 * @param int    $plan_id 0 if not supplied
	 * @return array<string, mixed>
	 */
	protected function build_razorpay_student_payment_history_row(
		$student_id,
		$batch_id,
		$admin_id,
		$order_id,
		$payment_id,
		$amount_rupees,
		$gateway_status,
		$plan_id = 0
	) {
		$f = $this->get_student_payment_history_table_fields();
		if ($f === false) {
			return array();
		}

		$student_id = (int) $student_id;
		$batch_id = (int) $batch_id;
		$admin_id = (int) $admin_id;
		$order_id = trim((string) $order_id);
		$payment_id = trim((string) $payment_id);
		$gateway_status = strtolower(trim((string) $gateway_status));
		$amount_rupees = (int) $amount_rupees;
		if ($amount_rupees < 1) {
			$amount_rupees = 1;
		}
		$plan_id = (int) $plan_id;

		$pay_stat = 'SUCCESS';
		if ($gateway_status !== '' && $gateway_status !== 'captured') {
			$pay_stat = strtoupper($gateway_status);
		}

		$amt_dec = (float) $amount_rupees;
		$candidates = array(
			'student_id' => $student_id,
			'batch_id' => $batch_id,
			'transaction_id' => $payment_id,
			'mode' => 'razorpay',
			'amount' => $amount_rupees,
			'admin_id' => $admin_id,
			'plan_id' => $plan_id,
			'base_amount' => $amt_dec,
			'batch_fee' => $amt_dec,
			'total_amount' => $amt_dec,
			'discount_amount' => 0.00,
			'promo_code_id' => null,
			'razorpay_order_id' => $order_id,
			'razorpay_payment_id' => $payment_id,
			'payment_status' => $pay_stat,
			'payment_date' => date('Y-m-d H:i:s'),
		);

		return $this->filter_insert_row_for_table_fields($f, $candidates);
	}

	/**
	 * Ensure `student_batchs` has the student–batch link (ledger lives in `student_payment_history` only).
	 *
	 * @return array{student_batchs_id: int, student_batchs_inserted: bool}
	 */
	protected function ensure_student_batchs_after_payment($student_id, $batch_id, $batch_admin_id)
	{
		$student_id = (int) $student_id;
		$batch_id = (int) $batch_id;
		$batch_admin_id = (int) $batch_admin_id;

		$res = array(
			'student_batchs_id' => 0,
			'student_batchs_inserted' => false,
		);

		if ($student_id < 1 || $batch_id < 1) {
			return $res;
		}

		$bf = $this->get_student_batchs_table_fields();

		$sb = $this->db_model->select_data('id, status', 'student_batchs', array('student_id' => $student_id, 'batch_id' => $batch_id), 1);
		if (!empty($sb)) {
			$res['student_batchs_id'] = (int) $sb[0]['id'];
			// Payment succeeded — enrollment must be active (batch-details uses status === 1).
			if ($bf !== false && isset($bf['status'])) {
				$cur = isset($sb[0]['status']) ? (int) $sb[0]['status'] : 0;
				if ($cur !== 1) {
					$this->db_model->update_data_limit(
						'student_batchs',
						array('status' => 1),
						array('id' => (int) $sb[0]['id']),
						1
					);
				}
			}
			return $res;
		}

		if ($bf === false) {
			return $res;
		}

		$batch_row = array(
			'student_id' => $student_id,
			'batch_id' => $batch_id,
			'added_by' => 'student',
			'admin_id' => $batch_admin_id,
			'status' => 1,
		);
		$insert_sb = $this->filter_insert_row_for_table_fields($bf, $batch_row);
		if (empty($insert_sb)) {
			return $res;
		}
		$this->db->insert('student_batchs', $this->security->xss_clean($insert_sb));
		$newsb = (int) $this->db->insert_id();
		if ($newsb > 0) {
			$res['student_batchs_id'] = $newsb;
			$res['student_batchs_inserted'] = true;
		}

		return $res;
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
		$ledger = array(
			'payments_id' => 0,
			'payments_inserted' => false,
			'student_payment_history_id' => 0,
			'student_batchs_id' => 0,
			'student_batchs_inserted' => false,
		);
		$history_dup = false;
		$new_hist_id = 0;
		$plan_id_req = 0;
		if (isset($data['plan_id']) && $data['plan_id'] !== '' && is_numeric($data['plan_id'])) {
			$plan_id_req = (int) $data['plan_id'];
		} elseif (isset($data['planId']) && $data['planId'] !== '' && is_numeric($data['planId'])) {
			$plan_id_req = (int) $data['planId'];
		} elseif (isset($data['first_payment_plan_id']) && $data['first_payment_plan_id'] !== '' && is_numeric($data['first_payment_plan_id'])) {
			$plan_id_req = (int) $data['first_payment_plan_id'];
		} elseif (isset($data['renewal_plan_id']) && $data['renewal_plan_id'] !== '' && is_numeric($data['renewal_plan_id'])) {
			$plan_id_req = (int) $data['renewal_plan_id'];
		}
		if (!empty($data['student_id']) && !empty($data['batch_id']) && $payload['ut'] === 'student') {
			$sid = (int) $data['student_id'];
			$bid = (int) $data['batch_id'];
			if ($sid === (int) $payload['uid'] && $bid > 0 && $sid > 0) {
				$dup = $this->db_model->select_data('id', 'student_payment_history', array(
					'student_id' => $sid,
					'batch_id' => $bid,
					'transaction_id' => $payment_id,
				), 1);
				$history_dup = !empty($dup);
				if (empty($dup)) {
					$st = $this->db_model->select_data('admin_id', 'students', array('id' => $sid), 1);
					if (!empty($st)) {
						$admin_id = (int) $st[0]['admin_id'];
						$amount_record = $amount_paise > 0 ? (int) round($amount_paise / 100) : 0;
						if ($amount_record < 1) {
							$amount_record = 1;
						}
						$hist_row = $this->build_razorpay_student_payment_history_row(
							$sid,
							$bid,
							$admin_id,
							$order_id,
							$payment_id,
							$amount_record,
							$status,
							$plan_id_req
						);
						if (empty($hist_row)) {
							$hist_row = array(
								'student_id' => $sid,
								'batch_id' => $bid,
								'transaction_id' => $payment_id,
								'mode' => 'razorpay',
								'amount' => $amount_record,
								'admin_id' => $admin_id,
							);
						}
						$hist_row = $this->security->xss_clean($hist_row);
						$new_hist_id = (int) $this->db_model->insert_data('student_payment_history', $hist_row);
						$recorded = ($new_hist_id > 0);
					}
				}
				if ($recorded || $history_dup) {
					$amount_ledger = $amount_paise > 0 ? (int) round($amount_paise / 100) : 0;
					if ($amount_ledger < 1) {
						$amount_ledger = 1;
					}
					$br = $this->db_model->select_data('admin_id', 'batches', array('id' => $bid), 1);
					$badm = !empty($br) ? (int) $br[0]['admin_id'] : 0;
					$hist_pk = $history_dup ? (int) $dup[0]['id'] : $new_hist_id;
					$sb_ledger = $this->ensure_student_batchs_after_payment($sid, $bid, $badm);
					$ledger = array(
						'payments_id' => $hist_pk,
						'payments_inserted' => ($recorded || $history_dup),
						'student_payment_history_id' => $hist_pk,
						'student_batchs_id' => $sb_ledger['student_batchs_id'],
						'student_batchs_inserted' => $sb_ledger['student_batchs_inserted'],
					);
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
			'ledger' => $ledger,
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
