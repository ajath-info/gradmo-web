<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Central place to persist in-app notifications for students (table `notifications`).
 * Fan-out: one row per enrolled student in `student_batchs` (status = 1).
 *
 * Use from controllers after business actions (homework, live class, attendance, etc.).
 * Listing remains {@see Main::notifications_list}; push/FCM can be layered on top later.
 */
class Notification_service
{
	/** @var CI_Controller */
	protected $CI;

	public function __construct()
	{
		$this->CI = &get_instance();
		$this->CI->load->model('db_model');
	}

	/**
	 * @param int    $batch_id
	 * @param string $notification_type e.g. live_class, homework, attendance, leave, event, batch
	 * @param string $msg
	 * @param string $url relative path or full URL (stored as varchar(255) — keep short)
	 * @return int rows inserted
	 */
	public function fan_out_batch_students($batch_id, $notification_type, $msg, $url = '')
	{
		$batch_id = (int) $batch_id;
		if ($batch_id < 1) {
			return 0;
		}
		$notification_type = trim((string) $notification_type);
		$msg = trim((string) $msg);
		if ($notification_type === '' || $msg === '') {
			return 0;
		}
		$url = trim((string) $url);
		if (strlen($url) > 250) {
			$url = substr($url, 0, 250);
		}
		if (strlen($msg) > 250) {
			$msg = substr($msg, 0, 250);
		}

		$students = $this->CI->db_model->select_data('student_id', 'student_batchs', array('batch_id' => $batch_id, 'status' => 1), '');
		if (empty($students)) {
			return 0;
		}
		$now = date('Y-m-d H:i:s');
		$n = 0;
		foreach ($students as $s) {
			$sid = isset($s['student_id']) ? (int) $s['student_id'] : 0;
			if ($sid < 1) {
				continue;
			}
			$this->CI->db_model->insert_data('notifications', array(
				'student_id' => $sid,
				'batch_id' => $batch_id,
				'notification_type' => $notification_type,
				'msg' => $msg,
				'url' => $url,
				'status' => 0,
				'time' => $now,
				'seen_by' => '',
			));
			$n++;
		}
		return $n;
	}

	/**
	 * Single student notification (e.g. targeted message).
	 *
	 * @return int insert id or 0
	 */
	public function notify_student($student_id, $batch_id, $notification_type, $msg, $url = '')
	{
		$student_id = (int) $student_id;
		$batch_id = (int) $batch_id;
		if ($student_id < 1 || $batch_id < 1) {
			return 0;
		}
		$notification_type = trim((string) $notification_type);
		$msg = trim((string) $msg);
		if ($notification_type === '' || $msg === '') {
			return 0;
		}
		$url = trim((string) $url);
		if (strlen($url) > 250) {
			$url = substr($url, 0, 250);
		}
		if (strlen($msg) > 250) {
			$msg = substr($msg, 0, 250);
		}
		return (int) $this->CI->db_model->insert_data('notifications', array(
			'student_id' => $student_id,
			'batch_id' => $batch_id,
			'notification_type' => $notification_type,
			'msg' => $msg,
			'url' => $url,
			'status' => 0,
			'time' => date('Y-m-d H:i:s'),
			'seen_by' => '',
		));
	}
}
