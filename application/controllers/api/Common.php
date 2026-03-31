<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Common extends CI_Controller {

    function __construct()
    {
        error_reporting(0);
        parent::__construct();
        $this->load->helper('api_access');
    }

    public function city_list()
    {
        if (api_require_valid_access_token($this) === false) {
            return;
        }

        $cities = $this->db_model->select_data(
            'id, city_name',
            'cities',
            array('status' => 1),
            '',
            array('city_name', 'asc')
        );

        if (!empty($cities)) {
            $arr = array(
                'status' => 'true',
                'msg' => 'City list fetched successfully.',
                'cityData' => $cities
            );
        } else {
            $arr = array(
                'status' => 'false',
                'msg' => 'No city found.',
                'cityData' => array()
            );
        }

        echo json_encode($arr);
    }
}
