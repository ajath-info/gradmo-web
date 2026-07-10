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
		 * $arr['purpose']     — templates.purpose (required), e.g. register, forgot_password, reset_password, password_changed
		 * $arr['purpose_fallbacks'] — optional extra purpose keys if primary template is missing
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
			$fallback_purposes = array();
			if (!empty($arr['purpose_fallbacks']) && is_array($arr['purpose_fallbacks'])) {
				foreach ($arr['purpose_fallbacks'] as $fp) {
					$fp = trim((string) $fp);
					if ($fp !== '' && $fp !== $purpose) {
						$fallback_purposes[] = $fp;
					}
				}
			}
			// Allow a caller to pass an already-fetched template row (single-query optimisation) so we
			// don't hit the templates table again; otherwise resolve it here as usual.
			if (!empty($arr['template_row']) && is_array($arr['template_row'])) {
				$row = $arr['template_row'];
			} else {
				$row = $this->email_find_template($purpose, $template_for, $fallback_purposes);
			}
			if (empty($row)) {
				return array('status' => false, 'msg' => 'Email template not found or inactive: ' . $purpose);
			}

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
			if (!isset($arr['dynamic_var']) || !is_array($arr['dynamic_var'])) {
				$arr['dynamic_var'] = array();
			}
			$arr['dynamic_var']['CURRENT_YEAR'] = date('Y');
			if (!empty($userMeta)) {
				$vars = array_merge($vars, $userMeta);
			}
			if (!empty($arr['dynamic_var']) && is_array($arr['dynamic_var'])) {
				foreach ($arr['dynamic_var'] as $k => $v) {
					$vars[$k] = is_scalar($v) || $v === null ? (string) $v : '';
				}
			}
			// Fall back to any name-ish variable the caller supplied so {{NAME}} always resolves
			// (callers often pass STUDENT_NAME / TEACHER_NAME / INSTITUTION_ADMIN_NAME instead of name).
			if (empty($vars['name'])) {
				foreach (array('NAME', 'STUDENT_NAME', 'TEACHER_NAME', 'INSTITUTION_ADMIN_NAME', 'USER_NAME', 'user_name') as $nameKey) {
					if (!empty($vars[$nameKey])) {
						$vars['name'] = $vars[$nameKey];
						break;
					}
				}
			}
			if (!empty($vars['name'])) {
				$vars['NAME'] = $vars['name'];
				$vars['USER_NAME'] = $vars['name'];
				if (empty($vars['STUDENT_NAME'])) { $vars['STUDENT_NAME'] = $vars['name']; }
			}
			$vars['SITE_NAME'] = isset($vars['site_name']) ? $vars['site_name'] : $this->siteTitle;
			$vars['LOGIN_LINK'] = isset($vars['link']) ? $vars['link'] : base_url('login');
			$vars['SUPPORT_EMAIL'] = isset($vars['support_email']) ? $vars['support_email'] : $this->siteOwnerEmail;

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

			// Optional caller-supplied HTML appended to the rendered template (e.g. an admin note).
			if (!empty($arr['append_html'])) {
				$body .= (string) $arr['append_html'];
			}

			$sent = $this->email_dispatch($to_email, $subject, $body);
			return array(
				'status' => $sent ? true : false,
				'msg' => $sent ? 'Email sent' : 'Failed to send email',
				'to' => $to_email,
				'subject' => $subject,
			);
		}

		/**
		 * Resolve an active templates row: exact purpose, then LIKE %purpose%, then optional fallbacks.
		 *
		 * @param string $purpose
		 * @param string $template_for
		 * @param array  $fallback_purposes
		 * @return array|null
		 */
		private function email_find_template($purpose, $template_for = 'email', array $fallback_purposes = array())
		{
			$purpose = trim((string) $purpose);
			$template_for = trim((string) $template_for);
			if ($template_for === '') {
				$template_for = 'email';
			}
			if ($purpose === '') {
				return null;
			}

			$candidates = array($purpose);
			foreach ($fallback_purposes as $fp) {
				$fp = trim((string) $fp);
				if ($fp !== '' && !in_array($fp, $candidates, true)) {
					$candidates[] = $fp;
				}
			}

			foreach ($candidates as $candidate) {
				$row = $this->email_find_template_once($candidate, $template_for, true);
				if (!empty($row)) {
					return $row;
				}
				$row = $this->email_find_template_once($candidate, $template_for, false);
				if (!empty($row)) {
					return $row;
				}
			}

			if ($template_for !== 'email') {
				return $this->email_find_template($purpose, 'email', $fallback_purposes);
			}

			return null;
		}

		/**
		 * @param string $purpose
		 * @param string $template_for
		 * @param bool   $exact
		 * @return array|null
		 */
		private function email_find_template_once($purpose, $template_for, $exact = true)
		{
			if ($exact) {
				$rows = $this->CI->db_model->select_data(
					'*',
					'templates',
					array('purpose' => $purpose, 'template_for' => $template_for, 'status' => '1'),
					1,
					array('id', 'desc')
				);
				return !empty($rows[0]) ? $rows[0] : null;
			}

			$this->CI->db->from('templates');
			$this->CI->db->where('status', '1');
			$this->CI->db->where('template_for', $template_for);
			$this->CI->db->like('purpose', $purpose, 'both');
			$this->CI->db->order_by('id', 'desc');
			$this->CI->db->limit(1);
			$query = $this->CI->db->get();
			if ($query->num_rows() > 0) {
				return $query->row_array();
			}

			return null;
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
				$k = preg_quote((string) $key, '/');
				// Whitespace- and case-tolerant: matches {{KEY}}, {{ KEY }}, {KEY}, { KEY } etc.
				// so a template author's stray spaces inside the braces don't leave the tag unreplaced.
				$pattern = '/\{\{\s*' . $k . '\s*\}\}|\{\s*' . $k . '\s*\}/i';
				$text = preg_replace_callback($pattern, function () use ($safe) {
					return $safe;
				}, $text);
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
				// Reuse one SMTP connection across sends. Without this, CI opens+QUITs a new
				// connection per email and Gmail drops it (errno=32 "Broken pipe") in bulk loops.
				'smtp_keepalive' => true,
				'smtp_timeout' => 30,
				// Do NOT word-wrap: long URLs (e.g. reset links) must not get a space/line-break
				// inserted, which would corrupt the token when clicked.
				'wordwrap' => false,
			);
			$this->CI->email->clear(true);
			$this->CI->email->initialize($config);
			$this->CI->email->from($frommail, $this->siteTitle);
			$this->CI->email->to($to_email);
			$this->CI->email->reply_to($frommail, $this->siteTitle);
			$this->CI->email->subject($subject);
			$this->CI->email->message($body);
			// Plain-text alternative for the HTML body — HTML-only mail scores higher as spam.
			$alt = trim(preg_replace('/\s+/', ' ', strip_tags(str_ireplace(array('<br>', '<br/>', '<br />', '</p>', '</div>', '</tr>'), "\n", $body))));
			if ($alt !== '') {
				$this->CI->email->set_alt_message($alt);
			}

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
			// Email clients may wrap long links and inject spaces/newlines (often arriving as %20)
			// into the token. Decode percent-encoding first, then strip ALL whitespace.
			// base64url never contains whitespace, so this is safe.
			$token = preg_replace('/\s+/', '', rawurldecode((string) $token));
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
	



