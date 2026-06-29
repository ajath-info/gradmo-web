<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main style="max-width:520px;margin:40px auto;padding:24px;border:1px solid #ddd;border-radius:8px;">
	<h1 style="margin:0 0 16px 0;"><?php echo isset($title) ? html_escape($title) : 'Register'; ?></h1>
	<form id="registerForm" method="post" action="<?php echo site_url('register'); ?>">
		<div style="margin-bottom:12px;">
			<label for="name">Full Name</label>
			<input id="name" name="name" type="text" required style="width:100%;padding:10px;margin-top:6px;" />
		</div>
		<div style="margin-bottom:12px;">
			<label for="email">Email</label>
			<input id="email" name="email" type="email" required style="width:100%;padding:10px;margin-top:6px;" />
		</div>
		<div style="margin-bottom:12px;">
			<label for="mobile">Mobile Number</label>
			<input id="mobile" name="mobile" type="text" maxlength="10" pattern="[0-9]{10}" required style="width:100%;padding:10px;margin-top:6px;" />
		</div>
		<div style="margin-bottom:12px;">
			<label for="password">Password</label>
			<input id="password" name="password" type="password" required style="width:100%;padding:10px;margin-top:6px;" />
		</div>
		<div style="margin-bottom:16px;">
			<label for="user_type">User Type</label>
			<select id="user_type" name="user_type" required style="width:100%;padding:10px;margin-top:6px;">
				<option value="">Select user type</option>
				<option value="student">Student</option>
				<option value="teacher">Teacher</option>
				<option value="institute">Institute</option>
			</select>
		</div>
		<div id="otpWrap" style="display:none;margin-bottom:16px;">
			<label for="otp">OTP</label>
			<input id="otp" name="otp" type="text" maxlength="6" pattern="[0-9]{4,6}" style="width:100%;padding:10px;margin-top:6px;" />
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<button id="signupBtn" type="submit" style="padding:10px 16px;">Create Account</button>
			<button id="verifyOtpBtn" type="button" style="padding:10px 16px;display:none;">Verify OTP & Login</button>
		</div>
	</form>
	<p id="registerMsg" style="margin-top:14px;"></p>
</main>
<script>
(function () {
	var form = document.getElementById('registerForm');
	var msg = document.getElementById('registerMsg');
	var otpWrap = document.getElementById('otpWrap');
	var otpInput = document.getElementById('otp');
	var verifyOtpBtn = document.getElementById('verifyOtpBtn');
	var signupBtn = document.getElementById('signupBtn');
	var step = 'signup';
	if (!form) return;

	function saveAndRedirect(data) {
		var loginData = data.data || {};
		try {
			if (loginData.access_token) localStorage.setItem('accessToken', loginData.access_token);
			if (loginData.userType) localStorage.setItem('userType', loginData.userType);
			if (loginData.id) localStorage.setItem('userId', String(loginData.id));
		} catch (err) {}
		setTimeout(function () {
			window.location.href = '<?php echo site_url("index"); ?>';
		}, 600);
	}

	function postAction(action) {
		msg.style.color = '#555';
		msg.textContent = 'Please wait...';
		var formData = new FormData(form);
		formData.set('action', action);

		return fetch(form.action, {
			method: 'POST',
			body: formData,
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
		.then(function (res) { return res.json(); });
	}

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		if (step !== 'signup') return;

		postAction('signup')
		.then(function (data) {
			var ok = String(data.status) === 'true';
			msg.style.color = ok ? 'green' : 'red';
			msg.textContent = data.msg || (ok ? 'Registration successful' : 'Registration failed');
			if (!ok) return Promise.reject(new Error('signup-failed'));
			return postAction('send_otp');
		})
		.then(function (otpData) {
			var ok = String(otpData.status) === 'true';
			msg.style.color = ok ? 'green' : 'red';
			msg.textContent = otpData.msg || (ok ? 'OTP sent' : 'Failed to send OTP');
			if (!ok) return;
			step = 'verify_otp';
			otpWrap.style.display = 'block';
			verifyOtpBtn.style.display = 'inline-block';
			signupBtn.style.display = 'none';
			otpInput.required = true;
			otpInput.focus();
		})
		.catch(function () {
			if (msg.textContent === 'Please wait...') {
				msg.style.color = 'red';
				msg.textContent = 'Request failed. Please try again.';
			}
		});
	});

	verifyOtpBtn.addEventListener('click', function () {
		if (!otpInput.value.trim()) {
			msg.style.color = 'red';
			msg.textContent = 'Please enter OTP first.';
			return;
		}
		postAction('verify_otp')
		.then(function (data) {
			var ok = String(data.status) === 'true';
			msg.style.color = ok ? 'green' : 'red';
			msg.textContent = data.msg || (ok ? 'Verification successful' : 'OTP verification failed');
			if (!ok) return;
			saveAndRedirect(data);
		})
		.catch(function () {
			msg.style.color = 'red';
			msg.textContent = 'Request failed. Please try again.';
		});
	});
})();
</script>
