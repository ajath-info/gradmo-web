<section class="edu_admin_content">
	<div class="edu_admin_right sectionHolder edu_notice_manage">
	    <div class="edu_admin_informationdiv edu_admin_informationdiv_new">
			<div class="container-fluid">
				<div class="row">
					<div class="col-lg-8 col-md-8 col-sm-8 col-12 padder0">
					    <?php 
            			// if((!empty($notice_data) && $notice_data>=1)){
            			?>
						<div class="edu_courses_section notic_mng noticeManage">
							<ul class="nav nav-tabs" role="tablist">
			

				    <li class="nav-item tabTableCls Both" data-url="ajaxcall/liveclass_table/zoom">
									<a class="nav-link active" href="#common" role="tab" data-toggle="tab" aria-selected="true">
										<span class="edu_tab_icons">
											<p><?php echo html_escape($this->common->languageTranslator('ltr_zoom_meeting'));?></p>
										</span>
									</a>
								</li>
								<li class="nav-item tabTableCls Student" data-url="ajaxcall/liveclass_table/jetsi">
									<a class="nav-link" href="#student" role="tab" data-toggle="tab" aria-selected="false">
										<span class="edu_tab_icons">
											<p><?php echo html_escape($this->common->languageTranslator('ltr_jetsi_meeting'));?></p>
										</span>
									</a>
								</li>
							</ul>
						</div>
						<?php 
						// }
						?>
					</div>
				</div>
			</div>
    		<?php 
    	    if(!empty($live_data) || !empty($jetsilive_data)){
    	    ?>
			<div class="container-fluid">
				<div class="row">
					<div class="col-lg-12 col-md-12 col-sm-12 col-12 padder0">
						<div class="edu_courses_section">
							<div class="tab-content">
							    <div role="tabpanel" class="tab-pane fade active in show" id="common">
									<div class="edu_courses_content ">
										<div class="col-lg-12 col-md-12 col-sm-12 col-12 padder0">
											<div class="edu_table_wrapper edu_main_wrapper ">
                                    			<div class="edu_admin_informationdiv sectionHolder">
                                    				<div class="tableFullWrapper"> 
                                        				<table class="server_datatable datatable table table-striped table-hover dt-responsive" data-url="ajaxcall/liveclass_table/zoom" cellspacing="0" width="100%">
                                        					<thead>
                                        					<tr>
                                    							<th>#</th>
                                    							<th><?php echo html_escape($this->common->languageTranslator('ltr_batch_name'));?></th>
                                    							<th ><?php echo html_escape($this->common->languageTranslator('ltr_join'));?></th>
                                    						</tr>
                                        					</thead>
                                        					<tbody>
                                        					</tbody> 
                                        				</table>
                                        			</div>
                                    			</div>
											</div>
										</div>
									</div>
								</div>
								<div role="tabpanel" class="tab-pane fade" id="student">
									<div class="edu_courses_content">
										<div class="col-lg-12 col-md-12 col-sm-12 col-12 padder0">
											<div class="edu_table_wrapper edu_main_wrapper ">
                                    			<div class="edu_admin_informationdiv sectionHolder">
                                    				<div class="tableFullWrapper">
                                        				<table class="server_datatable datatable table table-striped table-hover dt-responsive" cellspacing="0" width="100%">
                                        					<thead>
                                            					<tr>
                                        							<th>#</th>
                                        							<th><?php echo html_escape($this->common->languageTranslator('ltr_batch_name'));?></th>
                                        							<th><?php echo html_escape($this->common->languageTranslator('ltr_join'));?></th>
                                        						</tr>
                                        					</thead>
                                        					<tbody>
                                        					</tbody> 
                                        				</table>
                                        			</div>
                                    			</div>
											</div>
										</div>
									</div>	
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php }else{
		    echo '<section class="edu_admin_content">
                        <div class="edu_admin_right sectionHolder edu_add_users">
                            <div class="edu_admin_informationdiv edu_main_wrapper">
                                <div class="eac_text eac_page_re">'.html_escape($this->common->languageTranslator('ltr_live_no_data_ta')).'</div>
                            </div>
                        </div>
                    </section>';
		}
		?>
		</div>
	</div>
</section>


<!-- Pop Up Start  -->
<div id="classSettingModal" class="edu_popup_container mfp-hide">
    <div class="edu_popup_wrapper">
        <div class="edu_popup_inner">
            <h4 class="edu_sub_title" ><?php echo html_escape($this->common->languageTranslator('ltr_live_class'));?></h4>
            <form method="post" action="<?php echo base_url('teacher/start-class');?>" id="classForm" autocomplete="off" >
                <div class="row">
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_subjects'));?><sup>*</sup></label>
							<select class="form-control filter_subject edu_selectbox_with_search require " name="subject_id" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_subject'));?>">
										<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_sujects'));?></option>
										<?php
										if(!empty($subject)){
											foreach($subject as $sub){
												echo '<option value="'.$sub['id'].'">'.$sub['subject_name'].'</option>';
											}
										}
										?> 
									</select>	
    					</div>
    				</div>
    				<div class="col-lg-6 col-md-12 col-sm-12 col-12">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_chapter'));?><sup>*</sup></label>
							<select  class="form-control filter_chapter edu_selectbox_with_search require " name="chapter_id" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_chapter'));?>"> 
										<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_chapter'));?></option>
									</select>
    					</div>
    				</div>
    				
					<div class="col-lg-12 col-md-12 col-sm-12 col-12">
    					<div class="edu_btn_wrapper">
							<input type="hidden" name="live_class_id" id="live_class_id" value="">
							<input type="hidden" name="typeMeeting"  value="zoom">
							<input type="button" value="continue" class="edu_admin_btn liveClassSetting"  />
						</div>
    				</div>
				</div>
            </form>
        </div>
    </div>
</div>
<div id="JetsiclassSettingModal" class="edu_popup_container mfp-hide">
    <div class="edu_popup_wrapper">
        <div class="edu_popup_inner">
            <h4 class="edu_sub_title" ><?php echo html_escape($this->common->languageTranslator('ltr_live_class'));?></h4>
            <form method="post" action="<?php echo base_url('teacher-jetsi-meeting');?>" id="classForm" autocomplete="off" >
                <div class="row">
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_subjects'));?><sup>*</sup></label>
							<select class="form-control filter_subject edu_selectbox_with_search require " name="subject_id" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_subject'));?>">
										<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_sujects'));?></option>
										<?php
										if(!empty($subject)){
											foreach($subject as $sub){
												echo '<option value="'.$sub['id'].'">'.$sub['subject_name'].'</option>';
											}
										}
										?> 
									</select>	
    					</div>
    				</div>
    				<div class="col-lg-6 col-md-12 col-sm-12 col-12">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_chapter'));?><sup>*</sup></label>
							<select  class="form-control filter_chapter edu_selectbox_with_search require " name="chapter_id" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_chapter'));?>"> 
										<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_chapter'));?></option>
									</select>
    					</div>
    				</div>
    				
					<div class="col-lg-12 col-md-12 col-sm-12 col-12">
    					<div class="edu_btn_wrapper">
							<input type="hidden" name="live_class_id" id="jetsi_add_live_class" value="">
							<input type="hidden" name="typeMeeting"  value="jetsi">
							<input type="button" value="continue" class="edu_admin_btn liveClassSetting"  />
						</div>
    				</div>
				</div>
            </form>
        </div>
    </div>
</div>