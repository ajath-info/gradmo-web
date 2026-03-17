<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
	require APPPATH.'third_party/twilio-php-main/src/Twilio/autoload.php';
	use Twilio\Rest\Client;
	
	class Twilio{
    public $CI;
    public $sid = "AC0a28715de44e68cc16de299f935327df";
    public $token = "84f96c02a5a035f0336023b91cf279b9";
     
     	    
	 function __construct(){
	       //parent::__construct();
	 $this->CI = get_instance();
	$this->CI->load->model('db_model');
    
}

        function Send_Otp($data){
                // Your Account Sid and Auth Token from twilio.com/user/account
                  $sid = "AC0a28715de44e68cc16de299f935327df";
                    $token = "84f96c02a5a035f0336023b91cf279b9";
                $client = new Client($sid, $token);
            try {
               $messages = $client->messages->create(
                // the number you'd like to send the message to
               $data['mobile_number'],
                array(
                    // A Twilio phone number you purchased at twilio.com/console
                    "from" => "+14143107837",
                    // the body of the text message you'd like to send
                    'body' => $data['msg']
                )   
            );
            return $messages;
            } catch (\Exception $e) {
                // if($e->getStatusCode()=='400'){
                   return $e;
                //   echo  $e->getMessage();
                  
                // }
            }
            
        }
	}

?>