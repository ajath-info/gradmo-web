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
	 * POST/GET api/batch/batch-list
	 * Optional: search (filters batch_name)
	 * Auth:
	 *   - student: enrolled batches from sudent_batchs
	 *   - teacher: assigned batches from batch_subjects
	 */
	public function batch_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload();
		if ($payload === false) {
			return;
		}

		$search = isset($data['search']) ? trim($data['search']) : '';
		$like = ($search !== '') ? array('batches.batch_name', $search) : '';

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

			$batches = $this->db_model->select_data(
				'batches.*, sudent_batchs.status as enrollment_status, sudent_batchs.create_at as enrolled_at',
				'batches use index (id)',
				array('batches.status' => '1', 'sudent_batchs.student_id' => $student_id),
				'',
				array('batches.id', 'desc'),
				$like,
				array('sudent_batchs', 'sudent_batchs.batch_id = batches.id')
			);
		}
		// TEACHER FLOW: batches assigned via batch_subjects.teacher_id
		elseif ($payload['ut'] === 'teacher') {
			$teacher_id = (int) $payload['uid'];
			if ($teacher_id < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
				return;
			}

			// Build LIKE manually for teacher since we're using a custom query.
			$params = array($teacher_id);
			$like_sql = '';
			if ($search !== '') {
				$like_sql = ' AND b.batch_name LIKE ? ';
				$params[] = '%' . $search . '%';
			}

			$query = $this->db->query(
				"SELECT DISTINCT 
					b.*, 
					1 AS enrollment_status, 
					NULL AS enrolled_at
				 FROM batch_subjects bs
				 JOIN batches b ON b.id = bs.batch_id
				 WHERE bs.teacher_id = ?
				   AND b.status = 1
				   $like_sql
				 ORDER BY b.id DESC",
				$params
			);
			$batches = $query->result_array();
		} else {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Batch list is available for student and teacher only'
			));
			return;
		}

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
		$payload = $this->require_auth_payload(array('student'));
		if ($payload === false) {
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

	/**
	 * POST/GET api/batch/library-list
	 * Books (PDF) for a batch from book_pdf.
	 * Auth: student (enrolled in batch). Optional: search, sort_by, sort_dir, page, limit.
	 */
	public function library_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('student'));
		if ($payload === false) {
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
				'pagination' => array(
					'page' => $page,
					'limit' => $limit,
					'total' => $total
				)
			)
		);
		echo json_encode($arr, JSON_UNESCAPED_SLASHES);
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
		$payload = $this->require_auth_payload(array('student'));
		if ($payload === false) {
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
		$enrollment = $this->db_model->select_data('*', 'sudent_batchs', array('student_id' => $student_id, 'batch_id' => $batch_id), 1);
		if (empty($enrollment)) {
			echo json_encode(array('status' => 'false', 'msg' => 'You are not enrolled in this batch'));
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
				'pagination' => array(
					'page' => $page,
					'limit' => $limit,
					'total' => $total
				)
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
		$payload = $this->require_auth_payload(array('student'));
		if ($payload === false) {
			return;
		}

		$student_id = (int) $payload['uid'];
		if ($student_id < 1 || $this->authorize_student_request($student_id) === false) {
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
		$enrollment = $this->db_model->select_data('*', 'sudent_batchs', array('student_id' => $student_id, 'batch_id' => $batch_id), 1);
		if (empty($enrollment)) {
			echo json_encode(array('status' => 'false', 'msg' => 'You are not enrolled in this batch'));
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
		$payload = $this->require_auth_payload(array('student'));
		if ($payload === false) {
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

		$enrollment = $this->db_model->select_data('*', 'sudent_batchs', array('student_id' => $student_id, 'batch_id' => $batch_id), 1);
		if (empty($enrollment)) {
			echo json_encode(array('status' => 'false', 'msg' => 'You are not enrolled in this batch'));
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

		$limit = isset($data['limit']) ? (int) $data['limit'] : 20;
		if ($limit < 1) $limit = 20;
		if ($limit > 100) $limit = 100;
		$page = isset($data['page']) ? (int) $data['page'] : 1;
		if ($page < 1) $page = 1;
		$offset = ($page - 1) * $limit;

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
				'pagination' => array('page' => $page, 'limit' => $limit, 'total' => $total)
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
		$payload = $this->require_auth_payload(array('student'));
		if ($payload === false) {
			return;
		}

		$student_id = (int) $payload['uid'];
		if ($student_id < 1 || $this->authorize_student_request($student_id) === false) {
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

		// Validate student enrollment with at least one batch mapped in this lecture.
		$student = $this->db_model->select_data('batch_id', 'students use index (id)', array('id' => $student_id), 1);
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
		$payload = $this->require_auth_payload(array('student'));
		if ($payload === false) {
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
		$enrollment = $this->db_model->select_data('*', 'sudent_batchs', array('student_id' => $student_id, 'batch_id' => $batch_id), 1);
		if (empty($enrollment)) {
			echo json_encode(array('status' => 'false', 'msg' => 'You are not enrolled in this batch'));
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

		$limit = isset($data['limit']) ? (int) $data['limit'] : 20;
		if ($limit < 1) $limit = 20;
		if ($limit > 100) $limit = 100;
		$page = isset($data['page']) ? (int) $data['page'] : 1;
		if ($page < 1) $page = 1;
		$offset = ($page - 1) * $limit;

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
				'pagination' => array('page' => $page, 'limit' => $limit, 'total' => $total)
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
		$payload = $this->require_auth_payload(array('student'));
		if ($payload === false) {
			return;
		}
		$student_id = (int) $payload['uid'];
		if ($student_id < 1 || $this->authorize_student_request($student_id) === false) {
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

		$enrollment = $this->db_model->select_data('*', 'sudent_batchs', array('student_id' => $student_id, 'batch_id' => (int) $e['batchId']), 1);
		if (empty($enrollment)) {
			echo json_encode(array('status' => 'false', 'msg' => 'You are not enrolled in this batch'));
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
}
