<section class="edu_admin_content">
	<div class="edu_admin_right sectionHolder edu_batch_manager">
	    <div class="edu_btn_wrapper sectionHolder padderBottom30 text-right res-left">
    		   <a href="#input_feilds_zoom_credentials" class="edu_admin_btn openPopupLink addLiveclass"><i class="icofont-plus"></i><?php echo html_escape($this->common->languageTranslator('ltr_add_live_class')); ?> / Zoom API</a>
    		</div>
		<?php
			$zc = !empty($zoom_credentials[0]) ? $zoom_credentials[0] : array();
			$has_zoom = !empty($zoom_sdk_ready);
		?>
		<?php if ($has_zoom) { ?>
		<div class="edu_main_wrapper edu_table_wrapper">
			<div class="edu_admin_informationdiv sectionHolder">
				<p class="padderBottom15">Zoom credentials are stored in <strong>zoom_api_credentials</strong> (one row for the whole site). Per-batch meetings are created automatically in <strong>batch_zoom_meetings</strong> when a teacher starts a live class.</p>
				<div class="tableFullWrapper">
    				<table class="server_datatable datatable table table-striped table-hover dt-responsive" cellspacing="0" width="100%" data-url="ajaxcall/zoom_api_credentials_table">
    					<thead>
    						<tr>
    							<th>#</th>
    							<th>Scope</th>
    							<th><?php echo html_escape($this->common->languageTranslator('ltr_sdk_key')); ?></th>
    							<th><?php echo html_escape($this->common->languageTranslator('ltr_sdk_secret')); ?></th>
    							<th>Meetings</th>
    							<th>S2S / Host</th>
    							<th class="no-sort"><?php echo html_escape($this->common->languageTranslator('ltr_action')); ?></th>
    							<th class="no-sort">Table</th>
    						</tr>
    					</thead>
    					<tbody></tbody>
    				</table>
    			</div>
			</div>
		</div>
		<?php } else {
		    echo '<section class="edu_admin_content">
                        <div class="edu_admin_right sectionHolder edu_add_users">
                            <div class="edu_admin_informationdiv edu_main_wrapper">
                                <div class="eac_text eac_page_re">Configure Zoom in the button above: Meeting SDK (Client ID + Secret) and Server-to-Server OAuth in <code>zoom_api_credentials</code>. Per-batch meetings are stored in <code>batch_zoom_meetings</code>.</div>
                            </div>
                        </div>
                    </section>';
		} ?>
	</div>
</section>

<div id="input_feilds_zoom_credentials" class="edu_popup_container mfp-hide">
    <div class="edu_popup_wrapper">
        <div class="edu_popup_inner">
            <h4 class="edu_sub_title" id="classModalLabel">Zoom API credentials (zoom_api_credentials)</h4>
            <form method="post" id="zoom_credentials_form">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <p><small>Meeting SDK: Zoom General app → Development → Client ID &amp; Secret. S2S: Server-to-Server OAuth for creating meetings.</small></p>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
							<label>Meeting SDK Client ID (meeting_sdk_key)<sup>*</sup></label>
							<input type="text" class="form-control require" name="meeting_sdk_key" id="meeting_sdk_key" value="<?php echo html_escape(isset($zc['meeting_sdk_key']) ? $zc['meeting_sdk_key'] : ''); ?>">
						</div>
    				</div>
    				<div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
							<label>Meeting SDK Client Secret (meeting_sdk_secret)<sup>*</sup></label>
							<input type="text" class="form-control require" name="meeting_sdk_secret" id="meeting_sdk_secret" value="<?php echo html_escape(isset($zc['meeting_sdk_secret']) ? $zc['meeting_sdk_secret'] : ''); ?>">
						</div>
    				</div>
    				<div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
							<label>S2S Account ID</label>
							<input type="text" class="form-control" name="s2s_account_id" value="<?php echo html_escape(isset($zc['s2s_account_id']) ? $zc['s2s_account_id'] : ''); ?>">
						</div>
    				</div>
    				<div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
							<label>S2S Client ID</label>
							<input type="text" class="form-control" name="s2s_client_id" value="<?php echo html_escape(isset($zc['s2s_client_id']) ? $zc['s2s_client_id'] : ''); ?>">
						</div>
    				</div>
    				<div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
							<label>S2S Client Secret</label>
							<input type="text" class="form-control" name="s2s_client_secret" value="<?php echo html_escape(isset($zc['s2s_client_secret']) ? $zc['s2s_client_secret'] : ''); ?>">
						</div>
    				</div>
    				<div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
							<label>Zoom host email</label>
							<input type="text" class="form-control" name="zoom_host_email" value="<?php echo html_escape(isset($zc['zoom_host_email']) ? $zc['zoom_host_email'] : ''); ?>">
						</div>
    				</div>
    				<div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
							<label>Zoom host user ID (optional)</label>
							<input type="text" class="form-control" name="zoom_host_user_id" value="<?php echo html_escape(isset($zc['zoom_host_user_id']) ? $zc['zoom_host_user_id'] : ''); ?>">
						</div>
    				</div>
    				<div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="edu_btn_wrapper">
    					    <input type="hidden" name="live_class_id" id="live_class_id" value="<?php echo html_escape(isset($zc['id']) ? $zc['id'] : '1'); ?>">
							<input type="button" value="<?php echo html_escape($this->common->languageTranslator('ltr_save')); ?>" class="edu_admin_btn addLiveClassSetting" data-type="edit" />
						</div>
    				</div>
				</div>
            </form>
        </div>
    </div>
</div>

<div id="classSettingModal" class="edu_popup_container mfp-hide">
    <div class="edu_popup_wrapper">
        <div class="edu_popup_inner">
            <h4 class="edu_sub_title" ><?php echo html_escape($this->common->languageTranslator('ltr_live_class')); ?></h4>
            <form method="post" action="<?php echo base_url('admin/start-class');?>" id="classForm" autocomplete="off" >
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
							<input type="hidden" name="batch_id" id="live_class_batch_id" value="">
							<input type="hidden" name="live_class_id" id="live_class_id" value="">
							<input type="button" value="<?php echo html_escape($this->common->languageTranslator('ltr_continue')); ?>" class="edu_admin_btn liveClassSetting"  />
						</div>
    				</div>
				</div>
            </form>
        </div>
    </div>
</div>
