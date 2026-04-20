<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Minimal Razorpay REST client (no Composer). Uses Basic auth: key_id:key_secret.
 *
 * @see https://razorpay.com/docs/api/
 */
class Razorpay_api
{
	protected $key_id;
	protected $key_secret;
	protected $base_url = 'https://api.razorpay.com/v1/';

	public function __construct($params = array())
	{
		if (!is_array($params)) {
			$params = array();
		}
		$this->key_id = isset($params['key_id']) ? trim((string) $params['key_id']) : '';
		$this->key_secret = isset($params['key_secret']) ? trim((string) $params['key_secret']) : '';
	}

	public function has_credentials()
	{
		return $this->key_id !== '' && $this->key_secret !== '';
	}

	/**
	 * @param int    $amount_paise Smallest currency unit (INR = paise)
	 * @param string $currency     ISO code, default INR
	 * @param string|null $receipt Max 40 chars, alphanumeric + _ -
	 * @param array  $notes         Optional metadata (shown in dashboard)
	 * @return array{ http_code: int, body: array|string, ok: bool }
	 */
	public function create_order($amount_paise, $currency = 'INR', $receipt = null, array $notes = array())
	{
		$payload = array(
			'amount' => (int) $amount_paise,
			'currency' => strtoupper((string) $currency),
			'payment_capture' => 1,
		);
		if ($receipt !== null && $receipt !== '') {
			$clean = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $receipt);
			$payload['receipt'] = substr($clean, 0, 40);
		}
		if (!empty($notes)) {
			$payload['notes'] = $notes;
		}
		return $this->request('POST', 'orders', $payload);
	}

	/**
	 * @return array{ http_code: int, body: array|string, ok: bool }
	 */
	public function fetch_order($order_id)
	{
		$order_id = trim((string) $order_id);
		if ($order_id === '') {
			return array('http_code' => 0, 'body' => array('error' => array('description' => 'Empty order id')), 'ok' => false);
		}

		return $this->request('GET', 'orders/' . rawurlencode($order_id), null);
	}

	/**
	 * @return array{ http_code: int, body: array|string, ok: bool }
	 */
	public function fetch_payment($payment_id)
	{
		$payment_id = trim((string) $payment_id);
		if ($payment_id === '') {
			return array('http_code' => 0, 'body' => array('error' => array('description' => 'Empty payment id')), 'ok' => false);
		}

		return $this->request('GET', 'payments/' . rawurlencode($payment_id), null);
	}

	public function verify_signature($order_id, $payment_id, $signature)
	{
		$order_id = (string) $order_id;
		$payment_id = (string) $payment_id;
		$signature = (string) $signature;
		if ($order_id === '' || $payment_id === '' || $signature === '') {
			return false;
		}
		$expected = hash_hmac('sha256', $order_id . '|' . $payment_id, $this->key_secret);

		return hash_equals($expected, $signature);
	}

	/**
	 * Webhooks use the signing secret from Razorpay Dashboard → Webhooks (not the API key secret).
	 */
	public function verify_webhook_with_secret($raw_body, $header_signature, $webhook_secret)
	{
		$webhook_secret = (string) $webhook_secret;
		if ($webhook_secret === '' || $raw_body === '' || $header_signature === '') {
			return false;
		}
		$expected = hash_hmac('sha256', $raw_body, $webhook_secret);

		return hash_equals($expected, $header_signature);
	}

	protected function request($method, $path, $json_body)
	{
		if (!$this->has_credentials()) {
			return array(
				'http_code' => 0,
				'body' => array('error' => array('description' => 'Razorpay keys not configured')),
				'ok' => false,
			);
		}

		$url = $this->base_url . ltrim($path, '/');
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_USERPWD, $this->key_id . ':' . $this->key_secret);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 45);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

		if ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json_body));
		} else {
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		}

		$response = curl_exec($ch);
		$http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_err = curl_error($ch);
		curl_close($ch);

		if ($response === false) {
			return array(
				'http_code' => $http_code,
				'body' => array('error' => array('description' => $curl_err ?: 'cURL error')),
				'ok' => false,
			);
		}

		$decoded = json_decode($response, true);
		if (!is_array($decoded)) {
			$decoded = $response;
		}

		$ok = $http_code >= 200 && $http_code < 300;

		return array('http_code' => $http_code, 'body' => $decoded, 'ok' => $ok);
	}
}
