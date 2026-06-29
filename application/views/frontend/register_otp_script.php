<script>
(function () {
	var signupUrl = <?php echo json_encode(site_url('api/user/signup')); ?>;
	/* Same endpoint as login OTP verify: proxies API and creates CI web session (web_session). */
	var verifyUrl = <?php echo json_encode(site_url('login-otp-verify')); ?>;
	var mobile = '';
	var userType = '';

	function okStatus(j) {
		return j.status === true || j.status === 'true' || j.status === 1 || j.status === '1';
	}
	function okVerifyOtp(j) {
		return j.status === true || j.status === 'true' || j.status === '1' || j.status === 1;
	}

	function showStep1() {
		document.getElementById('registerStep1').classList.remove('d-none');
		document.getElementById('registerStep2').classList.add('d-none');
		var hint = document.getElementById('registerStepHint');
		if (hint) { hint.textContent = 'Step 1: enter your details, then request OTP.'; }
	}

	function showStep2() {
		document.getElementById('registerStep1').classList.add('d-none');
		document.getElementById('registerStep2').classList.remove('d-none');
		var hint = document.getElementById('registerStepHint');
		if (hint) { hint.textContent = 'Step 2: enter the OTP sent to your mobile, then verify.'; }
	}

	document.addEventListener('DOMContentLoaded', function () {
		var pw = document.getElementById('reg_password');
		var pwIcon = document.querySelector('.reg_pw_toggle');
		if (pwIcon && pw) {
			pwIcon.addEventListener('click', function () {
				pwIcon.classList.toggle('fa-eye');
				pwIcon.classList.toggle('fa-eye-slash');
				pw.type = pw.type === 'password' ? 'text' : 'password';
			});
		}

		document.getElementById('reg_back_step_btn').addEventListener('click', showStep1);

		document.getElementById('reg_send_otp_btn').addEventListener('click', function () {
			var name = (document.getElementById('reg_name').value || '').trim();
			var lastNameEl = document.getElementById('reg_last_name');
			var lastName = lastNameEl ? (lastNameEl.value || '').trim() : '';
			var email = (document.getElementById('reg_email').value || '').trim();
			var mob = (document.getElementById('reg_mobile').value || '').trim().replace(/\D/g, '');
			var password = document.getElementById('reg_password').value || '';
			userType = (document.getElementById('reg_user_type').value || 'student').toLowerCase();

			if (!name || !email || !password) {
				if (typeof toastr !== 'undefined') { toastr.error('Please fill name, email and password.'); }
				return;
			}
			if (name.length > 10) {
				if (typeof toastr !== 'undefined') { toastr.error('First name must be at most 10 characters.'); }
				return;
			}
			if (lastName.length > 10) {
				if (typeof toastr !== 'undefined') { toastr.error('Last name must be at most 10 characters.'); }
				return;
			}
			if (mob.length !== 10) {
				if (typeof toastr !== 'undefined') { toastr.error('Please enter a valid 10-digit mobile number.'); }
				return;
			}

			fetch(signupUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify({
					name: name,
					last_name: lastName,
					email: email,
					mobile: mob,
					password: password,
					user_type: userType
				})
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (okStatus(j)) {
					mobile = mob;
					if (typeof toastr !== 'undefined') { toastr.success(j.msg || 'Account created. Enter OTP to verify.'); }
					showStep2();
					document.getElementById('reg_otp').value = '';
					document.getElementById('reg_otp').focus();
				} else {
					if (typeof toastr !== 'undefined') { toastr.error(j.msg || j.message || 'Registration failed'); }
				}
			}).catch(function () {
				if (typeof toastr !== 'undefined') { toastr.error('Network error. Please try again.'); }
			});
		});

		document.getElementById('reg_verify_otp_btn').addEventListener('click', function () {
			var otp = (document.getElementById('reg_otp').value || '').trim();
			if (!mobile || otp.length < 4) {
				if (typeof toastr !== 'undefined') { toastr.error('Please enter the OTP.'); }
				return;
			}
			fetch(verifyUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify({ mobile: mobile, otp: otp, user_type: userType })
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (okVerifyOtp(j)) {
					var ws = j.web_session;
					if (ws && ws.url && (ws.status === 1 || ws.status === '1')) {
						if (typeof toastr !== 'undefined') { toastr.success(ws.msg || j.msg || 'Logged in'); }
						setTimeout(function () { window.location.href = ws.url; }, 600);
					} else {
						if (typeof toastr !== 'undefined') { toastr.warning((j.msg || 'Verified.') + ' Please sign in from the login page.'); }
						setTimeout(function () { window.location.href = <?php echo json_encode(site_url('login')); ?>; }, 800);
					}
				} else {
					if (typeof toastr !== 'undefined') { toastr.error(j.msg || j.message || 'Invalid OTP'); }
				}
			}).catch(function () {
				if (typeof toastr !== 'undefined') { toastr.error('Network error. Please try again.'); }
			});
		});
	});
})();
</script>
