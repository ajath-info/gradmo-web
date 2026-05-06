<!----- Page Title Start ----->
<section class="edu_page_title_wrapper">
	<div class="container">
		<div class="row">
			<div class="col-lg-12 col-md-12 col-sm-12 col-12 text-center">
				<div class="edu_page_title_text">
					<h1><?php echo html_escape($title); ?></h1>
					<ul>
						<li><a href="<?php echo base_url();?>"><?php echo html_escape($this->common->languageTranslator('ltr_home'));?></a></li>
						<li><a href="javascript:void(0);"><?php echo html_escape($this->common->languageTranslator('ltr_register'));?></a></li>
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
				<div class="edu_form_container_main edu_form_container withoutMapFrm" id="registerPageRoot">
					<h4><?php echo html_escape($this->common->languageTranslator('ltr_p_register_continue'));?></h4>
					<p class="text-muted small mb-3" id="registerStepHint"><?php echo html_escape($this->common->languageTranslator('ltr_register')); ?> — step 1: enter your details. We will send an OTP to verify your mobile.</p>
					<div id="registerStep1">
						<div class="row">
							<div class="col-lg-6 col-md-6 col-sm-12 col-12">
								<div class="edu_field_holder">
									<input type="text" id="reg_name" class="edu_form_field" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_name'));?> *" name="name" autocomplete="name">
								</div>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-12 col-12">
								<div class="edu_field_holder">
									<input type="text" id="reg_email" class="edu_form_field" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_email'));?> *" autocomplete="email">
								</div>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-12 col-12">
								<div class="edu_field_holder">
									<input type="text" id="reg_mobile" class="edu_form_field" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_mobile'));?> (10 digits) *" inputmode="numeric" maxlength="10" autocomplete="tel">
								</div>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-12 col-12">
								<div class="edu_field_holder">
									<select id="reg_user_type" class="edu_form_field" title="Account type">
										<option value="student"><?php echo html_escape($this->common->languageTranslator('ltr_student')); ?></option>
										<option value="teacher"><?php echo html_escape($this->common->languageTranslator('ltr_teacher')); ?></option>
										<!-- <option value="institute">Institute</option> -->
									</select>
								</div>
							</div>
							<div class="col-lg-12 col-md-12 col-sm-12 col-12">
								<div class="edu_field_holder" style="position:relative;">
									<input type="password" id="reg_password" class="edu_form_field" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_password'));?> *" style="padding-right:44px;" autocomplete="new-password">
									<i class="fas fa-eye-slash reg_pw_toggle" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer;"></i>
								</div>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-12 col-12 mt-2">
								<p class="edu-register-account mb-0"><?php echo html_escape($this->common->languageTranslator('ltr_already_account'));?><span><a href="<?php echo base_url('login');?>"> <?php echo html_escape($this->common->languageTranslator('ltr_login_now'));?></a></span></p>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-12 col-12 text-md-right mt-2">
								<button type="button" class="edu_btn edu_btn_black" id="reg_send_otp_btn"><?php echo html_escape($this->common->languageTranslator('ltr_register')); ?> &amp; OTP</button>
							</div>
						</div>
					</div>
					<div id="registerStep2" class="d-none">
						<div class="row">
							<div class="col-lg-12 col-md-12 col-sm-12 col-12">
								<div class="edu_field_holder">
									<input type="text" id="reg_otp" class="edu_form_field" placeholder="Enter OTP *" inputmode="numeric" maxlength="8" autocomplete="one-time-code">
								</div>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-12 col-12 mt-2">
								<button type="button" class="edu_btn edu_btn_black" id="reg_back_step_btn">Previous</button>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-12 col-12 text-md-right mt-2">
								<button type="button" class="edu_btn" id="reg_verify_otp_btn"><?php echo html_escape($this->common->languageTranslator('ltr_submit')); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
