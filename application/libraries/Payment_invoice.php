<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payment invoice (student_payment_history row -> invoice data / HTML / PDF).
 *
 * Used by the website (payment-history/invoice/{id}) and the app API (api/user/payment-invoice).
 * The PDF is generated in the browser (html2pdf.js) from the same HTML: the bundled Dompdf 0.8.5
 * and mPDF 5.7 do not run on PHP 8.
 *
 * Amount rules:
 *   - "paid" = total_amount (exact, may have paise) or amount (int) for older rows.
 *   - Paid amounts are GST-inclusive (Common::gst_percent(), 18%): taxable = paid / 1.18, GST = rest.
 *   - Subtotal before discount = taxable + discount_amount.
 *   - base_amount / batch_fee split the subtotal into "Batch fee" + "Application fee" when they add up.
 */
class Payment_invoice
{
	protected $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/** Invoice number shown on the document, e.g. INV-202610-00093. */
	public function invoice_number(array $row)
	{
		$date = !empty($row['payment_date']) ? $row['payment_date'] : (isset($row['create_at']) ? $row['create_at'] : '');
		$ts = $date !== '' ? strtotime($date) : false;
		return 'INV-' . ($ts ? date('Ym', $ts) : date('Ym')) . '-' . str_pad((string) (int) $row['id'], 5, '0', STR_PAD_LEFT);
	}

	/**
	 * Load invoice data for one payment row.
	 *
	 * @param int $payment_id student_payment_history.id
	 * @param int $student_id when > 0 the row must belong to this student (ownership check)
	 * @return array|null
	 */
	public function load($payment_id, $student_id = 0)
	{
		$payment_id = (int) $payment_id;
		if ($payment_id < 1) {
			return null;
		}
		$where = array('id' => $payment_id);
		if ((int) $student_id > 0) {
			$where['student_id'] = (int) $student_id;
		}
		$rows = $this->CI->db_model->select_data('*', 'student_payment_history', $where, 1);
		if (empty($rows[0])) {
			return null;
		}
		$p = $rows[0];

		$batch = $this->CI->db_model->select_data('id,batch_name,institute_id,admin_id,start_date,end_date', 'batches', array('id' => (int) $p['batch_id']), 1);
		$batch = !empty($batch[0]) ? $batch[0] : array();
		$stu = $this->CI->db_model->select_data('id,name,email,mobile,contact_no,enrollment_id,address,city,state,pincode', 'students', array('id' => (int) $p['student_id']), 1);
		$stu = !empty($stu[0]) ? $stu[0] : array();

		$institute = '';
		$iid = !empty($batch['institute_id']) ? (int) $batch['institute_id'] : 0;
		if ($iid > 0) {
			$ir = $this->CI->db_model->select_data('name,school_college_name', 'users', array('id' => $iid), 1);
			if (!empty($ir[0])) {
				$institute = trim((string) $ir[0]['name']);
			}
		}

		$promo_code = '';
		if (!empty($p['promo_code_id']) && $this->CI->db->table_exists('promo_codes')) {
			$pr = $this->CI->db_model->select_data('code', 'promo_codes', array('promo_code_id' => (int) $p['promo_code_id']), 1);
			if (!empty($pr[0]['code'])) {
				$promo_code = (string) $pr[0]['code'];
			}
		}

		// ---- Amounts -------------------------------------------------------------------
		$paid = (isset($p['total_amount']) && $p['total_amount'] !== null && $p['total_amount'] !== '')
			? (float) $p['total_amount'] : (float) $p['amount'];
		if ($paid <= 0 && (float) $p['amount'] > 0) {
			$paid = (float) $p['amount'];
		}
		$gst_rate = (float) $this->CI->common->gst_percent();
		$taxable = $paid > 0 ? round($paid / (1 + $gst_rate / 100), 2) : 0.0;
		$gst = round($paid - $taxable, 2);
		$discount = isset($p['discount_amount']) ? max(0.0, (float) $p['discount_amount']) : 0.0;
		$subtotal = round($taxable + $discount, 2);

		$batch_name = !empty($batch['batch_name']) ? $batch['batch_name'] : ('Batch #' . (int) $p['batch_id']);
		$base = isset($p['base_amount']) ? (float) $p['base_amount'] : 0.0;
		$bfee = isset($p['batch_fee']) ? (float) $p['batch_fee'] : 0.0;
		$items = array();
		if ($base > 0 && $bfee >= 0 && $bfee < $base && abs($base - $subtotal) < 0.05) {
			$items[] = array('title' => 'Batch Fee', 'desc' => $batch_name, 'amount' => round($bfee, 2));
			$items[] = array('title' => 'Application / Enrollment Fee', 'desc' => 'One time in 1 year', 'amount' => round($base - $bfee, 2));
		} else {
			$items[] = array('title' => 'Batch Fee', 'desc' => $batch_name, 'amount' => $subtotal);
		}

		$status = strtoupper(trim((string) (isset($p['payment_status']) ? $p['payment_status'] : '')));
		if ($status === '') {
			$status = 'SUCCESS';
		}
		$mode = strtolower(trim((string) $p['mode']));
		$mode_label = $mode === 'razorpay' ? 'Online (Razorpay)' : ($mode === 'promo' ? 'Promo code (100% discount)' : ucfirst($mode !== '' ? $mode : 'Online'));
		$date = !empty($p['payment_date']) ? $p['payment_date'] : $p['create_at'];

		return array(
			'id' => (int) $p['id'],
			'invoice_no' => $this->invoice_number($p),
			'date' => $date,
			'date_label' => $date ? date('d M Y, h:i A', strtotime($date)) : '',
			'status' => $status,
			'mode_label' => $mode_label,
			'transaction_id' => (string) $p['transaction_id'],
			'order_id' => isset($p['razorpay_order_id']) ? (string) $p['razorpay_order_id'] : '',
			'seller' => $this->seller(),
			'student' => array(
				'name' => isset($stu['name']) ? trim((string) $stu['name']) : '',
				'email' => isset($stu['email']) ? (string) $stu['email'] : '',
				'mobile' => !empty($stu['mobile']) ? (string) $stu['mobile'] : (isset($stu['contact_no']) ? (string) $stu['contact_no'] : ''),
				'enrollment_id' => isset($stu['enrollment_id']) ? (string) $stu['enrollment_id'] : '',
				'address' => trim(implode(', ', array_filter(array(
					isset($stu['address']) ? trim((string) $stu['address']) : '',
					isset($stu['city']) ? trim((string) $stu['city']) : '',
					isset($stu['state']) ? trim((string) $stu['state']) : '',
					isset($stu['pincode']) ? trim((string) $stu['pincode']) : '',
				)))),
			),
			'batch' => array(
				'name' => $batch_name,
				'institute' => $institute,
				'start_date' => isset($batch['start_date']) ? $batch['start_date'] : '',
				'end_date' => isset($batch['end_date']) ? $batch['end_date'] : '',
			),
			'items' => $items,
			'subtotal' => $subtotal,
			'discount' => $discount,
			'promo_code' => $promo_code,
			'taxable' => $taxable,
			'gst_rate' => $gst_rate,
			'gst' => $gst,
			'total' => round($paid, 2),
			'total_words' => $this->amount_in_words($paid),
		);
	}

