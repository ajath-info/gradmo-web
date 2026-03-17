<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Teacher_profile extends CI_Controller { 

	function __construct(){
		parent::__construct();
		$timezoneDB = $this->db_model->select_data('timezone','site_details',array('id'=>1));
		if(isset($timezoneDB[0]['timezone']) && !empty($timezoneDB[0]['timezone'])){
            date_default_timezone_set($timezoneDB[0]['timezone']);
        }
		if(!empty($_SESSION['role'])){
	        if($_SESSION['role']=='student'){
	            redirect(base_url('student/dashboard')); 
	        }else if($_SESSION['role']==1){
	            redirect(base_url('admin/dashboard')); 
	        }
	    }else{
	        redirect(base_url('login'));
	    }
		
		$uid = $this->session->userdata('uid');
        $teacherData = $this->db_model->select_data('token, brewers_check, status','users  use index (id)',array('id'=>$uid),'1',array('id','desc'));
		if(!empty($teacherData)){
    	   if(($teacherData[0]['token'] !=1) || ($teacherData[0]['status'] !=1) || ($teacherData[0]['brewers_check'] !=$_SESSION['brewers_check'])){
        		if($this->session->all_userdata()){
                    $this->session->sess_destroy();
        			redirect(base_url('login'));
        		}
    	   }
		}
		// check select language
		$this->load->helper('language');
		$language = $this->general_settings('language_name');
		if($language=="french"){
			$this->lang->load('french_lang', 'french');
		}else if($language=="arabic"){
			$this->lang->load('arabic_lang', 'arabic');
		}else if($language=="english"){
			$this->lang->load('english_lang', 'english');
		}else if($language=="hindi"){
	    	$this->lang->load('hindi_lang', 'hindi');
		}else if($language=="german"){
	    	$this->lang->load('german_lang', 'german');
		}else{
	    	$this->lang->load('spanish_lang', 'spanish');
		}
	}

	function general_settings($key_text=''){
        $data = $this->db_model->select_data('*','general_settings',array('key_text'=>$key_text),1);
        return $data[0]['velue_text'];
    }
	public function index()
	{
		$header['title'] =$this->lang->line('ltr_dashboard');
		$admin_id = $this->session->userdata('admin_id');
		$uid = $this->session->userdata('uid');
		$batch_id = $this->session->userdata('batch_id');
		if(!empty($batch_id)){
			$cond = "admin_id = $admin_id AND status = 1 AND type = 1 AND batch_id in ($batch_id) AND mock_sheduled_date = '".date('Y-m-d')."'";
			$data['total_mock_test']=$this->db_model->countAll('exams use index (id)',$cond);
		}else{
			$data['total_mock_test'] = 0;
		}
        $data['total_question']=$this->db_model->countAll('questions use index (id)',array('admin_id'=>$admin_id,'added_by'=>$uid));
        
        $data['imp_question']=$this->db_model->countAll('questions use index (id)',array('admin_id'=>$admin_id,'added_by'=>$uid,'category'=>1));
        $data['vimp_question']=$this->db_model->countAll('questions use index (id)',array('admin_id'=>$admin_id,'added_by'=>$uid,'category'=>2));
        
        // $batchid = $_SESSION['batch_id'];
        // $Conn = "teacher_id = $uid AND batch_id in ($batchid)";
        
        $data['batch_count']=$this->db_model->countAll('batch_subjects',array('teacher_id' => $uid),'','','','','','');
        // print_r($data['batch_count']);
        // die;
        $data['active_batch']=$this->db_model->countAll('batches',array( 'status'=>1),'','','','','','',array('id',$this->session->userdata('batch_id')));
        // echo $this->db->last_query();
        $data['inactive_batch']=$this->db_model->countAll('batches',array( 'status'=>0 ),'','','','','','',array('id',$this->session->userdata('batch_id'))); 
        
		$data['total_extra_class']=$this->db_model->countAll('extra_classes use index(id)',array('admin_id'=>$admin_id,'date'=>date('Y-m-d'),'teacher_id'=>$uid));
		$data['total_previous_class']=$this->db_model->countAll('extra_classes use index(id)',array('admin_id'=>$admin_id,'date < '=>date('Y-m-d'),'teacher_id'=>$uid));
		$data['total_upcoming_class']=$this->db_model->countAll('extra_classes use index(id)',array('admin_id'=>$admin_id,'date > '=>date('Y-m-d'),'teacher_id'=>$uid));
		
		$data['total_leave_request']=$this->db_model->countAll('leave_management ',array('admin_id'=>$admin_id,'teacher_id'=>$uid));
		$data['total_leave_aproved']=$this->db_model->countAll('leave_management ',array('admin_id'=>$admin_id,'teacher_id'=>$uid,'status'=>1));
		$data['total_leave_decline']=$this->db_model->countAll('leave_management ',array('admin_id'=>$admin_id,'teacher_id'=>$uid,'status'=>2));
		
		// if(!empty($batch_id)){
		// 	$batCon = "admin_id = $admin_id AND status=1 AND id in ($batch_id)";
		// 	$data['all_batches'] = $this->db_model->select_data('id,batch_name','batches  use index (id)',$batCon,'',array('id','desc'));
		// }else{
		// 	$data['all_batches'] = '';
		// }
		if(!empty($batch_id)){
			$batCon = "admin_id = $admin_id AND id in ($batch_id)";
			$data['all_batches'] = $this->db_model->select_data('id,batch_name','batches  use index (id)',$batCon,'',array('id','desc'));
		}else{
			$data['all_batches'] = '';
		}

		$data['exam'] = $this->db_model->select_data('id,name','exams  use index (id)',array('admin_id'=>$admin_id,'type'=>1,'mock_sheduled_date <='=>date('Y-m-d')),'1',array('id','desc'),'','','','',array('batch_id',$this->session->userdata('batch_id')));
		
		if(!empty($data['exam'][0]['id'])){
    		$data['top_three'] = $this->db_model->select_data('*','mock_result  use index (id)',array('paper_id'=>$data['exam'][0]['id'],'mock_result.percentage >'=>0),'3',array('mock_result.percentage','desc'),'',array('students','students.id=mock_result.student_id'));
    		
    	    $data['good'] = $this->db_model->countAll('mock_result',array('paper_id'=>$data['exam'][0]['id'],'mock_result.percentage >='=>80));
    	   
    	    $data['poor'] = $this->db_model->countAll('mock_result',array('paper_id'=>$data['exam'][0]['id'],'mock_result.percentage <'=>60));
    	   
    	    $data['avarage'] = $this->db_model->countAll('mock_result',array('paper_id'=>$data['exam'][0]['id'],'mock_result.percentage <'=>80,'mock_result.percentage >='=>60));
		}
		$data['doubts_data'] = $this->db_model->countAll('student_doubts_class',array('teacher_id' =>$uid));
		$data['doubts_data_aprove'] = $this->db_model->countAll('student_doubts_class',array('teacher_id' =>$uid,'status'=>1));
		$data['doubts_data_pending'] = $this->db_model->countAll('student_doubts_class',array('teacher_id' =>$uid,'status'=>0));
              
		$this->load->view('common/teacher_header',$header);
		$this->load->view('teacher/dashboard',$data);
		$this->load->view('common/teacher_footer');
	}
	
	function profile(){
		$header['title'] =$this->lang->line('ltr_profile');
		
		$this->load->view('common/teacher_header',$header);
		$this->load->view('teacher/profile');
		$this->load->view('common/teacher_footer');
	}
    
    function video_manage(){
		$header['title']=$this->lang->line('ltr_video_lecture_lanager');
		$admin_id = $this->session->userdata('admin_id');
		
		$subject_ids = $this->session->userdata('subject_id');
		$subCon = "admin_id = $admin_id AND id in ($subject_ids)";
		$data['subject'] = $this->db_model->select_data('id,subject_name','subjects use index (id)',$subCon,'',array('id','desc'));

		$batch_ids = $this->session->userdata('batch_id');
		if(!empty($batch_ids)){
			$batCon = "admin_id = $admin_id AND id in ($batch_ids)";
			$data['batch'] = $this->db_model->select_data('id,batch_name','batches  use index (id)',$batCon,'',array('id','desc'));
		}else{
			$data['batch'] = '';
		}
		$data['video_data'] = $this->db_model->select_data('id','video_lectures use index (id)',array('admin_id'=>$admin_id,'added_by'=>$this->session->userdata('uid')));
		$this->load->view("common/teacher_header",$header);
		$this->load->view("admin/video_manage",$data); 
		$this->load->view("common/teacher_footer");
	}

	function question_manage(){
		$header['title']=$this->lang->line('ltr_questions_manager');
		$admin_id = $this->session->userdata('admin_id');
		
		$subject_ids = $this->session->userdata('subject_id');
		$subCon = "admin_id = $admin_id AND id in ($subject_ids)";
		$data['subject'] = $this->db_model->select_data('id,subject_name','subjects use index (id)',$subCon,'',array('id','desc'));
		$data['question_data'] = $this->db_model->countAll('questions','');
		$this->load->view("common/teacher_header",$header);
		$this->load->view("admin/question_manage",$data); 
		$this->load->view("common/teacher_footer");
	}

	function exam_manage(){
		$header['title']=$this->lang->line('ltr_manage_paper');
	
		$data['batch'] = $this->db_model->select_data('id,batch_name','batches  use index (id)',array('admin_id'=>$this->session->userdata('admin_id')),'',array('id','desc'));
		$data['question_data'] = $this->db_model->countAll('exams',array('admin_id'=>$this->session->userdata('admin_id')));
	
		$this->load->view("common/teacher_header",$header);
		$this->load->view("admin/exam_manage",$data); 
		$this->load->view("common/teacher_footer");
	}

	function view_paper($id){
		$header['title']=$this->lang->line('ltr_view_paper');
		
		$data['paperData'] = $this->db_model->select_data('*','exams use index (id)',array('admin_id'=>$this->session->userdata('admin_id'),'id'=>$id),1);
		$this->load->view("common/teacher_header",$header);
		$this->load->view("admin/view_paper",$data); 
		$this->load->view("common/teacher_footer");
	}

	function practice_result(){
		$header['title']=$this->lang->line('ltr_practice_result');
		
		$data['paperList'] = $this->db_model->select_data('id,name','exams use index (id)',array('type'=>2,'admin_id'=>$this->session->userdata('admin_id')),'',array('id','desc'));
		$this->load->view("common/teacher_header",$header);
		$this->load->view("admin/practice_result",$data); 
		$this->load->view("common/teacher_footer");
	}

	function mock_result(){
		$header['title']=$this->lang->line('ltr_mock_test_result');
		
		$data['paperList'] = $this->db_model->select_data('id,name','exams use index (id)',array('type'=>1,'admin_id'=>$this->session->userdata('admin_id')),'',array('id','desc'));
		$this->load->view("common/teacher_header",$header);
		$this->load->view("admin/mock_result",$data); 
		$this->load->view("common/teacher_footer");
	}

	function answer_sheet($paper_type='',$result_id=''){
		$header['title']=$this->lang->line('ltr_answer_sheet');
		if($paper_type == 'mock'){
			$type = 1;
			$table = 'mock_result';
		}else{
			$type = 2;
			$table = 'practice_result';
		}
		$data['result_details'] = $this->db_model->select_data("$table.*,exams.question_ids,students.name",$table.' use index (id)',array("$table.id"=>$result_id),1,'','',array('multiple',array(array('students',"students.id = $table.student_id"),array('exams',"exams.id = $table.paper_id"))));
		
		$this->load->view("common/teacher_header",$header);
		$this->load->view("student/answer_sheet",$data); 
		$this->load->view("common/teacher_footer");
    }

	function extra_classes(){
		$header['title']=$this->lang->line('ltr_extra_classes');
		$data['teacher_data'] = $this->db_model->select_data('batch_id','extra_classes  use index (id)',array('admin_id'=>$this->session->userdata('admin_id'), 'teacher_id'=>$this->session->userdata('uid')),1);
		$this->load->view("common/teacher_header",$header);
		$this->load->view("admin/extra_classes",$data); 
		$this->load->view("common/teacher_footer");
	}

	function homework_manage($date=''){
	 
		$header['title']= $this->lang->line('ltr_assignment_manager');
		$data['date'] = $date;
		$admin_id = $this->session->userdata('admin_id');
		$subject_ids = $this->session->userdata('subject_id');
		// print_r($this->session);
		$subCon = "admin_id = $admin_id AND id in ($subject_ids)";
		$data['subject'] = $this->db_model->select_data('id,subject_name','subjects use index (id)',$subCon,'',array('id','desc'));
		$batch_ids = $this->session->userdata('batch_id');
		if(!empty($batch_ids)){
			$batCon = "admin_id = $admin_id AND id in ($batch_ids)";
			$data['batch'] = $this->db_model->select_data('id,batch_name','batches  use index (id)',$batCon,'',array('id','desc'));
		}else{
			$data['batch'] = '';
		}
// 		echo $this->db->last_query();
		$data['hwo_data'] = $this->db_model->select_data('*','homeworks use index (id)',array('teacher_id'=>$this->session->userdata('uid'),'admin_id'=>$admin_id));
		//print_r($data['hwo_data']);die;
		$this->load->view("common/teacher_header",$header);
		$this->load->view("teacher/homework_manage",$data); 
		$this->load->view("common/teacher_footer");
	}
	
	function notice(){
		$header['title'] = $this->lang->line('ltr_notice');
		$admin_id = $this->session->userdata('admin_id');
		$uid = $this->session->userdata('uid');
		$this->db_model->update_data('notices use index(id)',array('read_status'=>1),array('teacher_id'=>$this->session->userdata('uid')));
		$subCon = "admin_id = '$admin_id' AND notice_for in ('Both','Teacher') || teacher_id ='$uid'";
		$data['notice_data'] =$this->db_model->select_data('*','notices',$subCon,1,array('id','desc'));
		$this->load->view('common/teacher_header',$header);
		$this->load->view('student/notice',$data);
		$this->load->view('common/teacher_footer');
	}

	function progress(){
		$header['title'] = $this->lang->line('ltr_progress');
		$admin_id = $this->session->userdata('admin_id');
		
		$subject_ids = $this->session->userdata('subject_id');
		$subCon = "admin_id = $admin_id AND id in ($subject_ids)";
		$data['subjects'] = $this->db_model->select_data('id,subject_name','subjects use index (id)',$subCon,'',array('id','desc'));
		$batch_id = $this->session->userdata('batch_id');
		if(!empty($batch_id)){
			$batchCon = "admin_id = $admin_id AND id in ($batch_id)";
			$data['batches'] = $this->db_model->select_data('id,batch_name','batches use index (id)',$batchCon,'',array('id','desc'));
		}else{
			$data['batches'] = '';
		}
		
		$data['chapter_data'] = $this->db_model->select_data('chapter,chapter_status','batch_subjects use index (id)',array('teacher_id'=>$this->session->userdata('uid')));
		$this->load->view('common/teacher_header',$header);
		$this->load->view('teacher/progress',$data);
		$this->load->view('common/teacher_footer');
	}

	function student_details($id=""){
		$header['title']=$this->lang->line('ltr_student_details');
		$batch_id = $this->session->userdata('batch_id');
		$admin_id = $this->session->userdata('admin_id');

		if(!empty($batch_id)){
			$batchCon = "admin_id = $admin_id AND id in ($batch_id)";
			$data['batch_name'] = $this->db_model->select_data('id,batch_name','batches use index (id)',$batchCon,'',array('id','desc'));
			$cond = "admin_id = $admin_id AND batch_id in ($batch_id) AND status = 1";
	        $data1['student_data'] = $this->db_model->countAll('students',$cond);
		}else{
			$data['batch_name'] = '';
		}
        $data['bid']= $id;
		$this->load->view("common/teacher_header",$header);
		$this->load->view("teacher/student_details",$data); 
		$this->load->view("common/teacher_footer");
	}
	   function student_Details_table($id=""){
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')){
            $post = $this->input->post(NULL,TRUE);
            $get = $this->input->get(NULL,TRUE);
             $admin_id = $this->session->userdata('admin_id');
            if(isset($post['length']) && $post['length']>0){
                if(isset($post['start']) && !empty($post['start'])){
                    $limit = array($post['length'],$post['start']);
                    $count = $post['start']+1;
                }else{ 
                    $limit = array($post['length'],0);
                    $count = 1;
                }
            }else{
                $limit = '';
                $count = 1;
            }
            
            if($post['search']['value'] != ''){
                $like = array('name',$post['search']['value']);
            }else{
               $like = array('batch_id',$id);
            }
            if($get['user_batch']!=''){
                $like = array('batch_id',$get['user_batch']);
                    // $or_like = array(array('batch_id' ,$get['user_batch']));
            }
            $batch_ids = $this->session->userdata('batch_id');
           if(isset($id) && empty($id)){
                $cond=array('admin_id'=>$admin_id);
           }
            $student_dat = $this->db_model->select_data('students.admin_id as aid,students.id as s_id,students.payment_status as paymentStatus,students.status,students.admission_date,students.name,students.email,students.contact_no,students.enrollment_id','students',$cond,$limit,array('students.id','asc'),$like,'','','',$where_in);
      
        //   echo $this->db->last_query();die;
            $new = [];
           
            if(($this->session->userdata('role')==3) && empty($this->session->userdata('batch_id'))){
                $student_data = "";
            }
            if(!empty($student_dat)){
                $role = $this->session->userdata('role');
                if($role == '1'){  
                    $profile = 'admin';
                }else if($role == '3'){
                    $profile = 'teacher';
                }
 
                $batch_array = $this->db_model->select_data('id,batch_name','batches use index (id)',array('id'=>$student_data[0]['batch_id']));
  
                foreach($student_dat as $student){
    
                    if (!empty($student['image'])){ 
                        $image = '<img src="'.base_url().'uploads/students/'.$student['image'].'" title="'.$student['name'].'" class="view_large_image"></a>';
                    }else{
                        $image = '<img src="'.base_url().'assets/images/student_img.png" title="'.$student['name'].'" class="view_large_image"></a>';
                    }
     
                    $batchData = '<span class="batchesGet" sis='.$student['s_id'].'><svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:svgjs="http://svgjs.com/svgjs" class="batches_icon" width="25" height="25" x="0" y="0" viewBox="0 0 486.31 486.31" style="enable-background:new 0 0 512 512" xml:space="preserve" class="hovered-paths"><g><path d="M.018 393.086a18.082 18.082 0 0 0 13.434 16.707l183.256 48.558a51.974 51.974 0 0 0 13.273 1.721c5.718 0 11.416-.936 16.878-2.818l247.238-85.063a18.095 18.095 0 0 0 12.185-18.109c-.446-8.084-6.211-14.882-14.107-16.66l-31.558-7.076-203.079 69.875a84.455 84.455 0 0 1-27.558 4.611 84.85 84.85 0 0 1-21.681-2.826L43.936 363.752l-31.974 11.52C4.518 377.955-.334 385.176.018 393.086z"  data-original="#000000" class="hovered-path"></path><path d="M.018 305.044c.335 7.91 5.797 14.677 13.449 16.709l183.24 48.556a51.802 51.802 0 0 0 30.151-1.096l247.238-85.064a18.097 18.097 0 0 0 12.185-18.108 18.105 18.105 0 0 0-14.107-16.662l-31.558-7.076-203.079 69.876a84.474 84.474 0 0 1-27.558 4.603 84.884 84.884 0 0 1-21.681-2.817L43.936 275.711l-31.974 11.521C4.518 289.913-.334 297.135.018 305.044z"  data-original="#000000" class="hovered-path"></path><path d="M237.538 224.128a84.475 84.475 0 0 1-27.558 4.604 84.885 84.885 0 0 1-21.681-2.818L43.936 187.669 11.962 199.19C4.518 201.872-.334 209.094.018 217.004c.335 7.908 5.797 14.674 13.449 16.707l183.24 48.557a51.931 51.931 0 0 0 13.273 1.722 51.82 51.82 0 0 0 16.878-2.817l247.238-85.065a18.099 18.099 0 0 0 12.185-18.109 18.102 18.102 0 0 0-14.107-16.66l-31.558-7.078-203.078 69.867z"  data-original="#000000" class="hovered-path"></path><path d="m13.452 145.662 183.256 48.557a51.93 51.93 0 0 0 13.273 1.721c5.718 0 11.416-.937 16.878-2.818l247.238-85.063a18.099 18.099 0 0 0 12.185-18.109 18.104 18.104 0 0 0-14.107-16.661L268.008 27.494a51.856 51.856 0 0 0-11.352-1.256 51.812 51.812 0 0 0-17.582 3.066L11.962 111.14C4.518 113.822-.334 121.044.018 128.954a18.084 18.084 0 0 0 13.434 16.708z"  data-original="#000000" class="hovered-path"></path></g></svg></span>';
                    if($student['status'] == 1){
                        $statusDrop = '<div class="admin_tbl_status_wrap"><a class="tbl_status_btn light_sky_bg changeStatusButton" data-id="'.$student['s_id'].'" data-table ="students" data-status ="0" href="javascript:;">'.$this->lang->line('ltr_active').'</a></div>';
                    }else{
                        $statusDrop = '<div class="admin_tbl_status_wrap">
                    <a class="tbl_status_btn light_red_bg changeStatusButton" data-id="'.$student['s_id'].'" data-table ="students" data-status ="1" href="javascript:;">'.$this->lang->line('ltr_inactive').'</a></div>';
                    }
                    if($student['paymentStatus']==1){
                        $payment_status=$this->lang->line('ltr_paid'); 
                    }else{
                        $payment_status=$this->lang->line('ltr_unpaid'); 
                    }
        
                    $action = '<div class="actions_wrap_dot">
                        <span class="tbl_action_drop" >
                            <svg xmlns="https://www.w3.org/2000/svg" width="15px" height="4px">
            				<path fill-rule="evenodd" fill="rgb(77 74 129)" d="M13.031,4.000 C11.944,4.000 11.062,3.104 11.062,2.000 C11.062,0.895 11.944,-0.000 13.031,-0.000 C14.119,-0.000 15.000,0.895 15.000,2.000 C15.000,3.104 14.119,4.000 13.031,4.000 ZM7.500,4.000 C6.413,4.000 5.531,3.104 5.531,2.000 C5.531,0.895 6.413,-0.000 7.500,-0.000 C8.587,-0.000 9.469,0.895 9.469,2.000 C9.469,3.104 8.587,4.000 7.500,4.000 ZM1.969,4.000 C0.881,4.000 -0.000,3.104 -0.000,2.000 C-0.000,0.895 0.881,-0.000 1.969,-0.000 C3.056,-0.000 3.937,0.895 3.937,2.000 C3.937,3.104 3.056,4.000 1.969,4.000 Z"></path>
            				</svg>
            				<ul class="tbl_action_ul">
            				    <li>
            				        <a data-toggle="tooltip" data-placement="top" title="Attendance" href="'.base_url('teacher/student-attendance/').$student['s_id'].'">
            				            <span class="action_drop_icon">
            				                <i class="icofont-check-circled"></i>
            				            </span>
            				            '.$this->lang->line('ltr_attendance').'
            				        </a>
            				    </li>
            				    <li>
            				        <a data-toggle="tooltip" data-placement="top" title="Extra Class Attendance" href="'.base_url('teacher/student-attendance-extra-class/').$student['s_id'].'">
            				            <span class="action_drop_icon">
            				                <i class="icofont-tasks-alt"></i>
            				            </span>
            				            '.$this->lang->line('ltr_extra_class_attendance').'
            				        </a>
            				    </li>
            				    <li>
            				        <a href="'.base_url('teacher/student-progress/').$student['s_id'].'">
            				            <span class="action_drop_icon">
            				                <i class="icofont-paper"></i>
            				            </span>
            				             '.$this->lang->line('ltr_progress').'
            				        </a>
            				    </li>
            				    <li>
            				         <a  href="'.base_url('teacher/student-academic-record/').$student['s_id'].'">
            				                <i class="icofont-bars"></i>
            				            </span>'.$this->lang->line('ltr_academic_record').'
            				        </a>
            				    </li>
            				    <li>
            				         <a href="'.base_url().$profile.'/student-notice/'.$student['s_id'].'">
            				            <span class="action_drop_icon">
            				                <i class="fas fa-bell"></i>
            				            </span>
            				            '.$this->lang->line('ltr_notice').'
            				        </a>
            				    </li>
								<li>
            				        <a href="'.base_url().$profile.'/doubts-ask/'.$student['s_id'].'">
            				            <span class="action_drop_icon">
            				                <i class="icofont-speech-comments"></i>
            				            </span>
            				           '.$this->lang->line('ltr_doubts_ask').'
            				        </a>
            				    </li>
            				    <li>
            				        <a href="javascript:void(0);" class="changePassModal" data-id="'.$student['s_id'].'">
            				            <span class="action_drop_icon">
            				                <i class="icofont-gear"></i>
            				            </span>
            				            '.$this->lang->line('ltr_change_password').'
            				        </a>
            				    </li>
            				</ul>
                        </span>
                     </div>';
                                  
					 $user_name =$this->readMoreWord($student['name'], 'Student Name',15);
                 
                    $dataarray[] = array(
                        '<input type="checkbox" class="checkOneRow" value="'.$student['s_id'].'">',
                            $count,
                            $image.$user_name,
                            '<p class="email">'.$student['email'].'</p>',
                            $student['contact_no'],
                            $student['enrollment_id'],
                            $batchData,
                            date('d-m-Y',strtotime($student['admission_date'])),
                            $action
                    ); 
                    
                    $count++;
                }
                 $recordsTotal = $this->db_model->countAll('students use',$cond,'','',$like,'','','');
            
                $output = array(
                    "draw" => $post['draw'],
                    "recordsTotal" => $recordsTotal,
                    "recordsFiltered" => $recordsTotal,
                    "data" => $dataarray,
                );
            }else{ 
                $output = array(
                    "draw" => $post['draw'],
                    "recordsTotal" => 0,
                    "recordsFiltered" => 0,
                    "data" => array(),
                );
            }
            echo json_encode($output,JSON_UNESCAPED_SLASHES);
        }else{
            echo $this->lang->line('ltr_not_allowed_msg');
        } 
    }
     function readMoreWord($story_desc, $title='',$C_word='') {
        $chars = 90;
        if(!empty($C_word)){
            $chars =$C_word;
        }
        
        $count_word = strlen($story_desc);
        if($count_word>$chars){
            $readMore = '<a class="charaViewPopupModel" data-title="'.$title.'" data-word="'.$story_desc.'"  href="javascript:;">  .... </a>';
    	    $story_desc = substr($story_desc,0,$chars);  
    	    $story_desc = substr($story_desc,0,strrpos($story_desc,' '));  
    	    $story_desc = $story_desc.' '.$readMore;  
    	    return $story_desc;  
    	    
        }else{
            return $story_desc; 
        }
    }
	function student_notice($id){
		$data['student_id'] = $id;
		$header['title']=$this->lang->line('ltr_student_notice');
		if(!empty($id)){
			$data['student_data'] = $this->db_model->select_data('name,image,email,contact_no,admission_date,batch_id','students use index (id)',array('admin_id'=>$this->session->userdata('admin_id'),'id'=>$id));
			$this->load->view("common/teacher_header",$header);
    		$this->load->view("admin/student_notice",$data); 
    		$this->load->view("common/teacher_footer");
		}else{
			redirect(base_url('teacher/student-details'));
		}
	}
	
	function apply_leave(){
		$header['title']=$this->lang->line('ltr_apply_leave');
		$data = array();
		$this->load->view("common/teacher_header",$header);
		$data['leave_data'] = $this->db_model->select_data('id','leave_management use index (id)',array('admin_id'=>$this->session->userdata('admin_id'),'teacher_id'=>$this->session->userdata('uid')),1);
		$this->load->view("teacher/apply_leave",$data); 
		$this->load->view("common/teacher_footer");
	} 

	function live_class($date=''){
		$header['title']=$this->lang->line('ltr_live_class');
		$admin_id = $this->session->userdata('admin_id');
		
		$subject_ids = $this->session->userdata('subject_id');
		$subCon = "admin_id = $admin_id AND id in ($subject_ids)";
		$data['subject'] = $this->db_model->select_data('id,subject_name','subjects use index (id)',$subCon,'',array('id','desc'));
		
		$batch_ids =$this->session->userdata('batch_id');
		if(!empty($batch_ids)){
        $batCon = "batches.admin_id = $admin_id AND batches.id in ($batch_ids)";
        $data['live_data']  = $this->db_model->select_data('live_class_setting.*,batches.batch_name','live_class_setting',$batCon,1,array('id','desc'),'',array('batches','batches.id=live_class_setting.batch'));
        $data['jetsilive_data']  = $this->db_model->select_data('jetsi_setting.*,batches.batch_name','jetsi_setting',$batCon,1,array('id','desc'),'',array('batches','batches.id=jetsi_setting.batch'));
		}
		$this->load->view("common/teacher_header",$header);
		$this->load->view("teacher/live_class",$data); 
		$this->load->view("common/teacher_footer");
	}

    public function JetsiMeeting(){
	    $livedata =$this->db_model->select_data('*','jetsi_setting',array('id' =>$_POST['live_class_id']));
	    if(empty($livedata)){
	        redirect('teacher/live-class');
	    }else{
	        
    		$data=array(
    			'uid'=>$this->session->userdata('uid'),
    			'batch_id'=>$livedata[0]['batch'],
    			'subject_id'=>$_POST['subject_id'],
    			'chapter_id'=>$_POST['chapter_id'],
    			'start_time'=>date('h:i:s a'),
    			'date'=>date('Y-m-d'),
    			'admin_id'=>$this->session->userdata('uid'),
    			'type_class'=>2,
    			);
    		
    		$batch_id = $livedata[0]['batch'];
    		$student_data = $this->db_model->select_data('id','students', array('batch_id'=> $batch_id,'status'=>'1'));
    			$title = 'Live Class';
    			$where = 'live';
                for($i=0;$i<count($student_data);$i++){
                        $student_id = $student_data[$i]['id'];
                }
                
    		$this->push_notification_android($batch_id,$title,$where,$student_id);
            $ins = $this->db_model->insert_data('live_class_history',$data);
            $batch_name =$this->db_model->select_data('id,batch_name','batches',array('id' =>$livedata[0]['batch']));
        	$data['inser_id']=$ins;
    		$data['display_name']=$this->session->userdata('name');
    		$data['meeting_number']=$livedata[0]['meeting_number'];
    		$data['password']=$livedata[0]['password'];
            $data['title']="Jetsi Meet";
            $data['roomName']=$batch_name[0]['batch_name'];
            // print_r($data);
            // die;
    		if($ins){
    	        $this->load->view("teacher/meeting",$data);
    		    
    		}else{
    		     redirect('teacher/live-class');
    		    
    		}
	    }
	}
	function end_jetsi_metting($id){
	    if($id==0){
	         redirect('student/dashboard');
	    }else{
    	    $data=array(
        			'end_time'=>date('h:i:s a')
        			);
                $ins = $this->db_model->update_data_limit('live_class_history',$data,array('id'=>$id),1);
               redirect('teacher/live-class');
    	}
    }
    public function push_notification_android($batch_id='',$title='',$where='',$student_id=''){
     
        if(!empty($batch_id)){
            $batchCon = "status = 1 AND token !='' AND batch_id in ($batch_id)";
	        $get_token = $this->db_model->select_data('token','students',$batchCon,'');
	        $batch_data = current($this->db_model->select_data('batch_name','batches',array('id' => $batch_id),'')); 
        }else{
            if(!empty($student_id)){
                 $get_token = $this->db_model->select_data('token','students',array('status'=>1,'token !='=>'', 'id'=>$student_id),'');
                  
            }else{
                $get_token = $this->db_model->select_data('token','students',array('status'=>1,'token !='=>''),'');
            }
        }
        if(!empty($get_token)){
            $array_chunk = array_chunk($get_token,999);
            $array_count = count($array_chunk);
            for ($x = 0; $x < $array_count; $x++) {
                $device_id=array();
                foreach($array_chunk[$x] as $get_tokens){
                    if(!empty($get_tokens['token'])){
                        array_push($device_id,$get_tokens['token']);
                    }
                }
                $url = 'https://fcm.googleapis.com/fcm/send';
                $api_key = $this->general_settings('firebase_key');

                // $api_key = 'AAAAFU0Nyks:APA91bFWu1zpzRasM60cqJjMvfcL5Uc667MP38b5CaYd5O3g-ioRYGtVSvBCdFUt5ea4H8eIDbPKNs98z5W0RxFfRsswy07p1EbSKRRlQkUA1b9sb_fBC2sHvFJZWhpILlZlOqz0_M4u';
                $message = array(
                        'title' => $title,
                        'body' => array(
                            'where'=>$where,
                            'batch_name' =>(!empty($batch_data['batch_name'])) ? $batch_data['batch_name'] : "" ,
                            'batch_id'=>$batch_id
                            )
                );
                $fields = array (
                    'registration_ids' =>$device_id,
                    'data' => array (
                    "message" => $message
                    )
                );
                $headers = array(
                    'Content-Type:application/json',
                    'Authorization:key='.$api_key
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                $result = curl_exec($ch);
               
                if ($result === FALSE) {
                    die('FCM Send Error: ' . curl_error($ch));
                }
                curl_close($ch);
            }
             return $result;
        }
   
    }
    function start_class(){
		$livedata =$this->db_model->select_data('*','live_class_setting',array('id' =>$_POST['live_class_id']));
		$data=array(
			'uid'=>$this->session->userdata('uid'),
			'batch_id'=>$livedata[0]['batch'],
			'subject_id'=>$_POST['subject_id'],
			'chapter_id'=>$_POST['chapter_id'],
			'start_time'=>date('h:i:s a'),
			'date'=>date('Y-m-d')
			);
        $ins = $this->db_model->insert_data('live_class_history',$data);
    	$data['inser_id']=$ins;
	    $data['signature'] = $this->generate_signature($livedata[0]['zoom_api_key'], $livedata[0]['zoom_api_secret'],$livedata[0]['meeting_number'],1);
		$data['sdk_key']=$livedata[0]['zoom_api_key'];
		$data['sdk_secret']=$livedata[0]['zoom_api_secret'];
		$data['display_name']=$this->session->userdata('name');
		$data['meeting_number']=$livedata[0]['meeting_number'];
		$data['password']=$livedata[0]['password'];
		
		$this->load->view("teacher/start_live_class",$data);
	}
	
	function generate_signature ( $api_key, $api_sercet, $meeting_number, $role){
		$time = (time()- 5*60) * 1000; //time in milliseconds (or close enough)
		$data = base64_encode($api_key . $meeting_number . $time . $role);
		$hash = hash_hmac('sha256', $data, $api_sercet, true);
		$_sig = $api_key . "." . $meeting_number . "." . $time . "." . $role . "." . base64_encode($hash);
		return rtrim(strtr(base64_encode($_sig), '+/', '-_'), '=');
	}
	function end_metting($id){
	   
	    $data=array(
			'end_time'=>date('h:i:s a')
			);
		 $ins = $this->db_model->update_data_limit('live_class_history',$data,array('id'=>$id),1);
		redirect(base_url().'teacher/live-class');
	}
	function student_progress($id){		
		if(isset($_POST['filter_performance'])){
			$month = $_POST['month']; 
			$year = $_POST['year'];	
		}else{ 	
			$month = date('m');
			$year = date('Y');
		}
		$header['title']=$this->lang->line('ltr_student_progress');
		$like = $year.'-'.$month.'-';
		
        $table_name = 'practice_result';
		$cond1=array("admin_id"=>$this->session->userdata('admin_id'),'type'=>2);
		$exam_Data = $this->db_model->select_data('*', 'exams use index (id)',$cond1,'',array('id','asc'));
		$dataarray_pre =array();
        if($exam_Data){
            
           foreach($exam_Data as $exams){
               
            $cond['paper_id'] = $exams['id'];  
            $cond['student_id'] =$id;  
            $result_data = $this->db_model->select_data('*', $table_name.' use index (id)',$cond,'',array('id','asc'),array('date',$like));
            if(!empty($result_data)){
                $count = "";
                foreach($result_data as $rkey=>$result){
    
                    $attemptedQuestion = json_decode($result['question_answer'],true);
                    if(!empty($result['question_answer'])){
                        $question_ids = implode(',',array_keys($attemptedQuestion));
                        if(!empty($question_ids)){
                            $right_ansrs = $this->db_model->select_data('id,answer', 'questions use index (id)','id in ('.$question_ids.')');
                        }else{
                            $right_ansrs = array();
                        }
                        
                        $rightCount = 0;
                        $wrongCount = 0;
                        $c = 0;
                        foreach($attemptedQuestion as $key=>$value){
                            $right_ansrs = $this->db_model->select_data('id,answer', 'questions use index (id)',array('id'=>$key));
                            if(($key == $right_ansrs[0]['id']) && ($value == $right_ansrs[0]['answer'])){
                                $rightCount++;
                            }else{
                                $wrongCount++;
                            }
                          
                        }
        
                        $percentage = (($rightCount - ($wrongCount*0.25))*100)/$result['total_question'];
        
                        $time_taken = '';
                        if($result['start_time']!="" || $result['submit_time']!=""){
                            $stime=strtotime($result['start_time']);
                            $etime=strtotime($result['submit_time']);
                            $elapsed = $etime - $stime;
                            $time_taken = gmdate("H:i", $elapsed);
                        }
                     
                        $dataarray_pre[] = array(
                            'id'=>$result['id'],
                            'paper_id'=>$exams['id'],
                            'paper_name'=>$result['paper_name'],
                            'date'=>date('d-m-Y',strtotime($result['date'])),
                            'start_time'=>date('h:i A',strtotime($result['start_time'])),
                            'submit_time'=>date('h:i A',strtotime($result['submit_time'])),
                            'total_question'=>$result['total_question'],
                            'time_duration'=>gmdate("H:i", $result['time_duration']*60),
                            'attempted_question'=>$result['attempted_question'],
                            'question_answer'=>json_encode($attemptedQuestion),
                            'percentage'=>number_format((float)$percentage, 2, '.', ''),
                            'added_on'=>$result['added_on']
                           
                        ); 
                        
                        $count++;
                    }
                }
            }
           }
           }
        $data['practice_result'] =$dataarray_pre;
        
        $table_name = 'mock_result';
        $cond1=array("admin_id"=>$this->session->userdata('admin_id'),'type'=>1);
        $exam_Data = $this->db_model->select_data('*', 'exams use index (id)',$cond1,'',array('id','asc'));
        
        $dataarray =array();
        if($exam_Data){
            
           foreach($exam_Data as $exams){
               
            $cond['paper_id'] = $exams['id'];  
            $cond['student_id'] =$id;  
            $result_data = $this->db_model->select_data('*', $table_name.' use index (id)',$cond,'',array('id','desc'),array('date',$like));
           
            if(!empty($result_data)){
                $count = "";
                foreach($result_data as $rkey=>$result){
    
                    $attemptedQuestion = json_decode($result['question_answer'],true);
                    if(!empty($result['question_answer'])){
                        $question_ids = implode(',',array_keys($attemptedQuestion));
                        if(!empty($question_ids)){
                            $right_ansrs = $this->db_model->select_data('id,answer', 'questions use index (id)','id in ('.$question_ids.')');
                        }else{
                            $right_ansrs = array();
                        }
                        
                        $rightCount = 0;
                        $wrongCount = 0;
                        $c = 0;
                        foreach($attemptedQuestion as $key=>$value){
                            $right_ansrs = $this->db_model->select_data('id,answer', 'questions use index (id)',array('id'=>$key));
                            if(($key == $right_ansrs[0]['id']) && ($value == $right_ansrs[0]['answer'])){
                                $rightCount++;
                            }else{
                                $wrongCount++;
                            }
                          
                        }
        
                        $percentage = (($rightCount - ($wrongCount*0.25))*100)/$result['total_question'];
        
                        $time_taken = '';
                        if($result['start_time']!="" || $result['submit_time']!=""){
                            $stime=strtotime($result['start_time']);
                            $etime=strtotime($result['submit_time']);
                            $elapsed = $etime - $stime;
                            $time_taken = gmdate("H:i", $elapsed);
                        }
                     
                        $dataarray[] = array(
                            'id'=>$result['id'],
                            'paper_id'=>$exams['id'],
                            'paper_name'=>$result['paper_name'],
                            'date'=>date('d-m-Y',strtotime($result['date'])),
                            'start_time'=>date('h:i A',strtotime($result['start_time'])),
                            'submit_time'=>date('h:i A',strtotime($result['submit_time'])),
                            'total_question'=>$result['total_question'],
                            'time_duration'=>gmdate("H:i", $result['time_duration']*60),
                            'attempted_question'=>$result['attempted_question'],
                            'question_answer'=>json_encode($attemptedQuestion),
                            'percentage'=>number_format((float)$percentage, 2, '.', ''),
                            'added_on'=>$result['added_on']
                           
                        ); 
                        
                        $count++;
                    }
                }
            }
           }
           }
        $data['mock_result'] =$dataarray;
		$data['student_data'] = $this->db_model->select_data('name,image,email,contact_no,admission_date,batch_id','students use index (id)',array('admin_id'=>$this->session->userdata('admin_id'),'id'=>$id));
		
		$data['practice_result_d'] = $this->db_model->select_data('total_question,question_answer,date,paper_name,percentage','practice_result',array('student_id'=>$id,'admin_id'=>$this->session->userdata('admin_id')),1);
	    $data['mock_result_d'] = $this->db_model->select_data('total_question,question_answer,date,paper_name,percentage','mock_result',array('student_id'=>$id,'admin_id'=>$this->session->userdata('admin_id')),1);
		$data['month'] = $month;
		$data['year'] = $year;

		$data['baseurl'] = base_url();
		$this->load->view("common/teacher_header",$header);
		$this->load->view("student/view_progress",$data); 
		$this->load->view("common/teacher_footer");
	}

	function academic_record(){
		$header['title']=$this->lang->line('ltr_academic_record');
		if(isset($_POST['filter_performance'])){
			$month = $_POST['month']; 
			$year = $_POST['year'];	
		}else{ 	
			$month = date('m');
			$year = date('Y');
		}
		$data['month'] = $month;
		$data['year'] = $year;
	 
		$like = $year.'-'.$month.'-';
		
		$data['homework'] = $this->db_model->countAll('homeworks',array('admin_id'=>$this->session->userdata('admin_id'),'teacher_id'=>$this->session->userdata('uid')),'','',array('date',$like));
		
		$data['extra_class'] = $this->db_model->countAll('extra_classes',array('admin_id'=>$this->session->userdata('admin_id'),'status'=>'Complete','teacher_id'=>$this->session->userdata('uid')),'','',array('date',$like));
		
		$data['video_lecture'] = $this->db_model->countAll('video_lectures',array('admin_id'=>$this->session->userdata('admin_id'),'added_by'=>$this->session->userdata('uid')),'','',array('added_at',$like));
		
		$this->load->view("common/teacher_header",$header);
		$this->load->view("teacher/academic_record",$data); 
		$this->load->view("common/teacher_footer");
	}

	function student_academic_record($id){
		$header['title']=$this->lang->line('ltr_student_academic_record');
		if(isset($_POST['filter_performance'])){
			$month = $_POST['month']; 
			$year = $_POST['year'];	
		}else{ 	
			$month = date('m');
			$year = date('Y');
		}
		$data['month'] = $month;
		$data['year'] = $year;
	
		$like = $year.'-'.$month.'-';
		
		$data['student_data'] = $this->db_model->select_data('name,image,email,contact_no,admission_date,batch_id','students use index (id)',array('admin_id'=>$this->session->userdata('admin_id'),'id'=>$id));
        $like_batch_id='"'.$data['student_data'][0]['batch_id'].'"';
		$data['extra_class'] = $this->db_model->countAll('extra_class_attendance',array('student_id'=>$id),'','',array('date',$like));
		$data['total_extra_class'] = $this->db_model->countAll('extra_classes','',array('batch_id'=>$like_batch_id),'',array('date',$like));
		
		$data['homework'] = $this->db_model->countAll('homeworks',array('admin_id'=>$this->session->userdata('admin_id'),'batch_id'=>$data['student_data'][0]['batch_id']),'','',array('date',$like));
		
		$data['practice_result'] = $this->db_model->custom_slect_query(" COUNT(*) AS `numrows` FROM ( SELECT practice_result.id FROM `practice_result` JOIN `exams` ON `exams`.`id`=`practice_result`.`paper_id` WHERE `practice_result`.`admin_id` = '".$this->session->userdata('admin_id')."' AND `student_id` = '".$id."' AND date(added_at) LIKE '%".$like."%' ESCAPE '!' GROUP BY `paper_id` ) a")[0]['numrows'];
		
		$data['total_practice_test'] = $this->db_model->countAll('exams',array('admin_id'=>$this->session->userdata('admin_id'),'batch_id'=>$data['student_data'][0]['batch_id'],'type'=>2),'','',array('date(added_at)',$like));
		
		$data['mock_result'] = $this->db_model->countAll('mock_result',array('admin_id'=>$this->session->userdata('admin_id'),'student_id'=>$id),'','',array('date',$like));
		
		$data['total_mock_test'] = $this->db_model->countAll('exams',array('admin_id'=>$this->session->userdata('admin_id'),'batch_id'=>$data['student_data'][0]['batch_id'],'type'=>1),'','',array('date(added_at)',$like));
		
		$this->load->view("common/teacher_header",$header);
		$this->load->view("student/academic_record",$data); 
		$this->load->view("common/teacher_footer");
	}

	function create_exam(){
		$header['title']= $this->lang->line('ltr_create_paper');
		$admin_id = $this->session->userdata('admin_id');
		$subject_ids = $this->session->userdata('subject_id');
		$subCon = "admin_id = $admin_id AND id in ($subject_ids)";
		$data['subject'] = $this->db_model->select_data('id,subject_name,no_of_questions','subjects use index (id)',$subCon,'',array('id','desc'));
		$batch_id = $this->session->userdata('batch_id');
		if(!empty($batch_id)){
			$batchCon = "admin_id = $admin_id AND id in ($batch_id)";
			$data['batch'] = $this->db_model->select_data('id,batch_name','batches use index (id)',$batchCon,'',array('id','desc'));
		}else{
			$data['batch'] = '';
		}
		$data['question_data'] = $this->db_model->countAll('questions',array('admin_id'=>$this->session->userdata('admin_id')));
		$this->load->view("common/teacher_header",$header);
		$this->load->view("admin/create_exam",$data); 
		$this->load->view("common/teacher_footer");
	}
    function student_attendance($id){
    // print_r($_SESSION);
    // die;
		if(isset($_POST['filter_performance'])){
			$month = $_POST['month']; 
			$year = $_POST['year'];	
		}else{ 	
			$month = date('m');
			$year = date('Y');
		}
		$header['title']=$this->lang->line('ltr_student_attendance');
		$data['month'] = $month;
		$data['year'] = $year;
		$data['student_id'] = $id;
		$data['attendance'] = $this->db_model->select_data('id','attendance',array('admin_id'=>$this->session->userdata('admin_id')),1);
	 
		$data['baseurl'] = base_url();
		$this->load->view("common/teacher_header",$header);
		$this->load->view("student/student_attendance",$data); 
		$this->load->view("common/teacher_footer");
	}
	function student_attendance_extra_class($id){
		
		if(isset($_POST['filter_performance'])){
			$month = $_POST['month']; 
			$year = $_POST['year'];	
		}else{ 	
			$month = date('m');
			$year = date('Y');
		}
		$header['title']=$this->lang->line('ltr_extra_class_attendance');
		$data['month'] = $month;
		$data['year'] = $year;
		$data['student_id'] = $id;
		$data['baseurl'] = base_url();
		$this->load->view("common/teacher_header",$header);
		$this->load->view("student/student_attendance_extra_class",$data); 
		$this->load->view("common/teacher_footer");
	}
	
	function student_doubts_class(){
		
	
		$header['title']=$this->lang->line('ltr_doubts_class');
		$admin_id = $this->session->userdata('admin_id');
// 		print_r($_SESSION);
// 		die();
		$subject_ids = $this->session->userdata('subject_id');
		$subCon = "admin_id = $admin_id AND id in ($subject_ids)";
		$data['subject'] = $this->db_model->select_data('id,subject_name','subjects use index (id)',$subCon,'',array('id','desc'));
		$data['doubts_class_data'] = $this->db_model->select_data('doubt_id','student_doubts_class',array('teacher_id'=>$this->session->userdata('uid')),1);
		$this->load->view("common/teacher_header",$header);
		$this->load->view("teacher/doubts_class",$data); 
		$this->load->view("common/teacher_footer");
	}
	function doubts_ask($id){
	    $header['title']=$this->lang->line('ltr_student_doubts_ask');
		$data['doubts_class_data'] = $this->db_model->select_data('doubt_id','student_doubts_class',array('student_id'=>$id),1);
		$data['id'] = $id;

		$this->load->view("common/teacher_header",$header);
		$this->load->view("student/doubts_ask",$data); 
		$this->load->view("common/teacher_footer");
	}
	function add_question($id=0){
		$header['title']=$this->lang->line('ltr_question_manager');
		$admin_id = $this->session->userdata('admin_id');
		
		$subject_ids = $this->session->userdata('subject_id');
		$subCon = "admin_id = $admin_id AND id in ($subject_ids)";
		$data['subject'] = $this->db_model->select_data('id,subject_name','subjects use index (id)',$subCon,'',array('id','desc'));
		$data['question_data'] = $this->db_model->countAll('questions',array('added_by'=>$this->session->userdata('uid')));
		if($id>0){
			$data['single_question'] = $this->db_model->select_data('*','questions',array('added_by'=>$this->session->userdata('uid'),'id'=>$id))[0];
		}
		//print_r($data['single_question']);
		$this->load->view("common/teacher_header",$header);
		$this->load->view("admin/add_question",$data); 
		$this->load->view("common/teacher_footer");
	}
	
	//new update
	
	function book_manage(){
	    $header['title']=$this->lang->line('ltr_library_manager');
		$admin_id = $this->session->userdata('admin_id');
		
		$subject_ids = $this->session->userdata('subject_id');
		$subCon = "admin_id = $admin_id AND id in ($subject_ids)";
		$data['subject'] = $this->db_model->select_data('id,subject_name','subjects use index (id)',$subCon,'',array('id','desc'));
    		$batch_ids = $this->session->userdata('batch_id');
		if(!empty($batch_ids)){
			$batCon = "admin_id = $admin_id AND id in ($batch_ids)";
			$data['batch'] = $this->db_model->select_data('id,batch_name','batches  use index (id)',$batCon,'',array('id','desc'));
		}else{
			$data['batch'] = '';
		}
     	$data['book_data'] = $this->db_model->select_data('id','book_pdf use index (id)',array('admin_id'=>$admin_id,'added_by'=>$this->session->userdata('uid')));
		$this->load->view("common/teacher_header",$header);
		$this->load->view("admin/book_manage",$data); 
		$this->load->view("common/teacher_footer");
	}
	function notes_manage(){
		$header['title']=$this->lang->line('ltr_notes_manage');
		$admin_id = $this->session->userdata('admin_id');
		
		$subject_ids = $this->session->userdata('subject_id');
		$subCon = "admin_id = $admin_id AND id in ($subject_ids)";
		$data['subject'] = $this->db_model->select_data('id,subject_name','subjects use index (id)',$subCon,'',array('id','desc'));
		$batch_ids = $this->session->userdata('batch_id');
		if(!empty($batch_ids)){
			$batCon = "admin_id = $admin_id AND id in ($batch_ids)";
			$data['batch'] = $this->db_model->select_data('id,batch_name','batches  use index (id)',$batCon,'',array('id','desc'));
		}else{
			$data['batch'] = '';
		}
		$data['notes_data'] = $this->db_model->select_data('id','notes_pdf use index (id)',array('admin_id'=>$admin_id,'added_by'=>$this->session->userdata('uid')));
		$this->load->view("common/teacher_header",$header);
		$this->load->view("admin/notes_manage",$data); 
		$this->load->view("common/teacher_footer");
	}
	
	function file_view($type='',$id=''){
		
		$header['title']=$this->lang->line('ltr_file_view');
        if($type=="library"){
			$book_data =$this->db_model->select_data('*','library_books',array('id'=>$id,'status'=>1),'',array('id','desc'));
			if(!empty($book_data)){
			$data['file_name']= base_url('/uploads/library/').$book_data[0]['file_name'];
			}
		}elseif($type=="book"){
			$book_data = $this->db_model->select_data('*','book_pdf',array('id'=>$id,'status'=>1),'',array('id','desc'));
			if(!empty($book_data)){
			$data['file_name']= base_url('/uploads/book/').$book_data[0]['file_name'];
			}
		}elseif($type=="notes"){
			$book_data= $this->db_model->select_data('*','notes_pdf',array('id'=>$id,'status'=>1),'',array('id','desc'));
			if(!empty($book_data)){
			$data['file_name']= base_url('/uploads/notes/').$book_data[0]['file_name'];
			}
		}elseif($type=="old_paper"){
			$book_data= $this->db_model->select_data('*','old_paper_pdf',array('id'=>$id,'status'=>1),'',array('id','desc'));
			if(!empty($book_data)){
			$data['file_name']= base_url('/uploads/oldpaper/').$book_data[0]['file_name'];
			}
		}
		
		$this->load->view("common/teacher_header",$header);
		$this->load->view("admin/pdf_view_file",$data); 
		$this->load->view("common/teacher_footer");

	}
	
	function old_paper(){
		    
	    $header['title']=$this->lang->line('ltr_old_paper');
        $data['subject'] = $this->db_model->select_data('id,subject_name','subjects use index (id)',array('admin_id'=>$this->session->userdata('uid')),'',array('id','desc'));
        // $batch_id = current($this->db_model->select_data('teach_batch','users use index (id)',array('status' => '1'),'',array('id','desc'),array('teach_batch', $this->session->userdata('batch_id'))));
        // $batch_data = implode(',',$batch_id);
        $batch_id = $this->session->userdata('batch_id');
        	$Con = "status = 1 AND id in ($batch_id)";
        $data['batch'] = $this->db_model->select_data('id,batch_name','batches use index (id)',$Con,'',array('id','desc'));
    //   echo $this->db->last_query();
        // $like = array('batch','"'.$this->session->userdata('batch_id').'"');
        $data['notes_data'] = $this->db_model->select_data('*','old_paper_pdf use index (id)',array('status'=>1 ),'',array('id','desc'));
		$this->load->view("common/teacher_header",$header);
		$this->load->view("admin/old_paper_manage",$data); 
		$this->load->view("common/teacher_footer");
	}
	
		function manage_student_leave(){
	    $header['title']=$this->lang->line('ltr_manage_student_leave');
	    $data['page'] = 'student';
	    $data['student_leave'] = $this->db_model->countAll('leave_management',array('student_id !='=>0));
	   // $data['student_leave_c']=1;
	  
	    $this->load->view("common/teacher_header",$header);
		$this->load->view("teacher/student_leave",$data); 
		$this->load->view("common/teacher_footer");
	}
}
