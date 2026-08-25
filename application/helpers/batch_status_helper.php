<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Batch lifecycle status helpers.
 *
 * A batch has TWO independent things:
 *   - batches.status  : the admin's own Active (1) / Inactive (0) switch. Only a human changes it.
 *   - expiry          : derived from end_date + end_time vs "now". Never stored, never written back.
 *
 * Expiry used to be written into batches.status by a loop in Admin_profile::batch_manage(),
 * which silently reverted an admin's Active choice on the next page load. It is computed
 * on read instead, so "Expired" can be shown without destroying the admin's setting.
 */

if (!function_exists('batch_end_timestamp')) {
	/**
	 * Unix timestamp for the moment a batch ends (end_date + end_time).
	 *
	 * @param array $batch Row from `batches` (needs end_date, optionally end_time)
	 * @return int 0 when end_date is missing/unparseable
	 */
	function batch_end_timestamp($batch)
	{
		if (!is_array($batch)) {
			return 0;
		}
		$end_date = isset($batch['end_date']) ? trim((string) $batch['end_date']) : '';
		if ($end_date === '' || $end_date === '0000-00-00') {
			return 0;
		}
		$end_time = isset($batch['end_time']) ? trim((string) $batch['end_time']) : '';
		if ($end_time === '' || $end_time === '00:00:00') {
			// No usable end time: the batch runs to the end of its last day.
			$end_time = '23:59:59';
		}
		$ts = strtotime($end_date . ' ' . $end_time);
		return $ts === false ? 0 : (int) $ts;
	}
}

if (!function_exists('batch_is_expired')) {
	/**
	 * Has the batch's end date/time already passed?
	 *
	 * @param array $batch Row from `batches`
	 * @param int|null $now Unix timestamp to compare against (defaults to now)
	 * @return bool
	 */
	function batch_is_expired($batch, $now = null)
	{
		$end_ts = batch_end_timestamp($batch);
		if ($end_ts < 1) {
			return false;
		}
		$now = ($now === null) ? time() : (int) $now;
		return $now >= $end_ts;
	}
}

if (!function_exists('batch_lifecycle_state')) {
	/**
	 * Single label for what to show a user: expired wins over the Active/Inactive switch,
	 * because an expired batch is over regardless of how the switch is set.
	 *
	 * @param array $batch Row from `batches`
	 * @param int|null $now
	 * @return string 'expired' | 'active' | 'inactive'
	 */
	function batch_lifecycle_state($batch, $now = null)
	{
		if (batch_is_expired($batch, $now)) {
			return 'expired';
		}
		$status = (is_array($batch) && isset($batch['status'])) ? (int) $batch['status'] : 0;
		return $status === 1 ? 'active' : 'inactive';
	}
}
