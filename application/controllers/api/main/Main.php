<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends MY_Controller
{
	/**
	 * GET/POST api/main/site-details
	 */
	public function site_details()
	{
		$site = $this->db_model->select_data(
			'id,site_title,site_logo,timezone',
			'site_details',
			'',
			1,
			array('id', 'desc')
		);

		if (empty($site)) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => $this->lang->line('ltr_no_record_msg')
			));
			return;
		}

		$row = $site[0];
		$logo = !empty($row['site_logo']) ? base_url('uploads/site_data/') . $row['site_logo'] : '';

		echo json_encode(array(
			'status' => 'true',
			'msg' => $this->lang->line('ltr_fetch_successfully'),
			'siteDetails' => array(
				'id' => (int) $row['id'],
				'siteTitle' => isset($row['site_title']) ? $row['site_title'] : '',
				'siteLogo' => isset($row['site_logo']) ? $row['site_logo'] : '',
				'siteLogoUrl' => $logo,
				'timezone' => isset($row['timezone']) ? $row['timezone'] : ''
			)
		));
	}

	/**
	 * GET/POST api/main/notifications-list
	 * Auth:
	 *  - student: own notifications by student_id
	 *  - teacher: batch notifications for teacher's mapped batches
	 */
	public function notifications_list()
	{
		$data = $_REQUEST;
		$payload = $this->require_auth_payload();
		if ($payload === false) {
			return;
		}

		$limit = isset($data['limit']) ? (int) $data['limit'] : 20;
		if ($limit < 1) {
			$limit = 20;
		}
		if ($limit > 100) {
			$limit = 100;
		}

		$page = isset($data['page']) ? (int) $data['page'] : 1;
		if ($page < 1) {
			$page = 1;
		}
		$offset = ($page - 1) * $limit;

		$this->db->select('id,student_id as studentId,batch_id as batchId,notification_type as notificationType,msg,url,status,time,seen_by as seenBy');
		$this->db->from('notifications');

		if ($payload['ut'] === 'student') {
			$this->db->where('student_id', (int) $payload['uid']);
		} elseif ($payload['ut'] === 'teacher') {
			$teacher_id = (int) $payload['uid'];
			$rows = $this->db_model->select_data('batch_id', 'batch_subjects', array('teacher_id' => $teacher_id));

			$batch_ids = array();
			if (!empty($rows)) {
				foreach ($rows as $r) {
					$bid = isset($r['batch_id']) ? (int) $r['batch_id'] : 0;
					if ($bid > 0 && !in_array($bid, $batch_ids, true)) {
						$batch_ids[] = $bid;
					}
				}
			}

			if (empty($batch_ids)) {
				echo json_encode(array(
					'status' => 'true',
					'userType' => 'teacher',
					'notifications' => array(),
					'pagination' => array(
						'page' => $page,
						'limit' => $limit,
						'total' => 0
					),
					'msg' => $this->lang->line('ltr_no_record_msg')
				));
				return;
			}
			$this->db->where_in('batch_id', $batch_ids);
		} else {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Notifications are available for student and teacher only'
			));
			return;
		}

		if (!empty($data['notification_type'])) {
			$this->db->where('notification_type', $data['notification_type']);
		}

		$this->db->order_by('id', 'DESC');
		$this->db->limit($limit, $offset);
		$list = $this->db->get()->result_array();

		// Count query with same filters
		$this->db->from('notifications');
		if ($payload['ut'] === 'student') {
			$this->db->where('student_id', (int) $payload['uid']);
		} else {
			$rows = $this->db_model->select_data('batch_id', 'batch_subjects', array('teacher_id' => (int) $payload['uid']));
			$batch_ids = array();
			if (!empty($rows)) {
				foreach ($rows as $r) {
					$bid = isset($r['batch_id']) ? (int) $r['batch_id'] : 0;
					if ($bid > 0 && !in_array($bid, $batch_ids, true)) {
						$batch_ids[] = $bid;
					}
				}
			}
			if (!empty($batch_ids)) {
				$this->db->where_in('batch_id', $batch_ids);
			} else {
				$this->db->where('1', '0');
			}
		}
		if (!empty($data['notification_type'])) {
			$this->db->where('notification_type', $data['notification_type']);
		}
		$total = (int) $this->db->count_all_results();

		echo json_encode(array(
			'status' => 'true',
			'userType' => (string) $payload['ut'],
			'notifications' => !empty($list) ? $list : array(),
			'pagination' => array(
				'page' => $page,
				'limit' => $limit,
				'total' => $total
			),
			'msg' => !empty($list) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg')
		));
	}

	/**
	 * GET/POST api/main/pages
	 * Optional param: page_type (about_us | privacy_policy | terms_condition)
	 */
	public function pages()
	{
		$data = $_REQUEST;
		$page_type = isset($data['page_type']) ? strtolower(trim($data['page_type'])) : '';

		$rows = $this->db_model->select_data(
			'id,subject,content,status,updated_at,created_at',
			'pages',
			array('status' => 1),
			'',
			array('id', 'desc')
		);

		$mapped = array(
			'about_us' => array(),
			'privacy_policy' => array(),
			'terms_condition' => array()
		);

		if (!empty($rows)) {
			foreach ($rows as $r) {
				$subject = strtolower(trim((string) $r['subject']));
				$item = array(
					'id' => (int) $r['id'],
					'subject' => isset($r['subject']) ? $r['subject'] : '',
					'content' => isset($r['content']) ? $r['content'] : '',
					'updatedAt' => isset($r['updated_at']) ? $r['updated_at'] : '',
					'createdAt' => isset($r['created_at']) ? $r['created_at'] : ''
				);

				if (strpos($subject, 'about') !== false) {
					$mapped['about_us'] = $item;
				} elseif (strpos($subject, 'privacy') !== false || strpos($subject, 'policy') !== false) {
					$mapped['privacy_policy'] = $item;
				} elseif (strpos($subject, 'term') !== false || strpos($subject, 'condition') !== false) {
					$mapped['terms_condition'] = $item;
				}
			}
		}

		if ($page_type !== '') {
			if (!array_key_exists($page_type, $mapped)) {
				echo json_encode(array(
					'status' => 'false',
					'msg' => 'Invalid page_type. Use about_us, privacy_policy, or terms_condition'
				));
				return;
			}

			echo json_encode(array(
				'status' => !empty($mapped[$page_type]) ? 'true' : 'false',
				'pageType' => $page_type,
				'data' => !empty($mapped[$page_type]) ? $mapped[$page_type] : array(),
				'msg' => !empty($mapped[$page_type]) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg')
			));
			return;
		}

		echo json_encode(array(
			'status' => 'true',
			'data' => $mapped,
			'msg' => $this->lang->line('ltr_fetch_successfully')
		));
	}

	/**
	 * POST api/main/post-enquiry
	 * Required: name, mobile, email, subject, message
	 */
	public function post_enquiry()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$name = isset($data['name']) ? trim($data['name']) : '';
		$mobile = isset($data['mobile']) ? trim($data['mobile']) : '';
		$email = isset($data['email']) ? trim($data['email']) : '';
		$subject = isset($data['subject']) ? trim($data['subject']) : '';
		$message = isset($data['message']) ? trim($data['message']) : '';

		if ($name === '' || $mobile === '' || $email === '' || $subject === '' || $message === '') {
			echo json_encode(array(
				'status' => 'false',
				'msg' => $this->lang->line('ltr_missing_parameters_msg')
			));
			return;
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Please enter a valid email'
			));
			return;
		}

		$insert = array(
			'name' => $name,
			'mobile' => $mobile,
			'email' => $email,
			'subject' => $subject,
			'message' => $message,
			'date' => date('Y-m-d')
		);
		$insert = $this->security->xss_clean($insert);
		$ins_id = $this->db_model->insert_data('enquiry', $insert);

		echo json_encode(array(
			'status' => !empty($ins_id) ? 'true' : 'false',
			'msg' => !empty($ins_id) ? 'Enquiry submitted successfully' : 'Failed to submit enquiry'
		));
	}

	/**
	 * GET/POST api/main/country-list
	 * Optional: id (country id) — returns one country; omit for all countries.
	 */
	public function country_list()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$where = '';
		if (isset($data['id']) && $data['id'] !== '') {
			$cid = (int) $data['id'];
			if ($cid > 0) {
				$where = array('id' => $cid);
			}
		}

		$rows = $this->db_model->select_data(
			'id,countryCode,name',
			'countries',
			$where,
			'',
			array('name', 'asc')
		);

		$out = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$out[] = array(
					'id' => (int) $r['id'],
					'countryCode' => isset($r['countryCode']) ? $r['countryCode'] : (isset($r['countrycode']) ? $r['countrycode'] : ''),
					'name' => isset($r['name']) ? $r['name'] : ''
				);
			}
		}

		echo json_encode(array(
			'status' => !empty($out) ? 'true' : 'false',
			'countries' => $out,
			'msg' => !empty($out) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg')
		));
	}

	/**
	 * GET/POST api/main/state-list
	 * Required: country_id
	 */
	public function state_list()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$country_id = isset($data['country_id']) ? (int) $data['country_id'] : 0;
		if ($country_id < 1) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'country_id is required'
			));
			return;
		}

		$rows = $this->db_model->select_data(
			'id,name,country_id',
			'states',
			array('country_id' => $country_id),
			'',
			array('name', 'asc')
		);

		$out = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$out[] = array(
					'id' => (int) $r['id'],
					'name' => isset($r['name']) ? $r['name'] : '',
					'countryId' => isset($r['country_id']) ? (int) $r['country_id'] : $country_id
				);
			}
		}

		echo json_encode(array(
			'status' => !empty($out) ? 'true' : 'false',
			'countryId' => $country_id,
			'states' => $out,
			'msg' => !empty($out) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg')
		));
	}

	/**
	 * GET/POST api/main/city-list
	 * Required: state_id
	 */
	public function city_list()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$state_id = isset($data['state_id']) ? (int) $data['state_id'] : 0;
		if ($state_id < 1) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'state_id is required'
			));
			return;
		}

		$rows = $this->db_model->select_data(
			'id,city,state_id',
			'cities',
			array('state_id' => $state_id),
			'',
			array('city', 'asc')
		);

		$out = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$out[] = array(
					'id' => (int) $r['id'],
					'city' => isset($r['city']) ? $r['city'] : '',
					'stateId' => isset($r['state_id']) ? (int) $r['state_id'] : $state_id
				);
			}
		}

		echo json_encode(array(
			'status' => !empty($out) ? 'true' : 'false',
			'stateId' => $state_id,
			'cities' => $out,
			'msg' => !empty($out) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg')
		));
	}
}
