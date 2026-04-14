<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Institute APIs: listing (optional batch context) and details only.
 * Institutes live in `users` with role = 4 and/or user_type = institute.
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
	 * Great-circle distance in kilometres (WGS84 sphere).
	 */
	private function haversine_distance_km($lat1, $lon1, $lat2, $lon2)
	{
		$lat1 = (float) $lat1;
		$lon1 = (float) $lon1;
		$lat2 = (float) $lat2;
		$lon2 = (float) $lon2;
		$R = 6371.0;
		$dLat = deg2rad($lat2 - $lat1);
		$dLon = deg2rad($lon2 - $lon1);
		$a = sin($dLat / 2) * sin($dLat / 2)
			+ cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
		return $R * $c;
	}

	/**
	 * Column name => true for one DESCRIBE users per caller when passed through listing.
	 */
	private function users_table_field_flip()
	{
		try {
			return array_flip($this->db->list_fields('users'));
		} catch (Exception $e) {
			return array();
		}
	}

	/**
	 * SELECT fragment for institute rows; $field_flip from {@see users_table_field_flip()}.
	 */
	private function institute_user_select_columns(array $field_flip = null)
	{
		$candidates = array(
			'id', 'name', 'email', 'mobile', 'role', 'user_type', 'teach_image', 'pincode',
			'country', 'state', 'city', 'address',
			'school_college_name', 'teach_education', 'institute_code', 'institude_code',
			'lat', 'long', 'latitude', 'longitude',
		);
		if ($field_flip === null) {
			$field_flip = $this->users_table_field_flip();
		}
		if (empty($field_flip)) {
			return 'users.id,users.name,users.email,users.mobile,users.role,users.user_type,users.teach_image,users.pincode,users.lat,users.`long`';
		}
		$flip = $field_flip;
		$sel = array();
		foreach ($candidates as $c) {
			if (!isset($flip[$c])) {
				continue;
			}
			if ($c === 'long') {
				$sel[] = 'users.`long`';
			} else {
				$sel[] = 'users.' . $c;
			}
		}
		return implode(',', $sel);
	}

	/**
	 * OR search across institute-relevant user columns (only columns that exist on users).
	 */
	private function apply_institute_listing_search($search, array $field_flip = null)
	{
		$search = trim((string) $search);
		if ($search === '') {
			return;
		}
		if ($field_flip === null) {
			$field_flip = $this->users_table_field_flip();
		}
		if (empty($field_flip)) {
			return;
		}
		$have = $field_flip;
		$candidates = array(
			'name', 'email', 'mobile', 'city', 'state', 'address',
			'school_college_name', 'pincode', 'teach_education', 'institute_code', 'institude_code',
		);
		$cols = array();
		foreach ($candidates as $c) {
			if (isset($have[$c])) {
				$cols[] = $c;
			}
		}
		if (empty($cols)) {
			return;
		}
		$this->db->group_start();
		$this->db->like('users.' . $cols[0], $search, 'both');
		for ($i = 1, $n = count($cols); $i < $n; $i++) {
			$this->db->or_like('users.' . $cols[$i], $search, 'both');
		}
		$this->db->group_end();
	}

	/**
	 * Restrict listing to users.city containing $city (substring), if column exists.
	 */
	private function apply_institute_listing_city_filter($city, array $field_flip = null)
	{
		$city = trim((string) $city);
		if ($city === '') {
			return;
		}
		if ($field_flip === null) {
			$field_flip = $this->users_table_field_flip();
		}
		if (empty($field_flip) || !isset($field_flip['city'])) {
			return;
		}
		$this->db->like('users.city', $city, 'both');
	}

	/**
	 * Prefer users.lat / users.long, then latitude/longitude.
	 *
	 * @return array|null [lat, lon] as floats
	 */
	private function institute_row_stored_coordinates(array $r)
	{
		if (isset($r['lat'], $r['long']) && $r['lat'] !== '' && $r['long'] !== ''
			&& is_numeric($r['lat']) && is_numeric($r['long'])) {
			return array((float) $r['lat'], (float) $r['long']);
		}
		if (isset($r['latitude'], $r['longitude']) && $r['latitude'] !== '' && $r['longitude'] !== ''
			&& is_numeric($r['latitude']) && is_numeric($r['longitude'])) {
			return array((float) $r['latitude'], (float) $r['longitude']);
		}
		return null;
	}

	/**
	 * POST/GET api/institute/listing — institutes (auth required).
	 * Params: batch_id (optional; if set, student|teacher must be enrolled/assigned to that batch);
	 * search (optional); city (optional, partial match on users.city);
	 * order_field name|distance; order_type asc|desc;
	 * page (optional, default 1); limit or per_page (optional, default 20, max 100).
	 * latitude/longitude required only when order_field=distance (distance uses stored coords only; no geocoding).
	 */
	public function institute_listing()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		$batch_raw = isset($data['batch_id']) ? trim(trim((string) $data['batch_id']), "\"' \t\n\r\0\x0B") : '';
		$batch_id = (int) $batch_raw;
		if ($batch_id >= 1) {
			$ut = strtolower(trim((string) (isset($payload['ut']) ? $payload['ut'] : '')));
			if ($ut === 'student' || $ut === 'teacher') {
				if (!$this->assert_batch_access_student_or_teacher($payload, $batch_id)) {
					return;
				}
			}
		}

		$order_field = isset($data['order_field']) ? strtolower(trim(trim((string) $data['order_field']), "\"' \t\n\r\0\x0B")) : 'distance';
		if ($order_field !== 'name' && $order_field !== 'distance') {
			$order_field = 'distance';
		}
		$order_type = isset($data['order_type']) ? strtolower(trim(trim((string) $data['order_type']), "\"' \t\n\r\0\x0B")) : 'asc';
		if ($order_type !== 'desc') {
			$order_type = 'asc';
		}

		$lat_raw = isset($data['latitude']) ? trim(trim((string) $data['latitude']), "\"' \t\n\r\0\x0B") : '';
		$lon_raw = isset($data['longitude']) ? trim(trim((string) $data['longitude']), "\"' \t\n\r\0\x0B") : '';

		$ref_lat = null;
		$ref_lon = null;
		if ($order_field === 'distance') {
			if ($lat_raw === '' || $lon_raw === '' || !is_numeric($lat_raw) || !is_numeric($lon_raw)) {
				echo json_encode(array('status' => 'false', 'msg' => 'latitude and longitude are required when order_field is distance'));
				return;
			}
			$ref_lat = (float) $lat_raw;
			$ref_lon = (float) $lon_raw;
			if ($ref_lat < -90 || $ref_lat > 90 || $ref_lon < -180 || $ref_lon > 180) {
				echo json_encode(array('status' => 'false', 'msg' => 'Invalid latitude or longitude'));
				return;
			}
		} elseif ($lat_raw !== '' && $lon_raw !== '' && is_numeric($lat_raw) && is_numeric($lon_raw)) {
			$try_lat = (float) $lat_raw;
			$try_lon = (float) $lon_raw;
			if ($try_lat >= -90 && $try_lat <= 90 && $try_lon >= -180 && $try_lon <= 180) {
				$ref_lat = $try_lat;
				$ref_lon = $try_lon;
			}
		}

		$search = isset($data['search']) ? $data['search'] : '';
		$city_filter = isset($data['city']) ? $data['city'] : '';

		$pg = $this->parse_api_list_pagination($data);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		$users_flip = $this->users_table_field_flip();

		$this->db->from('users users');
		$this->db->where('users.status', 1);
		$this->db->where("(users.role = 4 OR LOWER(IFNULL(users.user_type,'')) = 'institute')", null, false);
		$this->apply_institute_listing_search($search, $users_flip);
		$this->apply_institute_listing_city_filter($city_filter, $users_flip);
		$total_records = (int) $this->db->count_all_results();

		$select = $this->institute_user_select_columns($users_flip);
		$this->db->select($select, false);
		$this->db->from('users users');
		$this->db->where('users.status', 1);
		$this->db->where("(users.role = 4 OR LOWER(IFNULL(users.user_type,'')) = 'institute')", null, false);
		$this->apply_institute_listing_search($search, $users_flip);
		$this->apply_institute_listing_city_filter($city_filter, $users_flip);
		if ($order_field === 'name') {
			$this->db->order_by('users.name', $order_type === 'desc' ? 'desc' : 'asc');
			$this->db->limit($limit, $offset);
		}
		$rows = $this->db->get()->result_array();

		$compute_distance = ($order_field === 'distance');
		$out = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$stored = $this->institute_row_stored_coordinates($r);
				$ilat = null;
				$ilon = null;
				if (is_array($stored)) {
					$ilat = $stored[0];
					$ilon = $stored[1];
				}

				$dist_km = null;
				if ($compute_distance && $ilat !== null && $ilon !== null && $ref_lat !== null && $ref_lon !== null) {
					$dist_km = round($this->haversine_distance_km($ref_lat, $ref_lon, $ilat, $ilon), 3);
				}

				$institute_code = '';
				if (isset($r['institute_code']) && $r['institute_code'] !== '') {
					$institute_code = (string) $r['institute_code'];
				} elseif (isset($r['institude_code']) && $r['institude_code'] !== '') {
					$institute_code = (string) $r['institude_code'];
				}

				$img = isset($r['teach_image']) ? trim((string) $r['teach_image']) : '';
				$out[] = array(
					'instituteId' => (int) $r['id'],
					'name' => isset($r['name']) ? $r['name'] : '',
					'email' => isset($r['email']) ? $r['email'] : '',
					'mobile' => isset($r['mobile']) ? $r['mobile'] : '',
					'pincode' => isset($r['pincode']) ? $r['pincode'] : '',
					'country' => isset($r['country']) ? $r['country'] : '',
					'state' => isset($r['state']) ? $r['state'] : '',
					'city' => isset($r['city']) ? $r['city'] : '',
					'address' => isset($r['address']) ? $r['address'] : '',
					'schoolCollegeName' => isset($r['school_college_name']) ? $r['school_college_name'] : '',
					'teachEducation' => isset($r['teach_education']) ? $r['teach_education'] : '',
					'instituteCode' => $institute_code,
					'role' => isset($r['role']) ? (int) $r['role'] : 0,
					'userType' => isset($r['user_type']) ? $r['user_type'] : '',
					'image' => $img,
					'imageUrl' => $img !== '' ? base_url('uploads/users/') . $img : '',
					'instituteLatitude' => $ilat,
					'instituteLongitude' => $ilon,
					'distanceKm' => $compute_distance ? $dist_km : null,
				);
			}
		}

		if ($order_field === 'distance') {
			$dir_desc = ($order_type === 'desc');
			usort($out, function ($a, $b) use ($dir_desc) {
				$da = $a['distanceKm'];
				$db = $b['distanceKm'];
				$na = isset($a['name']) ? $a['name'] : '';
				$nb = isset($b['name']) ? $b['name'] : '';
				if ($da === null && $db === null) {
					return strcasecmp($na, $nb);
				}
				if ($da === null) {
					return 1;
				}
				if ($db === null) {
					return -1;
				}
				if ($da === $db) {
					return strcasecmp($na, $nb);
				}
				if ($dir_desc) {
					return ($da > $db) ? -1 : 1;
				}
				return ($da < $db) ? -1 : 1;
			});
			$out = array_slice($out, $offset, $limit);
		}

		$response = array(
			'status' => 'true',
			'batchId' => ($batch_id >= 1 ? $batch_id : null),
			'orderField' => $order_field,
			'orderType' => strtoupper($order_type),
			'institutes' => $out,
			'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total_records),
			'msg' => !empty($out) ? 'Success' : 'No institutes found',
		);
		if ($ref_lat !== null && $ref_lon !== null) {
			$response['referenceLatitude'] = $ref_lat;
			$response['referenceLongitude'] = $ref_lon;
		}
		echo json_encode($response, JSON_UNESCAPED_SLASHES);
	}

	/**
	 * GET/POST api/institute/details
	 * Required: institute_id (or id)
	 * Optional: reviews_limit (default 50, max 100) — caps reviews[] length; rating.totalReviews is full approved count.
	 * Batches/reviews loaded via {@see MY_Controller::fetch_institute_batches_for_api()} and {@see MY_Controller::fetch_institute_approved_reviews_for_api()}.
	 */
	public function institute_details() {
		$data = $this->read_request_data();
		$id = isset($data['institute_id']) ? (int) $data['institute_id'] : (isset($data['id']) ? (int) $data['id'] : 0);
		if ($id < 1) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => $this->lang->line('ltr_missing_parameters_msg')
			));
			return;
		}

		$reviews_limit = isset($data['reviews_limit']) ? (int) $data['reviews_limit'] : 50;
		if ($reviews_limit < 1) {
			$reviews_limit = 50;
		}
		if ($reviews_limit > 100) {
			$reviews_limit = 100;
		}

		$this->db->select('users.id,users.name,users.email,users.mobile,users.role,users.user_type,users.teach_image,users.teach_education,users.teach_gender,users.pincode,users.parent_id,users.admin_id,users.updated_at');
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

		$batch_owner_ids = $this->resolve_institute_batch_owner_user_ids($id, $row);
		$batches = $this->fetch_institute_batches_for_api($id, array(
			'active_only' => true,
			'owner_ids' => $batch_owner_ids,
		));
		$review_data = $this->fetch_institute_approved_reviews_for_api($id, array('reviews_limit' => $reviews_limit));

		echo json_encode(array(
			'status' => 'true',
			'institute' => $base,
			'batches' => $batches,
			'rating' => array(
				'averageRating' => $review_data['averageRating'],
				'totalReviews' => $review_data['totalReviews'],
			),
			'reviews' => $review_data['reviews'],
			'msg' => $this->lang->line('ltr_fetch_successfully')
		), JSON_UNESCAPED_SLASHES);
	}
}
