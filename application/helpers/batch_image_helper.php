<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('batch_image_url')) {
	/**
	 * Public URL for a batch cover image stored under uploads/batch_image/.
	 *
	 * Fixes legacy rows where xss_clean turned a space before "PM" into "?" (URLs then
	 * break because "?" starts the query string). Encodes the path segment for spaces etc.
	 *
	 * @param string $filename Stored filename or relative path (only basename is used).
	 * @return string Full URL, or empty string if $filename is empty.
	 */
	function batch_image_url($filename)
	{
		$f = trim((string) $filename);
		if ($f === '') {
			return '';
		}
		if (preg_match('#^https?://#i', $f)) {
			return $f;
		}
		$f = basename(str_replace('\\', '/', $f));
		if ($f === '' || $f === '.' || $f === '..') {
			return '';
		}
		if (strpos($f, '?') !== false) {
			$f = preg_replace('/\?(?=PM)/i', ' ', $f);
		}
		return base_url('uploads/batch_image/' . rawurlencode($f));
	}
}
