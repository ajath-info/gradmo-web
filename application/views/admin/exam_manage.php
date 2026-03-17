<section class="edu_admin_content">
	<div class="edu_admin_right sectionHolder edu_manage_exam_wrap">
        <div class="PaperTable">
        	<?php 
    		if(!empty($question_data) && $question_data>=1){
    		?>
    		<div class="edu_admin_informationdiv sectionHolder">
                <div class="edu_main_wrapper edu_table_wrapper">
    		        <div class="tableFullWrapper">
    		           
    				    <table class="server_datatable datatable table table-striped table-hover dt-responsive" cellspacing="0" width="100%" data-url="ajaxcall/exam_table">
        			        <thead>
                                <tr>  
                                   <?php echo ($this->session->userdata('role')==1)?'<th><input type="checkbox" class="checkAllAttendance"></th>':''; ?>
                                    <th>#</th>
                                    <th><?php echo html_escape($this->common->languageTranslator('ltr_paper_format'));?></th>
                                    <th><?php echo html_escape($this->common->languageTranslator('ltr_batch'));?></th>
                                    <th><?php echo html_escape($this->common->languageTranslator('ltr_paper_name'));?></th>
                                    <th><?php echo html_escape($this->common->languageTranslator('ltr_total_question'));?></th>
                                    <th><?php echo html_escape($this->common->languageTranslator('ltr_time_duration'));?></th>
                                    <th><?php echo html_escape($this->common->languageTranslator('ltr_totale_mask'));?></th>
                                    <th><?php echo html_escape($this->common->languageTranslator('ltr_Negative_m'));?></th>
                                    <th><?php echo html_escape($this->common->languageTranslator('ltr_scheduled_date'));?></th>
                                    <th><?php echo html_escape($this->common->languageTranslator('ltr_scheduled_time'));?></th>
                                    <th><?php echo html_escape($this->common->languageTranslator('ltr_paper_type'));?></th>
                                    <th><?php echo html_escape($this->common->languageTranslator('ltr_added_by'));?></th>
                                    <!--<th>Status</th>-->
                                    <th><?php echo html_escape($this->common->languageTranslator('ltr_actions'));?></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
    		</div> 
    		<?php 
    			}else{ 
    			     echo '<section class="edu_admin_content">
                            <div class="edu_admin_right sectionHolder edu_add_users">
                                <div class="edu_admin_informationdiv edu_main_wrapper">
                                    <div class="eac_text eac_page_re">'.html_escape($this->common->languageTranslator('ltr_create_paper_msg')).'</div>
                                </div>
                            </div>
                        </section>';
    			} ?>
        </div>
    <div class="paper_update_edit paper_update_edit1">
        <div class="edu_main_wrapper">
            <div class="paper_edit_manage">
            <div class="edu_popup_wrapper">
                <div class="edu_popup_inner">
                    <h4 class="edu_sub_title" ><?php echo html_escape($this->common->languageTranslator('ltr_edit_manage_paper'));?></h4>
                    <form class="form edit_manage_paper" action="" method="post" autocomplete="off">
                        <div class="row">
        					<input type="hidden" class="totalQuestions" name="total_question" value="0">
        					<input type="hidden" class="expid" name="expid" value="">
        					<div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
    						    <div class="form-group paper_updatecheckbox"> 
            						<label>Existing Paper Modify
                                    <input type="radio" class="academics " value ="existing" id="existing" name="modifyType" ></label>
                                	<label class="">New Paper Create
                                    <input type="radio" class="academics " value="newpaper" id="newpaper" name="modifyType" checked></label>
        						</div>
    						</div>
        					<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
        						<div class="form-group">
        						   
        							<label><?php echo html_escape($this->common->languageTranslator('ltr_paper_type')); ?> <sup>*</sup></label>
        							<select id="type"class="form-control require changePaperType edu_selectbox_without_search" name="type" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_type')); ?>">
        								<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_type')); ?></option>
        								<option value="1" <?= isset($exam_paper[0]['type'])==1 ? 'selected' : ''?> ><?php echo html_escape($this->common->languageTranslator('ltr_mock_test_paper')); ?></option>
        								<option value="2"  ><?php echo html_escape($this->common->languageTranslator('ltr_practice_paper')); ?></option>
        							</select>
        						</div>
        					</div>
        					<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
        						<div class="form-group">
        							<label><?php echo html_escape($this->common->languageTranslator('ltr_paper_name')); ?> <sup>*</sup></label>
        							<input type="text"id="paper_name" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_paper_name')); ?>" value="<?=isset($exam_paper[0]['name'])? $exam_paper[0]['name'] :'';?>" class="form-control require" name="name">
        						</div>
        					</div> 
        				
        		            <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20 mocktesthideshow  <?php isset($exam_paper[0]['type'])==1 ? '':'Hide'?>">
        						<div class="form-group"> 
        							<label><?php echo html_escape($this->common->languageTranslator('ltr_mock_test_schedule_date')); ?> <sup>*</sup></label>
        							<input type="text"id="mock_date" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_schedule_date')); ?>" value="<?=isset($exam_paper[0]['mock_sheduled_date'])? $exam_paper[0]['mock_sheduled_date'] :'';?>" class="form-control chooseDate" name="mock_sheduled_date">
        						</div>
        					</div>
        					<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20 mocktesthideshow   <?php isset($exam_paper[0]['type'])==1 ? '':'Hide'?>">
        						<div class="form-group"> 
        							<label><?php echo html_escape($this->common->languageTranslator('ltr_mock_test_schedule_date')); ?> <sup>*</sup></label>
        							<input type="text"id="mock_time" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_schedule_time')); ?>" value="<?=isset($exam_paper[0]['mock_sheduled_time'])? $exam_paper[0]['mock_sheduled_time'] :'';?>" class="form-control chooseTime" name="mock_sheduled_time">
        						</div>
        					</div>
        					        
        					<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
        						<div class="form-group">
        							<label><?php echo html_escape($this->common->languageTranslator('ltr_time_duration_min')); ?> <sup>*</sup></label>
        							<input type="number"id="time_durarion" value="<?=isset($exam_paper[0]['time_duration'])? $exam_paper[0]['time_duration'] :'';?>" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_time_duration_mini')); ?>" class="form-control require" name="time_duration">
        						</div>
        					</div>
        					<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
        						<div class="form-group"> 
        							<label><?php echo html_escape($this->common->languageTranslator('ltr_batch')); ?> <sup>*</sup></label>
        							<select id="batch"name="batch_id" class="form-control require edu_selectbox_with_search" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_batch')); ?>">
        								<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_batch')); ?></option>
        								<?php foreach($batch as $ba){ 
        								    if($ba['id'] == $exam_paper[0]['batch_id'] ){
        							        	echo '<option value="'.$ba['id'].'" selected>'.$ba['batch_name'].'</option>';
        								    }else{
        							        	echo '<option value="'.$ba['id'].'" >'.$ba['batch_name'].'</option>';
        								    }
        								    
        								
        								}
        								
        								?>
        							</select>
        						</div>
        					</div>
        					<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20 paper_update   <?php isset($exam_paper[0]['type'])==1 ? '':'Hide'?>">
        						<div class="form-group paper_updatecheckbox"> 
            						<label><?php echo html_escape($this->common->languageTranslator('ltr_add_question_p'));?>
                                    <input type="checkbox" class="academics " id="addQuestion" name="addQuestion" ></label>
                                	<label class=""><?php echo html_escape($this->common->languageTranslator('ltr_Create_New_Question'));?>
                                    <input type="checkbox" class="academics " id="CreateQuestion" name="CreateQuestion" ></label>
        						</div>
        					</div>
        					<div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20 quetion_hide_show">
        						<div class="form-group"> 
        							<label><?php echo html_escape($this->common->languageTranslator('ltr_old_question')); ?> <sup>*</sup></label>
        							<select id="old_queston_p"name="old_queston[]" class="form-control require edu_selectbox_with_search" multiple data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_question_select')); ?>">
        								<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_question_select')); ?></option>
        								<?php 
        						        	$i=1;
        								    foreach($Question as $baa){ 
        								        
        								        echo '<option value="'.$baa['id'].'" >Q'.$i++.' '.$baa['question'].'</option>';
        								        echo '<br>';
        								        echo '<br>';
        								    }
        								
        								?>
        							</select>
        						</div>
        					</div>
        					<div class="row create_question_new create_question_new_space">
        				      <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
            					<div class="form-group "  >
        							<label><?php echo html_escape($this->common->languageTranslator('ltr_select_subject'));?><sup>*</sup></label>
        							<select class="form-control filter_subject modalSubjectCls require edu_selectbox_with_search" name="subject_id" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_subject'));?>"> 
        								<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_subject'));?></option>
        								<?php
        									if(!empty($subject)){
        										foreach($subject as $sub){
        											$selected="";
        											if(isset($single_question['subject_id'])){
        												if($single_question['subject_id']==$sub['id']){
        													$selected="selected";
        												}
        											}
        											echo '<option value="'.$sub['id'].'" '.$selected.'>'.$sub['subject_name'].'</option>';
        										}
        									}
        								?> 
        							</select>
        						</div>
            				</div>
            				<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
            					<div class="form-group  add_edit_question" data-id="<?=(isset($single_question['chapter_id']))?$single_question['chapter_id']:''?>">
        							<label><?php echo html_escape($this->common->languageTranslator('ltr_select_chapter'));?><sup>*</sup></label>
        							<select  class="form-control filter_modal_chapter  edu_selectbox_with_search" name="chapter_id" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_chapter'));?>"> 
        								<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_chapter'));?></option>
        							</select>
        						</div>
            				</div>
        				    	<div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
                					<div class="form-group">
        							<label ><?php echo html_escape($this->common->languageTranslator('ltr_question'));?><sup>*</sup></label>
        							<span ><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Note :-</b>If you want to insert a numerical equation please click on <img src="<?php echo base_url()?>assets/images/sum-sign.svg" style="width: 15px"> </span>
        							<textarea name="question" rows="2" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_question'));?>" class="form-control "></textarea>
        						</div>
            						<div  id="question_options">
            					        <div  class="row">
                    						<?php 
                    							$op = '1';
                    							$cn = 'A';
                    							$option=(isset($data['options']))?json_decode($data['options']):"";
                    							//print_r($option);
                    							for($i=1; $i<5; $i++){
                    								?>
                    								<div class="col-lg-6 col-md-12 col-sm-12 col-12">
                    									<div class="form-group">
                    										<label>
                    											<div class="ans_option"><?php echo html_escape($this->common->languageTranslator('ltr_option_'.$cn));?> <sup>*</sup></div>
                    										</label>
                    										<textarea type="text" class="form-control editor"  name="options[]" id="option<?=$i?>" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_option_'.$cn));?>"></textarea>
                    									</div>
                    								</div>
                    							<?php
                    								$op++;
                    								$cn++;
                    							} ?>
                					     </div>
            						</div>
            				    </div>
                				<div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
            
                				    <label class="ans_option"><?php echo html_escape($this->common->languageTranslator('ltr_right_answer'));?><sup>*</sup></label>
                					<div class="form-group edu_radio_holder_wrapper">
            							<div class="edu_radio_holder">
            							    <label for="radio"><?php echo html_escape($this->common->languageTranslator('ltr_a'));?></label>
            							    <input type="radio" class="ansRadioChck" name="answer" value="A" <?php if(isset($data[0]['answer']) && ($data[0]['answer']=="A")){ echo "checked"; }?>>
            							</div>
            							<div class="edu_radio_holder">
            							    <label for="radio"><?php echo html_escape($this->common->languageTranslator('ltr_b'));?></label>
            							    <input type="radio" class="ansRadioChck" name="answer" value="B" <?php if(isset($data[0]['answer']) && ($data[0]['answer']=="B")){ echo "checked"; }?>>
            							</div>
            							<div class="edu_radio_holder">
            							    <label for="radio"><?php echo html_escape($this->common->languageTranslator('ltr_c'));?></label>
            							    <input type="radio" class="ansRadioChck" name="answer" value="C" <?php if(isset($data[0]['answer']) && ($data[0]['answer']=="C")){ echo "checked"; }?>>
            							</div>
            							<div class="edu_radio_holder">
            							    <label for="radio"><?php echo html_escape($this->common->languageTranslator('ltr_d'));?></label>
            							    <input type="radio" class="ansRadioChck" name="answer" value="D" <?php if(isset($data[0]['answer']) && ($data[0]['answer']=="D")){ echo "checked"; }?>>
            							</div>
            							<div class="edu_radio_holder">
        							    	<label>Add Question Marks <sup>*</sup></label>
            							    <input type="number"id="marks" placeholder="Add Marks" value="" class="form-control " name="marks">
            							</div>
            						</div>
                				</div>
        				    </div>
        					<div class="col-lg-12 col-md-12 col-sm-12 col-12 q_data">
        						<div class="form-group">
        							<label class="emq"><b><?php echo html_escape($this->common->languageTranslator('ltr_Questions'));?> </b><sup>*</sup></label>
        						</div>
        						
        					</div>
        					<div class="col-lg-12 col-md-12 col-sm-12 col-12">
            					<div class="edu_btn_wrapper">
        							<input type="button" value="<?php echo html_escape($this->common->languageTranslator('ltr_Update_Paper'));?>" class="btn btn-primary Update_paper" data-id="<?php if(isset($single_question['id']) && ($single_question['id']!="")){ echo $single_question['id']; }?>">
        						</div>
            				</div>
        				</div>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </div>    

	</div>
</section>
<div class="createDivWrapper edu_add_question create_ppr_popup hide">
    		<div class="edu_admin_informationdiv sectionHolder">
    		    <div class="ppr_popup_inner">
        			<div class="row align-items-center text-center">
        			    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
					 <?php if($this->session->userdata('role')==1){ ?>
				    <button class="multiDelete btn_delete  btn btn-primary"  data-placement="top" title="Delete" data-table="exams" data-column="id"><?php echo html_escape($this->common->languageTranslator('ltr_delete'));?></button>
				    <?php }?>
        		</div>
					</div>
        		</div>
    		</div>
		</div>
		<!-- Pop Up Start  -->
	