	/** Seller block: site title + logo (data URI, works in Dompdf) + contact from frontend_details. */
	protected function seller()
	{
		$site = $this->CI->db_model->select_data('site_title,site_logo', 'site_details', '', 1, array('id', 'desc'));
		$fd = $this->CI->db_model->select_data('address,mobile,email', 'frontend_details', '', 1);
		$name = !empty($site[0]['site_title']) ? $site[0]['site_title'] : 'Gradmo';
		$logo = '';
		$logo_w = 0;
		$logo_h = 120;
		if (!empty($site[0]['site_logo'])) {
			$file = FCPATH . 'uploads/site_data/' . basename($site[0]['site_logo']);
			if (is_file($file) && filesize($file) < 2 * 1024 * 1024) {
				// Explicit width/height: Dompdf 0.8 on PHP 8 crashes on images with auto width.
				$dim = @getimagesize($file);
				$logo_w = (!empty($dim[0]) && !empty($dim[1])) ? (int) round($dim[0] * $logo_h / $dim[1]) : 140;
				$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
				$mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : (($ext === 'svg') ? 'image/svg+xml' : 'image/' . $ext);
				$logo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file));
			}
		}
		$gstin = trim((string) $this->CI->common->general_settings('gst_number'));
		return array(
			'name' => $name,
			'logo' => $logo,
			'logo_w' => $logo_w,
			'logo_h' => $logo_h,
			'address' => !empty($fd[0]['address']) ? (string) $fd[0]['address'] : '',
			'mobile' => !empty($fd[0]['mobile']) ? (string) $fd[0]['mobile'] : '',
			'email' => !empty($fd[0]['email']) ? (string) $fd[0]['email'] : '',
			'gstin' => $gstin,
			'website' => preg_replace('#^https?://#', '', rtrim(base_url(), '/')),
		);
	}

	/**
	 * Invoice HTML page.
	 * $links: back (URL), download (true shows the Download PDF button), autodownload (bool: save PDF on load).
	 */
	public function html(array $inv, $for_pdf = false, array $links = array())
	{
		return $this->CI->load->view('invoice/payment_invoice', array(
			'inv' => $inv,
			'for_pdf' => (bool) $for_pdf,
			'links' => $links,
		), true);
	}

	/** Indian-format amount in words, e.g. "Seven Hundred Five Rupees and Sixty Four Paise Only". */
	public function amount_in_words($amount)
	{
		$amount = round((float) $amount, 2);
		$rupees = (int) floor($amount);
		$paise = (int) round(($amount - $rupees) * 100);
		$words = $rupees > 0 ? $this->words_indian($rupees) . ' Rupees' : 'Zero Rupees';
		if ($paise > 0) {
			$words .= ' and ' . $this->words_indian($paise) . ' Paise';
		}
		return $words . ' Only';
	}

	protected function words_indian($n)
	{
		$ones = array('', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve',
			'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen');
		$tens = array('', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety');
		$two = function ($x) use ($ones, $tens) {
			return $x < 20 ? $ones[$x] : trim($tens[(int) ($x / 10)] . ' ' . $ones[$x % 10]);
		};
		$three = function ($x) use ($two, $ones) {
			$h = (int) ($x / 100);
			$r = $x % 100;
			return trim(($h ? $ones[$h] . ' Hundred' : '') . ($r ? ' ' . $two($r) : ''));
		};
		$parts = array();
		$crore = (int) ($n / 10000000); $n %= 10000000;
		$lakh = (int) ($n / 100000); $n %= 100000;
		$thousand = (int) ($n / 1000); $n %= 1000;
		if ($crore) { $parts[] = $this->words_indian($crore) . ' Crore'; }
		if ($lakh) { $parts[] = $two($lakh) . ' Lakh'; }
		if ($thousand) { $parts[] = $two($thousand) . ' Thousand'; }
		if ($n) { $parts[] = $three($n); }
		return trim(implode(' ', $parts));
	}
}
