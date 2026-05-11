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
							<span><?php echo html_escape($this->common->languageTranslator('ltr_login')); ?></span>
						</nav>
						<h1 class="edu-auth-heading"><?php echo html_escape($title); ?></h1>
						<p class="edu-auth-sub"><?php echo html_escape($this->common->languageTranslator('ltr_p_login_continue')); ?></p>

						<div class="edu-auth-form-card edu_form_container_main edu_form_container withoutMapFrm">
							<ul class="nav nav-tabs edu-auth-tabs mb-4" role="tablist">
								<li class="nav-item">
									<a class="nav-link active" id="tab-pw-link" data-toggle="tab" href="#tab-password" role="tab"><?php echo html_escape($this->common->languageTranslator('ltr_password')); ?></a>
								</li>
								<li class="nav-item">
									<a class="nav-link" id="tab-otp-link" data-toggle="tab" href="#tab-otp" role="tab">Mobile &amp; OTP</a>
								</li>
							</ul>
							<div class="tab-content edu-auth-tab-content">
								<div class="tab-pane fade show active" id="tab-password" role="tabpanel">
									<form class="form edu-auth-form" method="post" action="<?php echo base_url('login-password'); ?>" data-redirect="yes">
										<input type="hidden" name="login_redirect" value="index">
										<div class="edu-auth-field">
											<label class="edu-auth-label" for="password_login_user_type">Account type</label>
											<div class="edu_field_holder">
												<select class="edu_form_field require edu-auth-input" id="password_login_user_type" name="user_type" required>
													<option value="student">Student</option>
													<option value="teacher">Teacher</option>
												</select>
											</div>
										</div>
										<div class="edu-auth-field">
											<label class="edu-auth-label" for="email"><?php echo html_escape($this->common->languageTranslator('ltr_p_email')); ?></label>
											<div class="edu_field_holder">
												<input type="text" class="edu_form_field require edu-auth-input" id="email" name="email" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_p_email')); ?>" autocomplete="username" value="<?php echo (isset($_COOKIE['UML'])) ? html_escape(base64_decode(urldecode(base64_decode($_COOKIE['UML'])))) : ''; ?>">
											</div>
										</div>
										<div class="edu-auth-field">
											<label class="edu-auth-label" for="password_show"><?php echo html_escape($this->common->languageTranslator('ltr_password')); ?></label>
											<div class="edu_field_holder edu-auth-password-wrap">
												<input type="password" id="password_show" name="password" class="require edu_form_field edu-auth-input" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_password')); ?>" value="<?php echo (isset($_COOKIE['SSD'])) ? html_escape(base64_decode(urldecode(base64_decode($_COOKIE['SSD'])))) : ''; ?>" autocomplete="current-password">
												<button type="button" class="edu-auth-toggle-pw" onclick="myFunction()" aria-label="Toggle password visibility">
													<i class="fas fa-eye-slash hide_show"></i>
												</button>
											</div>
										</div>
										<div class="edu-auth-row-split">
											<div class="edu-auth-remember loginLinks checkbox_holder">
												<input type="checkbox" id="auth_remember" name="remember_me" <?php echo (isset($_COOKIE['UML'])) ? 'checked' : ''; ?>>
												<label for="auth_remember"><?php echo html_escape($this->common->languageTranslator('ltr_remember_me')); ?></label>
											</div>
											<a class="edu-auth-forgot" href="<?php echo base_url('forgot-password'); ?>"><?php echo html_escape($this->common->languageTranslator('ltr_forgot_p')); ?></a>
										</div>
										<div class="edu_field_holder verification_otp"></div>
										<div class="edu-auth-actions">
											<a class="edu-auth-btn edu-auth-btn--ghost" href="<?php echo base_url(); ?>"><?php echo html_escape($this->common->languageTranslator('ltr_back_to_home')); ?></a>
											<button class="edu-auth-btn edu-auth-btn--primary" id="auth_login" type="button" data-action="submitThisForm"><?php echo html_escape($this->common->languageTranslator('ltr_login')); ?></button>
										</div>
									</form>
								</div>
								<div class="tab-pane fade" id="tab-otp" role="tabpanel">
									<p class="edu-auth-otp-intro">Enter your registered 10-digit mobile number (OTP is sent to mobile only), request OTP, then enter the code to sign in.</p>
									<div class="edu-auth-field">
										<label class="edu-auth-label" for="otp_login_user_type">Account type</label>
										<div class="edu_field_holder">
											<select class="edu_form_field edu-auth-input" id="otp_login_user_type">
												<option value="student">Student</option>
												<option value="teacher">Teacher</option>
											</select>
										</div>
									</div>
									<div class="edu-auth-field">
										<label class="edu-auth-label" for="otp_login_mobile">Mobile</label>
										<div class="edu_field_holder">
											<input type="text" class="edu_form_field edu-auth-input" id="otp_login_mobile" placeholder="10-digit mobile" inputmode="numeric" maxlength="10" autocomplete="tel">
										</div>
									</div>
									<div class="edu-auth-otp-send-row mb-3">
										<button type="button" class="edu-auth-btn edu-auth-btn--outline" id="otp_send_btn">Send OTP</button>
									</div>
									<div class="d-none" id="otp_login_code_wrap">
										<div class="edu-auth-field">
											<label class="edu-auth-label" for="otp_login_code">OTP code</label>
											<div class="edu_field_holder">
												<input type="text" class="edu_form_field edu-auth-input" id="otp_login_code" placeholder="Enter OTP" inputmode="numeric" maxlength="8" autocomplete="one-time-code">
											</div>
										</div>
										<p class="edu-auth-resend mb-3" id="otp_resend_row">
											<button type="button" class="edu-auth-link-btn" id="otp_resend_btn" disabled>Resend OTP</button>
											<span class="text-muted ml-2" id="otp_resend_cooldown_text" aria-live="polite"></span>
										</p>
									</div>
									<div class="edu-auth-actions edu-auth-actions--otp">
										<a class="edu-auth-btn edu-auth-btn--ghost" href="<?php echo base_url(); ?>"><?php echo html_escape($this->common->languageTranslator('ltr_back_to_home')); ?></a>
										<div class="d-none" id="otp_login_verify_wrap">
											<button type="button" class="edu-auth-btn edu-auth-btn--primary" id="otp_verify_login_btn"><?php echo html_escape($this->common->languageTranslator('ltr_login')); ?></button>
										</div>
									</div>
								</div>
							</div>
							<p class="edu-auth-register"><?php echo html_escape($this->common->languageTranslator('ltr_dont_account')); ?> <a href="<?php echo base_url('register'); ?>"><?php echo html_escape($this->common->languageTranslator('ltr_register_now')); ?></a></p>
						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
