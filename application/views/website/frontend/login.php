<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main style="max-width:480px;margin:40px auto;padding:24px;border:1px solid #ddd;border-radius:8px;">
	<h1 style="margin:0 0 16px 0;"><?php echo isset($title) ? html_escape($title) : 'Login'; ?></h1>
	<div style="display:flex;gap:8px;margin-bottom:14px;">
		<button type="button" id="modePassword" style="padding:8px 12px;">Password Login</button>
		<button type="button" id="modeOtp" style="padding:8px 12px;">OTP Login</button>
	</div>
	<form id="loginForm" method="post" action="<?php echo site_url('login'); ?>">
		<div id="emailWrap" style="margin-bottom:12px;">
			<label for="username">Email</label>
			<input id="username" name="username" type="email" required style="width:100%;padding:10px;margin-top:6px;" />
		</div>
		<div id="passwordWrap" style="margin-bottom:12px;">
			<label for="password">Password</label>
			<input id="password" name="password" type="password" required style="width:100%;padding:10px;margin-top:6px;" />
		</div>
		<div id="mobileWrap" style="margin-bottom:12px;display:none;">
			<label for="mobile">Mobile Number</label>
			<input id="mobile" name="mobile" type="text" maxlength="10" pattern="[0-9]{10}" style="width:100%;padding:10px;margin-top:6px;" />
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
		<div id="otpWrap" style="margin-bottom:16px;display:none;">
			<label for="otp">OTP</label>
			<input id="otp" name="otp" type="text" maxlength="6" pattern="[0-9]{4,6}" style="width:100%;padding:10px;margin-top:6px;" />
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<button id="passwordLoginBtn" type="submit" style="padding:10px 16px;">Login</button>
			<button id="sendOtpBtn" type="button" style="padding:10px 16px;display:none;">Send OTP</button>
			<button id="verifyOtpBtn" type="button" style="padding:10px 16px;display:none;">Verify OTP & Login</button>
		</div>
	</form>
	<p id="loginMsg" style="margin-top:14px;"></p>
</main>
<script>
(function () {
	var form = document.getElementById('loginForm');
	var msg = document.getElementById('loginMsg');
	var modePassword = document.getElementById('modePassword');
	var modeOtp = document.getElementById('modeOtp');
	var emailWrap = document.getElementById('emailWrap');
	var passwordWrap = document.getElementById('passwordWrap');
	var mobileWrap = document.getElementById('mobileWrap');
	var otpWrap = document.getElementById('otpWrap');
	var sendOtpBtn = document.getElementById('sendOtpBtn');
	var verifyOtpBtn = document.getElementById('verifyOtpBtn');
	var passwordLoginBtn = document.getElementById('passwordLoginBtn');
	var username = document.getElementById('username');
	var password = document.getElementById('password');
	var mobile = document.getElementById('mobile');
	var otp = document.getElementById('otp');
	var mode = 'password';
	if (!form) return;

	function setMode(nextMode) {
		mode = nextMode;
		var otpMode = (mode === 'otp');
		emailWrap.style.display = otpMode ? 'none' : 'block';
		passwordWrap.style.display = otpMode ? 'none' : 'block';
		mobileWrap.style.display = otpMode ? 'block' : 'none';
		otpWrap.style.display = 'none';
		sendOtpBtn.style.display = otpMode ? 'inline-block' : 'none';
		verifyOtpBtn.style.display = 'none';
		passwordLoginBtn.style.display = otpMode ? 'none' : 'inline-block';
		username.required = !otpMode;
		password.required = !otpMode;
		mobile.required = otpMode;
		otp.required = false;
		msg.textContent = '';
	}

	function handleLoginSuccess(data) {
		var loginData = data.data || {};
		try {
			if (loginData.access_token) localStorage.setItem('accessToken', loginData.access_token);
			if (loginData.userType) localStorage.setItem('userType', loginData.userType);
			if (loginData.id) localStorage.setItem('userId', String(loginData.id));
		} catch (err) {}
		setTimeout(function () {
			window.location.href = '<?php echo site_url("index"); ?>';
		}, 500);
	}

	function postAction(action) {
		msg.textContent = 'Please wait...';
		msg.style.color = '#555';
		var formData = new FormData(form);
		formData.set('action', action);

		fetch(form.action, {
			method: 'POST',
			body: formData,
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
		.then(function (res) { return res.json(); })
		.then(function (data) {
			var ok = String(data.status) === 'true';
			msg.style.color = ok ? 'green' : 'red';
			msg.textContent = data.msg || (ok ? 'Success' : 'Login failed');
			if (!ok) return;

			if (action === 'send_otp') {
				otpWrap.style.display = 'block';
				verifyOtpBtn.style.display = 'inline-block';
				sendOtpBtn.textContent = 'Resend OTP';
				otp.required = true;
				otp.focus();
				return;
			}

			handleLoginSuccess(data);
		})
		.catch(function () {
			msg.style.color = 'red';
			msg.textContent = 'Request failed. Please try again.';
		});
	}

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		postAction('login_password');
	});

	sendOtpBtn.addEventListener('click', function () {
		postAction('send_otp');
	});

	verifyOtpBtn.addEventListener('click', function () {
		if (!otp.value.trim()) {
			msg.style.color = 'red';
			msg.textContent = 'Please enter OTP first.';
			return;
		}
		postAction('verify_otp');
	});

	modePassword.addEventListener('click', function () { setMode('password'); });
	modeOtp.addEventListener('click', function () { setMode('otp'); });
	setMode('password');
})();
</script>
