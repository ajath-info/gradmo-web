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
	 * book_pdf.batch is stored in different shapes in the wild:
	 * plain id ("1"), JSON string containing "1", or comma-separated ids ("1,2,3").
	 */
	private function apply_book_pdf_batch_filter($batch_id)
	{
		$bid = (int) $batch_id;
		$this->db->group_start();
		$this->db->like('batch', '"' . $bid . '"');
		$this->db->or_where('batch', (string) $bid);
		$this->db->or_where('batch', $bid);
		if ($bid > 0) {
			$this->db->or_where('FIND_IN_SET(' . (int) $bid . ', batch) > 0', null, false);
		}
		$this->db->group_end();
	}

	private function apply_text_batch_filter($column, $batch_id)
	{
		$bid = (int) $batch_id;
		$this->db->group_start();
		$this->db->like($column, '"' . $bid . '"');
		$this->db->or_where($column, (string) $bid);
		$this->db->or_where($column, $bid);
		if ($bid > 0) {
			$this->db->or_where('FIND_IN_SET(' . (int) $bid . ', ' . $column . ') > 0', null, false);
		}
		$this->db->group_end();
	}

	/**
	 * Enrolled student or teacher assigned in batch_subjects for this batch_id.
	 */
	private function assert_batch_access_student_or_teacher(array $payload, $batch_id)
	{
		$batch_id = (int) $batch_id;
		if ($batch_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Invalid batch'));
			return false;
		}
		$ut = strtolower(trim((string) $payload['ut']));
		$uid = (int) $payload['uid'];
		if ($ut === 'student') {
			if ($uid < 1 || $this->authorize_student_request($uid) === false) {
				return false;
			}
			$enrollment = $this->db_model->select_data('id', 'sudent_batchs', array('student_id' => $uid, 'batch_id' => $batch_id), 1);
			if (empty($enrollment)) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not enrolled in this batch'));
				return false;
			}
			return true;
		}
		if ($ut === 'teacher') {
			if ($uid < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
				return false;
			}
			$assigned = $this->db_model->select_data('id', 'batch_subjects', array('teacher_id' => $uid, 'batch_id' => $batch_id), 1);
			if (empty($assigned)) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not assigned to this batch'));
				return false;
			}
			return true;
		}
		echo json_encode(array('status' => 'false', 'msg' => 'This action is available for student and teacher only'));
		return false;
	}

	/**
	 * Teacher assigned to the batch, or institute that owns the batch (batches.admin_id).
	 */
	private function assert_batch_access_teacher_or_institute(array $payload, $batch_id)
	{
		$batch_id = (int) $batch_id;
		if ($batch_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Invalid batch'));
			return false;
		}
		$ut = strtolower(trim((string) $payload['ut']));
		$uid = (int) $payload['uid'];
		if ($ut === 'teacher') {
			if ($uid < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
				return false;
			}
			$assigned = $this->db_model->select_data('id', 'batch_subjects', array('teacher_id' => $uid, 'batch_id' => $batch_id), 1);
			if (empty($assigned)) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not assigned to this batch'));
				return false;
			}
			return true;
		}
		if ($ut === 'institute') {
			if ($uid < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'Institute not found'));
				return false;
			}
			$batch = $this->db_model->select_data('id,admin_id', 'batches use index (id)', array('id' => $batch_id), 1);
			if (empty($batch) || (int) $batch[0]['admin_id'] !== $uid) {
				echo json_encode(array('status' => 'false', 'msg' => 'This batch does not belong to your institute'));
				return false;
			}
			return true;
		}
		echo json_encode(array('status' => 'false', 'msg' => 'This action is available for teacher and institute only'));
		return false;
	}

	/**
	 * Single book_pdf row linked to batch_id (same matching rules as library list).
	 *
	 * @param bool $active_only If true, only status = 1.
	 * @return array|null
	 */
	private function get_book_pdf_for_batch($book_id, $batch_id, $active_only = true)
	{
		$book_id = (int) $book_id;
		$batch_id = (int) $batch_id;
		if ($book_id < 1 || $batch_id < 1) {
			return null;
		}
		$this->db->reset_query();
		$this->db->from('book_pdf');
		$this->db->where('id', $book_id);
		if ($active_only) {
			$this->db->where('status', 1);
		}
		$this->apply_book_pdf_batch_filter($batch_id);
		$row = $this->db->get()->row_array();
		return !empty($row) ? $row : null;
	}

	/** Video visible to teacher if it is mapped to any batch they teach. */
	private function video_accessible_to_teacher($video_id, $teacher_id)
	{
		$video_id = (int) $video_id;
		$teacher_id = (int) $teacher_id;
		if ($video_id < 1 || $teacher_id < 1) {
			return false;
		}
		$rows = $this->db_model->select_data('batch_id', 'batch_subjects', array('teacher_id' => $teacher_id), '');
		if (empty($rows)) {
			return false;
		}
		foreach ($rows as $r) {
			$bid = isset($r['batch_id']) ? (int) $r['batch_id'] : 0;
			if ($bid < 1) {
				continue;
			}
			$this->db->from('video_lectures');
			$this->db->where('id', $video_id);
			$this->db->where('status', 1);
			$this->apply_text_batch_filter('batch', $bid);
			if ((int) $this->db->count_all_results() > 0) {
				return true;
			}
		}
		return false;
	}


	/**
	 * POST/GET api/batch/batch-list
	 * Optional: search (filters batch_name); page (default 1); limit or per_page (default 20, max 100).
	 * Auth:
	 *   - student: enrolled batches from sudent_batchs
	 *   - teacher: assigned batches from batch_subjects
	 */
	public function batch_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		$search = isset($data['search']) ? trim($data['search']) : '';

		$pg = $this->parse_api_list_pagination($data);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		$total_records = 0;

		// STUDENT FLOW: existing behavior (enrolled batches)
		if ($payload['ut'] === 'student') {
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

			$total_records = $this->count_student_enrolled_batches_raw($student_id, $search);
			$batches = $this->fetch_student_enrolled_batches_raw($student_id, $search, $limit, $offset);
		}
		// TEACHER FLOW: batches assigned via batch_subjects.teacher_id
		elseif ($payload['ut'] === 'teacher') {
			$teacher_id = (int) $payload['uid'];
			if ($teacher_id < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
				return;
			}

			$total_records = $this->count_teacher_assigned_batches_raw($teacher_id, $search);
			$batches = $this->fetch_teacher_assigned_batches_raw($teacher_id, $search, $limit, $offset);
		} else {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Batch list is available for student and teacher only'
			));
			return;
		}

		$list = $this->map_batches_to_dashboard_list_cards(is_array($batches) ? $batches : array());

		$arr = array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'enrolled_batches' => $list,
				'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total_records),
			)
		);

		echo json_encode($arr,JSON_UNESCAPED_SLASHES);
        die;

	}

	/**
	 * POST/GET api/batch/slider-list
	 * Auth: any valid app token.
	 */
	public function slider_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		$banners = $this->build_slider_banners();
		$pg = $this->parse_api_list_pagination($data, 20, 100);
		$total = is_array($banners) ? count($banners) : 0;
		$banners_page = is_array($banners) ? array_slice($banners, $pg['offset'], $pg['limit']) : array();

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'banners' => $banners_page,
				'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total),
			)
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/batch-details
	 * Required: batch_id
	 * Auth: student (enrolled) or teacher (assigned via batch_subjects).
	 */
	public function batch_details()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		$ut = strtolower(trim((string) $payload['ut']));
		$uid = (int) $payload['uid'];

		if (empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id is required'));
			return;
		}

		$batch_id = (int) $data['batch_id'];
		$student_id = 0;
		$enrollment = array();

		if ($ut === 'student') {
			$student_id = $uid;
			if ($student_id < 1 || $this->authorize_student_request($student_id) === false) {
				return;
			}
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
		} elseif ($ut === 'teacher') {
			if ($uid < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
				return;
			}
			$assigned = $this->db_model->select_data('id', 'batch_subjects', array('teacher_id' => $uid, 'batch_id' => $batch_id), 1);
			if (empty($assigned)) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not assigned to this batch'));
				return;
			}
			$enrollment = array(array(
				'status' => 1,
				'create_at' => '',
				'added_by' => 'teacher',
			));
		} else {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Batch details are available for student and teacher only'
			));
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

		if ($ut === 'student') {
			$attendance_marked = (int) $this->db_model->countAll(
				'attendance',
				array('student_id' => $student_id, 'batch_id' => $batch_id)
			);
		} else {
			$attendance_marked = (int) $this->db_model->countAll(
				'attendance',
				array('batch_id' => $batch_id)
			);
		}

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

	/**
	 * POST/GET api/batch/library-list
	 * Books (PDF) for a batch from book_pdf.
	 * Auth: student (enrolled) or teacher (assigned via batch_subjects).
	 * Optional: search, sort_by, sort_dir, page, limit.
	 */
	public function library_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id is required'));
			return;
		}

		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_student_or_teacher($payload, $batch_id)) {
			return;
		}

		$batch = $this->db_model->select_data('*', 'batches use index (id)', array('id' => $batch_id), 1);
		if (empty($batch)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Batch not found'));
			return;
		}

		$search = isset($data['search']) ? trim($data['search']) : '';
		$sort_by = isset($data['sort_by']) ? strtolower(trim($data['sort_by'])) : 'added_at';
		$sort_dir = isset($data['sort_dir']) ? strtolower(trim($data['sort_dir'])) : 'desc';
		if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
			$sort_dir = 'desc';
		}
		$order_columns = array(
			'added_at' => 'added_at',
			'date_added' => 'added_at',
			'title' => 'title',
			'subject' => 'subject',
			'topic' => 'topic',
			'file_name' => 'file_name'
		);
		if (!isset($order_columns[$sort_by])) {
			$sort_by = 'added_at';
		}
		$order_col = $order_columns[$sort_by];

		$pg = $this->parse_api_list_pagination($data);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		$this->db->from('book_pdf');
		$this->db->where('status', 1);
		$this->apply_book_pdf_batch_filter($batch_id);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('title', $search);
			$this->db->or_like('topic', $search);
			$this->db->or_like('subject', $search);
			$this->db->or_like('file_name', $search);
			$this->db->group_end();
		}
		$total = (int) $this->db->count_all_results();

		$this->db->from('book_pdf');
		$this->db->where('status', 1);
		$this->apply_book_pdf_batch_filter($batch_id);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('title', $search);
			$this->db->or_like('topic', $search);
			$this->db->or_like('subject', $search);
			$this->db->or_like('file_name', $search);
			$this->db->group_end();
		}
		$this->db->order_by($order_col, $sort_dir);
		$this->db->limit($limit, $offset);
		$rows = $this->db->get()->result_array();

		$base_path = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'book' . DIRECTORY_SEPARATOR;
		$items = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$file = isset($r['file_name']) ? $r['file_name'] : '';
				$download_url = $file !== '' ? base_url('uploads/book/') . $file : '';
				$file_size_bytes = null;
				$file_size_label = '';
				if ($file !== '' && is_file($base_path . $file)) {
					$file_size_bytes = (int) filesize($base_path . $file);
					if ($file_size_bytes >= 1048576) {
						$file_size_label = round($file_size_bytes / 1048576, 2) . ' MB';
					} elseif ($file_size_bytes >= 1024) {
						$file_size_label = round($file_size_bytes / 1024) . ' KB';
					} else {
						$file_size_label = $file_size_bytes . ' B';
					}
				}

				$items[] = array(
					'id' => (int) $r['id'],
					'title' => isset($r['title']) ? $r['title'] : '',
					'topic' => isset($r['topic']) ? $r['topic'] : '',
					'subject' => isset($r['subject']) ? $r['subject'] : '',
					'fileName' => $file,
					'downloadUrl' => $download_url,
					'fileSizeBytes' => $file_size_bytes,
					'fileSize' => $file_size_label,
					'addedAt' => isset($r['added_at']) ? $r['added_at'] : ''
				);
			}
		}

		$arr = array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'batch_id' => $batch_id,
				'library' => $items,
				'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total),
			)
		);
		echo json_encode($arr, JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST api/batch/library-add-book (multipart recommended: pdf_file)
	 * Auth: teacher | institute only.
	 */
	public function library_add_book()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		if (empty($data['batch_id']) || empty($data['title'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id and title are required'));
			return;
		}
		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$batch = $this->db_model->select_data('admin_id', 'batches use index (id)', array('id' => $batch_id), 1);
		if (empty($batch)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Batch not found'));
			return;
		}
		$admin_id = (int) $batch[0]['admin_id'];
		if (empty($_FILES['pdf_file']['name'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'pdf_file is required'));
			return;
		}
		$config['upload_path'] = './uploads/book/';
		$config['allowed_types'] = '*';
		$config['max_size'] = '0';
		$this->load->library('upload', $config);
		if (!$this->upload->do_upload('pdf_file')) {
			echo json_encode(array('status' => 'false', 'msg' => strip_tags($this->upload->display_errors('', ''))));
			return;
		}
		$uploaddata = $this->upload->data();
		$pic = $uploaddata['raw_name'];
		$pic_ext = $uploaddata['file_ext'];
		$image = $pic . date('ymdHis') . $pic_ext;
		$old_path = './uploads/book/' . $pic . $pic_ext;
		$new_path = './uploads/book/' . $image;
		if (is_file($old_path)) {
			@rename($old_path, $new_path);
		} else {
			$image = $uploaddata['file_name'];
		}
		$subject = isset($data['subject']) ? trim((string) $data['subject']) : '';
		$topic = isset($data['topic']) ? trim((string) $data['topic']) : '';
		$insert = $this->security->xss_clean(array(
			'admin_id' => $admin_id,
			'title' => trim((string) $data['title']),
			'batch' => (string) $batch_id,
			'topic' => $topic,
			'subject' => $subject,
			'file_name' => $image,
			'status' => 1,
			'added_by' => (int) $payload['uid'],
			'added_at' => date('Y-m-d H:i:s'),
		));
		$new_id = $this->db_model->insert_data('book_pdf', $insert);
		if (empty($new_id)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Failed to add book'));
			return;
		}
		$download_url = $image !== '' ? base_url('uploads/book/') . $image : '';
		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'id' => (int) $new_id,
				'batch_id' => $batch_id,
				'title' => $insert['title'],
				'topic' => $insert['topic'],
				'subject' => $insert['subject'],
				'fileName' => $image,
				'downloadUrl' => $download_url,
			),
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST api/batch/library-edit-book (optional multipart pdf_file to replace file)
	 * Auth: teacher | institute only.
	 */
	public function library_edit_book()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		if (empty($data['book_id']) || empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'book_id and batch_id are required'));
			return;
		}
		$book_id = (int) $data['book_id'];
		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$row = $this->get_book_pdf_for_batch($book_id, $batch_id, true);
		if (empty($row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Book not found'));
			return;
		}
		$batch = $this->db_model->select_data('admin_id', 'batches use index (id)', array('id' => $batch_id), 1);
		$admin_id = !empty($batch) ? (int) $batch[0]['admin_id'] : (int) $row['admin_id'];

		$update = array(
			'admin_id' => $admin_id,
			'added_by' => (int) $payload['uid'],
		);
		if (isset($data['title']) && trim((string) $data['title']) !== '') {
			$update['title'] = trim((string) $data['title']);
		}
		if (array_key_exists('subject', $data)) {
			$update['subject'] = trim((string) $data['subject']);
		}
		if (array_key_exists('topic', $data)) {
			$update['topic'] = trim((string) $data['topic']);
		}
		if (!empty($_FILES['pdf_file']['name'])) {
			$config['upload_path'] = './uploads/book/';
			$config['allowed_types'] = '*';
			$config['max_size'] = '0';
			$this->load->library('upload', $config);
			if (!$this->upload->do_upload('pdf_file')) {
				echo json_encode(array('status' => 'false', 'msg' => strip_tags($this->upload->display_errors('', ''))));
				return;
			}
			$uploaddata = $this->upload->data();
			$pic = $uploaddata['raw_name'];
			$pic_ext = $uploaddata['file_ext'];
			$image = $pic . date('ymdHis') . $pic_ext;
			$old_path = './uploads/book/' . $pic . $pic_ext;
			$new_path = './uploads/book/' . $image;
			if (is_file($old_path)) {
				@rename($old_path, $new_path);
			} else {
				$image = $uploaddata['file_name'];
			}
			$update['file_name'] = $image;
		}
		$update = $this->security->xss_clean($update);
		$this->db_model->update_data_limit('book_pdf', $update, array('id' => $book_id), 1);
		$updated = $this->get_book_pdf_for_batch($book_id, $batch_id, true);
		$file = !empty($updated['file_name']) ? $updated['file_name'] : '';
		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'id' => $book_id,
				'batch_id' => $batch_id,
				'title' => isset($updated['title']) ? $updated['title'] : '',
				'topic' => isset($updated['topic']) ? $updated['topic'] : '',
				'subject' => isset($updated['subject']) ? $updated['subject'] : '',
				'fileName' => $file,
				'downloadUrl' => $file !== '' ? base_url('uploads/book/') . $file : '',
			),
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/library-delete-book
	 * Auth: teacher | institute only. Soft-delete: status = 0.
	 */
	public function library_delete_book()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		if (empty($data['book_id']) || empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'book_id and batch_id are required'));
			return;
		}
		$book_id = (int) $data['book_id'];
		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$row = $this->get_book_pdf_for_batch($book_id, $batch_id, true);
		if (empty($row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Book not found'));
			return;
		}
		$this->db_model->update_data_limit('book_pdf', array('status' => 0), array('id' => $book_id), 1);
		echo json_encode(array('status' => 'true', 'message' => 'Success', 'msg' => 'Book removed'), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/library-book-details
	 * Auth: teacher | institute only.
	 */
	public function library_book_details()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		if (empty($data['book_id']) || empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'book_id and batch_id are required'));
			return;
		}
		$book_id = (int) $data['book_id'];
		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$row = $this->get_book_pdf_for_batch($book_id, $batch_id, true);
		if (empty($row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Book not found'));
			return;
		}
		$file = isset($row['file_name']) ? $row['file_name'] : '';
		$base_path = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'book' . DIRECTORY_SEPARATOR;
		$file_size_bytes = null;
		$file_size_label = '';
		if ($file !== '' && is_file($base_path . $file)) {
			$file_size_bytes = (int) filesize($base_path . $file);
			if ($file_size_bytes >= 1048576) {
				$file_size_label = round($file_size_bytes / 1048576, 2) . ' MB';
			} elseif ($file_size_bytes >= 1024) {
				$file_size_label = round($file_size_bytes / 1024) . ' KB';
			} else {
				$file_size_label = $file_size_bytes . ' B';
			}
		}
		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'id' => (int) $row['id'],
				'batch_id' => $batch_id,
				'title' => isset($row['title']) ? $row['title'] : '',
				'topic' => isset($row['topic']) ? $row['topic'] : '',
				'subject' => isset($row['subject']) ? $row['subject'] : '',
				'fileName' => $file,
				'downloadUrl' => $file !== '' ? base_url('uploads/book/') . $file : '',
				'fileSizeBytes' => $file_size_bytes,
				'fileSize' => $file_size_label,
				'addedAt' => isset($row['added_at']) ? $row['added_at'] : '',
				'addedBy' => isset($row['added_by']) ? (int) $row['added_by'] : 0,
			),
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/live-class-list
	 * Live class history list for a student's enrolled batch.
	 * Params: batch_id (required), search, sort_by, sort_dir, page, limit
	 */
	public function live_class_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id is required'));
			return;
		}

		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_student_or_teacher($payload, $batch_id)) {
			return;
		}

		$search = isset($data['search']) ? trim($data['search']) : '';
		$sort_by = isset($data['sort_by']) ? strtolower(trim($data['sort_by'])) : 'entry_date_time';
		$sort_dir = isset($data['sort_dir']) ? strtolower(trim($data['sort_dir'])) : 'desc';
		if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
			$sort_dir = 'desc';
		}
		$order_map = array(
			'entry_date_time' => 'lch.entry_date_time',
			'date' => 'lch.date',
			'start_time' => 'lch.start_time',
			'end_time' => 'lch.end_time'
		);
		if (!isset($order_map[$sort_by])) {
			$sort_by = 'entry_date_time';
		}

		$pg = $this->parse_api_list_pagination($data);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		// Count query
		$this->db->from('live_class_history lch');
		$this->db->where('lch.batch_id', $batch_id);
		if ($search !== '') {
			$this->db->join('subjects s', 's.id = lch.subject_id', 'left');
			$this->db->join('chapters c', 'c.id = lch.chapter_id', 'left');
			$this->db->join('users u', 'u.id = lch.uid', 'left');
			$this->db->group_start();
			$this->db->like('s.subject_name', $search);
			$this->db->or_like('c.chapter_name', $search);
			$this->db->or_like('u.name', $search);
			$this->db->or_like('lch.start_time', $search);
			$this->db->group_end();
		}
		$total = (int) $this->db->count_all_results();

		$this->db->select(
			'lch.id as liveClassId,lch.uid as teacherId,lch.batch_id as batchId,lch.subject_id as subjectId,lch.chapter_id as chapterId,' .
			'lch.start_time as startTime,lch.end_time as endTime,lch.date,lch.entry_date_time as entryDateTime,lch.type_class as typeClass,' .
			'u.name as teacherName,u.teach_image as teacherImage,s.subject_name as subjectName,c.chapter_name as chapterName'
		);
		$this->db->from('live_class_history lch');
		$this->db->join('users u', 'u.id = lch.uid', 'left');
		$this->db->join('subjects s', 's.id = lch.subject_id', 'left');
		$this->db->join('chapters c', 'c.id = lch.chapter_id', 'left');
		$this->db->where('lch.batch_id', $batch_id);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('s.subject_name', $search);
			$this->db->or_like('c.chapter_name', $search);
			$this->db->or_like('u.name', $search);
			$this->db->or_like('lch.start_time', $search);
			$this->db->group_end();
		}
		$this->db->order_by($order_map[$sort_by], $sort_dir);
		$this->db->limit($limit, $offset);
		$rows = $this->db->get()->result_array();

		$list = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$type = isset($r['typeClass']) ? (int) $r['typeClass'] : 0;
				$r['typeLabel'] = ($type === 1) ? 'Zoom' : (($type === 2) ? 'Jetsi' : '');
				$r['teacherImageUrl'] = !empty($r['teacherImage']) ? base_url('uploads/users/') . $r['teacherImage'] : '';
				$r['isLive'] = (isset($r['endTime']) && (trim((string) $r['endTime']) === '' || $r['endTime'] === '0000-00-00 00:00:00')) ? 1 : 0;
				$list[] = $r;
			}
		}

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'batch_id' => $batch_id,
				'liveClasses' => $list,
				'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total),
			)
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/live-class-details
	 * Required: live_class_id
	 */
	public function live_class_details()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['live_class_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'live_class_id is required'));
			return;
		}

		$live_class_id = (int) $data['live_class_id'];
		$this->db->select(
			'lch.id as liveClassId,lch.uid as teacherId,lch.batch_id as batchId,lch.subject_id as subjectId,lch.chapter_id as chapterId,' .
			'lch.start_time as startTime,lch.end_time as endTime,lch.date,lch.entry_date_time as entryDateTime,lch.admin_id as adminId,lch.type_class as typeClass,' .
			'u.name as teacherName,u.teach_image as teacherImage,s.subject_name as subjectName,c.chapter_name as chapterName'
		);
		$this->db->from('live_class_history lch');
		$this->db->join('users u', 'u.id = lch.uid', 'left');
		$this->db->join('subjects s', 's.id = lch.subject_id', 'left');
		$this->db->join('chapters c', 'c.id = lch.chapter_id', 'left');
		$this->db->where('lch.id', $live_class_id);
		$row = $this->db->get()->row_array();

		if (empty($row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Live class not found'));
			return;
		}

		$batch_id = (int) $row['batchId'];
		if (!$this->assert_batch_access_student_or_teacher($payload, $batch_id)) {
			return;
		}

		$type = isset($row['typeClass']) ? (int) $row['typeClass'] : 0;
		$row['typeLabel'] = ($type === 1) ? 'Zoom' : (($type === 2) ? 'Jetsi' : '');
		$row['teacherImageUrl'] = !empty($row['teacherImage']) ? base_url('uploads/users/') . $row['teacherImage'] : '';
		$row['isLive'] = (isset($row['endTime']) && (trim((string) $row['endTime']) === '' || $row['endTime'] === '0000-00-00 00:00:00')) ? 1 : 0;

		// Attach meeting settings by class type.
		if ($type === 2) {
			$meeting = $this->db_model->select_data('meeting_number as meetingNumber', 'jetsi_setting', array('batch' => $batch_id), 1, array('id', 'desc'));
			$row['meeting'] = array(
				'type' => 'jetsi',
				'meetingNumber' => !empty($meeting[0]['meetingNumber']) ? $meeting[0]['meetingNumber'] : '',
				'password' => ''
			);
		} else {
			$meeting = $this->db_model->select_data(
				'meeting_number as meetingNumber,password',
				'live_class_setting',
				array('batch' => $batch_id, 'status' => 1),
				1,
				array('id', 'desc')
			);
			$row['meeting'] = array(
				'type' => 'zoom',
				'meetingNumber' => !empty($meeting[0]['meetingNumber']) ? $meeting[0]['meetingNumber'] : '',
				'password' => !empty($meeting[0]['password']) ? $meeting[0]['password'] : ''
			);
		}

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'liveClass' => $row
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/video-lecture-list
	 * Params: batch_id (required), search, sort_by, sort_dir, page, limit
	 */
	public function video_lecture_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id is required'));
			return;
		}
		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_student_or_teacher($payload, $batch_id)) {
			return;
		}

		$search = isset($data['search']) ? trim($data['search']) : '';
		$sort_by = isset($data['sort_by']) ? strtolower(trim($data['sort_by'])) : 'added_at';
		$sort_dir = isset($data['sort_dir']) ? strtolower(trim($data['sort_dir'])) : 'desc';
		if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
			$sort_dir = 'desc';
		}
		$order_map = array(
			'added_at' => 'added_at',
			'date_added' => 'added_at',
			'title' => 'title',
			'topic' => 'topic',
			'subject' => 'subject'
		);
		if (!isset($order_map[$sort_by])) {
			$sort_by = 'added_at';
		}

		$pg = $this->parse_api_list_pagination($data);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		$this->db->from('video_lectures');
		$this->db->where('status', 1);
		$this->apply_text_batch_filter('batch', $batch_id);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('title', $search);
			$this->db->or_like('topic', $search);
			$this->db->or_like('subject', $search);
			$this->db->or_like('description', $search);
			$this->db->group_end();
		}
		$total = (int) $this->db->count_all_results();

		$this->db->select('id,admin_id as adminId,title,batch,topic,subject,description,url,video_type as videoType,preview_type as previewType,added_by as addedBy,added_at as addedAt');
		$this->db->from('video_lectures');
		$this->db->where('status', 1);
		$this->apply_text_batch_filter('batch', $batch_id);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('title', $search);
			$this->db->or_like('topic', $search);
			$this->db->or_like('subject', $search);
			$this->db->or_like('description', $search);
			$this->db->group_end();
		}
		$this->db->order_by($order_map[$sort_by], $sort_dir);
		$this->db->limit($limit, $offset);
		$list = $this->db->get()->result_array();

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'batch_id' => $batch_id,
				'videoLectures' => !empty($list) ? $list : array(),
				'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total),
			)
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/video-lecture-details
	 * Required: video_lecture_id
	 */
	public function video_lecture_details()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['video_lecture_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'video_lecture_id is required'));
			return;
		}
		$video_id = (int) $data['video_lecture_id'];

		$row = $this->db_model->select_data(
			'id,admin_id as adminId,title,batch,topic,subject,description,url,video_type as videoType,preview_type as previewType,added_by as addedBy,added_at as addedAt,status',
			'video_lectures use index (id)',
			array('id' => $video_id, 'status' => 1),
			1
		);
		if (empty($row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Video lecture not found'));
			return;
		}
		$video = $row[0];

		$ut = strtolower(trim((string) $payload['ut']));
		$uid = (int) $payload['uid'];
		if ($ut === 'student') {
			if ($uid < 1 || $this->authorize_student_request($uid) === false) {
				return;
			}
			// Validate student enrollment with at least one batch mapped in this lecture.
			$student = $this->db_model->select_data('batch_id', 'students use index (id)', array('id' => $uid), 1);
			$student_batch_id = !empty($student[0]['batch_id']) ? (int) $student[0]['batch_id'] : 0;
			if ($student_batch_id < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not enrolled in any batch'));
				return;
			}

			$this->db->from('video_lectures');
			$this->db->where('id', $video_id);
			$this->apply_text_batch_filter('batch', $student_batch_id);
			$allowed = (int) $this->db->count_all_results();
			if ($allowed < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not allowed to access this video'));
				return;
			}
		} elseif ($ut === 'teacher') {
			if ($uid < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
				return;
			}
			if (!$this->video_accessible_to_teacher($video_id, $uid)) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not allowed to access this video'));
				return;
			}
		} else {
			echo json_encode(array('status' => 'false', 'msg' => 'This action is available for student and teacher only'));
			return;
		}

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'videoLecture' => $video
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/upcoming-exam-list
	 * Params: batch_id (required), search, sort_by, sort_dir, page, limit
	 */
	public function upcoming_exam_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id is required'));
			return;
		}
		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_student_or_teacher($payload, $batch_id)) {
			return;
		}

		$search = isset($data['search']) ? trim($data['search']) : '';
		$sort_by = isset($data['sort_by']) ? strtolower(trim($data['sort_by'])) : 'mock_sheduled_date';
		$sort_dir = isset($data['sort_dir']) ? strtolower(trim($data['sort_dir'])) : 'asc';
		if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
			$sort_dir = 'asc';
		}
		$order_map = array(
			'mock_sheduled_date' => 'mock_sheduled_date',
			'mock_sheduled_time' => 'mock_sheduled_time',
			'added_at' => 'added_at',
			'name' => 'name',
			'time_duration' => 'time_duration'
		);
		if (!isset($order_map[$sort_by])) {
			$sort_by = 'mock_sheduled_date';
		}

		$pg = $this->parse_api_list_pagination($data);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		$cond = array(
			'batch_id' => $batch_id,
			'status' => 1,
			'type' => 1,
			'mock_sheduled_date >=' => date('Y-m-d')
		);

		$this->db->from('exams');
		$this->db->where($cond);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('name', $search);
			$this->db->or_like('total_question', $search);
			$this->db->or_like('time_duration', $search);
			$this->db->group_end();
		}
		$total = (int) $this->db->count_all_results();

		$this->db->select('id,admin_id as adminId,name,type,format,batch_id as batchId,total_question as totalQuestion,time_duration as timeDuration,mock_sheduled_date as scheduledDate,mock_sheduled_time as scheduledTime,total_marks as totalMarks,marking_parcent as markingPercent,added_by as addedBy,added_at as addedAt');
		$this->db->from('exams');
		$this->db->where($cond);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('name', $search);
			$this->db->or_like('total_question', $search);
			$this->db->or_like('time_duration', $search);
			$this->db->group_end();
		}
		$this->db->order_by($order_map[$sort_by], $sort_dir);
		$this->db->limit($limit, $offset);
		$list = $this->db->get()->result_array();

		if (!empty($list)) {
			foreach ($list as $k => $v) {
				$list[$k]['completeBy'] = trim($v['scheduledTime'] . ', ' . date('M d, Y', strtotime($v['scheduledDate'])));
				$list[$k]['examTypeLabel'] = ((int) $v['type'] === 1) ? 'mock' : 'practice';
			}
		}

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'batch_id' => $batch_id,
				'upcomingExams' => !empty($list) ? $list : array(),
				'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total),
			)
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/upcoming-exam-details
	 * Required: exam_id
	 */
	public function upcoming_exam_details()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['exam_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'exam_id is required'));
			return;
		}
		$exam_id = (int) $data['exam_id'];

		$exam = $this->db_model->select_data(
			'id,admin_id as adminId,name,type,format,batch_id as batchId,total_question as totalQuestion,time_duration as timeDuration,mock_sheduled_date as scheduledDate,mock_sheduled_time as scheduledTime,total_marks as totalMarks,marking_parcent as markingPercent,question_ids as questionIds,status,added_by as addedBy,added_at as addedAt',
			'exams use index (id)',
			array('id' => $exam_id, 'status' => 1, 'type' => 1),
			1
		);
		if (empty($exam)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Exam not found'));
			return;
		}
		$e = $exam[0];

		if (!$this->assert_batch_access_student_or_teacher($payload, (int) $e['batchId'])) {
			return;
		}

		$e['completeBy'] = trim($e['scheduledTime'] . ', ' . date('M d, Y', strtotime($e['scheduledDate'])));
		$e['examTypeLabel'] = ((int) $e['type'] === 1) ? 'mock' : 'practice';
		$e['formatLabel'] = ((int) $e['format'] === 1) ? 'Shuffle' : (((int) $e['format'] === 2) ? 'Fix' : '');

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'exam' => $e
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * Tenant admin_id for a teacher (users.admin_id, stored as text in DB).
	 */
	private function teacher_tenant_admin_id($teacher_user_id)
	{
		$teacher_user_id = (int) $teacher_user_id;
		if ($teacher_user_id < 1) {
			return 0;
		}
		$rows = $this->db_model->select_data('admin_id', 'users use index (id)', array('id' => $teacher_user_id), 1);
		if (empty($rows) || !isset($rows[0]['admin_id'])) {
			return 0;
		}
		$raw = trim((string) $rows[0]['admin_id']);
		if ($raw === '') {
			return 0;
		}
		if (ctype_digit($raw)) {
			return (int) $raw;
		}
		$parts = preg_split('/\s*,\s*/', $raw);
		return isset($parts[0]) && ctype_digit($parts[0]) ? (int) $parts[0] : (int) $raw;
	}

	/**
	 * Teacher must be assigned to this batch for this subject (batch_subjects).
	 */
	private function assert_teacher_batch_subject($teacher_id, $batch_id, $subject_id)
	{
		$teacher_id = (int) $teacher_id;
		$batch_id = (int) $batch_id;
		$subject_id = (int) $subject_id;
		if ($teacher_id < 1 || $batch_id < 1 || $subject_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Invalid batch or subject'));
			return false;
		}
		$row = $this->db_model->select_data(
			'id',
			'batch_subjects',
			array('teacher_id' => $teacher_id, 'batch_id' => $batch_id, 'subject_id' => $subject_id),
			1
		);
		if (empty($row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'You are not assigned to this subject for this batch'));
			return false;
		}
		return true;
	}

	/**
	 * Same behaviour as {@see Home::Homework()}: student + teacher, batch_id/admin_id resolution, joins, homeWork response.
	 * POST/GET api/batch/homework-list
	 */
	public function homework_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('student', 'teacher'), $data);
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

		if ($payload['ut'] === 'teacher') {
			$teacher_row = $this->db_model->select_data('id,admin_id', 'users use index (id)', array('id' => (int) $payload['uid']), 1);
			if (!empty($teacher_row[0]['admin_id'])) {
				$data['admin_id'] = $teacher_row[0]['admin_id'];
			}
		}

		if (!isset($data['batch_id'])) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => $this->lang->line('ltr_missing_parameters_msg'),
			), JSON_UNESCAPED_SLASHES);
			die;
		}

		if ($payload['ut'] === 'student') {
			$this->mark_homework_notification_viewed((int) $payload['uid']);
		}

		$cond = array(
			'homeworks.admin_id' => $data['admin_id'],
			'homeworks.batch_id' => $data['batch_id'],
		);
		if ($payload['ut'] === 'teacher') {
			$cond['homeworks.teacher_id'] = (int) $payload['uid'];
		}

		$pg = $this->parse_api_list_pagination($data);
		$hw_join = array('multiple', array(array('users', 'users.id = homeworks.teacher_id'), array('subjects', 'subjects.id = homeworks.subject_id')));
		$total = (int) $this->db_model->countAll('homeworks use index (id)', $cond, '', '', '', $hw_join);

		$homewrkData = $this->db_model->select_data(
			'homeworks.id,homeworks.admin_id as adminId,homeworks.teacher_id as teacherId,homeworks.date,homeworks.subject_id as studentId,homeworks.batch_id as batchId,homeworks.description,homeworks.added_at as addedAt,users.name,users.teach_gender as teachGender,subjects.subject_name as subjectName',
			'homeworks use index (id)',
			$cond,
			array($pg['limit'], $pg['offset']),
			array('id', 'desc'),
			'',
			$hw_join,
			''
		);

		$pagination = $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total);
		if (!empty($homewrkData)) {
			$arr = array(
				'homeWork' => $homewrkData,
				'status' => 'true',
				'msg' => $this->lang->line('ltr_fetch_successfully'),
				'pagination' => $pagination,
			);
		} else {
			$arr = array(
				'status' => 'false',
				'msg' => $this->lang->line('ltr_no_record_msg'),
				'homeWork' => array(),
				'pagination' => $pagination,
			);
		}
		echo json_encode($arr, JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * Mirrors {@see Home::viewNotificationStatus()} for notice_type homeWork (student app).
	 */
	private function mark_homework_notification_viewed($student_id)
	{
		$student_id = (int) $student_id;
		if ($student_id < 1) {
			return;
		}
		$notice_type = 'homeWork';
		$cu_date = date('Y-m-d H:i:s');
		$noticeD = $this->db_model->select_data('*', 'views_notification_student', array('student_id' => $student_id, 'notice_type' => $notice_type), 1);
		if (!empty($noticeD)) {
			$this->db_model->update_data_limit('views_notification_student ', array('views_time' => $cu_date), array('n_id' => $noticeD[0]['n_id']), 1);
		} else {
			$data_arr = $this->security->xss_clean(array(
				'student_id' => $student_id,
				'notice_type' => $notice_type,
			));
			$this->db_model->insert_data('views_notification_student', $data_arr);
		}
	}

	/**
	 * POST api/batch/homework-add
	 * Auth: teacher only. Required: batch_id, subject_id, date, description.
	 */
	public function homework_add()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher'), $data);
		if ($payload === false) {
			return;
		}
		$teacher_id = (int) $payload['uid'];
		if ($teacher_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
			return;
		}
		if (empty($data['batch_id']) || empty($data['subject_id']) || empty($data['date']) || !isset($data['description']) || trim((string) $data['description']) === '') {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id, subject_id, date, and description are required'));
			return;
		}
		$batch_id = (int) $data['batch_id'];
		$subject_id = (int) $data['subject_id'];
		if (!$this->assert_teacher_batch_subject($teacher_id, $batch_id, $subject_id)) {
			return;
		}
		$admin_id = $this->teacher_tenant_admin_id($teacher_id);
		if ($admin_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Could not resolve admin for this teacher account'));
			return;
		}
		$date_ts = strtotime($data['date']);
		if ($date_ts === false) {
			echo json_encode(array('status' => 'false', 'msg' => 'Invalid date'));
			return;
		}
		$date = date('Y-m-d', $date_ts);
		$insert = $this->security->xss_clean(array(
			'admin_id' => $admin_id,
			'teacher_id' => $teacher_id,
			'date' => $date,
			'subject_id' => $subject_id,
			'batch_id' => (string) $batch_id,
			'description' => trim((string) $data['description']),
			'added_at' => date('Y-m-d H:i:s'),
		));
		$new_id = $this->db_model->insert_data('homeworks', $insert);
		if (empty($new_id)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Failed to add homework'));
			return;
		}
		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'msg' => $this->lang->line('ltr_homework_added_msg'),
			'data' => array(
				'id' => (int) $new_id,
				'batchId' => $batch_id,
				'subjectId' => $subject_id,
				'date' => $date,
			),
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST api/batch/homework-edit
	 * Auth: teacher only. Required: homework_id. Optional: batch_id, subject_id, date, description (must still teach batch+subject if changed).
	 */
	public function homework_edit()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher'), $data);
		if ($payload === false) {
			return;
		}
		$teacher_id = (int) $payload['uid'];
		if (empty($data['homework_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'homework_id is required'));
			return;
		}
		$hid = (int) $data['homework_id'];
		$existing = $this->db_model->select_data('*', 'homeworks use index (id)', array('id' => $hid, 'teacher_id' => $teacher_id), 1);
		if (empty($existing)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Homework not found'));
			return;
		}
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : (int) $existing[0]['batch_id'];
		$subject_id = isset($data['subject_id']) ? (int) $data['subject_id'] : (int) $existing[0]['subject_id'];
		if (!$this->assert_teacher_batch_subject($teacher_id, $batch_id, $subject_id)) {
			return;
		}
		$admin_id = $this->teacher_tenant_admin_id($teacher_id);
		if ($admin_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Could not resolve admin for this teacher account'));
			return;
		}
		$update = array(
			'admin_id' => $admin_id,
			'batch_id' => (string) $batch_id,
			'subject_id' => $subject_id,
		);
		if (isset($data['date']) && $data['date'] !== '') {
			$dts = strtotime($data['date']);
			if ($dts !== false) {
				$update['date'] = date('Y-m-d', $dts);
			}
		}
		if (isset($data['description'])) {
			$update['description'] = trim((string) $data['description']);
		}
		if (isset($update['description']) && $update['description'] === '') {
			echo json_encode(array('status' => 'false', 'msg' => 'description cannot be empty'));
			return;
		}
		$update['added_at'] = date('Y-m-d H:i:s');
		$update = $this->security->xss_clean($update);
		$this->db_model->update_data_limit('homeworks', $update, array('id' => $hid, 'teacher_id' => $teacher_id), 1);
		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'msg' => $this->lang->line('ltr_homework_updated_msg'),
			'data' => array('id' => $hid),
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/homework-delete
	 * Auth: teacher only. Required: homework_id.
	 */
	public function homework_delete()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher'), $data);
		if ($payload === false) {
			return;
		}
		$teacher_id = (int) $payload['uid'];
		if (empty($data['homework_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'homework_id is required'));
			return;
		}
		$hid = (int) $data['homework_id'];
		$this->db_model->delete_data('homeworks', array('id' => $hid, 'teacher_id' => $teacher_id), 1);
		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'msg' => 'Homework deleted',
		), JSON_UNESCAPED_SLASHES);
		die;
	}
}
