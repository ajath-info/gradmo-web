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
								<span><?php echo html_escape($this->common->languageTranslator('ltr_reset_password') ?: 'Reset password'); ?></span>
							</nav>
							<h1 class="edu-auth-heading"><?php echo html_escape($this->common->languageTranslator('ltr_reset_password') ?: 'Reset password'); ?></h1>

							<?php if (empty($reset_token_valid)) { ?>
								<p class="edu-auth-sub">This password reset link is invalid or has expired.</p>
								<div class="edu-auth-form-card edu_form_container_main edu_form_container withoutMapFrm">
									<div class="edu-auth-actions">
										<a class="edu-auth-btn edu-auth-btn--ghost" href="<?php echo base_url('forgot-password'); ?>">Request a new link</a>
										<a class="edu-auth-btn edu-auth-btn--primary" href="<?php echo base_url('login'); ?>">Back to Login</a>
									</div>
								</div>
							<?php } else { ?>
								<p class="edu-auth-sub">Enter your new password below.</p>
								<div class="edu-auth-form-card edu_form_container_main edu_form_container withoutMapFrm">
									<form class="form" method="post" id="resetForm" onsubmit="return false;">
										<div class="edu-auth-field">
											<label class="edu-auth-label" for="new_password">New password</label>
											<div class="edu_field_holder">
												<input type="password" id="new_password" class="edu_form_field require edu-auth-input" name="password" placeholder="New password" autocomplete="new-password">
											</div>
										</div>
										<div class="edu-auth-field">
											<label class="edu-auth-label" for="confirm_password">Confirm password</label>
											<div class="edu_field_holder">
												<input type="password" id="confirm_password" class="edu_form_field require edu-auth-input" name="confirm_password" placeholder="Confirm password" autocomplete="new-password">
											</div>
										</div>
										<div class="edu-auth-actions">
											<a class="edu-auth-btn edu-auth-btn--ghost" href="<?php echo base_url('login'); ?>">Back to Login</a>
											<button class="edu-auth-btn edu-auth-btn--primary" id="reset_submit" type="button">Update password</button>
										</div>
									</form>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<?php if (!empty($reset_token_valid)) { ?>
<script>
// Reset password posts directly to the shared API (website + mobile use one backend).
(function () {
	var apiUrl = <?php echo json_encode($set_new_password_url); ?>;
	var loginUrl = <?php echo json_encode($login_url); ?>;
	var token = <?php echo json_encode($reset_token); ?>;
	function notifyOk(m) { if (window.toastr) { toastr.success(m); } else { alert(m); } }
	function notifyErr(m) { if (window.toastr) { toastr.error(m); } else { alert(m); } }
	function submitReset() {
		var p = (document.getElementById('new_password').value || '');
		var c = (document.getElementById('confirm_password').value || '');
		var btn = document.getElementById('reset_submit');
		if (p.length < 4) { notifyErr('Password must be at least 4 characters.'); return; }
		if (p !== c) { notifyErr('Passwords do not match.'); return; }
		btn.disabled = true;
		fetch(apiUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
			body: JSON.stringify({ token: token, password: p })
		}).then(function (r) { return r.json(); }).then(function (j) {
			btn.disabled = false;
			var ok = j && (j.status === true || j.status === 'true' || j.status === 1 || j.status === '1');
			if (ok) {
				notifyOk(j.msg || 'Password updated.');
				setTimeout(function () { location.href = loginUrl; }, 1200);
			} else {
				notifyErr((j && j.msg) ? j.msg : 'Could not update password.');
			}
		}).catch(function () {
			btn.disabled = false;
			notifyErr('Network error. Please try again.');
		});
	}
	document.addEventListener('DOMContentLoaded', function () {
		var btn = document.getElementById('reset_submit');
		var c = document.getElementById('confirm_password');
		if (btn) { btn.addEventListener('click', submitReset); }
		if (c) { c.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); submitReset(); } }); }
	});
})();
</script>
<?php } ?>
