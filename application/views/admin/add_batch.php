<?php print_r($batchData);?>
<section class="edu_admin_content">
    <div class="edu_admin_right sectionHolder edu_add_users">
        <div class="edu_admin_informationdiv edu_main_wrapper">
            <form class="form" method="post">
                <div class="row">
                    <?php if(isset($batch_data) && !empty($batch_data)){
                        $batchData = $batch_data[0];
                        $subjectData = $this->db_model->select_data('batch_subjects.*,subjects.subject_name','batch_subjects use index (batch_id)',array('batch_subjects.batch_id'=>$batch_id),'','','',array('subjects','subjects.id = batch_subjects.subject_id'));
                    }else{
                        $batchData = array();
                    }?>
                    	<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    						<div class="form-group">
    							<label><?php echo html_escape($this->common->languageTranslator('ltr_select_category'));?> <sup>*</sup></label>								
    							<select class="form-control edu_selectbox_with_search category_dropdown" id="category_dropdown" name="category" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_category'));?>">
    								<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_category'));?></option>
    								<?php foreach($category_data as $cat){?>
    									<option value="<?php echo $cat['id'];?>"<?php if(!empty($batchData['cat_id'])){if($cat['id']== $batchData['cat_id']){ echo "selected"; }} ;?>><?php echo $cat['name']; ?>	</option>
    								<?php }?>
    							</select>								
			    			</div>	 
					    </div>
						<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
						<div class="form-group">
							<label><?php echo html_escape($this->common->languageTranslator('ltr_select_subcategory'));?> <sup>*</sup></label>								
							<select class="form-control edu_selectbox_with_search subcategory_dropdown" id="subcategory_dropdown" name="subcategory" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_subcategory'));?>">
								<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_subcategory'));?></option>
							               <?php
                                        if(!empty($batchData['cat_id'])){
                                             $subcat = $this->db_model->select_data('id,name,slug','batch_subcategory use index (id)',array('cat_id'=>$batchData['cat_id'],'status'=> 1),'',array('id','desc'));
                                        // $subcat = current($this->db_model->select_data('*','batch_subcategory use index (id)', array('cat_id'=>$batchData['cat_id'])));
                                            
                                         foreach($subcat as $sub_cat){?>
                                         
                                          <option value="<?php echo $sub_cat['id'];?>"<?php if(!empty($batchData['sub_cat_id'])){if($sub_cat['id']== $batchData['sub_cat_id']){ echo "selected"; }} ;?>><?php echo $sub_cat['name']; ?>	</option>        
                                                  
                                           
                                                       <?php } } ?>
							</select>							
						</div>	
					</div>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_batch_name'));?><sup>*</sup></label>
    						<input type="text" class="form-control require"  placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_batch_name'));?>" name="batch_name"  id="batchName" value="<?php echo (isset($batchData['batch_name']) && !empty($batchData['batch_name']))?$batchData['batch_name']:'';?>">			
    					</div>
    				</div>
    				<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_start_date'));?><sup>*</sup></label>
    						<input type="text" class="form-control require"  placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_start_date'));?>"  name="start_date"  id="b_startDate" value="<?php echo (isset($batchData['start_date']) && !empty($batchData['start_date']))?date('d-m-Y',strtotime($batchData['start_date'])):'';?>">	
    					</div>
    				</div>
    				<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_end_date'));?><sup>*</sup></label>
    						<input type="text" class="form-control require" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_end_date'));?>" name="end_date"  id="b_endDate" value="<?php echo (isset($batchData['end_date']) && !empty($batchData['end_date']))?date('d-m-Y',strtotime($batchData['end_date'])):'';?>">
    					</div>
    				</div>
    				<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_start_time'));?><sup>*</sup></label>
    						<div class="chooseTime">
    							<input type="text" class="form-control require"  placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_start_time'));?>"  name="start_time"  id="b_startTime" value="<?php echo (isset($batchData['start_time']) && !empty($batchData['start_time']))?date('h:i A',strtotime($batchData['start_time'])):'';?>">
    						</div>	
    					</div>
    				</div>
    				<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_end_time'));?><sup>*</sup></label>
    						<div class="chooseTime">
    							<input type="text" class="form-control require" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_end_time'));?>" name="end_time"  id="b_endTime" value="<?php echo (isset($batchData['end_time']) && !empty($batchData['end_time']))?date('h:i A',strtotime($batchData['end_time'])):'';?>">
    						</div>
    					</div>
					</div>
					<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group eb_batchtype">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_batch_type'));?><sup>*</sup></label><br>
							<div class="form-control">
								<label><?php echo html_escape($this->common->languageTranslator('ltr_free'));?></label>
    							<input type="radio" <?php if(!empty($batchData['batch_type']) && $batchData['batch_type']==1){ echo 'checked'; }else{ echo 'checked'; } ?> class="batchType" name="batchType" value="1">
								<label><?php echo html_escape($this->common->languageTranslator('ltr_paid'));?></label>
								<input type="radio" class="batchType" <?php if(!empty($batchData['batch_type']) && $batchData['batch_type']==2){ echo 'checked'; }?> name="batchType" value="2">
							</div>
    					</div>
					</div>
					<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20 batchPrice <?php if(!empty($batchData['batch_type']) && $batchData['batch_type']==2){ echo 'show'; }else{ echo 'hide'; } ?>">
					   
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_batch_price'));?><sup>*</sup></label><br>
							<input type="text" data-valid="number" data-error="<?php echo html_escape($this->common->languageTranslator('ltr_valid_price_msg'));?>" maxlength="10" class="form-control"  placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_price')).' '. $currency_code; ?> " name="batchPrice"  id="batchPrice" data-conv="" value="<?php if(!empty($batchData['batch_price'])){ echo $batchData['batch_price'] ;} ?>">	
    					</div>
					</div>
					<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20 batchPrice <?php if(!empty($batchData['batch_type']) && $batchData['batch_type']==2){ echo 'show'; }else{ echo 'hide'; } ?>">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_offer_price'));?><sup>*</sup></label><br>
							<input type="text" data-valid="number" data-error="<?php echo html_escape($this->common->languageTranslator('ltr_valid_price_msg'));?>" maxlength="10" class="form-control"  placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_price')).' '. $currency_code; ?> " name="batchOfferPrice"  id="batchOfferPrice" value="<?php if(!empty($batchData['batch_offer_price'])){ echo $batchData['batch_offer_price'] ;} ?>">	
    					</div>
					</div>
					<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20 batchPrice <?php if(!empty($batchData['batch_type']) && $batchData['batch_type']==2){ echo 'show'; }else{ echo 'hide'; } ?>">
    					<div class="form-group">
    					  <input type="radio" name="payMode"value="offline" class="payModeBatch">Offline
                            <input type="radio" name="payMode"value="Online" class="payModeBatch" checked>Online
    					</div>
					</div>
                </div>
                
				<div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="edu_accordion_container_heading">
                            <?php 
                            if(!empty($batch_fecherd)){
                                foreach($batch_fecherd as $value){ ?>
                                <div class="edu_accord_parent">
                                    <span class="edu_accordion_header">
										<span class="speci_heading"><?php echo isset($value['batch_specification_heading'])?$value['batch_specification_heading']: html_escape($this->common->languageTranslator('ltr_benefit'));?></span> 
										<i>
											<i class="fa fa-angle-right upDownI"></i>
											<i data-id="<?php echo isset($value['id'])?$value['id']:'';?>" class="fa fa-trash eb_removeacc removeAccHeading"></i>
										</i>
                                    </span>
                                    <div class="edu_accordion_content count_heading">
                                        <div class="row">
                                            <div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
                                                <div class="form-group">
												 <label><?php echo html_escape($this->common->languageTranslator('ltr_heading'));?></label>
                                                <input type="text" class="form-control"  placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_i_learn'));?>" name="batch_speci_heading[]" value="<?php echo isset($value['batch_specification_heading'])?$value['batch_specification_heading']:'';?>">
                                        <input value="<?php echo isset($value['id'])?$value['id']:'';?>" type="hidden" name="batch_speci_id[]">
                                                </div>
                                            </div>
											 <div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
												<div class="form-group">
													<label><?php echo html_escape($this->common->languageTranslator('ltr_fecherd'));?></label>
													<div class="batch_sub_heading">
											<?php if(!empty($value['batch_fecherd'])){
												$rrr = json_decode($value['batch_fecherd']);
												foreach($rrr as $key){ ?>
													<div class="sub_input">
													   <input type="text" class="form-control fecherd"  placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_fecherd'));?>" value="<?php echo $key; ?>" >
													   <div class="eb_subhead_icon">
    													  <i class="fa fa-plus eb_add_sheading assSubHeading"></i>
    													  <i class="fa fa-trash eb_rem_sheading removeSubHeading"></i>
    												    </div>
													</div>
												<?php }
											}else{ ?>
                                                    <div class="sub_input">
													  <input type="text" class="form-control fecherd"  placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_fecherd'));?>" name="batch_sub_fecherd[]" >
													  <div class="eb_subhead_icon">
    													  <i class="fa fa-plus eb_add_sheading assSubHeading"></i>
    													  <i class="fa fa-trash eb_rem_sheading removeSubHeading"></i>
    												    </div>
													</div>
												
											<?php } ?>
													</div>
												</div>
											</div>
                                        </div>
                                    </div>
                                </div>
                               <?php }
                            }else{ ?>
                            <div class="edu_accord_parent">
                                <span class="edu_accordion_header">
                                    <span class="speci_heading"><?php echo html_escape($this->common->languageTranslator('ltr_benefit'));?></span> 
                                    <i>
                                        <i class="fa fa-angle-right upDownI"></i>
                                        <i class="fa fa-trash eb_removeacc removeAccHeading"></i>
                                    </i>
                                </span>
                                <div class="edu_accordion_content count_heading">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
                                            <div class="form-group">
                                                <label><?php echo html_escape($this->common->languageTranslator('ltr_heading'));?></label>
                                                <input type="text" class="form-control"  placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_i_learn'));?>" name="batch_speci_heading[]" >
                                                <input value="" type="hidden" name="batch_speci_id[]">
                                            </div>
                                        </div>
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
                                            <div class="form-group">
                                                <label><?php echo html_escape($this->common->languageTranslator('ltr_fecherd'));?></label>
                                                <div class="batch_sub_heading">
                                                    <div class="sub_input">
													  <input type="text" class="form-control fecherd"  placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_fecherd'));?>" name="batch_sub_fecherd[]" >
													  <div class="eb_subhead_icon">
    													  <i class="fa fa-plus eb_add_sheading assSubHeading"></i>
    													  <i class="fa fa-trash eb_rem_sheading removeSubHeading"></i>
    												    </div>
													</div>
												</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                     </div>
					 
					 <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_image'));?></label>
    						<input type="file" class="form-control" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_image'));?>" name="batch_image" data-valid="image" data-error="<?php echo html_escape($this->common->languageTranslator('ltr_valid_image_msg'));?>" id="batch_image" value="">
    							<p class="fileNameShow"></p>
    							<p class="edu_top_10">Recommended Size (520*430)</p>
    					</div>
    				</div>
					<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_description'));?></label>
							<textarea class="form-control" name="batch_description" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_description'));?>"><?php if(!empty($batchData['description'])){ echo $batchData['description']; } ?></textarea>
    					</div>
    				</div>
                </div>
				<!-- end -->

                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="edu_accordion_container">
                            <?php 
                            if(!empty($subjectData)){
                                foreach($subjectData as $sublst){ ?>
                                <div class="edu_accord_parent">
                                    <span class="edu_accordion_header">
                                        <span class="subjects_name"><?php echo isset($sublst['subject_name'])?$sublst['subject_name']:'';?></span> 
                                        <i>
                                            <i class="fa fa-angle-right upDownI"></i>
                                            <i class="fa fa-trash removeAccSub eb_removeacc"></i>
                                        </i>
                                    </span>
                                    <div class="edu_accordion_content">
                                        <div class="row">
                                            <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                                                <div class="form-group">
                                                    <label><?php echo html_escape($this->common->languageTranslator('ltr_subject'));?><sup>*</sup></label>
                                                    <select class="edu_selectbox_with_search form-control require filter_subject" name="batch_subject[]" data-teacher="yes" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_subject'));?>">
                                                        <option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_subject'));?></option>
                                                        <?php if(!empty($subject))
                                                        {
                                                            foreach($subject as $sub){ 
                                                                if($sub['id'] == $sublst['subject_id']){
                                                                    $sel = 'selected';
                                                                }else{
                                                                    $sel = '';
                                                                }
                                                                echo '<option value="'.$sub['id'].'" '.$sel.'>'.$sub['subject_name'].'</option>';
                                                            }
                                                        }?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12 col-sm-12 col-12">
                                                <div class="form-group">
                                                    <label><?php echo html_escape($this->common->languageTranslator('ltr_chapters'));?><sup>*</sup></label>
                                                    <select class="edu_selectbox_with_search form-control require filter_chapter" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_chapter'));?>" multiple>
                                                        <option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_chapter'));?></option>
                                                        <?php
                                                           if(isset($sublst['chapter']) && !empty($sublst['chapter'])){
                                                                //$chapters = $this->db_model->select_data('id,chapter_name','chapters use index (id)', 'id in ('.implode(',',json_decode($sublst['chapter'],true)).')');
                                                                $chapters = $this->db_model->select_data('id,chapter_name','chapters use index (id)', array('subject_id'=>$sublst['subject_id']));
                                                            }else{
                                                                $chapters = '';
                                                            }

                                                            if(!empty($chapters)){
                                                                foreach($chapters as $chap){
                                                                    $chaperArr = json_decode($sublst['chapter'],true);
                                                                   
                                                                    if(in_array($chap['id'],$chaperArr)){
                                                                        $sel = 'selected';
                                                                    }else{
                                                                        $sel = '';
                                                                    }
                                                                    echo '<option value="'.$chap['id'].'" '.$sel.'>'.$chap['chapter_name'].'</option>';
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12 col-sm-12 col-12">
                                                <div class="form-group">
                                                    <label><?php echo html_escape($this->common->languageTranslator('ltr_teacher'));?><sup>*</sup></label>
                                                    <select class="edu_selectbox_with_search form-control require filter_teacher" name="batch_teacher[]" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_teacher'));?>">
                                                        <option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_teacher'));?></option>
                                                        <?php
                                                            if(isset($sublst['subject_id']) && !empty($sublst['subject_id'])){
                                                                $like = array('teach_subject','"'.$sublst['subject_id'].'"');
                                                                $teacher = $this->db_model->select_data('id,name','users use index (id)', '','','',$like);
                                                                
                                                            }else{
                                                                $teacher = '';
                                                            }

                                                            if(!empty($teacher)){
                                                                foreach($teacher as $teach){
                                                                    if(isset($sublst['teacher_id']) && ($sublst['teacher_id']==$teach['id'])){
                                                                        $sel = 'selected';
                                                                    }else{
                                                                        $sel = '';
                                                                    }
                    
                                                                    echo '<option value="'.$teach['id'].'" '.$sel.'>'.$teach['name'].'</option>';
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12 col-sm-12 col-12">
                                                <div class="form-group">
                                                    <label><?php echo html_escape($this->common->languageTranslator('ltr_start_date'));?><sup>*</sup></label>
                                                    <input type="text" class="form-control chooseDate require" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_start_date'));?>" name="sub_start_date[]" value="<?php echo (isset($sublst['sub_start_date']) && !empty($sublst['sub_start_date']))?date('d-m-Y',strtotime($sublst['sub_start_date'])):'';?>">	
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12 col-sm-12 col-12">
                                                <div class="form-group">
                                                    <label><?php echo html_escape($this->common->languageTranslator('ltr_end_date'));?><sup>*</sup></label>
                                                    <input type="text" class="form-control chooseDate require" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_end_date'));?>" name="sub_end_date[]" value="<?php echo (isset($sublst['sub_end_date']) && !empty($sublst['sub_end_date']))?date('d-m-Y',strtotime($sublst['sub_end_date'])):'';?>">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12 col-sm-12 col-12">
                                                <div class="form-group">
                                                    <label><?php echo html_escape($this->common->languageTranslator('ltr_start_time'));?><sup>*</sup></label>
                                                    <div class="chooseTime">
                                                        <input type="text" class="form-control require"  placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_start_time'));?>" name="sub_start_time[]" value="<?php echo (isset($sublst['sub_start_time']) && !empty($sublst['sub_start_time']))?date('h:i A',strtotime($sublst['sub_start_time'])):'';?>">
                                                    </div>	
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12 col-sm-12 col-12">
                                                <div class="form-group">
                                                    <label><?php echo html_escape($this->common->languageTranslator('ltr_end_time'));?><sup>*</sup></label>
                                                    <div class="chooseTime">
                                                        <input type="text" class="form-control require" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_end_time'));?>" name="sub_end_time[]" value="<?php echo (isset($sublst['sub_end_time']) && !empty($sublst['sub_end_time']))?date('h:i A',strtotime($sublst['sub_end_time'])):'';?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                               <?php }
                            }else{ ?>
                            <div class="edu_accord_parent">
                                <span class="edu_accordion_header">
                                    <span class="subjects_name"><?php echo html_escape($this->common->languageTranslator('ltr_subject'));?></span> 
                                    <i>
                                        <i class="fa fa-angle-right upDownI"></i>
                                        <i class="fa fa-trash removeAccSub eb_removeacc"></i>
                                    </i>
                                </span>
                                <div class="edu_accordion_content">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label><?php echo html_escape($this->common->languageTranslator('ltr_subject'));?><sup>*</sup></label>
                                                <select class="edu_selectbox_with_search form-control require filter_subject" name="batch_subject[]" data-teacher="yes" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_subject'));?>">
                                                    <option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_subject'));?></option>
                                                    <?php if(!empty($subject))
                                                    {
                                                        foreach($subject as $sub){ 
                                                            echo '<option value="'.$sub['id'].'" >'.$sub['subject_name'].'</option>';
                                                        }
                                                    }?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label><?php echo html_escape($this->common->languageTranslator('ltr_chapters'));?><sup>*</sup></label>
                                                <select class="edu_selectbox_with_search form-control require filter_chapter" multiple data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_chapter'));?>">
                                                    <option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_chapter'));?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label><?php echo html_escape($this->common->languageTranslator('ltr_teacher'));?><sup>*</sup></label>
                                                <select class="edu_selectbox_with_search form-control require filter_teacher" name="batch_teacher[]" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_teacher'));?>">
                                                    <option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_teacher'));?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label><?php echo html_escape($this->common->languageTranslator('ltr_start_date'));?><sup>*</sup></label>
                                                <input type="text" class="form-control chooseDate require"  placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_start_date'));?>" name="sub_start_date[]">	
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label><?php echo html_escape($this->common->languageTranslator('ltr_end_date'));?><sup>*</sup></label>
                                                <input type="text" class="form-control chooseDate require" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_end_date'));?>" name="sub_end_date[]">
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label><?php echo html_escape($this->common->languageTranslator('ltr_start_time'));?><sup>*</sup></label>
                                                <div class="chooseTime">
                                                    <input type="text" class="form-control require"  placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_start_time'));?>" name="sub_start_time[]">
                                                </div>	
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-12 col-sm-12 col-12">
                                            <div class="form-group">
                                                <label><?php echo html_escape($this->common->languageTranslator('ltr_end_time'));?><sup>*</sup></label>
                                                <div class="chooseTime">
                                                    <input type="text" class="form-control require" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_end_time'));?>" name="sub_end_time[]">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                     </div>
                </div>
                <div class="row">
                    <?php if(!empty($batch_id)){
                        echo '<input type="hidden" name="batch_id" id="batch_id" value="'.$batch_id.'">';
                        $type = 'edit';
                    }else{
                        $type = 'add';
                    }?>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                        <div class="edu_btn_wrapper">
                            <div class='hide subjectArrayDiv'><?php echo json_encode($subject);?></div>
                            <button type="button" class="edu_admin_btn AssignBatchHeading res-380-bottom">+ <?php echo html_escape($this->common->languageTranslator('ltr_assign_benefit'));?></button>
							<button type="button" class="edu_admin_btn AssignSubBatch res-380-bottom">+ <?php echo html_escape($this->common->languageTranslator('ltr_assign_subject'));?></button>
                            <button type="button" class="edu_admin_btn addEditBatch res-380-bottom" data-type="<?php echo $type;?>"><?php echo !empty($batch_data)? html_escape($this->common->languageTranslator('ltr_update_batch')): html_escape($this->common->languageTranslator('ltr_save_batch'));?></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>