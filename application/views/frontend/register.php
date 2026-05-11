<section class="edu-auth-page">
	<div class="container py-4 py-md-5">
		<div class="row justify-content-center">
			<div class="col-xl-6 col-lg-7 col-md-10">
				<div class="edu-auth-shell">
					<div class="edu-auth-main">
						<div class="edu-auth-main-inner">
							<nav class="edu-auth-breadcrumb" aria-label="Breadcrumb">
								<a href="<?php echo base_url(); ?>"><?php echo html_escape($this->common->languageTranslator('ltr_home')); ?></a>
								<span class="edu-auth-breadcrumb-sep" aria-hidden="true">/</span>
								<span><?php echo html_escape($this->common->languageTranslator('ltr_register')); ?></span>
							</nav>
							<h1 class="edu-auth-heading"><?php echo html_escape($title); ?></h1>
							<p class="edu-auth-sub"><?php echo html_escape($this->common->languageTranslator('ltr_p_register_continue')); ?></p>

							<div class="edu-auth-form-card edu_form_container_main edu_form_container withoutMapFrm" id="registerPageRoot">
								<p class="edu-auth-otp-intro mb-3" id="registerStepHint"><?php echo html_escape($this->common->languageTranslator('ltr_register')); ?> — step 1: enter your details. We will send an OTP to verify your mobile.</p>
								<div id="registerStep1">
									<div class="row">
										<div class="col-lg-6 col-md-6 col-sm-12 col-12">
											<div class="edu-auth-field">
												<label class="edu-auth-label" for="reg_name"><?php echo html_escape($this->common->languageTranslator('ltr_name')); ?></label>
												<div class="edu_field_holder">
													<input type="text" id="reg_name" class="edu_form_field edu-auth-input" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_name'));?> *" name="name" autocomplete="name">
												</div>
											</div>
										</div>
										<div class="col-lg-6 col-md-6 col-sm-12 col-12">
											<div class="edu-auth-field">
												<label class="edu-auth-label" for="reg_email"><?php echo html_escape($this->common->languageTranslator('ltr_email')); ?></label>
												<div class="edu_field_holder">
													<input type="text" id="reg_email" class="edu_form_field edu-auth-input" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_email'));?> *" autocomplete="email">
												</div>
											</div>
										</div>
										<div class="col-lg-6 col-md-6 col-sm-12 col-12">
											<div class="edu-auth-field">
												<label class="edu-auth-label" for="reg_mobile"><?php echo html_escape($this->common->languageTranslator('ltr_mobile')); ?></label>
												<div class="edu_field_holder">
													<input type="text" id="reg_mobile" class="edu_form_field edu-auth-input" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_mobile'));?> (10 digits) *" inputmode="numeric" maxlength="10" autocomplete="tel">
												</div>
											</div>
										</div>
										<div class="col-lg-6 col-md-6 col-sm-12 col-12">
											<div class="edu-auth-field">
												<label class="edu-auth-label" for="reg_user_type">Account type</label>
												<div class="edu_field_holder">
													<select id="reg_user_type" class="edu_form_field edu-auth-input" title="Account type">
														<option value="student"><?php echo html_escape($this->common->languageTranslator('ltr_student')); ?></option>
														<option value="teacher"><?php echo html_escape($this->common->languageTranslator('ltr_teacher')); ?></option>
													</select>
												</div>
											</div>
										</div>
										<div class="col-lg-12 col-md-12 col-sm-12 col-12">
											<div class="edu-auth-field">
												<label class="edu-auth-label" for="reg_password"><?php echo html_escape($this->common->languageTranslator('ltr_password')); ?></label>
												<div class="edu_field_holder edu-auth-password-wrap">
													<input type="password" id="reg_password" class="edu_form_field edu-auth-input" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_password'));?> *" autocomplete="new-password">
													<button type="button" class="edu-auth-toggle-pw reg_pw_toggle" aria-label="Toggle password visibility"><i class="fas fa-eye-slash"></i></button>
												</div>
											</div>
										</div>
									</div>
									<div class="edu-auth-actions">
										<p class="edu-auth-register m-0"><?php echo html_escape($this->common->languageTranslator('ltr_already_account'));?> <a href="<?php echo base_url('login');?>"><?php echo html_escape($this->common->languageTranslator('ltr_login_now'));?></a></p>
										<button type="button" class="edu-auth-btn edu-auth-btn--primary" id="reg_send_otp_btn"><?php echo html_escape($this->common->languageTranslator('ltr_register')); ?> &amp; OTP</button>
									</div>
								</div>

								<div id="registerStep2" class="d-none">
									<div class="edu-auth-field">
										<label class="edu-auth-label" for="reg_otp">OTP code</label>
										<div class="edu_field_holder">
											<input type="text" id="reg_otp" class="edu_form_field edu-auth-input" placeholder="Enter OTP *" inputmode="numeric" maxlength="8" autocomplete="one-time-code">
										</div>
									</div>
									<div class="edu-auth-actions">
										<button type="button" class="edu-auth-btn edu-auth-btn--ghost" id="reg_back_step_btn">Previous</button>
										<button type="button" class="edu-auth-btn edu-auth-btn--primary" id="reg_verify_otp_btn"><?php echo html_escape($this->common->languageTranslator('ltr_submit')); ?></button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
