<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Exam extends CI_Controller {

    function __construct()
    {
         error_reporting(0);
        parent::__construct();
        $timezoneDB = $this->db_model->select_data('timezone','site_details',array('id'=>1));
        if(isset($timezoneDB[0]['timezone']) && !empty($timezoneDB[0]['timezone'])){
            date_default_timezone_set($timezoneDB[0]['timezone']);
        }
		
		// check select language
		$this->load->helper('language');
		$language = $this->general_settings('language_name');
		if($language=="french"){
			$this->lang->load('french_lang', 'french');
		}else if($language=="arabic"){
			$this->lang->load('arabic_lang', 'arabic');
		}else{
			$this->lang->load('english_lang', 'english');
		}
    }
 
    function general_settings($key_text=''){
        $data = $this->db_model->select_data('*','general_settings',array('key_text'=>$key_text),1);
        return $data[0]['velue_text'];
    }
    function exam_paper(){
        $data = $_REQUEST;
      
        if(isset($data['exam_type']) && isset($data['batch_id']) && isset($data['admin_id']) && isset($data['student_id'])){
            $exam_type = $data['exam_type'];
            $batch_id = $data['batch_id'];
            $admin_id = $data['admin_id'];
            $student_id = $data['student_id'];
            
            if($exam_type == 'practice'){
                if(!empty($data['student_id'])){
                $this->viewNotificationStatus($data['student_id'],'practicePaper');
                 }
                
                $examPaper = $this->db_model->select_data('id,name,time_duration as timeDuration,total_question as totalQuestion,"practice" AS examType,total_marks as Total,marking_parcent as negetive','exams use index (id)',array('type'=>2,'status'=>1,'batch_id'=>$batch_id),'',array('id','desc'));
                if(!empty($examPaper)){
                    $arr = array(
                        'status'=>'true',
                        'msg'=>$this->lang->line('ltr_record_fetch'),
                        'examPaper'=>$examPaper,
                        'mockInfo'=>'false'
                    );
                }else{
                    $arr 	=	array(
                        'status'=>'false',
                        'msg'=>$this->lang->line('ltr_no_record_msg'),
                    );
                }
            }else if($exam_type == 'mock_test'){  
                
                if(!empty($data['student_id'])){
                $this->viewNotificationStatus($data['student_id'],'mockPaper');
                 }
                $str_date =	strtotime(date("d-M-Y"));
                $datesss = date('Y-m-d');
                $dateTimeee = date('Y-m-d H:i:s');  
                
                $examPaper = $this->db_model->select_data('id,name,time_duration as timeDuration,total_question as totalQuestion,mock_sheduled_date as mockSheduledDate,mock_sheduled_time as mockSheduledTime,total_marks as Total,marking_parcent as negetive','exams use index (id)',array('type' => 1,'status' => 1,'batch_id' => $batch_id,'mock_sheduled_date >=' => date('Y-m-d')),'',array('id','desc'));
              
                $newexampaper = array();          
                if(!empty($examPaper)){
                    foreach($examPaper as $exam){
                        $scheduled_time = $exam['mockSheduledDate'].' '.$exam['mockSheduledTime'];
                        $minutes_to_add = $exam['timeDuration'];
                        $time = new DateTime($scheduled_time);
                        $time->add(new DateInterval('PT' . $minutes_to_add . 'M'));
                        $stamp = $time->format('Y-m-d H:i');
                            
                        if($stamp >= date('Y-m-d H:i')){

                            $paperstatus = $this->db_model->select_data('id','mock_result use index (id)',array('admin_id' => $admin_id,'student_id' => $student_id,'paper_id' => $exam['id']),1);
                            
              
                            $exam['currentTime'] = date('Y-m-d H:i:s');
                            $exam['examType'] = "mock_test";
                            if(empty($paperstatus)){
                                array_push($newexampaper,$exam); 
                            }
                        }
                    }

                    if(!empty($newexampaper)){
                        $arr = array(
                            'status' => 'true',
                            'msg' => $this->lang->line('ltr_record_fetch'),
                            'examPaper' => $newexampaper,
                            'mockInfo' => 'true',
                            'serverTime' => date('Y-m-d H:i:s'),
                        );
                    }else{
                        $arr = array(
                            'status'=>'false'
                        );
                    }
                }else{
                    $arr = array(
                        'status'=>'false',
                        'msg'=>$this->lang->line('ltr_no_record_msg'),
                    );
                }
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg'),
            );
        }
        echo json_encode($arr);
    }

    function get_exam_paper(){
        $data = $_REQUEST;
            if(isset($data['paper_id']) && isset($data['admin_id'])){
            $paper_id = $data['paper_id'];
            $admin_id = $data['admin_id'];
            $paperDetails = $this->db_model->select_data('id,admin_id as adminId, name,type,format,batch_id as batchId,total_question as totalQuestion, time_duration as timeDuration,question_ids as questionIds,mock_sheduled_date as mockSheduledDate,mock_sheduled_time as mockSheduledTime,status,added_by as addedBy,added_at as addedAt','exams use index (id)',array('id'=>$paper_id),1);
            if(!empty($paperDetails)){
                $time =$paperDetails[0]['timeDuration'];
                if((strtotime($paperDetails[0]['mockSheduledDate'])<=strtotime(date('Y-m-d'))) && $paperDetails[0]['type']==1 && (strtotime(date('H:i:s')) >= strtotime('+'.$time. 'minutes', strtotime($paperDetails[0]['mockSheduledTime'])))){
                    $arr = array(
                            'status'=>'false',
                            'msg'=>$this->lang->line('ltr_exam_over'),
                            );
                    echo json_encode($arr);
                    die();
                }
                $questions = json_decode($paperDetails[0]['questionIds'],true);
                if(!empty($questions)){
                    $question_id = implode(',',$questions);
                    $questionData = $this->db_model->select_data('id,question,options,answer','questions use index (id)',"id in ($question_id)",'',array('id','desc'));
                    if($paperDetails[0]['format'] == 1){
                    $questionData = $this->shuffle_array($questionData);
                    }
                    if($paperDetails[0]['totalQuestion'] != count($questionData)){
                        $this->db_model->update_data_limit('exams',array('total_question' => count($questionData)),array('id'=>$paper_id),1);
                        $paperDetails[0]['totalQuestion'] = count($questionData);
                        
                    }

                    $paperDetails[0]['questionDetails'] = array_values($questionData);
                    $arr = array(
                        'status'=>'true',
                        'currentTime' => date('h:i:s A'),
                        'msg'=>$this->lang->line('ltr_question_fetch'),
                        'totalExamData'=>$paperDetails
                    );
                }else{
                    $arr =	array(
                        'status'=>'true',
                        'msg'=>$this->lang->line('ltr_question_no')
                    );
                }
            }else{
                $arr =	array(
                        'status'=>'true',
                        'msg'=>$this->lang->line('ltr_no_record_msg')
                    );
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg'),
            );
        }
		echo json_encode($arr);
    }
    
    function shuffle_array($array) {
        $keys = array_keys($array);

        shuffle($keys);

        foreach($keys as $key) {
            $new[$key] = $array[$key];
        }

        return $new;
    }

    function exam_result(){
        $data = $_REQUEST;
        if(isset($data['exam_type']) && isset($data['admin_id']) && isset($data['student_id']) && isset($data['batch_id'])){
            $exam_type = $data['exam_type'];
            $admin_id = $data['admin_id'];
            $student_id = $data['student_id'];
            $batch_id = $data['batch_id'];
            $currnt_mnth                 =   date('m');
            $currnt_yrs                  =   date('y');
             if($exam_type == 'practice'){
                $table = 'practice_result';
             }else{
                $table = 'mock_result';  
             }
            if(isset($data['month']) && isset($data['year']) && !empty($data['month']) && !empty($data['year'])){
                $year = $data['year'];
                $month = $data['month'];  
                $datefiltr = $year.'-'.$month;
                $like = array($table.'.date',$datefiltr); 
                
            }else{
                $datefiltr = $currnt_yrs.'-'.$currnt_mnth;
                $like = array($table.'.date',$datefiltr);  
            }

            if($exam_type == 'practice'){
                $like1 = array('date(added_at)',$datefiltr);  
                $new_condition= array('type'=>2,'batch_id'=>$batch_id);
            }else{
                $like1 = array('mock_sheduled_date',$datefiltr); 
                $adm_id = $this->db_model->select_data('admin_id', 'exams use index (id)',array('type'=>1,'batch_id'=>$data['batch_id']));
         
                $new_condition=array("admin_id"=>$adm_id[0]['admin_id'],'type'=>1,'concat(mock_sheduled_date ," ",mock_sheduled_time)<=' => date('Y-m-d H:i:s'),'batch_id'=>$batch_id);
            } 
           $exam_Data = $this->db_model->select_data('*', 'exams use index (id)',$new_condition,'',array('id','ASC'),$like1);
     
           if(!empty($exam_Data)){
           foreach($exam_Data as $exams){
            $result_data = $this->db_model->select_data($table.'.*,exams.total_marks as paperTotalMarks,exams.marking_parcent', $table.' use index (id)',array($table.'.student_id'=>$student_id,$table.'.paper_id'=>$exams['id']),'',array('id','desc'),$like,array('exams','exams.id='.$table.'.paper_id'));
            if(!empty($result_data)){
                foreach($result_data as $result){
                    $attemptedQuestion = json_decode($result['question_answer'],true);
                    $rightCount = 0;
                    $wrongCount = 0;
                   
                        foreach($attemptedQuestion as $key=>$value){
                            $right_ansrs = $this->db_model->select_data('id,answer', 'questions use index (id)',array('id'=>$key));
                            if(($key == $right_ansrs[0]['id']) && ($value == $right_ansrs[0]['answer'])){
                                $rightCount++;
                            }else{
                                $wrongCount++;
                            }
                          
                        }
                  
                   if($result_data[0]['marking_parcent']==0){
                     $total_score = $rightCount;
                    }else if($result_data[0]['marking_parcent']!=0){
                     $per = $wrongCount*$result_data[0]['marking_parcent'];
                     $total_score = ($rightCount -$per);
                    }
                    $time_taken = '';
                    if($result['start_time']!="" || $result['submit_time']!=""){
                        $stime=strtotime($result['start_time']);
                        $etime=strtotime($result['submit_time']);
                        $elapsed = $etime - $stime;
                        $time_taken = gmdate("H:i:s", $elapsed);
                    }
                   
                    $dataarray[] = array(
                        'resultId' => $result['id'],
                        'paperId' => $result['paper_id'],
                        'paperName' => $result['paper_name'],
                        'date' => date('d-m-Y',strtotime($result['date'])),
                        'startTime' => date('h:i A',strtotime($result['start_time'])),
                        'submitTime' => date('h:i A',strtotime($result['submit_time'])),
                        'totalQuestion' => $result['total_question'],
                        'attemptedQuestion' => $result['attempted_question'],
                        'totalTime' => gmdate("H:i", $result['time_duration']*60),
                        'timeTaken' => $time_taken,
                        'rightAns' => $rightCount,
                        'percentage' => $result['percentage'],
                        'totalScore'=>$total_score,
                        'scheduleTime' => date('h:i A',strtotime($exams['mock_sheduled_time'])),
                        'paperTotalMarks'=>$result['paperTotalMarks'],
                        'paperNegetiveMarks'=>$result['marking_parcent'],
                    ); 
					
                }
 
                $arr = array(
                    'status'=>'true',
                    'msg'=>$this->lang->line('ltr_record_fetch'),
                    'resultData'=>$dataarray
                );
		  //  echo json_encode($arr);
            }else{
                if($exam_type == 'practice'){
                   $dataarray=array(); 
                }else{
                    //$mock_dayTime = strtotime($exams['mock_sheduled_date']." ".$exams['mock_sheduled_time']);
                    $mock_dayTime = strtotime($exams['mock_sheduled_date']." ".$exams['mock_sheduled_time'])+(60*$exams['time_duration']);
                  
                     $curentDayTime =strtotime(date('Y-m-d H:i:s'));
                    if($mock_dayTime>=$curentDayTime){
                        $dataarray=array(); 
                    }else{ 
                        $dataarray[] = array(
                                'resultId' => '0', 
                                'paperId' => $exams['id'],
                                'paperName' =>$exams['name'],
                                'date' => ($exams['type']==1)?date('d-m-Y',strtotime($exams['mock_sheduled_date'])):date('d-m-Y',strtotime($exams['added_at'])),
                                'startTime' => '',
                                'submitTime' => '',
                                'totalQuestion' => $exams['total_question'],
                                'attemptedQuestion' => '0',
                                'totalTime' =>'0',
                                'timeTaken' =>'0',
                                'rightAns' => 0,
                                'percentage' => '0',
                                'totalScore'=>0,
                                'scheduleTime' => date('h:i A',strtotime($exams['mock_sheduled_time'])),
                                'paperTotalMarks'=>$result['paperTotalMarks'],
                                'paperNegetiveMarks'=>$result['marking_parcent'],
                            ); 
                    }
                }
                $arr = array(
                    'status'=>'true',
                    'msg'=>$this->lang->line('ltr_record_fetch'),
                    'resultData'=>$dataarray
                );
                // echo json_encode($arr);
            }
           }
           }else{
                    $arr=	array(
                        'status'=>'false',
                        'msg'=>$this->lang->line('ltr_no_record_msg'),
                    );
                // echo json_encode($arr);
           }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg'),
            );
            
        }
         echo json_encode($arr);
        
    }
    
    function view_ans_sheet(){
        $data = $_REQUEST;
        if(isset($data['exam_type']) && isset($data['result_id'])){
            $paper_type = $data['exam_type'];
            $result_id = $data['result_id'];
            if($paper_type == 'practice'){
                $type = 2;
                $table = 'practice_result';
            }else{
                $type = 1;
                $table = 'mock_result';
            }
             
            $result_details = $this->db_model->select_data("$table.id,$table.admin_id as adminId,$table.student_id as studentId,$table.paper_id as paperId,$table.paper_name as paperName,$table.date,$table.start_time as startTime,$table.submit_time as submitTime,$table.total_question as totalQuestion,$table.time_duration as timeDuration,$table.attempted_question as attemptedQuestion, $table.question_answer as questionAnswer,$table.percentage, $table.added_on as addedOn,exams.question_ids as questionIds",$table.' use index (id)',array("$table.id"=>$result_id),1,'','',array('exams',"exams.id = $table.paper_id",'LEFT'));
           //echo $this->db->last_query();
            if(!empty($result_details)){
                $results = $result_details[0];
                $allQuestionArr = json_decode($results['questionIds'],true);
                $allQuestions = implode(',',$allQuestionArr);
                $questionLists = $this->db_model->select_data('id,question,answer,options', 'questions use index (id)','id in ('.$allQuestions.')');
                $attemptedQuestion = json_decode($results['questionAnswer'],true);
                $results['allQuestions'] = $questionLists;
                $i = 0;
                foreach($results['allQuestions'] as $quest){
                    $ansCheck = array($quest['id']=>$quest['answer']);
                    $optionArr = json_decode($quest['options'],true);
                    if(!array_key_exists($quest['id'],$attemptedQuestion)){
                        $results['allQuestions'][$i]['studentAnswer'] = '';
                        $results['allQuestions'][$i]['rightAnswer'] = 'notattempt';
                    }else if($attemptedQuestion[$quest['id']] != $quest['answer']){
                        $results['allQuestions'][$i]['studentAnswer'] = $attemptedQuestion[$quest['id']];
                        $results['allQuestions'][$i]['rightAnswer'] = 'wrong';
                    }else{
                        $results['allQuestions'][$i]['studentAnswer'] = $attemptedQuestion[$quest['id']];
                        $results['allQuestions'][$i]['rightAnswer'] = 'right';
                    }
                    $i++;
                }
               
                $arr = array(
                    'status'=>'true',
                    'resultData'=>$results,
                    'msg'=>$this->lang->line('ltr_record_fetch'),
                );
            }else{
                $result_details = $this->db_model->select_data("*",'exams use index (id)',array("id"=>$data['paper_id']),1,'','');
                $results['id']=0;
                $results['adminId']=$result_details[0]['admin_id'];
                $results['studentId']=0;
                $results['paperId']=$result_details[0]['id'];
                $results['paperName']=$result_details[0]['name'];
                $results['date']='';
                $results['startTime']=0;
                $results['submitTime']=0;
                $results['totalQuestion']=$result_details[0]['total_question'];
                $results['timeDuration']=0;
                $results['attemptedQuestion']=0;
                $results['questionAnswer']="";
                $results['percentage']=0;
                $results['addedOn']=$result_details[0]['added_at'];
                $results['questionIds']=$result_details[0]['question_ids'];
                $allQuestionArr = json_decode($results['questionIds'],true);
                $allQuestions = implode(',',$allQuestionArr);
                $questionLists = $this->db_model->select_data('id,question,answer,options,"notattempt" as rightAnswer,"" as studentAnswer', 'questions use index (id)','id in ('.$allQuestions.')');
                $attemptedQuestion = json_decode($results['questionAnswer'],true);
                $results['allQuestions'] = $questionLists;
                $arr = array(
                    'status'=>'true',
                    'resultData'=>$results,
                    'msg'=>$this->lang->line('ltr_record_fetch'),
                );
            } 
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg'),
            );
        }
        echo json_encode($arr);
    }

    // function submit_paper(){
    //     $data = $_REQUEST;

    //     if(isset($data['exam_type']) && isset($data['admin_id']) && isset($data['student_id']) && isset($data['question_answer']) && isset($data['paper_id']) && isset($data['paper_name']) && isset($data['total_question']) && isset($data['start_time']) && isset($data['time_duration'])){
    //       $this->db_model->insert_data('temp_data',array('temp'=>json_encode($data)));
    //         $paper_type = $data['exam_type'];
    //         $admin_id = $data['admin_id'];
    //         $student_id = $data['student_id'];
    //         $date = date('Y-m-d');
    //         $question_answer = $data['question_answer'];
    //         $attempted_question = count(json_decode($data['question_answer'],true));
            
    //         $paper_id = $data['paper_id'];
    //         $paper_name = $data['paper_name'];
    //         $total_question = $data['total_question'];
    //         $start_time = date('H:i', strtotime($data['start_time']));
    //         $submit_time = date('H:i');
    //         $time_duration = $data['time_duration'];
    //         $attemptedQuestion = json_decode($question_answer,true);
    //                 $rightCount = 0;
    //                 $wrongCount = 0;
    //                 $c = 0;
    //                     foreach($attemptedQuestion as $key=>$value){
    //                         $right_ansrs = $this->db_model->select_data('id,answer', 'questions use index (id)',array('id'=>$key));
    //                         if(($key == $right_ansrs[0]['id']) && ($value == $right_ansrs[0]['answer'])){
    //                             $rightCount++;
    //                         }else{
    //                             $wrongCount++;
    //                         }
    //                         $c++;
    //                     }
                  
        
    //                 $total_score = ($rightCount - ($wrongCount*0.25));
    //                 $percData = ($total_score*100)/$total_question;
    //                 if($percData < 0){
				// 		$percentage = 0;
				// 	}else{
				// 		$percentage = $percData;
				// 	}
    //             $adm_id = $this->db_model->select_data('admin_id,id','exams use index (id)',array('name'=>$data['paper_name']));
                  
    //         $data_arr =	array(
    //             'admin_id'=>$adm_id[0]['admin_id'],
    //             'student_id'=>$student_id,
    //             'paper_id'=> $adm_id[0]['id'],
    //             'paper_name'=>$paper_name,
    //             'date'=>$date,
    //             'start_time'=>$start_time,
    //             'submit_time'=>$submit_time,
    //             'total_question'=>$total_question,
    //             'time_duration'=>$time_duration,
    //             'attempted_question'=>$attempted_question, 
    //             'question_answer'=>$question_answer,
    //             'percentage'=>number_format($percentage, 2, '.', '')
    //         );
    
    //         if($paper_type=='practice'){
    //             $table = 'practice_result';
    //             $prevdata = array();
    //         }else{
    //             $table = 'mock_result';
    //             $prevdata = $this->db_model->select_data('id','mock_result use index (id)',array('student_id'=>$student_id,'admin_id'=>$admin_id,'paper_id'=>$paper_id),1);
    //         }
   
    //         if(empty($prevdata)){
    //             $ins = $this->db_model->insert_data($table,$data_arr);
    //             if(!empty($ins)){
    //                 $time_taken = '';
    //                 if($start_time!="" || $submit_time!=""){
    //                     $stime=strtotime($start_time);
    //                     $etime=strtotime($submit_time);
    //                     $elapsed = $etime - $stime;
    //                     $time_taken = gmdate("H:i", $elapsed);
    //                 } 
    
    //                 $arr = array(
    //                     'status'=>'true',
    //                     'msg'=>$this->lang->line('ltr_paper_submitted'),
    //                     'totalQuestion'=>$total_question,
    //                     'totalAttempt'=>$attempted_question,
    //                     'attemptQuestion'=>$attempted_question,
    //                     'correctAns'=>$rightCount,
    //                     'wrongAns'=>$wrongCount,
    //                     'totalScore'=>number_format($total_score, 2, '.', ''),
    //                     'percentage'=>number_format($percentage, 2, '.', ''),
    //                     'startTime'=>$start_time,
    //                     'submitTime'=>$submit_time,
    //                     'date'=>$date,
    //                     'timeTaken'=>$time_taken
    //                 );
    //             }else{
    //                 $arr = array(
    //                     'status'=>'false'
    //                 );
    //             }
    //         }else{
    //             $arr = array('status'=>'false','msg'=>$this->lang->line('ltr_paper_this_test'));
    //         }
    //     }else{
    //         $arr = array(
    //             'status'=>'false',
    //             'msg'=>$this->lang->line('ltr_missing_parameters_msg'),
    //         );
    //     }
    //     echo json_encode($arr);
    // }
        function submit_paper(){
        $data = $_REQUEST;

        if(isset($data['exam_type']) && isset($data['admin_id']) && isset($data['student_id']) && isset($data['question_answer']) && isset($data['paper_id']) && isset($data['paper_name']) && isset($data['total_question']) && isset($data['start_time']) && isset($data['time_duration'])){
           $this->db_model->insert_data('temp_data',array('temp'=>json_encode($data)));
            $paper_type = $data['exam_type'];
            $admin_id = $data['admin_id'];
            $student_id = $data['student_id'];
            $date = date('Y-m-d');
            $question_answer = $data['question_answer'];
            $attempted_question = count(json_decode($data['question_answer'],true));
            
            $paper_id = $data['paper_id'];
            $paper_name = $data['paper_name'];
            $total_question = $data['total_question'];
            $start_time = date('H:i:s', strtotime($data['start_time']));
            $submit_time = date('H:i:s');
            $time_duration = $data['time_duration'];
            $adm_id = $this->db_model->select_data('*','exams use index (id)',array('name'=>$data['paper_name']));
        
            $attemptedQuestion = json_decode($question_answer,true);
            
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
                            $c++;
                        }
                        
             
                if($adm_id[0]['marking_parcent']==0){
                    $total_score = ($rightCount )/$adm_id[0]['total_question'];
                }else if($adm_id[0]['marking_parcent']!=0){
                    $per = $wrongCount*$adm_id[0]['marking_parcent'];
                    $total_score = ($rightCount -$per)/$adm_id[0]['total_question'];
                }
          
          
                 $percent = $total_score*100;
                 if($percent<0){
                     $percentage = 0;
                 }else{
                     $percentage =$percent;
                 }
         
                $data_arr =	array(
                    // 'admin_id'=>$admin_id,
                    'admin_id'=>$adm_id[0]['admin_id'],
                    'student_id'=>$student_id,
                    'paper_id'=>$paper_id,
                    'paper_name'=>$paper_name,
                    'date'=>$date,
                    'start_time'=>$start_time,
                    'submit_time'=>$submit_time,
                    'total_question'=>$total_question,
                    'time_duration'=>$time_duration,
                    'attempted_question'=>$attempted_question, 
                    'question_answer'=>$question_answer,
                    'percentage'=>number_format($percentage, 2, '.', '')
                );
      
            if($paper_type=='practice'){
                $table = 'practice_result';
                $prevdata = array();
            }else{
                $table = 'mock_result';
                $prevdata = $this->db_model->select_data('id','mock_result use index (id)',array('student_id'=>$student_id,'admin_id'=>$admin_id,'paper_id'=>$paper_id),1);
            }
   
            if(empty($prevdata)){
               
                $ins = $this->db_model->insert_data($table,$data_arr);
                if(!empty($ins)){
                    $time_taken = '';
                    if($start_time!="" || $submit_time!=""){
                        $stime=strtotime($start_time);
                        $etime=strtotime($submit_time);
                        $elapsed = $etime - $stime;
                        $time_taken = gmdate("H:i:s", $elapsed);
                    } 
    
                    $arr = array(
                        'status'=>'true',
                        'msg'=>$this->lang->line('ltr_paper_submitted'),
                        'totalQuestion'=>$total_question,
                        'totalAttempt'=>$attempted_question,
                        'attemptQuestion'=>$attempted_question,
                        'correctAns'=>$rightCount,
                        'wrongAns'=>$wrongCount,
                        'totalScore'=>number_format($total_score, 2, '.', ''),
                        'percentage'=>number_format($percentage, 2, '.', ''),
                        'startTime'=>$start_time,
                        'submitTime'=>$submit_time,
                        'date'=>$date,
                        'timeTaken'=>$time_taken,
                        'paperTotalMarks'=>$adm_id[0]['total_marks'],
                        'paperNegetiveMarks'=>$adm_id[0]['marking_parcent'],
                        'paperTotalMarks'=>$adm_id[0]['total_marks'],
                        'paperNegetiveMarks'=>$adm_id[0]['marking_parcent']
                    );
                }else{
                    $arr = array(
                        'status'=>'false'
                    );
                }
            }else{
                $arr = array('status'=>'false','msg'=>$this->lang->line('ltr_paper_this_test'));
            }
        }else{
            $arr = array(
                'status'=>'false',
                'msg'=>$this->lang->line('ltr_missing_parameters_msg'),
            );
        }
        echo json_encode($arr);
    }
    function viewNotificationStatus($student_id='',$notice_type=''){
            $data = $_REQUEST;
            if(!empty($student_id) && !empty($notice_type)){
                
               
                 $data_arr=array();
                 $cu_date = date('Y-m-d h:i:s');
                 $data_arr['student_id']=$student_id;
                 $data_arr['notice_type']=$notice_type;
                 $noticeD = $this->db_model->select_data('*','views_notification_student',array('student_id'=>$student_id,'notice_type'=>$notice_type),1);
                 if(!empty($noticeD)){
                     $this->db_model->update_data_limit('views_notification_student ',array('views_time'=>$cu_date),array('n_id'=>$noticeD[0]['n_id']),1);
                 }else{
                     $data_arr = $this->security->xss_clean($data_arr);
                     $ins = $this->db_model->insert_data('views_notification_student',$data_arr);
                    
                 }
            }
                    
                return;
                
        }
}