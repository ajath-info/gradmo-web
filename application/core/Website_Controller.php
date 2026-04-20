<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller for website/* controllers in the main app.
 * Keeps shared helpers used by application/controllers/website/Home.php.
 */
class Website_Controller extends CI_Controller
{
	protected $main_app_base_url = '';

	public function __construct()
	{
		parent::__construct();
		$this->main_app_base_url = rtrim(base_url(), '/') . '/';
		$timezoneDB = $this->db_model->select_data('timezone', 'site_details', array('id' => 1));
		if (isset($timezoneDB[0]['timezone']) && !empty($timezoneDB[0]['timezone'])) {
			date_default_timezone_set($timezoneDB[0]['timezone']);
		}
	}

	protected function general_settings($key_text = '')
	{
		if ($key_text === '') {
			return '';
		}
		if (isset($this->common) && method_exists($this->common, 'general_settings')) {
			return (string) $this->common->general_settings($key_text);
		}
		$row = $this->db_model->select_data('velue_text', 'general_settings', array('key_text' => $key_text), 1);
		return !empty($row[0]['velue_text']) ? (string) $row[0]['velue_text'] : '';
	}

	protected function readMoreWord($story_desc, $C_word = '')
	{
		$chars = 90;
		if (!empty($C_word)) {
			$chars = (int) $C_word;
			if ($chars < 1) {
				$chars = 90;
			}
		}
		$story_desc = (string) $story_desc;
		if (strlen($story_desc) > $chars) {
			$story_desc = substr($story_desc, 0, $chars);
			$last_space = strrpos($story_desc, ' ');
			if ($last_space !== false) {
				$story_desc = substr($story_desc, 0, $last_space);
			}
		}
		return $story_desc;
	}

	protected function render_frontend_layout($content_view, array $data = array())
	{
		$this->load->view('common/front_header', $data);
		$this->load->view($content_view, $data);
		$this->load->view('common/front_footer', $data);
	}

	protected function render_home_portal_layout(array $data = array())
	{
		$this->load->view('common/home_design_header', $data);
		$this->load->view('frontend/home', $data);
		$this->load->view('common/home_design_footer', $data);
	}

	protected function main_api_url($path = '')
	{
		$path = ltrim((string) $path, '/');
		return $this->main_app_base_url . $path;
	}

	protected function call_main_api($path, array $payload = array(), $method = 'POST')
	{
		$url = $this->main_api_url($path);
		$method = strtoupper((string) $method);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		if ($method === 'GET') {
			if (!empty($payload)) {
				$url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($payload);
				curl_setopt($ch, CURLOPT_URL, $url);
			}
		} else {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
		}

		$raw = curl_exec($ch);
		$err = curl_error($ch);
		$http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($raw === false || $err !== '') {
			return array('status' => 'false', 'msg' => 'API connection failed: ' . $err, 'httpCode' => $http_code);
		}

		$data = json_decode($raw, true);
		if (!is_array($data)) {
			return array('status' => 'false', 'msg' => 'Invalid API response', 'httpCode' => $http_code, 'raw' => $raw);
		}

		$data['httpCode'] = $http_code;
		return $data;
	}
}