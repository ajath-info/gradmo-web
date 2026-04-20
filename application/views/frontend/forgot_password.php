<!----- Page Title Start ----->
<section class="edu_page_title_wrapper">
	<div class="container">
		<div class="row">
			<div class="col-lg-12 col-md-12 col-sm-12 col-12 text-center">
				<div class="edu_page_title_text">
					<h1><?php echo html_escape($title); ?></h1>
					<ul>
						<li><a href="<?php echo base_url();?>"><?php echo html_escape($this->common->languageTranslator('ltr_home'));?></a></li>
						<li><a href="javascript:void(0);"><?php echo html_escape($this->common->languageTranslator('ltr_forgot_password'));?></a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</section>
<section class="edu_form_wrapper enroll-wrapper contactpage">
	<div class="container">
		<div class="row">
			<div class="col-lg-12 col-md-12 col-sm-12 col-12 p-0">
				<div class="edu_form_container_main edu_form_container withoutMapFrm">
					<h4><?php echo html_escape($this->common->languageTranslator('ltr_forgot_password'));?></h4>
					<form class="form" method="post" action="<?php echo base_url('front_ajax/reset_password'); ?>" data-redirect="yes" id="forgotForm">
						<div class="row">
							<div class="col-lg-12 col-md-12 col-sm-12 col-12">
								<div class="edu_field_holder">
									<input type="text" class="edu_form_field require" name="email" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_email_address'));?>" autocomplete="off" data-valid="email" data-error="<?php echo html_escape($this->common->languageTranslator('ltr_valid_enter_your_email'));?>">
								</div>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-12 col-12 mt-3">
								<a class="edu_btn edu_btn_black" href="<?php echo base_url('login');?>"><?php echo html_escape($this->common->languageTranslator('ltr_back_to_login'));?></a>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-12 col-12 text-md-right mt-3">
								<button class="edu_btn" id="auth_forgot" type="button" data-action="submitThisForm"><?php echo html_escape($this->common->languageTranslator('ltr_submit'));?></button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</section>
