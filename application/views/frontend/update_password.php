<section class="edu_page_title_wrapper">
	<div class="container">
		<div class="row">
			<div class="col-12 text-center">
				<div class="edu_page_title_text">
					<h1>Update Password</h1>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="edu_form_wrapper enroll-wrapper contactpage">
	<div class="container">
		<div class="row">
			<div class="col-lg-8 col-md-10 col-sm-12 col-12 mx-auto">
				<div class="edu_form_container_main edu_form_container withoutMapFrm">
					<h4>Set new password by mobile</h4>
					<div class="row">
						<div class="col-md-6"><div class="edu_field_holder"><input id="upp_mobile" class="edu_form_field" type="text" placeholder="Mobile (10 digits)" maxlength="10" inputmode="numeric"></div></div>
						<div class="col-md-6"><div class="edu_field_holder">
							<select id="upp_user_type" class="edu_form_field">
								<option value="student">Student</option>
								<option value="teacher">Teacher</option>
								<!-- <option value="institute">Institute</option> -->
							</select>
						</div>
						<option value="institute">Institute</option></select></div></div>
						<div class="col-12"><div class="edu_field_holder"><input id="upp_password" class="edu_form_field" type="password" placeholder="New Password"></div></div>
						<div class="col-12"><div class="edu_field_holder"><input id="upp_confirm_password" class="edu_form_field" type="password" placeholder="Confirm Password"></div></div>
						<div class="col-12 mt-2 text-md-right"><button id="upp_submit_btn" type="button" class="edu_btn edu_btn_black">Update Password</button></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<script>
(function () {
	var url = <?php echo json_encode(site_url('update-password-submit')); ?>;
	function ok(v) { return v === true || v === 'true' || v === 1 || v === '1'; }
	document.addEventListener('DOMContentLoaded', function () {
		var btn = document.getElementById('upp_submit_btn');
		if (!btn) { return; }
		btn.addEventListener('click', function () {
			var payload = {
				mobile: (document.getElementById('upp_mobile').value || '').trim().replace(/\D/g, ''),
				user_type: (document.getElementById('upp_user_type').value || 'student').toLowerCase(),
				password: document.getElementById('upp_password').value || '',
				confirm_password: document.getElementById('upp_confirm_password').value || ''
			};
			fetch(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify(payload)
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (ok(j.status)) {
					if (typeof toastr !== 'undefined') { toastr.success(j.msg || j.message || 'Password updated'); }
				} else if (typeof toastr !== 'undefined') {
					toastr.error(j.msg || j.message || 'Password update failed');
				}
			}).catch(function () {
				if (typeof toastr !== 'undefined') { toastr.error('Network error.'); }
			});
		});
	});
})();
</script>
