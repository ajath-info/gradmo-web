<section class="edu_admin_content">
	<div class="edu_admin_right sectionHolder edu_siteSetting_wrapper">
	    <div class="pxn_admin_informationdiv edu_main_wrapper">
			<form class="pxn_amin form" enctype="multipart/form-data" method="post">
				<div class="edu_site_setting_wrap edu_from_wrapper">
					<div class="row">
						<div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">
						    <div class="form-group">
								<label><?php echo html_escape($this->common->languageTranslator('ltr_msg91_authkey'));?><sup>*</sup></label>
								<input type="text" class="form-control require" name="msg91_authkey" value="<?php echo !empty($msg91_authkey)? html_escape($msg91_authkey):''; ?>" placeholder="MSG91 Auth Key">
							</div>
						</div>
						<div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">
						    <div class="form-group">
								<label><?php echo html_escape($this->common->languageTranslator('ltr_msg91_sender'));?></label>
								<input type="text" class="form-control" name="msg91_sender" value="<?php echo !empty($msg91_sender)? html_escape($msg91_sender):''; ?>" placeholder="Sender ID (e.g. GRADMO)">
							</div>
						</div>
						<div class="col-lg-6 col-md-6 col-sm-12 col-12 edu_bottom_20">
						    <div class="form-group">
								<label><?php echo html_escape($this->common->languageTranslator('ltr_msg91_template_id'));?></label>
								<input type="text" class="form-control" name="msg91_template_id" value="<?php echo !empty($msg91_template_id)? html_escape($msg91_template_id):''; ?>" placeholder="DLT Flow Template ID (optional)">
							</div>
						</div>
						<div class="col-lg-3 col-md-3 col-sm-12 col-12 edu_bottom_20">
						    <div class="form-group">
								<label><?php echo html_escape($this->common->languageTranslator('ltr_msg91_route'));?></label>
								<input type="text" class="form-control" name="msg91_route" value="<?php echo isset($msg91_route) && $msg91_route!=='' ? html_escape($msg91_route):'4'; ?>" placeholder="4">
							</div>
						</div>
						<div class="col-lg-3 col-md-3 col-sm-12 col-12 edu_bottom_20">
						    <div class="form-group">
								<label><?php echo html_escape($this->common->languageTranslator('ltr_msg91_country'));?></label>
								<input type="text" class="form-control" name="msg91_country" value="<?php echo isset($msg91_country) && $msg91_country!=='' ? html_escape($msg91_country):'91'; ?>" placeholder="91">
							</div>
						</div>

						<div class="edu_btn_wrapper">
							<div class="col-lg-12 col-md-12 col-sm-12 col-12" >
								<button type="button" class="btn btn-primary updateSmsDetails"><?php echo html_escape($this->common->languageTranslator('ltr_save'));?></button>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</section>
