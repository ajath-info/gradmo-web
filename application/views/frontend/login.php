<!----- Page Title Start ----->
<section class="edu_page_title_wrapper">
	<div class="container">
		<div class="row">
			<div class="col-lg-12 col-md-12 col-sm-12 col-12 text-center">
				<div class="edu_page_title_text">
					<h1><?php echo html_escape($title); ?></h1>
					<ul>
						<li><a href="<?php echo base_url();?>"><?php echo html_escape($this->common->languageTranslator('ltr_home'));?></a></li>
						<li><a href="javascript:void(0);"><?php echo html_escape($this->common->languageTranslator('ltr_login'));?></a></li>
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
					<ul class="nav nav-tabs mb-3 justify-content-center" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" id="tab-pw-link" data-toggle="tab" href="#tab-password" role="tab"><?php echo html_escape($this->common->languageTranslator('ltr_password')); ?></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" id="tab-otp-link" data-toggle="tab" href="#tab-otp" role="tab">Mobile &amp; OTP</a>
						</li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane fade show active" id="tab-password" role="tabpanel">
							<h4><?php echo html_escape($this->common->languageTranslator('ltr_p_login_continue'));?></h4>
							<form class="form" method="post" action="<?php echo base_url('login-password'); ?>" data-redirect="yes">
								<input type="hidden" name="login_redirect" value="index">
								<div class="row">
									<div class="col-lg-12 col-md-12 col-sm-12 col-12">
										<div class="edu_field_holder">
											<select class="edu_form_field require" id="password_login_user_type" name="user_type" required>
												<option value="student">Student</option>
												<option value="teacher">Teacher</option>
											</select>
										</div>
									</div>
									<div class="col-lg-12 col-md-12 col-sm-12 col-12">
										<div class="edu_field_holder">
											<input type="text" class="edu_form_field require" id="email" name="email" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_p_email'));?>" autocomplete="username" value="<?php echo(isset($_COOKIE['UML'])) ? html_escape(base64_decode(urldecode(base64_decode($_COOKIE['UML'])))) : ''; ?>">
										</div>
									</div>
									<div class="col-lg-12 col-md-12 col-sm-12 col-12">
										<div class="edu_field_holder" style="position:relative;">
											<input type="password" id="password_show" name="password" class="require edu_form_field" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_password'));?>" value="<?php echo(isset($_COOKIE['SSD'])) ? html_escape(base64_decode(urldecode(base64_decode($_COOKIE['SSD'])))) : ''; ?>" style="padding-right:44px;" autocomplete="current-password">
											<i class="fas fa-eye-slash hide_show" onclick="myFunction()" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer;"></i>
										</div>
									</div>
									<div class="col-lg-6 col-md-6 col-sm-12 col-12">
										<div class="loginLinks checkbox_holder mt-2">
											<input type="checkbox" id="auth_remember" name="remember_me" <?php echo(isset($_COOKIE['UML'])) ? 'checked':''; ?>>
											<label for="auth_remember"><?php echo html_escape($this->common->languageTranslator('ltr_remember_me'));?></label>
										</div>
									</div>
									<div class="col-lg-6 col-md-6 col-sm-12 col-12 text-md-right">
										<div class="loginLinks mt-2">
											<a class="form_link" href="<?php echo base_url('forgot-password');?>"><?php echo html_escape($this->common->languageTranslator('ltr_forgot_p'));?></a>
										</div>
									</div>
									<div class="edu_field_holder verification_otp col-12"></div>
									<div class="col-lg-6 col-md-6 col-sm-12 col-12 mt-3">
										<a class="edu_btn" href="<?php echo base_url();?>"><?php echo html_escape($this->common->languageTranslator('ltr_back_to_home'));?></a>
									</div>
									<div class="col-lg-6 col-md-6 col-sm-12 col-12 text-md-right mt-3">
										<button class="edu_btn edu_btn_black" id="auth_login" type="button" data-action="submitThisForm"><?php echo html_escape($this->common->languageTranslator('ltr_login'));?></button>
									</div>
								</div>
							</form>
						</div>
						<div class="tab-pane fade" id="tab-otp" role="tabpanel">
							<h4>OTP login</h4>
							<p class="text-muted small mb-3">Enter your registered 10-digit mobile number (OTP is sent to mobile only), request OTP, then enter the code to sign in.</p>
							<div class="row">
								<div class="col-lg-12 col-md-12 col-sm-12 col-12">
									<div class="edu_field_holder">
										<select class="edu_form_field" id="otp_login_user_type">
											<option value="student">Student</option>
											<option value="teacher">Teacher</option>
										</select>
									</div>
								</div>
								<div class="col-lg-12 col-md-12 col-sm-12 col-12">
									<div class="edu_field_holder">
										<input type="text" class="edu_form_field" id="otp_login_mobile" placeholder="Mobile (10 digits)" inputmode="numeric" maxlength="10" autocomplete="tel">
									</div>
								</div>
								<div class="col-lg-6 col-md-6 col-sm-12 col-12 mt-2">
									<button type="button" class="edu_btn" id="otp_send_btn">Send OTP</button>
								</div>
								<div class="col-lg-12 col-md-12 col-sm-12 col-12 mt-3 d-none" id="otp_login_code_wrap">
									<div class="edu_field_holder">
										<input type="text" class="edu_form_field" id="otp_login_code" placeholder="Enter OTP" inputmode="numeric" maxlength="8" autocomplete="one-time-code">
									</div>
									<p class="small mb-0 mt-2" id="otp_resend_row">
										<button type="button" class="btn btn-link p-0 align-baseline" id="otp_resend_btn" disabled style="font-size:inherit;">Resend OTP</button>
										<span class="text-muted ml-1" id="otp_resend_cooldown_text" aria-live="polite"></span>
									</p>
								</div>
								<div class="col-lg-6 col-md-6 col-sm-12 col-12 mt-2">
									<a class="edu_btn" href="<?php echo base_url();?>"><?php echo html_escape($this->common->languageTranslator('ltr_back_to_home'));?></a>
								</div>
								<div class="col-lg-6 col-md-6 col-sm-12 col-12 text-md-right mt-2 d-none" id="otp_login_verify_wrap">
									<button type="button" class="edu_btn edu_btn_black" id="otp_verify_login_btn"><?php echo html_escape($this->common->languageTranslator('ltr_login'));?></button>
								</div>
							</div>
						</div>
					</div>
					<div class="col-12 mt-3 px-0">
						<p class="edu-register-account mb-0"><?php echo html_escape($this->common->languageTranslator('ltr_dont_account'));?><span><a href="<?php echo base_url('register');?>"> <?php echo html_escape($this->common->languageTranslator('ltr_register_now'));?></a></span></p>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
