<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Student batch APIs: enrolled list (dashboard) and batch detail with module summaries.
 * Auth: Bearer access_token from multi_user_login (student).
 * Shared token helpers: {@see MY_Controller}.
 */
class Batch extends MY_Controller
{
	private function read_request_data()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = array();
		}
		return array_merge($data, $this->input->post(), $this->input->get());
	}

	private function format_time_range($start_time, $end_time)
	{
		$start_ts = strtotime($start_time);
		$end_ts = strtotime($end_time);
		if ($start_ts && $end_ts) {
			return date('g:i a', $start_ts) . ' - ' . date('g:i a', $end_ts);
		}
		return trim($start_time . ' - ' . $end_time);
	}

	private function teacher_names_for_batch($batch_id)
	{
		$rows = $this->db_model->select_data(
			'users.name',
			'batch_subjects use index (id)',
			array('batch_subjects.batch_id' => $batch_id),
			'',
			array('batch_subjects.id', 'asc'),
			'',
			array('users', 'users.id = batch_subjects.teacher_id')
		);
		if (empty($rows)) {
			return '';
		}
		$names = array();
		foreach ($rows as $r) {
			if (!empty($r['name']) && !in_array($r['name'], $names, true)) {
				$names[] = $r['name'];
			}
		}
		return implode(', ', $names);
	}

	private function build_slider_banners()
	{
		$banners = array();
		$row = $this->db_model->select_data('slider_details', 'frontend_details', array('id' => 1), 1);
		if (empty($row[0]['slider_details'])) {
			return $banners;
		}
		$sliders = json_decode($row[0]['slider_details'], true);
		if (!is_array($sliders) || empty($sliders['slider_img'])) {
			return $banners;
		}
		$count = count($sliders['slider_img']);
		for ($i = 0; $i < $count; $i++) {
			$img = isset($sliders['slider_img'][$i]) ? $sliders['slider_img'][$i] : '';
			if ($img === '') {
				continue;
			}
			$banners[] = array(
				'id' => $i + 1,
				'image_url' => base_url('uploads/site_data/') . $img,
				'heading' => isset($sliders['slider_heading'][$i]) ? $sliders['slider_heading'][$i] : '',
				'subheading' => isset($sliders['slider_subheading'][$i]) ? $sliders['slider_subheading'][$i] : '',
				'description' => isset($sliders['slider_desc'][$i]) ? $sliders['slider_desc'][$i] : ''
			);
		}
		return $banners;
	}

	/**
	 * POST/GET api/batch/batch-list
	 * Optional: search (filters batch_name)
	 */
	public function batch_list()
	{
		$data = $this->read_request_data();
		$token = $this->get_access_token_from_request();
		$payload = $this->parse_access_token($token);
		
		$student_id = (int) $payload['uid'];
		if ($student_id < 1 || $this->authorize_student_request($student_id) === false) {
			return;
		}

		$student = $this->db_model->select_data(
			'id,name,image,email',
			'students use index (id)',
			array('id' => $student_id, 'status' => 1),
			1
		);
		if (empty($student)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Student not found'));
			return;
		}

		$search = isset($data['search']) ? trim($data['search']) : '';
		$like = ($search !== '') ? array('batches.batch_name', $search) : '';

		$batches = $this->db_model->select_data(
			'batches.*, sudent_batchs.status as enrollment_status, sudent_batchs.create_at as enrolled_at',
			'batches use index (id)',
			array('batches.status' => '1', 'sudent_batchs.student_id' => $student_id),
			'',
			array('batches.id', 'desc'),
			$like,
			array('sudent_batchs', 'sudent_batchs.batch_id = batches.id')
		);

		$list = array();
		if (!empty($batches)) {
			foreach ($batches as $b) {
				$bid = (int) $b['id'];
				$logo = '';
				if (!empty($b['batch_image'])) {
					$logo = base_url('uploads/batch_image/') . $b['batch_image'];
				}
				$list[] = array(
					'batch_id' => $bid,
					'title' => $b['batch_name'],
					'batchName' => $b['batch_name'],
					'instructor' => $this->teacher_names_for_batch($bid),
					'schedule' => $this->format_time_range($b['start_time'], $b['end_time']),
					'start_time' => $b['start_time'],
					'end_time' => $b['end_time'],
					'start_date' => $b['start_date'],
					'end_date' => $b['end_date'],
					'logo' => $logo,
					'batchImage' => $logo,
					'batch_type' => (int) $b['batch_type'],
					'description' => $b['description'],
					'enrollment_status' => isset($b['enrollment_status']) ? (int) $b['enrollment_status'] : 0,
					'enrolled_at' => isset($b['enrolled_at']) ? $b['enrolled_at'] : ''
				);
			}
		}

		$arr = array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'banners' => $this->build_slider_banners(),
				'enrolled_batches' => $list
			)
		);

		echo json_encode($arr,JSON_UNESCAPED_SLASHES);
        die;

	}

	/**
	 * POST/GET api/batch/batch-details
	 * Required: batch_id
	 */
	public function batch_details()
	{
		$data = $this->read_request_data();
		$token = $this->get_access_token_from_request();
		$payload = $this->parse_access_token($token);

		if ($payload === false || $payload['ut'] !== 'student') {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Unauthorized: invalid or expired access token'
			));
			return;
		}

		$student_id = (int) $payload['uid'];
		if ($student_id < 1 || $this->authorize_student_request($student_id) === false) {
			return;
		}

		if (empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id is required'));
			return;
		}

		$batch_id = (int) $data['batch_id'];
		$enrollment = $this->db_model->select_data(
			'*',
			'sudent_batchs',
			array('student_id' => $student_id, 'batch_id' => $batch_id),
			1
		);
		if (empty($enrollment)) {
			echo json_encode(array('status' => 'false', 'msg' => 'You are not enrolled in this batch'));
			return;
		}

		$batch = $this->db_model->select_data('*', 'batches use index (id)', array('id' => $batch_id), 1);
		if (empty($batch)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Batch not found'));
			return;
		}

		$b = $batch[0];
		$admin_id = (int) $b['admin_id'];

		$logo = '';
		if (!empty($b['batch_image'])) {
			$logo = base_url('uploads/batch_image/') . $b['batch_image'];
		}

		$batch_fecherd = $this->db_model->select_data(
			'batch_specification_heading as batchSpecification, batch_fecherd as fecherd',
			'batch_fecherd',
			array('batch_id' => $batch_id)
		);
		if (empty($batch_fecherd)) {
			$batch_fecherd = array();
		}

		$live_row = $this->db_model->select_data(
			'id,end_time',
			'live_class_history',
			array('batch_id' => $batch_id),
			'1',
			array('id', 'desc')
		);
		$is_live = false;
		$current_session_id = null;
		if (!empty($live_row[0])) {
			$et = isset($live_row[0]['end_time']) ? trim((string) $live_row[0]['end_time']) : '';
			if ($et === '' || $et === '0000-00-00 00:00:00') {
				$is_live = true;
				$current_session_id = (int) $live_row[0]['id'];
			}
		}

		$batch_like = '"' . $batch_id . '"';
		$video_count = (int) $this->db_model->countAll(
			'video_lectures use index (id)',
			array('status' => 1, 'batch' => $batch_id)
		);
		$book_count = (int) $this->db_model->countAll(
			'book_pdf use index (id)',
			array('admin_id' => $admin_id),
			'',
			'',
			array('batch', $batch_like)
		);
		$notes_count = (int) $this->db_model->countAll(
			'notes_pdf use index (id)',
			array('admin_id' => $admin_id),
			'',
			'',
			array('batch', $batch_like)
		);

		$attendance_marked = (int) $this->db_model->countAll(
			'attendance',
			array('student_id' => $student_id, 'batch_id' => $batch_id)
		);

		$upcoming_exams = (int) $this->db_model->countAll(
			'exams use index (id)',
			array(
				'batch_id' => $batch_id,
				'status' => 1,
				'type' => 1,
				'mock_sheduled_date >=' => date('Y-m-d')
			)
		);

		$today = date('Y-m-d');
		$homework_today = (int) $this->db_model->countAll(
			'homeworks use index (id)',
			array('admin_id' => $admin_id, 'batch_id' => $batch_id, 'date' => $today)
		);
		$homework_upcoming = (int) $this->db_model->countAll(
			'homeworks use index (id)',
			array('admin_id' => $admin_id, 'batch_id' => $batch_id, 'date >=' => $today)
		);

		$category = $this->db_model->select_data('name', 'batch_category use index (id)', array('id' => $b['cat_id']), 1);
		$subcategory = $this->db_model->select_data('name', 'batch_subcategory use index (id)', array('id' => $b['sub_cat_id']), 1);

		$data = array(
			'batch_id' => $batch_id,
			'title' => $b['batch_name'],
			'batchName' => $b['batch_name'],
			'instructor' => $this->teacher_names_for_batch($batch_id),
			'schedule' => $this->format_time_range($b['start_time'], $b['end_time']),
			'start_time' => $b['start_time'],
			'end_time' => $b['end_time'],
			'start_date' => $b['start_date'],
			'end_date' => $b['end_date'],
			'logo' => $logo,
			'batchImage' => $logo,
			'description' => $b['description'],
			'batch_type' => (int) $b['batch_type'],
			'batch_price' => $b['batch_price'],
			'batch_offer_price' => $b['batch_offer_price'],
			'pay_mode' => $b['pay_mode'],
			'category_name' => !empty($category[0]['name']) ? $category[0]['name'] : '',
			'subcategory_name' => !empty($subcategory[0]['name']) ? $subcategory[0]['name'] : '',
			'batchFecherd' => $batch_fecherd,
			'enrollment' => array(
				'status' => (int) $enrollment[0]['status'],
				'create_at' => isset($enrollment[0]['create_at']) ? $enrollment[0]['create_at'] : '',
				'added_by' => isset($enrollment[0]['added_by']) ? $enrollment[0]['added_by'] : ''
			),
			'modules' => array(
				'live_classes' => array(
					'is_live' => $is_live,
					'current_session_id' => $current_session_id,
					'icon' => 'icofont-video-cam'
				),
				'video_lectures' => array('count' => $video_count, 'icon' => 'icofont-file-alt'),
				'library' => array(
					'book_count' => $book_count,
					'notes_count' => $notes_count,
					'has_new_content' => false,
					'icon' => 'icofont-book'
				),
				'attendance' => array(
					'marked_records' => $attendance_marked,
					'icon' => 'icofont-check-circled'
				),
				'upcoming_exams' => array('count' => $upcoming_exams, 'icon' => 'icofont-exam'),
				'homework' => array(
					'today_count' => $homework_today,
					'pending_count' => $homework_upcoming,
					'icon' => 'icofont-file-alt'
				)
			)
		);

		$arr = array(
			'status' => 'true',
			'message' => 'Success',
			'batch_details' => $data
		);
		echo json_encode($arr,JSON_UNESCAPED_SLASHES);
        die;
	}
}
