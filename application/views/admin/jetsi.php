<section class="edu_admin_content">
	<div class="edu_admin_right sectionHolder edu_batch_manager">
	    <?php 
	        ?>
	        <div class="edu_btn_wrapper sectionHolder padderBottom30 text-right res-left">
    		   <a href="#input_feilds_liveclass" class="edu_admin_btn openPopupLink addLiveclass"><i class="icofont-plus"></i><?php echo html_escape($this->common->languageTranslator('ltr_add_ljetsi_class')); ?></a>
    		</div>
	        <?php 
	    ?>
		<?php 
// 		print_r($live_data);
          
			if(!empty($live_data) && $live_data>=1){
			?>
		<div class="edu_main_wrapper edu_table_wrapper">
			<div class="edu_admin_informationdiv sectionHolder">
				<div class="tableFullWrapper">
    				<table class="server_datatable datatable table table-striped table-hover dt-responsive" cellspacing="0" width="100%" data-url="ajaxcall/live_class_jetsi_table">
    					<thead>
    						<tr>
    							<th>#</th>
    							<th><?php echo html_escape($this->common->languageTranslator('ltr_batch_name')); ?></th>
    							<!--<th><?php echo html_escape($this->common->languageTranslator('ltr_sdk_key')); ?></th>-->
    							<!--<th><?php echo html_escape($this->common->languageTranslator('ltr_sdk_secret')); ?></th>-->
    							<th><?php echo html_escape($this->common->languageTranslator('ltr_meeting_number')); ?></th>
    							<!--<th><?php echo html_escape($this->common->languageTranslator('ltr_password')); ?></th>-->
    							<th class="no-sort"><?php echo html_escape($this->common->languageTranslator('ltr_action')); ?></th>
    							<th class="no-sort"><?php echo html_escape($this->common->languageTranslator('ltr_added_by')); ?></th>
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
                                <div class="eac_text eac_page_re">'.html_escape($this->common->languageTranslator('ltr_live_no_data')).'</div>
                            </div>
                        </div>
                    </section>';
		} ?>
	</div>
</section>

<!-- Pop Up Start  -->
<div id="input_feilds_liveclass" class="edu_popup_container mfp-hide">
    <div class="edu_popup_wrapper">
        <div class="edu_popup_inner">
            <h4 class="edu_sub_title" id="classModalLabel"><?php echo html_escape($this->common->languageTranslator('ltr_add_live_class')); ?></h4>
            <form method="post">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
							<label><?php echo html_escape($this->common->languageTranslator('ltr_batch')); ?><sup>*</sup></label>
							<select class="form-control require edu_selectbox_with_search" name="batch" id="batch">
								<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_batch')); ?></option>
                                            <?php if(!empty($batch)){
                                                foreach($batch as $batch){
                                                    echo '<option value="'.$batch['id'].'">'.$batch['batch_name'].'</option>';    
                                                }
                                            } ?>
							</select>
						</div>
    				</div>
      <!--              <div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">-->
    		<!--			<div class="form-group">-->
						<!--	<label><?php echo html_escape($this->common->languageTranslator('ltr_sdk_key')); ?><sup>*</sup></label>-->
						<!--	<input type="text" class="form-control require" name="zoom_api_key" id="zoom_api_key" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_zoom_api_key')); ?>">-->
						<!--</div>-->
    		<!--		</div>-->
    		<!--		<div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">-->
    		<!--			<div class="form-group">-->
						<!--	<label><?php echo html_escape($this->common->languageTranslator('ltr_sdk_secret')); ?><sup>*</sup></label>-->
						<!--	<input type="text" class="form-control require" name="zoom_api_secret" id="zoom_api_secret" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_zoom_api_secret')); ?>">-->
						<!--</div>-->
    		<!--		</div>-->
    				<div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
							<label><?php echo html_escape($this->common->languageTranslator('ltr_meeting_number')); ?><sup>*</sup></label>
							<input type="text" class="form-control require" name="meeting_number" value="mid=<?php echo rand(1111111111,9999999999);?>" readonly id="meeting_number" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_meeting_number')); ?>">
						</div>
    				</div>
    				<div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20 ">
    					<div class="form-group hidden">
							<label hidden><?php echo html_escape($this->common->languageTranslator('ltr_password')); ?><sup>*</sup></label>
							<input type="hidden" class="form-control" name="password" id="password" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_password')); ?>">
						</div>
    				</div>
				 
    				<div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="edu_btn_wrapper">
    					    <input type="hidden" name="live_class_id" id="live_class_id" value="">
							<input type="button" value="<?php echo html_escape($this->common->languageTranslator('ltr_save')); ?>" class="edu_admin_btn addjetsiClass " data-type="add" />
							 <input type="button" value="Genrate Meeting Id " class="edu_admin_btn genrateMeetingId"/>
						</div>
    				</div>
				</div>
            </form>
        </div>
    </div>
</div>



<!-- Pop Up Start  -->
<div id="classSettingModal" class="edu_popup_container mfp-hide">
    <div class="edu_popup_wrapper">
        <div class="edu_popup_inner">
            <h4 class="edu_sub_title"><?php echo html_escape($this->common->languageTranslator('ltr_live_class')); ?></h4>
            <form method="post"  action="<?php echo base_url('jetsi-meeting');?>" id="classForm" autocomplete="off">
                <div class="row">
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_subject')); ?><sup>*</sup></label>
							<select class="form-control filter_subject edu_selectbox_with_search require " name="subject_id" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_subject')); ?>" id="filter_subject">
										<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_subjects')); ?></option>
										 
									</select>	
    					</div>
    				</div>
    				<div class="col-lg-6 col-md-12 col-sm-12 col-12">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_chapter')); ?><sup>*</sup></label>
							<select  class="form-control filter_chapter edu_selectbox_with_search require " name="chapter_id" data-placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_select_chapter')); ?>"> 
										<option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_chapter')); ?></option>
									</select>
    					</div>
    				</div>
    				
					<div class="col-lg-12 col-md-12 col-sm-12 col-12">
    					<div class="edu_btn_wrapper">
							<input type="hidden" name="live_class_id" id="live_class_id" value="">
						<input type="button" value="<?php echo html_escape($this->common->languageTranslator('ltr_continue')); ?>" class="edu_admin_btn liveClassSetting"  />
						</div>
    				</div>
				</div>
            </form>
        </div>
    </div>
</div>