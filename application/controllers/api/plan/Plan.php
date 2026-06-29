<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Plan & promo code APIs (tables: plans, promo_codes).
 * POST/GET — merge JSON body, POST, and GET.
 */
class Plan extends MY_Controller
{
	private function read_request_data()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (! is_array($data)) {
			$data = array();
		}
		return array_merge($data, $this->input->post(), $this->input->get());
	}

	private function format_plan_row(array $row)
	{
		return array(
			'planId' => isset($row['plan_id']) ? (int) $row['plan_id'] : 0,
			'planName' => isset($row['plan_name']) ? $row['plan_name'] : '',
			'planType' => isset($row['plan_type']) ? $row['plan_type'] : '',
			'amount' => isset($row['amount']) ? (float) $row['amount'] : 0.0,
			'validityDays' => isset($row['validity_days']) ? (int) $row['validity_days'] : 0,
			'description' => isset($row['description']) ? $row['description'] : '',
			'status' => isset($row['status']) ? (int) $row['status'] : 0,
			'createdAt' => isset($row['created_at']) ? $row['created_at'] : '',
		);
	}

	private function format_promo_row(array $row)
	{
		return array(
			'promoCodeId' => isset($row['promo_code_id']) ? (int) $row['promo_code_id'] : 0,
			'code' => isset($row['code']) ? $row['code'] : '',
			'discountType' => isset($row['discount_type']) ? $row['discount_type'] : '',
			'discountValue' => isset($row['discount_value']) ? (float) $row['discount_value'] : 0.0,
			'validFrom' => isset($row['valid_from']) ? $row['valid_from'] : '',
			'validTo' => isset($row['valid_to']) ? $row['valid_to'] : '',
			'maxUse' => array_key_exists('max_use', $row) && $row['max_use'] !== null && $row['max_use'] !== '' ? (int) $row['max_use'] : null,
			'usedCount' => isset($row['used_count']) ? (int) $row['used_count'] : 0,
			'status' => isset($row['status']) ? (int) $row['status'] : 0,
			'createdAt' => isset($row['created_at']) ? $row['created_at'] : '',
		);
	}

	/**
	 * POST/GET api/plan/plans
	 * Active plans only (status = 1). Optional: page, limit, per_page.
	 * Auth: any valid Bearer token (same pattern as other app APIs).
	 */
	public function plans()
	{
		$data = $this->read_request_data();
		if ($this->require_auth_payload(array(), $data) === false) {
			return;
		}

		$pg = $this->parse_api_list_pagination($data, 20, 100);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		$this->db->reset_query();
		$this->db->from('plans');
		$this->db->where('status', 1);
		$total = (int) $this->db->count_all_results();

		$this->db->reset_query();
		$this->db->from('plans');
		$this->db->where('status', 1);
		$this->db->order_by('plan_id', 'asc');
		$this->db->limit($limit, $offset);
		$rows = $this->db->get()->result_array();

		$list = array();
		if (is_array($rows)) {
			foreach ($rows as $r) {
				if (is_array($r)) {
					$list[] = $this->format_plan_row($r);
				}
			}
		}

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'plans' => $list,
				'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total),
			),
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * POST/GET api/plan/promo-codes
	 * Promo codes that are active, within valid_from / valid_to (inclusive), and not exhausted.
	 * Optional: page, limit, per_page.
	 * Auth: any valid Bearer token.
	 */
	public function promo_codes()
	{
		$data = $this->read_request_data();
		if ($this->require_auth_payload(array(), $data) === false) {
			return;
		}

		$pg = $this->parse_api_list_pagination($data, 20, 100);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		$today = date('Y-m-d');

		$this->db->reset_query();
		$this->db->from('promo_codes');
		$this->db->where('status', 1);
		$this->db->where('valid_from <=', $today);
		$this->db->where('valid_to >=', $today);
		$this->db->group_start();
		$this->db->where('max_use IS NULL', null, false);
		$this->db->or_where('used_count < max_use', null, false);
		$this->db->group_end();
		$total = (int) $this->db->count_all_results();

		$this->db->reset_query();
		$this->db->from('promo_codes');
		$this->db->where('status', 1);
		$this->db->where('valid_from <=', $today);
		$this->db->where('valid_to >=', $today);
		$this->db->group_start();
		$this->db->where('max_use IS NULL', null, false);
		$this->db->or_where('used_count < max_use', null, false);
		$this->db->group_end();
		$this->db->order_by('promo_code_id', 'desc');
		$this->db->limit($limit, $offset);
		$rows = $this->db->get()->result_array();

		$list = array();
		if (is_array($rows)) {
			foreach ($rows as $r) {
				if (is_array($r)) {
					$list[] = $this->format_promo_row($r);
				}
			}
		}

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'promoCodes' => $list,
				'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total),
			),
		), JSON_UNESCAPED_SLASHES);
	}
}
