            <form class="form" method="post">
                <div class="row">
                    <?php if(isset($batch_data) && !empty($batch_data)){
                        $batchData = $batch_data[0];
                        $subjectData = $this->db_model->select_data('batch_subjects.*,subjects.subject_name','batch_subjects use index (batch_id)',array('batch_subjects.batch_id'=>$batch_id),'','','',array('subjects','subjects.id = batch_subjects.subject_id'));
                    }else{
                        $batchData = array();
                    }
                    $tcb_api_dropdowns = !empty($load_batch_dropdowns_via_api);
                    $tcb_selected_cat = !empty($batchData['cat_id']) ? (int) $batchData['cat_id'] : 0;
                    $tcb_selected_subcat = !empty($batchData['sub_cat_id']) ? (int) $batchData['sub_cat_id'] : 0;
                    ?>
                    	<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    						<div class="form-group">
    							<label><?php echo html_escape($this->common->languageTranslator('ltr_select_category'));?> <sup>*</sup></label>								
    							<select class="form-control<?php echo $tcb_api_dropdowns ? ' tcb-native-select' : ' edu_selectbox_with_search'; ?> category_dropdown" id="category_dropdown" name="category" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_category'));?>"<?php echo $tcb_api_dropdowns ? ' data-api-load="categories" data-selected="' . $tcb_selected_cat . '"' : ''; ?>>
    								<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_category'));?></option>
    								<?php if (!$tcb_api_dropdowns && !empty($category_data)) {
    								foreach ($category_data as $cat) { ?>
    									<option value="<?php echo (int) $cat['id']; ?>"<?php if ($tcb_selected_cat && (int) $cat['id'] === $tcb_selected_cat) { echo ' selected'; } ?>><?php echo html_escape($cat['name']); ?></option>
    								<?php }
    								} ?>
    							</select>								
			    			</div>	 
					    </div>
						<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
						<div class="form-group">
							<label><?php echo html_escape($this->common->languageTranslator('ltr_select_subcategory'));?> <sup>*</sup></label>								
							<select class="form-control<?php echo $tcb_api_dropdowns ? ' tcb-native-select' : ' edu_selectbox_with_search'; ?> subcategory_dropdown" id="subcategory_dropdown" name="subcategory" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_subcategory'));?>"<?php echo $tcb_api_dropdowns ? ' data-api-load="subcategories" data-selected="' . $tcb_selected_subcat . '"' : ''; ?>>
								<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_subcategory'));?></option>
								<?php if (!$tcb_api_dropdowns && !empty($batchData['cat_id'])) {
									$subcat = $this->db_model->select_data('id,name,slug', 'batch_subcategory use index (id)', array('cat_id' => $batchData['cat_id'], 'status' => 1), '', array('id', 'desc'));
									if (!empty($subcat)) {
										foreach ($subcat as $sub_cat) { ?>
											<option value="<?php echo (int) $sub_cat['id']; ?>"<?php if ($tcb_selected_subcat && (int) $sub_cat['id'] === $tcb_selected_subcat) { echo ' selected'; } ?>><?php echo html_escape($sub_cat['name']); ?></option>
										<?php }
									}
								} ?>
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
    						<label>Institute <span class="text-muted">(optional)</span></label>
    						<select class="form-control<?php echo $tcb_api_dropdowns ? ' tcb-native-select' : ' edu_selectbox_with_search'; ?>" id="institute_id" name="institute_id" data-placeholder="Select institute">
    							<option value="">Select institute</option>
    							<?php if (!empty($institute_list)) {
    								foreach ($institute_list as $ins) { ?>
    									<option value="<?php echo (int) $ins['id']; ?>"<?php if (!empty($batchData['institute_id']) && (int) $batchData['institute_id'] === (int) $ins['id']) { echo ' selected'; } ?>><?php echo html_escape($ins['name']); ?></option>
    								<?php }
    							} ?>
    						</select>
    					</div>
    				</div>
    				<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label>Batch mode (Online / Offline) <sup>*</sup></label>
    						<select class="form-control require" id="batch_mode" name="batch_mode">
    							<option value="Online"<?php echo (empty($batchData['batch_mode']) || strtolower((string) $batchData['batch_mode']) === 'online') ? ' selected' : ''; ?>>Online</option>
    							<option value="Offline"<?php echo (!empty($batchData['batch_mode']) && strtolower((string) $batchData['batch_mode']) === 'offline') ? ' selected' : ''; ?>>Offline</option>
    						</select>
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
    					  <?php
    					  $pay_offline_sel = !empty($batchData['pay_mode']) && strtolower((string) $batchData['pay_mode']) === 'offline';
    					  ?>
    					  <input type="radio" name="payMode" value="offline" class="payModeBatch"<?php echo $pay_offline_sel ? ' checked' : ''; ?>>Offline
                            <input type="radio" name="payMode" value="Online" class="payModeBatch"<?php echo $pay_offline_sel ? '' : ' checked'; ?>>Online
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
                                                    <select class="edu_selectbox_with_search form-control require filter_subject" name="batch_subject[]" data-teacher="yes" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_subject'));?>"<?php echo ($tcb_api_dropdowns && !empty($sublst['subject_id'])) ? ' data-selected="' . (int) $sublst['subject_id'] . '"' : ''; ?>>
                                                        <option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_subject'));?></option>
                                                        <?php if (!$tcb_api_dropdowns && !empty($subject)) {
                                                            foreach ($subject as $sub) {
                                                                $sel = ((int) $sub['id'] === (int) $sublst['subject_id']) ? ' selected' : '';
                                                                echo '<option value="' . (int) $sub['id'] . '"' . $sel . '>' . html_escape($sub['subject_name']) . '</option>';
                                                            }
                                                        } ?>
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
                                                    <?php if (!$tcb_api_dropdowns && !empty($subject)) {
                                                        foreach ($subject as $sub) {
                                                            echo '<option value="' . (int) $sub['id'] . '">' . html_escape($sub['subject_name']) . '</option>';
                                                        }
                                                    } ?>
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
                                                    <?php
                                                    if (!empty($teacher_user_id)) {
                                                        $self_teacher = $this->db_model->select_data('id,name', 'users use index (id)', array('id' => (int) $teacher_user_id), 1);
                                                        if (!empty($self_teacher)) {
                                                            echo '<option value="' . (int) $self_teacher[0]['id'] . '" selected>' . html_escape($self_teacher[0]['name']) . '</option>';
                                                        }
                                                    }
                                                    ?>
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
                            <div class="hide subjectArrayDiv"<?php echo $tcb_api_dropdowns ? ' data-api-load="subjects"' : ''; ?>><?php echo $tcb_api_dropdowns ? '[]' : json_encode(!empty($subject) ? $subject : array()); ?></div>
                            <button type="button" class="edu_admin_btn AssignBatchHeading res-380-bottom">+ <?php echo html_escape($this->common->languageTranslator('ltr_assign_benefit'));?></button>
							<button type="button" class="edu_admin_btn AssignSubBatch res-380-bottom">+ <?php echo html_escape($this->common->languageTranslator('ltr_assign_subject'));?></button>
                            <button type="button" class="edu_admin_btn addEditBatch res-380-bottom" data-type="<?php echo $type;?>"><?php echo !empty($batch_data)? html_escape($this->common->languageTranslator('ltr_update_batch')): html_escape($this->common->languageTranslator('ltr_save_batch'));?></button>
                        </div>
                    </div>
                </div>
            </form>

<script>
(function() {
    // Toggle Batch Price fields based on Batch Type selection
    function togglePriceFields() {
        var isPaid = $("input[name='batchType'][value='2']").is(':checked');
        var $priceFields = $('.batchPrice');

        if (isPaid) {
            $priceFields.show();
            $('#batchPrice, #batchOfferPrice').addClass('require');
        } else {
            $priceFields.hide();
            $('#batchPrice, #batchOfferPrice').removeClass('require');
        }
    }

    // Bind to radio button change
    $(document).on('change', "input[name='batchType']", function() {
        togglePriceFields();
    });

    // Initialize on document ready
    $(document).ready(function() {
        togglePriceFields();
    });
})();
</script>