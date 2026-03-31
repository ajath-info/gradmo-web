<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_cities extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('cities')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE
                ),
                'city_name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 150
                ),
                'status' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1
                ),
                'created_at DATETIME DEFAULT CURRENT_TIMESTAMP'
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table('cities', TRUE);
        }

        $seedCities = array(
            'Ahmedabad', 'Allahabad', 'Amritsar', 'Aurangabad', 'Bengaluru',
            'Bhopal', 'Bhubaneswar', 'Chandigarh', 'Chennai', 'Coimbatore',
            'Cuttack', 'Dehradun', 'Delhi', 'Dhanbad', 'Faridabad',
            'Ghaziabad', 'Gorakhpur', 'Gurugram', 'Guwahati', 'Gwalior',
            'Hyderabad', 'Indore', 'Jaipur', 'Jalandhar', 'Jammu',
            'Jamshedpur', 'Jodhpur', 'Kanpur', 'Kochi', 'Kolkata',
            'Kota', 'Lucknow', 'Ludhiana', 'Madurai', 'Meerut',
            'Mumbai', 'Mysuru', 'Nagpur', 'Nashik', 'Noida',
            'Patna', 'Prayagraj', 'Pune', 'Raipur', 'Rajkot',
            'Ranchi', 'Surat', 'Thane', 'Udaipur', 'Vadodara',
            'Varanasi', 'Vijayawada', 'Visakhapatnam'
        );

        foreach ($seedCities as $cityName) {
            $exists = $this->db->where('city_name', $cityName)->get('cities')->row_array();
            if (empty($exists)) {
                $this->db->insert('cities', array(
                    'city_name' => $cityName,
                    'status' => 1
                ));
            }
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('cities', TRUE);
    }
}
