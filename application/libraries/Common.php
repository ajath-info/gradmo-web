<?php
defined('BASEPATH') OR exit('No direct script access allowed');
	class Common{
		public $CI;
		public $siteLogo = '';
		public $siteminiLogo = '';
		public $siteFavicon = '';
		public $siteLoader = '';
		public $siteTitle = '';
		public $siteAuthorName = '';
		public $siteDescription = '';
		public $siteKeywords = '';
		public $enrollWord = '';		
		public $copyrightText = '';	
		public $siteOwnerEmail = '';	
		public $languageTranslator = '';	
		public $language_name = '';	
		public $rzp_key = '';
		public $theme_colors = '';
		
		
		function __construct(){
			$this->CI = get_instance();
			$this->CI->load->model('db_model');
			/** @var object{db_model: Db_model} $ci */
			$ci = $this->CI;
			$db_model = $ci->db_model;

			$site_details = $db_model->select_data('*','site_details',array('id'=>1),1);
			$front_details = $db_model->select_data('email','frontend_details',array('id'=>1),1);
			$theme_colors = $db_model->select_data('*','theme_color  use index (id)',array('status'=>'1'),1,array('id','desc'));
				if(!empty($site_details)){
					if($site_details[0]['site_logo']!='')
						$img = base_url().'uploads/site_data/'.$site_details[0]['site_logo'];
					else
                        $img = base_url().'assets/images/logo.png';
                        
						if($site_details[0]['site_minilogo']!='')
						$mini_img = base_url().'uploads/site_data/'.$site_details[0]['site_minilogo'];
					else
                        $mini_img = base_url().'assets/images/mini_logo.png';
						
                    if($site_details[0]['site_favicon']!='')
						$fav = base_url().'uploads/site_data/'.$site_details[0]['site_favicon'];
					else
                        $fav = base_url().'assets/images/favicon.png';

                    if($site_details[0]['site_loader']!='')
						$loader = base_url().'uploads/site_data/'.$site_details[0]['site_loader'];
					else
                        $loader = base_url().'assets/images/preloader.gif';
                    
                    $siteTitle = ($site_details[0]['site_title']!='')?$site_details[0]['site_title']:'E Academy';
                    $authorName = ($site_details[0]['site_author']!='')?$site_details[0]['site_author']:'E Academy';
                    $desccription = ($site_details[0]['site_description']!='')?$site_details[0]['site_description']:'E Academy';
                    $keyword = ($site_details[0]['site_keywords']!='')?$site_details[0]['site_keywords']:'E Academy';
                    $enrol_word = ($site_details[0]['enrollment_word']!='')?$site_details[0]['enrollment_word']:'ACAD';
                    $copyright = ($site_details[0]['copyright_text']!='')?$site_details[0]['copyright_text']:'Copyright &copy; 2020 E Academy. All Right Reserved.';
                    $siteemail = ($front_details[0]['email']!='')?$front_details[0]['email']:'';
					
					$this->siteLogo = $img;
					$this->siteminiLogo = $mini_img;
                    $this->siteFavicon = $fav;
                    $this->siteLoader = $loader;
					$this->siteTitle = $siteTitle;
					$this->siteAuthorName = $authorName;
					$this->siteDescription = $desccription;
					$this->siteKeywords = $keyword;		
					$this->enrollWord = $enrol_word;		
					$this->copyrightText = $copyright;		
					$this->siteOwnerEmail = $siteemail;	
					 $this->theme_colors = $theme_colors;	
				}


			$language = $this->general_settings('language_name');
			$this->language_name= $language;
			$razorpay_key_id = $this->general_settings('razorpay_key_id');
			$this->rzp_key= $razorpay_key_id;
			if($language=="french"){
				$this->CI->lang->load('french_lang', 'french');
			}else if($language=="arabic"){
				$this->CI->lang->load('arabic_lang', 'arabic');
			}else if($language=="english"){
				$this->CI->lang->load('english_lang', 'english');
			}else if($language=="hindi"){
		    	$this->CI->lang->load('hindi_lang', 'hindi');
			}else if($language=="german"){
		    	$this->CI->lang->load('german_lang', 'german');
			}else{
			    $this->CI->lang->load('spanish_lang', 'spanish');
			}
		}	
		function general_settings($key_text=''){
			/** @var object{db_model: Db_model} $ci */
			$ci = $this->CI;
			$data = $ci->db_model->select_data('*','general_settings',array('key_text'=>$key_text),1);
			return $data[0]['velue_text'];
		}
		function languageTranslator($traWord=''){
            return $this->CI->lang->line($traWord);
		}

		/**
		 * Send email using `templates` row by purpose.
		 *
		 * $arr['purpose']     — templates.purpose (required), e.g. register, forgot_password
		 * $arr['user_id']     — optional; used to resolve email/name if to_email omitted
		 * $arr['user_type']   — student|teacher|institute (optional with user_id)
		 * $arr['to_email']    — override recipient
		 * $arr['dynamic_var'] — key => value replacements in subject/body ({{key}} or {key})
		 * $arr['template_for'] — email|sms (default email)
		 */
		public function send_email($arr = array())
		{
			if (!is_array($arr) || empty($arr['purpose'])) {
				return array('status' => false, 'msg' => 'Email purpose is required');
			}

			$purpose = trim((string) $arr['purpose']);
			$template_for = isset($arr['template_for']) ? trim((string) $arr['template_for']) : 'email';
			if ($template_for === '') {
				$template_for = 'email';
			}

			// templates.status: 1 = Active, 0 = Inactive (only active templates are sent).
			$template = $this->CI->db_model->select_data(
				'*',
				'templates',
				array('purpose' => $purpose, 'template_for' => $template_for, 'status' => '1'),
				1,
				array('id', 'desc')
			);
			if (empty($template[0]) && $template_for !== 'email') {
				$template = $this->CI->db_model->select_data(
					'*',
					'templates',
					array('purpose' => $purpose, 'template_for' => 'email', 'status' => '1'),
					1,
					array('id', 'desc')
				);
			}
			if (empty($template[0])) {
				return array('status' => false, 'msg' => 'Email template not found or inactive: ' . $purpose);
			}
			$row = $template[0];

			$userMeta = $this->email_resolve_user(
				isset($arr['user_id']) ? (int) $arr['user_id'] : 0,
				isset($arr['user_type']) ? trim((string) $arr['user_type']) : ''
			);

			$to_email = isset($arr['to_email']) ? trim((string) $arr['to_email']) : '';
			if ($to_email === '' && !empty($userMeta['email'])) {
				$to_email = $userMeta['email'];
			}
			if ($to_email === '' || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
				return array('status' => false, 'msg' => 'Valid recipient email is required');
			}

			$vars = array(
				'site_name' => $this->siteTitle,
				'site_logo' => $this->siteLogo,
				'support_email' => $this->siteOwnerEmail,
				'link' => base_url('login'),
				'year' => date('Y'),
			);
			if (!empty($userMeta)) {
				$vars = array_merge($vars, $userMeta);
			}
			if (!empty($arr['dynamic_var']) && is_array($arr['dynamic_var'])) {
				foreach ($arr['dynamic_var'] as $k => $v) {
					$vars[$k] = is_scalar($v) || $v === null ? (string) $v : '';
				}
			}

			$subject = $this->email_apply_vars(isset($row['title']) ? $row['title'] : $purpose, $vars);
			$description = $this->email_apply_vars(isset($row['description']) ? $row['description'] : '', $vars);
			$html_code = $this->email_apply_vars(isset($row['html_code']) ? $row['html_code'] : '', $vars);

			$body = trim($html_code);
			if ($description !== '') {
				$body = ($body !== '') ? ($description . '<br><br>' . $body) : $description;
			}
			if ($body === '') {
				$body = '<p>' . html_escape($subject) . '</p>';
			}

			if (!empty($row['image'])) {
				$img_url = base_url('uploads/templates/' . $row['image']);
				$body = '<p><img src="' . $img_url . '" alt="" style="max-width:200px;"></p>' . $body;
			}

			$sent = $this->email_dispatch($to_email, $subject, $body);
			return array(
				'status' => $sent ? true : false,
				'msg' => $sent ? 'Email sent' : 'Failed to send email',
				'to' => $to_email,
				'subject' => $subject,
			);
		}

		private function email_resolve_user($user_id, $user_type = '')
		{
			if ($user_id < 1) {
				return array();
			}

			$user_type = strtolower(trim($user_type));

			if ($user_type === 'student' || $user_type === '') {
				$student = $this->CI->db_model->select_data(
					'id,name,email,enrollment_id,contact_no,mobile',
					'students use index (id)',
					array('id' => $user_id),
					1
				);
				if (!empty($student[0])) {
					return array(
						'name' => $student[0]['name'],
						'email' => $student[0]['email'],
						'enrollment_id' => isset($student[0]['enrollment_id']) ? $student[0]['enrollment_id'] : '',
						'mobile' => !empty($student[0]['contact_no']) ? $student[0]['contact_no'] : (isset($student[0]['mobile']) ? $student[0]['mobile'] : ''),
					);
				}
			}

			if ($user_type === '' || in_array($user_type, array('teacher', 'institute'), true)) {
				$cond = array('id' => $user_id);
				if ($user_type !== '') {
					$cond['user_type'] = $user_type;
				}
				$user = $this->CI->db_model->select_data(
					'id,name,email,mobile,user_type',
					'users use index (id)',
					$cond,
					1
				);
				if (!empty($user[0])) {
					return array(
						'name' => $user[0]['name'],
						'email' => $user[0]['email'],
						'mobile' => isset($user[0]['mobile']) ? $user[0]['mobile'] : '',
						'user_type' => isset($user[0]['user_type']) ? $user[0]['user_type'] : '',
					);
				}
			}

			return array();
		}

		private function email_apply_vars($text, array $vars)
		{
			$text = (string) $text;
			foreach ($vars as $key => $value) {
				$safe = (string) $value;
				// Case-insensitive so template authors can use {{NAME}}, {{name}} or {name}.
				$text = str_ireplace(array('{{' . $key . '}}', '{' . $key . '}'), $safe, $text);
			}
			return $text;
		}

		private function email_dispatch($to_email, $subject, $body)
		{
			$frommail = $this->general_settings('smtp_mail');
			$frompwd = $this->general_settings('smtp_pwd');
			if ($frommail === '' || $frompwd === '') {
				return false;
			}

			$this->CI->load->library('email');
			$config = array(
				'protocol' => $this->general_settings('server_type'),
				'smtp_host' => $this->general_settings('smtp_host'),
				'smtp_port' => $this->general_settings('smtp_port'),
				'smtp_user' => $frommail,
				'smtp_pass' => $frompwd,
				'charset' => 'utf-8',
				'mailtype' => 'html',
				'smtp_crypto' => $this->general_settings('smtp_encryption'),
				'newline' => "\r\n",
			);
			$this->CI->email->clear(true);
			$this->CI->email->initialize($config);
			$this->CI->email->from($frommail, $this->siteTitle);
			$this->CI->email->to($to_email);
			$this->CI->email->subject($subject);
			$this->CI->email->message($body);

			return (bool) @$this->CI->email->send();
		}

		/**
		 * Signed verification URL (valid for $ttl_hours). Used when admin creates accounts.
		 */
		public function email_verify_link($user_id, $user_type, $ttl_hours = 168)
		{
			$token = $this->email_verify_token_create($user_id, $user_type, $ttl_hours);
			if ($token === '') {
				return base_url('login');
			}
			return site_url('verify-account/' . $token);
		}

		public function email_verify_token_create($user_id, $user_type, $ttl_hours = 168)
		{
			$user_id = (int) $user_id;
			$user_type = strtolower(trim((string) $user_type));
			if ($user_id < 1 || !in_array($user_type, array('student', 'teacher', 'institute'), true)) {
				return '';
			}
			$exp = time() + ((int) $ttl_hours * 3600);
			$payload = $user_id . '|' . $user_type . '|' . $exp;
			$key = (string) $this->CI->config->item('encryption_key');
			$sig = hash_hmac('sha256', $payload, $key);
			$raw = $payload . '|' . $sig;
			return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
		}

		public function email_verify_token_parse($token)
		{
			$token = trim((string) $token);
			if ($token === '') {
				return false;
			}
			$pad = strlen($token) % 4;
			if ($pad > 0) {
				$token .= str_repeat('=', 4 - $pad);
			}
			$raw = base64_decode(strtr($token, '-_', '+/'), true);
			if ($raw === false || $raw === '') {
				return false;
			}
			$parts = explode('|', $raw);
			if (count($parts) !== 4) {
				return false;
			}
			list($user_id, $user_type, $exp, $sig) = $parts;
			$payload = $user_id . '|' . $user_type . '|' . $exp;
			$key = (string) $this->CI->config->item('encryption_key');
			$expected = hash_hmac('sha256', $payload, $key);
			if (!hash_equals($expected, (string) $sig)) {
				return false;
			}
			if ((int) $exp < time()) {
				return false;
			}
			$user_type = strtolower(trim((string) $user_type));
			if (!in_array($user_type, array('student', 'teacher', 'institute'), true)) {
				return false;
			}
			return array(
				'user_id' => (int) $user_id,
				'user_type' => $user_type,
			);
		}

		/**
		 * Verify email from link token; sets is_verified and sends account_verified email.
		 *
		 * @return array{status:bool,msg:string,already?:bool}
		 */
		public function email_verify_account($token)
		{
			$parsed = $this->email_verify_token_parse($token);
			if ($parsed === false) {
				return array('status' => false, 'msg' => 'Invalid or expired verification link.');
			}

			$user_id = $parsed['user_id'];
			$user_type = $parsed['user_type'];
			$table = ($user_type === 'student') ? 'students' : 'users';

			if (!$this->CI->db->field_exists('is_verified', $table)) {
				return array('status' => false, 'msg' => 'Email verification is not available. Please contact support.');
			}

			if ($user_type === 'student') {
				$rows = $this->CI->db_model->select_data(
					'id,name,email,is_verified',
					'students use index (id)',
					array('id' => $user_id),
					1
				);
			} else {
				$rows = $this->CI->db_model->select_data(
					'id,name,email,is_verified,role,user_type',
					'users use index (id)',
					array('id' => $user_id),
					1
				);
				if (!empty($rows[0])) {
					$role = isset($rows[0]['role']) ? (int) $rows[0]['role'] : 0;
					$ut = isset($rows[0]['user_type']) ? strtolower((string) $rows[0]['user_type']) : '';
					if ($user_type === 'teacher' && $role !== 3 && $ut !== 'teacher') {
						$rows = array();
					}
					if ($user_type === 'institute' && $role !== 4 && $ut !== 'institute') {
						$rows = array();
					}
				}
			}
			$row = !empty($rows[0]) ? $rows[0] : null;

			if (empty($row) || empty($row['id'])) {
				return array('status' => false, 'msg' => 'Account not found.');
			}

			if (!empty($row['is_verified']) && (int) $row['is_verified'] === 1) {
				return array(
					'status' => true,
					'msg' => 'Your account is already verified.',
					'already' => true,
				);
			}

			$this->CI->db_model->update_data($table, array('is_verified' => 1), array('id' => $user_id));

			$this->send_email(array(
				'purpose' => 'account_verified',
				'user_id' => $user_id,
				'user_type' => $user_type,
				'to_email' => isset($row['email']) ? $row['email'] : '',
				'dynamic_var' => array(
					'name' => isset($row['name']) ? $row['name'] : '',
					'link' => base_url('login'),
				),
			));

			return array(
				'status' => true,
				'msg' => 'Your email has been verified successfully. You can now log in.',
			);
		}
	}
?>