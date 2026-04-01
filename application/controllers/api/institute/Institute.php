<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public institute APIs (listing + details).
 * Institutes are stored in `users` with role = 4 and/or user_type = institute.
 */
class Institute extends MY_Controller
{
	private function read_request_data()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = array();
		}
		return array_merge($data, $this->input->post(), $this->input->get());
	}

	private function institute_where_active()
	{
		$this->db->where('users.status', 1);
		$this->db->where("(users.role = 4 OR LOWER(IFNULL(users.user_type,'')) = 'institute')", null, false);
	}

	private function apply_search($search)
	{
		$search = trim((string) $search);
		if ($search === '') {
			return;
		}
		$this->db->group_start();
		$this->db->like('users.name', $search);
		$this->db->or_like('users.email', $search);
		$this->db->or_like('users.mobile', $search);
		$this->db->group_end();
	}

	private function format_institute_row($row)
	{
		$img = isset($row['teach_image']) ? trim((string) $row['teach_image']) : '';
		$imageUrl = $img !== '' ? base_url('uploads/users/') . $img : '';

		return array(
			'instituteId' => (int) $row['id'],
			'name' => isset($row['name']) ? $row['name'] : '',
			'email' => isset($row['email']) ? $row['email'] : '',
			'mobile' => isset($row['mobile']) ? $row['mobile'] : '',
			'pincode' => isset($row['pincode']) ? $row['pincode'] : '',
			'role' => isset($row['role']) ? (int) $row['role'] : 0,
			'userType' => isset($row['user_type']) ? $row['user_type'] : '',
			'image' => $img,
			'imageUrl' => $imageUrl,
			'updatedAt' => isset($row['updated_at']) ? $row['updated_at'] : ''
		);
	}

	/**
	 * GET/POST api/institute/list
	 * Params: search, page, limit, sort_by (id|name|updated_at), sort_dir (asc|desc)
	 */
	public function institute_list()
	{
		$data = $this->read_request_data();
		$search = isset($data['search']) ? $data['search'] : '';

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

		$sort_by = isset($data['sort_by']) ? strtolower(trim($data['sort_by'])) : 'name';
		$sort_dir = isset($data['sort_dir']) ? strtolower(trim($data['sort_dir'])) : 'asc';
		if ($sort_dir !== 'desc') {
			$sort_dir = 'asc';
		}
		$order_map = array(
			'id' => 'users.id',
			'name' => 'users.name',
			'updated_at' => 'users.updated_at'
		);
		if (!isset($order_map[$sort_by])) {
			$sort_by = 'name';
		}

		$this->db->from('users users');
		$this->institute_where_active();
		$this->apply_search($search);
		$total = (int) $this->db->count_all_results();

		$this->db->select('users.id,users.name,users.email,users.mobile,users.role,users.user_type,users.teach_image,users.pincode,users.updated_at');
		$this->db->from('users users');
		$this->institute_where_active();
		$this->apply_search($search);
		$this->db->order_by($order_map[$sort_by], $sort_dir);
		$this->db->limit($limit, $offset);
		$rows = $this->db->get()->result_array();

		$list = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$list[] = $this->format_institute_row($r);
			}
		}

		echo json_encode(array(
			'status' => 'true',
			'institutes' => $list,
			'pagination' => array(
				'page' => $page,
				'limit' => $limit,
				'total' => $total
			),
			'msg' => !empty($list) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg')
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * GET/POST api/institute/details
	 * Required: institute_id (or id)
	 */
	public function institute_details()
	{
		$data = $this->read_request_data();
		$id = isset($data['institute_id']) ? (int) $data['institute_id'] : (isset($data['id']) ? (int) $data['id'] : 0);
		if ($id < 1) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => $this->lang->line('ltr_missing_parameters_msg')
			));
			return;
		}

		$this->db->select('users.id,users.name,users.email,users.mobile,users.role,users.user_type,users.teach_image,users.teach_education,users.teach_gender,users.pincode,users.parent_id,users.updated_at');
		$this->db->from('users users');
		$this->db->where('users.id', $id);
		$this->institute_where_active();
		$row = $this->db->get()->row_array();

		if (empty($row)) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => $this->lang->line('ltr_no_record_msg')
			));
			return;
		}

		$base = $this->format_institute_row($row);
		$base['teachEducation'] = isset($row['teach_education']) ? $row['teach_education'] : '';
		$base['teachGender'] = isset($row['teach_gender']) ? $row['teach_gender'] : '';
		$base['parentId'] = isset($row['parent_id']) ? (int) $row['parent_id'] : 0;

		echo json_encode(array(
			'status' => 'true',
			'institute' => $base,
			'msg' => $this->lang->line('ltr_fetch_successfully')
		), JSON_UNESCAPED_SLASHES);
	}
}
