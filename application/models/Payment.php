// <?php defined('BASEPATH') OR exit('No direct script access allowed'); 
 
// class Payment extends CI_Model{ 
     
//     function __construct() { 
//         $this->transTable = 'payments'; 
//     } 
     
//     /* 
//      * Fetch payment data from the database 
//      * @param id returns a single record if specified, otherwise all records 
//      */ 
//     public function getPayment($conditions = array()){ 
//         $this->db->select('*'); 
//         $this->db->from($this->transTable); 
         
//         if(!empty($conditions)){ 
//             foreach($conditions as $key=>$val){ 
//                 $this->db->where($key, $val); 
//             } 
//         } 
         
//         $result = $this->db->get(); 
//         return ($result->num_rows() > 0)?$result->row_array():false; 
//     } 
     
//     /* 
//      * Insert payment data in the database 
//      * @param data array 
//      */ 
//     public function insertTransaction($data){ 
//         $insert = $this->db->insert($this->transTable,$data); 
//         return $insert?true:false; 
//     } 
     
// }
// ?>


<?php defined('BASEPATH') OR exit('No direct script access allowed'); 
 
class Payment extends CI_Model{ 
     
    function __construct() { 
        $this->transTable = 'student_payment_history'; 
    } 
     
    /* 
     * Fetch payment data from the database (student_payment_history; txn_id maps to transaction_id).
     */ 
    public function getPayment($conditions = array()){ 
        $this->db->select('*'); 
        $this->db->from($this->transTable); 
         
        if(!empty($conditions)){ 
            foreach($conditions as $key=>$val){ 
				$dbKey = $key;
				if ($key === 'txn_id') {
					$dbKey = 'transaction_id';
				}
                $this->db->where($dbKey, $val); 
            } 
        } 
         
        $result = $this->db->get(); 
        return ($result->num_rows() > 0)?$result->row_array():false; 
    } 
     
    /** 
     * Insert payment row into student_payment_history (legacy PayPal IPN keys mapped).
	 * @param array $data
     */ 
    public function insertTransaction($data){ 
		if (!$this->db->table_exists($this->transTable)) {
			return false;
		}
		$fields = array_flip($this->db->list_fields($this->transTable));
		$row = array();
		if (isset($data['txn_id'])) {
			$row['transaction_id'] = $data['txn_id'];
		}
		if (isset($data['user_id'])) {
			$row['student_id'] = (int) $data['user_id'];
		}
		if (isset($data['product_id'])) {
			$row['batch_id'] = (int) $data['product_id'];
		}
		if (isset($data['payment_gross'])) {
			$row['amount'] = (int) round((float) $data['payment_gross']);
		} elseif (isset($data['mc_gross'])) {
			$row['amount'] = (int) round((float) $data['mc_gross']);
		}
		$row['mode'] = 'paypal';
		if (isset($data['status'])) {
			$row['payment_status'] = strtoupper(trim((string) $data['status']));
		}
		if (isset($fields['payment_date'])) {
			$row['payment_date'] = date('Y-m-d H:i:s');
		}
		if (isset($fields['admin_id'])) {
			$row['admin_id'] = 0;
		}
		$insert = array();
		foreach ($row as $k => $v) {
			if (isset($fields[$k])) {
				$insert[$k] = $v;
			}
		}
		if (empty($insert) || empty($insert['transaction_id'])) {
			return false;
		}
        return $this->db->insert($this->transTable, $insert); 
    } 
     
}