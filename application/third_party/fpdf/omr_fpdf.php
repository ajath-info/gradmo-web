<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/fpdf.php';

/**
 * FPDF for OMR sheets — skips mbstring.func_overload check (common on XAMPP).
 */
class Omr_FPDF extends FPDF
{
	protected function _dochecks()
	{
		// Parent blocks generation when mbstring.func_overload includes bit 2.
	}
}
