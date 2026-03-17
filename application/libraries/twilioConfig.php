<?php 
 
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    $CI =& get_instance();
    if($_SESSION['role']=="Admin"){
        $con = $_SESSION['user_id'];
         // dynemic data 
            $data = $CI->db->where('user_id',$con)->get('ka_twilio_setting')->row();
            $config['mode'] = 'sandbox'; 
            //* Account SID
            $config['account_sid'] = $data->account_sid;
            //Auth Token
            $config['auth_token'] = $data->auth_token;
            // Twilio Phone Number
            $config['number'] = $data->number;
            /**
            * API Version
            **/
            $config['api_version'] = '2010-04-01';
    }else if($_SESSION['role']=="Instructor"){
        //  Statics Data Twilio setting 
            $config['mode'] = 'sandbox'; 
            //* Account SID
            $config['account_sid'] = 'ACe54a883061a121bf62d6af477f2cec52';
            //Auth Token
            $config['auth_token'] = 'f40d9b3c78c515b2bc9452657f4d3f0d';
            // Twilio Phone Number
            $config['number'] = '+19549457405';
            /**
            * API Version
            **/
            $config['api_version'] = '2010-04-01';
    }
    
    // //  Statics Data Twilio setting 
    //         $config['mode'] = 'sandbox'; 
    //         //* Account SID
    //         $config['account_sid'] = 'ACe54a883061a121bf62d6af477f2cec52';
    //         //Auth Token
    //         $config['auth_token'] = 'f40d9b3c78c515b2bc9452657f4d3f0d';
    //         // Twilio Phone Number
    //         $config['number'] = '+19549457405';
    //         /**
    //         * API Version
    //         **/
    //         $config['api_version'] = '2010-04-01';
?>