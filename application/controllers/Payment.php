<?php
defined('BASEPATH') OR exit('No direct script access allowed');
  
class Payment extends CI_Controller {
  
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(array('form_validation','session'));
        $this->load->helper(array('url','html','form'));
             
     }
  
    public function index()
    {
        $this->load->view('razorpay');
    }   
    public function razorPaySuccess()
    { 
		$this->load->library('security');
		$student_id = (int) $this->input->post('user_id');
		if ($student_id < 1) {
			$student_id = 1;
		}
		$batch_id = (int) $this->input->post('product_id');
		$txn = trim((string) $this->input->post('razorpay_payment_id'));
		$amt = (int) round((float) $this->input->post('totalAmount'));
		if ($student_id < 1 || $batch_id < 1 || $txn === '') {
			echo json_encode(array('msg' => 'Invalid payment data', 'status' => false));
			return;
		}
		if (!$this->db->table_exists('student_payment_history')) {
			echo json_encode(array('msg' => 'Payment table not available', 'status' => false));
			return;
		}
		$fields = array_flip($this->db->list_fields('student_payment_history'));
		$row = array(
			'student_id' => $student_id,
			'batch_id' => $batch_id,
			'transaction_id' => $txn,
			'mode' => 'razorpay',
			'amount' => $amt > 0 ? $amt : 0,
			'admin_id' => 0,
		);
		if (isset($fields['razorpay_payment_id'])) {
			$row['razorpay_payment_id'] = $txn;
		}
		if (isset($fields['payment_status'])) {
			$row['payment_status'] = 'SUCCESS';
		}
		if (isset($fields['payment_date'])) {
			$row['payment_date'] = date('Y-m-d H:i:s');
		}
		$ins = array();
		foreach ($row as $k => $v) {
			if (isset($fields[$k])) {
				$ins[$k] = $v;
			}
		}
		$this->db->insert('student_payment_history', $this->security->xss_clean($ins));
		$arr = array('msg' => 'Payment successfully credited', 'status' => true);
		echo json_encode($arr);
    }
    public function RazorThankYou()
    {
     $this->load->view('razorthankyou');
    }
}