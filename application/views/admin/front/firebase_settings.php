<section class="edu_admin_content">
	<div class="edu_admin_right sectionHolder edu_siteSetting_wrapper">
	    <div class="pxn_admin_informationdiv edu_main_wrapper">
			<form class="pxn_amin form" enctype="multipart/form-data" method="post">
				<div class="edu_site_setting_wrap edu_from_wrapper">
					<div class="row">
						<div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
						    <div class="form-group">
								<label><?php echo html_escape($this->common->languageTranslator('ltr_firebase_service_account'));?><sup>*</sup></label>
								<textarea class="form-control" name="firebase_service_account_json" rows="10" placeholder='{ "type": "service_account", "project_id": "...", "private_key": "...", "client_email": "..." }'><?php if(!empty($firebase_service_account_json)){ echo html_escape($firebase_service_account_json); } ?></textarea>
								<p class="edu_top_10"><?php echo html_escape($this->common->languageTranslator('ltr_firebase_service_account_note'));?></p>
							</div>
						</div>

						<div class="edu_btn_wrapper">
							<div class="col-lg-12 col-md-12 col-sm-12 col-12" > 
								<button type="button" class="btn btn-primary updateFirebaseDetails"><?php echo html_escape($this->common->languageTranslator('ltr_save'));?></button>
								
								<!--<button type="button" class="btn btn-primary updateTestEmailDetails"><?php echo html_escape($this->common->languageTranslator('ltr_test_email'));?></button>-->
								
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>		
	</div>
</section>