<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Home extends MY_Controller {
    function __construct()
    {
        //error_reporting(0);
        ini_set('display_errors', 1);
        parent::__construct();
		
			// check select language
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
	
	function general_setting($key_text=''){
	    $data=array();
	    $data['languageName']=$this->general_settings('language_name');
	    $data['currencyCode']=$this->general_settings('currency_code');
	    $data['currencyDecimalCode']=$this->general_settings('currency_decimal_code');
	    $data['paymentType']=$this->general_settings('payment_type');
	    $data['razorpayKeyId']=$this->general_settings('razorpay_key_id');
	    $data['razorpaySecretKey']=$this->general_settings('razorpay_secret_key');
	    $data['paypalClientId']=$this->general_settings('paypal_client_id');
	    $data['paypalSecretKey']=$this->general_settings('paypal_secret_key');
		$arr = array(
                    'status'=>'true',
                    'data'=>$data
                        );
        echo json_encode($arr);
	}

	private function is_valid_password($plain_password, $stored_password)
	{
		$plain_password = (string) $plain_password;
		$stored_password = trim((string) $stored_password);
		if ($stored_password === '') {
			return false;
		}

		// Preferred: modern hash (bcrypt/argon).
		if (password_verify($plain_password, $stored_password)) {
			return true;
		}

		// Backward compatibility: legacy MD5/plain text records.
		$md5_plain = md5($plain_password);
		if (strcasecmp($md5_plain, $stored_password) === 0 || $plain_password === $stored_password) {
			return true;
		}

		// Some admin flows use SHA1.
		if (strlen($stored_password) === 40 && ctype_xdigit($stored_password)) {
			if (strcasecmp(sha1($plain_password), $stored_password) === 0) {
				return true;
			}
		}

		return false;
	}

	/** Read password column from DB row regardless of key casing (PASSWORD vs password). */
	private function row_password_value(array $row)
	{
		foreach ($row as $key => $val) {
			if (strtolower((string) $key) === 'password') {
				return (string) $val;
			}
		}
		return '';
	}

	/**
	 * Nominatim HTTP GET (HTTPS). cURL first — more reliable on Windows/XAMPP than file_get_contents.
	 * User-Agent must identify the app (https://operations.osmfoundation.org/policies/nominatim/).
	 *
	 * @return string Raw JSON body or empty string on failure
	 */
	private function nominatim_http_get($search_query)
	{
		$search_query = trim((string) $search_query);
		if ($search_query === '') {
			return '';
		}
		$base = rtrim((string) base_url(), '/');
		if ($base === '') {
			$base = 'https://localhost';
		}
		$userAgent = 'GradmoEducation/1.0 (institute profile; +' . $base . ')';

		$url = 'https://nominatim.openstreetmap.org/search?' . http_build_query(array(
			'q' => $search_query,
			'format' => 'json',
			'limit' => 1,
			'addressdetails' => 0,
		), '', '&', PHP_QUERY_RFC3986);

		if (function_exists('curl_init')) {
			$headers = array(
				'User-Agent: ' . $userAgent,
				'Accept: application/json',
				'Accept-Language: en',
			);
			$do = function ($verifySsl) use ($url, $headers) {
				$ch = curl_init($url);
				curl_setopt_array($ch, array(
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_TIMEOUT => 20,
					CURLOPT_CONNECTTIMEOUT => 10,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTPHEADER => $headers,
					CURLOPT_SSL_VERIFYPEER => $verifySsl,
					CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
				));
				$body = curl_exec($ch);
				$errno = curl_errno($ch);
				curl_close($ch);
				return array($body === false ? '' : (string) $body, $errno);
			};

			list($raw, $errno) = $do(true);
			if ($raw === '' && in_array($errno, array(60, 77, 35, 51, 58, 59), true)) {
				list($raw,) = $do(false);
			}
			if ($raw !== '') {
				return $raw;
			}
		}

		if (ini_get('allow_url_fopen')) {
			$ctx = stream_context_create(array(
				'http' => array(
					'method' => 'GET',
					'timeout' => 20,
					'header' => 'User-Agent: ' . $userAgent . "\r\nAccept: application/json\r\nAccept-Language: en\r\n",
					'ignore_errors' => true,
				),
				'ssl' => array(
					'verify_peer' => true,
					'verify_peer_name' => true,
				),
			));
			$raw = @file_get_contents($url, false, $ctx);
			if ($raw !== false && $raw !== '') {
				return (string) $raw;
			}
		}

		return '';
	}

	/**
	 * Geocode institute address for profile update (OpenStreetMap Nominatim; no API key).
	 *
	 * @return array{lat: string, long: string}|null
	 */
	private function geocode_institute_address($address, $city, $state, $country, $pincode)
	{
		$address = trim((string) $address);
		$city = trim((string) $city);
		$state = trim((string) $state);
		$country = trim((string) $country);
		$pincode = trim((string) $pincode);

		$attempts = array();
		$full = array_filter(array($address, $city, $state, $pincode, $country), function ($p) {
			return $p !== '';
		});
		if (!empty($full)) {
			$attempts[] = implode(', ', $full);
		}
		$attempts[] = trim(implode(', ', array_filter(array($city, $state, $pincode, $country))));
		$attempts[] = trim(implode(', ', array_filter(array($city, $state, $country))));
		$attempts[] = trim(implode(', ', array_filter(array($pincode, $country))));
		$attempts = array_values(array_unique(array_filter(array_map('trim', $attempts))));

		foreach ($attempts as $i => $query) {
			if ($query === '') {
				continue;
			}
			if ($i > 0) {
				usleep(1100000);
			}
			$raw = $this->nominatim_http_get($query);
			if ($raw === '') {
				continue;
			}
			$decoded = json_decode($raw, true);
			if (!is_array($decoded) || !isset($decoded[0]['lat'], $decoded[0]['lon'])) {
				continue;
			}
			$lat = (float) $decoded[0]['lat'];
			$lon = (float) $decoded[0]['lon'];
			if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
				continue;
			}
			return array('lat' => (string) $lat, 'long' => (string) $lon);
		}

		return null;
	}

	/**
	 * multipart/form-data often uses mixed-case keys (City, Address). PHP keys are case-sensitive.
	 * Also strips wrapping quotes from tools like curl --form 'name="value"'.
	 *
	 * @return array<string, mixed>
	 */
	private function normalize_multi_user_registration_data(array $data)
	{
		$out = array();
		foreach ($data as $k => $v) {
			if (!is_string($k) && !is_int($k)) {
				continue;
			}
			$key = strtolower(trim((string) $k));
			if ($key === '') {
				continue;
			}
			if (is_string($v)) {
				$v = trim($v);
				$v = trim($v, "\"' \t\n\r\0\x0B");
			}
			$out[$key] = $v;
		}
		return $out;
	}

	/**
	 * Email and mobile must be unique across users (teacher/institute) and students.
	 *
	 * @return string|null Error message or null if OK
	 */
	private function registration_duplicate_message($email, $mobile)
	{
		$email = trim((string) $email);
		$mobile = trim((string) $mobile);
		if ($email === '' || $mobile === '') {
			return null;
		}
		if ($this->db->get_where('users', array('email' => $email), 1)->row_array()) {
			return 'Email already exists';
		}
		if ($this->db->get_where('users', array('mobile' => $mobile), 1)->row_array()) {
			return 'Mobile number already exists';
		}
		if ($this->db->get_where('students', array('email' => $email), 1)->row_array()) {
			return 'Email already exists';
		}
		$this->db->group_start();
		$this->db->where('mobile', $mobile);
		$this->db->or_where('contact_no', $mobile);
		$this->db->group_end();
		if ($this->db->limit(1)->get('students')->row_array()) {
			return 'Mobile number already exists';
		}
		return null;
	}
	
    function chekLogin(){
        $data = $_REQUEST;
        if(isset($data['student_id']) && isset($data['token'])){
            $student_id = trim($data['student_id']);
            $token  =  trim($data['token']);
            // $batch_id  =  trim($data['batch_id']);
            $studentDetails = $this->db_model->select_data('login_status,status,token','students use index (id)',array('id'=>$student_id),1);
            $batch_details = $this->db_model->select_data('id,status','batches use index (id)',array('id'=>$batch_id),1);
            // if(($studentDetails[0]['status'] == 1) && ($studentDetails[0]['token'] == $token) && ($batch_details[0]['status'] == 1))
            // {
            if(($studentDetails[0]['status'] == 1) && ($studentDetails[0]['token'] == $token))
            {
                if($studentDetails[0]['login_status'] == 0){
                    $arr = array(
                        'status'=>'false',
                        'msg'=>$this->lang->line('ltr_logout')
                    ); 
                }else{
					$l =$this->general_settings('language_name');
                    $arr = array(
                            'status'=>'true',
                            'msg'=>$this->lang->line('ltr_login_continue'),
							'languageName' => $l
							
                        );
                }
            }else{
                $arr = array(
                        'status'=>'false',
                        'msg'=>$this->lang->line('ltr_logout')
                    ); 
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            ); 
        }
        echo json_encode($arr);
    }

    public function multi_user_login()
	{
	    // Get JSON input
	    $data = json_decode(file_get_contents("php://input"), true);
	    if (empty($data)) {
	        $data = $this->input->post();
	    }
 
	    // ==============================
	    // 			 VALIDATION
	    // ==============================
	    if (
	        empty($data['username']) ||
	        empty($data['password']) ||
	        empty($data['user_type'])
	    ) {
	        echo json_encode([
	            'status' => 'false',
	            'msg' => 'Email, password & user_type required'
	        ]);
	        return;
	    }

	    $email     = trim($data['username']);
	    $password  = trim($data['password']);
	    $user_type = strtolower(trim($data['user_type']));

		$device_id = $data['device_id'] ?? '';
	    $device_token = $data['device_token'] ?? '';
		$device_type = $data['device_type'] ?? '';

	    // ==============================
	    // 			 STUDENT LOGIN
	    // ==============================
	    if ($user_type == 'student') {

	        $student = $this->db_model->select_data('*', 'students', ['email' => $email], 1);

	        if (empty($student)) {
	            echo json_encode(['status' => 'false', 'msg' => 'Student not found']);
	            return;
	        }

	        $student = $student[0];

	        // VERIFY PASSWORD
	        if (!$this->is_valid_password($password, isset($student['password']) ? $student['password'] : '')) {
	            echo json_encode(['status' => 'false', 'msg' => 'Invalid password']);
	            return;
	        }
            if ($student['status'] == 0) {
	            echo json_encode(['status' => 'false', 'msg' => 'Student is not active']);
	            return;
	        }

	        // Issue token first, then store last_login_app from token iat so only this session validates.
	        $tok = $this->mint_access_credentials($student['id'], 'student');
	        $access_token = $tok['access_token'];
	        $now = date('Y-m-d H:i:s', $tok['iat']);
	        $this->db_model->update_data_limit('students', [
	            'login_status' => 1,
	            'last_login_app' => $now,
	            'token' => $device_token,
				'device_id' => $device_id,
				'device_token' => $device_token,
				'device_type' => $device_type,
	        ], ['id' => $student['id']], 1);

	        $response = $this->build_student_login_data_array($student, $device_id, $device_token, $device_type, $now, $access_token);
	    }

	    // ==============================
	    // 	 TEACHER / INSTITUTE LOGIN
	    // ==============================
	    else {

	        $user = $this->db_model->select_data('*', 'users', [
	            'email' => $email,
	            'user_type' => $user_type
	        ], 1);

	        if (empty($user)) {
	            echo json_encode(['status' => 'false', 'msg' => 'User not found']);
	            return;
	        }

	        $user = $user[0];
	        // VERIFY PASSWORD
	        if (!$this->is_valid_password($password, isset($user['password']) ? $user['password'] : '')) {
	            echo json_encode(['status' => 'false', 'msg' => 'Invalid password']);
	            return;
	        }

	        $tok = $this->mint_access_credentials($user['id'], $user['user_type']);
	        $access_token = $tok['access_token'];
	        $now = date('Y-m-d H:i:s', $tok['iat']);
	        $this->db_model->update_data_limit('users', [
				'login_status' => 1,
	            'device_token' => $device_token,
	            'updated_at'   => $now
	        ], ['id' => $user['id']], 1);
			
			if(empty($user['city'])){
				$profile_completed = 0;
			}else{
				$profile_completed = 1;
			}

	        $response = $this->build_non_student_login_data_array($user, $device_id, $device_token, $device_type, $now, $access_token, $profile_completed);
	    }

	    // ==============================
	    // SUCCESS RESPONSE (same envelope as api/user/verify-otp)
	    // ==============================
	    $this->json_login_success_response($response);
	}
   
	function logout(){
        $payload = $this->require_auth_payload();
        if ($payload === false) {
            return;
        }

        // This logout endpoint currently supports student app sessions.
        if ($payload['ut'] !== 'student') {
            $user_id = (int) $payload['uid'];
            $ins = $this->db_model->update_data_limit(
                'users use index (id)',
                array('login_status' => 0, 'token' => ''),
                array('id' => $user_id),
                1
            );
        } else { 
                $student_id = (int) $payload['uid'];
                $ins = $this->db_model->update_data_limit(
                    'students use index (id)',
                    array('login_status' => 0, 'token' => ''),
                    array('id' => $student_id),
                    1
                );
            }
    
        if($ins){
             $arr =	array('status'=>'true','msg'=>$this->lang->line('ltr_logged_out'));
        }else{
             $arr =	array('status'=>'false','msg'=>$this->lang->line('ltr_failed_out'));
        }

        echo json_encode($arr);
    }
	
	public function multi_user_registration()
	{
	    $raw = file_get_contents('php://input');
	    $data = json_decode($raw, true);
	    if (!is_array($data) || empty($data)) {
	        $data = array_merge($this->input->get(), $this->input->post());
	    }
	    $data = $this->normalize_multi_user_registration_data($data);

	    if (
	        empty($data['name']) ||
	        empty($data['email']) ||
	        empty($data['mobile']) ||
	        empty($data['user_type']) ||
	        empty($data['password'])
	    ) {
	        echo json_encode([
	            'status' => 'false',
	            'msg' => 'Missing required parameters'
	        ]);
	        return;
	    }

	    $user_type = strtolower(trim((string) $data['user_type']));

	    if (!in_array($user_type, ['student', 'teacher', 'institute'])) {
	        echo json_encode([
	            'status' => 'false',
	            'msg' => 'Invalid user type'
	        ]);
	        return;
	    }

	    $email = trim((string) $data['email']);
	    $mobile = trim((string) $data['mobile']);
	    $dupMsg = $this->registration_duplicate_message($email, $mobile);
	    if ($dupMsg !== null) {
	        echo json_encode(array(
	            'status' => 'false',
	            'msg' => $dupMsg,
	        ));
	        return;
	    }

	    // ==============================
	    // 		 COMMON DATA
	    // ==============================
	    $name        = trim((string) $data['name']);
	    $password    = trim((string) $data['password']);
	    
	    $device_id   = $data['device_id'] ?? '';
	    $device_token= $data['device_token'] ?? '';
	    $device_type = $data['device_type'] ?? '';
		
	    // Secure password
	    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

	    $this->db->trans_start();

	    $isNewUser = false;
	    $insert_id = 0;
		
		// Generate OTP
	    $otp = rand(1000, 9999);

	    // =====================================================
	    // 		 STUDENT TABLE
	    // =====================================================
				if ($user_type == 'student') {

	            $isNewUser = true;

	            //$batch = $this->db_model->select_data('*', 'batches', ['id' => $batch_id], 1);
	            $admin_id = !empty($batch) ? $batch[0]['admin_id'] : 0;

	            $enrolid = "ENR" . $admin_id . rand(100, 999);

	            $studentData = [
	                'admin_id'        => $admin_id,
	                'name'            => $name,
	                'email'           => $email,
	                'added_by'        => 'student',
	                'status'          => 1,
	                'enrollment_id'   => $enrolid,
	                'password'        => $hashedPassword,
	                'admission_date'  => date('Y-m-d'),
	                'image'           => 'student_img.png',
	                'contact_no'      => $mobile,
					'mobile'      	  => $mobile,
	                'login_status'    => 1,
	                'last_login_app'  => date("Y-m-d H:i:s"),
					'device_id'      => $device_id,
					'device_token'   => $device_token,
					'device_type'    => $device_type,
					'otp'          => $otp,
	                'otp_created_at' => date("Y-m-d H:i:s"),
	            ];

	            $insert_id = $this->db_model->insert_data(
	                'students',
	                $this->security->xss_clean($studentData)
	            );
 
	        $student = $this->db_model->select_data('*', 'students', ['id' => $insert_id], 1)[0];

	        $response = [
	            'userType'     => 'student',
	            'studentId'    => $student['id'],
	            'name'         => $student['name'],
	            'email'        => $student['email'],
	            'mobile'       => $student['contact_no'],
	            'device_id'    => $student['device_id'],
	            'device_token' => $student['device_token'],
	            'device_type'  => $student['device_type']
	        ];
	    }

	    // =====================================================
	    // 		 USERS TABLE (teacher / institute)
	    // =====================================================
	    else {

	        $role = ($user_type == 'teacher') ? 3 : 4;

	            $isNewUser = true;

	            $userData = [
	                'name'         => $name,
	                'email'        => $email,
	                'mobile'       => $mobile,
	                'user_type'    => $user_type,
	                'role'         => $role,
	                'password'     => $hashedPassword,
	                'status'       => 1,
	                'image'        => 'default.png',
	                'device_id'    => $device_id,
	                'device_token' => $device_token,
	                'device_type'  => $device_type,
	                'created_at'   => date('Y-m-d H:i:s'),
					'otp'          => $otp,
	                'otp_created_at' => date("Y-m-d H:i:s"),
	            ];

	            $insert_id = $this->db_model->insert_data(
	                'users',
	                $this->security->xss_clean($userData)
	            );

	        $user = $this->db_model->select_data('*', 'users', ['id' => $insert_id], 1)[0];

	        $response = [
	            'userType' => $user['user_type'],
	            'userId'   => $user['id'],
	            'name'     => $user['name'],
	            'email'    => $user['email'],
	            'mobile'   => $user['mobile'],
	            'image'    => base_url('uploads/users/') . $user['image'],
	            'role'     => $user['role'],
				'device_id'    => $device_id,
	            'device_token' => $device_token,
	            'device_type'  => $device_type
	        ];
	    }

	    // ==============================
	    // 		 FINAL RESPONSE
	    // ==============================
	    if (!$insert_id) {
	        $this->db->trans_rollback();
	        echo json_encode(['status' => 'false', 'msg' => 'Operation failed']);
	        return;
	    }

	    $this->db->trans_complete();

	    echo json_encode([
	        'status' => "true",
	        'msg' => $isNewUser ? "OTP sent successfully. Your OTP is $otp" : "OTP sent successfully. Your OTP is $otp",
	        'data' => $response,
			"otp" => $otp
	    ]);
	}

	function checkLanguage(){
		  
		$language =$this->general_settings('language_name');
		if(!empty($language)){
				$arr = array('status'=>'true', 'msg' =>$this->lang->line('ltr_fetch_successfully'),'languageName'=>$language);
				
			}else{
				$arr = array('status'=>'false', 'msg' => $this->lang->line('ltr_no_record_msg')); 
			}
		echo json_encode($arr);
	}

    function get_batch_fee(){
       
			$data = $_REQUEST;
 			$this->db_model->insert_data('temp_data',array('temp'=>json_encode($data)));
			$search = trim($data['search']);
		    $slider_limit = $data['limit'];
		    
			if(!empty($search)){
			  $like_search = array('batch_name',$search);
			}else{
			    $like_search = '';
			}
	
          if(isset($data['length']) && $data['length']>0){
                if(isset($data['start']) && !empty($data['start'])){
                    $limit = array($data['length'],$data['start']);
                    // $count = $data['start']+1;
                }else{ 
                    $limit = array($data['length'],0);
                    // $count = 1;
                }
            }else{
                $limit = '';
                // $count = 1;
            }
			
            $category = $this->db_model->select_data('id as categoryId,name as categoryName','batch_category use index (id)',array('status'=>1),$limit);
    	     if(!empty($category)){
		        foreach($category as $catkey=>$value){
					                 
					   //  }
					   
					   // $cond="id not in ($batch_id) AND status=1 AND cat_id= $value['id']";
					   $cond = array('status'=>1,'cat_id'=>$value['categoryId']);
					   
					    $subCategory = $this->db_model->select_data('id as SubcategoryId,name as SubcategoryName','batch_subcategory use index (id)',$cond,'');
					   // echo $this->db->last_query();
					    if(!empty($subCategory)){
					        
					        foreach($subCategory as $subkey=>$value){
					    
					     
					   $cond_sub1 = array('status'=>1,'sub_cat_id'=>$value['SubcategoryId']);
					   
    	            $batchData = $this->db_model->select_data('id, batch_name as batchName, start_date as startDate, end_date as endDate, start_time as startTime, end_time as endTime, batch_type as batchType, batch_price as batchPrice, no_of_student as noOfStudent ,status,description, batch_image as batchImage, batch_offer_price as batchOfferPrice','batches use index (id)',$cond_sub1,$slider_limit,array('id','desc'),$like_search);
           
    				if(!empty($batchData)){
    					foreach($batchData as $key=>$value){
    						if(!empty($value['batchImage'])){
    							$batchData[$key]['batchImage']= base_url('uploads/batch_image/').$value['batchImage'];
    						}
    						$startDate =$value['startDate'];
    						$endDate =$value['endDate'];
                            $batchData[$key]['startDate']=date('d-m-Y',strtotime($startDate));
                            $batchData[$key]['endDate']=date('d-m-Y',strtotime($endDate));
                           
    						$batch_fecherd =$this->db_model->select_data('batch_specification_heading as batchSpecification, batch_fecherd as fecherd','batch_fecherd',array('batch_id'=>$value['id']));
    						if(!empty($batch_fecherd)){
    							   $batchData[$key]['batchFecherd'] =$batch_fecherd;
    						}else{
    							$batchData[$key]['batchFecherd']=array();
    						}
    	
    					   // add payment type
    					   $batchData[$key]['paymentType'] = $this->general_settings('payment_type');
    					   $batchData[$key]['currencyCode'] = $this->general_settings('currency_code');
    					   $batchData[$key]['currencyDecimalCode'] = $this->general_settings('currency_decimal_code');
    					   
    					   
    					   $batchSubject = $this->db_model->select_data('subjects.id,subjects.subject_name as subjectName, batch_subjects.chapter','subjects use index (id)',array('batch_id'=>$value['id']),'',array('id','desc'),'',array('batch_subjects','batch_subjects.subject_id=subjects.id'));
    					   if(!empty($batchSubject)){
    					       foreach($batchSubject as $skey=>$svalue){
    					            $cid=implode(', ',json_decode($svalue['chapter']));
    					            $con ="id in ($cid)";
    					            $chapter =$this->db_model->select_data('id,chapter_name as chapterName','chapters use index (id)',$con,'',array('id','desc'));
    					            if(!empty($chapter)){
    					                foreach($chapter as $ckey=>$cvalue){
    					                    
    					                    $sub_like = array('topic',urldecode($cvalue['chapterName']));
    					                    
                    					    $sub_videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('status'=>1,'batch'=>$value['id']),'',array('id','desc'),$sub_like);
                                          
                                            if(!empty($sub_videos)){
                                                foreach($sub_videos as $vkey=>$vvalue){
                                                    $url = $vvalue['url'];
                                                    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                                    $sub_videos[$vkey]['videoId']=$match[1];
                                                    
                                                    // preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                                    // $sub_videos[$vkey]['videoId']=$match[1];
                                                }
                                                $chapter[$ckey]['videoLectures']=$sub_videos;
                                            }else{
                                              $chapter[$ckey]['videoLectures'] = array(); 
                                            }
    					                    
    					                    
    					                }
    					                $batchSubject[$skey]['chapter']=$chapter;
    					            }else{
    					             $batchSubject[$skey]['chapter']=array();   
    					            }
    					            
    					            
    					       }
    					       
    					       $batchData[$key]['batchSubject'] = $batchSubject;
    					   }else{
    					     $batchData[$key]['batchSubject'] = array();  
    					   }
    					    $like = array('batch','"'.$value['id'].'"');
    					    $videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('status'=>1,'batch'=>$value['id']),'',array('id','desc'),'');
                            // echo $this->db->last_query();
                        //   print_r($like);die;
                            if(!empty($videos)){
                                foreach($videos as $vkey=>$vvalue){
                                    $url = $vvalue['url'];
                                    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                    $videos[$vkey]['videoId']=$match[1];
                                }
                                $batchData[$key]['videoLectures']=$videos;
                            }else{
                              $batchData[$key]['videoLectures'] = array(); 
                            }
    					   $student_batch_dtail = $this->db_model->select_data('*','sudent_batchs',array('student_id'=>$data['student_id'],'batch_id' => $value['id'] ),'');
    					   if(!empty($student_batch_dtail)){
    					        $batchData[$key]['purchase_condition'] = true;
    					   }else{
    					       $batchData[$key]['purchase_condition'] = false;
    					   }
    					}
            				$subCategory[$subkey]['BatchData'] = $batchData;
            				
    			    }else{
    			        
    			       unset($subCategory[$subkey]);
    			       
    			       
    			       
    					   	}
    					   	
    					   	if(!empty($subCategory[$subkey]['BatchData'])){
    					   	     $subCategory=array_values($subCategory);  
    					   	$category[$catkey]['subcategory'] = $subCategory;
    					   	}else{
    					   	    unset($subCategory[$subkey]);
    					   	    unset($category[$catkey]['subcategory'][$subkey]);
    					   	}
    					   
    					   
    					}
    					if(!isset($category[$catkey]['subcategory'])){
    					    unset($category[$catkey]);
    					    
    					}
    					 
    					 
    			   }else{
    					       
    				   		$category[$catkey]['subcategory'] = array();
    				   }
				  
				}
			}
			 $category=array_values($category); 
			 if($data['start']==0){
				$getOtherBatch=$this->otherBatchData($data);
				if($getOtherBatch){
				    array_push($category,$getOtherBatch);
				}
			 }

			$yourBatch=array();
			$recommendedBatch=array();
			
			if(!empty($data['student_id'])){
			    
			    $batchs_id =$this->db_model->select_data('batch_id','sudent_batchs',array('student_id'=>$data['student_id']));
			       //print_r($batchs_id);
			    if(!empty($batchs_id)){
			        
			        $batchId=array();
			        foreach($batchs_id as $key=>$value){
			            $batchId[$key] =$value['batch_id'];
			        }
		
			    $batch_id =implode(', ',$batchId);
			  
			    if(!empty($batch_id)){
			    $cond="id in ($batch_id)";
			    }else{
			        $cond = '';
			    }
			   
			    $yourBatch = $this->db_model->select_data('id ,batch_name as batchName, start_date as startDate, end_date as endDate, start_time	as startTime, end_time as endTime, batch_type as batchType, batch_price as batchPrice, no_of_student as noOfStudent ,status,description, batch_image as batchImage, batch_offer_price as batchOfferPrice','batches use index (id)',$cond,'',array('id','desc'));
			 
			                        
			    if(!empty($yourBatch)){
			        
			        foreach($yourBatch as $key=>$value){
						if(!empty($value['batchImage'])){
							$yourBatch[$key]['batchImage']= base_url('uploads/batch_image/').$value['batchImage'];
						}
						$startDate =$value['startDate'];
						$endDate =$value['endDate'];
                        $yourBatch[$key]['startDate']=date('d-m-Y',strtotime($startDate));
                        $yourBatch[$key]['endDate']=date('d-m-Y',strtotime($endDate));
						$batch_fecherd =$this->db_model->select_data('batch_specification_heading as batchSpecification, batch_fecherd as fecherd','batch_fecherd',array('batch_id'=>$value['id']));
						if(!empty($batch_fecherd)){
							   $yourBatch[$key]['batchFecherd'] =$batch_fecherd;
						}else{
							$yourBatch[$key]['batchFecherd']=array();
						}
					   // add payment type
					   $yourBatch[$key]['paymentType'] = $this->general_settings('payment_type');
					   $yourBatch[$key]['currencyCode'] = $this->general_settings('currency_code');
					   $yourBatch[$key]['currencyDecimalCode'] = $this->general_settings('currency_decimal_code');
					   
					   $batchSubject = $this->db_model->select_data('subjects.id,subjects.subject_name as subjectName, batch_subjects.chapter','subjects use index (id)',array('batch_id'=>$value['id']),'',array('id','desc'),'',array('batch_subjects','batch_subjects.subject_id=subjects.id'));
					   if(!empty($batchSubject)){
					       foreach($batchSubject as $skey=>$svalue){
					            $cid=implode(', ',json_decode($svalue['chapter']));
					            $con ="id in ($cid)";
					            
					            $chapter =$this->db_model->select_data('id,chapter_name as chapterName','chapters use index (id)',$con,'',array('id','desc'));
					            if(!empty($chapter)){
					                foreach($chapter as $ckey=>$cvalue){
					                    
					                    $sub_like = array('topic,batch',urldecode($cvalue['chapterName']).',"'.$value['id'].'"');
                					    $sub_videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('status'=>1),'',array('id','desc'),$sub_like);
                                        if(!empty($sub_videos)){
                                            foreach($sub_videos as $vkey=>$vvalue){
                                                $url = $vvalue['url'];
                                                preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                                $sub_videos[$vkey]['videoId']=$match[1];
                                            }
                                            $chapter[$ckey]['videoLectures']=$sub_videos;
                                        }else{
                                          $chapter[$ckey]['videoLectures'] = array(); 
                                        }
					                    
					                    
					                }
					                $batchSubject[$skey]['chapter']=$chapter;
					            }else{
					             $batchSubject[$skey]['chapter']=array();   
					            }
					            
					       }
					       
					       $yourBatch[$key]['batchSubject'] = $batchSubject;
					   }else{
					       $yourBatch[$key]['batchSubject'] = array();  
					   }
					    $like = array('batch','"'.$value['id'].'"');
					    $videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('status'=>1),'',array('id','desc'),$like);
                        if(!empty($videos)){
                            foreach($videos as $vkey=>$vvalue){
                                $url = $vvalue['url'];
                                preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                $videos[$vkey]['videoId']=$match[1];
                            }
                            $yourBatch[$key]['videoLectures']=$videos;
                        }else{
                            $yourBatch[$key]['videoLectures'] = array(); 
                        }

					}
			    }
			        
			    }
			    
			   $categoryData = $this->db_model->select_data('id as categoryId,name as categoryName','batch_category use index (id)',array('status'=>1),'');
			     if(!empty($categoryData)){
			        
			        foreach($categoryData as $key1=>$value){
			                 
			   //  }
			   
			   // $cond="id not in ($batch_id) AND status=1 AND cat_id= $value['id']";
			   $cond = array('status'=>1,'cat_id'=>$value['categoryId']);
			   

			    $subCategory = $this->db_model->select_data('id as SubcategoryId,name as SubcategoryName','batch_subcategory use index (id)',$cond,'');
			   // echo $this->db->last_query();
			    if(!empty($subCategory)){
			        
			        foreach($subCategory as $key2=>$value){
			    
			     
			   $cond_sub = array('status'=>1,'sub_cat_id'=>$value['SubcategoryId']);
			
                $recommendedBatch = $this->db_model->select_data('id ,batch_name as batchName, start_date as startDate, end_date as endDate, start_time	as startTime, end_time as endTime, batch_type as batchType, batch_price as batchPrice, no_of_student as noOfStudent ,status,description, batch_image as batchImage, batch_offer_price as batchOfferPrice','batches use index (id)',$cond_sub,'',array('id','desc'));
                // print_r($recommendedBatch);
                // echo $this->db->last_query();
			    if(!empty($recommendedBatch)){
			        
			        foreach($recommendedBatch as $key=>$value){
						if(!empty($value['batchImage'])){
							$recommendedBatch[$key]['batchImage']= base_url('uploads/batch_image/').$value['batchImage'];
						}
						$startDate =$value['startDate'];
						$endDate =$value['endDate'];
                        $recommendedBatch[$key]['startDate']=date('d-m-Y',strtotime($startDate));
                        $recommendedBatch[$key]['endDate']=date('d-m-Y',strtotime($endDate));
						$batch_fecherd =$this->db_model->select_data('batch_specification_heading as batchSpecification, batch_fecherd as fecherd','batch_fecherd',array('batch_id'=>$value['id']));
						if(!empty($batch_fecherd)){
							   $recommendedBatch[$key]['batchFecherd'] =$batch_fecherd;
						}else{
							$recommendedBatch[$key]['batchFecherd']=array();
						}
					   // add payment type
					   $recommendedBatch[$key]['paymentType'] = $this->general_settings('payment_type');
					   $recommendedBatch[$key]['currencyCode'] = $this->general_settings('currency_code');
					   $recommendedBatch[$key]['currencyDecimalCode'] = $this->general_settings('currency_decimal_code');
					   
					   $batchSubject = $this->db_model->select_data('subjects.id,subjects.subject_name as subjectName, batch_subjects.chapter','subjects use index (id)',array('batch_id'=>$value['id']),'',array('id','desc'),'',array('batch_subjects','batch_subjects.subject_id=subjects.id'));
					   if(!empty($batchSubject)){
					       foreach($batchSubject as $skey=>$svalue){
					            $cid=implode(', ',json_decode($svalue['chapter']));
					            $con ="id in ($cid)";
					            
					            $chapter =$this->db_model->select_data('id,chapter_name as chapterName','chapters use index (id)',$con,'',array('id','desc'));
					            if(!empty($chapter)){
					                foreach($chapter as $ckey=>$cvalue){
					                    
					                    $sub_like = array('topic,batch',urldecode($cvalue['chapterName']).',"'.$value['id'].'"');
                					    $sub_videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('status'=>1),'',array('id','desc'),$sub_like);
                                        if(!empty($sub_videos)){
                                            foreach($sub_videos as $vkey=>$vvalue){
                                                $url = $vvalue['url'];
                                                preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                                $sub_videos[$vkey]['videoId']=$match[1];
                                            }
                                            $chapter[$ckey]['videoLectures']=$sub_videos;
                                        }else{
                                          $chapter[$ckey]['videoLectures'] = array(); 
                                        }
					                    
					                    
					                }
					                $batchSubject[$skey]['chapter']=$chapter;
					            }else{
					             $batchSubject[$skey]['chapter']=array();   
					            }
					       }
					       
					       $recommendedBatch[$key]['batchSubject'] = $batchSubject;
					   }else{
					     $recommendedBatch[$key]['batchSubject'] = array();  
					   }
					   		

					   	}
					   	$subCategory[$key2]['BatchData'] = $recommendedBatch;
					   	
					}
				
			       else{
					   	 $subCategory[$key2]['BatchData'] = array();
					   	 $categoryData[$key1]['subcategory'] = array();
					   	}
					}
		// 			print_r($subCategory);
					 $categoryData[$key1]['subcategory'] = $subCategory;
			   }else{
			   		$categoryData[$key1]['subcategory'] = array();
			   }
			  
			}
		}
			    
			}
		
			$arr = array(
		                'status'=>'true',
		                'msg' =>$this->lang->line('ltr_fetch_successfully'),
		                'batchData'=>$category,
		                'yourBatch'=>$yourBatch,
		                // 'category'=>$categoryData,
		                'recommendedBatch'=>$categoryData
		                );
			
		// }else{
		// 	$arr = array('status'=>'false', 'msg' => $this->lang->line('ltr_no_record_msg')); 
		// }
	echo json_encode($arr);
}
/*other batch function start*/
public function otherBatchData($data){
    $search = trim($data['search']);
		    $slider_limit = $data['limit'];
		    
			if(!empty($search)){
			  $like_search = array('batch_name',$search);
			}else{
			    $like_search = '';
			}
	
          if(isset($data['length']) && $data['length']>0){
                if(isset($data['start']) && !empty($data['start'])){
                    $limit = array($data['length'],$data['start']);
                    // $count = $data['start']+1;
                }else{ 
                    $limit = array($data['length'],0);
                    // $count = 1;
                }
            }else{
                $limit = '';
                // $count = 1;
            }
					     $category=array('categoryId'=>'0','categoryName'=>'other');
					     $subCategory['SubcategoryId']='0';
					     $subCategory['SubcategoryName']='other';
					   $cond_sub1 = array('status'=>1,'sub_cat_id'=>0);
					   
	$batchData = $this->db_model->select_data('id, batch_name as batchName, start_date as startDate, end_date as endDate, start_time as startTime, end_time as endTime, batch_type as batchType, batch_price as batchPrice, no_of_student as noOfStudent ,status,description, batch_image as batchImage, batch_offer_price as batchOfferPrice','batches use index (id)',$cond_sub1,$slider_limit,array('id','desc'),$like_search);
       
				if(!empty($batchData)){
					foreach($batchData as $key=>$value){
						if(!empty($value['batchImage'])){
							$batchData[$key]['batchImage']= base_url('uploads/batch_image/').$value['batchImage'];
						}
						$startDate =$value['startDate'];
						$endDate =$value['endDate'];
                        $batchData[$key]['startDate']=date('d-m-Y',strtotime($startDate));
                        $batchData[$key]['endDate']=date('d-m-Y',strtotime($endDate));
                       
						$batch_fecherd =$this->db_model->select_data('batch_specification_heading as batchSpecification, batch_fecherd as fecherd','batch_fecherd',array('batch_id'=>$value['id']));
						if(!empty($batch_fecherd)){
							   $batchData[$key]['batchFecherd'] =$batch_fecherd;
						}else{
							$batchData[$key]['batchFecherd']=array();
						}
	
					   // add payment type
					   $batchData[$key]['paymentType'] = $this->general_settings('payment_type');
					   $batchData[$key]['currencyCode'] = $this->general_settings('currency_code');
					   $batchData[$key]['currencyDecimalCode'] = $this->general_settings('currency_decimal_code');
					   
					   
					   $batchSubject = $this->db_model->select_data('subjects.id,subjects.subject_name as subjectName, batch_subjects.chapter','subjects use index (id)',array('batch_id'=>$value['id']),'',array('id','desc'),'',array('batch_subjects','batch_subjects.subject_id=subjects.id'));
					   if(!empty($batchSubject)){
					       foreach($batchSubject as $skey=>$svalue){
					            $cid=implode(', ',json_decode($svalue['chapter']));
                                if(!empty($cid)){
					            $con ="id in ($cid)";
					            $chapter =$this->db_model->select_data('id,chapter_name as chapterName','chapters use index (id)',$con,'',array('id','desc'));
					            if(!empty($chapter)){
					                foreach($chapter as $ckey=>$cvalue){
					                    
					                    $sub_like = array('topic,batch',urldecode($cvalue['chapterName']).',"'.$value['id'].'"');
                					    $sub_videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('status'=>1),'',array('id','desc'),$sub_like);
                                        if(!empty($sub_videos)){
                                            foreach($sub_videos as $vkey=>$vvalue){
                                                $url = $vvalue['url'];
                                                preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                                $sub_videos[$vkey]['videoId']=$match[1];
                                            }
                                            $chapter[$ckey]['videoLectures']=$sub_videos;
                                        }else{
                                          $chapter[$ckey]['videoLectures'] = array(); 
                                        }
					                    
					                    
					                }
					                $batchSubject[$skey]['chapter']=$chapter;
					            }else{
					             $batchSubject[$skey]['chapter']=array();   
					            }
                            }else{
                                $batchSubject[$skey]['chapter']=array();   
                               }
					            
					            
					       }
					       
					       $batchData[$key]['batchSubject'] = $batchSubject;
					   }else{
					     $batchData[$key]['batchSubject'] = array();  
					   }
					    $like = array('batch','"'.$value['id'].'"');
					    $videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('status'=>1),'',array('id','desc'),$like);
                        if(!empty($videos)){
                            foreach($videos as $vkey=>$vvalue){
                                $url = $vvalue['url'];
                                preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                $videos[$vkey]['videoId']=$match[1];
                            }
                            $batchData[$key]['videoLectures']=$videos;
                        }else{
                          $batchData[$key]['videoLectures'] = array(); 
                        }
					   $student_batch_dtail = $this->db_model->select_data('*','sudent_batchs',array('student_id'=>$data['student_id'],'batch_id' => $value['id'] ),'');
					   if(!empty($student_batch_dtail)){
					        $batchData[$key]['purchase_condition'] = true;
					   }else{
					       $batchData[$key]['purchase_condition'] = false;
					   }
					}
        				$subCategory['BatchData'] = $batchData;
        				
					    }else{
					        
					       unset($subCategory);
					       
					       
					       
        					   	}
        					   	
        					   	if(!empty($subCategory['BatchData'])){
        					   	     //$subCategory=array_values($subCategory);  
        					   	$category['subcategory'] = array($subCategory);
        					   	}else{
        					   	    unset($subCategory);
        					   	    unset($category['subcategory']);
        					   	}
        					   if(!empty($category['subcategory'])){
        					       return	$category;
        					   }else{
        					       return	false;
        					   }
        					   //print_r($category);
						
}
							/*Other batch functio end*/
    function profile_update()
{
    $from_body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($from_body)) {
        $from_body = array();
    }
    $data = array_merge($_REQUEST, $from_body);
    if (!is_array($data)) {
        $data = array();
    }
    $data = $this->normalize_multi_user_registration_data($data);

    $payload = $this->require_auth_payload();
    if ($payload === false) {
        return;
    }

    $uid = (int) $payload['uid'];
    $ut = strtolower(trim((string) $payload['ut']));

    if ($uid < 1) {
        echo json_encode(array('status' => 'false', 'msg' => 'Invalid token user'));
        return;
    }

    if ($ut === 'student') {
        if ($this->authorize_student_request($uid) === false) {
            return;
        }
        $data_arr = array();

        $fields = array(
            'name', 'email', 'mobile', 'address', 'country',
            'state', 'city', 'pincode', 'school_college_name', 'grade', 'is_complete',
        );

        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $data_arr[$field] = $data[$field];
            }
        }

        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $upload_path = FCPATH . 'uploads/students/';
            if (!is_dir($upload_path)) {
                @mkdir($upload_path, 0777, true);
            }

            $uploaded_image = $this->upload_media($_FILES, $upload_path, 'image');
            if (is_array($uploaded_image) && isset($uploaded_image['status']) && $uploaded_image['status'] === '2') {
                echo json_encode(array(
                    'status' => 'false',
                    'msg' => strip_tags($uploaded_image['msg']),
                ));
                return;
            }

            if (!empty($uploaded_image)) {
                $data_arr['image'] = $uploaded_image;
            }
        } elseif (!empty($data['image']) && is_string($data['image'])) {
            $raw_image = $data['image'];
            if (preg_match('/^data:image\/(\w+);base64,/', $raw_image, $matches)) {
                $extension = strtolower($matches[1]);
                if (!in_array($extension, array('jpg', 'jpeg', 'png'), true)) {
                    echo json_encode(array(
                        'status' => 'false',
                        'msg' => 'Only jpg, jpeg, png images are allowed',
                    ));
                    return;
                }

                $base64_data = substr($raw_image, strpos($raw_image, ',') + 1);
                $binary = base64_decode($base64_data, true);
                if ($binary === false) {
                    echo json_encode(array(
                        'status' => 'false',
                        'msg' => 'Invalid base64 image data',
                    ));
                    return;
                }

                $upload_path = FCPATH . 'uploads/students/';
                if (!is_dir($upload_path)) {
                    @mkdir($upload_path, 0777, true);
                }

                $file_name = 'student_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
                if (file_put_contents($upload_path . $file_name, $binary) === false) {
                    echo json_encode(array(
                        'status' => 'false',
                        'msg' => 'Unable to save profile image',
                    ));
                    return;
                }

                $data_arr['image'] = $file_name;
            }
        }

        if (empty($data_arr)) {
            echo json_encode(array('status' => 'false', 'msg' => 'No data to update'));
            return;
        }

        $ins = $this->db_model->update_data_limit(
            'students',
            $data_arr,
            array('id' => $uid),
            1
        );

        if (!$ins) {
            echo json_encode(array('status' => 'false', 'msg' => 'Update failed'));
            return;
        }

        $updated = $this->db_model->select_data('*', 'students', array('id' => $uid), 1);
        if (empty($updated[0])) {
            echo json_encode(array('status' => 'false', 'msg' => 'Unable to load updated profile'));
            return;
        }

        $stu = $updated[0];
        unset($stu['password']);

        $access_token = trim((string) $this->get_access_token_from_request());
        if ($access_token === '') {
            $mint = $this->mint_access_credentials($uid, 'student');
            $access_token = $mint['access_token'];
            $this->db_model->update_data_limit(
                'students use index (id)',
                array('last_login_app' => date('Y-m-d H:i:s', $mint['iat'])),
                array('id' => $uid),
                1
            );
        }

        $now = date('Y-m-d H:i:s');
        $dev_id = isset($stu['device_id']) ? $stu['device_id'] : '';
        $dev_tok = isset($stu['device_token']) ? $stu['device_token'] : '';
        $dev_type = isset($stu['device_type']) ? $stu['device_type'] : '';

        $response_data = $this->build_student_login_data_array(
            $stu,
            $dev_id,
            $dev_tok,
            $dev_type,
            $now,
            $access_token
        );

        echo json_encode(array(
            'status' => 'true',
            'msg' => 'Profile updated successfully',
            'data' => $response_data,
        ), JSON_UNESCAPED_SLASHES);
        return;
    }

    if ($ut === 'teacher' || $ut === 'institute') {
        $rows = $this->db_model->select_data('*', 'users', array('id' => $uid), 1);
        if (empty($rows[0])) {
            echo json_encode(array('status' => 'false', 'msg' => 'User not found'));
            return;
        }
        $urow = $rows[0];
        if (!empty($urow['user_type']) && strtolower(trim((string) $urow['user_type'])) !== $ut) {
            echo json_encode(array('status' => 'false', 'msg' => 'Token does not match this account'));
            return;
        }

        $data_arr = array();

        $map = array(
            'name' => 'name',
            'email' => 'email',
            'mobile' => 'mobile',
            'address' => 'address',
            'country' => 'country',
            'state' => 'state',
            'city' => 'city',
            'pincode' => 'pincode',
        );
        foreach ($map as $in => $col) {
            if (isset($data[$in]) && $data[$in] !== '') {
                $data_arr[$col] = $data[$in];
            }
        }

        if (isset($data['gender']) && $data['gender'] !== '') {
            $data_arr['teach_gender'] = $data['gender'];
        }
        if (isset($data['teach_gender']) && $data['teach_gender'] !== '') {
            $data_arr['teach_gender'] = $data['teach_gender'];
        }
        if (isset($data['school_college_name']) && $data['school_college_name'] !== '') {
            $data_arr['teach_education'] = $data['school_college_name'];
        }
        if (isset($data['teach_education']) && $data['teach_education'] !== '') {
            $data_arr['teach_education'] = $data['teach_education'];
        }

        if ($ut === 'institute') {
            $locKeys = array('address', 'city', 'state', 'country', 'pincode');
            $locTouched = false;
            foreach ($locKeys as $lk) {
                if (isset($data[$lk]) && trim((string) $data[$lk]) !== '') {
                    $locTouched = true;
                    break;
                }
            }
            if ($locTouched) {
                $pickLoc = function ($key) use ($data_arr, $urow) {
                    if (isset($data_arr[$key]) && trim((string) $data_arr[$key]) !== '') {
                        return trim((string) $data_arr[$key]);
                    }
                    return isset($urow[$key]) ? trim((string) $urow[$key]) : '';
                };
                $ia = $pickLoc('address');
                $ic = $pickLoc('city');
                $is = $pickLoc('state');
                $ico = $pickLoc('country');
                $ip = $pickLoc('pincode');
                if ($ia === '' || $ic === '' || $is === '' || $ico === '' || $ip === '') {
                    echo json_encode(array(
                        'status' => 'false',
                        'msg' => 'To update location, address, city, state, country, and pincode must all be set (send missing fields or save them on your profile first).',
                    ));
                    return;
                }
                $coords = $this->geocode_institute_address($ia, $ic, $is, $ico, $ip);
                if ($coords === null) {
                    echo json_encode(array(
                        'status' => 'false',
                        'msg' => 'Could not determine latitude and longitude from the address. Please verify address, city, state, country, and pincode.',
                    ));
                    return;
                }
                try {
                    $userColumns = $this->db->list_fields('users');
                } catch (Exception $e) {
                    $userColumns = array();
                }
                $haveCol = array_flip($userColumns);
                if (isset($haveCol['lat'])) {
                    $data_arr['lat'] = $coords['lat'];
                }
                if (isset($haveCol['long'])) {
                    $data_arr['long'] = $coords['long'];
                }
                if (isset($haveCol['latitude'])) {
                    $data_arr['latitude'] = $coords['lat'];
                }
                if (isset($haveCol['longitude'])) {
                    $data_arr['longitude'] = $coords['long'];
                }
            }
        }

        $upload_path = FCPATH . 'uploads/users/';
        if (!is_dir($upload_path)) {
            @mkdir($upload_path, 0777, true);
        }

        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $uploaded_image = $this->upload_media($_FILES, $upload_path, 'image');
            if (is_array($uploaded_image) && isset($uploaded_image['status']) && $uploaded_image['status'] === '2') {
                echo json_encode(array(
                    'status' => 'false',
                    'msg' => strip_tags($uploaded_image['msg']),
                ));
                return;
            }
            if (!empty($uploaded_image)) {
                $data_arr['image'] = $uploaded_image;
                $data_arr['teach_image'] = $uploaded_image;
            }
        } elseif (!empty($data['image']) && is_string($data['image'])) {
            $raw_image = $data['image'];
            if (preg_match('/^data:image\/(\w+);base64,/', $raw_image, $matches)) {
                $extension = strtolower($matches[1]);
                if (!in_array($extension, array('jpg', 'jpeg', 'png'), true)) {
                    echo json_encode(array(
                        'status' => 'false',
                        'msg' => 'Only jpg, jpeg, png images are allowed',
                    ));
                    return;
                }

                $base64_data = substr($raw_image, strpos($raw_image, ',') + 1);
                $binary = base64_decode($base64_data, true);
                if ($binary === false) {
                    echo json_encode(array(
                        'status' => 'false',
                        'msg' => 'Invalid base64 image data',
                    ));
                    return;
                }

                $file_name = 'user_' . $uid . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
                if (file_put_contents($upload_path . $file_name, $binary) === false) {
                    echo json_encode(array(
                        'status' => 'false',
                        'msg' => 'Unable to save profile image',
                    ));
                    return;
                }
                $data_arr['image'] = $file_name;
                $data_arr['teach_image'] = $file_name;
            }
        }

        if (empty($data_arr)) {
            echo json_encode(array('status' => 'false', 'msg' => 'No data to update'));
            return;
        }

        // Do not set users.updated_at here: JWT validation uses updated_at vs token iat for
        // teacher/institute; bumping it on every profile save invalidates the access token.
        $geoKeys = array('lat', 'long', 'latitude', 'longitude');
        $geoVals = array();
        foreach ($geoKeys as $gk) {
            if (array_key_exists($gk, $data_arr)) {
                $geoVals[$gk] = $data_arr[$gk];
                unset($data_arr[$gk]);
            }
        }
        $data_arr = $this->security->xss_clean($data_arr);
        $data_arr = array_merge($data_arr, $geoVals);

        $this->db->reset_query();
        $this->db->where('id', $uid);
        $this->db->limit(1);
        $ins = $this->db->update('users', $data_arr);

        if (!$ins) {
            echo json_encode(array('status' => 'false', 'msg' => 'Update failed'));
            return;
        }

        $updated = $this->db_model->select_data('*', 'users', array('id' => $uid), 1);
        if (empty($updated[0])) {
            echo json_encode(array('status' => 'false', 'msg' => 'Unable to load updated profile'));
            return;
        }

        $u = $updated[0];
        unset($u['password']);

        $access_token = trim((string) $this->get_access_token_from_request());
        if ($access_token === '') {
            $mint = $this->mint_access_credentials($uid, isset($u['user_type']) ? $u['user_type'] : $ut);
            $access_token = $mint['access_token'];
            $this->db_model->update_data_limit(
                'users use index (id)',
                array('updated_at' => date('Y-m-d H:i:s', $mint['iat'])),
                array('id' => $uid),
                1
            );
        }

        $now = date('Y-m-d H:i:s');
        $dev_id = isset($u['device_id']) ? $u['device_id'] : '';
        $dev_tok = isset($u['device_token']) ? $u['device_token'] : '';
        $dev_type = isset($u['device_type']) ? $u['device_type'] : '';
        $profile_completed = (empty($u['city']) || trim((string) $u['city']) === '') ? 0 : 1;

        $response_data = $this->build_non_student_login_data_array(
            $u,
            $dev_id,
            $dev_tok,
            $dev_type,
            $now,
            $access_token,
            $profile_completed
        );

        echo json_encode(array(
            'status' => 'true',
            'msg' => 'Profile updated successfully',
            'data' => $response_data,
        ), JSON_UNESCAPED_SLASHES);
        return;
    }

    echo json_encode(array(
        'status' => 'false',
        'msg' => 'Profile update is not available for this account type',
    ));
}
	
    function upload_media($files,$path,$file){   
        $config['upload_path'] =$path;
        $config['allowed_types'] = 'jpeg|jpg|png';
        $config['max_size']    = '0';
        $filename = '';		
        $this->load->library('upload', $config);
        if ($this->upload->do_upload($file)){
            $uploadData = $this->upload->data();
            $filename = $uploadData['file_name'];
            return $filename;
        }else{
            $resp = array('status'=>'2', 'msg' => $this->upload->display_errors());
            return $resp;
        }     
    }
    function notice_count(){
        $data = $_REQUEST;
        if(isset($data['admin_id'])){
            $admin_id = $data['admin_id'];
            $unreadNotice=[];
            $cond = "admin_id = $admin_id AND status = 1 AND (notice_for = 'Student' OR notice_for = 'Both')";
            $notice_count = $this->db_model->countAll('notices',$cond);
            if(!empty($notice_count)){
                $arr = array('status'=>'true','unreadNotice'=>$notice_count);
            }else{
                $arr = array('status'=>'false','msg'=>$this->lang->line('ltr_something_msg'));
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            );
        }
		echo json_encode($arr);
    }
    function notices(){
        $data = $_REQUEST;
        if(isset($data['admin_id']) && isset($data['student_id'])){
            $admin_id = $data['admin_id'];
            if(!empty($data['uid'])){
                $this->viewNotificationStatus($data['uid'],'notices');
            }
            $student_id = $data['student_id'];
            $cond = "admin_id = $admin_id AND status = 1";
            if($student_id == 'all'){
                $cond .= " AND (notice_for = 'Student' OR notice_for = 'Both')";
            }else{
                $cond .= " AND student_id = $student_id";
            }
            // $cond = "admin_id = $admin_id AND status = 1 AND (notice_for = 'Student' OR notice_for = 'Both')";
            $notices = $this->db_model->select_data('`id`, `title`, `description`, `notice_for` as noticeFor, `status`, `date`, `admin_id` as adminId, `student_id` as studentId, `teacher_id` as teacherId, `added_by` as addedBy, `read_status` as readStatus, `added_at` as addedAt','notices use index (id)',$cond,'',array('id','desc'));
            if($notices){
                foreach($notices as $key=>$value){
                    
                    $notices[$key]['date']= date('d-m-Y',strtotime($value['date']));
                    $notices[$key]['addedAt']= date('d-m-Y',strtotime($value['addedAt']));
                }
                $this->db_model->update_data('notices use index(id)',array('read_status'=>1),array('student_id'=>$student_id));
                $arr = array('status'=>'true','msg'=>$this->lang->line('ltr_fetch_successfully'),'allNotice'=>$notices);
            }else{
                $arr = array('status'=>'false','msg'=>$this->lang->line('ltr_no_record_msg'));
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            );
        }
    	echo json_encode($arr);		
    }
    function extraClass(){
        $data = $_REQUEST;
        if(isset($data['admin_id']) && isset($data['type']) && isset($data['page_no']) && isset($data['batch_id'])){
            if(!empty($data['student_id'])){
                $this->viewNotificationStatus($data['student_id'],'extraClass');
            }
            $admin_id = $data['admin_id'];
            $type = $data['type'];
            $startlimit = $data['page_no'];
            $batch_id_like = '"'.$data['batch_id'].'"';
            if($startlimit > 0){
                $start = ((($startlimit)*10)+1)-1; 
            }else{
                $start = 0;
            }
            $limit = array(10,$start);
            if($type=='previous'){
                $cond = array('extra_classes.admin_id'=>$admin_id,'extra_classes.date < '=>date('Y-m-d'));
                $order = array('date','desc');
            }else{
                $cond = array('extra_classes.admin_id'=>$admin_id,'extra_classes.date >= '=>date('Y-m-d'));
                $order = array('date','asc');
            }
            $extraClsData = $this->db_model->select_data(' extra_classes.id,extra_classes.admin_id as adminId, extra_classes.date,extra_classes.start_time as startTime, extra_classes.end_time as endTime,extra_classes.teacher_id as teacherId, extra_classes.description,extra_classes.status,extra_classes.batch_id as batchId,extra_classes.added_at as addedAt,extra_classes.completed_date_time as completedDateTime,users.name,users.teach_gender as teachGender','extra_classes use index (id)',$cond,$limit,$order,array('batch_id',$batch_id_like),array('users','users.id = extra_classes.teacher_id'),'');
            if (!empty($extraClsData)){
                $arr = array(
                    'extraClass' => $extraClsData,
                    'status' => 'true',
                    'msg' => $this->lang->line('ltr_fetch_successfully')
                );
            } else {
                $arr = array(
                    'status' => 'false',
                    'msg' => $this->lang->line('ltr_no_record_msg')
                );
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            );
        }
        echo json_encode($arr);
    }
    function Homework(){ 
        $data = $_REQUEST;
        $payload = $this->require_auth_payload();
        if ($payload === false) {
            return;
        }

        if (empty($data['batch_id'])) {
            if ($payload['ut'] === 'student') {
                $student_row = $this->db_model->select_data('id,batch_id,admin_id', 'students use index (id)', array('id' => (int) $payload['uid']), 1);
                if (!empty($student_row[0]['batch_id'])) {
                    $data['batch_id'] = $student_row[0]['batch_id'];
                }
                if (empty($data['admin_id']) && !empty($student_row[0]['admin_id'])) {
                    $data['admin_id'] = $student_row[0]['admin_id'];
                }
            }
        }

        if ( $payload['ut'] === 'teacher') {
            $teacher_row = $this->db_model->select_data('id,admin_id', 'users use index (id)', array('id' => (int) $payload['uid']), 1);
            if (!empty($teacher_row[0]['admin_id'])) {
                $data['admin_id'] = $teacher_row[0]['admin_id'];
            }
        }

        if ( isset($data['batch_id'])) {
            $date = !empty($data['homework_date']) ? date('Y-m-d', strtotime($data['homework_date'])) : date('Y-m-d');

            // Mark notification read for student app calls.
            if ($payload['ut'] === 'student') {
                $this->viewNotificationStatus((int) $payload['uid'], 'homeWork');
            }

            $cond = array(
                'homeworks.admin_id' => $data['admin_id'],
                //'homeworks.date' => $date,
                'homeworks.batch_id' => $data['batch_id']
            );

            // For teacher, show only homework created by logged-in teacher.
            if ($payload['ut'] === 'teacher') {
                $cond['homeworks.teacher_id'] = (int) $payload['uid'];
            }

            $homewrkData = $this->db_model->select_data(
                'homeworks.id,homeworks.admin_id as adminId,homeworks.teacher_id as teacherId,homeworks.date,homeworks.subject_id as studentId,homeworks.batch_id as batchId,homeworks.description,homeworks.added_at as addedAt,users.name,users.teach_gender as teachGender,subjects.subject_name as subjectName',
                'homeworks use index (id)',
                $cond,
                '',
                array('id', 'desc'),
                '',
                array('multiple', array(array('users', 'users.id = homeworks.teacher_id'), array('subjects', 'subjects.id = homeworks.subject_id'))),
                ''
            );

            if (!empty($homewrkData)) {
                $arr = array(
                    'homeWork' => $homewrkData,
                    'status' => 'true',
                    'msg' => $this->lang->line('ltr_fetch_successfully')
                );
            } else {
                $arr = array(
                    'status' => 'false',
                    'msg' => $this->lang->line('ltr_no_record_msg')
                );
            }
        } else {
            $arr = array(
                'status' => 'false',
                'msg' => $this->lang->line('ltr_missing_parameters_msg')
            );
        }
        echo json_encode($arr);
    }


    function get_subject(){
        $data = $_REQUEST;
        if(isset($data['admin_id']) && isset($data['batch_id'])){
            if(!empty($data['student_id'])){
                $this->viewNotificationStatus($data['student_id'],'videoLecture');
            }
            $subject = $this->db_model->select_data('subjects.id,subject_name as subjectName','subjects use index (id)',array('batch_subjects.batch_id'=>$data['batch_id']),'',array('id','desc'),'',array('batch_subjects','batch_subjects.subject_id=subjects.id'));
            
            $arr = array(
                'subject' => $subject,
                'status' => 'true',
                'msg' => $this->lang->line('ltr_fetch_successfully')
            ); 
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            ); 
        }
        echo json_encode($arr);
    }
    function get_chapter(){
        $data = $_REQUEST;
        if(isset($data['subject_id'])){
            $chapter = $this->db_model->select_data('chapter_name  as chapterName','chapters use index (id)',array('subject_id'=>$data['subject_id']),'',array('id','desc'));
            if(!empty($chapter)){
                $arr = array(
                    'chapter' => $chapter,
                    'status' => 'true',
                    'msg' => $this->lang->line('ltr_fetch_successfully')
                ); 
            }else{
                $arr = array(
                    'status' => 'false',
                    'msg' => $this->lang->line('ltr_no_record_msg')
                ); 
            }
            
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            ); 
        }
        echo json_encode($arr);
    }
  
     function video_lecture(){
        $data = $_REQUEST;
        if(isset($data['admin_id']) && isset($data['page_no']) && $data['batch_id']){
            $startlimit = $data['page_no'];
            $admin_id = $data['admin_id'];
            if($startlimit > 1){
                $start = (($startlimit-1)*10)+1; 
            }else{
                $start = 0;
            }
            $limit = array(10,$start);
            // $like = array('batch','"'.$data['batch_id'].'"');
            if(isset($data['chapter']) && $data['chapter']!=''){	
                $like = array('topic,"'.$data['chapter'].'"');			
            }			
			
			 $join = array('chapters',"chapters.id = video_lectures.topic");
			 // $videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('admin_id'=>$admin_id),$limit,array('id','desc'),$like);
            $videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('batch'=>$data['batch_id'],'topic'=>$data['chapter']),$limit,array('video_lectures.id','desc'),$like);
            // 			echo $this->db->last_query();		die;	
            if(!empty($videos)){
                foreach($videos as $key=>$value){
                    $url = $value['url'];
                    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                    $videos[$key]['videoId']=$match[1];
                }
            }			
            $num_rows = $this->db_model->countAll('video_lectures use index (id)',array('admin_id'=>$admin_id));
            if(!empty($videos)){
                $arr = array(
                    'videoLecture' => $videos,
                    'totalCount' => $num_rows,
                    'encCode' => base64_encode('AIzaSyAQ8wpz16d6izBb0wfpDI1A895Bln5nbRs'),
                    'status' => 'true',
                    'msg' => $this->lang->line('ltr_fetch_successfully')
                );
            }else{
                $arr = array(
                    'status' => 'false',
                    'msg' => $this->lang->line('ltr_no_record_msg')
                );
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            ); 
        }
        echo json_encode($arr);
    }
    function viewVacancy()
    {   
        $data = $_REQUEST;
        if(isset($data['admin_id'])){
            
            if(!empty($data['student_id'])){
                $this->viewNotificationStatus($data['student_id'],'vacancy');
             }
            $currentDate = date("Y-m-d");
            $admin_id  =  $data['admin_id'];
            $vid = isset($_POST['vid'])?$_POST['vid']:'';
            if(!empty($vid)){
               $con = array('admin_id'=>$admin_id,'last_date >= '=> $currentDate,'status'=>1,'id'=>$vid);
            }else{
               $con = array('admin_id'=>$admin_id,'last_date >= '=> $currentDate,'status'=>1);
            }
            $vacancy = $this->db_model->select_data('id,title,description,start_date as startDate,last_date as lastDate,mode,files,status,admin_id as adminId,added_at as addedAt','vacancy use index (id)',$con,'',array('id','desc'));
            if (!empty($vacancy)){
                foreach($vacancy as $key=>$value){
                    $vacancy[$key]['startDate']= date('d-m-Y', strtotime($value['startDate']));
                    $vacancy[$key]['lastDate']= date('d-m-Y', strtotime($value['lastDate']));
                    $vacancy[$key]['addedAt']= date('d-m-Y', strtotime($value['addedAt']));
                }
                $arr = array(
                    'vacancy' => $vacancy,
                    'filesUrl' => base_url('uploads/vacancy/'),
                    'status' => 'true',
                    'msg' => $this->lang->line('ltr_fetch_successfully'),
                );
            }else{
                $arr = array(
                    'status' => 'false',
                    'msg' => $this->lang->line('ltr_no_record_msg')
                );
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            ); 
        }
        echo json_encode($arr);
    }
 	function app_version(){
        $appVersion = $this->db_model->select_data('*','app_versions');
 	    if(!empty($appVersion)){
			$arr = array( 
        				'status'=>'true',
        				'version'=>$appVersion[0]['latest_version'],
        			);
		}else{
			$arr = array('status'=>'false');
		}
		echo json_encode($arr);
 	}
 	function update_version(){
        $data =	$_REQUEST;
        if(isset($data['version'])){
            $ins = $this->db_model->update_data_limit('app_versions',array('latest_version'=>$data['version']),array('id'=>1),1);
            if($ins){
               $arr = array(
                   'status' => 'true',
                   'msg' => $this->lang->line('ltr_appv_updated_msg')
               );
           }else{
               $arr = array('status'=>'false');
           }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            ); 
        }
		echo json_encode($arr);
     }
    function last_login_time(){
        $data =	$_REQUEST;
        if(isset($data['student_id'])){
            $ins = $this->db_model->update_data_limit('students',array('last_login_app'=>date('Y-m-d H:i:s')),array('id'=>$data['student_id']),1);
            if($ins){
               $arr = array(
                   'status' => 'true',
               );
           }else{
               $arr = array('status'=>'false');
           }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            ); 
        }
        echo json_encode($arr);
    }
    function db_new_changes(){
        $data =	$_REQUEST;
      
        if(isset($data['admin_id']) && isset($data['student_id']) && isset($data['batch_id'])){
            $admin_id = $data['admin_id'];
            $student_id = $data['student_id'];
            $batch_id = $data['batch_id'];
            
            $last_login = $this->db_model->select_data('last_login_app','students use index(id)',array('id'=>$student_id),1);
       
            if($last_login[0]['last_login_app'] != '0000-00-00 00:00:00'){
                
                $lastLoginTime = $last_login[0]['last_login_app'];
             
                $likeEX = array('batch_id','"'.$batch_id.'"');
              
                $extraData = $this->db_model->select_data('extra_classes.id,extra_classes.admin_id as adminId,extra_classes.date, extra_classes.start_time as startDate,extra_classes.end_time as endTime,extra_classes.teacher_id as teacherId,extra_classes.description,extra_classes.status,extra_classes.batch_id as batchId,extra_classes.added_at as addedAt,extra_classes.completed_date_time as completedDateTime,users.name,users.teach_gender as teachGender','extra_classes use index(id)',array('extra_classes.admin_id'=>$admin_id,'extra_classes.added_at >= '=>$lastLoginTime,'date >=' => date('Y-m-d')),'',array('date','asc'),$likeEX,array('users','users.id = extra_classes.teacher_id'));
                $extraClass =array();
                if(!empty($extraData)){
                    foreach($extraData as $key=>$value){
                        $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'extraClass'));
                        if(empty($view_n)){
                            $extraClass[$key]=$value;
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $extraClass[$key]=$value;
                            }
                        }
                    }
                }
                
                $homewrk = $this->db_model->select_data('homeworks.id, homeworks.admin_id as adminId,homeworks.teacher_id as teacherId, homeworks.date,homeworks.subject_id as subjectId, homeworks.batch_id as batchId, homeworks.description,homeworks.added_at as addedAt,users.name,users.teach_gender as teachGender,subjects.subject_name as subjectName','homeworks use index (id)',array('homeworks.admin_id'=>$admin_id,'homeworks.added_at >= '=>$lastLoginTime,'homeworks.batch_id'=>$batch_id,'date >=' => date('Y-m-d')),'',array('id','desc'),'',array('multiple',array(array('users','users.id = homeworks.teacher_id'),array('subjects','subjects.id = homeworks.subject_id'))),'');
                $homewrkData=array();
                if(!empty($homewrk)){
                    foreach($homewrk as $key=>$value){
                     $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'homeWork'));
                        if(empty($view_n)){
                            $homewrkData[$key]=$value;
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $homewrkData[$key]=$value;
                            }
                        }
                    }
                }
                
                $likev = array('batch',$batch_id);
                $videosData = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt, video_type as videoType','video_lectures use index (id)',array('admin_id'=>$admin_id,'added_at >= '=>$lastLoginTime),'',array('id','desc'),$likev);
                $videos =array();
              
                if(!empty($videosData)){
                    foreach($videosData as $key=>$value){
                        $sub_id =$this->db_model->select_data('subject_id','batch_subjects',array('batch_id'=>$batch_id),1);
                        if(!empty($sub_id)){
                            $sub_name =$this->db_model->select_data('subject_name','subjects',array('id'=>$sub_id[0]['subject_id']),1);
                            if(!empty($sub_name)){
                               // if($sub_name[0]['subject_name']==$value['subject']){
                                    $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id'=>$student_id,'notice_type'=>'videoLecture'));
                                    if(empty($view_n)){
                                        $videos[$key]=$value;
                                        $url = $value['url'];
                                        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                        $videos[$key]['videoId']=$match[1];
                                    }else{
                                        if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                            $videos[$key]=$value;
                                            $url = $value['url'];
                                        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                        $videos[$key]['videoId']=$match[1];
                                        }
                                    }
                            //  }
                            }
                        }
                        
                    }
                }
                
                $vacancyData = $this->db_model->select_data('id,title,description,start_date as startDate,last_date as lastDate,mode,files,status,admin_id as adminId,added_at as addedAt','vacancy use index (id)',array('admin_id'=>$admin_id,'last_date >= '=> date("Y-m-d"),'status'=>1,'added_at >='=>$lastLoginTime),'',array('id','desc'));
                $vacancy=array();
                if(!empty($vacancyData)){
                    foreach($vacancyData as $key=>$value){
                        $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'vacancy'));
                        if(empty($view_n)){
                            $vacancy[$key]=$value;
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $vacancy[$key]=$value;
                            }
                        }
                    }
                }
                
                $noticesData = $this->db_model->select_data('`id`, `title`, `description`, `notice_for` as noticeFor, `status`, `date`, `admin_id` as adminId, `student_id` as studentId, `teacher_id` as teacherId, `added_at` as addedAt, `added_by` as addedBy, `read_status` as readStatus','notices use index (id)',"admin_id = $admin_id AND status = 1 AND (notice_for = 'Student' OR notice_for = 'Both' OR student_id = $student_id) AND added_at >= '$lastLoginTime' AND date >= CURDATE()",'',array('id','desc'));
                
                $notices=array();
                if(!empty($noticesData)){
                    foreach($noticesData as $key=>$value){
                        $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id'=>$student_id,'notice_type'=>'notices'));
                        if(empty($view_n)){
                            $notices[$key]=$value;
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $notices[$key]=$value;
                            }
                        }
                    }
                }
                
                $practice_paper = $this->db_model->select_data('id,name,time_duration as timeDuration, added_at as addedAt, total_question as totalQuestion','exams use index (id)',array('type'=>2,'admin_id'=>$admin_id,'status'=>1,'batch_id'=>$batch_id,'added_at >='=>$lastLoginTime),'',array('id','desc'));
              
                 $newexampaper_test = array();       
                if(!empty($practice_paper)){
                    foreach($practice_paper as $key=>$pexam){
                            $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id'=>$student_id,'notice_type'=>'practicePaper'));
                            $paperstatus = $this->db_model->select_data('id','practice_result use index (id)',array('admin_id' => $admin_id,'student_id' => $student_id,'paper_id' => $pexam['id']),1);
                         
                            if(empty($paperstatus) && empty($view_n)){
                               // array_push($newexampaper_test,$pexam); 
                               $newexampaper_test[$key]=$pexam;
                            }else{
                                if(!empty($view_n)){
                        
                                    if(strtotime($view_n[0]['views_time'])<strtotime($pexam['addedAt'])){
                                        $newexampaper_test[$key]=$pexam;
                                        
                                      }
                                  
                                }
                            }
                        }
                    }
                    
                    
                $mock_paper = $this->db_model->select_data('id,name,time_duration as timeDuration, added_at as addedAt, total_question as  totalQuestion,mock_sheduled_date as mockSheduledDate,mock_sheduled_time as mockSheduledTime','exams use index (id)',array('type' => 1,'admin_id' => $admin_id,'status' => 1,'batch_id' => $batch_id,'mock_sheduled_date >=' => date('Y-m-d'),'added_at >='=>$lastLoginTime),'',array('id','desc'));
             
                $mockPaper = array();            
                if(!empty($mock_paper)){
                    foreach($mock_paper as $exam){
                         
                            // $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'mockPaper'));
                            $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id'=>$student_id,'notice_type'=>'mockPaper'));
                            $paperstatus = $this->db_model->select_data('id','mock_result use index (id)',array('admin_id' => $admin_id,'student_id' => $student_id,'paper_id' => $exam['id']),1);
                            
                          if($exam['mockSheduledDate']==date('Y-m-d')){
                               
                                if(strtotime($exam['mockSheduledTime'])>strtotime(date('H:i:s'))){
                                   
                                    if(empty($paperstatus) && empty($view_n)){
                                    array_push($mockPaper,$exam); 
                                    }else{
                                    
                                        if(!empty($view_n)){
                                            if(strtotime($view_n[0]['views_time'])<strtotime($exam['addedAt'])){
                                                //$new_exampaper=$exam;
                                                array_push($mockPaper,$exam);
                                              }
                                          
                                        }
                                    }
                                }
                            }else{
                               
                                if(empty($paperstatus) && empty($view_n)){
                                    
                                array_push($mockPaper,$exam); 
                                    }else{
                                       
                                        if(!empty($view_n)){
                                            if(strtotime($view_n[0]['views_time'])<strtotime($exam['addedAt'])){
                                                array_push($mockPaper,$exam);
                                              }
                                          
                                        }
                                    }
                            }
                         
                            
                        }
                    }
                  
                $addNewBook=array();
                $like = array('batch','"'.$batch_id.'"');
    		    $book_pdf =$this->db_model->select_data('id,admin_id as adminId, title, batch, topic, subject, file_name as fileName,  status, added_by as addedBy, added_at as addedAt','book_pdf use index (id)',array('status'=>1,'added_at >= '=>$lastLoginTime),'',array('id','desc'),$like);
    		    if(!empty($book_pdf)){
    		        foreach($book_pdf as $key=>$value){
    		            $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'book_notes_paper'));
                        if(empty($view_n)){
                            $addNewBook[$key]=$value;
                            $addNewBook[$key]['url']= base_url('uploads/book/');
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $addNewBook[$key]=$value;
                                $addNewBook[$key]['url']= base_url('uploads/book/');
                            }
                        }
    		        }
    		    }
    		    
    		    $addNewNotes=array();
    		    $notes_pdf =$this->db_model->select_data('id,admin_id as adminId, title, batch, topic, subject, file_name as fileName,  status, added_by as addedBy, added_at as addedAt','notes_pdf use index (id)',array('status'=>1,'added_at >= '=>$lastLoginTime),'',array('id','desc'),$like);
    		    if(!empty($notes_pdf)){
    		        foreach($notes_pdf as $key=>$value){
    		            
    		            $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'book_notes_paper'));
                        if(empty($view_n)){
                            $addNewNotes[$key]=$value;
                            $addNewNotes[$key]['url']= base_url('uploads/notes/');
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $addNewNotes[$key]=$value;
                                $addNewNotes[$key]['url']= base_url('uploads/notes/');
                            }
                        }
    		        }
    		    }
    		    
    		    $addOldPaper=array();
                $newexampaper =$this->db_model->select_data('id,admin_id as adminId, title, batch, topic, subject, file_name as fileName,  status, added_by as addedBy, added_at as addedAt','old_paper_pdf use index (id)',array('status'=>1,'added_at >= '=>$lastLoginTime),'',array('id','desc'),$like);
    		    
    		    if(!empty($newexampaper)){
    		        foreach($newexampaper as $key=>$value){
    		            
    		            $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'book_notes_paper'));
                        if(empty($view_n)){
                            $addOldPaper[$key]=$value;
                            $addOldPaper[$key]['url']= base_url('uploads/oldpaper/');
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $addOldPaper[$key]=$value;
                                $addOldPaper[$key]['url']= base_url('uploads/oldpaper/');
                            }
                        }
    		        }
    		    }
                
                $arr = array(
                    'status'=>'true',
                    'extraClass'=> $extraClass,
                    'homeWork'=>$homewrkData,
                    'videoLecture'=>$videos,
                    'vacancy'=>$vacancy,
                    'notices'=>$notices,
                    'practicePaper'=>$newexampaper_test,
                    'mockPaper'=>$mockPaper,
                    'addOldPaper'=>$addOldPaper,
                    'addNewBook'=>$addNewBook,
                    'addNewNotes'=>$addNewNotes,
                ); 
                
            }else{
                $arr = array('status'=>'false','msg'=>$this->lang->line('ltr_its_first_login'));
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            ); 
        }
        echo json_encode($arr);
    }
    
    function viewNotificationStatus($student_id='',$notice_type=''){
        $data = $_REQUEST;
        if(!empty($student_id) && !empty($notice_type)){
            
           
             $data_arr=array();
             $cu_date = date('Y-m-d H:i:s');
             $data_arr['student_id']=$student_id;
             $data_arr['notice_type']=$notice_type;
             $noticeD = $this->db_model->select_data('*','views_notification_student',array('student_id'=>$student_id,'notice_type'=>$notice_type),1);
             if(!empty($noticeD)){
                 $this->db_model->update_data_limit('views_notification_student ',array('views_time'=>$cu_date),array('n_id'=>$noticeD[0]['n_id']),1);
             }else{
                 $data_arr = $this->security->xss_clean($data_arr);
                 $ins = $this->db_model->insert_data('views_notification_student',$data_arr);
                
             }
        }
                
            return;
            
    }
    
    function getLiveClassData()
    {   
        $data = $_REQUEST;
        if(isset($data['batch_id'])){
           
            $setting = $this->db_model->select_data('meeting_number as meetingNumber,password','live_class_setting',array('batch'=>$data['batch_id'],'status'=>1),'',array('id','desc'));
            $datasdk = $this->db_model->select_data('zoom_api_key,zoom_api_secret','live_class_setting',array('batch'=>$data['batch_id']))[0];
            $setting[0]['sdkKey']=$datasdk['zoom_api_key'];
            $setting[0]['sdkSecret']=$datasdk['zoom_api_secret'];
            if (!empty($setting)){
                $arr = array(
                    'data' => $setting,
                    'status' => 'true',
                    'msg' => $this->lang->line('ltr_fetch_successfully'),
                );
            }else{
                $arr = array(
                    'status' => 'false',
                    'msg' => $this->lang->line('ltr_no_record_msg')
                );
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            ); 
        }
        echo json_encode($arr);
    }
    function getAttendance(){ 
        $data = $_REQUEST;
        if(isset($data['student_id']) && isset($data['month']) && isset($data['year'])){
            $student_id = $data['student_id'];
            $year=$data['year'];
            $month=$data['month'];
            $like = array('date',$year.'-'.$month);
            $attendance = $this->db_model->select_data('student_id as studentId,added_id as addedId,date,time','attendance use index (id)',array('student_id'=>$student_id),'','',$like,'','');
            if (!empty($attendance)) {
                $arr = array(
                    'attendance' => $attendance,
                    'status' => 'true',
                    'msg' => $this->lang->line('ltr_fetch_successfully')
                );
            }else{
                $arr = array(
                    'status' => 'false',
                    'msg' => $this->lang->line('ltr_no_record_msg')
                );
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            );
        }
        echo json_encode($arr);
    }

	/**
	 * Normalize clock values to minutes since midnight for late comparison.
	 * Accepts MySQL TIME strings (09:00:00), H:i, or dotted times (10.30).
	 */
	private function attendance_minutes_from_midnight($t)
	{
		$t = trim((string) $t);
		if ($t === '') {
			return null;
		}
		if (preg_match('/^\d{1,2}\.\d{2}$/', $t)) {
			$t = preg_replace('/^(\d{1,2})\.(\d{2})$/', '$1:$2', $t);
		} else {
			$t = preg_replace('/^(\d{1,2}:\d{2}(?::\d{2})?)\.\d+$/', '$1', $t);
		}
		if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $t, $m)) {
			return (int) $m[1] * 60 + (int) $m[2];
		}
		$ts = strtotime('1970-01-01 ' . $t);
		if ($ts) {
			return ((int) date('G', $ts)) * 60 + (int) date('i', $ts);
		}
		return null;
	}

	/** Late if attendance time is after batch start_time (same calendar day). */
	private function attendance_is_late($attendance_time, $batch_start_time)
	{
		$att = $this->attendance_minutes_from_midnight($attendance_time);
		$start = $this->attendance_minutes_from_midnight($batch_start_time);
		if ($att === null || $start === null) {
			return 0;
		}
		return $att > $start ? 1 : 0;
	}

	/** True if student is linked to batch via sudent_batchs, students.batch_id, or multi_batch JSON. */
	private function attendance_student_enrolled_in_batch($student_id, $batch_id)
	{
		$student_id = (int) $student_id;
		$batch_id = (int) $batch_id;
		if ($student_id < 1 || $batch_id < 1) {
			return false;
		}
		if (!empty($this->db_model->select_data('id', 'sudent_batchs', array('student_id' => $student_id, 'batch_id' => $batch_id), 1))) {
			return true;
		}
		$rows = $this->db_model->select_data('batch_id, multi_batch', 'students', array('id' => $student_id, 'status' => 1), 1);
		if (empty($rows)) {
			return false;
		}
		$r = $rows[0];
		$sb = isset($r['batch_id']) ? trim((string) $r['batch_id']) : '';
		if ($sb !== '' && ((int) $sb === $batch_id || preg_match('/\b' . $batch_id . '\b/', $sb))) {
			return true;
		}
		$mb = isset($r['multi_batch']) ? trim((string) $r['multi_batch']) : '';
		if ($mb !== '') {
			$dec = json_decode($mb, true);
			if (is_array($dec)) {
				foreach ($dec as $v) {
					if ((int) $v === $batch_id) {
						return true;
					}
				}
			}
			if (strpos($mb, '"' . $batch_id . '"') !== false || strpos($mb, (string) $batch_id) !== false) {
				return true;
			}
		}
		return false;
	}

    function attendanceList()
    {
        $from_body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($from_body)) {
            $from_body = array();
        }
        $data = array_merge($_REQUEST, $from_body);
        $payload = $this->require_auth_payload(array(), $from_body);
        if ($payload === false) {
            return;
        }

        $ut = strtolower(trim((string) $payload['ut']));

        // STUDENT VIEW: summary = selected month; attendance = whole year (calendar can show every month, e.g. March marks while viewing April).
        if ($ut === 'student') {
            $student_id = (int) $payload['uid'];
            $month_raw = isset($data['month']) ? $data['month'] : null;
            $year_raw = isset($data['year']) ? $data['year'] : null;
            $year = ($year_raw !== null && $year_raw !== '' && (int) $year_raw > 2000)
                ? (int) $year_raw
                : (int) date('Y');
            $m = ($month_raw !== null && $month_raw !== '' && (int) $month_raw >= 1 && (int) $month_raw <= 12)
                ? (int) $month_raw
                : (int) date('m');
            $month = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
            $month_prefix = $year . '-' . $month;

            $year_like = $year . '-%';
            $sql_stu = "SELECT a.id, a.student_id AS studentId, a.added_id AS addedId, a.date, a.time,
                    COALESCE(b.start_time, b2.start_time) AS batchStartTime
                FROM attendance a
                LEFT JOIN batches b ON b.id = a.batch_id AND IFNULL(a.batch_id, 0) > 0
                LEFT JOIN students st ON st.id = a.student_id
                LEFT JOIN batches b2 ON b2.id = st.batch_id
                WHERE a.student_id = ?
                  AND a.date LIKE ?
                ORDER BY a.date DESC, a.id DESC";
            $q_stu = $this->db->query($sql_stu, array($student_id, $year_like));
            $att_rows = $q_stu->result_array();

            $attendance = array();
            $days_present = 0;
            foreach ($att_rows as $ar) {
                $row_date = isset($ar['date']) ? (string) $ar['date'] : '';
                if ($row_date !== '' && strncmp($row_date, $month_prefix, strlen($month_prefix)) === 0) {
                    $days_present++;
                }
                $attendance[] = array(
                    'id' => (int) $ar['id'],
                    'studentId' => (int) $ar['studentId'],
                    'addedId' => (int) $ar['addedId'],
                    'date' => $ar['date'],
                    'attendance_date' => $ar['date'],
                    'time' => $ar['time'],
                    'is_late' => $this->attendance_is_late($ar['time'], isset($ar['batchStartTime']) ? $ar['batchStartTime'] : '')
                );
            }
            $days_in_month = cal_days_in_month(CAL_GREGORIAN, $m, (int) $year);
            $percentage = $days_in_month > 0 ? round(($days_present / $days_in_month) * 100, 2) : 0;

            $pg = $this->parse_api_list_pagination($data);
           	$total_att = count($attendance);
           	$attendance_page = array_slice($attendance, $pg['offset'], $pg['limit']);

            echo json_encode(array(
                'status' => 'true',
                'userType' => 'student',
                'attendance' => !empty($attendance_page) ? $attendance_page : array(),
               	'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total_att),
                'summary' => array(
                    'year' => (int) $year,
                    'month' => $m,
                    'daysPresent' => (int) $days_present,
                    'daysInMonth' => (int) $days_in_month,
                    'attendancePercent' => (float) $percentage
                ),
                'msg' => $this->lang->line('ltr_fetch_successfully')
            ));
            return;
        }

        // TEACHER VIEW: students enrolled in teacher's batch(es) with date-wise attendance (batch_id optional).
        if ($ut === 'teacher') {
            $teacher_id = (int) $payload['uid'];
            if ($teacher_id < 1) {
                echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
                return;
            }

            // Date filter uses attendance.date (YYYY-MM-DD). Use date or attendance_date; omit => today.
            $raw_date = isset($data['date']) ? trim((string) $data['date']) : '';
            $raw_date = trim($raw_date, "\"' \t\n\r\0\x0B");
            if ($raw_date === '' && isset($data['attendance_date'])) {
                $raw_date = trim(trim((string) $data['attendance_date']), "\"' \t\n\r\0\x0B");
            }
            if ($raw_date === '') {
                $date = date('Y-m-d');
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw_date)) {
                $date = $raw_date;
            } else {
                $date = date('Y-m-d');
            }

            $assigned = $this->db_model->select_data('batch_id', 'batch_subjects', array('teacher_id' => $teacher_id), '');
            $batch_ids = array();
            if (!empty($assigned)) {
                foreach ($assigned as $r) {
                    $bid = isset($r['batch_id']) ? (int) $r['batch_id'] : 0;
                    if ($bid > 0) {
                        $batch_ids[] = $bid;
                    }
                }
            }
            $batch_ids = array_values(array_unique($batch_ids));

            if (isset($data['batch_id']) && trim((string) $data['batch_id'], "\"' \t\n\r\0\x0B") !== '') {
                $want = (int) trim(trim((string) $data['batch_id']), "\"' \t\n\r\0\x0B");
                if ($want < 1) {
                    echo json_encode(array('status' => 'false', 'msg' => 'Invalid batch_id'));
                    return;
                }
                if (!in_array($want, $batch_ids, true)) {
                    echo json_encode(array(
                        'status' => 'false',
                        'msg' => 'You are not assigned to this batch'
                    ));
                    return;
                }
                $batch_ids = array($want);
            }

            if (empty($batch_ids)) {
                echo json_encode(array(
                    'status' => 'true',
                    'userType' => 'teacher',
                    'batchId' => 0,
                    'batchIds' => array(),
                    'date' => $date,
                    'attendance_date' => $date,
                    'students' => array(),
                    'presentCount' => 0,
                    'totalStudents' => 0,
                    'msg' => $this->lang->line('ltr_fetch_successfully')
                ));
                return;
            }

            $ph = implode(',', array_fill(0, count($batch_ids), '?'));
            // Rows in attendance for this date and batch (teacher scoped via batch_subjects above).
            // Do not require sudent_batchs: some students have attendance + batch_id but no enrollment row there.
            // Do not filter by attendance.added_id (marker may differ from current teacher id).
            $sql = "SELECT s.id AS studentId, s.name, s.image, a.batch_id AS batchId,
                        a.id AS attendanceId, a.date, a.time,
                        b.start_time AS batchStartTime
                 FROM attendance a
                 INNER JOIN students s ON s.id = a.student_id AND s.status = 1
                 LEFT JOIN batches b ON b.id = a.batch_id AND IFNULL(a.batch_id, 0) > 0
                 WHERE a.date = ?
                   AND (a.batch_id IN (" . $ph . ") OR IFNULL(a.batch_id, 0) = 0)
                 ORDER BY s.name ASC";
            $bind = array_merge(array($date), $batch_ids);
            $query = $this->db->query($sql, $bind);
            $rows = $query->result_array();

            $students = array();
            foreach ($rows as $row) {
                $row_d = !empty($row['date']) ? $row['date'] : $date;
                $students[] = array(
                    'studentId' => (int) $row['studentId'],
                    'name' => $row['name'],
                    'image' => $row['image'],
                    'batchId' => (int) $row['batchId'],
                    'isPresent' => !empty($row['attendanceId']) ? 1 : 0,
                    'is_late' => !empty($row['attendanceId'])
                        ? $this->attendance_is_late(
                            isset($row['time']) ? $row['time'] : '',
                            isset($row['batchStartTime']) ? $row['batchStartTime'] : ''
                        )
                        : 0,
                    'attendanceId' => !empty($row['attendanceId']) ? (int) $row['attendanceId'] : 0,
                    'date' => $row_d,
                    'attendance_date' => $row_d,
                    'time' => !empty($row['time']) ? $row['time'] : ''
                );
            }

            $single_batch_id = count($batch_ids) === 1 ? (int) $batch_ids[0] : 0;

            $present_count = count(array_filter($students, function ($v) { return $v['isPresent'] == 1; }));
            $total_students = count($students);
            $pg = $this->parse_api_list_pagination($data);
            $students_page = array_slice($students, $pg['offset'], $pg['limit']);

            echo json_encode(array(
                'status' => 'true',
                'userType' => 'teacher',
                'batchId' => $single_batch_id,
                'batchIds' => $batch_ids,
                'date' => $date,
                'attendance_date' => $date,
                'students' => $students_page,
                'presentCount' => $present_count,
                'totalStudents' => $total_students,
               	'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total_students),
                'msg' => $this->lang->line('ltr_fetch_successfully')
            ));
            return;
        }

        echo json_encode(array(
            'status' => 'false',
            'msg' => 'Attendance list is currently available for student and teacher only'
        ));
    }

	/**
	 * POST/GET api/user/add-attendance
	 * Auth: Bearer teacher. Body/JSON/form: batch_id (required), student_id or student_ids (required),
	 * attendance_date (required, YYYY-MM-DD), time (required, e.g. 10.30 or 10:30).
	 * Upserts on (student_id, date, batch_id). Response includes date (echo of attendance_date) and attendance_date.
	 */
	public function addAttendance()
	{
		$from_body = json_decode(file_get_contents('php://input'), true);
		if (!is_array($from_body)) {
			$from_body = array();
		}
		$data = array_merge($this->input->post(), $this->input->get(), $from_body);

		$payload = $this->require_auth_payload(array('teacher'), $from_body);
		if ($payload === false) {
			return;
		}
		$teacher_id = (int) $payload['uid'];
		if ($teacher_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
			return;
		}

		$batch_raw = isset($data['batch_id']) ? trim(trim((string) $data['batch_id']), "\"' \t\n\r\0\x0B") : '';
		$batch_id = (int) $batch_raw;
		if ($batch_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id is required'));
			return;
		}

		$assigned = $this->db_model->select_data('id', 'batch_subjects', array('teacher_id' => $teacher_id, 'batch_id' => $batch_id), 1);
		if (empty($assigned)) {
			echo json_encode(array('status' => 'false', 'msg' => 'You are not assigned to this batch'));
			return;
		}

		$batch_row = $this->db_model->select_data('id,admin_id', 'batches', array('id' => $batch_id, 'status' => 1), 1);
		if (empty($batch_row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Batch not found'));
			return;
		}
		$admin_id = isset($batch_row[0]['admin_id']) ? (int) $batch_row[0]['admin_id'] : 0;

		$raw_ad = isset($data['attendance_date']) ? trim(trim((string) $data['attendance_date']), "\"' \t\n\r\0\x0B") : '';
		if ($raw_ad === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw_ad)) {
			echo json_encode(array('status' => 'false', 'msg' => 'attendance_date is required (use YYYY-MM-DD)'));
			return;
		}
		$date = $raw_ad;

		$time_raw = isset($data['time']) ? trim(trim((string) $data['time']), "\"' \t\n\r\0\x0B") : '';
		if ($time_raw === '') {
			echo json_encode(array('status' => 'false', 'msg' => 'time is required'));
			return;
		}
		$time = $time_raw;

		$student_ids = array();
		if (!empty($data['student_ids']) && is_array($data['student_ids'])) {
			foreach ($data['student_ids'] as $sid) {
				$student_ids[] = (int) $sid;
			}
		} elseif (!empty($data['student_ids']) && is_string($data['student_ids'])) {
			foreach (explode(',', $data['student_ids']) as $p) {
				$p = (int) trim($p);
				if ($p > 0) {
					$student_ids[] = $p;
				}
			}
		} elseif (isset($data['student_id']) && trim((string) $data['student_id'], "\"' \t\n\r\0\x0B") !== '') {
			$student_ids[] = (int) trim(trim((string) $data['student_id']), "\"' \t\n\r\0\x0B");
		}
		$student_ids = array_values(array_unique(array_filter($student_ids, function ($v) { return (int) $v > 0; })));

		if (empty($student_ids)) {
			echo json_encode(array('status' => 'false', 'msg' => 'student_id or student_ids is required'));
			return;
		}

		$results = array();
		$any_ok = false;
		foreach ($student_ids as $student_id) {
			$student = $this->db_model->select_data('id,admin_id', 'students', array('id' => $student_id, 'status' => 1), 1);
			if (empty($student)) {
				$results[] = array('studentId' => $student_id, 'status' => 'false', 'msg' => 'Student not found');
				continue;
			}
			if (!$this->attendance_student_enrolled_in_batch($student_id, $batch_id)) {
				$prior = $this->db_model->select_data('id', 'attendance', array('student_id' => $student_id, 'batch_id' => $batch_id), 1);
				if (empty($prior)) {
					$results[] = array('studentId' => $student_id, 'status' => 'false', 'msg' => 'Student is not enrolled in this batch');
					continue;
				}
			}
			$use_admin = $admin_id > 0 ? $admin_id : (int) $student[0]['admin_id'];

			$existing = $this->db_model->select_data('id', 'attendance', array(
				'student_id' => $student_id,
				'date' => $date,
				'batch_id' => $batch_id
			), 1);

			if (!empty($existing)) {
				$att_id = (int) $existing[0]['id'];
				$this->db_model->update_data_limit('attendance', array(
					'time' => $time,
					'added_id' => $teacher_id,
					'admin_id' => $use_admin
				), array('id' => $att_id), 1);
				$results[] = array(
					'studentId' => $student_id,
					'status' => 'true',
					'msg' => 'updated',
					'attendanceId' => $att_id,
					'date' => $date,
					'attendance_date' => $date,
					'time' => $time
				);
			} else {
				$ins_row = $this->security->xss_clean(array(
					'student_id' => $student_id,
					'added_id' => $teacher_id,
					'date' => $date,
					'time' => $time,
					'batch_id' => $batch_id,
					'admin_id' => $use_admin > 0 ? $use_admin : 1
				));
				$this->db_model->insert_data('attendance', $ins_row);
				$att_id = (int) $this->db->insert_id();
				$results[] = array(
					'studentId' => $student_id,
					'status' => 'true',
					'msg' => 'added',
					'attendanceId' => $att_id,
					'date' => $date,
					'attendance_date' => $date,
					'time' => $time
				);
			}
			$any_ok = true;
		}

		$all_ok = count(array_filter($results, function ($r) { return isset($r['status']) && $r['status'] === 'true'; })) === count($results);

		echo json_encode(array(
			'status' => $any_ok ? 'true' : 'false',
			'msg' => $all_ok
				? 'Attendance saved successfully'
				: ($any_ok ? 'Some attendance records could not be saved' : 'Attendance could not be saved'),
			'batchId' => $batch_id,
			'date' => $date,
			'attendance_date' => $date,
			'results' => $results
		), JSON_UNESCAPED_SLASHES);
	}


    function getTopScorer(){ 
        $data = $_REQUEST;
      
        if(isset($data['batch_id'])){
            $batch_id = $data['batch_id'];
            $exam = $this->db_model->select_data('id,name','exams  use index (id)',array('batch_id'=>$batch_id,'type'=>1,'mock_sheduled_date <='=>date('Y-m-d')),'1',array('id','desc'));
            //  echo $this->db->last_query();die;
         
            $top_three = $this->db_model->select_data('mock_result.paper_name as paperName,students.name,students.image,mock_result.percentage','mock_result  use index (id)',array('paper_id'=>$exam[0]['id'],'mock_result.percentage >'=>0),'3',array('mock_result.percentage','desc'),'',array('students','students.id=mock_result.student_id'));
       
            if (!empty($top_three)) {
                $arr = array(
                    'topThree' => $top_three,
                    'filesUrl' => base_url('uploads/students/'),
                    'status' => 'true',
                    
                    'msg' => $this->lang->line('ltr_fetch_successfully')
                );
            }else{
                $arr = array(
                    'status' => 'false',
                    'msg' => $this->lang->line('ltr_no_record_msg')
                );
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            );
        }
        echo json_encode($arr);
    }
    
    function getAcademicRecord(){ 
        $data = $_REQUEST;
        if(isset($data['student_id']) && isset($data['month']) && isset($data['year']) && isset($data['batch_id'])){
            $student_id = $data['student_id'];
            $year=$data['year'];
            $month=$data['month'];
            $like = $year.'-'.$month.'-';
            $batch_id=$data['batch_id'];
            $like_batch_id='"'.$data['batch_id'].'"';
		    $data1['extraClass'] = $this->db_model->countAll('extra_class_attendance',array('student_id'=>$student_id),'','',array('date',$like));
		    $data1['totalExtraClass'] = $this->db_model->countAll('extra_classes','',array('batch_id'=>$like_batch_id),'',array('date',$like));
    	    
    		$data1['practiceResult'] =(int)$this->db_model->custom_slect_query(" COUNT(*) AS `numrows` FROM ( SELECT practice_result.id FROM `practice_result` JOIN `exams` ON `exams`.`id`=`practice_result`.`paper_id` WHERE  `student_id` = '".$student_id."' AND date(added_at) LIKE '%".$like."%' ESCAPE '!' GROUP BY `paper_id` ) a")[0]['numrows'];
    		
    		$data1['totalPracticeTest'] = $this->db_model->countAll('exams',array('batch_id'=>$batch_id,'type'=>2),'','',array('date(added_at)',$like));
    		
		    $data1['mockResult'] = $this->db_model->countAll('mock_result',array('student_id'=>$student_id),'','',array('date',$like));
		    
		    $data1['totalMockTest'] = $this->db_model->countAll('exams',array('batch_id'=>$batch_id,'type'=>1),'','',array('date(added_at)',$like));
		  
            if (!empty($data1)) {
                $arr = array(
                    'academicRecord' => $data1,
                    'status' => 'true',
                    'msg' => $this->lang->line('ltr_fetch_successfully')
                );
            }else{
                $arr = array(
                    'status' => 'false',
                    'msg' => $this->lang->line('ltr_no_record_msg')
                );
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            );
        }
        echo json_encode($arr);
    }
    
    function checkActiveLiveClassJetsi(){ 
        $data = $_REQUEST;
        if(isset($data['batch_id'])){
            $batch_id = $data['batch_id'];
		    $meeting_number = $this->db_model->select_data('meeting_number','jetsi_setting',array('batch'=>$batch_id),'1')[0];
		    $class_data = $this->db_model->select_data('users.name,users.teach_image AS teachImage,subjects.subject_name as subjectName,chapters.chapter_name as chapterName,live_class_history.end_time as endTime,live_class_history.type_class as Type','live_class_history',array('batch_id'=>$batch_id,'type_class'=>2),'1',array('live_class_history.id','desc'),'',array('multiple',array(array('users','users.id = live_class_history.uid'),array('subjects','subjects.id = live_class_history.subject_id'),array('chapters','chapters.id = live_class_history.chapter_id'))));
            if($class_data[0]['Type']==2){
                $meetingId  = $meeting_number['meeting_number'];
            }else{
                $meetingId ="";
            }
            if (!empty($class_data)) {
                if(empty($class_data[0]['endTime'])){
                    $arr = array(
                    'liveClass' => $class_data[0],
                    'meeting_number' =>$meetingId,
                    'filesUrl' => base_url('uploads/teachers/'),
                    'status' => 'true',
                    'msg' => $this->lang->line('ltr_fetch_successfully')
                );
                }else{
                     $arr = array(
                    'status' => 'false',
                    'msg' => $this->lang->line('ltr_no_record_msg')
                );
                }
                
            }else{
                $arr = array(
                    'status' => 'false',
                    'msg' => $this->lang->line('ltr_no_record_msg')
                );
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            );
        }
        echo json_encode($arr);
    }
    function checkActiveLiveClass(){ 
        $data = $_REQUEST;
        if(isset($data['batch_id'])){
            $batch_id = $data['batch_id'];
		    $class_data = $this->db_model->select_data('users.name,users.teach_image AS teachImage,subjects.subject_name as subjectName,chapters.chapter_name as chapterName,live_class_history.end_time as endTime','live_class_history',array('batch_id'=>$batch_id,'type_class'=>1),'1',array('live_class_history.id','desc'),'',array('multiple',array(array('users','users.id = live_class_history.uid'),array('subjects','subjects.id = live_class_history.subject_id'),array('chapters','chapters.id = live_class_history.chapter_id'))));
		   
            if (!empty($class_data)) {
                if(empty($class_data[0]['endTime'])){
                    $arr = array(
                    'liveClass' => $class_data[0],
                    'filesUrl' => base_url('uploads/teachers/'),
                    'status' => 'true',
                    'msg' => $this->lang->line('ltr_fetch_successfully')
                );
                }else{
                     $arr = array(
                    'status' => 'false',
                    'msg' => $this->lang->line('ltr_no_record_msg')
                );
                }
                
            }else{
                $arr = array(
                    'status' => 'false',
                    'msg' => $this->lang->line('ltr_no_record_msg')
                );
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            );
        }
        echo json_encode($arr);
    }
    
    function apply_leave(){
        $data = $_REQUEST;
            if(!empty($data['from_date']) && !empty($data['to_date']) && !empty($data['subject']) && !empty($data['uid'])){
        
                $data_arr['from_date'] = date('Y-m-d', strtotime($data['from_date']));
                $data_arr['to_date'] = date('Y-m-d', strtotime($data['to_date']));
                $data_arr['subject'] = $data['subject'];
                $data_arr['leave_msg'] = $data['leave_msg'];
                $data_arr['student_id'] = $data['uid'];
                $data_arr['admin_id'] = 1;
                $data_arr['status'] = 0; 
                $Datediff = strtotime($data['to_date']) - strtotime($data['from_date']);               
                $data_arr['total_days'] = abs(round($Datediff / 86400)); 
                
                $data_arr = $this->security->xss_clean($data_arr);
                $ins = $this->db_model->insert_data('leave_management',$data_arr);
                if($ins==true){
                    $resp = array('status'=>'true','msg'=>$this->lang->line('ltr_leave_apply_msg'));
                }else{
                    $resp = array('status'=>'false','msg'=>$this->lang->line('ltr_leave_apply_msg'));
                }
            }else{
                $resp = array('status'=>'false','msg'=>$this->lang->line('ltr_missing_parameters_msg')); 
            } 
            echo json_encode($resp,JSON_UNESCAPED_SLASHES);
        
    }
    
    function view_leave(){
        $data = $_REQUEST;
            if(!empty($data['uid'])){
                $student_id = $data['uid'];
                $leave_data = $this->db_model->select_data('id,status, subject, leave_msg as leaveMsg, from_date as fromDate, to_date as toDate, total_days as totalDays, added_at as addedAt','leave_management',array('student_id'=>$student_id),'',array('id','desc'));
		   
                if(!empty($leave_data)){
                    $resp = array('status'=>'true','msg'=>$this->lang->line('ltr_fetch_successfully'), 'leaveData'=>$leave_data);
                }else{
                    $resp = array('status'=>'false','msg'=>$this->lang->line('ltr_no_record_msg'));
                }
            }else{
                $resp = array('status'=>'false','msg'=>$this->lang->line('ltr_missing_parameters_msg')); 
            } 
            echo json_encode($resp,JSON_UNESCAPED_SLASHES);
        
    }
    
    function view_subject_list(){
        $data = $_REQUEST;
            if(!empty($data['batch_id'])){
                $batch_id = $data['batch_id'];
                $arrayData=array();
                if(!empty($batch_id) && empty($data['subject_id']) && empty($data['teacher_id'])){
                    $arrayData= array('batch_id'=>$batch_id);
                    
                }else if(!empty($batch_id) && !empty($data['subject_id']) && empty($data['teacher_id'])){
                    
                    $arrayData= array(
                                    'batch_id'=>$batch_id,
                                    'subject_id'=>$data['subject_id']
                                    );
                    
                }else if(!empty($batch_id) && !empty($data['subject_id']) && !empty($data['teacher_id'])){
                    
                    $arrayData= array(
                                    'batch_id'=>$batch_id,
                                    'subject_id'=>$data['subject_id'],
                                    'teacher_id'=>$data['teacher_id']
                                    );
                    
                }
                
                $sub_data = $this->db_model->select_data('id,teacher_id as teacherId, subject_id as subjectId, chapter','batch_subjects',$arrayData,'',array('id','desc'));
                
                if(!empty($sub_data)){
                    foreach($sub_data as $key=>$value){
                        
                        $sub_name = $this->db_model->select_data('id, subject_name','subjects',array('id'=>$value['subjectId']),'',array('id','desc'));
                        
                        $sub_data[$key]['subjectName']= !empty($sub_name)?$sub_name[0]['subject_name']:'';
                        if(!empty($batch_id) && (!empty($data['subject_id']) || !empty($data['teacher_id']))){
                            $teacher_name = $this->db_model->select_data('id, name','users',array('id'=>$value['teacherId']),'',array('id','desc'));
                            
                            $sub_data[$key]['teacherData']= !empty($teacher_name)?$teacher_name:'';
                            
                            $chap_id =implode(', ', json_decode($value['chapter']));
                            $chapterCon = "id in ($chap_id)";
        	                $chapterName = $this->db_model->select_data('id,chapter_name','chapters',$chapterCon,'');
        	                if(!empty($chapterName)){
        	                    
                                $sub_data[$key]['chapterData']=$chapterName;
        	                }else{
        	                    $sub_data[$key]['chapterData']='';
        	                }
                    
                        }
                        
                        unset($sub_data[$key]['teacherId']);
                        unset($sub_data[$key]['chapter']);
                        
    	                
                    }
                    
                    
                    
                    $resp = array('status'=>'true','msg'=>$this->lang->line('ltr_fetch_successfully'), 'subjectData'=>$sub_data);
                }else{
                    $resp = array('status'=>'false','msg'=>$this->lang->line('ltr_no_record_msg'));
                }
            }else{
                $resp = array('status'=>'false','msg'=>$this->lang->line('ltr_missing_parameters_msg')); 
            } 
            echo json_encode($resp,JSON_UNESCAPED_SLASHES);
        
    }
	
	function doubts_class_ask(){
        $data = $_REQUEST;
      
		if(!empty($data['batch_id']) && !empty($data['subject_id']) && !empty($data['student_id'])){
			$batch_id = $data['batch_id'];
			
			$arrayData=array(
							'student_id'=>$data['student_id'],
							'batch_id'=>$data['batch_id'],
							'subjects_id'=>$data['subject_id'],
							);
			if(!empty($data['teacher_id'])){
				$arrayData['teacher_id']= $data['teacher_id'];
			}
			
			if(!empty($data['chapter_id'])){
				$arrayData['chapters_id']= $data['chapter_id'];
			}
			
			if(!empty($data['description'])){
				$arrayData['users_description']= $data['description'];
			}
			
			
		    	 $adm_id = $this->db_model->select_data('*','sudent_batchs',array('student_id'=>$data['student_id'],'batch_id'=>$data['batch_id']));
			$arrayData['admin_id']= $adm_id[0]['admin_id'];
			$checkusers = $this->db_model->select_data('doubt_id','student_doubts_class',array('teacher_id'=>$data['teacher_id'],'batch_id'=>$data['batch_id'],'status'=>0,'student_id'=>$data['student_id'],'subjects_id'=>$data['subject_id'],'chapters_id'=>$data['chapter_id']),'',array('doubt_id ','desc'));
			if(empty($checkusers)){
				/*$coundUsers = count($this->db_model->select_data('doubt_id ','student_doubts_class',array('teacher_id'=>$data['teacher_id'],'batch_id'=>$data['batch_id']),'',array('doubt_id ','desc')));
				
				if($coundUsers<=10){*/
					$data_arr = $this->security->xss_clean($arrayData);
					$ins = $this->db_model->insert_data('student_doubts_class',$data_arr);
					
					$doubts_data = array(
									'doubtId'=>$ins,
									'teacherId'=>$data['teacher_id'],
									'studentId'=>$data['student_id'],
									'batchId'=>$data['batch_id'],
									'subjectsId'=>$data['subject_id'],
									'chaptersId'=>$data['chapter_id'],
									'usersDescription'=>$data['description'],
									'teacherDescription'=>'',
									'appointmentDate'=>'',
									'appointmentTime'=>'',
									'createAt'=>date('Y-m-d H:i:s'),
									'status'=>0
									);
					
					$resp = array('status'=>'true','msg'=>$this->lang->line('ltr_doubt_request_msg'), 'doubtsData'=>$doubts_data);
				/*}else{
					$resp = array('status'=>'false','msg'=>$this->lang->line('ltr_something_msg'));
				}*/
				
			}else{
				$resp = array('status'=>'false','msg'=>$this->lang->line('ltr_doubt_request_already_msg'));
			}
		}else{
			$resp = array('status'=>'false','msg'=>$this->lang->line('ltr_missing_parameters_msg')); 
		} 
		echo json_encode($resp,JSON_UNESCAPED_SLASHES);
        
    }
	
	function get_doubts_ask(){
        $data = $_REQUEST;
		if(!empty($data['student_id'])){
			$adm_id = $this->db_model->select_data('*','students',array('id'=>$data['student_id']));
	    	if($this->session->userdata('role')==1){
                $cond = array('admin_id'=>$_SESSION['uid']);
                if(!empty($get['subject'])){
                    $cond.=" subjects_id=".$get['subject'];
                }
            }else{
                $cond = "teacher_id = $tid";
                if(!empty($get['subject'])){
                    $cond.=" AND subjects_id=".$get['subject'];
                }
            }
			$checkusers = $this->db_model->select_data('doubt_id as doubtId, student_id as studentId, teacher_id as teacherId, batch_id as batchId, subjects_id as subjectsId, chapters_id as chaptersId, users_description as usersDescription, teacher_description as teacherDescription, appointment_date as appointmentDate, appointment_time as appointmentTime, create_at as createAt, status','student_doubts_class',array('student_id'=>$data['student_id'],'batch_id'=>$adm_id[0]['batch_id']),'',array('doubtId ','desc'));
			if(!empty($checkusers)){
				
				foreach($checkusers as $key=>$value){
					
					$teaNam= $this->db_model->select_data('name','users',array('id'=>$value['teacherId']),'',array('id ','desc'));
					$batchNam= $this->db_model->select_data('batch_name','batches',array('id'=>$value['batchId']),'',array('id ','desc'));
					$subNam= $this->db_model->select_data('subject_name','subjects',array('id'=>$value['subjectsId']),'',array('id ','desc'));
					$chrNam= $this->db_model->select_data('chapter_name','chapters',array('id'=>$value['chaptersId']),'',array('id ','desc'));
					
					$checkusers[$key]['teacherName'] = !empty($teaNam[0]['name'])?$teaNam[0]['name']:'';
					$checkusers[$key]['batchName'] = !empty($batchNam[0]['batch_name'])?$batchNam[0]['batch_name']:'';
					$checkusers[$key]['subjectName'] = !empty($subNam[0]['subject_name'])?$subNam[0]['subject_name']:'';
					$checkusers[$key]['chapterName'] = !empty($chrNam[0]['chapter_name'])?$chrNam[0]['chapter_name']:'';
				}
				$resp = array('status'=>'true','msg'=>$this->lang->line('ltr_fetch_successfully'), 'doubtsData'=>$checkusers);
				
			}else{
				$resp = array('status'=>'false','msg'=>$this->lang->line('ltr_no_record_msg'));
			}
		}else{
			$resp = array('status'=>'false','msg'=>$this->lang->line('ltr_missing_parameters_msg')); 
		} 
		echo json_encode($resp,JSON_UNESCAPED_SLASHES);
        
    }
    
	function pay_batch_fee(){
			$data = $_REQUEST;
		
			if(!empty($data['student_id']) && !empty($data['batch_id'])){
				
				$batch_type =$this->db_model->select_data('batch_type','batches use index (id)',array('id'=>$data['batch_id']),1)[0]['batch_type'];
				
				if($batch_type==2){
					if(!empty($data['transaction_id']) && !empty($data['amount'])){
					
						$chackPayment =$this->db_model->select_data('*','student_payment_history ',array('batch_id'=>$data['batch_id'],'student_id'=>$data['student_id']));
					
						if(empty($chackPayment)){
							$data_pay=array(
							              'student_id'=>$data['student_id'],
										  'batch_id'=>$data['batch_id'],
										  'transaction_id'=>$data['transaction_id'],
										  'amount'=>$data['amount'],
											);
							$data_pay = $this->security->xss_clean($data_pay);
							$ins = $this->db_model->insert_data('student_payment_history',$data_pay);
						}else{
							$arr = array(
								'status'=>'false',
								'msg'=>$this->lang->line('ltr_payment_already')
							);
							echo json_encode($arr);
						    die();
						}
						
					}else{
						$arr = array(
							'status'=>'false',
							'msg'=>$this->lang->line('ltr_missing_parameters_msg'),

						);
						echo json_encode($arr);
						die();
					}
					
				}
				
					//update app version and login status                  
					$this->db_model->update_data_limit('students use index (id)',array('status'=>1,'batch_id'=>$data['batch_id'],'payment_status'=>1),array('id'=>$data['student_id']),1);
					
					$batchData =$this->db_model->select_data('*','batches use index (id)',array('id'=>$data['batch_id']),1);
					
				    $studentData =$this->db_model->select_data('id as studentId,email as userEmail,name as fullName,enrollment_id as enrollmentId,contact_no as mobile,app_version as versionCode, batch_id as batchId,admin_id as adminId,admission_date as admissionDate, image,password,token','students use index (id)',array('id'=>$data['student_id']),1);
					$studentData[0]['batchName']=!empty($batchData)?$batchData[0]['batch_name']:'';
					$studentData[0]['image']= base_url('uploads/students/').$studentData[0]['image'];
					$studentData[0]['password'] = $studentData[0]['password'];
					
					$studentData[0]['transactionId'] = !empty($data['transaction_id'])?$data['transaction_id']:'';
    				$studentData[0]['amount'] = !empty($data['amount'])?$data['amount']:'';
					
				    $arr = array('status'=>'true', 'msg' =>$this->lang->line('ltr_payment_processed'),'studentData'=>$studentData[0]);
				
				
			}else{
				$arr = array(
					'status'=>'false',
					'msg'=>$this->lang->line('ltr_missing_parameters_msg')
				); 
			}
			echo json_encode($arr);
    }
	
	function get_payment_history(){
		$from_body = json_decode(file_get_contents('php://input'), true);
		if (!is_array($from_body)) {
			$from_body = array();
		}
		$data = array_merge($_REQUEST, $from_body);

		$payload = $this->require_auth_payload(array('student', 'institute'), $from_body);
		if ($payload === false) {
			return;
		}

		$pg = $this->parse_api_list_pagination($data);
		$user_type = isset($payload['ut']) ? strtolower(trim((string) $payload['ut'])) : '';
		$total = 0;
		$payData = array();

		if ($user_type === 'student') {
			$student_id = (int) $payload['uid'];
			if ($student_id < 1) {
				$resp = array('status' => 'false', 'msg' => $this->lang->line('ltr_missing_parameters_msg'));
				echo json_encode($resp, JSON_UNESCAPED_SLASHES);
				return;
			}

			$cond = array('student_id' => $student_id);
			if (!empty($data['batch_id'])) {
				$cond['batch_id'] = (int) $data['batch_id'];
			}

			$this->db->reset_query();
			$this->db->from('student_payment_history');
			$this->db->where($cond);
			$total = (int) $this->db->count_all_results();

			$payData = $this->db_model->select_data(
				'id,batch_id as batchId,transaction_id as transactionId,mode,amount,create_at as createAt,admin_id as adminId',
				'student_payment_history',
				$cond,
				array($pg['limit'], $pg['offset']),
				array('id', 'desc')
			);
		} elseif ($user_type === 'institute') {
			$institute_id = (int) $payload['uid'];
			if ($institute_id < 1) {
				$resp = array('status' => 'false', 'msg' => $this->lang->line('ltr_missing_parameters_msg'));
				echo json_encode($resp, JSON_UNESCAPED_SLASHES);
				return;
			}

			$batchRows = $this->db_model->select_data('id', 'batches', array('admin_id' => $institute_id));
			$batchIds = array();
			if (!empty($batchRows)) {
				foreach ($batchRows as $br) {
					$batchIds[] = (int) $br['id'];
				}
			}
			if (empty($batchIds)) {
				$resp = array('status' => 'false', 'msg' => $this->lang->line('ltr_no_record_msg'));
				echo json_encode($resp, JSON_UNESCAPED_SLASHES);
				return;
			}

			if (!empty($data['batch_id'])) {
				$bid = (int) $data['batch_id'];
				if (!in_array($bid, $batchIds, true)) {
					$resp = array('status' => 'false', 'msg' => $this->lang->line('ltr_no_record_msg'));
					echo json_encode($resp, JSON_UNESCAPED_SLASHES);
					return;
				}
				$hist_cond = array('batch_id' => $bid);
				$this->db->reset_query();
				$this->db->from('student_payment_history');
				$this->db->where($hist_cond);
				$total = (int) $this->db->count_all_results();

				$payData = $this->db_model->select_data(
					'id,student_id as studentId,batch_id as batchId,transaction_id as transactionId,mode,amount,create_at as createAt,admin_id as adminId',
					'student_payment_history',
					$hist_cond,
					array($pg['limit'], $pg['offset']),
					array('id', 'desc')
				);
			} else {
				$this->db->reset_query();
				$this->db->from('student_payment_history');
				$this->db->where_in('batch_id', $batchIds);
				$total = (int) $this->db->count_all_results();

				$this->db->reset_query();
				$this->db->select('id,student_id as studentId,batch_id as batchId,transaction_id as transactionId,mode,amount,create_at as createAt,admin_id as adminId', false);
				$this->db->from('student_payment_history');
				$this->db->where_in('batch_id', $batchIds);
				$this->db->order_by('id', 'desc');
				$this->db->limit($pg['limit'], $pg['offset']);
				$payData = $this->db->get()->result_array();
			}
		} else {
			$resp = array('status' => 'false', 'msg' => 'Unauthorized: invalid token user');
			echo json_encode($resp, JSON_UNESCAPED_SLASHES);
			return;
		}

		if (!empty($payData)) {
			foreach ($payData as $key => $value) {
				$batchData = $this->db_model->select_data('*', 'batches use index (id)', array('id' => $value['batchId']), 1);

				$payData[$key]['batchName'] = !empty($batchData) ? $batchData[0]['batch_name'] : '';
				$payData[$key]['startDate'] = !empty($batchData) ? $batchData[0]['start_date'] : '';
				$payData[$key]['endDate'] = !empty($batchData) ? $batchData[0]['end_date'] : '';
				$payData[$key]['startTime'] = !empty($batchData) ? $batchData[0]['start_time'] : '';
				$payData[$key]['endTime'] = !empty($batchData) ? $batchData[0]['end_time'] : '';
				$payData[$key]['batchType'] = !empty($batchData) ? $batchData[0]['batch_type'] : '';
				$payData[$key]['batchPrice'] = !empty($batchData) ? $batchData[0]['batch_price'] : '';
				$payData[$key]['description'] = !empty($batchData) ? $batchData[0]['description'] : '';
				$payData[$key]['status'] = !empty($batchData) ? $batchData[0]['status'] : '';
				$payData[$key]['batchOfferPrice'] = !empty($batchData) ? $batchData[0]['batch_offer_price'] : '';
				$payData[$key]['currencyCode'] = $this->general_settings('currency_code');
				$payData[$key]['currencyDecimalCode'] = $this->general_settings('currency_decimal_code');

				if ($user_type === 'institute' && isset($value['studentId'])) {
					$stu = $this->db_model->select_data(
						'name,email,contact_no,image',
						'students use index (id)',
						array('id' => (int) $value['studentId']),
						1
					);
					$payData[$key]['studentName'] = !empty($stu) ? $stu[0]['name'] : '';
					$payData[$key]['studentEmail'] = !empty($stu) ? $stu[0]['email'] : '';
					$payData[$key]['studentMobile'] = !empty($stu) ? $stu[0]['contact_no'] : '';
					$img = !empty($stu) ? $stu[0]['image'] : '';
					$payData[$key]['studentImage'] = $img !== '' ? base_url('uploads/students/') . $img : '';
				}
			}
			$resp = array(
				'status' => 'true',
				'msg' => $this->lang->line('ltr_fetch_successfully'),
				'paymentData' => $payData,
				'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total),
			);
		} else {
			$resp = array(
				'status' => 'false',
				'msg' => $this->lang->line('ltr_no_record_msg'),
				'paymentData' => array(),
				'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], isset($total) ? $total : 0),
			);
		}
		echo json_encode($resp, JSON_UNESCAPED_SLASHES);
    }

	/**
	 * Same as get_payment_history — REST-style name for mobile routes.
	 */
	function paymentHistory()
	{
		$this->get_payment_history();
	}
    
    function convertCurrency($amount,$from_currency,$to_currency){
          $apikey = $this->general_settings('currency_converter_api');
        
          $from_Currency = urlencode($from_currency);
          $to_Currency = urlencode($to_currency);
          $query =  "{$from_Currency}_{$to_Currency}";
        
          // change to the free URL if you're using the free version
          @$json = file_get_contents("https://free.currconv.com/api/v7/convert?q={$query}&compact=ultra&apiKey={$apikey}");
          $obj = json_decode($json, true);
        
          $val = floatval($obj["$query"]);
        
        
          $total = $val * $amount;
          return number_format($total, 2, '.', '');
        }
    
        public function SendMail($tomail='', $subject='', $msg=''){
            $frommail =$this->general_settings('smtp_mail');
            $frompwd =$this->general_settings('smtp_pwd');
            $title = $this->db_model->select_data('site_title','site_details','',1,array('id','desc'))[0]['site_title'];

            $this->load->library('email');
            $config = array();
            $config['protocol'] = $this->general_settings('server_type');
            $config['smtp_host'] = $this->general_settings('smtp_host');
            $config['smtp_port'] = $this->general_settings('smtp_port');
            $config['smtp_user'] = $frommail;
            $config['smtp_pass'] = $frompwd;
            $config['charset'] = "utf-8";
            $config['mailtype'] = "html";
            $config['smtp_crypto'] = $this->general_settings('smtp_encryption');
            $config['newline'] = "\r\n";
             $this->email->initialize($config);
            // Set to, from, message, etc.
            
            $this->email->from($frommail, $title);
            $this->email->to($tomail);
            
            $this->email->subject($subject);
            $this->email->message($msg);
            
           @$this->email->send();
            return true;
        }
        function check_batch(){
            $data = $_REQUEST;
    		if(!empty($data['email'])){
    		    $check_email = $this->db_model->select_data('*','students',array('email'=>trim($_POST['email'])));
    		    $check_email_t = $this->db_model->select_data('*','users',array('email'=>trim($_POST['email'])));
    		    if(!empty($check_email_t)){
    		        $resp = array('isEmailExist'=>'true');
    		        echo json_encode($resp,JSON_UNESCAPED_SLASHES);
    		        die();
    		    }
    		    if(!empty($check_email)){
    		        $check_batch = $this->db_model->select_data('*','batches',array('id'=>$check_email[0]['batch_id'],'status'=>1));
    		        if(!empty($check_batch)){
    		            $resp = array('isEmailExist'=>'true');
    		        }else{
    		            $resp = array('isEmailExist'=>'false');
    		        }
    		    }else{
    		        $resp = array('isEmailExist'=>'false');
    		    }
    		}else{
    			$resp = array('status'=>'false','msg'=>$this->lang->line('ltr_missing_parameters_msg')); 
    		} 
    		echo json_encode($resp,JSON_UNESCAPED_SLASHES);
        }
         function reset_password(){
            $data = $_REQUEST;
            if(!empty($data['email'])){
                $check_email = $this->db_model->select_data('*','students',array('email'=>trim($_POST['email'])));
                if(!empty($check_email)){
                     $a=str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
                    $pwd = substr($a, 0, 5);
                    
                    $subj = 'Recover your account password '.$this->common->siteTitle;
                    $em_msg = 'Hi '.ucwords($userDetails[0]['name']).',<br/><br/>We have received your request to reset your account password.<br/><br/>Here is your new password : '.$pwd.'<br/><br/> This is an auto-generated email. Please do not reply to this email.';
                    $response = $this->SendMail($_POST['email'],$subj, $em_msg);
                   
                    if($response==true){
                        $data = array( 
                            'password'=>md5($pwd)
                        );
                        $data = $this->security->xss_clean($data);
                        $this->db_model->update_data('students',$data, array('email'=>$_POST['email']));
    
                        $resp = array(
                            'status'=>'true',
                            'msg'=>'We\'ve sent an email to '.$_POST['email'].'.' 
                        );
                    }
                    else{
                        $resp = array(
                            'status'=>'false',
                            'msg'=>$this->lang->line('ltr_something_msg')
                        );
                    }
                }else{
                    $resp = array('status'=>'false','msg'=>$this->lang->line('ltr_email_not_exists_msg'));
                }
            }else{
                $resp = array('status'=>'false','msg'=>$this->lang->line('ltr_missing_parameters_msg')); 
            } 
            echo json_encode($resp,JSON_UNESCAPED_SLASHES);
        }
        
        //new update
        function list_book_notes_paper(){
            $data = $_REQUEST;
    		if(!empty($data['student_id']) && !empty($data['batch_id'])){
    		    
    		    if(!empty($data['student_id'])){
                    $this->viewNotificationStatus($data['student_id'],'book_notes_paper');
                 }
    		    $like = json_decode(array('batch','"'.$data['batch_id'].'"'));
    		    
    		    
    		    $book_pdf =$this->db_model->select_data('id,admin_id as adminId, title, batch, topic, subject, file_name as fileName,  status, added_by as addedBy, added_at as addedAt','book_pdf use index (id)',array('status'=>1,'batch'=>$data['batch_id']),'',array('id','desc'),$like);
    		    if(!empty($book_pdf)){
    		        foreach($book_pdf as $key=>$value){
    		            $book_pdf[$key]['url']= base_url('uploads/book/');
    		        }
    		    }
    		    $notes_pdf =$this->db_model->select_data('id,admin_id as adminId, title, batch, topic, subject, file_name as fileName,  status, added_by as addedBy, added_at as addedAt','notes_pdf use index (id)',array('status'=>1,'batch'=>$data['batch_id']),'',array('id','desc'),$like);
    		    if(!empty($notes_pdf)){
    		        foreach($notes_pdf as $key=>$value){
    		            $notes_pdf[$key]['url']= base_url('uploads/notes/');
    		        }
    		    }
    		    
                $newexampaper =$this->db_model->select_data('id,admin_id as adminId, title, batch, topic, subject, file_name as fileName,  status, added_by as addedBy, added_at as addedAt','old_paper_pdf use index (id)',array('status'=>1,'batch'=>$data['batch_id']),'',array('id','desc'),$like);
    		    if(!empty($newexampaper)){
    		        foreach($newexampaper as $key=>$value){
    		            $newexampaper[$key]['url']= base_url('uploads/oldpaper/');
    		        }
    		    }
    		    
    		     $resp = array(
                            'status'=>'true',
                            'msg'=>$this->lang->line('ltr_fetch_successfully'),
                            'bookPdf'=> $book_pdf,
                            'notesPdf'=> $notes_pdf,
                            'oldPapers'=>$newexampaper,
                        );
    		    
    		}else{
                $resp = array('status'=>'false','msg'=>$this->lang->line('ltr_missing_parameters_msg')); 
            } 
            echo json_encode($resp,JSON_UNESCAPED_SLASHES);
        }
        
        function changeMyBatch(){
         
            $data = $_REQUEST;
            	$this->db_model->insert_data('temp_data',array('temp'=>json_encode($data)));
    		if(!empty($data['student_id']) && !empty($data['batch_id'])){
    		    
    		    $checkBatch =$this->db_model->select_data('*','sudent_batchs',array('batch_id'=>$data['batch_id'],'student_id'=>$data['student_id']),1);
    		    if(!empty($checkBatch)){
    		        
        		    $studentData =$this->db_model->select_data('id as studentId,email as userEmail,name as fullName,enrollment_id as enrollmentId,contact_no as mobile,app_version as versionCode, batch_id as batchId,admin_id as adminId,admission_date as admissionDate, image, token','students use index (id)',array('id'=>$data['student_id']),1);
    				$studentData[0]['batchName']='';
    				$studentData[0]['image']= base_url('uploads/students/').$studentData[0]['image'];
    				$studentData[0]['password'] = $password;
    				$studentData[0]['paymentType'] = $this->general_settings('payment_type');
    				$studentData[0]['languageName'] = $this->general_settings('language_name');
    				
    				 //check batch type
    				$batch_type =$this->db_model->select_data('*','batches use index (id)',array('id'=>$data['batch_id']),1);
    				
    			    if(!empty($batch_type)){
    			        $studentData[0]['batchName']= $batch_type[0]['batch_name'];
    			        $studentData[0]['batchId']= $batch_type[0]['id'];
    			        if($batch_type[0]['batch_type']==2){
        			            
        			        $batch_his=   $this->db_model->select_data('*','student_payment_history',array('batch_id'=>$data['batch_id'], 'student_id'=>$data['student_id']),1);
        					$studentData[0]['transactionId'] = !empty($batch_his[0]['transaction_id'])?$batch_his[0]['transaction_id']:'';
        			    	$studentData[0]['amount'] = !empty($batch_his[0]['amount'])?$batch_his[0]['amount']:'';
    			            
    			        }else{
    			            $studentData[0]['transactionId'] = 'free';
    			    	    $studentData[0]['amount'] = '';
    			        }
    			    	$update_value =array('batch_id'=>$data['batch_id']);
    				}else{
    				    $resp = array('status'=>'false', 'msg' =>$this->lang->line('ltr_batch_in_msg'));
    				}
    			    
    			    $this->db_model->update_data_limit('students use index (id)',$update_value,array('id'=>$data['student_id']),1);
    			    
    				$resp = array('status'=>'true', 'msg' =>$this->lang->line('ltr_batch_change_msg'),'studentData'=>$studentData[0]);
    				//batch asin
    			   
    		    }else{
    		        
        		    $studentData =$this->db_model->select_data('id as studentId,email as userEmail,name as fullName,enrollment_id as enrollmentId,contact_no as mobile,app_version as versionCode, batch_id as batchId,admin_id,admission_date as admissionDate, image, token','students use index (id)',array('id'=>$data['student_id']),1);
    				$studentData[0]['batchName']='';
    				$studentData[0]['image']= base_url('uploads/students/').$studentData[0]['image'];
    				$studentData[0]['password'] = $password;
    				$studentData[0]['paymentType'] = $this->general_settings('payment_type');
    				$studentData[0]['languageName'] = $this->general_settings('language_name');
    				
    				 //check batch type
    				$batch_type =$this->db_model->select_data('*','batches use index (id)',array('id'=>$data['batch_id']),1);
    			
					if(!empty($batch_type)){
        				    $admin_ids = $batch_type[0]['admin_id'];
				    }else{
				            $admin_ids = 0;
				    }
				    
				    
    				$studentData[0]['batchId']= $batch_type[0]['id'];
    			    if($batch_type[0]['batch_type']==2){
    			        $studentData[0]['batchName']= $batch_type[0]['batch_name'];
    					$data_pay=array(
    					           'student_id'=>$data['student_id'],
    						       'batch_id'=>$data['batch_id'],
    							   'transaction_id'=> !empty($data['transaction_id'])?$data['transaction_id']:'',
    								  'amount'=> !empty($data['amount'])?$data['amount']:'',
    								  'admin_id'=>$admin_ids
    									);
    					$data_pay = $this->security->xss_clean($data_pay);
    					$insf = $this->db_model->insert_data('student_payment_history',$data_pay);
    					$studentData[0]['transactionId'] = !empty($data['transaction_id'])?$data['transaction_id']:'';
    			    	$studentData[0]['amount'] = !empty($data['amount'])?$data['amount']:'';
    			    	$update_value =array(
    			    	    'payment_status'=>1,
    			    	    'batch_id'=>$data['batch_id']
    			    	    );
    				}else{
    				    $studentData[0]['batchName']= $batch_type[0]['batch_name'];
    			    	$studentData[0]['transactionId'] = 'free';
    			    	$studentData[0]['amount'] = !empty($amount)?$amount:'';
    			    	$update_value =array(
    			    	    'batch_id'=>$data['batch_id']
    			    	    );
    				}
    			    
    			    $this->db_model->update_data_limit('students use index (id)',$update_value,array('id'=>$data['student_id']),1);
    			    if($studentData[0]['admin_id']==0){
    			        $update_value =array(
    			    	    'admin_id'=>$batch_type[0]['admin_id']
    			    	    );
    				    // $data_arr = $batch_type[0]['admin_id'];
    				    $this->db_model->update_data_limit('students use index (id)',$update_value,array('id'=>$data['student_id']),1);
    				}
    				$resp = array('status'=>'true', 'msg' =>$this->lang->line('ltr_account_created'),'studentData'=>$studentData[0]);
    				//batch asin
    			    $data_batch= array(
    			                 'student_id'=>$data['student_id'],
    			                 'batch_id'=>$data['batch_id'],
    			                 'added_by'=>'student',
    			                 'admin_id'=>$admin_ids
    					                 );
    		 	   $this->db_model->insert_data('sudent_batchs',$data_batch);
    			    // send email 
    			   $title = $this->db_model->select_data('site_title','site_details','',1,array('id','desc'))[0]['site_title'];
                    $subj = $title.'- '.$this->lang->line('ltr_credentials');
                    $em_msg = $this->lang->line('ltr_hey').' '.ucwords($data['name']).', '.$this->lang->line('ltr_congratulation').' <br/><br/>'.$this->lang->line('ltr_successfully_enrolled').'<br/><br/>'.$this->lang->line('ltr_login_details').'<br/><br/> '.$this->lang->line('ltr_enrolment_id').' : '.$enrolid.'<br/><br/>'.$this->lang->line('ltr_password').' : '.$password.'';
                    $this->SendMail($data['email'], $subj, $em_msg);
        		}
                
            }else{
                $resp = array('status'=>'false','msg'=>$this->lang->line('ltr_missing_parameters_msg')); 
            } 
            echo json_encode($resp,JSON_UNESCAPED_SLASHES);
        }
        

        function db_new_changes_all_batch(){
        $data =	$_REQUEST;
        if(isset($data['admin_id']) && isset($data['student_id'])){
            $admin_id = $data['admin_id'];
            $student_id = $data['student_id'];
            //$batch_id = $data['batch_id'];
            
            $last_login = $this->db_model->select_data('last_login_app','students use index(id)',array('id'=>$student_id),1);
            
            if($last_login[0]['last_login_app'] != '0000-00-00 00:00:00'){
                $batch_details = $this->db_model->select_data('*','sudent_batchs ',array('student_id'=>$student_id),'','','',array('batches','batches.id=sudent_batchs.batch_id'));
                foreach($batch_details as $batch_detail){
                $lastLoginTime = $last_login[0]['last_login_app'];
                $batch_id = $batch_detail['batch_id'];
                $likeEX = array('batch_id','"'.$batch_id.'"');
                $extraData = $this->db_model->select_data('extra_classes.id,extra_classes.admin_id as adminId,extra_classes.date, extra_classes.start_time as startDate,extra_classes.end_time as endTime,extra_classes.teacher_id as teacherId,extra_classes.description,extra_classes.status,extra_classes.batch_id as batchId,extra_classes.added_at as addedAt,extra_classes.completed_date_time as completedDateTime,users.name,users.teach_gender as teachGender','extra_classes use index(id)',array('extra_classes.admin_id'=>$admin_id,'extra_classes.added_at >= '=>$lastLoginTime,'date >=' => date('Y-m-d')),'',array('date','asc'),$likeEX,array('users','users.id = extra_classes.teacher_id'));
                $extraClass =array();
                if(!empty($extraData)){
                    foreach($extraData as $key=>$value){
                        $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'extraClass'));
                        if(empty($view_n)){
                            $extraClass[$key]=$value;
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $extraClass[$key]=$value;
                            }
                        }
                    }
                }
                
                $homewrk = $this->db_model->select_data('homeworks.id, homeworks.admin_id as adminId,homeworks.teacher_id as teacherId, homeworks.date,homeworks.subject_id as subjectId, homeworks.batch_id as batchId, homeworks.description,homeworks.added_at as addedAt,users.name,users.teach_gender as teachGender,subjects.subject_name as subjectName','homeworks use index (id)',array('homeworks.admin_id'=>$admin_id,'homeworks.added_at >= '=>$lastLoginTime,'homeworks.batch_id'=>$batch_id,'date >=' => date('Y-m-d')),'',array('id','desc'),'',array('multiple',array(array('users','users.id = homeworks.teacher_id'),array('subjects','subjects.id = homeworks.subject_id'))),'');
                $homewrkData=array();
                if(!empty($homewrk)){
                    foreach($homewrk as $key=>$value){
                     $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'homeWork'));
                        if(empty($view_n)){
                            $homewrkData[$key]=$value;
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $homewrkData[$key]=$value;
                            }
                        }
                    }
                }
                
                $likev = array('batch','"'.$batch_id.'"');
                $videosData = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt, video_type as videoType','video_lectures use index (id)',array('admin_id'=>$admin_id,'added_at >= '=>$lastLoginTime),'',array('id','desc'),$likev);
                $videos =array();
               
                if(!empty($videosData)){
                    foreach($videosData as $key=>$value){
                        $sub_id =$this->db_model->select_data('subject_id','batch_subjects',array('batch_id'=>$batch_id),1);
                        if(!empty($sub_id)){
                            $sub_name =$this->db_model->select_data('subject_name','subjects',array('id'=>$sub_id[0]['subject_id']),1);
                            if(!empty($sub_name)){
                               // if($sub_name[0]['subject_name']==$value['subject']){
                                    $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id'=>$student_id,'notice_type'=>'videoLecture'));
                                    if(empty($view_n)){
                                        $videos[$key]=$value;
                                        $url = $value['url'];
                                        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                        $videos[$key]['videoId']=$match[1];
                                    }else{
                                        if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                            $videos[$key]=$value;
                                            $url = $value['url'];
                                        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                        $videos[$key]['videoId']=$match[1];
                                        }
                                    }
                            //  }
                            }
                        }
                        
                    }
                }
                
                $vacancyData = $this->db_model->select_data('id,title,description,start_date as startDate,last_date as lastDate,mode,files,status,admin_id as adminId,added_at as addedAt','vacancy use index (id)',array('admin_id'=>$admin_id,'last_date >= '=> date("Y-m-d"),'status'=>1,'added_at >='=>$lastLoginTime),'',array('id','desc'));
                $vacancy=array();
                if(!empty($vacancyData)){
                    foreach($vacancyData as $key=>$value){
                        $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'vacancy'));
                        if(empty($view_n)){
                            $vacancy[$key]=$value;
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $vacancy[$key]=$value;
                            }
                        }
                    }
                }
                
                $noticesData = $this->db_model->select_data('`id`, `title`, `description`, `notice_for` as noticeFor, `status`, `date`, `admin_id` as adminId, `student_id` as studentId, `teacher_id` as teacherId, `added_at` as addedAt, `added_by` as addedBy, `read_status` as readStatus','notices use index (id)',"admin_id = $admin_id AND status = 1 AND (notice_for = 'Student' OR notice_for = 'Both' OR student_id = $student_id) AND added_at >= '$lastLoginTime' AND date >= CURDATE()",'',array('id','desc'));
                
                $notices=array();
                if(!empty($noticesData)){
                    foreach($noticesData as $key=>$value){
                        $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id'=>$student_id,'notice_type'=>'notices'));
                        if(empty($view_n)){
                            $notices[$key]=$value;
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $notices[$key]=$value;
                            }
                        }
                    }
                }
                
                $practice_paper = $this->db_model->select_data('id,name,time_duration as timeDuration, added_at as addedAt, total_question as totalQuestion','exams use index (id)',array('type'=>2,'status'=>1,'batch_id'=>$batch_id,'added_at >='=>$lastLoginTime),'',array('id','desc'));
                
                 $newexampaper_test = array();       
                if(!empty($practice_paper)){
                    foreach($practice_paper as $key=>$pexam){
                            $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id'=>$student_id,'notice_type'=>'practicePaper'));
                            $paperstatus = $this->db_model->select_data('id','practice_result use index (id)',array('admin_id' => $admin_id,'student_id' => $student_id,'paper_id' => $pexam['id']),1);
                         
                            if(empty($paperstatus) && empty($view_n)){
                               // array_push($newexampaper_test,$pexam); 
                               $newexampaper_test[$key]=$pexam;
                            }else{
                                if(!empty($view_n)){
                        
                                    if(strtotime($view_n[0]['views_time'])<strtotime($pexam['addedAt'])){
                                        $newexampaper_test[$key]=$pexam;
                                        
                                      }
                                  
                                }
                            }
                        }
                    }
                    
                    
                $mock_paper = $this->db_model->select_data('id,name,time_duration as timeDuration, added_at as addedAt, total_question as  totalQuestion,mock_sheduled_date as mockSheduledDate,mock_sheduled_time as mockSheduledTime','exams use index (id)',array('type' => 1,'admin_id' => $admin_id,'status' => 1,'batch_id' => $batch_id,'mock_sheduled_date >=' => date('Y-m-d'),'added_at >='=>$lastLoginTime),'',array('id','desc'));
           
                $newexampaper = array();          
                if(!empty($mock_paper)){
                    foreach($mock_paper as $exam){
                            $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'mockPaper'));
                            $paperstatus = $this->db_model->select_data('id','mock_result use index (id)',array('admin_id' => $admin_id,'student_id' => $student_id,'paper_id' => $exam['id']),1);
                            if($exam['mockSheduledDate']==date('Y-m-d')){
                                if(strtotime($exam['mockSheduledTime'])>strtotime(date('H:i:s'))){
                                    if(empty($paperstatus) && empty($view_n)){
                                array_push($newexampaper,$exam); 
                                    }else{
                                        if(!empty($view_n)){
                                            if(strtotime($view_n[0]['views_time'])<strtotime($exam['addedAt'])){
                                                array_push($newexampaper,$exam);
                                              }
                                          
                                        }
                                    }
                                }
                            }else{
                                if(empty($paperstatus) && empty($view_n)){
                                array_push($newexampaper,$exam); 
                                    }else{
                                        if(!empty($view_n)){
                                            if(strtotime($view_n[0]['views_time'])<strtotime($exam['addedAt'])){
                                                array_push($newexampaper,$exam);
                                              }
                                          
                                        }
                                    }
                            }
                         
                            
                        }
                    }
                    
                $addNewBook=array();
                $like = array('batch','"'.$batch_id.'"');
    		    $book_pdf =$this->db_model->select_data('id,admin_id as adminId, title, batch, topic, subject, file_name as fileName,  status, added_by as addedBy, added_at as addedAt','book_pdf use index (id)',array('status'=>1,'added_at >= '=>$lastLoginTime),'',array('id','desc'),$like);
    		    if(!empty($book_pdf)){
    		        foreach($book_pdf as $key=>$value){
    		            $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'book_notes_paper'));
                        if(empty($view_n)){
                            $addNewBook[$key]=$value;
                            $addNewBook[$key]['url']= base_url('uploads/book/');
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $addNewBook[$key]=$value;
                                $addNewBook[$key]['url']= base_url('uploads/book/');
                            }
                        }
    		        }
    		    }
    		    
    		    $addNewNotes=array();
    		    $notes_pdf =$this->db_model->select_data('id,admin_id as adminId, title, batch, topic, subject, file_name as fileName,  status, added_by as addedBy, added_at as addedAt','notes_pdf use index (id)',array('status'=>1,'added_at >= '=>$lastLoginTime),'',array('id','desc'),$like);
    		    if(!empty($notes_pdf)){
    		        foreach($notes_pdf as $key=>$value){
    		            
    		            $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'book_notes_paper'));
                        if(empty($view_n)){
                            $addNewNotes[$key]=$value;
                            $addNewNotes[$key]['url']= base_url('uploads/notes/');
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $addNewNotes[$key]=$value;
                                $addNewNotes[$key]['url']= base_url('uploads/notes/');
                            }
                        }
    		        }
    		    }
    		    
    		    $addOldPaper=array();
                $newexampaper =$this->db_model->select_data('id,admin_id as adminId, title, batch, topic, subject, file_name as fileName,  status, added_by as addedBy, added_at as addedAt','old_paper_pdf use index (id)',array('status'=>1,'added_at >= '=>$lastLoginTime),'',array('id','desc'),$like);
    		    if(!empty($newexampaper)){
    		        foreach($newexampaper as $key=>$value){
    		            
    		            $view_n =$this->db_model->select_data('views_time','views_notification_student',array('student_id >= '=>$student_id,'notice_type'=>'book_notes_paper'));
                        if(empty($view_n)){
                            $addOldPaper[$key]=$value;
                            $addOldPaper[$key]['url']= base_url('uploads/oldpaper/');
                        }else{
                            if(strtotime($view_n[0]['views_time'])<strtotime($value['addedAt'])){
                                $addOldPaper[$key]=$value;
                                $addOldPaper[$key]['url']= base_url('uploads/oldpaper/');
                            }
                        }
    		        }
    		    }
    		    $classdata=array();
    		    $classdata = current($this->db_model->select_data('users.name,users.teach_image AS teachImage,subjects.subject_name as subjectName,chapters.chapter_name as chapterName,live_class_history.end_time as endTime','live_class_history',array('batch_id'=>$batch_id),'1',array('live_class_history.id','desc'),'',array('multiple',array(array('users','users.id = live_class_history.uid'),array('subjects','subjects.id = live_class_history.subject_id'),array('chapters','chapters.id = live_class_history.chapter_id')))));
                $exam = $this->db_model->select_data('id,name','exams  use index (id)',array('batch_id'=>$batch_id,'type'=>1,'mock_sheduled_date <='=>date('Y-m-d')),'1',array('id','desc'));
            $top_three = $this->db_model->select_data('mock_result.paper_name as paperName,students.name,students.image,mock_result.percentage','mock_result  use index (id)',array('paper_id'=>$exam[0]['id'],'mock_result.percentage >'=>0),'3',array('mock_result.percentage','desc'),'',array('students','students.id=mock_result.student_id'));
                $arr_dd = array(
                    'topThreeData' => $top_three,
                    'filesUrl' => base_url('uploads/students/')
                );
    		         
    		         
                $arr1[] = array(
                    'batchName'=>$batch_detail['batch_name'],
                    'batchId'=>$batch_detail['id'],
                    'extraClass'=> $extraClass,
                    'homeWork'=>$homewrkData,
                    'videoLecture'=>$videos,
                    'vacancy'=>$vacancy,
                    'notices'=>$notices,
                    'practicePaper'=>$newexampaper_test,
                    'mockPaper'=>$newexampaper,
                    'addOldPaper'=>$addOldPaper,
                    'addNewBook'=>$addNewBook,
                    'addNewNotes'=>$addNewNotes,
                    'liveClass' => ($classdata['endTime']=='' && !empty($classdata))?$classdata:null,
                    'topThree' => $arr_dd,
                ); 
                }
              
                $arr['status']=isset($arr1) && !empty($arr1) ? 'true' : 'false';
                $arr['msg']= $this->lang->line('ltr_fetch_successfully');
                $arr['noticesCount']=count($notices) + count($extraClass) + count($homewrkData) + count($videos) + count($vacancy) + count($addNewNotes) + count($addNewBook) + count($addOldPaper) + count($newexampaper) + count($newexampaper_test);
                $arr['data']=$arr1;
                
            }else{
                $arr = array('status'=>'false','msg'=>$this->lang->line('ltr_its_first_login'));
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            ); 
        }
        echo json_encode($arr);
    }
    
    function get_syllabus_data(){
        
          $data =	$_REQUEST;
        if(isset($data['batch_id'])){
            $join = array('batch_subjects',"batch_subjects.batch_id = batches.id");
            $batchData = $this->db_model->select_data('batch_subjects.*, batches.batch_name as batchName','batches use index (id)',array('status'=>1,'batch_id'=>$data['batch_id']),'',array('id','desc'),'',$join);  
            
          if(!empty($batchData)){
            foreach($batchData as $key => $value){ 
                            
            $t_name = $this->db_model->select_data('users.name','users use index (id)',array('id'=>$batchData[$key]['teacher_id']));  
    
            $data_arr['batchId'] = $batchData[$key]['batch_id'];
            $data_arr['batchName'] = $batchData[$key]['batchName'];
            $data_arr['teacherId'] = $batchData[$key]['teacher_id'];
            $data_arr['teacherName'] = $t_name[0]['name'];
            // $data_arr['chapterStatus']=implode(', ',json_decode($batchData[$key]['chapter_status']));
             $batchSubject = $this->db_model->select_data('subjects.id,subjects.subject_name as subjectName, batch_subjects.chapter','subjects use index (id)',array('batch_id'=>$data_arr['batchId']),'',array('id','desc'),'',array('batch_subjects','batch_subjects.subject_id=subjects.id'));
				
					   if(!empty($batchSubject)){
					       foreach($batchSubject as $skey=>$svalue){
					            $cid=implode(', ',json_decode($svalue['chapter']));
					            $complete_id = json_decode($value['chapter_status']);
					           
					            $con ="id in ($cid)";
					            $chapter = $this->db_model->select_data('id,chapter_name as chapterName','chapters use index (id)',$con,'',array('id','desc'));
					            if(!empty($chapter)){
					                foreach($chapter as $ckey=>$cvalue){
					              if (in_array($cvalue['id'], $complete_id))
                                {
                                 $chapter[$ckey]['complete'] = true;
                                  }
                                else
                                  {
                                    $chapter[$ckey]['complete'] = false;
                                  }
					              
					                }
					                $batchSubject[$skey]['chapter']= $chapter;
					              
					            }else{
					             $batchSubject[$skey]['chapter']=array();   
					            }
					            
					       }
					       
					       $data_arr['batchSubject']= $batchSubject;
					   }else{
					     $data_arr['batchSubject'] = array();  
					   }
            }
                      
                   
              $arr = array(
                 'data' => $data_arr,
                'status'=>'True',
                'msg'=>$this->lang->line('ltr_fetch_successfully')
            ); 
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_no_record_msg')
            ); 
        }
        }
        else{  
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg')
            ); 
        }
        echo json_encode($arr);
    }
  
    function get_all_data(){
        $data = $_REQUEST;
        
        $subcat = $data['subcat'];
        if(!empty($subcat)){
       
                 
      if(isset($data['length']) && $data['length']>0){
            if(isset($data['start']) && !empty($data['start'])){
                $limit = array($data['length'],$data['start']);
                // $count = $data['start']+1;
            }else{ 
                $limit = array($data['length'],0);
                // $count = 1;
            }
        }else{
            $limit = '';
            // $count = 1;
        }
        
         $slider_limit = $data['limit'];
        $category = $this->db_model->select_data('id as categoryId,name as categoryName','batch_category use index (id)',array('status'=>1));
                     if(!empty($category)){
                        
                        foreach($category as $catkey=>$value){
                                 

            $cond = array('status'=>1,'cat_id'=>$value['categoryId'],'id'=>$subcat);
            
                    $subCategory = $this->db_model->select_data('id as SubcategoryId,name as SubcategoryName','batch_subcategory use index (id)',$cond,'','');
            
                    if(!empty($subCategory)){
                        
                        foreach($subCategory as $subkey=>$value){
                    
                     $search = trim($data['search']);
                    if(!empty($search)){
                      $like_search = array('batch_name',$search);
                       $cond_sub1='';
                    }
                    
                    else{
                        
                        $like_search = '';
                        $cond_sub1 = array('status'=>1,'sub_cat_id'=>$value['SubcategoryId']);
                        //$cond_sub1 = array('status'=>1,'id'=>$value['SubcategoryId']);
                    }
                  
                   
            $batchData = $this->db_model->select_data('id, batch_name as batchName, start_date as startDate, end_date as endDate, start_time as startTime, end_time as endTime, batch_type as batchType, batch_price as batchPrice, no_of_student as noOfStudent ,status,description, batch_image as batchImage, batch_offer_price as batchOfferPrice, enrollment_status as enrollmentStatus, enrolled_at as enrolledAt, enrollment_type as enrollmentType ','batches use index (id)',$cond_sub1,$limit,array('id','desc'),$like_search);
 
            if(!empty($batchData)){
                foreach($batchData as $key=>$value){
                    if(!empty($value['batchImage'])){
                        $batchData[$key]['batchImage']= base_url('uploads/batch_image/').$value['batchImage'];
                    }
                    $startDate =$value['startDate'];
                    $endDate =$value['endDate'];
                    $batchData[$key]['startDate']=date('d-m-Y',strtotime($startDate));
                    $batchData[$key]['endDate']=date('d-m-Y',strtotime($endDate));
                   
                    $batch_fecherd =$this->db_model->select_data('batch_specification_heading as batchSpecification, batch_fecherd as fecherd','batch_fecherd',array('batch_id'=>$value['id']));
                    if(!empty($batch_fecherd)){
                           $batchData[$key]['batchFecherd'] =$batch_fecherd;
                    }else{
                        $batchData[$key]['batchFecherd']=array();
                    }

                   // add payment type
                   $batchData[$key]['paymentType'] = $this->general_settings('payment_type');
                   $batchData[$key]['currencyCode'] = $this->general_settings('currency_code');
                   $batchData[$key]['currencyDecimalCode'] = $this->general_settings('currency_decimal_code');
                   
                   
                   $batchSubject = $this->db_model->select_data('subjects.id,subjects.subject_name as subjectName, batch_subjects.chapter','subjects use index (id)',array('batch_id'=>$value['id']),'',array('id','desc'),'',array('batch_subjects','batch_subjects.subject_id=subjects.id'));
                   if(!empty($batchSubject)){
                       foreach($batchSubject as $skey=>$svalue){
                            $cid=implode(', ',json_decode($svalue['chapter']));
                            $con ="id in ($cid)";
                            $chapter =$this->db_model->select_data('id,chapter_name as chapterName','chapters use index (id)',$con,'',array('id','desc'));
                            if(!empty($chapter)){
                                foreach($chapter as $ckey=>$cvalue){
                                    
                                    $sub_like = array('topic,batch',urldecode($cvalue['chapterName']).',"'.$value['id'].'"');
                                    $sub_videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('status'=>1),'',array('id','desc'),$sub_like);
                                    if(!empty($sub_videos)){
                                        foreach($sub_videos as $vkey=>$vvalue){
                                            $url = $vvalue['url'];
                                            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                            $sub_videos[$vkey]['videoId']=$match[1];
                                        }
                                        $chapter[$ckey]['videoLectures']=$sub_videos;
                                    }else{
                                      $chapter[$ckey]['videoLectures'] = array(); 
                                    }
                                    
                                    
                                }
                                $batchSubject[$skey]['chapter']=$chapter;
                            }else{
                             $batchSubject[$skey]['chapter']=array();   
                            }
                       
                       }
                       
                       $batchData[$key]['batchSubject'] = $batchSubject;
                   }else{
                     $batchData[$key]['batchSubject'] = array();  
                   }
                    $like = array('batch','"'.$value['id'].'"');
                    $videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('status'=>1),'',array('id','desc'),$like);
                    if(!empty($videos)){
                        foreach($videos as $vkey=>$vvalue){
                            $url = $vvalue['url'];
                            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                            $videos[$vkey]['videoId']=$match[1];
                        }
                        $batchData[$key]['videoLectures']=$videos;
                    }else{
                      $batchData[$key]['videoLectures'] = array(); 
                    }
                   $student_batch_dtail = $this->db_model->select_data('*','sudent_batchs',array('student_id'=>$data['student_id'],'batch_id' => $value['id'] ),'');
                   if(!empty($student_batch_dtail)){
                        $batchData[$key]['purchase_condition'] = true;
                   }else{
                       $batchData[$key]['purchase_condition'] = false;
                   }
                }
                    $subCategory[$subkey]['BatchData'] = $batchData;
                    }else{
                       
                                $subCategory[$subkey]['BatchData'] = array();
                               }
                        }
                         $category[$catkey]['subcategory'] = $subCategory;
                   }else{
                           $category[$catkey]['subcategory'] = array();
                   }
                  
                }
            
            }
                    
                 if($data['subcat']=='other'){
                    $getOther_Batch=$this->otherBatch_data($data);
                 /*print_r($getOther_Batch);
                die();*/
                    if($getOther_Batch){
                        array_push($category,$getOther_Batch);
                    }
                 }
                        
                     $category=array_values($category);    
            $arr = array(           
                            'status'=>'true',
                            'msg' =>$this->lang->line('ltr_fetch_successfully'),
                            'batchData'=>$category
                            );
        }
            
                    //     elseif(!empty($data['search'])){
                                
                    //       $search = trim($data['search']);
                            // $like_search = array('batch_name',$search);
                            
                    // 	if(isset($data['length']) && $data['length']>0){
                    //             if(isset($data['start']) && !empty($data['start'])){
                    //                 $limit = array($data['length'],$data['start']);
                    //                 // $count = $data['start']+1;
                    //             }else{ 
                    //                 $limit = array($data['length'],0);
                    //                 // $count = 1;
                    //             }
                    //         }else{
                    //             $limit = '';
                    //             // $count = 1;
                    //         }

            //         //  $slider_limit = $data['limit'];
            //         $category = $this->db_model->select_data('id as categoryId,name as categoryName','batch_category use index (id)',array('status'=>1),$limit);
            // 	     if(!empty($category)){
                        
            // 	        foreach($category as $catkey=>$value){
                                 
            // 	   //  }
                   
            // 	   // $cond="id not in ($batch_id) AND status=1 AND cat_id= $value['id']";
            // 	   $cond = array('status'=>1,'cat_id'=>$value['categoryId']);
                   
            // 	     $subCategory = $this->db_model->select_data('id as SubcategoryId,name as SubcategoryName','batch_subcategory use index (id)',$cond,'','',array('id',$data['subcat']));
            // 	   // echo $this->db->last_query();
            // 	    if(!empty($subCategory)){
                        
            // 	        foreach($subCategory as $subkey=>$value){
                    
                     
            // 	   $cond_sub1 = array('status'=>1,'sub_cat_id'=>$value['SubcategoryId']);
                   
            //                $batchData = $this->db_model->select_data('id, batch_name as batchName, start_date as startDate, end_date as endDate, start_time as startTime, end_time as endTime, batch_type as batchType, batch_price as batchPrice, no_of_student as noOfStudent ,status,description, batch_image as batchImage, batch_offer_price as batchOfferPrice','batches use index (id)',$cond_sub1,$slider_limit,array('id','desc'),$like_search);
   
            // if(!empty($batchData)){
            // 	foreach($batchData as $key=>$value){
            // 		if(!empty($value['batchImage'])){
            // 			$batchData[$key]['batchImage']= base_url('uploads/batch_image/').$value['batchImage'];
            // 		}
            // 		$startDate =$value['startDate'];
            // 		$endDate =$value['endDate'];
            //                     $batchData[$key]['startDate']=date('d-m-Y',strtotime($startDate));
            //                     $batchData[$key]['endDate']=date('d-m-Y',strtotime($endDate));
                   
            // 		$batch_fecherd =$this->db_model->select_data('batch_specification_heading as batchSpecification, batch_fecherd as fecherd','batch_fecherd',array('batch_id'=>$value['id']));
            // 		if(!empty($batch_fecherd)){
            // 			   $batchData[$key]['batchFecherd'] =$batch_fecherd;
            // 		}else{
            // 			$batchData[$key]['batchFecherd']=array();
            // 		}

            // 	   // add payment type
            // 	   $batchData[$key]['paymentType'] = $this->general_settings('payment_type');
            // 	   $batchData[$key]['currencyCode'] = $this->general_settings('currency_code');
            // 	   $batchData[$key]['currencyDecimalCode'] = $this->general_settings('currency_decimal_code');
                   
                   
            // 	   $batchSubject = $this->db_model->select_data('subjects.id,subjects.subject_name as subjectName, batch_subjects.chapter','subjects use index (id)',array('batch_id'=>$value['id']),'',array('id','desc'),'',array('batch_subjects','batch_subjects.subject_id=subjects.id'));
            // 	   if(!empty($batchSubject)){
            // 	       foreach($batchSubject as $skey=>$svalue){
            // 	            $cid=implode(', ',json_decode($svalue['chapter']));
            // 	            $con ="id in ($cid)";
            // 	            $chapter =$this->db_model->select_data('id,chapter_name as chapterName','chapters use index (id)',$con,'',array('id','desc'));
            // 	            if(!empty($chapter)){
            // 	                foreach($chapter as $ckey=>$cvalue){
                                    
            // 	                    $sub_like = array('topic,batch',urldecode($cvalue['chapterName']).',"'.$value['id'].'"');
            //             					    $sub_videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('status'=>1),'',array('id','desc'),$sub_like);
            //                                     if(!empty($sub_videos)){
            //                                         foreach($sub_videos as $vkey=>$vvalue){
            //                                             $url = $vvalue['url'];
            //                                             preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
            //                                             $sub_videos[$vkey]['videoId']=$match[1];
            //                                         }
            //                                         $chapter[$ckey]['videoLectures']=$sub_videos;
            //                                     }else{
            //                                       $chapter[$ckey]['videoLectures'] = array(); 
            //                                     }
                                    
                                    
            // 	                }
            // 	                $batchSubject[$skey]['chapter']=$chapter;
            // 	            }else{
            // 	             $batchSubject[$skey]['chapter']=array();   
            // 	            }
                            
                            
            // 	       }
                       
            // 	       $batchData[$key]['batchSubject'] = $batchSubject;
            // 	   }else{
            // 	     $batchData[$key]['batchSubject'] = array();  
            // 	   }
            // 	    $like = array('batch','"'.$value['id'].'"');
            // 	    $videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('status'=>1),'',array('id','desc'),$like);
            //                     if(!empty($videos)){
            //                         foreach($videos as $vkey=>$vvalue){
            //                             $url = $vvalue['url'];
            //                             preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
            //                             $videos[$vkey]['videoId']=$match[1];
            //                         }
            //                         $batchData[$key]['videoLectures']=$videos;
            //                     }else{
            //                       $batchData[$key]['videoLectures'] = array(); 
            //                     }
            // 	   $student_batch_dtail = $this->db_model->select_data('*','sudent_batchs',array('student_id'=>$data['student_id'],'batch_id' => $value['id'] ),'');
            // 	   if(!empty($student_batch_dtail)){
            // 	        $batchData[$key]['purchase_condition'] = true;
            // 	   }else{
            // 	       $batchData[$key]['purchase_condition'] = false;
            // 	   }
            // 	}
            //     			$subCategory[$subkey]['BatchData'] = $batchData;
            
            // 	    }else{
                       
            //     					   	 $subCategory[$subkey]['BatchData'] = array();
            //     					   	}
            // 			}
            // 			 $category[$catkey]['subcategory'] = $subCategory;
            // 	   }else{
            // 	   		$category[$catkey]['subcategory'] = array();
            // 	   }
                  
            // 	}
            // }

                //         $arr = array(           
                //                         'status'=>'true',
            //                 'msg' =>$this->lang->line('ltr_fetch_successfully'),
            //                 'batchData'=>$category
            //                 );
              
            // }
            
            else{
                $arr = array('status'=>'false', 'msg' => $this->lang->line('ltr_missing_parameters_msg')); 
            }
        echo json_encode($arr);
    }

    public function otherBatch_data($data){
        $search = trim($data['search']);
        $slider_limit = $data['limit'];
        
        if(!empty($search)){
          $like_search = array('batch_name',$search);
        }else{
            $like_search = '';
        }

      if(isset($data['length']) && $data['length']>0){
            if(isset($data['start']) && !empty($data['start'])){
                $limit = array($data['length'],$data['start']);
                // $count = $data['start']+1;
            }else{ 
                $limit = array($data['length'],0);
                // $count = 1;
            }
        }else{
            $limit = '';
            // $count = 1;
        }
                     $category=array('categoryId'=>'0','categoryName'=>'other');
                     $subCategory['SubcategoryId']='0';
                     $subCategory['SubcategoryName']='other';
                   $cond_sub1 = array('status'=>1,'sub_cat_id'=>0);
                   
            $batchData = $this->db_model->select_data('id, batch_name as batchName, start_date as startDate, end_date as endDate, start_time as startTime, end_time as endTime, batch_type as batchType, batch_price as batchPrice, no_of_student as noOfStudent ,status,description, batch_image as batchImage, batch_offer_price as batchOfferPrice','batches use index (id)',$cond_sub1,$limit,array('id','desc'),$like_search);

            if(!empty($batchData)){
                foreach($batchData as $key=>$value){
                    if(!empty($value['batchImage'])){
                        $batchData[$key]['batchImage']= base_url('uploads/batch_image/').$value['batchImage'];
                    }
                    $startDate =$value['startDate'];
                    $endDate =$value['endDate'];
                    $batchData[$key]['startDate']=date('d-m-Y',strtotime($startDate));
                    $batchData[$key]['endDate']=date('d-m-Y',strtotime($endDate));
                   
                    $batch_fecherd =$this->db_model->select_data('batch_specification_heading as batchSpecification, batch_fecherd as fecherd','batch_fecherd',array('batch_id'=>$value['id']));
                    if(!empty($batch_fecherd)){
                           $batchData[$key]['batchFecherd'] =$batch_fecherd;
                    }else{
                        $batchData[$key]['batchFecherd']=array();
                    }

                   // add payment type
                   $batchData[$key]['paymentType'] = $this->general_settings('payment_type');
                   $batchData[$key]['currencyCode'] = $this->general_settings('currency_code');
                   $batchData[$key]['currencyDecimalCode'] = $this->general_settings('currency_decimal_code');
                   
                   
                   $batchSubject = $this->db_model->select_data('subjects.id,subjects.subject_name as subjectName, batch_subjects.chapter','subjects use index (id)',array('batch_id'=>$value['id']),'',array('id','desc'),'',array('batch_subjects','batch_subjects.subject_id=subjects.id'));
                   if(!empty($batchSubject)){
                       foreach($batchSubject as $skey=>$svalue){
                            $cid=implode(', ',json_decode($svalue['chapter']));
                              if(!empty($cid)){
                            $con ="id in ($cid)";
                            $chapter =$this->db_model->select_data('id,chapter_name as chapterName','chapters use index (id)',$con,'',array('id','desc'));
                            if(!empty($chapter)){
                                foreach($chapter as $ckey=>$cvalue){
                                    
                                    $sub_like = array('topic,batch',urldecode($cvalue['chapterName']).',"'.$value['id'].'"');
                                    $sub_videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('status'=>1),'',array('id','desc'),$sub_like);
                                    if(!empty($sub_videos)){
                                        foreach($sub_videos as $vkey=>$vvalue){
                                            $url = $vvalue['url'];
                                            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                                            $sub_videos[$vkey]['videoId']=$match[1];
                                        }
                                        $chapter[$ckey]['videoLectures']=$sub_videos;
                                    }else{
                                      $chapter[$ckey]['videoLectures'] = array(); 
                                    }
                                    
                                }
                                $batchSubject[$skey]['chapter']=$chapter;
                            }else{
                             $batchSubject[$skey]['chapter']=array();   
                            }
                              }else{
                             $batchSubject[$skey]['chapter']=array();   
                            }
                       }
                       
                       $batchData[$key]['batchSubject'] = $batchSubject;
                   }else{
                     $batchData[$key]['batchSubject'] = array();  
                   }
                    $like = array('batch','"'.$value['id'].'"');
                    $videos = $this->db_model->select_data('id,admin_id as adminId,title,batch,topic,subject,url,status,added_by as addedBy,added_at as addedAt,video_type as videoType, preview_type as previewType, description','video_lectures use index (id)',array('status'=>1),'',array('id','desc'),$like);
                    if(!empty($videos)){
                        foreach($videos as $vkey=>$vvalue){
                            $url = $vvalue['url'];
                            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                            $videos[$vkey]['videoId']=$match[1];
                        }
                        $batchData[$key]['videoLectures']=$videos;
                    }else{
                      $batchData[$key]['videoLectures'] = array(); 
                    }
                   $student_batch_dtail = $this->db_model->select_data('*','sudent_batchs',array('student_id'=>$data['student_id'],'batch_id' => $value['id'] ),'');
                   
                   
                   if(!empty($student_batch_dtail)){
                        $batchData[$key]['purchase_condition'] = true;
                   }else{
                       $batchData[$key]['purchase_condition'] = false;
                   }
        
                    $subCategory['BatchData'] = $batchData;
                
                }
                    }else{
                                $subCategory['BatchData'] = array();
                               }
                    
                         $category['subcategory'][] = $subCategory;
                    
                         if(!empty($category)){
                               return $category;
                           }else{
                               return false;
                           }
    }
    
   	public function certificate(){
        $data = $_REQUEST;
         if(isset($data['student_id'])){
             $id=$data['student_id'];
            //  $batch_id = $data['batch_id'];
            $cond = array('sudent_batchs.student_id' => $id);
            $table = 'batches';
            $join = array('batches', 'sudent_batchs.batch_id = batches.id');
            $batches = $this->db_model->select_data('batches.batch_name,sudent_batchs.batch_id,sudent_batchs.student_id','sudent_batchs',$cond,$limit,array('sudent_batchs.id','desc'),$like,$join,'',$or_like);
                
            if(!empty($batches)){
                 
                foreach($batches as $key => $cert){
                    $data['batchdata'] = $cert;
                    $data['student_certificate'] = $this->db_model->select_data('*','certificate',array('student_id'=>$cert['student_id'],'batch_id'=>$cert['batch_id']));
                    $data['certificate_details']=$this->db_model->select_data('*','certificate_setting','',1,array('id','desc'));
                    $data['site_details_logo']=$this->db_model->select_data('site_logo','site_details','',1,array('id','desc'));
                    $data['student_details']=$this->db_model->select_data('name','students',array('id'=>$id),1,array('id','desc'));
                    // $data['batchdata']=$this->db_model->select_data('batch_name','batches',array('id'=>$batch_id),1,array('id','desc'));
                   
                    $data['baseurl'] = base_url();
              
                    $html=  $this->load->view("student/certificate_pdf",$data,true); 
                 
                    $this->load->library('pdf'); // change to pdf_ssl for ssl
                    // $filename = "certificate_".$id."_".$cert['batch_id'];
                    $result=$this->pdf->create($html);
                    
                    $batches[$key]['filename']= "certificate_".$id."_".$cert['batch_id'].'.pdf';
                    $batches[$key]['batch_name']= $cert['batch_name'];
                    $batches[$key]['filesUrl']= base_url('uploads/certificate/');
                    $batches[$key]['assign'] = $data['student_certificate'] ? true : false;
                    
                    $file_path= explode("application",APPPATH);
                    file_put_contents($file_path[0].'uploads/certificate/'.$batches[$key]['filename'], $result);
                   
                }
               
                $arr = array(
                    'data' => $batches,
                    'status' => 'true',
                    'msg' => $this->lang->line('ltr_fetch_successfully')
                );
           
             }
             else{
                 $arr = array(
                     'status' => 'false',
                     'msg' => $this->lang->line('ltr_no_record_msg')
                 );
             }
         }else{
             $arr = array(
                 'status'=>'false',
                 'msg'=>$this->lang->line('ltr_missing_parameters_msg')
             );
         }
         echo json_encode($arr);
    }
    function SendMailSmtp($to='', $subject = '', $body = ''){
    	$config 				= 	[];
    	$config['protocol'] 	= 	'smtp';
    	$config['smtp_host'] 	= 	'email-smtp.us-east-1.amazonaws.com';
    	$config['smtp_user'] 	= 	'AKIAQL4DOKA3GMC5QN6I';
    	$config['smtp_pass'] 	= 	'BOasr6+wM2rbWC42g9lJCbUpNYA91pMmwXrhNwzrsHcC';
    	$config['smtp_port'] 	= 	 587;
    	$config['smtp_crypto'] 	= 	'tls';
    	$config['mailtype'] 	= 	'html';
    	$config['charset'] 		= 	'UTF-8';
    	
    	$this->email->initialize($config);
    	$this->email->set_newline("\r\n");
    	$this->email->from('support@plrfunnels.in');
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($body);
        
        if(!$this->email->send()){
    		return $this->email->print_debugger();
    	}else{
    	    return 1;
    	}
    }
      function  order_id_gen(){
        $data = $_REQUEST;
       if(!empty($data['amount']) && !empty($data['currency'])){
            $razorpay_key_id = $this->general_settings('razorpay_key_id');
            $razorpay_secret_key = $this->general_settings('razorpay_secret_key');
            // $razorpay_key_id = $this->general_settings('razorpay_key_id');
            // $razorpay_secret_key = $this->general_settings('razorpay_secret_key');
            $currency_code = $this->general_settings('currency_code');
           
            $am = $data['amount']*100;
            $crny = isset($data['currency']) && !empty($data['currency'])?$data['currency']:$currency_code;
          
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n  \"amount\": $am,\n  \"currency\": \"$crny\"}");
            curl_setopt($ch, CURLOPT_USERPWD, $razorpay_key_id . ':' . $razorpay_secret_key);
            
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }
            curl_close($ch);
            $res =  json_decode($result,true);
            $arr = array(
                 'status'=>'true',
                 'order_id'=>$res['id']
            );
       }else{
             $arr = array(
                 'status'=>'false',
                 'msg'=>$this->lang->line('ltr_missing_parameters_msg')
             );
       } 
        echo json_encode($arr,JSON_UNESCAPED_SLASHES);
        die;
    
    }  
    
    function deleteAccount(){
        $data = $_REQUEST;
        $payload = $this->require_auth_payload(array('student'));
        if ($payload === false) {
            return;
        }

        $student_id = (int) $payload['uid'];
        if ($student_id > 0) {
            if ($this->authorize_student_request($student_id) === false) {
                return;
            }
            $check = $this->db_model->update_data_limit('students use index (id)',array('status'=>0,),array('id'=>$student_id),1);
            if($check){
               $arr = array(
                   'status'=>true,
                   'message'=>$this->lang->line('ltr_accountDelete')
                   );
            }else{
               $arr = array(
                   'status'=>false,
                   'message'=>$this->lang->line('ltr_something_msg')
                   );
            }
        }else{
             $arr = array(
                 'status'=>false,
                 'message'=>'Invalid token user'
             );
        }
        echo json_encode($arr,JSON_UNESCAPED_SLASHES);
        die;
    }

	/**
	 * Student login payload for `data` — used by password login and verify_otp (same shape).
	 */
	private function build_student_login_data_array(array $student, $device_id, $device_token, $device_type, $now, $access_token)
	{
		$profile_completed = empty($student['city']) ? 0 : 1;

		$student_image = !empty($student['image'])
			? base_url('uploads/students/') . $student['image']
			: '';

		return array(
			'userType'     => 'student',
			'studentId'    => (int) $student['id'],
			'adminId'      => (int) $student['admin_id'],
			'name'         => $student['name'],
			'email'        => $student['email'],
			'mobile'       => $student['contact_no'],
			'contactNo'    => $student['contact_no'],
			'mobileAlt'    => isset($student['mobile']) ? $student['mobile'] : '',
			'enrollmentId' => $student['enrollment_id'],
			'multiBatch'   => $student['multi_batch'],
			'gender'       => $student['gender'],
			'dob'          => $student['dob'],
			'fatherName'   => $student['father_name'],
			'fatherDesignation' => $student['father_designtn'],
			'address'      => $student['address'],
			'pincode'      => $student['pincode'],
			'country'      => $student['country'],
			'state'        => $student['state'],
			'city'         => $student['city'],
			'batchId'      => $student['batch_id'],
			'admissionDate' => $student['admission_date'],
			'status'       => (int) $student['status'],
			'loginStatus'  => 1,
			'paymentStatus' => (int) $student['payment_status'],
			'appVersion'   => $student['app_version'],
			'payMode'      => (int) $student['pay_mode'],
			'schoolCollegeName' => isset($student['school_college_name']) ? $student['school_college_name'] : '',
			'grade'        => isset($student['grade']) ? $student['grade'] : '',
			'isVerified'   => isset($student['is_verified']) ? $student['is_verified'] : null,
			'userTypeDb'   => isset($student['user_type']) ? $student['user_type'] : 'student',
			'addedBy'      => $student['added_by'],
			'lastLoginApp' => $now,
			'updatedAt'    => isset($student['updated_at']) ? $student['updated_at'] : null,
			'image'        => $student_image,
			'device_id'    => $device_id,
			'device_token' => $device_token,
			'device_type'  => $device_type,
			'is_profile_completed' => $profile_completed,
			'access_token' => $access_token,
			'token_type'   => 'Bearer',
		);
	}

	/**
	 * Teacher/Institute login payload in the same key shape as student login payload.
	 * Missing fields are returned as blank/default values for app compatibility.
	 */
	private function build_non_student_login_data_array(array $user, $device_id, $device_token, $device_type, $now, $access_token, $profile_completed)
	{
		$user_image = !empty($user['image'])
			? base_url('uploads/users/') . $user['image']
			: (!empty($user['teach_image']) ? base_url('uploads/users/') . $user['teach_image'] : '');

		$mobile = isset($user['mobile']) ? $user['mobile'] : '';
		$city = isset($user['city']) ? $user['city'] : '';
		$admin_id = isset($user['admin_id']) ? (int) $user['admin_id'] : 0;

		return array(
			'userType'     => isset($user['user_type']) ? $user['user_type'] : 'teacher',
			'studentId'    => (int) $user['id'],
			'userId'       => (int) $user['id'],
			'adminId'      => $admin_id,
			'name'         => isset($user['name']) ? $user['name'] : '',
			'email'        => isset($user['email']) ? $user['email'] : '',
			'mobile'       => $mobile,
			'contactNo'    => $mobile,
			'mobileAlt'    => $mobile,
			'enrollmentId' => '',
			'multiBatch'   => '',
			'gender'       => isset($user['teach_gender']) ? $user['teach_gender'] : '',
			'dob'          => isset($user['dob']) ? $user['dob'] : '',
			'fatherName'   => '',
			'fatherDesignation' => '',
			'address'      => isset($user['address']) ? $user['address'] : '',
			'pincode'      => isset($user['pincode']) ? $user['pincode'] : '',
			'country'      => isset($user['country']) ? $user['country'] : '',
			'state'        => isset($user['state']) ? $user['state'] : '',
			'city'         => $city,
			'batchId'      => isset($user['teach_batch']) ? $user['teach_batch'] : '',
			'admissionDate' => '',
			'status'       => isset($user['status']) ? (int) $user['status'] : 1,
			'loginStatus'  => 1,
			'paymentStatus' => 0,
			'appVersion'   => '',
			'payMode'      => 0,
			'schoolCollegeName' => '',
			'grade'        => '',
			'isVerified'   => isset($user['is_verified']) ? $user['is_verified'] : '',
			'userTypeDb'   => isset($user['user_type']) ? $user['user_type'] : '',
			'addedBy'      => isset($user['added_by']) ? $user['added_by'] : '',
			'lastLoginApp' => $now,
			'updatedAt'    => isset($user['updated_at']) ? $user['updated_at'] : null,
			'image'        => $user_image,
			'device_id'    => $device_id,
			'device_token' => $device_token,
			'device_type'  => $device_type,
			'role'         => isset($user['role']) ? $user['role'] : '',
			'is_profile_completed' => (int) $profile_completed,
			'access_token' => $access_token,
			'token_type'   => 'Bearer',
		);
	}

	/**
	 * Same JSON envelope as password login — use for verify_otp success so clients get identical shape.
	 */
	private function json_login_success_response(array $data)
	{
		echo json_encode(array(
			'status' => 'true',
			'msg' => 'Login successful',
			'data' => $data,
		), JSON_UNESCAPED_SLASHES);
	}

    // GET OTP
    public function send_otp() {
	    // Get JSON input
	    $data = json_decode(file_get_contents("php://input"), true);
	    if (empty($data)) {
	        $data = $this->input->post();
	    }

	    // Sanitize inputs
	    $mobile     = isset($data['mobile']) ? trim($data['mobile']) : '';
	    $user_type  = isset($data['user_type']) ? strtolower(trim($data['user_type'])) : '';

	    // Validate required fields
	    if (empty($mobile)) {
	        echo json_encode([
	            "status" => false,
	            "msg" => "Mobile number required"
	        ]);
	        return;
	    }

	    if (empty($user_type)) {
	        echo json_encode([
	            "status" => false,
	            "msg" => "User type required (student/teacher/institute)"
	        ]);
	        return;
	    }

	    if (!in_array($user_type, array('student', 'teacher', 'institute'), true)) {
	        echo json_encode([
	            "status" => false,
	            "msg" => "Invalid user type. Use student, teacher, or institute.",
	            "code" => "INVALID_USER_TYPE"
	        ]);
	        return;
	    }

	    // Optional: validate mobile format
	    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
	        echo json_encode([
	            "status" => false,
	            "msg" => "Invalid mobile number",
	            "code" => "INVALID_MOBILE"
	        ]);
	        return;
	    }

	    if (!$this->db_model->otp_account_exists($mobile, $user_type)) {
	        echo json_encode([
	            "status" => false,
	            "msg" => "This mobile number is not registered. Please sign up or use a registered number.",
	            "code" => "MOBILE_NOT_REGISTERED"
	        ]);
	        return;
	    }

	    // Generate OTP
	    $otp = rand(1000, 9999);
		
		$this->db_model->update_otp($mobile, $otp, $user_type);
	   
	    echo json_encode([
	        "status" => true,
	        "msg" => "OTP sent successfully. Your OTP is $otp",
	        "data" => [
	            "mobile" => $mobile,
	            "user_type" => $user_type
	        ],
	        "otp" => $otp // remove in production
	    ]);
	}

    // Verify OTP
    public function verify_otp()
	{
	    // Get JSON input
	    $data = json_decode(file_get_contents("php://input"), true);
	    if (empty($data)) {
	        $data = $this->input->post();
	    }
	    if (!is_array($data)) {
	        $data = array();
	    }
	    $data = $this->normalize_multi_user_registration_data($data);

	    $mobile    = isset($data['mobile']) ? trim((string) $data['mobile']) : '';
	    $otp       = isset($data['otp']) ? trim((string) $data['otp']) : '';
	    $user_type = isset($data['user_type']) ? strtolower(trim((string) $data['user_type'])) : '';

	    // Validation
	    if (empty($mobile) || empty($otp)) {
	        echo json_encode([
	            "status" => false,
	            "msg" => "Mobile & OTP required"
	        ]);
	        return;
	    }

	    if (empty($user_type)) {
	        echo json_encode([
	            "status" => false,
	            "msg" => "User type required",
	            "code" => "USER_TYPE_REQUIRED"
	        ]);
	        return;
	    }

	    if (!in_array($user_type, array('student', 'teacher', 'institute'), true)) {
	        echo json_encode([
	            "status" => false,
	            "msg" => "Invalid user type. Use student, teacher, or institute.",
	            "code" => "INVALID_USER_TYPE"
	        ]);
	        return;
	    }

	    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
	        echo json_encode([
	            "status" => false,
	            "msg" => "Invalid mobile number",
	            "code" => "INVALID_MOBILE"
	        ]);
	        return;
	    }

	    if (!$this->db_model->otp_account_exists($mobile, $user_type)) {
	        echo json_encode([
	            "status" => false,
	            "msg" => "This mobile number is not registered. Please sign up first, then request an OTP.",
	            "code" => "MOBILE_NOT_REGISTERED"
	        ]);
	        return;
	    }

	    $user = $this->db_model->verify_otp($mobile, $otp, $user_type);
	   
	    if ($user) {
	        $this->db_model->otp_verified($mobile, $otp, $user_type);

	        $device_id = isset($data['device_id']) ? trim($data['device_id']) : '';
	        $device_token = isset($data['device_token']) ? trim($data['device_token']) : '';
	        $device_type = isset($data['device_type']) ? trim($data['device_type']) : '';

	        if ($user_type === 'student') {
	        	$tok = $this->mint_access_credentials($user->id, 'student');
	        	$access_token = $tok['access_token'];
	        	$session_time = date('Y-m-d H:i:s', $tok['iat']);
	        	$this->db_model->update_data_limit('students', array(
	        		'login_status' => 1,
	        		'last_login_app' => $session_time,
	        		'token' => $device_token,
	        		'device_id' => $device_id,
	        		'device_token' => $device_token,
	        		'device_type' => $device_type,
	        	), array('id' => $user->id), 1);

	        	$fresh = $this->db_model->select_data('*', 'students use index (id)', array('id' => $user->id), 1);
	        	if (empty($fresh)) {
	        		echo json_encode(array('status' => 'false', 'msg' => 'Student not found'));
	        		return;
	        	}
	        	$response_data = $this->build_student_login_data_array($fresh[0], $device_id, $device_token, $device_type, $session_time, $access_token);

	        	$this->json_login_success_response($response_data);
	        	return;
	        }

	        $utoken = isset($user->user_type) ? strtolower(trim((string) $user->user_type)) : $user_type;
	        $tok = $this->mint_access_credentials((int) $user->id, $utoken);
	        $access_token = $tok['access_token'];
	        $session_time = date('Y-m-d H:i:s', $tok['iat']);
	        $this->db_model->update_data_limit('users', array(
	        	'login_status' => 1,
	        	'device_id' => $device_id,
	        	'device_token' => $device_token,
	        	'device_type' => $device_type,
	        	'updated_at' => $session_time,
	        ), array('id' => $user->id), 1);

	        $urow = $this->db_model->select_data('*', 'users', array('id' => $user->id), 1);
	        if (empty($urow)) {
	        	echo json_encode(array('status' => 'false', 'msg' => 'User not found'));
	        	return;
	        }
	        $u = $urow[0];
	        $profile_completed = (empty($u['city']) || trim((string) $u['city']) === '') ? 0 : 1;

	        $response_data = $this->build_non_student_login_data_array(
	        	$u,
	        	$device_id,
	        	$device_token,
	        	$device_type,
	        	$session_time,
	        	$access_token,
	        	$profile_completed
	        );
	        $this->json_login_success_response($response_data);
	        return;

	    } else {

	        echo json_encode([
	            "status" => false,
	            "msg" => "Invalid or expired OTP. Please check the code or request a new OTP from Send OTP.",
	            "code" => "INVALID_OTP"
	        ]);
	    }
	}

	/**
	 * POST/GET api/user/change-password
	 * Auth: Bearer access_token (logged-in student, teacher, or institute).
	 * Body (JSON, form, or query): current_password, new_password, confirm_password
	 * Aliases: current_pass, new_pass, confirm_pass
	 */
	public function change_password()
	{
		$from_body = json_decode(file_get_contents('php://input'), true);
		if (!is_array($from_body)) {
			$from_body = array();
		}
		$post = $this->input->post();
		$get = $this->input->get();
		if (!is_array($post)) {
			$post = array();
		}
		if (!is_array($get)) {
			$get = array();
		}
		// JSON body must win — merging body first then post/get overwrites correct passwords with empty fields.
		$data = array_merge($post, $get, $from_body);

		$payload = $this->require_auth_payload();
		if ($payload === false) {
			return;
		}

		$current = '';
		if (isset($data['current_password']) && $data['current_password'] !== '') {
			$current = trim((string) $data['current_password']);
		} elseif (isset($data['current_pass']) && $data['current_pass'] !== '') {
			$current = trim((string) $data['current_pass']);
		}

		$new = '';
		if (isset($data['new_password']) && $data['new_password'] !== '') {
			$new = (string) $data['new_password'];
		} elseif (isset($data['new_pass']) && $data['new_pass'] !== '') {
			$new = (string) $data['new_pass'];
		}

		$confirm = '';
		if (isset($data['confirm_password']) && $data['confirm_password'] !== '') {
			$confirm = (string) $data['confirm_password'];
		} elseif (isset($data['confirm_pass']) && $data['confirm_pass'] !== '') {
			$confirm = (string) $data['confirm_pass'];
		}

		if ($current === '' || $new === '' || $confirm === '') {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'current_password, new_password and confirm_password are required',
				'code' => 'MISSING_PARAMETERS',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		if ($new !== $confirm) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'New password and confirm password do not match',
				'code' => 'PASSWORD_MISMATCH',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		if (strlen($new) < 6) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'New password must be at least 6 characters',
				'code' => 'PASSWORD_TOO_SHORT',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		$uid = (int) $payload['uid'];
		$ut = strtolower(trim((string) $payload['ut']));

		if (!in_array($ut, array('student', 'teacher', 'institute'), true)) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Password change is not available for this account type',
				'code' => 'INVALID_USER_TYPE',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		if ($ut === 'student') {
			$rows = $this->db_model->select_data('id,password', 'students', array('id' => $uid), 1);
		} else {
			$rows = $this->db_model->select_data('id,password,user_type', 'users', array('id' => $uid), 1);
			if (!empty($rows[0]) && isset($rows[0]['user_type']) && trim((string) $rows[0]['user_type']) !== '') {
				$db_ut = strtolower(trim((string) $rows[0]['user_type']));
				if ($db_ut !== $ut) {
					echo json_encode(array(
						'status' => 'false',
						'msg' => 'Token does not match this account',
						'code' => 'USER_MISMATCH',
					), JSON_UNESCAPED_SLASHES);
					return;
				}
			}
		}

		if (empty($rows)) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'User not found',
				'code' => 'USER_NOT_FOUND',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		$stored = $this->row_password_value($rows[0]);
		if (!$this->is_valid_password($current, $stored)) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Current password is incorrect',
				'code' => 'INVALID_CURRENT_PASSWORD',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		if ($this->is_valid_password($new, $stored)) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'New password must be different from current password',
				'code' => 'SAME_PASSWORD',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		$hashed_new = password_hash($new, PASSWORD_DEFAULT);

		if ($ut === 'student') {
			$update = $this->db_model->update_data_limit(
				'students use index (id)',
				array('password' => $hashed_new),
				array('id' => $uid),
				1
			);
		} else {
			$update = $this->db_model->update_data_limit(
				'users',
				array('password' => $hashed_new),
				array('id' => $uid),
				1
			);
		}

		if ($update) {
			echo json_encode(array(
				'status' => 'true',
				'msg' => 'Password changed successfully',
			), JSON_UNESCAPED_SLASHES);
		} else {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Failed to update password',
				'code' => 'UPDATE_FAILED',
			), JSON_UNESCAPED_SLASHES);
		}
	}
	
	/**
	 * POST api/user/update-password
	 * Body (JSON or form): mobile, password, confirm_password, user_type (student|teacher|institute)
	 * Student lookup: mobile OR contact_no (same as OTP flow).
	 */
	public function update_password()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (empty($data)) {
			$data = $this->input->post();
		}
		if (empty($data)) {
			$data = $this->input->get();
		}

		$mobile = isset($data['mobile']) ? trim($data['mobile']) : '';
		$password = isset($data['password']) ? $data['password'] : '';
		$confirm_password = isset($data['confirm_password']) ? $data['confirm_password'] : '';
		$user_type = isset($data['user_type']) ? strtolower(trim($data['user_type'])) : '';

		if ($mobile === '' || $password === '' || $confirm_password === '') {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Mobile, password and confirm_password are required',
				'code' => 'MISSING_PARAMETERS',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		if ($user_type === '') {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'user_type is required (student, teacher, institute)',
				'code' => 'USER_TYPE_REQUIRED',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		if (!in_array($user_type, array('student', 'teacher', 'institute'), true)) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Invalid user_type',
				'code' => 'INVALID_USER_TYPE',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		if (!preg_match('/^[0-9]{10}$/', $mobile)) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Invalid mobile number (10 digits)',
				'code' => 'INVALID_MOBILE',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		if ($password !== $confirm_password) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Password and confirm password do not match',
				'code' => 'PASSWORD_MISMATCH',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		if (strlen($password) < 6) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Password must be at least 6 characters',
				'code' => 'PASSWORD_TOO_SHORT',
			), JSON_UNESCAPED_SLASHES);
			return;
		}

		$hashed_password = password_hash($password, PASSWORD_BCRYPT);

		if ($user_type === 'student') {
			$this->db->from('students');
			$this->db->group_start();
			$this->db->where('mobile', $mobile);
			$this->db->or_where('contact_no', $mobile);
			$this->db->group_end();
			$row = $this->db->get()->row();
			if (empty($row)) {
				echo json_encode(array(
					'status' => 'false',
					'msg' => 'No account found with this mobile number',
					'code' => 'USER_NOT_FOUND',
				), JSON_UNESCAPED_SLASHES);
				return;
			}
			$update = $this->db_model->update_data_limit(
				'students use index (id)',
				array('password' => $hashed_password),
				array('id' => $row->id),
				1
			);
		} else {
			$user = $this->db_model->select_data(
				'id',
				'users',
				array('mobile' => $mobile, 'user_type' => $user_type),
				1
			);
			if (empty($user)) {
				echo json_encode(array(
					'status' => 'false',
					'msg' => 'No account found with this mobile number',
					'code' => 'USER_NOT_FOUND',
				), JSON_UNESCAPED_SLASHES);
				return;
			}
			$update = $this->db_model->update_data_limit(
				'users',
				array('password' => $hashed_password),
				array('id' => $user[0]['id']),
				1
			);
		}

		if ($update) {
			echo json_encode(array(
				'status' => 'true',
				'msg' => 'Password updated successfully',
			), JSON_UNESCAPED_SLASHES);
		} else {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Failed to update password',
				'code' => 'UPDATE_FAILED',
			), JSON_UNESCAPED_SLASHES);
		}
	}
	
	
	
	
}