public function email_already_sent(
    $purpose,
    $user_id,
    $user_type,
    $batch_id,
    $period
)
{
    return $this->CI->db
        ->where('purpose', $purpose)
        ->where('user_id', $user_id)
        ->where('user_type', $user_type)
        ->where('batch_id', $batch_id)
        ->where('period_key', $period)
        ->count_all_results('email_notification_logs') > 0;
}

		public function email_log_sent( $purpose, $user_id, $user_type, $batch_id, $period, $email) {

			return $this->CI->db->insert(
				'email_notification_logs',
				array(
					'purpose'    => $purpose,
					'user_id'    => $user_id,
					'user_type'  => $user_type,
					'batch_id'   => $batch_id,
					'period_key' => $period,
					'email'      => $email,
					'sent_at'    => date('Y-m-d H:i:s')
				)
			);
		}

		/**
		 * Application-fee rules for a STUDENT paying for a batch. Returns a breakdown in RUPEES:
		 *   - first_time     (no prior enrollment anywhere)      => flat 499 (first batch fee free)
		 *   - same_institute (already enrolled at this institute) => 99 + batch fee
		 *   - new_institute  (enrolled before, but not here)     => 499 + batch fee
		 * "institute" = batches.institute_id; "already enrolled" = any student_batchs row at that
		 * institute (paid online, admin-added, or free) for a batch OTHER than the one being bought.
		 *
		 * @return array{scenario:string,application_fee:float,batch_fee:float,total:float,institute_id:int}|null
		 */
		public function compute_application_fee($student_id, $batch_id)
		{
			$student_id = (int) $student_id;
			$batch_id = (int) $batch_id;
			if ($student_id < 1 || $batch_id < 1) {
				return null;
			}
			$batch = $this->CI->db_model->select_data('id,institute_id,batch_price,batch_offer_price', 'batches use index (id)', array('id' => $batch_id), 1);
			if (empty($batch[0])) {
				return null;
			}
			$institute_id = isset($batch[0]['institute_id']) ? (int) $batch[0]['institute_id'] : 0;
			$offer = isset($batch[0]['batch_offer_price']) ? (float) $batch[0]['batch_offer_price'] : 0.0;
			$price = isset($batch[0]['batch_price']) ? (float) $batch[0]['batch_price'] : 0.0;
			// Batch fee = batch_price - batch_offer_price (offer price is a discount off the regular price).
			$batch_fee = max(0.0, $price - $offer);

			// Free institute (users.paid = '0'): batch fee AND application/subscription fee are 0.
			if ($institute_id > 0 && $this->CI->db->field_exists('paid', 'users')) {
				$inst = $this->CI->db_model->select_data('paid', 'users use index (id)', array('id' => $institute_id), 1);
				if (!empty($inst) && isset($inst[0]['paid']) && (string) $inst[0]['paid'] === '0') {
					return array('scenario' => 'free_institute', 'application_fee' => 0.0, 'batch_fee' => 0.0, 'total' => 0.0, 'institute_id' => $institute_id);
				}
			}

			// Fee amounts (rupees). Change here if they ever differ.
			$FEE_FIRST = 499.0;          // first ever enrollment — flat, first batch fee free
			$FEE_NEW_INSTITUTE = 499.0;  // first batch at an institute the student hasn't joined
			$FEE_SAME_INSTITUTE = 99.0;  // additional batch at an institute already joined

			// Institutes the student has already ENROLLED at (any student_batchs row), excluding the
			// batch currently being purchased.
			$this->CI->db->reset_query();
			$this->CI->db->select('b.institute_id AS institute_id', false)
				->from('student_batchs sb')
				->join('batches b', 'b.id = sb.batch_id', 'left')
				->where('sb.student_id', $student_id)
				->where('sb.batch_id !=', $batch_id);
			$rows = $this->CI->db->get()->result_array();

			$has_prior = !empty($rows);
			$joined_institutes = array();
			foreach ((array) $rows as $r) {
				$joined_institutes[(int) $r['institute_id']] = true;
			}

			if (!$has_prior) {
				return array('scenario' => 'first_time', 'application_fee' => $FEE_FIRST, 'batch_fee' => 0.0, 'total' => $FEE_FIRST, 'institute_id' => $institute_id);
			}
			if ($institute_id > 0 && isset($joined_institutes[$institute_id])) {
				return array('scenario' => 'same_institute', 'application_fee' => $FEE_SAME_INSTITUTE, 'batch_fee' => $batch_fee, 'total' => $FEE_SAME_INSTITUTE + $batch_fee, 'institute_id' => $institute_id);
			}
			return array('scenario' => 'new_institute', 'application_fee' => $FEE_NEW_INSTITUTE, 'batch_fee' => $batch_fee, 'total' => $FEE_NEW_INSTITUTE + $batch_fee, 'institute_id' => $institute_id);
		}

	


		/**
		 * Send a transactional SMS via MSG91. Credentials come from general_settings:
		 *   msg91_authkey      — MSG91 auth key (required)
		 *   msg91_sender       — 6-char sender / DLT header (required for India)
		 *   msg91_route        — route id (default 4 = transactional)
		 *   msg91_country      — country code (default 91)
		 *   msg91_template_id  — optional DLT Flow template id; if set, the Flow API is used and the
		 *                        whole message is passed as the single variable {#var#}/var1.
		 *
		 * @param string $mobile   recipient number (10-digit, or with country code)
		 * @param string $message
		 * @return array{ok:bool,http_code:int,response:string,error:string}
		 */
		public function send_sms($mobile, $message)
		{
			$mobile = preg_replace('/\D+/', '', (string) $mobile);
			$message = trim((string) $message);
			$authkey = trim((string) $this->general_settings('msg91_authkey'));
			$sender = trim((string) $this->general_settings('msg91_sender'));
			$route = trim((string) $this->general_settings('msg91_route'));
			$country = trim((string) $this->general_settings('msg91_country'));
			$template_id = trim((string) $this->general_settings('msg91_template_id'));
			if ($route === '') { $route = '4'; }
			if ($country === '') { $country = '91'; }

			if ($authkey === '') {
				return array('ok' => false, 'http_code' => 0, 'response' => '', 'error' => 'MSG91 auth key is not configured.');
			}
			if ($mobile === '' || $message === '') {
				return array('ok' => false, 'http_code' => 0, 'response' => '', 'error' => 'Mobile and message are required.');
			}
			// Normalise to country-code + 10 digits (MSG91 expects e.g. 9198XXXXXXXX).
			if (strlen($mobile) === 10) {
				$mobile = $country . $mobile;
			}

			if ($template_id !== '') {
				// MSG91 v5 Flow API (DLT). Template must expose a single variable named "var1".
				$url = 'https://control.msg91.com/api/v5/flow/';
				$payload = json_encode(array(
					'template_id' => $template_id,
					'sender' => $sender,
					'short_url' => '0',
					'recipients' => array(array('mobiles' => $mobile, 'var1' => $message)),
				), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				$headers = array('authkey: ' . $authkey, 'Content-Type: application/json', 'accept: application/json');
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			} else {
				// Legacy text API (no DLT template); works for non-DLT / international accounts.
				$url = 'https://api.msg91.com/api/sendhttp.php?' . http_build_query(array(
					'authkey' => $authkey,
					'mobiles' => $mobile,
					'message' => $message,
					'sender' => $sender,
					'route' => $route,
					'country' => $country,
				));
				$headers = array();
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
			}

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			if (!empty($headers)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}
			$result = curl_exec($ch);
			$err = $result === false ? curl_error($ch) : '';
			$http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			$ok = $result !== false && $http_code >= 200 && $http_code < 300 && stripos((string) $result, 'error') === false;
			return array('ok' => $ok, 'http_code' => $http_code, 'response' => (string) $result, 'error' => $err);
		}

		/**
		 * Low-level FCM sender using the HTTP v1 API (the legacy /fcm/send API was shut down
		 * by Google in June 2024). Auth uses a Firebase service account: a short-lived OAuth2
		 * access token is minted from a JWT signed (RS256) with the service account private key.
		 *
		 * The service account JSON is read from general_settings.firebase_service_account_path
		 * when set, otherwise from APPPATH/config/firebase-service-account.json.
		 *
		 * v1 sends to a single token per request, so a token list is looped and results aggregated.
		 *
		 * @param string|array $tokens one device token or a list of tokens
		 * @param string       $title
		 * @param string       $message
		 * @param array        $data    extra data payload (values are coerced to strings; v1 requires string data)
		 * @param string|null  $image   notification image URL. null => use the site-logo default (applied to
		 *                               every push); '' => no image; any string => that image URL.
		 * @return array{ok:bool,http_code:int,response:string,request:string,error:string,sent:int,failed:int}
		 */
		public function sendPushNotification($tokens, $title, $message, $data = array(), $image = null)
		{
			$tokens = is_array($tokens) ? array_values(array_filter(array_map('strval', $tokens))) : array_values(array_filter(array((string) $tokens)));
			if (empty($tokens)) {
				return array('ok' => false, 'http_code' => 0, 'response' => '', 'request' => '', 'error' => 'No device tokens.', 'sent' => 0, 'failed' => 0);
			}

			$sa = $this->fcm_service_account();
			if (empty($sa['project_id']) || empty($sa['client_email']) || empty($sa['private_key'])) {
				return array('ok' => false, 'http_code' => 0, 'response' => '', 'request' => '', 'error' => 'Firebase service account is not configured.', 'sent' => 0, 'failed' => 0);
			}

			$access = $this->fcm_v1_access_token($sa);
			if (empty($access['token'])) {
				return array('ok' => false, 'http_code' => 0, 'response' => '', 'request' => '', 'error' => 'FCM auth failed: ' . $access['error'], 'sent' => 0, 'failed' => 0);
			}

			// v1 requires data to be a flat map of strings.
			$flat = array();
			foreach ((array) $data as $k => $v) {
				$flat[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			}

			// null => site-logo default for every push; '' => no image; string => that URL.
			$image = ($image === null) ? $this->default_push_image_url() : trim((string) $image);

			$url = 'https://fcm.googleapis.com/v1/projects/' . $sa['project_id'] . '/messages:send';
			$sent = 0; $failed = 0;
			$last_code = 0; $last_resp = ''; $last_req = ''; $last_err = '';
			$invalid_tokens = array();

			foreach ($tokens as $tok) {
				$notification = array('title' => (string) $title, 'body' => (string) $message);
				if ($image !== '') {
					$notification['image'] = $image;
				}
				$android = array('priority' => 'high');
				if ($image !== '') {
					$android['notification'] = array('image' => $image);
				}
				// iOS/APNs: FCM does not auto-fill sound/badge, and the image only shows when
				// mutable-content=1 + fcm_options.image are set (needs a Notification Service Extension app-side).
				$apns = array(
					'headers' => array('apns-priority' => '10'),
					'payload' => array('aps' => array('sound' => 'default', 'mutable-content' => 1)),
				);
				if ($image !== '') {
					$apns['fcm_options'] = array('image' => $image);
				}
				$payload = array('message' => array(
					'token'        => $tok,
					'notification' => $notification,
					'data'         => $flat,
					'android'      => $android,
					'apns'         => $apns,
				));
				$body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer ' . $access['token'],
					'Content-Type: application/json',
				));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

				$result = curl_exec($ch);
				$err = $result === false ? curl_error($ch) : '';
				$http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);

				$last_code = $http_code; $last_resp = (string) $result; $last_req = $body; $last_err = $err;
				if ($result !== false && $http_code >= 200 && $http_code < 300) {
					$sent++;
				} else {
					$failed++;
					// A dead token (app uninstalled / token rotated): FCM returns 404 UNREGISTERED
					// or 400 INVALID_ARGUMENT. Flag it so the caller can purge it from the DB.
					if ($this->fcm_response_token_is_dead($http_code, (string) $result)) {
						$invalid_tokens[] = $tok;
						$this->clear_dead_device_token($tok);
					}
				}
			}

			return array(
				'ok'             => $sent > 0,
				'http_code'      => $last_code,
				'response'       => $last_resp,
				'request'        => $last_req,
				'error'          => $last_err,
				'sent'           => $sent,
				'failed'         => $failed,
				'invalid_tokens' => $invalid_tokens,
			);
		}

		/**
		 * True when an FCM v1 error means the token is permanently invalid and should be removed.
		 */
		private function fcm_response_token_is_dead($http_code, $response)
		{
			if ((int) $http_code === 404) {
				return true; // NOT_FOUND / UNREGISTERED
			}
			$decoded = json_decode((string) $response, true);
			$status = isset($decoded['error']['status']) ? (string) $decoded['error']['status'] : '';
			if ($status === 'NOT_FOUND' || $status === 'UNREGISTERED') {
				return true;
			}
			if (!empty($decoded['error']['details']) && is_array($decoded['error']['details'])) {
				foreach ($decoded['error']['details'] as $d) {
					$code = isset($d['errorCode']) ? (string) $d['errorCode'] : '';
					if ($code === 'UNREGISTERED' || $code === 'INVALID_ARGUMENT') {
						return true;
					}
				}
			}
			return false;
		}

		/**
		 * Blank out a dead FCM token wherever it is stored (students + users, token/device_token columns),
		 * so future sends skip it. Best-effort; ignores tables/columns that do not exist.
		 */
		private function clear_dead_device_token($token)
		{
			$token = trim((string) $token);
			if ($token === '') {
				return;
			}
			$targets = array(
				'students' => array('token', 'device_token'),
				'users'    => array('token', 'device_token'),
			);
			foreach ($targets as $table => $cols) {
				if (!$this->CI->db->table_exists($table)) {
					continue;
				}
				foreach ($cols as $col) {
					if ($this->CI->db->field_exists($col, $table)) {
						$this->CI->db->where($col, $token)->update($table, array($col => ''));
					}
				}
			}
		}

		/**
		 * Load and cache (per request) the Firebase service account credentials. Resolution order:
		 *   1. general_settings.firebase_service_account_json  (full JSON stored in the DB — preferred)
		 *   2. general_settings.firebase_service_account_path   (absolute path to a JSON file)
		 *   3. APPPATH/config/firebase-service-account.json     (bundled fallback)
		 *
		 * @return array<string,mixed>
		 */
		private function fcm_service_account()
		{
			static $sa = null;
			if ($sa !== null) {
				return $sa;
			}
			$sa = array();

			// 1) Full JSON stored in general_settings (no file on disk).
			$json = trim((string) $this->general_settings('firebase_service_account_json'));
			if ($json !== '') {
				$decoded = json_decode($json, true);
				if (is_array($decoded)) {
					$sa = $decoded;
					return $sa;
				}
			}

			// 2) Configurable path, then 3) bundled fallback file.
			$path = trim((string) $this->general_settings('firebase_service_account_path'));
			if ($path === '' || !is_file($path)) {
				$path = APPPATH . 'config/firebase-service-account.json';
			}
			if (is_file($path)) {
				$decoded = json_decode((string) file_get_contents($path), true);
				if (is_array($decoded)) {
					$sa = $decoded;
				}
			}
			return $sa;
		}

		/**
		 * Mint (and file-cache ~1h) a Google OAuth2 access token for FCM using the service account.
		 *
		 * @param array $sa service account fields
		 * @return array{token:string,error:string}
		 */
		private function fcm_v1_access_token(array $sa)
		{
			$aud = !empty($sa['token_uri']) ? $sa['token_uri'] : 'https://oauth2.googleapis.com/token';
			$cache_file = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'fcm_v1_token_' . md5((string) $sa['client_email']) . '.json';

			if (is_file($cache_file)) {
				$c = json_decode((string) file_get_contents($cache_file), true);
				if (is_array($c) && !empty($c['token']) && isset($c['exp']) && (int) $c['exp'] > time() + 60) {
					return array('token' => (string) $c['token'], 'error' => '');
				}
			}

			$now = time();
			$header = array('alg' => 'RS256', 'typ' => 'JWT');
			$claim = array(
				'iss'   => $sa['client_email'],
				'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
				'aud'   => $aud,
				'iat'   => $now,
				'exp'   => $now + 3600,
			);
			$input = $this->b64url(json_encode($header)) . '.' . $this->b64url(json_encode($claim));
			$signature = '';
			if (!openssl_sign($input, $signature, $sa['private_key'], 'sha256')) {
				return array('token' => '', 'error' => 'openssl_sign failed (check private_key).');
			}
			$jwt = $input . '.' . $this->b64url($signature);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $aud);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
				'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
				'assertion'  => $jwt,
			)));
			$resp = curl_exec($ch);
			$err = $resp === false ? curl_error($ch) : '';
			curl_close($ch);

			$data = json_decode((string) $resp, true);
			if (is_array($data) && !empty($data['access_token'])) {
				$exp = $now + (isset($data['expires_in']) ? (int) $data['expires_in'] : 3600);
				@file_put_contents($cache_file, json_encode(array('token' => $data['access_token'], 'exp' => $exp)));
				return array('token' => (string) $data['access_token'], 'error' => '');
			}
			return array('token' => '', 'error' => $err !== '' ? $err : (is_array($data) ? json_encode($data) : 'Unknown token error.'));
		}

		private function b64url($data)
		{
			return rtrim(strtr(base64_encode((string) $data), '+/', '-_'), '=');
		}

		/**
		 * Default notification image shown on every push (unless a caller overrides it).
		 * Prefers general_settings.push_notification_image, then the site logo. Cached per request.
		 *
		 * @return string absolute image URL, or '' when none is configured
		 */
		private function default_push_image_url()
		{
			static $img = null;
			if ($img !== null) {
				return $img;
			}
			$img = '';
			$override = trim((string) $this->general_settings('push_notification_image'));
			if ($override !== '') {
				$img = (stripos($override, 'http') === 0) ? $override : base_url($override);
				return $img;
			}
			$site = $this->CI->db_model->select_data('site_logo', 'site_details', array('id' => 1), 1);
			if (!empty($site[0]['site_logo'])) {
				$img = base_url('uploads/site_data/' . $site[0]['site_logo']);
			}
			return $img;
		}



	}


	


?>