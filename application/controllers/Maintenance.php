<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Maintenance extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('upload_media');
	}

	private function require_cli()
	{
		if (!$this->input->is_cli_request()) {
			show_404();
			return false;
		}
		return true;
	}

	private function stored_upload_basename($value)
	{
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}
		if (preg_match('#^https?://#i', $value)) {
			$path = parse_url($value, PHP_URL_PATH);
			if (is_string($path) && $path !== '') {
				$value = $path;
			}
		}
		$value = rawurldecode(str_replace('\\', '/', $value));
		return basename($value);
	}

	private function upload_file_exists($dir, $filename)
	{
		$dir = uploaded_file_path_segment($dir);
		$filename = trim((string) $filename);
		if ($dir === '' || $filename === '') {
			return false;
		}
		$full = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir) . DIRECTORY_SEPARATOR . $filename;
		return is_file($full);
	}

	private function resolve_existing_upload_name(array $dirs, $value)
	{
		$basename = $this->stored_upload_basename($value);
		if ($basename === '') {
			return '';
		}
		foreach ($dirs as $dir) {
			$resolved = uploaded_file_resolve_name($dir, $basename);
			if ($resolved !== '' && $this->upload_file_exists($dir, $resolved)) {
				return $resolved;
			}
		}
		return '';
	}

	private function cleanup_table_column($table, $id_column, $column, callable $dirs_resolver, array $extra_columns = array())
	{
		$summary = array(
			'table' => $table,
			'column' => $column,
			'scanned' => 0,
			'updated' => 0,
			'unresolved' => 0,
		);

		$select_cols = array_merge(array($id_column, $column), $extra_columns);
		$rows = $this->db->select(implode(',', $select_cols))
			->from($table)
			->where($column . ' IS NOT NULL', null, false)
			->where("TRIM(" . $column . ") <> ''", null, false)
			->get()
			->result_array();

		if (empty($rows)) {
			return $summary;
		}

		foreach ($rows as $row) {
			$summary['scanned']++;
			$current = isset($row[$column]) ? (string) $row[$column] : '';
			$current_base = $this->stored_upload_basename($current);
			$dirs = call_user_func($dirs_resolver, $row);
			if (empty($dirs)) {
				$summary['unresolved']++;
				continue;
			}

			$resolved = $this->resolve_existing_upload_name($dirs, $current);
			if ($resolved === '') {
				$summary['unresolved']++;
				continue;
			}

			if ($current === $resolved || $current_base === $resolved) {
				continue;
			}

			$this->db->where($id_column, $row[$id_column]);
			$ok = $this->db->update($table, array($column => $resolved));
			if ($ok) {
				$summary['updated']++;
				echo '[UPDATED] ' . $table . '.' . $column . ' #' . $row[$id_column] . ' => ' . $resolved . PHP_EOL;
			}
		}

		return $summary;
	}

	public function normalize_uploaded_image_filenames()
	{
		if (!$this->require_cli()) {
			return;
		}

		@set_time_limit(0);
		echo 'Starting uploaded image filename normalization...' . PHP_EOL;

		$summaries = array();
		if ($this->db->table_exists('batches') && $this->db->field_exists('batch_image', 'batches')) {
			$summaries[] = $this->cleanup_table_column('batches', 'id', 'batch_image', function () {
				return array('uploads/batch_image');
			});
		}
		if ($this->db->table_exists('students') && $this->db->field_exists('image', 'students')) {
			$summaries[] = $this->cleanup_table_column('students', 'id', 'image', function () {
				return array('uploads/students');
			});
		}
		if ($this->db->table_exists('users')) {
			if ($this->db->field_exists('teach_image', 'users')) {
				$summaries[] = $this->cleanup_table_column('users', 'id', 'teach_image', function ($row) {
					return profile_upload_dir_candidates(isset($row['role']) ? $row['role'] : 0, isset($row['user_type']) ? $row['user_type'] : '');
				}, array('role', 'user_type'));
			}
			if ($this->db->field_exists('image', 'users')) {
				$summaries[] = $this->cleanup_table_column('users', 'id', 'image', function ($row) {
					return profile_upload_dir_candidates(isset($row['role']) ? $row['role'] : 0, isset($row['user_type']) ? $row['user_type'] : '');
				}, array('role', 'user_type'));
			}
		}

		echo PHP_EOL . 'Summary:' . PHP_EOL;
		foreach ($summaries as $item) {
			echo '- ' . $item['table'] . '.' . $item['column']
				. ' scanned=' . $item['scanned']
				. ' updated=' . $item['updated']
				. ' unresolved=' . $item['unresolved']
				. PHP_EOL;
		}
		echo 'Done.' . PHP_EOL;
	}
}
