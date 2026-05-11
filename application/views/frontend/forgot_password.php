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
								<span><?php echo html_escape($this->common->languageTranslator('ltr_forgot_password')); ?></span>
							</nav>
							<h1 class="edu-auth-heading"><?php echo html_escape($title); ?></h1>
							<p class="edu-auth-sub"><?php echo html_escape($this->common->languageTranslator('ltr_forgot_password')); ?></p>

							<div class="edu-auth-form-card edu_form_container_main edu_form_container withoutMapFrm">
								<form class="form" method="post" action="<?php echo base_url('front_ajax/reset_password'); ?>" data-redirect="yes" id="forgotForm">
									<div class="edu-auth-field">
										<label class="edu-auth-label" for="forgot_email"><?php echo html_escape($this->common->languageTranslator('ltr_email_address')); ?></label>
										<div class="edu_field_holder">
											<input type="text" id="forgot_email" class="edu_form_field require edu-auth-input" name="email" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_email_address'));?>" autocomplete="off" data-valid="email" data-error="<?php echo html_escape($this->common->languageTranslator('ltr_valid_enter_your_email'));?>">
										</div>
									</div>
									<div class="edu-auth-actions">
										<a class="edu-auth-btn edu-auth-btn--ghost" href="<?php echo base_url('login');?>"><?php echo html_escape($this->common->languageTranslator('ltr_back_to_login'));?></a>
										<button class="edu-auth-btn edu-auth-btn--primary" id="auth_forgot" type="button" data-action="submitThisForm"><?php echo html_escape($this->common->languageTranslator('ltr_submit'));?></button>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
