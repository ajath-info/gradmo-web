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
					<h4>Change your password</h4>
					<div class="row">
						<div class="col-12"><div class="edu_field_holder"><input id="cp_current" class="edu_form_field" type="password" placeholder="Current Password" autocomplete="current-password"></div></div>
						<div class="col-12"><div class="edu_field_holder"><input id="cp_new" class="edu_form_field" type="password" placeholder="New Password" autocomplete="new-password"></div></div>
						<div class="col-12"><div class="edu_field_holder"><input id="cp_confirm" class="edu_form_field" type="password" placeholder="Confirm New Password" autocomplete="new-password"></div></div>
						<div class="col-12 mt-2 text-md-right"><button id="cp_submit_btn" type="button" class="edu_btn edu_btn_black">Update Password</button></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<script>
(function () {
	// Call the shared API directly from the browser (logged-in user) with the session token.
	var url = <?php echo json_encode(isset($change_password_url) ? $change_password_url : ''); ?>;
	var accessToken = <?php echo json_encode(isset($api_access_token) ? $api_access_token : ''); ?>;
	var loginUrl = <?php echo json_encode(isset($login_url) ? $login_url : ''); ?>;
	function ok(v) { return v === true || v === 'true' || v === 1 || v === '1'; }
	function notifyOk(m) { if (typeof toastr !== 'undefined') { toastr.success(m); } else { alert(m); } }
	function notifyErr(m) { if (typeof toastr !== 'undefined') { toastr.error(m); } else { alert(m); } }
	document.addEventListener('DOMContentLoaded', function () {
		var btn = document.getElementById('cp_submit_btn');
		if (!btn) { return; }
		btn.addEventListener('click', function () {
			var current = document.getElementById('cp_current').value || '';
			var np = document.getElementById('cp_new').value || '';
			var cp = document.getElementById('cp_confirm').value || '';
			if (current === '' || np === '' || cp === '') { notifyErr('All fields are required.'); return; }
			if (np.length < 6) { notifyErr('New password must be at least 6 characters.'); return; }
			if (np !== cp) { notifyErr('New password and confirm password do not match.'); return; }
			var payload = {
				current_password: current,
				new_password: np,
				confirm_password: cp
			};
			if (accessToken) { payload.access_token = accessToken; }
			var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
			if (accessToken) { headers.Authorization = 'Bearer ' + accessToken; }
			btn.disabled = true;
			fetch(url, {
				method: 'POST',
				headers: headers,
				body: JSON.stringify(payload)
			}).then(function (r) { return r.json(); }).then(function (j) {
				btn.disabled = false;
				if (ok(j.status)) {
					notifyOk(j.msg || j.message || 'Password changed successfully.');
					document.getElementById('cp_current').value = '';
					document.getElementById('cp_new').value = '';
					document.getElementById('cp_confirm').value = '';
				} else {
					notifyErr(j.msg || j.message || 'Password update failed.');
				}
			}).catch(function () {
				btn.disabled = false;
				notifyErr('Network error.');
			});
		});
	});
})();
</script>
