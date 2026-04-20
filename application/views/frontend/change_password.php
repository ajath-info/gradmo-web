<section class="edu_page_title_wrapper">
	<div class="container">
		<div class="row">
			<div class="col-12 text-center">
				<div class="edu_page_title_text">
					<h1>Change Password</h1>
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
					<h4>Change password for current account</h4>
					<div class="row">
						<div class="col-12"><div class="edu_field_holder"><input id="cp_current_password" class="edu_form_field" type="password" placeholder="Current Password"></div></div>
						<div class="col-12"><div class="edu_field_holder"><input id="cp_new_password" class="edu_form_field" type="password" placeholder="New Password"></div></div>
						<div class="col-12"><div class="edu_field_holder"><input id="cp_confirm_password" class="edu_form_field" type="password" placeholder="Confirm New Password"></div></div>
						<div class="col-12 mt-2 text-md-right"><button id="cp_submit_btn" type="button" class="edu_btn edu_btn_black">Change Password</button></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<script>
(function () {
	var url = <?php echo json_encode(site_url('change-password-submit')); ?>;
	function ok(v) { return v === true || v === 'true' || v === 1 || v === '1'; }
	document.addEventListener('DOMContentLoaded', function () {
		var btn = document.getElementById('cp_submit_btn');
		if (!btn) { return; }
		btn.addEventListener('click', function () {
			var payload = {
				current_password: document.getElementById('cp_current_password').value || '',
				new_password: document.getElementById('cp_new_password').value || '',
				confirm_password: document.getElementById('cp_confirm_password').value || ''
			};
			fetch(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify(payload)
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (ok(j.status)) {
					if (typeof toastr !== 'undefined') { toastr.success(j.msg || j.message || 'Password changed'); }
				} else if (typeof toastr !== 'undefined') {
					toastr.error(j.msg || j.message || 'Password change failed');
				}
			}).catch(function () {
				if (typeof toastr !== 'undefined') { toastr.error('Network error.'); }
			});
		});
	});
})();
</script>
