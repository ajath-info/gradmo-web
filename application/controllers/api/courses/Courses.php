<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Courses extends MY_Controller
{
	/**
	 * GET/POST api/courses/courses-list
	 * Optional params: search, admin_id, page, limit
	 */
	public function courses_list()
	{
		$data = $_REQUEST;
		$search = isset($data['search']) ? trim($data['search']) : '';
		$admin_id = isset($data['admin_id']) ? (int) $data['admin_id'] : 0;

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
		$limit_arr = array($limit, $offset);

		$cond = array('status' => 1);
		if ($admin_id > 0) {
			$cond['admin_id'] = $admin_id;
		}
		$like = ($search !== '') ? array('course_name', $search) : '';

		$rows = $this->db_model->select_data(
			'id,course_name as courseName,start_date as startDate,end_date as endDate,image,admin_id as adminId,course_duration as courseDuration,class_size as classSize,time_duration as timeDuration,description',
			'courses use index (id)',
			$cond,
			$limit_arr,
			array('id', 'desc'),
			$like
		);

		$total = (int) $this->db_model->countAll('courses use index (id)', $cond, '', '', $like);
		$list = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$r['courseImageUrl'] = !empty($r['image']) ? base_url('uploads/courses/') . $r['image'] : '';
				$list[] = $r;
			}
		}

		echo json_encode(array(
			'status' => 'true',
			'courses' => $list,
			'pagination' => array(
				'page' => $page,
				'limit' => $limit,
				'total' => $total
			),
			'msg' => !empty($list) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg')
		));
	}
}
