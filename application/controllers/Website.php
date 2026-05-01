<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Website extends MY_Controller
{
	function __construct(){
		parent::__construct();
	
			$this->load->helper('file');
			$this->load->helper('language');
			$language = $this->general_settings('language_name');
			if($language=="french"){
				$this->lang->load('french_lang', 'french');
			}else if($language=="arabic"){
				$this->lang->load('arabic_lang', 'arabic');
			}else if($language=="english"){
				$this->lang->load('english_lang', 'english');
			}else if($language=="hindi"){
				$this->lang->load('hindi_lang', 'hindi');
			}else if($language=="german"){
				$this->lang->load('german_lang', 'german');
			}else{
				$this->lang->load('spanish_lang', 'spanish');
			}

		}


		function general_settings($key_text=''){
			$data = $this->db_model->select_data('*','general_settings',array('key_text'=>$key_text),1);
			return $data[0]['velue_text'];
		}

		/**
		 * Same shell data as {@see Home::contact()} for front_header / front_footer.
		 *
		 * @return array<string, mixed>
		 */
		private function frontend_shell_data($page_title)
		{
			return array(
				'title' => $page_title,
				'frontend_details' => $this->db_model->select_data('*', 'frontend_details', array('id' => '1'), 1),
				'courses' => $this->db_model->select_data('course_name', 'courses use index (id)', array('status' => '1', 'admin_id' => '1'), 5),
				'facilities' => $this->db_model->select_data('title', 'facilities use index (id)', array('status' => '1'), 5),
			);
		}

		function index(){
			$data['title'] =$this->lang->line('ltr_home');
			$data['frontend_details'] = $this->db_model->select_data('*','frontend_details',array('id'=>'1'),1);
			
			$data['Allcourses'] = $this->db_model->select_data('*','courses use index (id)',array('status'=>'1','admin_id'=>'1'),'');
			//print_r($data['Allcourses']);
			// die();
			$data['Allfacilities'] = $this->db_model->select_data('*','facilities use index (id)',array('status'=>'1'),6);
			$data['courses'] = $this->db_model->select_data('course_name','courses use index (id)',array('status'=>'1','admin_id'=>'1'),5);
			$data['facilities'] = $this->db_model->select_data('title','facilities use index (id)',array('status'=>'1'),5);
			$batches = $this->db_model->select_data('*','batches use index (id)',array('status'=>'1','admin_id'=>'1'),3,array('id','DESC'));
			$data['site_Details'] = $this->db_model->select_data('*','site_details',array('id'=>'1'),1);
			$data['currency_decimal'] =$this->general_settings('currency_decimal_code');
			if(!empty($batches)){
				foreach($batches as $key =>$value){
					$batches[$key]['description'] = $this->readMoreWord($value['description'], 150);
				}
				$data['batches']= $batches;
			}else{
				$data['batches'] =''; 
			}
			$data['home_institute_api_url'] = site_url('api/institute/listing');
			$data['home_institute_details_url'] = site_url('institute/details');
			$data['api_access_token'] = $this->website_session_access_token();
			$this->render_frontend_layout('frontend/home', $data);
		}

		function login(){
			if(isset($this->session->userdata['role']))
			{
				$role = $this->session->userdata['role'];
				if($role==1){
				redirect(base_url().'admin/dashboard');
				}elseif($role==3){
				redirect(base_url().'teacher/dashboard');
				}else if($role=='student'){
				redirect(base_url().'student/my_course');
				}
			} 
			$data = $this->frontend_shell_data($this->lang->line('ltr_login'));
			$data['load_auth_form_assets'] = true;
			$data['load_login_otp_script'] = true;
			$this->render_frontend_layout('frontend/login', $data);
		}

		function register(){
			$data = $this->frontend_shell_data($this->lang->line('ltr_register'));
			$data['load_auth_form_assets'] = true;
			$data['load_register_otp_script'] = true;
			$this->render_frontend_layout('frontend/register', $data);
		}

		function forgot_password(){
			$data = $this->frontend_shell_data($this->lang->line('ltr_forgot_password'));
			$data['load_auth_form_assets'] = true;
			$this->render_frontend_layout('frontend/forgot_password', $data);
		}

		public function update_profile()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Update Profile');
			$uid = (int) $this->session->userdata('uid');
			$role = (string) $this->session->userdata('role');
			$profile = array(
				'name' => (string) $this->session->userdata('name'),
				'email' => (string) $this->session->userdata('email'),
				'mobile' => (string) $this->session->userdata('mobile'),
				'address' => '',
				'country' => '',
				'state' => '',
				'city' => '',
				'pincode' => '',
				'school_college_name' => '',
				'grade' => '',
				'show_grade_field' => true,
			);
			if ($uid > 0) {
				if ($role === 'student') {
					$rows = $this->db_model->select_data('*', 'students use index (id)', array('id' => $uid), 1);
					if (! empty($rows) && is_array($rows[0])) {
						$row = $rows[0];
						$profile['name'] = isset($row['name']) ? (string) $row['name'] : $profile['name'];
						$profile['email'] = isset($row['email']) ? (string) $row['email'] : $profile['email'];
						$profile['mobile'] = isset($row['contact_no']) && $row['contact_no'] !== '' ? (string) $row['contact_no'] : (isset($row['mobile']) ? (string) $row['mobile'] : $profile['mobile']);
						$profile['address'] = isset($row['address']) ? (string) $row['address'] : '';
						$profile['country'] = isset($row['country']) ? (string) $row['country'] : '';
						$profile['state'] = isset($row['state']) ? (string) $row['state'] : '';
						$profile['city'] = isset($row['city']) ? (string) $row['city'] : '';
						$profile['pincode'] = isset($row['pincode']) ? (string) $row['pincode'] : '';
						$profile['school_college_name'] = isset($row['school_college_name']) ? (string) $row['school_college_name'] : '';
						$profile['grade'] = isset($row['grade']) ? (string) $row['grade'] : '';
					}
					$profile['show_grade_field'] = true;
				} else {
					$rows = $this->db_model->select_data('name,email,mobile,address,country,state,city,pincode,teach_education', 'users use index (id)', array('id' => $uid), 1);
					if (! empty($rows) && is_array($rows[0])) {
						$row = $rows[0];
						$profile['name'] = isset($row['name']) ? (string) $row['name'] : $profile['name'];
						$profile['email'] = isset($row['email']) ? (string) $row['email'] : $profile['email'];
						$profile['mobile'] = isset($row['mobile']) ? (string) $row['mobile'] : $profile['mobile'];
						$profile['address'] = isset($row['address']) ? (string) $row['address'] : '';
						$profile['country'] = isset($row['country']) ? (string) $row['country'] : '';
						$profile['state'] = isset($row['state']) ? (string) $row['state'] : '';
						$profile['city'] = isset($row['city']) ? (string) $row['city'] : '';
						$profile['pincode'] = isset($row['pincode']) ? (string) $row['pincode'] : '';
						$profile['school_college_name'] = isset($row['teach_education']) ? (string) $row['teach_education'] : '';
						$profile['grade'] = '';
					}
					$profile['show_grade_field'] = false;
				}
			}
			$data['profile'] = $profile;
			$this->render_frontend_layout('frontend/update_profile', $data);
		}

		public function update_password()
		{
			$data = $this->frontend_shell_data('Update Password');
			$this->render_frontend_layout('frontend/update_password', $data);
		}

		public function change_password_page()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Change Password');
			$this->render_frontend_layout('frontend/change_password', $data);
		}

		public function delete_account_page()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Delete Account');
			$this->render_frontend_layout('frontend/delete_account', $data);
		}

		public function update_profile_submit()
		{
			if (! isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
				$this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(array('status' => false, 'msg' => 'Not allowed')));
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => false, 'msg' => 'Please login again. Missing API token.')));
				return;
			}
			$raw = file_get_contents('php://input');
			$in = json_decode($raw, true);
			if (! is_array($in)) {
				$in = array();
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/user/update-profile'), $in, array('Authorization: Bearer ' . $token));
			$decoded = json_decode($body, true);
			if (is_array($decoded)) {
				$st = isset($decoded['status']) ? $decoded['status'] : '';
				$ok = ($st === true || $st === 'true' || $st === 1 || $st === '1' || strtolower((string) $st) === 'true');
				if ($ok && ! empty($decoded['data']) && is_array($decoded['data'])) {
					$this->establish_web_session_from_api_login_data($decoded['data']);
				}
			}
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => false, 'msg' => 'Empty response')));
		}

		public function update_password_submit()
		{
			if (! isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
				$this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(array('status' => false, 'msg' => 'Not allowed')));
				return;
			}
			$raw = file_get_contents('php://input');
			$in = json_decode($raw, true);
			if (! is_array($in)) {
				$in = array();
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/user/update-password'), $in);
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => false, 'msg' => 'Empty response')));
		}

		public function change_password_submit()
		{
			if (! isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
				$this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(array('status' => false, 'msg' => 'Not allowed')));
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => false, 'msg' => 'Please login again. Missing API token.')));
				return;
			}
			$raw = file_get_contents('php://input');
			$in = json_decode($raw, true);
			if (! is_array($in)) {
				$in = array();
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/user/change-password'), $in, array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => false, 'msg' => 'Empty response')));
		}

		public function delete_account_submit()
		{
			if (! isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
				$this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(array('status' => false, 'msg' => 'Not allowed')));
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => false, 'msg' => 'Please login again. Missing API token.')));
				return;
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/user/delete-account'), array(), array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => false, 'msg' => 'Empty response')));
		}

		public function payment_history()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data($this->common->languageTranslator('ltr_payment_history'));
			$data['payment_history_data_url'] = site_url('payment-history-data');
			$this->render_frontend_layout('frontend/payment_history', $data);
		}

		public function payment_history_data()
		{
			$in = $this->website_require_ajax_json();
			if ($in === null) {
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Please login again.')));
				return;
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/user/payment-history'), is_array($in) ? $in : array(), array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => 'false', 'msg' => 'Empty response')));
		}

		/**
		 * Website-only: POST JSON to api/user/send-otp (mobile must be 10 digits; API unchanged).
		 */
		public function login_otp_send()
		{
			if (! isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
				$this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(array('status' => false, 'msg' => 'Not allowed')));
				return;
			}
			$raw = file_get_contents('php://input');
			$in = json_decode($raw, true);
			if (! is_array($in)) {
				$in = array();
			}
			$user_type = isset($in['user_type']) ? strtolower(trim((string) $in['user_type'])) : '';
			$mobile = isset($in['mobile']) ? preg_replace('/\D/', '', trim((string) $in['mobile'])) : '';
			if ($user_type === '' || ! in_array($user_type, array('student', 'teacher', 'institute'), true)) {
				$this->output->set_content_type('application/json')->set_output(json_encode(array(
					'status' => false,
					'msg' => 'user_type is required (student, teacher, institute)',
				)));
				return;
			}
			if (strlen($mobile) !== 10) {
				$this->output->set_content_type('application/json')->set_output(json_encode(array(
					'status' => false,
					'msg' => 'Enter a valid 10-digit mobile number registered to your account.',
				)));
				return;
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/user/send-otp'), array(
				'mobile' => $mobile,
				'user_type' => $user_type,
			));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => false, 'msg' => 'Empty response')));
		}

		/**
		 * Website-only: forward to api/user/verify-otp, then create CodeIgniter web session from API payload.
		 */
		public function login_otp_verify()
		{
			if (! isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
				$this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(array('status' => false, 'msg' => 'Not allowed')));
				return;
			}
			$raw = file_get_contents('php://input');
			$in = json_decode($raw, true);
			if (! is_array($in)) {
				$in = array();
			}
			$user_type = isset($in['user_type']) ? strtolower(trim((string) $in['user_type'])) : '';
			$otp = isset($in['otp']) ? trim((string) $in['otp']) : '';
			$mobile = isset($in['mobile']) ? preg_replace('/\D/', '', trim((string) $in['mobile'])) : '';
			if ($user_type === '' || ! in_array($user_type, array('student', 'teacher', 'institute'), true)) {
				$this->output->set_content_type('application/json')->set_output(json_encode(array(
					'status' => false,
					'msg' => 'user_type is required',
				)));
				return;
			}
			if ($otp === '') {
				$this->output->set_content_type('application/json')->set_output(json_encode(array(
					'status' => false,
					'msg' => 'OTP required',
				)));
				return;
			}
			if (strlen($mobile) !== 10) {
				$this->output->set_content_type('application/json')->set_output(json_encode(array(
					'status' => false,
					'msg' => 'Enter the same 10-digit mobile you used to request OTP.',
				)));
				return;
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/user/verify-otp'), array(
				'mobile' => $mobile,
				'otp' => $otp,
				'user_type' => $user_type,
			));
			$decoded = json_decode($body, true);
			if (! is_array($decoded)) {
				$this->output->set_status_header((int) $code > 0 ? $code : 500);
				$hint = (strlen((string) $body) > 0 && $body[0] === '<') ? 'Server returned HTML instead of JSON (check URL / rewrite rules).' : 'Invalid response from OTP service.';
				$this->output->set_content_type('application/json')->set_output(json_encode(array('status' => false, 'msg' => $hint)));
				return;
			}
			$st = isset($decoded['status']) ? $decoded['status'] : '';
			$ok = ($st === true || $st === 'true' || $st === 1 || $st === '1' || strtolower((string) $st) === 'true');
			if ($ok && ! empty($decoded['data']) && is_array($decoded['data'])) {
				$web = $this->establish_web_session_from_api_login_data($decoded['data']);
				if (is_array($web)) {
					$decoded['web_session'] = $web;
				}
			}
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output(json_encode($decoded, JSON_UNESCAPED_SLASHES));
		}

		/**
		 * Public password login: POST email, password, user_type — delegates to api/user/login (unchanged API).
		 * Response shape matches front_ajax/login for assets/js/login.js.
		 */
		public function login_password()
		{
			if (! isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
				echo $this->lang->line('ltr_not_allowed_msg');
				return;
			}
			$login_redirect_home = in_array(trim((string) $this->input->post('login_redirect', TRUE)), array('index', 'home'), TRUE);
			$front_home_url = rtrim(base_url(), '/') . '/';

			$email = trim((string) $this->input->post('email', TRUE));
			$password_raw = $this->input->post('password', false);
			$password = is_string($password_raw) ? trim($password_raw) : '';
			$user_type = strtolower(trim((string) $this->input->post('user_type', TRUE)));
			if ($email === '' || $password === '' || ! in_array($user_type, array('student', 'teacher', 'institute'), true)) {
				echo json_encode(array('status' => '0', 'msg' => 'Email, password and account type are required.'), JSON_UNESCAPED_SLASHES);
				return;
			}

			list($code, $body) = $this->website_proxy_post_json(site_url('api/user/login'), array(
				'username' => $email,
				'password' => $password,
				'user_type' => $user_type,
			));
			$decoded = json_decode($body, true);
			if (! is_array($decoded)) {
				echo json_encode(array('status' => '0', 'msg' => 'Invalid server response'), JSON_UNESCAPED_SLASHES);
				return;
			}
			$st = isset($decoded['status']) ? $decoded['status'] : '';
			$ok = ($st === true || $st === 'true' || strtolower((string) $st) === 'true');
			if (! $ok || empty($decoded['data']) || ! is_array($decoded['data'])) {
				$msg = isset($decoded['msg']) ? $decoded['msg'] : $this->lang->line('ltr_wrong_credentials_msg');
				echo json_encode(array('status' => '0', 'msg' => $msg), JSON_UNESCAPED_SLASHES);
				return;
			}

			$web = $this->establish_web_session_from_api_login_data($decoded['data']);
			if (! is_array($web)) {
				echo json_encode(array('status' => '0', 'msg' => $this->lang->line('ltr_wrong_credentials_msg')), JSON_UNESCAPED_SLASHES);
				return;
			}

			if ($this->input->post('remember_me', TRUE)) {
				setcookie('UML', base64_encode(urlencode(base64_encode($email))), time() + 86400, '/');
				setcookie('SSD', base64_encode(urlencode(base64_encode($password))), time() + 86400, '/');
			} else {
				setcookie('UML', base64_encode(urlencode(base64_encode($email))), time() - 86400, '/');
				setcookie('SSD', base64_encode(urlencode(base64_encode($password))), time() - 86400, '/');
			}

			$url = isset($web['url']) ? $web['url'] : $front_home_url;
			if (! $login_redirect_home) {
				$role = $this->session->userdata('role');
				if ($role === '1' || $role === 1) {
					$url = base_url() . 'admin/dashboard';
				} elseif ($role === '3' || $role === 3) {
					$url = base_url() . 'teacher/dashboard';
				} elseif ($role === 'student') {
					$url = base_url() . 'student/my-course';
				} else {
					$url = $front_home_url;
				}
			} else {
				$url = $front_home_url;
			}

			echo json_encode(array(
				'status' => '1',
				'msg' => isset($web['msg']) ? $web['msg'] : $this->lang->line('ltr_logged_msg'),
				'url' => $url,
				'otp' => '',
			), JSON_UNESCAPED_SLASHES);
		}

		/**
		 * @return array{0:int,1:string} HTTP status code and raw body
		 */
		private function website_proxy_post_json($url, array $payload, array $extra_headers = array())
		{
			$json = json_encode($payload);
			$headers = array(
				'Content-Type: application/json',
				'Accept: application/json',
				'X-Requested-With: XMLHttpRequest',
			);
			if (! empty($extra_headers)) {
				$headers = array_merge($headers, $extra_headers);
			}
			if (function_exists('curl_init')) {
				$ch = curl_init($url);
				$curl_opts = array(
					CURLOPT_POST => true,
					CURLOPT_HTTPHEADER => $headers,
					CURLOPT_POSTFIELDS => $json,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_TIMEOUT => 60,
					CURLOPT_USERAGENT => 'EducationSiteOtpBridge/1.0',
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_SSL_VERIFYHOST => 0,
				);
				if (defined('CURLPROTO_HTTPS')) {
					$curl_opts[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
				}
				if (defined('CURL_REDIR_POST_ALL')) {
					$curl_opts[CURLOPT_POSTREDIR] = CURL_REDIR_POST_ALL;
				}
				curl_setopt_array($ch, $curl_opts);
				$body = curl_exec($ch);
				$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				return array($code > 0 ? $code : 200, $body === false ? '' : (string) $body);
			}
			$ctx = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'header' => implode("\r\n", $headers) . "\r\n",
					'content' => $json,
					'timeout' => 60,
					'ignore_errors' => true,
				),
			));
			$body = @file_get_contents($url, false, $ctx);
			$code = 200;
			if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
				$code = (int) $m[1];
			}
			return array($code, $body === false ? '' : (string) $body);
		}

		private function website_api_scalar_string(array $d, array $keys, $default = '')
		{
			foreach ($keys as $k) {
				if (! array_key_exists($k, $d)) {
					continue;
				}
				$v = $d[$k];
				if ($v === null || $v === '') {
					continue;
				}
				if (is_scalar($v)) {
					return (string) $v;
				}
			}
			return $default;
		}

		private function website_profile_img_basename($imageUrl)
		{
			$imageUrl = trim((string) $imageUrl);
			if ($imageUrl === '') {
				return '';
			}
			$path = parse_url($imageUrl, PHP_URL_PATH);
			if ($path === false || $path === null || $path === '') {
				return '';
			}
			return basename($path);
		}

		private function web_login_random_string($length = 10)
		{
			$str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
			return substr(str_shuffle($str_result), 0, $length);
		}

		private function website_front_home_url()
		{
			return rtrim(base_url(), '/') . '/';
		}

		private function website_session_access_token()
		{
			$tok = (string) $this->session->userdata('access_token');
			if ($tok !== '') {
				return $tok;
			}
			$tok = (string) $this->session->userdata('token');
			return $tok;
		}

		/**
		 * Build CI session from api/user/login or api/user/verify-otp success payload (no DB in Website).
		 *
		 * @param array $d API "data" object
		 * @return array{status:int,msg:string,url:string}|null
		 */
		private function establish_web_session_from_api_login_data(array $d)
		{
			$brewers_strings = $this->web_login_random_string(10);
			$user_type = '';
			if (isset($d['userType'])) {
				$user_type = strtolower(trim((string) $d['userType']));
			} elseif (isset($d['user_type'])) {
				$user_type = strtolower(trim((string) $d['user_type']));
			}

			if ($user_type === 'student') {
				$sid = (int) $this->website_api_scalar_string($d, array('studentId', 'student_id'), '0');
				if ($sid < 1) {
					return null;
				}
				$imgUrl = isset($d['image']) ? (string) $d['image'] : '';
				$sess_arr = array(
					'uid' => $sid,
					'name' => $this->website_api_scalar_string($d, array('name'), ''),
					'role' => 'student',
					'mobile' => $this->website_api_scalar_string($d, array('mobile', 'contactNo', 'contact_no'), ''),
					'admin_id' => (int) $this->website_api_scalar_string($d, array('adminId', 'admin_id'), '0'),
					'profile_img' => $this->website_profile_img_basename($imgUrl),
					'email' => $this->website_api_scalar_string($d, array('email'), ''),
					'batch_id' => $this->website_api_scalar_string($d, array('batchId', 'batch_id'), ''),
					'enrollment_id' => $this->website_api_scalar_string($d, array('enrollmentId', 'enrollment_id'), ''),
					'brewers_check' => $brewers_strings,
					'api_user_type' => 'student',
					'access_token' => $this->website_api_scalar_string($d, array('access_token'), ''),
				);
				$this->session->set_userdata($sess_arr);
				return array(
					'status' => 1,
					'msg' => $this->lang->line('ltr_logged_msg'),
					'url' => $this->website_front_home_url(),
				);
			}

			$uid = (int) $this->website_api_scalar_string($d, array('userId', 'user_id'), '0');
			if ($uid < 1) {
				$uid = (int) $this->website_api_scalar_string($d, array('studentId', 'student_id'), '0');
			}
			if ($uid < 1) {
				return null;
			}

			$role = isset($d['role']) ? $d['role'] : '';
			$imgUrl = isset($d['image']) ? (string) $d['image'] : '';
			$sess_arr = array(
				'uid' => $uid,
				'name' => $this->website_api_scalar_string($d, array('name'), ''),
				'role' => $role,
				'status' => isset($d['status']) ? $d['status'] : 1,
				'admin_id' => (int) $this->website_api_scalar_string($d, array('adminId', 'admin_id'), '0'),
				'profile_img' => $this->website_profile_img_basename($imgUrl),
				'email' => $this->website_api_scalar_string($d, array('email'), ''),
				'mobile' => $this->website_api_scalar_string($d, array('mobile', 'contactNo', 'contact_no'), ''),
				'brewers_check' => $brewers_strings,
				'super_admin' => isset($d['super_admin']) ? $d['super_admin'] : 0,
				'api_user_type' => $user_type,
				'access_token' => $this->website_api_scalar_string($d, array('access_token'), ''),
			);
			$r = (string) $role;
			if ($r === '3' || $r === '4' || $role === 3 || $role === 4) {
				$sess_arr['subject_id'] = $this->website_api_scalar_string($d, array('subjectId', 'subject_id'), '');
				$sess_arr['batch_id'] = $this->website_api_scalar_string($d, array('batchId', 'batch_id'), '');
			}
			$this->session->set_userdata($sess_arr);
			return array(
				'status' => 1,
				'msg' => $this->lang->line('ltr_logged_msg'),
				'url' => $this->website_front_home_url(),
			);
		}

		private function website_require_ajax_json()
		{
			if (! isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
				$this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(array('status' => false, 'msg' => 'Not allowed')));
				return null;
			}
			$raw = file_get_contents('php://input');
			$in = json_decode($raw, true);
			return is_array($in) ? $in : array();
		}

		private function website_session_is_institute()
		{
			$ut = strtolower(trim((string) $this->session->userdata('api_user_type')));
			if ($ut === 'institute') {
				return true;
			}
			$role = $this->session->userdata('role');
			return ($role === 4 || $role === '4');
		}

		private function website_is_truthy($value)
		{
			if ($value === true || $value === 1 || $value === '1') {
				return true;
			}
			if (is_string($value) && strtolower(trim($value)) === 'true') {
				return true;
			}
			return false;
		}

		public function batch_list()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Batches');
			$data['batch_list_data_url'] = site_url('batch/list-data');
			$data['batch_details_base'] = site_url('batch/details');
			$this->render_frontend_layout('frontend/batch_list', $data);
		}

		public function batch_list_data()
		{
			$in = $this->website_require_ajax_json();
			if ($in === null) {
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Please login again.')));
				return;
			}
			if (! is_array($in)) {
				$in = array();
			}
			$payload = array(
				'search' => isset($in['search']) ? trim((string) $in['search']) : '',
				'page' => isset($in['page']) ? (int) $in['page'] : 1,
				'limit' => isset($in['limit']) ? (int) $in['limit'] : 10,
			);
			if ($payload['page'] < 1) {
				$payload['page'] = 1;
			}
			if ($payload['limit'] < 1) {
				$payload['limit'] = 10;
			}
			if ($payload['limit'] > 100) {
				$payload['limit'] = 100;
			}
			if (isset($in['list']) && trim((string) $in['list']) !== '') {
				$payload['list'] = trim((string) $in['list']);
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/batch/batch-list'), $payload, array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => 'false', 'msg' => 'Empty response')));
		}

		public function batch_mylist()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('My batches');
			$data['batch_mylist_data_url'] = site_url('batch/mylist-data');
			$data['batch_details_base'] = site_url('batch/details');
			$this->render_frontend_layout('frontend/batch_mylist', $data);
		}

		public function batch_mylist_data()
		{
			$in = $this->website_require_ajax_json();
			if ($in === null) {
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Please login again.')));
				return;
			}
			if (! is_array($in)) {
				$in = array();
			}
			$payload = array(
				'search' => isset($in['search']) ? trim((string) $in['search']) : '',
				'page' => isset($in['page']) ? (int) $in['page'] : 1,
				'limit' => isset($in['limit']) ? (int) $in['limit'] : 10,
			);
			if ($payload['page'] < 1) {
				$payload['page'] = 1;
			}
			if ($payload['limit'] < 1) {
				$payload['limit'] = 10;
			}
			if ($payload['limit'] > 100) {
				$payload['limit'] = 100;
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/batch/batch-list'), $payload, array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => 'false', 'msg' => 'Empty response')));
		}

		public function batch_details()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$batch_id = (int) $this->input->get('batch_id');
			if ($batch_id < 1) {
				$batch_id = (int) $this->input->get('id');
			}
			$data = $this->frontend_shell_data('Batch details');
			$data['batch_id'] = $batch_id;
			$data['batch_details_data_url'] = site_url('api/batch/batch-details');
			$data['batch_payment_plan_url'] = site_url('batch/payment-plan');
			$data['api_access_token'] = $this->website_session_access_token();
			$this->render_frontend_layout('frontend/batch_details', $data);
		}

		public function batch_payment_plan()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$batch_id = (int) $this->input->get('batch_id');
			if ($batch_id < 1) {
				$batch_id = (int) $this->input->get('id');
			}
			$data = $this->frontend_shell_data('Payment plan');
			$data['batch_id'] = $batch_id;
			$data['batch_uid'] = (int) $this->session->userdata('uid');
			$data['batch_details_data_url'] = site_url('api/batch/batch-details');
			$data['batch_plans_data_url'] = site_url('api/plan/plans');
			$data['batch_promo_codes_data_url'] = site_url('api/plan/promo-codes');
			$data['batch_payment_history_data_url'] = site_url('api/user/payment-history');
			$data['batch_create_order_url'] = site_url('api/payment/razorpay/create-order');
			$data['batch_verify_payment_url'] = site_url('api/payment/razorpay/verify-payment');
			$data['batch_payment_success_url'] = site_url('batch/payment-success');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['batch_details_prefetch'] = array();
			if ($batch_id > 0) {
				$token = $this->website_session_access_token();
				if ($token !== '') {
					list($bd_code, $bd_body) = $this->website_proxy_post_json(
						site_url('api/batch/batch-details'),
						array('batch_id' => $batch_id),
						array('Authorization: Bearer ' . $token)
					);
					$bd_json = json_decode($bd_body, true);
					if ((int) $bd_code >= 200 && (int) $bd_code < 300 && is_array($bd_json) && isset($bd_json['status']) && ($bd_json['status'] === 'true' || $bd_json['status'] === true) && ! empty($bd_json['data']) && is_array($bd_json['data'])) {
						$data['batch_details_prefetch'] = $bd_json['data'];
						if (array_key_exists('canEnroll', $bd_json['data']) && ! $this->website_is_truthy($bd_json['data']['canEnroll'])) {
							redirect(site_url('batch/details?batch_id=' . $batch_id));
							return;
						}
					}
				}
			}
			$this->render_frontend_layout('frontend/batch_payment_plan', $data);
		}

		public function batch_payment_success()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Payment success');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['payment_id'] = (string) $this->input->get('payment_id');
			$data['order_id'] = (string) $this->input->get('order_id');
			$data['amount'] = (string) $this->input->get('amount');
			$this->render_frontend_layout('frontend/batch_payment_success', $data);
		}

		public function batch_live_classes()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$batch_id = (int) $this->input->get('batch_id');
			if ($batch_id < 1) {
				$batch_id = (int) $this->input->get('id');
			}
			$data = $this->frontend_shell_data('Live classes');
			$data['batch_id'] = $batch_id;
			$data['api_access_token'] = $this->website_session_access_token();
			$data['live_class_list_url'] = site_url('api/batch/live-class-list');
			$data['live_class_room_url'] = site_url('batch/live-room');
			$this->render_frontend_layout('frontend/batch_live_classes', $data);
		}

		public function batch_live_room()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$live_class_id = (int) $this->input->get('live_class_id');
			if ($live_class_id < 1) {
				$live_class_id = (int) $this->input->get('id');
			}
			$data = $this->frontend_shell_data('Live room');
			$data['live_class_id'] = $live_class_id;
			$data['api_access_token'] = $this->website_session_access_token();
			$data['live_class_details_url'] = site_url('api/batch/live-class-details');
			$this->render_frontend_layout('frontend/batch_live_room', $data);
		}

		public function attendance_page()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Attendance');
			$data['attendance_api_url'] = site_url('api/user/attendance-list');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$this->render_frontend_layout('frontend/attendance_page', $data);
		}

		public function homework_page()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Homework');
			$data['homework_api_url'] = site_url('api/batch/homework-list');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$this->render_frontend_layout('frontend/homework_list_page', $data);
		}

		public function notifications_page()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Notifications');
			$data['notifications_api_url'] = site_url('api/main/notifications-list');
			$data['api_access_token'] = $this->website_session_access_token();
			$this->render_frontend_layout('frontend/notifications_page', $data);
		}

		public function library_page()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$batch_id = (int) $this->input->get('batch_id');
			if ($batch_id < 1) {
				$batch_id = (int) $this->input->get('id');
			}
			$data = $this->frontend_shell_data('Library');
			$data['library_api_url'] = site_url('api/batch/library-list');
			$data['batch_id'] = $batch_id;
			$data['api_access_token'] = $this->website_session_access_token();
			$data['my_batches_url'] = site_url('batch/mylist');
			$this->render_frontend_layout('frontend/library_page', $data);
		}

		public function institute_listing()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Institutes');
			$data['institute_listing_data_url'] = site_url('institute/listing-data');
			$data['institute_details_url'] = site_url('institute/details');
			$data['institute_city_list_url'] = site_url('api/institute/city-list');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['show_institute_reviews_link'] = $this->website_session_is_institute();
			$data['institute_reviews_list_url'] = site_url('institute/reviews-list');
			$this->render_frontend_layout('frontend/institute_listing', $data);
		}

		public function institute_listing_data()
		{
			$in = $this->website_require_ajax_json();
			if ($in === null) {
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Please login again.')));
				return;
			}
			if (! isset($in['order_field']) || $in['order_field'] === '') {
				$in['order_field'] = 'name';
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/institute/listing'), $in, array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => 'false', 'msg' => 'Empty response')));
		}

		public function institute_details()
		{
			$id = (int) $this->input->get('institute_id');
			if ($id < 1) {
				$id = (int) $this->input->get('id');
			}
			$data = $this->frontend_shell_data('Institute details');
			$data['institute_id'] = $id;
			$data['institute_details_data_url'] = site_url('institute/details-data');
			$data['add_review_url'] = site_url('institute/add-review');
			$data['web_logged_in'] = isset($this->session->userdata['role']) && $this->website_session_access_token() !== '';
			$this->render_frontend_layout('frontend/institute_details', $data);
		}

		public function institute_details_data()
		{
			$in = $this->website_require_ajax_json();
			if ($in === null) {
				return;
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/institute/details'), $in);
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => 'false', 'msg' => 'Empty response')));
		}

		public function institute_add_review()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$iid = (int) $this->input->get('institute_id');
			if ($iid < 1) {
				redirect(site_url('institute/listing'));
				return;
			}
			$data = $this->frontend_shell_data('Write a review');
			$data['institute_id'] = $iid;
			$data['institute_add_review_submit_url'] = site_url('institute/add-review-submit');
			$this->render_frontend_layout('frontend/institute_add_review', $data);
		}

		public function institute_add_review_submit()
		{
			$in = $this->website_require_ajax_json();
			if ($in === null) {
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Please login again.')));
				return;
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/main/add-review'), $in, array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => 'false', 'msg' => 'Empty response')));
		}

		public function institute_reviews_list()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if (! $this->website_session_is_institute()) {
				show_error('This page is for institute accounts only.', 403);
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Institute reviews');
			$data['institute_reviews_data_url'] = site_url('institute/reviews-data');
			$data['institute_approve_review_submit_url'] = site_url('institute/approve-review-submit');
			$this->render_frontend_layout('frontend/institute_reviews_list', $data);
		}

		public function institute_reviews_data()
		{
			$in = $this->website_require_ajax_json();
			if ($in === null) {
				return;
			}
			if (! $this->website_session_is_institute()) {
				$this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Forbidden')));
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Please login again.')));
				return;
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/main/institute-reviews-list'), $in, array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => 'false', 'msg' => 'Empty response')));
		}

		public function institute_approve_review_submit()
		{
			$in = $this->website_require_ajax_json();
			if ($in === null) {
				return;
			}
			if (! $this->website_session_is_institute()) {
				$this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Forbidden')));
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Please login again.')));
				return;
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/main/approve-review'), $in, array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => 'false', 'msg' => 'Empty response')));
		}

		public function institute_edit_review()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$rid = (int) $this->input->get('review_id');
			if ($rid < 1) {
				redirect(site_url('institute/listing'));
				return;
			}
			$data = $this->frontend_shell_data('Edit review');
			$data['review_id'] = $rid;
			$data['institute_review_detail_data_url'] = site_url('institute/review-detail-data');
			$data['institute_update_review_submit_url'] = site_url('institute/update-review-submit');
			$this->render_frontend_layout('frontend/institute_edit_review', $data);
		}

		public function institute_review_detail_data()
		{
			$in = $this->website_require_ajax_json();
			if ($in === null) {
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Please login again.')));
				return;
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/main/review-detail'), $in, array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => 'false', 'msg' => 'Empty response')));
		}

		public function institute_update_review_submit()
		{
			$in = $this->website_require_ajax_json();
			if ($in === null) {
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Please login again.')));
				return;
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/main/update-review'), $in, array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => 'false', 'msg' => 'Empty response')));
		}

		public function institute_delete_review()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$rid = (int) $this->input->get('review_id');
			if ($rid < 1) {
				redirect(site_url('institute/listing'));
				return;
			}
			$data = $this->frontend_shell_data('Delete review');
			$data['review_id'] = $rid;
			$data['institute_delete_review_submit_url'] = site_url('institute/delete-review-submit');
			$this->render_frontend_layout('frontend/institute_delete_review', $data);
		}

		public function institute_delete_review_submit()
		{
			$in = $this->website_require_ajax_json();
			if ($in === null) {
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Please login again.')));
				return;
			}
			list($code, $body) = $this->website_proxy_post_json(site_url('api/main/delete-review'), $in, array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => 'false', 'msg' => 'Empty response')));
		}

		function readMoreWord($story_desc,$C_word='') {
			$chars = 90;
			if(!empty($C_word)){
				$chars =$C_word;
			}
			
			$count_word = strlen($story_desc);
			if($count_word>$chars){
			   
				$story_desc = substr($story_desc,0,$chars);  
				$story_desc = substr($story_desc,0,strrpos($story_desc,' '));  
				$story_desc = $story_desc ;
				return $story_desc ;  
				
			}else{
				return $story_desc ; 
			}
		}
		

}
