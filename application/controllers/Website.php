<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Website extends MY_Controller
{
	function __construct(){
		parent::__construct();

			// Single-session enforcement for the website. The CI session stores the API
			// access_token at login; if a newer login happens elsewhere (another device,
			// Postman, the mobile app) the server bumps app_token_iat and this stored token
			// becomes stale. Re-validate it every request and drop the web session if stale,
			// so the existing role/token guards redirect this browser back to login.
			$this->enforce_website_single_session();

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

		private function home_batch_instructor_map(array $batch_ids)
		{
			$batch_ids = array_values(array_unique(array_filter(array_map('intval', $batch_ids))));
			if (empty($batch_ids)) {
				return array();
			}

			$this->db->reset_query();
			$rows = $this->db->select('batch_subjects.batch_id, users.name')
				->from('batch_subjects')
				->join('users', 'users.id = batch_subjects.teacher_id', 'left')
				->where_in('batch_subjects.batch_id', $batch_ids)
				->where('users.name IS NOT NULL', null, false)
				->where("TRIM(users.name) <> ''", null, false)
				->order_by('users.name', 'ASC')
				->get()
				->result_array();

			$map = array();
			foreach ($rows as $row) {
				$batch_id = isset($row['batch_id']) ? (int) $row['batch_id'] : 0;
				$name = isset($row['name']) ? trim((string) $row['name']) : '';
				if ($batch_id < 1 || $name === '') {
					continue;
				}
				if (!isset($map[$batch_id])) {
					$map[$batch_id] = array();
				}
				if (!in_array($name, $map[$batch_id], true)) {
					$map[$batch_id][] = $name;
				}
			}

			foreach ($map as $batch_id => $names) {
				$map[$batch_id] = implode(', ', $names);
			}
			return $map;
		}

		function index(){
			// Public home: require a logged-in session (same idea as front_header). No role-based redirects here.
			if (empty($_SESSION['role'])) {
				redirect(base_url('login'));
				return;
			}
			$data['title'] =$this->lang->line('ltr_home');
			$data['frontend_details'] = $this->db_model->select_data('*','frontend_details',array('id'=>'1'),1);
			
			$data['Allcourses'] = $this->db_model->select_data('*','courses use index (id)',array('status'=>'1','admin_id'=>'1'),'');
			//print_r($data['Allcourses']);
			// die();
			$data['Allfacilities'] = $this->db_model->select_data('*','facilities use index (id)',array('status'=>'1'),6);
			$data['courses'] = $this->db_model->select_data('course_name','courses use index (id)',array('status'=>'1','admin_id'=>'1'),5);
			$data['facilities'] = $this->db_model->select_data('title','facilities use index (id)',array('status'=>'1'),5);
			$data['currency_decimal'] =$this->general_settings('currency_decimal_code');
			// Curated home batches + site details come from the shared API (website + mobile share one backend).
			$data['batches'] = '';
			$data['site_Details'] = array();
			list($hc_code, $hc_body) = $this->website_proxy_post_json(site_url('api/main/home-content'), array());
			$hc = json_decode($hc_body, true);
			if (is_array($hc) && isset($hc['data'])) {
				if (!empty($hc['data']['batches']) && is_array($hc['data']['batches'])) {
					$data['batches'] = $hc['data']['batches'];
				}
				if (!empty($hc['data']['siteDetails']) && is_array($hc['data']['siteDetails'])) {
					$data['site_Details'] = $hc['data']['siteDetails'];
				}
			}
			$data['home_institute_api_url'] = site_url('api/institute/listing');
			$data['home_institute_details_url'] = site_url('institute/details');
			$data['api_access_token'] = $this->website_session_access_token();
			$this->render_frontend_layout('frontend/home', $data);
		}

		function login(){
			if (! empty($_SESSION['role'])) {
				redirect($this->website_default_logged_in_url());
				return;
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

		/**
		 * Email verification link from admin-created accounts (institute / teacher / student).
		 */
		public function verify_account($token = '')
		{
			$result = $this->common->email_verify_account($token);
			$data = $this->frontend_shell_data('Verify account');
			$data['verify_success'] = !empty($result['status']);
			$data['verify_message'] = isset($result['msg']) ? $result['msg'] : '';
			$data['load_auth_form_assets'] = true;
			$this->render_frontend_layout('frontend/verify_account', $data);
		}

		public function reset_password_page($token = '')
		{
			$data = $this->frontend_shell_data('Reset password');
			$data['load_auth_form_assets'] = true;
			// Validate the reset token up front so an expired/invalid link shows a clear message.
			$parsed = $this->common->email_verify_token_parse($token);
			$data['reset_token_valid'] = ($parsed !== false);
			$data['reset_token'] = (string) $token;
			$data['set_new_password_url'] = site_url('api/user/set-new-password');
			$data['login_url'] = base_url('login');
			$this->render_frontend_layout('frontend/reset_password_form', $data);
		}

		public function update_profile()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Update Profile');
			$role = (string) $this->session->userdata('role');
			// Profile prefill comes from the shared API (website + mobile share one backend).
			$profile = array(
				'name' => (string) $this->session->userdata('name'),
				'last_name' => '',
				'email' => (string) $this->session->userdata('email'),
				'mobile' => (string) $this->session->userdata('mobile'),
				'address' => '',
				'country' => '',
				'state' => '',
				'city' => '',
				'pincode' => '',
				'school_college_name' => '',
				'grade' => '',
				'show_grade_field' => ($role === 'student'),
			);
			$token = $this->website_session_access_token();
			if ($token !== '') {
				list($pf_code, $pf_body) = $this->website_proxy_post_json(site_url('api/user/profile'), array(), array('Authorization: Bearer ' . $token));
				$pf = json_decode($pf_body, true);
				if (is_array($pf) && !empty($pf['data']['profile']) && is_array($pf['data']['profile'])) {
					$profile = array_merge($profile, $pf['data']['profile']);
				}
			}
			$data['profile'] = $profile;
			$this->render_frontend_layout('frontend/update_profile', $data);
		}

		public function update_password()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Change Password');
			// Logged-in user changes their own password via the shared API (browser-direct).
			$data['change_password_url'] = site_url('api/user/change-password');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['login_url'] = base_url('login');
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
			$raw = file_get_contents('php://input');
			$in = json_decode($raw, true);
			if (! is_array($in)) {
				$in = array();
			}
			$confirmed = !empty($in['confirmed']) || !empty($in['confirm']) || !empty($in['da_confirm']);
			if (!$confirmed) {
				$this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(array('status' => false, 'msg' => 'Please confirm that you want to delete your account.')));
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

		/**
		 * Website logout: clears the web session and returns to login.
		 * Route: logout -> website/logout
		 */
		public function logout()
		{
			$uid = (int) $this->session->userdata('uid');
			if ($uid > 0) {
				// Single-session: clear login_status (blocks any token) and reset app_token_iat.
				$table = ($this->session->userdata('role') === 'student') ? 'students' : 'users';
				$this->db_model->update_data_limit(
					$table,
					array('login_status' => 0, 'app_token_iat' => 0),
					array('id' => $uid),
					1
				);
			}
			$this->session->sess_destroy();
			redirect(base_url('login'));
		}

		public function payment_history()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data($this->common->languageTranslator('ltr_payment_history'));
			// Call the shared API directly from the browser (like batch list), with the session token.
			$data['payment_history_data_url'] = site_url('api/user/payment-history');
			$data['api_access_token'] = $this->website_session_access_token();
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
			$front_home_url = $this->website_front_home_url();

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

			$url = $login_redirect_home ? $front_home_url : $this->website_default_logged_in_url();

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
			// Many Apache setups strip Authorization on internal subrequests; duplicate Bearer into JSON
			// so API helpers that only re-read the body still see access_token.
			foreach ($extra_headers as $h) {
				$h = trim((string) $h);
				if ($h !== '' && preg_match('/^Authorization:\s*Bearer\s*:?\s*(.+)$/i', $h, $m)) {
					$bearer = trim($m[1]);
					if ($bearer !== '' && empty($payload['access_token']) && empty($payload['token'])) {
						$payload['access_token'] = $bearer;
					}
					break;
				}
			}
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
			return rawurldecode(basename($path));
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

		private function website_default_logged_in_url()
		{
			$role = $this->session->userdata('role');
			if ($role === '1' || $role === 1) {
				return base_url('admin/dashboard');
			}
			// Teachers use the same post-login landing page as students (public home).
			return $this->website_front_home_url();
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
		 * Drop the web session when its stored access_token is no longer the latest one
		 * (single-session). parse_access_token() returns false when the token's iat is older
		 * than the DB app_token_iat, when the account is logged out, or when it expired —
		 * any of which means this browser should be signed out.
		 */
		private function enforce_website_single_session()
		{
			// Nothing to enforce for guests / pages without a session token.
			if (empty($_SESSION) || empty($_SESSION['role'])) {
				return;
			}
			$token = $this->website_session_access_token();
			if ($token === '') {
				return;
			}
			if ($this->parse_access_token($token) === false) {
				$this->session->sess_destroy();
				$_SESSION = array();
			}
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
					'url' => $this->website_default_logged_in_url(),
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
				'url' => $this->website_default_logged_in_url(),
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

		private function website_session_is_teacher()
		{
			$ut = strtolower(trim((string) $this->session->userdata('api_user_type')));
			if ($ut === 'teacher') {
				return true;
			}
			$role = $this->session->userdata('role');
			return ($role === 3 || $role === '3');
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
			// One page for both "all" and "my" batches (like institute listing/mylist).
			// list flag: ?list=my|all wins; else 'my' on the batch/mylist route; default 'all'. Never blank.
			$list_flag = trim((string) $this->input->get('list'));
			if ($list_flag === '') {
				$list_flag = ($this->uri->segment(2) === 'mylist') ? 'my' : 'all';
			}
			$is_my = ($list_flag === 'my');
			$data = $this->frontend_shell_data($is_my ? 'My batches' : 'Batches');
			// Call the shared API directly from the browser (like batch/details), with the session token.
			$data['batch_list_data_url'] = site_url('api/batch/batch-list');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['batch_details_base'] = site_url('batch/details');
			$data['is_teacher'] = (int) $this->session->userdata('role') === 3;
			$data['list_flag'] = $list_flag;
			$this->render_frontend_layout('frontend/batch_list', $data);
		}

		public function global_search()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Search');
			// Call the shared api/main/global-search directly from the browser with the session token.
			$data['global_search_url'] = site_url('api/main/global-search');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['batch_details_base'] = site_url('batch/details');
			$data['institute_details_base'] = site_url('institute/details');
			$data['search_key'] = isset($_GET['key']) ? trim((string) $_GET['key']) : '';
			$this->render_frontend_layout('frontend/global_search', $data);
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
			// This page is the "all active batches" listing, so default to list=all
			// (the shared api/batch/batch-list uses list=All for all batches).
			$payload['list'] = (isset($in['list']) && trim((string) $in['list']) !== '') ? trim((string) $in['list']) : 'all';
			list($code, $body) = $this->website_proxy_post_json(site_url('api/batch/batch-list'), $payload, array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => 'false', 'msg' => 'Empty response')));
		}

		// batch/mylist now routes to batch_list() with list_flag = 'my' (single shared page + view).

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
			// "My batches" now uses the SAME shared API as the mobile app (api/batch/batch-list):
			// students get enrolled batches, teachers get created/assigned. list != 'All' = the "my" flow.
			$payload['list'] = (isset($in['list']) && trim((string) $in['list']) !== '') ? trim((string) $in['list']) : 'my';
			list($code, $body) = $this->website_proxy_post_json(site_url('api/batch/batch-list'), $payload, array('Authorization: Bearer ' . $token));
			$this->output->set_status_header((int) $code > 0 ? $code : 200);
			$this->output->set_content_type('application/json')->set_output($body !== '' ? $body : json_encode(array('status' => 'false', 'msg' => 'Empty response')));
		}
		public function teacher_create_batch($batch_id = '')
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			$user_role = (int) $this->session->userdata('role');
			if ($user_role !== 3) {
				redirect(base_url('batch/mylist'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}

			$user_id = (int) $this->session->userdata('uid');
			$user_admin_id = (int) $this->session->userdata('admin_id');
			$cond = array('admin_id' => $user_admin_id, 'status' => '1');

			$data = $this->frontend_shell_data(empty($batch_id) ? 'Create Batch' : 'Edit Batch');
			$data['load_batch_form_assets'] = true;
			$data['load_batch_dropdowns_via_api'] = true;
			$data['api_access_token'] = $this->website_session_access_token();
			$data['batch_subjects_api_url'] = site_url('api/batch/batch-subjects');
			$data['batch_categories_api_url'] = site_url('api/batch/categories');
			$data['batch_subcategories_api_url'] = site_url('api/batch/subcategories');
			$data['teacher_user_id'] = $user_id;
			$data['batch_id'] = '';
			$data['batch_fecherd'] = array();

			$token = $this->website_session_access_token();

			if (!empty($batch_id)) {
				$batch_id = (int) $batch_id;
				$data['batch_id'] = $batch_id;
				// Edit prefill via the shared API (teacher-batch-edit does the ownership check).
				list($be_code, $be_body) = $this->website_proxy_post_json(site_url('api/batch/teacher-batch-edit'), array('batchId' => $batch_id), array('Authorization: Bearer ' . $token));
				$be = json_decode($be_body, true);
				$ok = is_array($be) && isset($be['status']) && in_array((string) $be['status'], array('true', '1'), true) && !empty($be['data']);
				if (!$ok) {
					redirect(base_url('batch/mylist'));
					return;
				}
				$bd = $be['data'];
				// Remap the API's camelCase response into the raw row the shared form expects.
				$data['batch_data'] = array(array(
					'id' => $batch_id,
					'batch_name' => isset($bd['batchName']) ? $bd['batchName'] : '',
					'cat_id' => isset($bd['categoryId']) ? $bd['categoryId'] : '',
					'sub_cat_id' => isset($bd['subcategoryId']) ? $bd['subcategoryId'] : '',
					'institute_id' => isset($bd['instituteId']) ? $bd['instituteId'] : '',
					'batch_mode' => isset($bd['batchMode']) ? $bd['batchMode'] : '',
					'start_date' => isset($bd['startDate']) ? $bd['startDate'] : '',
					'end_date' => isset($bd['endDate']) ? $bd['endDate'] : '',
					'start_time' => isset($bd['startTime']) ? $bd['startTime'] : '',
					'end_time' => isset($bd['endTime']) ? $bd['endTime'] : '',
					'batch_type' => isset($bd['batchType']) ? $bd['batchType'] : '',
					'batch_price' => isset($bd['batchPrice']) ? $bd['batchPrice'] : '',
					'batch_offer_price' => isset($bd['batchOfferPrice']) ? $bd['batchOfferPrice'] : '',
					'pay_mode' => isset($bd['payMode']) ? $bd['payMode'] : '',
					'description' => isset($bd['description']) ? $bd['description'] : '',
				));
				$fecherd = array();
				if (!empty($bd['benefits']) && is_array($bd['benefits'])) {
					foreach ($bd['benefits'] as $bn) {
						$features = isset($bn['features']) && is_array($bn['features']) ? array_values($bn['features']) : array();
						$fecherd[] = array(
							'id' => isset($bn['id']) ? $bn['id'] : '',
							'batch_specification_heading' => isset($bn['heading']) ? $bn['heading'] : '',
							'batch_fecherd' => json_encode($features),
						);
					}
				}
				$data['batch_fecherd'] = $fecherd;

				// Subject prefill from the same API response (remapped to the shared form's raw shape).
				// The shared form uses $batch_subjects_prefill when set (teacher flow); admin keeps its own DB read.
				$subjects_prefill = array();
				if (!empty($bd['subjects']) && is_array($bd['subjects'])) {
					foreach ($bd['subjects'] as $sb) {
						$chapter_ids = isset($sb['chapterIds']) && is_array($sb['chapterIds']) ? array_values($sb['chapterIds']) : array();
						$subjects_prefill[] = array(
							'id' => isset($sb['id']) ? $sb['id'] : '',
							'subject_id' => isset($sb['subjectId']) ? $sb['subjectId'] : '',
							'subject_name' => isset($sb['subjectName']) ? $sb['subjectName'] : '',
							'teacher_id' => isset($sb['teacherId']) ? $sb['teacherId'] : '',
							'chapter' => json_encode($chapter_ids),
							'sub_start_date' => isset($sb['startDate']) ? $sb['startDate'] : '',
							'sub_end_date' => isset($sb['endDate']) ? $sb['endDate'] : '',
							'sub_start_time' => isset($sb['startTime']) ? $sb['startTime'] : '',
							'sub_end_time' => isset($sb['endTime']) ? $sb['endTime'] : '',
						);
					}
				}
				$data['batch_subjects_prefill'] = $subjects_prefill;
			}

			$currency_rows = $this->db_model->select_data('*', 'general_settings', array('key_text' => 'currency_decimal_code'));
			$data['currency_code'] = (!empty($currency_rows[0]['velue_text'])) ? $currency_rows[0]['velue_text'] : '';
			// Category / subcategory / subject dropdowns load via API on teacher batch form.
			$data['subject'] = array();
			$data['category_data'] = array();
			$data['subcat_data'] = array();

			// Institute dropdown options come from the shared API (teacher-batch-form-options).
			$data['institute_list'] = array();
			list($fo_code, $fo_body) = $this->website_proxy_post_json(site_url('api/batch/teacher-batch-form-options'), array(), array('Authorization: Bearer ' . $token));
			$fo = json_decode($fo_body, true);
			if (is_array($fo) && !empty($fo['data']['institutes']) && is_array($fo['data']['institutes'])) {
				$data['institute_list'] = $fo['data']['institutes'];
			}

			$this->render_frontend_layout('frontend/teacher_create_batch', $data);
		}

		public function delete_batch()
		{
			if (! isset($this->session->userdata['role'])) {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Unauthorized')));
				return;
			}
			$in = $this->website_require_ajax_json();
			if ($in === null) {
				return;
			}
			if (empty($in['batch_id'])) {
				$this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Batch ID is required')));
				return;
			}

			$user_role = (int) $this->session->userdata('role');
			$batch_id = (int) $in['batch_id'];

			// Only teachers can delete batches via frontend
			if ($user_role !== 3) {
				$this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'You are not authorized to delete batches')));
				return;
			}

			$token = $this->website_session_access_token();
			if ($token === '') {
				$this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(array('status' => 'false', 'msg' => 'Please login again.')));
				return;
			}

			// Ownership check + cascade delete (batch_subjects, batch_fecherd, student_batchs, batches)
			// all live in the shared API so the backend logic stays in one place (website + mobile).
			$payload = array('batchId' => $batch_id);
			list($code, $body) = $this->website_proxy_post_json(site_url('api/batch/teacher-delete-batch'), $payload, array('Authorization: Bearer ' . $token));
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
			$api_ut = strtolower(trim((string) $this->session->userdata('api_user_type')));
			$role = $this->session->userdata('role');
			$data['can_manage_batch_zoom'] = ($api_ut === 'teacher' || $api_ut === 'institute' || (string) $role === '3' || (string) $role === '4' || $role === 3 || $role === 4);
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
			// Application-fee breakdown (499/99 rules) so the page shows what the server will charge.
			$data['application_fee'] = null;
			if ($batch_id > 0 && (string) $this->session->userdata('role') === 'student') {
				$data['application_fee'] = $this->common->compute_application_fee((int) $this->session->userdata('uid'), $batch_id);
			}
			// Free institute (users.paid = 0): no payment — the page shows an Enroll button instead.
			$data['is_free_institute'] = $batch_id > 0 ? $this->website_batch_institute_is_free($batch_id) : false;
			$data['batch_free_enroll_url'] = site_url('batch/free-enroll');
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

		/** True when the batch's owning institute is free (users.paid = '0'). */
		private function website_batch_institute_is_free($batch_id)
		{
			$brow = $this->db_model->select_data('institute_id,admin_id', 'batches use index (id)', array('id' => (int) $batch_id), 1);
			if (empty($brow[0])) {
				return false;
			}
			$iid = !empty($brow[0]['institute_id']) ? (int) $brow[0]['institute_id'] : (int) $brow[0]['admin_id'];
			return $this->institute_is_free($iid);
		}

		/**
		 * POST batch/free-enroll — enroll the logged-in student into a FREE-institute batch without
		 * payment. Mirrors the paid flow's enrollment (student_batchs + students.batch_id + email)
		 * but does no Razorpay step. Rejected for non-free institutes (so it can't bypass paid ones).
		 */
		public function batch_free_enroll()
		{
			header('Content-Type: application/json');
			if (! isset($this->session->userdata['role']) || $this->website_session_access_token() === '') {
				echo json_encode(array('status' => 'false', 'msg' => 'Please log in again.'));
				return;
			}
			if ((string) $this->session->userdata('role') !== 'student') {
				echo json_encode(array('status' => 'false', 'msg' => 'Only students can enroll.'));
				return;
			}
			$student_id = (int) $this->session->userdata('uid');
			$body = json_decode((string) file_get_contents('php://input'), true);
			if (! is_array($body)) {
				$body = array();
			}
			$batch_id = (int) (isset($body['batch_id']) ? $body['batch_id'] : $this->input->post('batch_id'));
			if ($student_id < 1 || $batch_id < 1) {
				echo json_encode(array('status' => 'false', 'msg' => $this->lang->line('ltr_missing_parameters_msg')));
				return;
			}

			// Security: only allow free enrollment when the institute is actually free.
			if (! $this->website_batch_institute_is_free($batch_id)) {
				echo json_encode(array('status' => 'false', 'msg' => 'This batch requires payment.'));
				return;
			}

			$batch = $this->db_model->select_data('id,batch_name,admin_id,institute_id', 'batches use index (id)', array('id' => $batch_id), 1);
			if (empty($batch[0])) {
				echo json_encode(array('status' => 'false', 'msg' => $this->lang->line('ltr_no_record_msg')));
				return;
			}
			$admin_id = (int) $batch[0]['admin_id'];

			// Enrollment must be ACTIVE (batch-details treats student_batchs.status === 1 as enrolled).
			$exists = $this->db_model->select_data('id,status', 'student_batchs', array('student_id' => $student_id, 'batch_id' => $batch_id), 1);
			$newly_enrolled = empty($exists);
			if (! empty($exists)) {
				// Re-activate an existing inactive row.
				if ((int) $exists[0]['status'] !== 1) {
					$this->db_model->update_data_limit('student_batchs', array('status' => 1), array('id' => (int) $exists[0]['id']), 1);
				}
			} else {
				$this->db_model->insert_data('student_batchs', array(
					'student_id' => $student_id,
					'batch_id' => $batch_id,
					'added_by' => 'student',
					'admin_id' => $admin_id,
					'status' => 1,
				));
			}
			if ($newly_enrolled) {
				$this->db_model->update_data_limit('students use index (id)', array('batch_id' => $batch_id, 'status' => 1), array('id' => $student_id), 1);
				$srow = $this->db_model->select_data('admin_id', 'students use index (id)', array('id' => $student_id), 1);
				if (! empty($srow) && (int) $srow[0]['admin_id'] === 0 && $admin_id > 0) {
					$this->db_model->update_data_limit('students use index (id)', array('admin_id' => $admin_id), array('id' => $student_id), 1);
				}

				// Enrollment email (best-effort).
				$stu = $this->db_model->select_data('name,email,enrollment_id', 'students use index (id)', array('id' => $student_id), 1);
				if (! empty($stu[0]['email'])) {
					@$this->notification_service->common_send_email_push(array(
						'purpose' => 'enrolled_batch',
						'user_id' => $student_id,
						'user_type' => 'student',
						'to_email' => $stu[0]['email'],
						'dynamic_var' => array(
							'name' => isset($stu[0]['name']) ? $stu[0]['name'] : '',
							'batch_name' => isset($batch[0]['batch_name']) ? $batch[0]['batch_name'] : '',
							'enrollment_id' => isset($stu[0]['enrollment_id']) ? $stu[0]['enrollment_id'] : '',
							'link' => base_url('login'),
						),
					));
				}
			}

			echo json_encode(array(
				'status' => 'true',
				'msg' => $this->lang->line('ltr_batch_change_msg'),
				'redirect' => site_url('batch/live-classes?batch_id=' . $batch_id),
			));
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
			$data['batch_details_url'] = site_url('batch/details');
			$data['recorded_meetings_url'] = site_url('batch/recorded-meetings');
			$this->render_frontend_layout('frontend/batch_live_classes', $data);
		}

		public function batch_video_lectures()
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
			$data = $this->frontend_shell_data('Video lectures');
			$data['batch_id'] = $batch_id;
			$data['api_access_token'] = $this->website_session_access_token();
			$data['video_list_api_url'] = site_url('api/batch/video-lecture-list');
			$data['batch_mylist_data_url'] = site_url('batch/mylist-data');
			$this->render_frontend_layout('frontend/batch_video_lectures', $data);
		}

		public function batch_exams()
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
			$data = $this->frontend_shell_data('Exams');
			$data['batch_id'] = $batch_id;
			$data['api_access_token'] = $this->website_session_access_token();
			$data['student_exam_dashboard_api_url'] = site_url('api/batch/student-exam-dashboard');
			$data['student_exam_attempt_url'] = site_url('batch/exam-attempt');
			$data['student_exam_result_url'] = site_url('batch/exam-result');
			$data['exam_omr_sheet_api_url'] = site_url('api/batch/exam-omr-sheet');
			$this->render_frontend_layout('frontend/batch_exams', $data);
		}

		public function batch_exam_attempt()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$exam_id = (int) $this->input->get('exam_id');
			if ($exam_id < 1) {
				$exam_id = (int) $this->input->get('id');
			}
			$data = $this->frontend_shell_data('Assessment');
			$data['exam_id'] = $exam_id;
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['student_exam_paper_api_url'] = site_url('api/batch/student-exam-paper');
			$data['student_submit_exam_api_url'] = site_url('api/batch/student-submit-exam');
			$data['student_exam_result_page_url'] = site_url('batch/exam-result');
			$data['student_exam_list_page_url'] = site_url('batch/exams');
			$data['exam_omr_sheet_api_url'] = site_url('api/batch/exam-omr-sheet');
			$this->render_frontend_layout('frontend/batch_exam_attempt', $data);
		}

		public function batch_exam_result()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$exam_id = (int) $this->input->get('exam_id');
			if ($exam_id < 1) {
				$exam_id = (int) $this->input->get('id');
			}
			$data = $this->frontend_shell_data('Assessment');
			$data['exam_id'] = $exam_id;
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['exam_done'] = (int) $this->input->get('done') === 1;
			$data['api_access_token'] = $this->website_session_access_token();
			$data['student_exam_result_api_url'] = site_url('api/batch/student-exam-result');
			$data['student_exam_list_page_url'] = site_url('batch/exams');
			$data['exam_omr_sheet_api_url'] = site_url('api/batch/exam-omr-sheet');
			$this->render_frontend_layout('frontend/batch_exam_result', $data);
		}

		public function batch_recorded_meetings()
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
			$data = $this->frontend_shell_data('Recorded meetings');
			$data['batch_id'] = $batch_id;
			$data['api_access_token'] = $this->website_session_access_token();
			$data['recorded_meeting_list_url'] = site_url('api/batch/recorded-meeting-list');
			$data['recorded_meeting_sync_url'] = site_url('api/batch/recorded-meeting-sync');
			$data['is_teacher_or_institute'] = $this->website_session_is_teacher() || $this->website_session_is_institute();
			$this->render_frontend_layout('frontend/batch_recorded_meetings', $data);
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
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['live_class_details_url'] = site_url('api/batch/live-class-details');
			$data['live_meeting_end_url'] = site_url('api/batch/live-meeting-end');
			$data['live_recording_start_url'] = site_url('api/batch/live-recording-start');
			$data['live_recording_stop_url'] = site_url('api/batch/live-recording-stop');
			$data['is_teacher_host'] = $this->website_session_is_teacher() || $this->website_session_is_institute();
			$data['auto_join'] = in_array(strtolower((string) $this->input->get('join')), array('1', 'true', 'yes'), true);
			$data['batch_details_url'] = site_url('batch/details');
			$data['live_classes_url'] = site_url('batch/live-classes');
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
			$data['attendance_batch_options_url'] = site_url('batch/mylist-data');
			$data['attendance_is_teacher'] = $this->website_session_is_teacher();
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
			$data['my_submissions_api_url'] = site_url('api/batch/my-homework-submissions');
			$data['homework_view_url'] = site_url('homework/view');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$this->render_frontend_layout('frontend/homework_list_page', $data);
		}

		public function homework_detail_page()
		{
			if (! isset($this->session->userdata['role'])) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Homework details');
			$data['homework_id'] = (int) $this->input->get('homework_id');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['homework_details_api_url'] = site_url('api/batch/homework-details');
			$data['homework_submit_api_url'] = site_url('api/batch/homework-submit');
			$data['my_submissions_api_url'] = site_url('api/batch/my-homework-submissions');
			$data['homework_list_url'] = site_url('homework-list');
			$this->render_frontend_layout('frontend/homework_detail_page', $data);
		}

		public function teacher_homework_submissions_page()
		{
			if (! isset($this->session->userdata['role']) || ! $this->website_session_is_teacher()) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Homework submissions');
			$data['homework_id'] = (int) $this->input->get('homework_id');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['homework_details_api_url'] = site_url('api/batch/homework-details');
			$data['homework_submissions_api_url'] = site_url('api/batch/homework-submissions');
			$data['attendance_roster_api_url'] = site_url('api/batch/attendance-roster');
			$data['teacher_homework_url'] = site_url('teacher/homework');
			$data['submission_detail_url'] = site_url('teacher/homework/submission');
			$this->render_frontend_layout('frontend/teacher/teacher_homework_submissions', $data);
		}

		public function teacher_homework_submission_page()
		{
			if (! isset($this->session->userdata['role']) || ! $this->website_session_is_teacher()) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Review submission');
			$data['submission_id'] = (int) $this->input->get('submission_id');
			$data['homework_id'] = (int) $this->input->get('homework_id');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['submission_details_api_url'] = site_url('api/batch/homework-submission-details');
			$data['homework_evaluate_api_url'] = site_url('api/batch/homework-evaluate');
			$data['submissions_list_url'] = site_url('teacher/homework/submissions');
			$this->render_frontend_layout('frontend/teacher/teacher_homework_submission_detail', $data);
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
			// The Notification Page shows the FULL list (all_notifications-list) — including items the
			// user cleared from the header popup (clear = 1). The header uses the active list only.
			$data['notifications_api_url'] = site_url('api/main/all_notifications-list');
			$data['notifications_read_url'] = site_url('api/main/notifications-read');
			$data['notifications_delete_url'] = site_url('api/main/notifications-delete');
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

		public function teacher_attendance_page()
		{
			if (! isset($this->session->userdata['role']) || ! $this->website_session_is_teacher()) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Teacher Attendance');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['attendance_roster_api_url'] = site_url('api/batch/attendance-roster');
			$data['attendance_save_api_url'] = site_url('api/user/add-attendance');
			$data['attendance_matrix_api_url'] = site_url('api/batch/attendance-roster-matrix');
			$data['attendance_matrix_save_api_url'] = site_url('api/batch/attendance-matrix-save');
			$this->render_frontend_layout('frontend/teacher/teacher_attendance', $data);
		}

		public function teacher_videos_page()
		{
			if (! isset($this->session->userdata['role']) || ! $this->website_session_is_teacher()) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Teacher Videos');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['video_list_api_url'] = site_url('api/batch/video-lecture-list');
			$data['video_add_api_url'] = site_url('api/batch/video-lecture-add');
			$data['video_edit_api_url'] = site_url('api/batch/video-lecture-edit');
			$data['video_delete_api_url'] = site_url('api/batch/video-lecture-delete');
			$data['student_video_lectures_url'] = site_url('batch/video-lectures?batch_id=' . (int) $data['batch_id']);
			$this->render_frontend_layout('frontend/teacher/teacher_videos', $data);
		}

		public function teacher_books_page()
		{
			if (! isset($this->session->userdata['role']) || ! $this->website_session_is_teacher()) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Teacher Library Books');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['library_list_api_url'] = site_url('api/batch/library-list');
			$data['library_add_api_url'] = site_url('api/batch/library-add-book');
			$data['library_edit_api_url'] = site_url('api/batch/library-edit-book');
			$data['library_delete_api_url'] = site_url('api/batch/library-delete-book');
			$this->render_frontend_layout('frontend/teacher/teacher_books', $data);
		}

		public function teacher_notes_page()
		{
			if (! isset($this->session->userdata['role']) || ! $this->website_session_is_teacher()) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Teacher Notes');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['notes_list_api_url'] = site_url('api/batch/notes-list');
			$data['notes_add_api_url'] = site_url('api/batch/notes-add');
			$data['notes_edit_api_url'] = site_url('api/batch/notes-edit');
			$data['notes_delete_api_url'] = site_url('api/batch/notes-delete');
			$this->render_frontend_layout('frontend/teacher/teacher_notes', $data);
		}

		public function teacher_homework_page()
		{
			if (! isset($this->session->userdata['role']) || ! $this->website_session_is_teacher()) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$data = $this->frontend_shell_data('Teacher Homework');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['homework_list_api_url'] = site_url('api/batch/homework-list');
			$data['batch_subjects_api_url'] = site_url('api/batch/batch-subjects');
			$data['homework_add_api_url'] = site_url('api/batch/homework-add');
			$data['homework_edit_api_url'] = site_url('api/batch/homework-edit');
			$data['homework_delete_api_url'] = site_url('api/batch/homework-delete');
			$data['homework_submissions_page_url'] = site_url('teacher/homework/submissions');
			$this->render_frontend_layout('frontend/teacher/teacher_homework', $data);
		}

		public function teacher_exams_page()
		{
			if (! isset($this->session->userdata['role']) || (! $this->website_session_is_teacher() && ! $this->website_session_is_institute())) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$is_institute = $this->website_session_is_institute();
			$data = $this->frontend_shell_data($is_institute ? 'Institute Exams' : 'Teacher Exams');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['batch_name'] = '';
			if ($data['batch_id'] > 0) {
				// Batch name (page heading) comes from the shared API, not a direct DB read.
				$token = $this->website_session_access_token();
				list($code, $body) = $this->website_proxy_post_json(site_url('api/batch/batch-details'), array('batch_id' => $data['batch_id']), array('Authorization: Bearer ' . $token));
				$decoded = json_decode($body, true);
				if (is_array($decoded) && !empty($decoded['data']['batch_details']['batchName'])) {
					$data['batch_name'] = (string) $decoded['data']['batch_details']['batchName'];
				}
			}
			$data['api_access_token'] = $this->website_session_access_token();
			$data['exam_list_api_url'] = site_url('api/batch/exam-manage-list');
			$data['exam_details_api_url'] = site_url('api/batch/upcoming-exam-details');
			$data['exam_add_api_url'] = site_url('api/batch/exam-add');
			$data['exam_edit_api_url'] = site_url('api/batch/exam-edit');
			$data['exam_delete_api_url'] = site_url('api/batch/exam-delete');
			$data['batch_subjects_api_url'] = site_url('api/batch/batch-subjects');
			$data['batch_chapters_api_url'] = site_url('api/batch/batch-chapters');
			$data['batch_mylist_data_url'] = site_url('batch/mylist-data');
			$data['exam_builder_role_label'] = $is_institute ? 'Institute' : 'Teacher';
			$data['exam_submissions_page_url'] = site_url($is_institute ? 'institute/exam/submissions' : 'teacher/exam/submissions');
			$this->render_frontend_layout('frontend/exam_builder_page', $data);
		}

		public function teacher_exam_submissions_page()
		{
			if (! isset($this->session->userdata['role']) || (! $this->website_session_is_teacher() && ! $this->website_session_is_institute())) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$is_institute = $this->website_session_is_institute();
			$data = $this->frontend_shell_data('Exam submissions');
			$data['exam_id'] = (int) $this->input->get('exam_id');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['exam_submission_list_api_url'] = site_url('api/batch/exam-submission-list');
			$data['exam_details_api_url'] = site_url('api/batch/upcoming-exam-details');
			$data['teacher_exams_url'] = site_url($is_institute ? 'institute/exams' : 'teacher/exams');
			$data['submission_detail_url'] = site_url($is_institute ? 'institute/exam/submission' : 'teacher/exam/submission');
			$data['exam_omr_sheet_api_url'] = site_url('api/batch/exam-omr-sheet');
			$this->render_frontend_layout('frontend/teacher/teacher_exam_submissions', $data);
		}

		public function teacher_exam_submission_page()
		{
			if (! isset($this->session->userdata['role']) || (! $this->website_session_is_teacher() && ! $this->website_session_is_institute())) {
				redirect(base_url('login'));
				return;
			}
			if ($this->website_session_access_token() === '') {
				redirect(base_url('login'));
				return;
			}
			$is_institute = $this->website_session_is_institute();
			$data = $this->frontend_shell_data('Submission details');
			$data['submission_id'] = (int) $this->input->get('submission_id');
			$data['exam_id'] = (int) $this->input->get('exam_id');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['exam_submission_details_api_url'] = site_url('api/batch/exam-submission-details');
			$data['submissions_list_url'] = site_url($is_institute ? 'institute/exam/submissions' : 'teacher/exam/submissions');
			$data['exam_omr_sheet_api_url'] = site_url('api/batch/exam-omr-sheet');
			$this->render_frontend_layout('frontend/teacher/teacher_exam_submission_detail', $data);
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
			// Call the shared API directly from the browser (like batch list), with the session token.
			$data['institute_listing_data_url'] = site_url('api/institute/listing');
			$data['api_access_token'] = $this->website_session_access_token();
			$data['institute_details_url'] = site_url('institute/details');
			$data['institute_city_list_url'] = site_url('api/institute/city-list');
			$data['batch_id'] = (int) $this->input->get('batch_id');
			$data['show_institute_reviews_link'] = $this->website_session_is_institute();
			$data['institute_reviews_list_url'] = site_url('institute/reviews-list');
			// list flag: ?list=my|all wins; else 'my' on the institute/mylist route; default 'all'. Never blank.
			$list_flag = trim((string) $this->input->get('list'));
			if ($list_flag === '') {
				$list_flag = ($this->uri->segment(2) === 'mylist') ? 'my' : 'all';
			}
			$data['list_flag'] = $list_flag;
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
			// Call the shared API directly from the browser (like batch details), with the session token.
			$data['institute_details_data_url'] = site_url('api/institute/details');
			$data['api_access_token'] = $this->website_session_access_token();
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
