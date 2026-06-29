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
								<form class="form" method="post" id="forgotForm" onsubmit="return false;">
									<div class="edu-auth-field">
										<label class="edu-auth-label" for="forgot_email"><?php echo html_escape($this->common->languageTranslator('ltr_email_address')); ?></label>
										<div class="edu_field_holder">
											<input type="text" id="forgot_email" class="edu_form_field require edu-auth-input" name="email" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_email_address'));?>" autocomplete="off" data-valid="email" data-error="<?php echo html_escape($this->common->languageTranslator('ltr_valid_enter_your_email'));?>">
										</div>
									</div>
									<div class="edu-auth-actions">
										<a class="edu-auth-btn edu-auth-btn--ghost" href="<?php echo base_url('login');?>"><?php echo html_escape($this->common->languageTranslator('ltr_back_to_login'));?></a>
										<button class="edu-auth-btn edu-auth-btn--primary" id="auth_forgot" type="button"><?php echo html_escape($this->common->languageTranslator('ltr_submit'));?></button>
									</div>
								</form>
								<script>
								// Forgot password calls the shared API directly from the browser (website + mobile use one backend).
								(function () {
									var apiUrl = <?php echo json_encode(site_url('api/user/reset-password')); ?>;
									var loginUrl = <?php echo json_encode(base_url('login')); ?>;
									function notifyOk(m) { if (window.toastr) { toastr.success(m); } else { alert(m); } }
									function notifyErr(m) { if (window.toastr) { toastr.error(m); } else { alert(m); } }
									function submitForgot() {
										var inp = document.getElementById('forgot_email');
										var btn = document.getElementById('auth_forgot');
										var email = (inp.value || '').trim();
										if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
											notifyErr('Please enter a valid email.');
											return;
										}
										btn.disabled = true;
										var fd = new FormData();
										fd.append('email', email);
										fetch(apiUrl, {
											method: 'POST',
											headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
											body: fd
										}).then(function (r) { return r.json(); }).then(function (j) {
											btn.disabled = false;
											var ok = j && (j.status === true || j.status === 'true' || j.status === 1 || j.status === '1');
											if (ok) {
												notifyOk(j.msg || 'We\'ve sent you an email.');
												setTimeout(function () { location.href = loginUrl; }, 1200);
											} else {
												notifyErr((j && j.msg) ? j.msg : 'Something went wrong. Please try again.');
											}
										}).catch(function () {
											btn.disabled = false;
											notifyErr('Network error. Please try again.');
										});
									}
									document.addEventListener('DOMContentLoaded', function () {
										var inp = document.getElementById('forgot_email');
										var btn = document.getElementById('auth_forgot');
										if (btn) { btn.addEventListener('click', submitForgot); }
										if (inp) { inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); submitForgot(); } }); }
									});
								})();
								</script>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
