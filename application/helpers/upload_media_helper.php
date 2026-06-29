<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('uploaded_file_path_segment')) {
	function uploaded_file_path_segment($dir)
	{
		$dir = trim((string) $dir);
		$dir = str_replace('\\', '/', $dir);
		$dir = trim($dir, '/');
		if ($dir === '') {
			return '';
		}
		if (strpos($dir, 'uploads/') !== 0) {
			$dir = 'uploads/' . $dir;
		}
		return $dir;
	}
}

if (!function_exists('uploaded_file_match_key')) {
	function uploaded_file_match_key($filename)
	{
		$name = trim((string) $filename);
		if ($name === '') {
			return '';
		}
		if (preg_match('#^https?://#i', $name)) {
			$path = parse_url($name, PHP_URL_PATH);
			if (is_string($path) && $path !== '') {
				$name = $path;
			}
		}
		$name = rawurldecode(str_replace('\\', '/', $name));
		$name = basename($name);
		if ($name === '' || $name === '.' || $name === '..') {
			return '';
		}
		$name = preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}\?]+/u', ' ', $name);
		$name = preg_replace('/\s+/u', ' ', $name);
		return strtolower(trim($name));
	}
}

if (!function_exists('uploaded_file_resolve_name')) {
	function uploaded_file_resolve_name($dir, $filename)
	{
		$raw = trim((string) $filename);
		if ($raw === '') {
			return '';
		}
		if (preg_match('#^https?://#i', $raw)) {
			$path = parse_url($raw, PHP_URL_PATH);
			$raw = is_string($path) ? $path : '';
		}
		$raw = rawurldecode(str_replace('\\', '/', $raw));
		$raw = basename($raw);
		if ($raw === '' || $raw === '.' || $raw === '..') {
			return '';
		}

		$dir = uploaded_file_path_segment($dir);
		if ($dir === '') {
			return $raw;
		}

		$physical_dir = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir) . DIRECTORY_SEPARATOR;
		$candidates = array(
			$raw,
			str_replace('?', ' ', $raw),
			str_replace('?', '_', $raw),
			str_replace('?', "\xE2\x80\xAF", $raw),
		);
		$seen = array();
		foreach ($candidates as $candidate) {
			if ($candidate === '' || isset($seen[$candidate])) {
				continue;
			}
			$seen[$candidate] = true;
			if (is_file($physical_dir . $candidate)) {
				return $candidate;
			}
		}

		static $dir_files = array();
		if (!array_key_exists($physical_dir, $dir_files)) {
			$dir_files[$physical_dir] = array();
			if (is_dir($physical_dir)) {
				$list = @scandir($physical_dir);
				if (is_array($list)) {
					foreach ($list as $entry) {
						if ($entry === '.' || $entry === '..') {
							continue;
						}
						if (is_file($physical_dir . $entry)) {
							$dir_files[$physical_dir][] = $entry;
						}
					}
				}
			}
		}

		$target_key = uploaded_file_match_key($raw);
		if ($target_key !== '') {
			foreach ($dir_files[$physical_dir] as $entry) {
				if (uploaded_file_match_key($entry) === $target_key) {
					return $entry;
				}
			}
		}

		return str_replace('?', ' ', $raw);
	}
}

if (!function_exists('uploaded_file_url')) {
	function uploaded_file_url($dir, $filename)
	{
		$path = uploaded_file_path_segment($dir);
		$name = uploaded_file_resolve_name($path, $filename);
		if ($path === '' || $name === '') {
			return '';
		}
		return base_url($path . '/' . rawurlencode($name));
	}
}

if (!function_exists('profile_upload_dir')) {
	function profile_upload_dir($role, $user_type = '')
	{
		$role = (int) $role;
		$user_type = strtolower(trim((string) $user_type));
		if ($role === 1 || $user_type === 'admin') {
			return 'uploads/admin';
		}
		if ($role === 2 || $user_type === 'student') {
			return 'uploads/students';
		}
		if ($role === 3 || $user_type === 'teacher') {
			return 'uploads/teachers';
		}
		return 'uploads/users';
	}
}

if (!function_exists('profile_upload_dir_candidates')) {
	function profile_upload_dir_candidates($role, $user_type = '')
	{
		$primary = profile_upload_dir($role, $user_type);
		$list = array($primary);
		$role = (int) $role;
		$user_type = strtolower(trim((string) $user_type));

		if ($role === 3 || $user_type === 'teacher') {
			$list[] = 'uploads/users';
		} elseif ($role === 4 || $user_type === 'institute') {
			$list[] = 'uploads/teachers';
		} elseif ($role === 1 || $user_type === 'admin') {
			$list[] = 'uploads/users';
		}

		$out = array();
		foreach ($list as $dir) {
			$dir = uploaded_file_path_segment($dir);
			if ($dir !== '' && !in_array($dir, $out, true)) {
				$out[] = $dir;
			}
		}
		return $out;
	}
}

if (!function_exists('uploaded_file_url_from_dirs')) {
	function uploaded_file_url_from_dirs(array $dirs, $filename)
	{
		$raw = trim((string) $filename);
		if ($raw === '') {
			return '';
		}
		foreach ($dirs as $dir) {
			$path = uploaded_file_path_segment($dir);
			if ($path === '') {
				continue;
			}
			$name = uploaded_file_resolve_name($path, $raw);
			if ($name !== '' && is_file(rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path) . DIRECTORY_SEPARATOR . $name)) {
				return base_url($path . '/' . rawurlencode($name));
			}
		}
		$first = reset($dirs);
		if ($first === false) {
			return '';
		}
		return uploaded_file_url($first, $raw);
	}
}

if (!function_exists('profile_image_url')) {
	function profile_image_url($filename, $role = 0, $user_type = '')
	{
		return uploaded_file_url_from_dirs(profile_upload_dir_candidates($role, $user_type), $filename);
	}
}
